<?php
class Post{

    protected $pdo;

    public function __construct(\PDO $pdo){
        $this->pdo = $pdo;
    }

    public function postCycle($body){
        $values = [];
        $errmsg = "";
        $code = 0;

        foreach($body as $value){
            array_push($values, $value);
        }

        try{
            $sqlString = "INSERT INTO cycle_tbl(cycleId,userId,startDate, endDate, cycleLength, flowIntensity) VALUES (?,?,?,?,?,?)";
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

    public function postOvulation($body){
        $values = [];
        $errmsg = "";
        $code = 0;

        foreach($body as $value){
            array_push($values, $value);
        }

        try{
            $sqlString = "INSERT INTO ovulation_tbl(ovulationId, userId, cycleId, fertile_window_start, ovulationDate, next_fertile_start, predicted_ovulation_date) VALUE (?,?,?,?,?,?,?)";
            $sql = $this->pdo->prepare($sqlString);
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

    public function postSymptom($body){
        $values = [];
        $errmsg = "";
        $code = 0;

        foreach($body as $value){
            array_push($values, $value);
        }

        try{
            $sqlString = "INSERT INTO symptom_tbl(symptomId, userId, data_log, symptomType, severity) VALUE (?,?,?,?,?)";
            $sql = $this->pdo->prepare($sqlString);
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

    public function postHealth($body){
        $values = [];
        $errmsg = "";
        $code = 0;

        foreach($body as $value){
            array_push($values, $value);
        }

        try{
            $sqlString = "INSERT INTO health_metric(metricId, userid, height, weight, BMI, timestamp) VALUE (?,?,?,?,?,?)";
            $sql = $this->pdo->prepare($sqlString);
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

    public function postNotification($body){
        $values = [];
        $errmsg = "";
        $code = 0;

        foreach($body as $value){
            array_push($values, $value);
        }

        try{
            $sqlString = "INSERT INTO notification_tbl(notificationId, userid, notificationType, notificationDate, isSent) VALUE (?,?,?,?)";
            $sql = $this->pdo->prepare($sqlString);
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
