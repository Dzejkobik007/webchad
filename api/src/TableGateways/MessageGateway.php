<?php
namespace Src\TableGateways;

class MessageGateway {

    private $db = null;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function findAll($roomId, $limit = 100, $fromid = 0)
    {
        $statement = "
            SELECT message.id, message.text, user.name as 'sender', message.reply, message.room, message.file, message.created
            FROM message 
            JOIN user 
            ON user.id=message.sender 
            WHERE room = $roomId AND message.id > $fromid ORDER BY message.created DESC LIMIT $limit;
        ";
        try {
            $statement = $this->db->prepare($statement);
            $status = $statement->execute();
            if (!$status){
                error_log("Mysql query returned: ".var_export($status, true)."!!");
                error_log(var_export($statement, true));
                error_log(var_export($statement->errorInfo(), true));
                return $status;
            }
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function get($id)
    {
        $statement = "
            SELECT 
                id, text, sender, reply, room, file
            FROM
                message
            WHERE id = ?;
        ";

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute(array($id));
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }

    public function insert(Array $input)
    {
        $statement = "
            INSERT INTO message 
                (text, sender, reply, room, file)
            VALUES
                (:text, :sender, :reply, :room, :file);
        ";
        try {
            $statement = $this->db->prepare($statement);
            $status = $statement->execute(array(
                'text' => $input['message'],
                'sender'  => $input['sender'],
                'reply' => $input['reply'] ?? null,
                'room' => $input['room'],
                'file' => $input['file'] ?? null,
            ));
            if (!$status){
                error_log("Mysql query returned: ".var_export($status, true)."!!");
                error_log(var_export($input, true));
                error_log(var_export($statement, true));
                error_log(var_export($statement->errorInfo(), true));
                return $status;
            }
            return $statement->rowCount();
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }

    public function update($id, Array $input)
    {
        $statement = "
            UPDATE message
            SET 
                text = :text,
                sender = :sender,
                reply = :reply,
                room = :room
                file = :file
            WHERE id = :id;
        ";

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute(array(
                'id' => (int) $id,
                'text' => $input['text'],
                'sender'  => $input['sender'],
                'reply' => $input['reply'] ?? null,
                'room' => $input['room'],
                'file' => $input['file'] ?? null,
            ));
            return $statement->rowCount();
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }

    public function delete($id)
    {
        $statement = "
            DELETE FROM message
            WHERE id = :id;
        ";

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute(array('id' => $id));
            return $statement->rowCount();
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }
}