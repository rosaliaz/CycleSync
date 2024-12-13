<?php

include_once "Common.php";

class Post extends Common{

    protected $pdo;

    public function __construct(\PDO $pdo){
        $this->pdo = $pdo;
    }

    public function postSymptom($body){
        $result = $this->postData("symptom_tbl", $body, $this->pdo);

        if ($result['code']== 200){
            $this->logger("littlegiant", "POST", "Created a new symptom record.");
            return $this->generateResponse($result['data'], "Successfully created record", "success", $result['code']);
        }
        return $this->generateResponse(null, $result['errmsg'], "failed", $result['code']);
    }

    public function postHealth($body){
        $result = $this->postData("health_metric", $body, $this->pdo);

        if ($result['code']== 200){
            $this->logger("littlegiant", "POST", "Created a new health metric record.");
            return $this->generateResponse($result['data'], "Successfully created record", "success", $result['code']);
        }
        return $this->generateResponse(null, $result['errmsg'], "failed", $result['code']);
    }

    public function postNotification($body){
            $result = $this->postData("notification_tbl", $body, $this->pdo);
    
            if ($result['code']== 200){
                $this->logger("littlegiant", "POST", "Created a new notification record.");
                return $this->generateResponse($result['data'], "Successfully created record", "success", $result['code']);
            }
            return $this->generateResponse(null, $result['errmsg'], "failed", $result['code']);
    /*    $values = [];
        $errmsg = "";
        $code = 0;

        foreach($body as $value){
            array_push($values, $value);
        }

        try{
            $sqlString = "INSERT INTO notification_tbl(notificationId, userid, notificationType, notificationDate, isSent) VALUE (?,?,?,?)";
            $sql = $this->pdo->prepare($sqlString);
            $sql->execute($values);

            $code =200;
            $data = null;

            return array("data"=>$data, "code"=>$code);
        }
        catch(\PDOException $e){
            $errmsg = $e->getMessage();
            $code = 400;
        }
        return array("errmsg"=>$errmsg, "code"=>$code); */
    }

    public function calculateCyclePrediction($userid) {
        try {
            // Fetch user data from the cycle table
            $query = "SELECT cycleStart, cycleLength, cycleDuration 
            FROM cycle_tbl 
            WHERE userId = :userid 
            ORDER BY cycleId DESC 
            LIMIT 1";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return ['error' => 'User data not found'];
            }

            $lastCycleStart = $result['cycleStart'];
            $averageCycleLength = $result['cycleLength'];
            $averageCycleDuration = $result['cycleDuration'];

            // Calculate predicted dates
            $predictedStartDate = date('Y-m-d', strtotime($lastCycleStart . " + $averageCycleLength days"));
            $predictedEndDate = date('Y-m-d', strtotime($predictedStartDate . " + $averageCycleDuration days"));

            return [
                'predictedCycleStart' => $predictedStartDate,
                'predictedCycleEnd' => $predictedEndDate,
            ];
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function postCycle($body) {
        $values = [];
        $errmsg = "";
        $code = 0;
    
        foreach ($body as $value) {
            array_push($values, $value);
        }
    
        try {
            // Insert the new cycle data
            $sqlString = "INSERT INTO cycle_tbl(cycleId, userId, cycleStart, cycleEnd, cycleLength, cycleDuration, flowIntensity) 
                          VALUES (?, ?, ?, ?, ?, ?,?)";
            $sql = $this->pdo->prepare($sqlString);
            $sql->execute($values);
    
            // Fetch the userId from the request body
            $userid = $values[1]; // Assuming userId is the second element in the $values array
    
            // Calculate the next cycle prediction
            $predictions = $this->calculateCyclePrediction($userid);

            // Update the database with the predicted dates
            $updateQuery = "UPDATE cycle_tbl 
            SET predictedCycleStart = :predictedCycleStart, 
                predictedCycleEnd = :predictedCycleEnd 
            WHERE userId = :userId 
            ORDER BY cycleId DESC 
            LIMIT 1";

            $updateStmt = $this->pdo->prepare($updateQuery);
            $updateStmt->bindParam(':predictedCycleStart', $predictions['predictedCycleStart']);
            $updateStmt->bindParam(':predictedCycleEnd', $predictions['predictedCycleEnd']);
            $updateStmt->bindParam(':userId', $userid, PDO::PARAM_INT);
            $updateStmt->execute();

            // Include predictions in the response
            $code = 200;
            $data = $predictions;
    
            return array("data" => $data, "code" => $code);
        } 
        catch (\PDOException $e) {
            $errmsg = $e->getMessage();
            $code = 400;
        }
    
        return array("errmsg" => $errmsg, "code" => $code);
    }


    public function calculateOvulation($userid) {
        try {
            // Fetch the user's ovulation data
            $query = "SELECT ovulationDate, next_fertile_start, predicted_ovulation_date 
                      FROM ovulation_tbl 
                      WHERE userId = :userid 
                      ORDER BY ovulationId DESC 
                      LIMIT 1";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return ['error' => 'User data not found'];
            }

            // Fetch user cycle length
            $cycleQuery = "SELECT cycleLength 
                           FROM cycle_tbl 
                           WHERE userId = :userid";
            $cycleStmt = $this->pdo->prepare($cycleQuery);
            $cycleStmt->bindParam(':userid', $userid, PDO::PARAM_INT);
            $cycleStmt->execute();
            $cycleData = $cycleStmt->fetch(PDO::FETCH_ASSOC);

            if (!$cycleData) {
                return ['error' => 'Cycle data not found'];
            }

            $averageCycleLength = $cycleData['cycleLength'];
            $lastOvulationDate = $result['ovulationDate'];

            // Calculate next predicted ovulation date
            $predictedOvulationDate = date('Y-m-d', strtotime($lastOvulationDate . " + $averageCycleLength days"));

            // Calculate next fertile window
            $nextFertileStart = date('Y-m-d', strtotime($predictedOvulationDate . " - 5 days"));

            return [
                'next_fertile_start' => $nextFertileStart,
                'predicted_ovulation_date' => $predictedOvulationDate,
            ];
        } 
        catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function postOvulation($body) {
        $values = [];
        $errmsg = "";
        $code = 0;
    
        foreach ($body as $value) {
            array_push($values, $value);
        }
    
        try {
            // Insert the new ovulation data
            $sqlString = "INSERT INTO ovulation_tbl(ovulationId, userId, cycleId, fertile_window_start, ovulationDate) 
                          VALUES (?, ?, ?, ?, ?)";
            $sql = $this->pdo->prepare($sqlString);
            $sql->execute($values);
    
            // Fetch the userId from the request body
            $userid = $values[1]; // Assuming userId is the second element in the $values array
    
            // Calculate the next ovulation prediction
            $predictions = $this->calculateOvulation($userid);
    
            // Update the database with the predictions
            $updateQuery = "UPDATE ovulation_tbl 
                            SET next_fertile_start = :nextFertileStart, 
                                predicted_ovulation_date = :predictedOvulationDate 
                            WHERE userId = :userId 
                            ORDER BY ovulationId DESC 
                            LIMIT 1";
            $updateStmt = $this->pdo->prepare($updateQuery);
            $updateStmt->bindParam(':nextFertileStart', $predictions['next_fertile_start']);
            $updateStmt->bindParam(':predictedOvulationDate', $predictions['predicted_ovulation_date']);
            $updateStmt->bindParam(':userId', $userid, PDO::PARAM_INT);
            $updateStmt->execute();
    
            // Return the predictions in the response
            $code = 200;
            $data = $predictions;
    
            return array("data" => $data, "code" => $code);
        } catch (\PDOException $e) {
            $errmsg = $e->getMessage();
            $code = 400;
        }
    
        return array("errmsg" => $errmsg, "code" => $code);
    }
}
?>