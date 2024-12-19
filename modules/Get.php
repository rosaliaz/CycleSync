<?php

include_once "Common.php";

class Get extends Common {
    protected $pdo;
    protected $user;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;

        // Extract user from headers
        $this->user = $this->getUserFromHeaders();
    }

    public function getCycle($id = null) {
        $condition = "1=1";
        if ($id != null) {
            $condition = "userid = $id";
        }
         try {
            $result = $this->getDataByTable("cycle_tbl", $condition, $this->pdo);

            // Logging the action
            $this->logger($this->user, 'GET', $id ? "Retrieve cycle $id" : "Retrieve all cycles");
    
            if ($result['code'] == 200) {
                return $this->generateResponse($result['data'], "Successfully retrieved records", "success", $result['code']);
            }
    
            return $this->generateResponse(null, $result['errmsg'], "failed", $result['code']);
         } catch (\PDOException $e) {
            // Logging the SQL error
            $this->logger($this->user, 'ERROR', "SQL Error: " . $e->getMessage());
    
            // Return failure response
            return $this->generateResponse(null, "A database error occurred.", "failed", 500);
         }
    }

    public function getAccount($id = null) {
        $condition = "1=1"; // Default condition to ensure valid SQL
        if ($id != null) {
            $condition = "userid = $id";
        }
    
        try {
            $result = $this->getDataByTable("accounts", $condition, $this->pdo);
    
            // Logging successful action
            $this->logger($this->user, 'GET', $id ? "Retrieve account $id" : "Retrieve all accounts");
    
            if ($result['code'] == 200) {
                return $this->generateResponse($result['data'], "Successfully retrieved records", "success", $result['code']);
            }
    
            return $this->generateResponse(null, $result['errmsg'], "failed", $result['code']);
        } catch (\PDOException $e) {
            // Logging the SQL error
            $this->logger($this->user, 'ERROR', "SQL Error: " . $e->getMessage());
    
            // Return failure response
            return $this->generateResponse(null, "A database error occurred.", "failed", 500);
        }
    }       

    public function getOvulation($id = null) {
        $condition = "1=1";
        if ($id != null) {
            $condition = "userid = $id";
        }
        $result = $this->getDataByTable("ovulation_tbl", $condition, $this->pdo);

        // Logging the action
        $this->logger($this->user, 'GET', $id ? "Retrieve ovulation $id" : "Retrieve all ovulations");

        if ($result['code'] == 200) {
            return $this->generateResponse($result['data'], "Successfully retrieved records", "success", $result['code']);
        }
        return $this->generateResponse(null, $result['errmsg'], "failed", $result['code']);
    }

    public function getSymptom($id = null){
        $condition = "1=1";
        if ($id != null) {
            $condition = "userid = $id";
        }
        $result = $this->getDataByTable("symptom_tbl", $condition, $this->pdo);

        // Logging the action
        $this->logger($this->user, 'GET', $id ? "Retrieve symptom $id" : "Retrieve all symptoms");

        if ($result['code'] == 200) {
            return $this->generateResponse($result['data'], "Successfully retrieved records", "success", $result['code']);
        }
        return $this->generateResponse(null, $result['errmsg'], "failed", $result['code']);
    }

    public function getHealth($id = null){
        $condition = "1=1";
        if ($id != null) {
            $condition = "userid = $id";
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
            $condition = "userid = $id";
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
