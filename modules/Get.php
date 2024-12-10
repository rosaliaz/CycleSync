<?php

include_once "Common.php";

Class Get extends Common{
    protected $pdo;

    public function __construct(\PDO $pdo){
        $this->pdo = $pdo;
    }

    public function getLogs($date = "2024-12-11"){
        $filename = "./logs/" . $date . ".log";
    //    $file = file_get_contents("./logs/$filename");
    //    $logs = explode(PHP_EOL, $file);
        $logs = array();
        try{
            $file = new SplFileObject($filename);
        
            while(!$file->eof()){
                array_push($logs, $file->fgets());
            }
            $remarks = "success";
            $message = "Successfully retrieved logs.";
        }
        catch(Exception $e) {
            $remarks = "failed";
            $message = $e->getMessage();
        }
        return $this->generateResponse(array("logs"=>$logs), $remarks, $message, 200);
    }

    public function getCycle($id = null){
        $condition = "isDeleted = 0";
        if ($id != null){
            $condition .= " AND cycleId= $id";
        }
        $result = $this->getDataByTable("cycle_tbl",$condition, $this->pdo);
        if ($result['code']== 200){
            return $this->generateResponse($result['data'], "Successfully retrieved records", "success", $result['code']);
        }
        return $this->generateResponse(null, $result['errmsg'], "failed", $result['code']);
    }

    public function getAccount($id = null){
        $condition = "isDeleted = 0";
        if ($id != null){
            $condition .= " AND userid= $id";
        }
        $result = $this->getDataByTable("accounts",$condition, $this->pdo);
        if ($result['code']== 200){
            return $this->generateResponse($result['data'], "Successfully retrieved records", "success", $result['code']);
        }
        return $this->generateResponse(null, $result['errmsg'], "failed", $result['code']);
    }

    public function getOvulation($id = null){
        $condition = "isDeleted = 0";
        if ($id != null){
            $condition .= " AND ovulationId= $id";
        }
        $result = $this->getDataByTable("ovulation_tbl",$condition, $this->pdo);
        if ($result['code']== 200){
            return $this->generateResponse($result['data'], "Successfully retrieved records", "success", $result['code']);
        }
        return $this->generateResponse(null, $result['errmsg'], "failed", $result['code']);
    }

    public function getSymptom($id = null){
        $sqlString = "SELECT * FROM symptom_tbl";

        if($id != null){
            $sqlString .= "WHERE symptomId = " .$id;
        }
        $data = array();
        $errmsg = "";
        $code = 0;

        try{
            if($result = $this->pdo->query($sqlString)->fetchAll()){
                foreach($result as $record){
                    array_push($data, $record);
                }
                $result = null;
                $code = 200;
                return array("code"=>$code, "data"=>$data);
            }
            else{
                $errmsg = "No data found.";
                $code = 403;
            }
        }
        catch(\PDOException $e){
            $errmsg = $e->getMessage();
            $code = 403;
        }
        return array("code"=>$code, "errmsg"=>$errmsg);
    }

    public function getHealthMetric($id = null){
        $sqlString = "SELECT * FROM health_metric";

        if($id != null){
            $sqlString .= "WHERE health_metric = " .$id;
        }
        $data = array();
        $errmsg = "";
        $code = 0;

        try{
            if($result = $this->pdo->query($sqlString)->fetchAll()){
                foreach($result as $record){
                    array_push($data, $record);
                }
                $result = null;
                $code = 200;
                return array("code"=>$code, "data"=>$data);
            }
            else{
                $errmsg = "No data found.";
                $code = 403;
            }
         }
         catch(\PDOException $e){
            $errmsg = $e->getMessage();
            $code = 403;
        }
        return array("code"=>$code, "errmsg"=>$errmsg);
     }

     public function getNotification($id = null){
        $sqlString = "SELECT * FROM notification_tbl";

        if($id != null){
            $sqlString .= "WHERE notification_tbl= " .$id;
        }
        $data = array();
        $errmsg = "";
        $code = 0;

        try{
            if($result = $this->pdo->query($sqlString)->fetchAll()){
                foreach($result as $record){
                    array_push($data, $record);
                }
                $result = null;
                $code = 200;
                return array("code"=>$code, "data"=>$data);
            }
            else{
                $errmsg = "No data found.";
                $code = 403;
            }
         }
         catch(\PDOException $e){
            $errmsg = $e->getMessage();
            $code = 403;
        }
        return array("code"=>$code, "errmsg"=>$errmsg);
     }
     
}
?>