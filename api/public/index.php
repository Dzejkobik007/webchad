<?php
require "../bootstrap.php";

use Src\Controller\RoomController;
use Src\Controller\UserController;
use Src\Controller\DeviceController;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$requestMethod = $_SERVER["REQUEST_METHOD"];

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

$deviceController = new DeviceController($dbConnection, $requestMethod);
$auth = (Array) authenticate();
// error_log(var_export($_SERVER["HTTP_AUTHORIZATION"], true));
// if (!$auth) {
//     header("HTTP/1.1 401 Unauthorized");
//     exit('Unauthorized');
// }

// all of our endpoints start with /user or /room
// everything else results in a 404 Not Found

// ROOM
if ($uri[1] == 'room') {
    if ($auth["state"]) {
        $roomId = null;
        if (isset($uri[2])) {
            if (is_numeric($uri[2])) {
                $roomId = (int) $uri[2];
                if (isset($uri[3]) && $uri[3] == "message") {
                    $controller = new RoomController($dbConnection, $requestMethod, $roomId, True, $auth);
                    $controller->processRequest();
                } else {
                    $controller = new RoomController($dbConnection, $requestMethod, $roomId, false, $auth);
                    $controller->processRequest();
                }
            } else {
                notfound();
            }
        } else {
            $controller = new RoomController($dbConnection, $requestMethod, null, false, $auth);
            $controller->processRequest();
        }
    } else {
        unauthorized();
    }
}
// USER
elseif ($uri[1] == 'user') {
    if (isset($uri[2]) && $uri[2] == "login") {
        $controller = new UserController($dbConnection, $requestMethod, null, null);
        if ($response = $controller->processRequest("login")) {
            header($response['status_code_header']);
            if ($response) {
                echo $response['body'];
            } else {
                unauthorized();
            }
        }
    } else {
        if ($auth["state"]) {
            $controller = new UserController($dbConnection, $requestMethod, $auth);
            $controller->processRequest();
        }
    }
} else {
    notfound();
}

function authenticate()
{
    global $deviceController;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        return $deviceController->checkToken($_SERVER['HTTP_AUTHORIZATION']);
    }
}

function unauthorized() {
    header("HTTP/1.1 401 Unauthorized");
    exit('Unauthorized');
}

function notfound()
{
    header("HTTP/1.1 404 Not Found");
    exit();
}
