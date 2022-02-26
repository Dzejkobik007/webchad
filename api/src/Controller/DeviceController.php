<?php

namespace Src\Controller;

use Src\TableGateways\DeviceGateway;

class DeviceController
{

    private $db;
    private $requestMethod;
    private $deviceId;

    private $deviceGateway;

    public function __construct($db, $requestMethod, $action = null, $auth = false)
    {
        $this->db = $db;
        $this->requestMethod = $requestMethod;
        $this->action = $action;
        $this->auth = $auth;

        $this->deviceGateway = new DeviceGateway($db);
    }

    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                if ($this->deviceId) {
                    $response = $this->getDevice($this->deviceId);
                } else {
                    $response = $this->getAllDevices();
                };
                break;
            case 'PUT':
                $response = $this->updateDevice($this->deviceId);
                break;
            case 'DELETE':
                $response = $this->deleteDevice($this->deviceId);
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

    private function getAllDevices()
    {
        $result = $this->deviceGateway->findAll();
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode($result);
        return $response;
    }

    private function getDevice($id)
    {
        $result = $this->deviceGateway->findId($id);
        if (!$result) {
            return $this->notFoundResponse();
        }
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode($result);
        return $response;
    }

    public function registerDevice(Array $input)
    {
        $input["token"] = $this->generateRandomString(60);
        if (!$this->validateDevice($input)) {
            return $this->unprocessableEntityResponse();
        }
        return $this->deviceGateway->create($input);
    }

    public function checkToken($token)
    {
        $result = $this->deviceGateway->findToken($token);
        if ($result) {
            $result["state"] = true;
        } else {
            $result = null;
            $result["state"] = false;
        }
        return $result;
    }

    private function updateDevice($id)
    {
        $result = $this->deviceGateway->findId($id);
        if (!$result) {
            return $this->notFoundResponse();
        }
        $input = (array) json_decode(file_get_contents('php://input'), TRUE);
        if (!$this->validateDevice($input)) {
            return $this->unprocessableEntityResponse();
        }
        $this->deviceGateway->update($id, $input);
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = null;
        return $response;
    }

    private function deleteDevice($id)
    {
        $result = $this->deviceGateway->findId($id);
        if (!$result) {
            return $this->notFoundResponse();
        }
        $this->deviceGateway->delete($id);
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = null;
        return $response;
    }

    private function validateDevice($input)
    {
        if (!isset($input['userid']) || !is_numeric($input['userid'])) {
            return false;
        }
        if (!isset($input['token'])) {
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

    // Function from https://stackoverflow.com/questions/4356289/php-random-string-generator
    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
