<?php
Class Get{
    protected $pdo;

    public function __construct(\PDO $pdo){
        $this->pdo = $pdo;
    }

    public function getCycle($id = null){
        $sqlString = "SELECT * FROM cycle_tbl WHERE isDeleted=0";

        if($id != null){
            $sqlString .= " AND cycleid=" . $id;
        }
        $data = array();
        $errmsg = "";
        $code = 0;

        // retrieve records
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

    public function getAccount($id = null){
        $sqlString = "SELECT * FROM accounts";

        if($id != null){
            $sqlString .= " WHERE userid=" . $id;
        }
        $data = array();
        $errmsg = "";
        $code = 0;

        // retrieve records
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
}
?>