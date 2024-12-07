<?php
class Patch{

    protected $pdo;

    public function __construct(\PDO $pdo){
        $this->pdo = $pdo;
    }

    public function patchAccounts($body, $id){
        $values = [];
        $errmsg = "";
        $code = 0;

        foreach($body as $value){
            array_push($values, $value);
        }

        array_push($values, $id);

        try{
            $sqlString = "UPDATE accounts SET userName=?, email=?, password=?, token=? WHERE userid=?";
            $sql = $this->pdo->prepare($sqlString);
            $sql->execute($values);

            $code = 200;
            $data = null;

            return array("data"=>$data, "code"=>$code);
        }
        catch (\PDOException $e){
            $errmsg = $e->getMessage();
            $code = 400;
        }

        return array("errmsg"=>$errmsg, "code"=>$code);
    }

    public function patchCycle($body, $id){
        $values = [];
        $errmsg = "";
        $code = 0;

        foreach($body as $value){
            array_push($values, $value);
        }

        array_push($values, $id);

        try{
            $sqlString = "UPDATE cycle_tbl SET startDate=?, endDate=?, cycleLength=?, flowIntensity=?, painLevel=? WHERE cycleid = ?";
            $sql = $this->pdo->prepare($sqlString);
            $sql->execute($values);

            $code = 200;
            $data = null;

            return array("data"=>$data, "code"=>$code);
        }
        catch (\PDOException $e){
            $errmsg = $e->getMessage();
            $code = 400;
        }

        return array("errmsg"=>$errmsg, "code"=>$code);
    }

    public function archiveCycle($id){
        $errmsg = "";
        $code = 0;

        try{
            $sqlString = "UPDATE cycle_tbl SET isDeleted=1 WHERE cycleid = ?";
            $sql = $this->pdo->prepare($sqlString);
            $sql->execute([$id]);

            $code = 200;
            $data = null;

            return array("data"=>$data, "code"=>$code);
        }
        catch (\PDOException $e){
            $errmsg = $e->getMessage();
            $code = 400;
        }

        return array("errmsg"=>$errmsg, "code"=>$code);
    }
    
}
?>