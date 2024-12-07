<?php

    require_once "./config/database.php";
    require_once "./modules/Get.php";
    require_once "./modules/Post.php";
    require_once "./modules/Patch.php";
    require_once "./modules/Delete.php";

    $db = new Connection();
    $pdo = $db->connect();

    // instantitate post, get class
    $post = new Post($pdo);
    $patch = new Patch($pdo);
    $get = new Get($pdo);
    $delete = new Delete($pdo);
    

    if(isset($_REQUEST['request'])){
        $request = explode("/", $_REQUEST['request']);
    }
    else{
        echo "URL does not exist.";
    }

    switch($_SERVER['REQUEST_METHOD']){

        case "GET":
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
        break;

        case "POST":
            $body = json_decode(file_get_contents("php://input"));
            switch($request[0]){
                case "cycle":
                    echo json_encode($post->postCycle($body));
                break;

                case "account":
                    echo json_encode($post->postAccounts($body));
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
                    echo json_encode($patch->patchAccounts($body,$request[1]));
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
            }
        break;

    default:
        http_response_code(400);
        echo "Invalid Request Method.";
    break;
}
?>