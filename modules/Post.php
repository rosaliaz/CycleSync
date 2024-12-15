<?php

include_once "./modules/common.php";

class Post extends common{
        
    protected $pdo;
        
     public function __construct(\PDO $pdo){
            $this->pdo = $pdo;
         }
      
         public function postCycleAndOvulation($body) {
            try {
                $values = array_values(array_filter($body, fn($key) => !in_array($key, ['cycleId', 'ovulationId']), ARRAY_FILTER_USE_KEY));
                list($userid, $cycleStart, $cycleEnd, $cycleLength, $cycleDuration, $flowIntensity) = $values;
    
                // Prevent duplicate entries
                $monthCheckQuery = "SELECT COUNT(*) as count FROM cycle_tbl WHERE userid = :userid AND MONTH(cycleStart) = MONTH(:cycleStart1) AND YEAR(cycleStart) = YEAR(:cycleStart2)";
                $monthCheckResult = $this->fetchOne($monthCheckQuery, [
                    ':userid' => $userid,
                    ':cycleStart1' => $cycleStart,
                    ':cycleStart2' => $cycleStart
                ]);
    
                if ($monthCheckResult['count'] > 0) {
                    $this->logger($userid, 'ERROR', "Duplicate cycle entry for userId $userid in the same month");
                    return ["errmsg" => "A cycle entry for this month already exists.", "code" => 400];
                }
    
                // Insert cycle data
                $insertCycleQuery = "INSERT INTO cycle_tbl(userid, cycleStart, CycleEnd, cycleLength, cycleDuration, flowIntensity) VALUES (?, ?, ?, ?, ?, ?)";
                $this->runQuery($insertCycleQuery, $values);
    
                // Fetch newly inserted cycleId
                $cycleId = $this->pdo->lastInsertId();
    
                // Calculate predictions
                $predictedCycleStart = date('Y-m-d', strtotime("$cycleStart + $cycleLength days"));
                $predictedCycleEnd = date('Y-m-d', strtotime("$predictedCycleStart + $cycleDuration days"));
                $fertileWindowStart = date('Y-m-d', strtotime("$cycleStart + " . ($cycleLength - 14 - 5) . " days"));
                $ovulationDate = date('Y-m-d', strtotime("$cycleStart + " . ($cycleLength - 14) . " days"));
                $nextFertileStart = date('Y-m-d', strtotime("$fertileWindowStart + $cycleLength days"));
                $predictedOvulationDate = date('Y-m-d', strtotime("$ovulationDate + $cycleLength days"));
    
                // Update cycle predictions
                $updateCycleQuery = "UPDATE cycle_tbl SET predictedCycleStart = :predictedCycleStart, predictedCycleEnd = :predictedCycleEnd WHERE userid = :userId ORDER BY cycleId DESC LIMIT 1";
                $this->runQuery($updateCycleQuery, [
                    ':predictedCycleStart' => $predictedCycleStart,
                    ':predictedCycleEnd' => $predictedCycleEnd,
                    ':userId' => $userid
                ]);
    
                // Insert ovulation data
                $ovulationInsertQuery = "INSERT INTO ovulation_tbl(userId, cycleId, fertile_window_start, ovulationDate) VALUES (:userId, :cycleId, :fertileWindowStart, :ovulationDate)";
                $this->runQuery($ovulationInsertQuery, [
                    ':userId' => $userid,
                    ':cycleId' => $cycleId,
                    ':fertileWindowStart' => $fertileWindowStart,
                    ':ovulationDate' => $ovulationDate
                ]);
    
                // Update ovulation predictions
                $updateOvulationQuery = "UPDATE ovulation_tbl SET next_fertile_start = :nextFertileStart, predicted_ovulation_date = :predictedOvulationDate WHERE userId = :userId ORDER BY ovulationId DESC LIMIT 1";
                $this->runQuery($updateOvulationQuery, [
                    ':nextFertileStart' => $nextFertileStart,
                    ':predictedOvulationDate' => $predictedOvulationDate,
                    ':userId' => $userid
                ]);
    
                $this->logger($userid, 'POST', "New cycle and ovulation entry created for userId $userid");
                return [
                    "data" => compact('fertileWindowStart', 'ovulationDate', 'predictedCycleStart', 'predictedCycleEnd', 'nextFertileStart', 'predictedOvulationDate'),
                    "code" => 200
                ];
    
            } catch (PDOException $e) {
                $errmsg = $e->getMessage();
                $this->logger($userid ?? 'Unknown', 'ERROR', "SQL Error: $errmsg");
                return ["errmsg" => $errmsg, "code" => 400];
            }
        }
    
        public function postHealth($body) {
            try {
                $this->ensureFieldsExist($body, ['userid', 'height', 'weight']);
    
                $userid = $body['userid'];
                $height = (float)$body['height'];
                $weight = (float)$body['weight'];
                $bmi = $this->calculateBMI($height, $weight);
    
                $insertHealthQuery = "INSERT INTO health_metric (userid, height, weight, BMI, timestamp) VALUES (?, ?, ?, ?, NOW())";
                $this->runQuery($insertHealthQuery, [$userid, $height, $weight, $bmi]);
    
                return ["data" => compact('userid', 'height', 'weight', 'bmi'), "code" => 200];
    
            } catch (Exception $e) {
                return ["errmsg" => $e->getMessage(), "code" => 400];
            }
        }
    
        public function postSymptom($body) {
            try {
                $this->ensureFieldsExist($body, ['userId', 'symptomType', 'severity']);
    
                $values = [
                    $body['userId'],
                    $body['symptomType'],
                    $body['severity']
                ];
    
                $sqlString = "INSERT INTO symptom_tbl(userId, date_log, symptomType, severity) VALUES (?, NOW(), ?, ?)";
                $this->runQuery($sqlString, $values);
    
                $this->logger($body['userId'], 'POST', "New symptom entry created for userId {$body['userId']}");
                return ["data" => null, "code" => 200];
    
            } catch (PDOException $e) {
                $errmsg = $e->getMessage();
                $this->logger($body['userId'] ?? 'Unknown', 'ERROR', "SQL Error: $errmsg");
                return ["errmsg" => $errmsg, "code" => 400];
            }
        }
    
        public function postNotification($body) {
            try {
                $this->ensureFieldsExist($body, ['userid', 'message', 'notifDate', 'notifTime', 'isSent']);
    
                $values = [
                    $body['userid'],
                    $body['message'],
                    $body['notifDate'],
                    $body['notifTime'],
                    $body['isSent']
                ];
    
                $sqlString = "INSERT INTO notification_tbl(userid, message, notifDate, notifTime, isSent) VALUES (?, ?, ?, ?, ?)";
                $this->runQuery($sqlString, $values);
    
                $this->logger($body['userid'], 'POST', "New notification created for userId {$body['userid']}");
                return ["data" => null, "code" => 200];
    
            } catch (PDOException $e) {
                $errmsg = $e->getMessage();
                $this->logger($body['userId'] ?? 'Unknown', 'ERROR', "SQL Error: $errmsg");
                return ["errmsg" => $errmsg, "code" => 400];
            }

        }
   }
?>
