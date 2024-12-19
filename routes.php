<?php

    require_once "./config/database.php";
    require_once "./modules/Get.php";
    require_once "./modules/Post.php";
    require_once "./modules/Patch.php";
    require_once "./modules/Auth.php";
    require_once "./modules/common.php";

    $db = new Connection();
    $pdo = $db->connect();

    // instantitate post, get class
    $post = new Post($pdo);
    $get = new Get($pdo);
    $patch = new Patch($pdo);
    $auth = new Authentication($pdo);
    

    if(isset($_REQUEST['request'])){
        $request = explode("/", $_REQUEST['request']);
    }
    else{
        echo "URL does not exist.";
    }

    switch($_SERVER['REQUEST_METHOD']){

        case "GET":
            if($auth->isAuthorized()){
            switch($request[0]){
                case "account":
                    if (count($request)>1){
                        echo json_encode($get->getAccount($request[1]));
                    }
                    else{
                        echo json_encode($get->getAccount());
                    }
                break;

                case "cycle":
                    if (count($request)>1){
                        echo json_encode($get->getCycle($request[1]));
                    }
                    else{
                        echo json_encode($get->getCycle());
                    }
                break;

                case "ovulation":
                    if (count($request)>1){
                        echo json_encode($get->getOvulation($request[1]));
                    }
                    else{
                        echo json_encode($get->getOvulation());
                    }
                break;

                case "symptoms":
                    if (count($request)>1){
                        echo json_encode($get->getSymptom($request[1]));
                    }
                    else{
                        echo json_encode($get->getSymptom());
                    }
                break;

                case "health":
                    if(count($request)>1){
                        echo json_encode($get->getHealth($request[1]));
                    }
                    else{
                        echo json_encode($get->getHealth());
                    }
                break;

                case "notification":
                    if(count($request)>1){
                        echo json_encode($get->getNotification($request[1]));
                    }
                    else{
                        echo json_encode($get->getNotification());
                    }
                break;

                default:
                    http_response_code(400);
                    echo "Invalid Request Method.";
                break;
            }
          }
         else{
            http_response_code(401);
        }
         break;

        case "POST":
            $body = json_decode(file_get_contents("php://input"),true);

            if ($body === null) {
                http_response_code(400);
                echo json_encode(["error" => "Invalid JSON input."]);
                exit;
            }
            
            if (in_array($request[0], ["login", "register"])) {
                switch ($request[0]) {
                    case "login":
                        echo json_encode($auth->login($body));
                        break;
        
                    case "register":
                        echo json_encode($auth->addAccounts($body));
                        break;
                }
                break;
            }
            
            // Check authorization for all other POST endpoints
            if ($auth->isAuthorized()) {
                switch ($request[0]) {
                   
                    case "monthly_cycle":
                        echo json_encode($post->postCycleAndOvulation($body));
                        break;
        
                    case "symptoms":
                        echo json_encode($post->postSymptom($body));
                        break;
        
                    case "health":
                        echo json_encode($post->postHealth($body));
                        break;
        
                    case "notification":
                        echo json_encode($post->postNotification($body));
                        break;
        
                    default:
                        http_response_code(400);
                        echo json_encode(["error" => "Invalid endpoint."]);
                        break;
                }
            } else {
                http_response_code(401);
                echo json_encode(["error" => "Unauthorized"]);
        }
        break;
        
        case "PATCH":
            $body = json_decode(file_get_contents("php://input"),true);
            if ($auth->isAuthorized()) {
            switch($request[0]){
                case "cycle":
                    echo json_encode($patch->patchCycle($body, $request[1]));
                break;

                case "account":
                    echo json_encode($patch->patchAccounts($body, $request[1]));
                break;

                case "symptoms":
                    echo json_encode($patch->patchSymptom($body, $request[1]));
                break;

                case "health":
                    echo json_encode($patch->patchHealth($body, $request[1]));
                break;

                case "notification":
                    echo json_encode($patch->patchNotification($body, $request[1]));
                break;

                case "delete_notification":
                    echo json_encode($patch->archiveNotification($request[1]));
                break;

                case "delete_symptom":
                    echo json_encode($patch->archiveSymptom($request[1]));
                break;

                case "delete_health":
                    echo json_encode($patch->archiveHealth($request[1]));
                break;

            default:
                http_response_code(400);
                echo "Invalid Request Method.";
            break;
        }
         }else {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized"]);
    }
     break;

}

?>
