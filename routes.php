<?php

    require_once "./config/database.php";
    require_once "./modules/Get.php";
    require_once "./modules/Post.php";
    require_once "./modules/Patch.php";
    require_once "./modules/Delete.php";
    require_once "./modules/Auth.php";

    $db = new Connection();
    $pdo = $db->connect();

    // instantitate post, get class
    $post = new Post($pdo);
    $get = new Get($pdo);
    $patch = new Patch($pdo);
    $delete = new Delete($pdo);
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

                case "cycle":
                    if (count($request)>1){
                        echo json_encode($get->getCycle($request[1]));
                    }
                    else{
                        echo json_encode($get->getCycle());
                    }
                break;

                case "account":
                    if (count($request)>1){
                        echo json_encode($get->getAccount($request[1]));
                    }
                    else{
                        echo json_encode($get->getAccount());
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
            $body = json_decode(file_get_contents("php://input"));
            switch($request[0]){
                case "login":
                    echo json_encode($auth->login($body));
                break;

                case "cycle":
                    echo json_encode($post->postCycle($body));
                break;

                case "account":
                    echo json_encode($auth->addAccounts($body));
                break;

                default:
                    http_response_code(401);
                    echo "This is invalid endpoint";
                break;
            }
        break;

        case "PATCH":
            $body = json_decode(file_get_contents("php://input"));
            switch($request[0]){
                case "cycle":
                    echo json_encode($patch->patchCycle($body, $request[1]));
                break;

                case "account":
                    echo json_encode($patch->patchAccounts($body, $request[1]));
                break;

            default:
                http_response_code(400);
                echo "Invalid Request Method.";
            break;
        }
        break;

    case "DELETE":
        switch($request[0]){
            case "cycle":
                echo json_encode($patch->archiveCycle($request[1]));
            break;

            case "destroycycle":
                echo json_encode($delete->deleteCycle($request[1]));
            break;

            default:
                http_response_code(400);
                echo "Invalid Request Method.";
            break;
        }
        break;
}

?>
