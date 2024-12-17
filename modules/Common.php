<?php
class common{
    protected function logger($user, $method, $action){
        // datetime, user, method, action . text file .log
        $filename = date("Y-m-d").".log";
        $datetime = date("Y-m-d H:m:s");
        $logMessage = "$datetime,$method,$user,$action" .PHP_EOL; //PHP EOL concat, new line
        file_put_contents("./logs/$filename", $logMessage,FILE_APPEND | LOCK_EX); 
    }

    private function generateInsertString($tablename,$body){
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
    
        // Generate column placeholders dynamically
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
    
}
?>
