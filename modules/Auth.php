<?php

require_once "./modules/common.php";

class Authentication extends common{

    protected $pdo;

    public function __construct(\PDO $pdo){
        $this->pdo = $pdo;
    }

    public function isAuthorized(){
        $headers = array_change_key_case(getallheaders(),CASE_LOWER);
        return $this->getToken() === $headers['authorization'];
    }

    private function getToken(){
        $headers = array_change_key_case(getallheaders(),CASE_LOWER);

        $sqlString = "SELECT token FROM accounts WHERE userName=?";
        try{
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute([$headers['x-auth-user']]);
            $result = $stmt->fetchAll()[0];
            return $result['token'];
        }
        catch(Exception $e){
            echo $e->getMessage();
        }
       return $token;
    }

    public function saveToken($token, $username){
        
        $errmsg = "";
        $code = 0;
        
        try{
            $sqlString = "UPDATE accounts SET token=? WHERE userName = ?";
            $sql = $this->pdo->prepare($sqlString);
            $sql->execute( [$token, $username] );

            $code = 200;
            $data = null;

            return array("data"=>$data, "code"=>$code);
        }
        catch(\PDOException $e){
            $errmsg = $e->getMessage();
            $code = 400;
        }
        return array("errmsg"=>$errmsg, "code"=>$code);
    }


    private function generateHeader(){
       $header = [ 
        "typ" => "JWT",
        "alg" => "HS256"
       ];
       return base64_encode(json_encode($header));
    }

    private function generatePayload($id, $username){
        $payload = [ 
            "userId" => $id,
            "user" => $username,
            "date" => date_create()
           ];
           return base64_encode(json_encode($payload));
    }

    private function generateToken($id, $username){
       $header = $this->generateHeader();
       $payload = $this->generatePayload($id, $username);
       $signature = hash_hmac("sha256", "$header.$payload", TOKEN_KEY);
       return "$header.$payload." . base64_encode($signature);
    }

    private function isSamePassword($inputPassword, $existingHash){
        $hash = crypt($inputPassword, $existingHash);
        return $hash == $existingHash;
    }

    private function encryptPassword($password){
        $hashFormat = "$2y$10$";
        $saltLength = 22;
        $salt = $this->generateSalt($saltLength);
        return crypt($password, $hashFormat . $salt);
    }

    private function generateSalt($length){
        $urs = md5(uniqid(mt_rand(), true));
        $b64String = base64_encode($urs);
        $mb64String = str_replace("+",".",$b64String);
        return substr($mb64String, 0, $length);
    }

    public function login($body) {
        try {
            $username = $body['username'];
            $password = $body['password'];
    
            $sqlString = "SELECT userid, userName, email, password, token FROM accounts WHERE userName = ?";
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute([$username]);
    
            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
                if ($this->isSamePassword($password, $result['password'])) {
                    $token = $this->generateToken($result['userid'], $result['userName']);
                    $tokenArr = explode('.', $token);
                    $this->saveToken($tokenArr[2], $result['userName']);
    
                    session_start();
                    $_SESSION['userid'] = $result['userid'];
                    $_SESSION['userName'] = $result['userName'];
    
                    $this->logger($username, 'LOGIN', "User $username logged in successfully.");
    
                    return [
                        "payload" => [
                            "userid" => $result['userid'],
                            "userName" => $result['userName'],
                            "token" => $tokenArr[2]
                        ],
                        "remarks" => "success",
                        "message" => "Logged in successfully.",
                        "code" => 200
                    ];
                } else {
                    $this->logger($username, 'LOGIN', "Incorrect password for $username.");
                    return [
                        "payload" => null,
                        "remarks" => "failed",
                        "message" => "Incorrect Password!",
                        "code" => 401
                    ];
                }
            } else {
                $this->logger($username, 'LOGIN', "Username $username does not exist.");
                return [
                    "payload" => null,
                    "remarks" => "failed",
                    "message" => "Username does not exist.",
                    "code" => 401
                ];
            }
        } catch (PDOException $e) {
            $errmsg = $e->getMessage();
            $this->logger(null, 'ERROR', "Login failed: $errmsg");
            return ["errmsg" => $errmsg, "code" => 400];
        }
    }
    
    public function addAccounts($body) {
        try {
            $this->ensureFieldsExist($body, ['username', 'password', 'email']);
    
            $password = $this->encryptPassword($body['password']);
            $values = [
                $body['username'],
                $body['email'],
                $password
            ];
    
            $sqlString = "INSERT INTO accounts(userName, email, password) VALUES (?, ?, ?)";
            $this->runQuery($sqlString, $values);
    
            $this->logger($body['username'], 'POST', "New account created for username {$body['username']}");
            return ["message" => "Account created successfully.", "code" => 200];
    
        } catch (PDOException $e) {
            $errmsg = $e->getMessage();
            $this->logger(null, 'ERROR', "Account creation failed: $errmsg");
            return ["errmsg" => $errmsg, "code" => 400];
        }
    }
    
}
?>
