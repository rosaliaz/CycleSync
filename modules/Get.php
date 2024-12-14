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
        $condition = "1=1";
        if ($id != null) {
            $condition = "symptomId = $id";
        }
        $result = $this->getDataByTable("symptom_tbl", $condition, $this->pdo);

        // Logging the action
        $this->logger($this->user, 'GET', $id ? "Retrieve symptom $id" : "Retrieve all symptoms");

        if ($result['code'] == 200) {
            return $this->generateResponse($result['data'], "Successfully retrieved records", "success", $result['code']);
        }
        return $this->generateResponse(null, $result['errmsg'], "failed", $result['code']);
    }

    public function getHealthMetric($id = null){
        $condition = "1=1";
        if ($id != null) {
            $condition = "metricId = $id";
        }
        $result = $this->getDataByTable("health_metric", $condition, $this->pdo);

        // Logging the action
        $this->logger($this->user, 'GET', $id ? "Retrieve health metric $id" : "Retrieve all health metrics");

        if ($result['code'] == 200) {
            return $this->generateResponse($result['data'], "Successfully retrieved records", "success", $result['code']);
        }
        return $this->generateResponse(null, $result['errmsg'], "failed", $result['code']);
     }

     public function getNotification($id = null){
        $condition = "1=1";
        if ($id != null) {
            $condition = "notificationId = $id";
        }
        $result = $this->getDataByTable("notification_tbl", $condition, $this->pdo);

        // Logging the action
        $this->logger($this->user, 'GET', $id ? "Retrieve notification $id" : "Retrieve all notifications");

        if ($result['code'] == 200) {
            return $this->generateResponse($result['data'], "Successfully retrieved records", "success", $result['code']);
        }
        return $this->generateResponse(null, $result['errmsg'], "failed", $result['code']);
     }
     
}
?>
