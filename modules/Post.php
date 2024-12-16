<?php

include_once "./modules/common.php";

class Post extends common{
        
    protected $pdo;
        
     public function __construct(\PDO $pdo){
            $this->pdo = $pdo;
         }
      
        public function postCycleAndOvulation($body) {
        $values = [];
        $errmsg = "";
        $code = 0;

        foreach ($body as $key => $value) {
            // Exclude identifiers if present
            if (!in_array($key, ['cycleId', 'ovulationId'])) {
                array_push($values, $value);
            }
        }

        try {
            // Map input values
            $username = $values[0];
            $cycleStart = $values[1];
            $cycleEnd = $values[2];
            $cycleLength = $values[3];
            $cycleDuration = $values[4];
            $flowIntensity = $values[5];

            // Fetch the user ID from the username
            $useridQuery = "SELECT userid FROM accounts WHERE username = :username";
            $useridResult = $this->fetchOne($useridQuery, [':username' => $username]);

            if (!$useridResult || !$useridResult['userid']) {
                throw new Exception("Invalid username: $username");
            }

            $userid = $useridResult['userid'];

            // Validate and prevent duplicate entries
            $monthCheckQuery = "SELECT COUNT(*) as count 
                                FROM cycle_tbl 
                                WHERE userid = :userid 
                                AND MONTH(cycleStart) = MONTH(:cycleStart1) 
                                AND YEAR(cycleStart) = YEAR(:cycleStart2)";
            $monthCheckStmt = $this->pdo->prepare($monthCheckQuery);
            $monthCheckStmt->bindParam(':userid', $userid, PDO::PARAM_INT);
            $monthCheckStmt->bindParam(':cycleStart1', $cycleStart);
            $monthCheckStmt->bindParam(':cycleStart2', $cycleStart);
            $monthCheckStmt->execute();
            $monthCheckResult = $monthCheckStmt->fetch(PDO::FETCH_ASSOC);

            if ($monthCheckResult && $monthCheckResult['count'] > 0) {
                $this->logger($username, 'ERROR', "Duplicate cycle entry for username $username in the same month");
                return ["errmsg" => "A cycle entry for this month already exists.", "code" => 400];
            }

            // Insert new cycle data
            $sqlString = "INSERT INTO cycle_tbl(userid, cycleStart, CycleEnd, cycleLength, cycleDuration, flowIntensity) 
                        VALUES (?, ?, ?, ?, ?, ?)";
            $sql = $this->pdo->prepare($sqlString);
            $sql->execute([$userid, $cycleStart, $cycleEnd, $cycleLength, $cycleDuration, $flowIntensity]);

            // Fetch the newly inserted cycleId
            $cycleIdQuery = "SELECT LAST_INSERT_ID() as cycleId";
            $cycleIdStmt = $this->pdo->query($cycleIdQuery);
            $cycleIdResult = $cycleIdStmt->fetch(PDO::FETCH_ASSOC);
            if (!$cycleIdResult || !$cycleIdResult['cycleId']) {
                throw new Exception("Failed to retrieve the cycleId of the newly inserted row.");
            }
            $cycleId = $cycleIdResult['cycleId'];

            // Use calculatePredictions to compute all predictions
            $predictions = $this->calculatePredictions($cycleStart, $cycleLength, $cycleDuration);

            // Update cycle predictions
            $updateCycleQuery = "UPDATE cycle_tbl 
                                SET predictedCycleStart = :predictedCycleStart, 
                                    predictedCycleEnd = :predictedCycleEnd 
                                WHERE userid = :userId 
                                ORDER BY cycleId DESC 
                                LIMIT 1";
            $updateCycleStmt = $this->pdo->prepare($updateCycleQuery);
            $updateCycleStmt->bindParam(':predictedCycleStart', $predictions['predictedCycleStart']);
            $updateCycleStmt->bindParam(':predictedCycleEnd', $predictions['predictedCycleEnd']);
            $updateCycleStmt->bindParam(':userId', $userid, PDO::PARAM_INT);
            $updateCycleStmt->execute();

            // Insert ovulation data
            $ovulationInsertQuery = "INSERT INTO ovulation_tbl(userId, cycleId, fertile_window_start, ovulationDate) 
                                    VALUES (:userId, :cycleId, :fertileWindowStart, :ovulationDate)";
            $ovulationInsertStmt = $this->pdo->prepare($ovulationInsertQuery);
            $ovulationInsertStmt->bindParam(':userId', $userid, PDO::PARAM_INT);
            $ovulationInsertStmt->bindParam(':cycleId', $cycleId, PDO::PARAM_INT);
            $ovulationInsertStmt->bindParam(':fertileWindowStart', $predictions['fertileWindowStart']);
            $ovulationInsertStmt->bindParam(':ovulationDate', $predictions['ovulationDate']);
            $ovulationInsertStmt->execute();

            // Update ovulation predictions
            $updateOvulationQuery = "UPDATE ovulation_tbl 
                                    SET next_fertile_start = :nextFertileStart, 
                                        predicted_ovulation_date = :predictedOvulationDate 
                                    WHERE userId = :userId 
                                    ORDER BY ovulationId DESC 
                                    LIMIT 1";
            $updateOvulationStmt = $this->pdo->prepare($updateOvulationQuery);
            $updateOvulationStmt->bindParam(':nextFertileStart', $predictions['nextFertileStart']);
            $updateOvulationStmt->bindParam(':predictedOvulationDate', $predictions['predictedOvulationDate']);
            $updateOvulationStmt->bindParam(':userId', $userid, PDO::PARAM_INT);
            $updateOvulationStmt->execute();

            $code = 200;
            $data = $predictions;

            $this->logger($username, 'POST', "New cycle and ovulation entry created for username $username");
            return ["data" => $data, "code" => $code];

        } catch (PDOException $e) {
            $errmsg = $e->getMessage();
            $code = 400;
            $this->logger($values[0] ?? 'Unknown', 'ERROR', "SQL Error: $errmsg");
        }

        return ["errmsg" => $errmsg, "code" => $code];
    }

    public function postHealth($body) {
        try {
            $this->ensureFieldsExist($body, ['username', 'height', 'weight']);

            $username = $body['username'];
            $height = (float)$body['height'];
            $weight = (float)$body['weight'];

            // Get user ID by username
            $userid = $this->getUserIdByUsername($username);

            $bmi = $this->calculateBMI($height, $weight);

            $insertHealthQuery = "INSERT INTO health_metric (userid, height, weight, BMI, timestamp) VALUES (?, ?, ?, ?, NOW())";
            $this->runQuery($insertHealthQuery, [$userid, $height, $weight, $bmi]);

            return ["data" => compact('username', 'height', 'weight', 'bmi'), "code" => 200];

        } catch (Exception $e) {
            return ["errmsg" => $e->getMessage(), "code" => 400];
        }
    }

    public function postSymptom($body) {
        try {
            $this->ensureFieldsExist($body, ['username', 'symptomType', 'severity']);

            $username = $body['username'];
            $symptomType = $body['symptomType'];
            $severity = $body['severity'];

            // Get user ID by username
            $userid = $this->getUserIdByUsername($username);

            $sqlString = "INSERT INTO symptom_tbl(userId, date_log, symptomType, severity) VALUES (?, NOW(), ?, ?)";
            $this->runQuery($sqlString, [$userid, $symptomType, $severity]);

            $this->logger($username, 'POST', "New symptom entry created for username $username");
            return ["data" => null, "code" => 200];

        } catch (PDOException $e) {
            $errmsg = $e->getMessage();
            $this->logger($username ?? 'Unknown', 'ERROR', "SQL Error: $errmsg");
            return ["errmsg" => $errmsg, "code" => 400];
        }
    }

    public function postNotification($body) {
        try {
            $this->ensureFieldsExist($body, ['username', 'message', 'notifDate', 'notifTime', 'isSent']);

            $username = $body['username'];
            $message = $body['message'];
            $notifDate = $body['notifDate'];
            $notifTime = $body['notifTime'];
            $isSent = $body['isSent'];

            // Get user ID by username
            $userid = $this->getUserIdByUsername($username);

            $sqlString = "INSERT INTO notification_tbl(userid, message, notifDate, notifTime, isSent) VALUES (?, ?, ?, ?, ?)";
            $this->runQuery($sqlString, [$userid, $message, $notifDate, $notifTime, $isSent]);

            $this->logger($username, 'POST', "New notification created for username $username");
            return ["data" => null, "code" => 200];

        } catch (PDOException $e) {
            $errmsg = $e->getMessage();
            $this->logger($username ?? 'Unknown', 'ERROR', "SQL Error: $errmsg");
            return ["errmsg" => $errmsg, "code" => 400];
        }
     }
   }
?>
