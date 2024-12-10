<?php
class Common{

    protected function logger($user, $method, $action){
        // datetime, user, method, action . text file .log
        $filename = date("Y-m-d").".log";
        $datetime = date("Y-m-d H:m:s");
        $logMessage = "$datetime,$method,$user,$action" .PHP_EOL; //PHP EOL concat, new line
        file_put_contents("./logs/$filename", $logMessage,FILE_APPEND | LOCK_EX); // wo file append, laging overwriting mangyayari sa txt file
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

    public function postData($tableName, $body, \PDO $pdo){
        $values = [];
        $errmsg = "";
        $code = 0;

        foreach($body as $value){
            array_push($values, $value);
        }

        try{
            $sqlString = $this->generateInsertString($tableName,$body);
            $sql = $pdo->prepare($sqlString);
            $sql->execute($values);

            $code =200;
            $data = null;

            return array("data"=>$data, "code"=>$code);
        }
        catch(\PDOException $e){
            $errmsg = $e->getMessage();
            $code = 400;
        }
        return array("errmsg"=>$errmsg, "code"=>$code);
    }
}
?>