<?php

namespace Src\Controller;

use Src\TableGateways\RoomGateway;
use Src\TableGateways\MessageGateway;

class RoomController
{

    private $db;
    private $requestMethod;
    private $userId;

    private $roomGateway;

    public function __construct($db, $requestMethod, $roomId, $message = false, Array $auth = null)
    {
        $this->db = $db;
        $this->requestMethod = $requestMethod;
        $this->roomId = $roomId;
        $this->auth = $auth;
        $this->message = $message;

        $this->roomGateway = new RoomGateway($db);
        $this->messageGateway = new MessageGateway($db);
    }

    public function processRequest()
    {
        switch ($this->requestMethod) {
            case 'GET':
                if ($this->message) {
                    if ($this->roomId) {
                        $response = $this->getMessages($this->roomId);
                    } else {
                        $this->unprocessableEntityResponse();
                    }
                } else {
                    if ($this->roomId) {
                        $response = $this->getRoom($this->roomId);
                    } else {
                        $response = $this->getRooms();
                    }
                }
                break;
            case 'PUT':
                if ($this->message) {
                    $response = $this->createMessage($this->roomId);
                } else {
                    $response = $this->createRoom();
                }
                break;
            case 'DELETE':
                $response = $this->deleteRoom($this->roomId);
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

    private function getMessages($roomId)
    {
        
        $limit = $_GET['limit'] ?? 100;
        $fromid = $_GET['fromid'] ?? 0;
        $result = $this->messageGateway->findAll($roomId, $limit, $fromid);
        if (is_bool($result) && !$result) {
            return $this->unprocessableEntityResponse();
        }
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode($result);
        return $response;
    }

    private function getMessage($id)
    {
        $result = $this->roomGateway->find($id);
        if (!$result) {
            return $this->notFoundResponse();
        }
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode($result);
        return $response;
    }

    private function createMessage()
    {
        $input = (array) json_decode(file_get_contents('php://input'), TRUE);
        error_log(var_export($input));
        $input["sender"] = $this->auth['user'];
        $input["room"] = $this->roomId;
        if (!$this->validateMessage($input)) {
            return $this->unprocessableEntityResponse();
        }
        $result = $this->messageGateway->insert($input);
        if (!$result) {
            return $this->notFoundResponse();
        }
        $response['status_code_header'] = 'HTTP/1.1 201 Created';
        $response['body'] = null;
        return $response;
    }

    private function getRooms()
    {
        $result = $this->roomGateway->findAll($this->auth["user"]);
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode($result);
        return $response;
    }

    private function getRoom($id)
    {
        $result = $this->roomGateway->find($id);
        if (!$result) {
            return $this->notFoundResponse();
        }
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = json_encode($result);
        return $response;
    }

    private function createRoom()
    {
        $input = (array) json_decode(file_get_contents('php://input'), TRUE);
        if (!$this->validateRoom($input)) {
            return $this->unprocessableEntityResponse();
        }
        $this->roomGateway->insert($input);
        $response['status_code_header'] = 'HTTP/1.1 201 Created';
        $response['body'] = null;
        return $response;
    }

    private function updateRoom($id)
    {
        $result = $this->roomGateway->find($id);
        if (!$result) {
            return $this->notFoundResponse();
        }
        $input = (array) json_decode(file_get_contents('php://input'), TRUE);
        if (!$this->validateRoom($input)) {
            return $this->unprocessableEntityResponse();
        }
        $this->roomGateway->update($id, $input);
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = null;
        return $response;
    }

    private function deleteRoom($id)
    {
        $result = $this->roomGateway->find($id);
        if (!$result) {
            return $this->notFoundResponse();
        }
        $this->roomGateway->delete($id);
        $response['status_code_header'] = 'HTTP/1.1 200 OK';
        $response['body'] = null;
        return $response;
    }

    // (*name, *owner, *visible, password)
    private function validateRoom($input)
    {
        if (!isset($input['name'])) {
            return false;
        }
        if (!isset($input['owner'])) {
            return false;
        }
        if (!isset($input['visible'])) {
            return false;
        }
        return true;
    }

    // (*message, *sender, reply, *room, file)
    private function validateMessage($input)
    {
        if (!isset($input['message'])) {
            return false;
        }
        if (!isset($input['sender'])) {
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
