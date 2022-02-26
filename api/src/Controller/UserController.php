<?php

namespace Src\Controller;

use Src\TableGateways\UserGateway;
use Src\Controller\DeviceController;

class UserController
{

    private $db;
    private $requestMethod;
    private $userId;

    private $userGateway;

    public function __construct($db, $requestMethod, $auth)
    {
        $this->db = $db;
        $this->requestMethod = $requestMethod;
        $this->auth = $auth;
        $this->userId = $auth['user'] ?? null;
        $this->userGateway = new UserGateway($db);
        $this->deviceController = new DeviceController($db, $requestMethod);
    }

    public function processRequest($action = null)
    {
        switch ($this->requestMethod) {
            case 'GET':
                if (isset($this->userId)) {
                    $response = $this->getUser($this->userId);
                } else {
                    $response = $this->notFoundResponse();
                };
                break;
            case 'POST':
                if (isset($action)) {
                    if ($action == "create") {
                        $response = $this->createUser();
                    }
                    if ($action == "login") {
                        $response = $this->loginUser();
                    } else {
                        $response = $this->notFoundResponse();
                    }
                } else {
                    $response = $this->notFoundResponse();
                }
                break;
            case 'PUT':
                $response = $this->updateUser($this->userId);
                break;
            case 'DELETE':
                $response = $this->deleteUser($this->userId);
                break;
            default:
                $response = $this->notFoundResponse();
                break;
        }
        header($response['status_code_header']);
        if ($response['body']) {
            echo $response['body'];
        }
    }

    private function getAllUsers()
    {
        $result = $this->userGateway->findAll();
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode($result);
        return $response;
    }

    private function getUser($userId = null)
    {
        if (!isset($userId)) {
            $userId = $this->userId;
        }
        $result = $this->userGateway->findId($userId);
        if (!$result) {
            return $this->notFoundResponse();
        }
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode($result);
        return $response;
    }

    private function createUser()
    {
        $input = (array) json_decode(file_get_contents('php://input'), TRUE);
        if (!$this->validateUser($input)) {
            return $this->unprocessableEntityResponse();
        }
        $this->userGateway->insert($input);
        $response['status_code_header'] = 'HTTP/1.1 201 Created';
        $response['body'] = null;
        return $response;
    }

    public function loginUser()
    {
        if (!isset($_POST["username"])) {
            $this->unprocessableEntityResponse();
        } elseif (!isset($_POST["password"])) {
            $this->unprocessableEntityResponse();
        } else {
            $username = $_POST["username"];
            $password = $_POST["password"];
            $input["devicename"] = $_POST["devicename"] ?? "Device-" . rand(1000, 9999);
            $result = $this->userGateway->findName($username);
        }
        if (!$result) {
            return false;
        }
        if (password_verify($password, $result[0]["password"])) {
            $input["userid"] = $result[0]["id"];

            if ($result = $this->deviceController->registerDevice($input)) {
                $response['status_code_header'] = 'HTTP/1.1 200 OK';
                $response['body'] = json_encode($result);
                return $response;
            } else {
                $this->unprocessableEntityResponse();
            }
        } else {
            return false;
        }
    }

    private function updateUser($id)
    {
        $result = $this->userGateway->findId($id);
        if (!$result) {
            return $this->notFoundResponse();
        }
        $input = (array) json_decode(file_get_contents('php://input'), TRUE);
        if (!$this->validateUser($input)) {
            return $this->unprocessableEntityResponse();
        }
        $this->userGateway->update($id, $input);
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = null;
        return $response;
    }

    private function deleteUser($id)
    {
        $result = $this->userGateway->findId($id);
        if (!$result) {
            return $this->notFoundResponse();
        }
        $this->userGateway->delete($id);
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = null;
        return $response;
    }

    private function validateUser($input)
    {
        if (!isset($input['name'])) {
            return false;
        }
        if (!isset($input['password'])) {
            return false;
        }
        return true;
    }

    private function unprocessableEntityResponse()
    {
        $response['status_code_header'] = 'HTTP/1.1 422 Unprocessable Entity';
        $response['body'] = json_encode([
            'error' => 'Invalid input'
        ]);
        return $response;
    }

    private function notFoundResponse()
    {
        $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
        $response['body'] = null;
        return $response;
    }
}
