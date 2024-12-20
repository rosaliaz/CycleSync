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
        return $this->processPostRequest(
            $body,
            'health_metric',
            ['username', 'height', 'weight'],
            function ($data) {
                $data['BMI'] = $this->calculateBMI($data['height'], $data['weight']);
                $data['timestamp'] = date('Y-m-d H:i:s');
                return $data;
            },
            function ($username) {
                $this->logger($username, 'POST', 'Health data successfully added');
            }
        );
    }
    
    public function postSymptom($body) {
        return $this->processPostRequest(
            $body,
            'symptom_tbl',
            ['username', 'symptomType', 'severity'],
            function ($data) {
                $data['date_log'] = date('Y-m-d H:i:s');
                return $data;
            },
            function ($username) {
                $this->logger($username, 'POST', 'Symptom data successfully added');
            }
        );
    }
    
    public function postNotification($body) {
        return $this->processPostRequest(
            $body,
            'notification_tbl',
            ['username', 'message', 'notifDate', 'notifTime', 'isSent'],
            null,
            function ($username) {
                $this->logger($username, 'POST', 'Notification successfully added');
            }
        );
    }
}
?>
