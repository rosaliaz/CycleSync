<?php

require_once "./modules/common.php";

class Patch extends common{


    protected $pdo;
    public function __construct(\PDO $pdo){
        $this->pdo = $pdo;
    }

    public function patchAccounts($body, $id) {
        return $this->patchData('accounts', $body, 'userid', $id);
    }

    public function patchSymptom($body, $id) {
        return $this->patchData('symptom_tbl', $body, 'symptomId', $id);
    }

    public function patchNotification($body, $id) {
        return $this->patchData('notification_tbl', $body, 'notificationId', $id);
    }

    public function patchHealth($body, $id) {
        // Define the table name and ID column
        $table = 'health_metric';
        $idColumn = 'metricId';
    
        // Ensure at least one field is being updated
        if (empty($body['height']) && empty($body['weight'])) {
            return ["errmsg" => "No valid fields to update.", "code" => 400];
        }
    
        // Update the provided fields in the database
        $updateResult = $this->patchData($table, $body, $idColumn, $id);
        if ($updateResult['code'] !== 200) {
            return $updateResult;
        }
    
        // Fetch the complete record to retrieve missing fields
        $query = "SELECT height, weight FROM $table WHERE $idColumn = ?";
        $record = $this->fetchOne($query, [$id]);
    
        if (!$record) {
            return ["errmsg" => "Health record not found.", "code" => 404];
        }
    
        // Use the updated values from $body and fall back to the database values for missing fields
        $height = floatval($body['height'] ?? $record['height']);
        $weight = floatval($body['weight'] ?? $record['weight']);
    
        // Validate that height is not zero before calculating BMI
        if ($height <= 0) {
            return ["errmsg" => "Height must be greater than 0 to calculate BMI.", "code" => 400];
        }
    
        // Calculate and update BMI
        $bmi = $this->calculateBMI($height, $weight);
    
        $bmiUpdateQuery = "UPDATE $table SET bmi = ? WHERE $idColumn = ?";
        $this->runQuery($bmiUpdateQuery, [$bmi, $id]);
    
        $this->logger(null, 'DEBUG', "Updated BMI for metricId $id: $bmi");
    
        return ["message" => "Health metric updated successfully.", "bmi" => $bmi, "code" => 200];
    }

    public function patchCycle($body, $id) {
        try {
            // Update the cycle_tbl using patchData
            $updateResult = $this->patchData('cycle_tbl', $body, 'cycleid', $id);
    
            if ($updateResult['code'] !== 200) {
                return $updateResult; // Return if update fails
            }
    
            // Fetch the updated cycle data
            $cycle = $this->fetchOne(
                "SELECT userid, cycleStart, cycleLength, cycleDuration FROM cycle_tbl WHERE cycleid = ?",
                [$id]
            );
    
            if (!$cycle) {
                return ["errmsg" => "Cycle not found or unable to fetch data.", "code" => 400];
            }
    
            // Recalculate predictions
            $predictions = $this->calculatePredictions(
                $cycle['cycleStart'],
                $cycle['cycleLength'],
                $cycle['cycleDuration']
            );
    
            // Update cycle predictions
            $this->updateCyclePredictions($predictions, ['cycleid' => $id]);
    
            // Update ovulation predictions
            $this->updateOvulationPredictions($predictions, $id);
    
            // Log the successful update
            $this->logger($cycle['userid'], 'PATCH', "Cycle and predictions updated for cycleId $id");
    
            // Return success response
            return [
                "message" => "Cycle updated successfully.",
                "predictions" => $predictions,
                "code" => 200
            ];
        } catch (\Exception $e) {
            // Log and return error details
            $this->logger(null, 'ERROR', "Error in patchCycle: " . $e->getMessage());
            return ["errmsg" => $e->getMessage(), "code" => 400];
        }
    }

    public function archiveSymptom($id) {
        $id = (int)$id;
        $sqlString = "UPDATE symptom_tbl SET isDeleted=1 WHERE symptomId=?";
        $result = $this->runQuery($sqlString, [$id]);
    
        if ($result->rowCount() > 0) {
            return ["message" => "Symptom Data archived successfully.", "code" => 200];
        } else {
            return ["message" => "No symptom found to archive.", "code" => 404];
        }
    }
    
    public function archiveHealth($id) {
        $id = (int)$id;
        $sqlString = "UPDATE health_metric SET isDeleted=1 WHERE metricId=?";
        $result = $this->runQuery($sqlString, [$id]);
    
        if ($result->rowCount() > 0) {
            return ["message" => "Health metric Data archived successfully.", "code" => 200];
        } else {
            return ["message" => "No health metric found to archive.", "code" => 404];
        }
    }
    
    public function archiveNotification($id) {
        $id = (int)$id;
        $sqlString = "UPDATE notification_tbl SET isDeleted=1 WHERE notificationId=?";
        $result = $this->runQuery($sqlString, [$id]);
    
        if ($result->rowCount() > 0) {
            return ["message" => "Notification Data archived successfully.", "code" => 200];
        } else {
            return ["message" => "No notification found to archive.", "code" => 404];
        }
    } 
}
?>
