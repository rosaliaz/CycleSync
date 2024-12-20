<?php
class common{
    
    protected function getUserFromHeaders() {
        $headers = getallheaders();
        if (isset($headers['x-auth-user'])) {
            return $headers['x-auth-user'];
        }
        // Fallback if user header is missing
        return 'unknown_user';
    }

    protected function logger($user, $method, $action){
        // datetime, user, method, action . text file .log
        $filename = date("Y-m-d").".log";
        $datetime = date("Y-m-d H:m:s");
        $logMessage = "$datetime,$method,$user,$action" .PHP_EOL; //PHP EOL concat, new line
        file_put_contents("./logs/$filename", $logMessage,FILE_APPEND | LOCK_EX); 
    }
    
    protected function generateInsertString($tablename,$body){
        $keys = array_keys($body);
        $fields = implode(",", $keys);
        $parameter_array = [];
        for ($i = 0; $i <count($keys); $i++){
            $parameter_array[$i] = "?";
        }
        $parameters = implode(',', $parameter_array);
        $sql = "INSERT INTO $tablename($fields) VALUES($parameters)";
        return $sql;
    }

    public function getDataByTable($tableName, $condition, \PDO $pdo){
        $data = array();
        $errmsg = "";
        $code = 0;

        $condition .= " AND isDeleted = 0";
        $sqlString = "SELECT * FROM $tableName WHERE $condition";

        // retrieve records
        try{
            if($result = $pdo->query($sqlString)->fetchAll()){
                foreach($result as $record){
                    array_push($data, $record);
                }
                $result = null;
                $code = 200;
                return array("code"=>$code, "data"=>$data);
            }
            else{
                $errmsg = "No data found";
                $code = 404;
            }
        }
        catch(\PDOException $e){
            $errmsg = $e->getMessage();
            $code = 403;
        }

        return array("code" =>$code, "errmsg"=>$errmsg);
    } 

    public function generateResponse($data, $message, $remarks, $statusCode){
        $status = array(
            "message"=> $message,
            "remarks"=> $remarks
        );

        http_response_code($statusCode);

        return array(
            "payload"=> $data,
            "status"=>$status,
            "date_generated" => date_create()
        );
    }

    protected function patchData($table, $body, $idColumn, $id) {
        if (empty($body)) {
            return ["errmsg" => "No fields to update", "code" => 400];
        }

        $checkQuery = "SELECT COUNT(*) as count FROM $table WHERE $idColumn = ? AND isDeleted = 0";
        $checkResult = $this->fetchOne($checkQuery, [$id]);

        if (!$checkResult || $checkResult['count'] === 0) {
            return ["errmsg" => "Cannot update archived data.", "code" => 403];
        }

        $columns = array_keys($body);
        $values = array_values($body);
        $values[] = $id;
    
        $placeholders = implode(", ", array_map(fn($col) => "$col=?", $columns));
        $sqlString = "UPDATE $table SET $placeholders WHERE $idColumn=?";
    
        try {
            $sql = $this->pdo->prepare($sqlString);
            $sql->execute($values);
    
            return ["data" => null, "code" => 200];
        } catch (\PDOException $e) {
            // Log the error using the logger
            $this->logger(null, 'ERROR', "Failed to update table $table: " . $e->getMessage());
            return ["errmsg" => $e->getMessage(), "code" => 400];
        }
    }         

    protected function processPostRequest($body, $tableName, $requiredFields, $dataCallback, $logMessage) {
        try {
            $this->ensureFieldsExist($body, $requiredFields);
    
            $username = $body['username'];
            unset($body['username']); // Remove username for insertion
    
            // Get user ID by username
            $userid = $this->getUserIdByUsername($username);
            $body['userid'] = $userid; // Add userid to the data
    
            // Apply any additional data transformations
            if ($dataCallback !== null) {
                $body = $dataCallback($body);
            }
    
            // Insert data into the specified table
            $this->storeData($tableName, $body);
    
            // Log the successful action
            $this->logger($username, 'POST', "$logMessage for username $username");
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

    protected function runQuery(string $query, array $params = []) {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logger(null, 'ERROR', "SQL Error: " . $e->getMessage());
            throw $e;
        }
    }

    protected function fetchOne(string $query, array $params = []) {
        $stmt = $this->runQuery($query, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    protected function ensureFieldsExist(array $data, array $requiredFields) {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
    }

    protected function calculateBMI($height, $weight) {
        $heightInMeters = $height / 100;
        return round($weight / ($heightInMeters * $heightInMeters), 2);
    }

    protected function calculatePredictions($cycleStart, $cycleLength, $cycleDuration) {
        $predictedCycleStart = date('Y-m-d', strtotime("$cycleStart + $cycleLength days"));
        $predictedCycleEnd = date('Y-m-d', strtotime("$predictedCycleStart + $cycleDuration days"));
        $fertileWindowStart = date('Y-m-d', strtotime("$cycleStart + " . ($cycleLength - 14 - 5) . " days"));
        $ovulationDate = date('Y-m-d', strtotime("$cycleStart + " . ($cycleLength - 14) . " days"));
        $nextFertileStart = date('Y-m-d', strtotime("$fertileWindowStart + $cycleLength days"));
        $predictedOvulationDate = date('Y-m-d', strtotime("$ovulationDate + $cycleLength days"));
    
        return compact('predictedCycleStart', 'predictedCycleEnd', 'fertileWindowStart', 'ovulationDate', 'nextFertileStart', 'predictedOvulationDate');
    }

    protected function getUserIdByUsername($username) {
        $useridQuery = "SELECT userid FROM accounts WHERE username = :username";
        $useridResult = $this->fetchOne($useridQuery, [':username' => $username]);

        if (!$useridResult || !$useridResult['userid']) {
            throw new Exception("Invalid username: $username");
        }

        return $useridResult['userid'];
    }
    
    protected function isDuplicateCycle($userid, $cycleStart) {
        $monthCheckQuery = "SELECT COUNT(*) as count 
                            FROM cycle_tbl 
                            WHERE userid = :userid 
                              AND MONTH(cycleStart) = MONTH(:cycleStart1) 
                              AND YEAR(cycleStart) = YEAR(:cycleStart2)";

        $monthCheckResult = $this->fetchOne($monthCheckQuery, [
            ':userid' => $userid,
            ':cycleStart1' => $cycleStart,
            ':cycleStart2' => $cycleStart
        ]);

        return $monthCheckResult && $monthCheckResult['count'] > 0;
    }

    protected function insertCycle($userid, $cycleStart, $CycleEnd, $cycleLength, $cycleDuration, $flowIntensity) {
        $insertQuery = "INSERT INTO cycle_tbl(userid, cycleStart, CycleEnd, cycleLength, cycleDuration, flowIntensity) 
                        VALUES (?, ?, ?, ?, ?, ?)";
        $this->runQuery($insertQuery, [$userid, $cycleStart, $CycleEnd, $cycleLength, $cycleDuration, $flowIntensity]);

        $cycleIdQuery = "SELECT LAST_INSERT_ID() as cycleId";
        $cycleIdResult = $this->fetchOne($cycleIdQuery);

        if (!$cycleIdResult || !$cycleIdResult['cycleId']) {
            throw new Exception("Failed to retrieve the cycleId of the newly inserted row.");
        }

        return $cycleIdResult['cycleId'];
    }

    protected function updateCyclePredictions($predictions, $conditions) {
        $whereClauses = [];
        $params = [];
        $orderByLimit = '';
    
        foreach ($conditions as $key => $value) {
            $whereClauses[] = "$key = :$key";
            $params[":$key"] = $value;
        }
    
        if (isset($conditions['userid'])) {
            $orderByLimit = "ORDER BY cycleId DESC LIMIT 1";
        }
    
        $updateQuery = "UPDATE cycle_tbl 
                        SET predictedCycleStart = :predictedCycleStart, 
                            predictedCycleEnd = :predictedCycleEnd 
                        WHERE " . implode(' AND ', $whereClauses) . " $orderByLimit";
    
        $params[':predictedCycleStart'] = $predictions['predictedCycleStart'];
        $params[':predictedCycleEnd'] = $predictions['predictedCycleEnd'];
    
        $this->runQuery($updateQuery, $params);
    }

    protected function insertOvulation($userid, $cycleId, $predictions) {
        $ovulationInsertQuery = "INSERT INTO ovulation_tbl(userId, cycleId, fertile_window_start, ovulationDate) 
                                VALUES (:userId, :cycleId, :fertileWindowStart, :ovulationDate)";
        $this->runQuery($ovulationInsertQuery, [
            ':userId' => $userid,
            ':cycleId' => $cycleId,
            ':fertileWindowStart' => $predictions['fertileWindowStart'],
            ':ovulationDate' => $predictions['ovulationDate']
        ]);

        $updateOvulationQuery = "UPDATE ovulation_tbl 
                                 SET next_fertile_start = :nextFertileStart, 
                                     predicted_ovulation_date = :predictedOvulationDate 
                                 WHERE cycleId = :cycleId";
        $this->runQuery($updateOvulationQuery, [
            ':nextFertileStart' => $predictions['nextFertileStart'],
            ':predictedOvulationDate' => $predictions['predictedOvulationDate'],
            ':cycleId' => $cycleId
        ]);
    }

    protected function storeData($tableName, $body, $additionalFields = []) {
        $data = array_merge($body, $additionalFields);
        $columns = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columnList = implode(', ', $columns);
    
        $sqlString = "INSERT INTO $tableName ($columnList) VALUES ($placeholders)";
        $this->runQuery($sqlString, array_values($data));
    }    
}
?>
