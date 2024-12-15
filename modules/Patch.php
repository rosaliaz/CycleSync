<?php
require_once "./modules/common.php";

class Patch extends common{
    protected $pdo;
    public function __construct(\PDO $pdo){
        $this->pdo = $pdo;
    }

    public function patchAccounts($body, $id) {
        $values = array_values($body);
        $values[] = $id; // Add the user ID to the values array
        $sqlString = "UPDATE accounts SET userName=?, email=?, password=?, token=? WHERE userid=?";
        return $this->runQuery($sqlString, $values);
    }

    public function patchCycle($body, $id) {
        $body = (array) $body; // Ensure $body is an array
        $columns = [];
        $values = [];
    
        // Construct the query dynamically based on provided fields
        foreach ($body as $key => $value) {
            $columns[] = "$key = ?";
            $values[] = $value;
        }
    
        // If no columns are provided, return an error
        if (empty($columns)) {
            return ["errmsg" => "No fields provided for update.", "code" => 400];
        }
    
        // Add the cycle ID as the last parameter
        $values[] = $id;
    
        // Create the dynamic query
        $sqlString = "UPDATE cycle_tbl SET " . implode(', ', $columns) . " WHERE cycleid = ?";
    
        // Execute the query
        $this->runQuery($sqlString, $values);
    
        // Fetch the updated cycle data
        $query = "SELECT userid, cycleStart, cycleLength, cycleDuration FROM cycle_tbl WHERE cycleid = ?";
        $stmt = $this->runQuery($query, [$id]);
        $cycle = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($cycle) {
            // Recalculate predictions
            $predictions = $this->calculatePredictions($cycle['cycleStart'], $cycle['cycleLength'], $cycle['cycleDuration']);
    
            $updateCycleQuery = "UPDATE cycle_tbl 
                                SET predictedCycleStart = :predictedCycleStart, 
                                    predictedCycleEnd = :predictedCycleEnd 
                                WHERE cycleid = :cycleId";
            $this->runQuery($updateCycleQuery, [
                    ':predictedCycleStart' => $predictions['predictedCycleStart'],
                    ':predictedCycleEnd' => $predictions['predictedCycleEnd'],
                    ':cycleId' => $id
            ]);

           // Update predictions in ovulation_tbl
            $updateOvulationQuery = "UPDATE ovulation_tbl 
                                     SET next_fertile_start = :nextFertileStart, 
                                         predicted_ovulation_date = :predictedOvulationDate 
                                    WHERE cycleId = :cycleId";
            $this->runQuery($updateOvulationQuery, [
                ':nextFertileStart' => $predictions['nextFertileStart'],
                ':predictedOvulationDate' => $predictions['predictedOvulationDate'],
                ':cycleId' => $id
        ]);
    
            $this->logger($cycle['userid'], 'PATCH', "Cycle and predictions updated for cycleId $id");
    
            // Return updated predictions in the response
            return [
                "message" => "Cycle updated successfully.",
                "predictions" => $predictions,
                "code" => 200
            ];
        }
    
        return ["errmsg" => "Cycle not found or unable to recalculate.", "code" => 400];
    }

    public function archiveSymptom($id) {
        $sqlString = "UPDATE symptom_tbl SET isDeleted=1 WHERE symptomId=?";
        return $this->runQuery($sqlString, [$id]);
    }

    public function archiveHealth($id) {
        $sqlString = "UPDATE health_metric SET isDeleted=1 WHERE metricId=?";
        return $this->runQuery($sqlString, [$id]);
    }

    public function archiveNotification($id) {
        $sqlString = "UPDATE notification_tbl SET isDeleted=1 WHERE notificationId=?";
        return $this->runQuery($sqlString, [$id]);
    }
    
}
?>
