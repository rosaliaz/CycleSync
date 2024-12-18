<?php

include_once "./modules/common.php";

class Post extends common{
        
    protected $pdo;
        
     public function __construct(\PDO $pdo){
            $this->pdo = $pdo;
         }
      
    public function postCycleAndOvulation($body) {
    try {
        $this->ensureFieldsExist($body, ['username', 'cycleStart', 'CycleEnd', 'cycleLength', 'cycleDuration', 'flowIntensity']);

        $username = $body['username'];
        $cycleStart = $body['cycleStart'];
        $cycleEnd = $body['CycleEnd'];
        $cycleLength = $body['cycleLength'];
        $cycleDuration = $body['cycleDuration'];
        $flowIntensity = $body['flowIntensity'];

        // Get user ID by username
        $userid = $this->getUserIdByUsername($username);

        // Validate and prevent duplicate entries
        if ($this->isDuplicateCycle($userid, $cycleStart)) {
            $this->logger($username, 'ERROR', "Duplicate cycle entry for username $username in the same month");
            return ["errmsg" => "A cycle entry for this month already exists.", "code" => 400];
        }

        // Insert cycle data
        $cycleId = $this->insertCycle($userid, $cycleStart, $cycleEnd, $cycleLength, $cycleDuration, $flowIntensity);

        // Calculate predictions
        $predictions = $this->calculatePredictions($cycleStart, $cycleLength, $cycleDuration);

        // Update cycle predictions
        $this->updateCyclePredictions($predictions, ['cycleId' => $cycleId]);

        // Insert ovulation data
        $this->insertOvulation($userid, $cycleId, $predictions);

        $this->logger($username, 'POST', "New cycle and ovulation entry created for username $username");

        return [
            "data" => $predictions,
            "code" => 200
        ];

    } catch (PDOException $e) {
        $errmsg = $e->getMessage();
        $this->logger($username ?? 'Unknown', 'ERROR', "SQL Error: $errmsg");
        return ["errmsg" => $errmsg, "code" => 400];
    } catch (Exception $e) {
        return ["errmsg" => $e->getMessage(), "code" => 400];
    }
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
    
            // Prepare data for insertion
            $data = [
                'userid' => $userid,
                'height' => $height,
                'weight' => $weight,
                'BMI' => $bmi,
                'timestamp' => date('Y-m-d H:i:s')
            ];
    
            // Insert health data
            $this->storeData('health_metric', $data);
    
            $this->logger($username, 'POST', "New health entry created for username $username");
            return ["data" => compact('username', 'height', 'weight', 'bmi'), "code" => 200];
    
        } catch (PDOException $e) {
            $errmsg = $e->getMessage();
            $this->logger($username ?? 'Unknown', 'ERROR', "SQL Error: $errmsg");
            return ["errmsg" => $errmsg, "code" => 400];
        } catch (Exception $e) {
            $errmsg = $e->getMessage();
            $this->logger($username ?? 'Unknown', 'ERROR', "General Error: $errmsg");
            return ["errmsg" => $errmsg, "code" => 400];
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
    
            // Prepare data for insertion
            $data = [
                'userId' => $userid,
                'date_log' => date('Y-m-d H:i:s'),
                'symptomType' => $symptomType,
                'severity' => $severity
            ];
    
            // Insert symptom data
            $this->storeData('symptom_tbl', $data);
    
            $this->logger($username, 'POST', "New symptom entry created for username $username");
            return ["data" => null, "code" => 200];
    
        } catch (PDOException $e) {
            $errmsg = $e->getMessage();
            $this->logger($username ?? 'Unknown', 'ERROR', "SQL Error: $errmsg");
            return ["errmsg" => $errmsg, "code" => 400];
        } catch (Exception $e) {
            $errmsg = $e->getMessage();
            $this->logger($username ?? 'Unknown', 'ERROR', "General Error: $errmsg");
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
    
            // Prepare data for insertion
            $data = [
                'userid' => $userid,
                'message' => $message,
                'notifDate' => $notifDate,
                'notifTime' => $notifTime,
                'isSent' => $isSent
            ];
    
            // Insert notification data
            $this->storeData('notification_tbl', $data);
    
            $this->logger($username, 'POST', "New notification created for username $username");
            return ["data" => null, "code" => 200];
    
        } catch (PDOException $e) {
            $errmsg = $e->getMessage();
            $this->logger($username ?? 'Unknown', 'ERROR', "SQL Error: $errmsg");
            return ["errmsg" => $errmsg, "code" => 400];
        } catch (Exception $e) {
            $errmsg = $e->getMessage();
            $this->logger($username ?? 'Unknown', 'ERROR', "General Error: $errmsg");
            return ["errmsg" => $errmsg, "code" => 400];
        }
    }
}
?>
