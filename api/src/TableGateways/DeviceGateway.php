<?php
namespace Src\TableGateways;

class DeviceGateway {

    private $db = null;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function findAll()
    {
        $statement = "
            SELECT 
                id, name, token, user
            FROM
                device;
        ";

        try {
            $statement = $this->db->query($statement);
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function findId($id)
    {
        $statement = "
            SELECT 
                id, name, token, user
            FROM
                device
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

    public function findToken($token)
    {
        $statement = "
            SELECT 
                id, name, token, user
            FROM
                device
            WHERE token = ?
            LIMIT 1;
        ";

        try {
            $statement = $this->db->prepare($statement);
            $status = $statement->execute(array($token));
            if (!$status){
                error_log("Mysql query returned: ".var_export($status, true)."!!");
                error_log(var_export($statement, true));
                return $status;
            }
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if ($statement->rowCount() == 0) {
                return false;
            }
            return $result[0];
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }

    public function create(Array $input)
    {
        $statement = "
            INSERT INTO device 
                (name, token, user)
            VALUES
                (:name, :token, :user);
        ";

        try {
            $statement = $this->db->prepare($statement);
            $status = $statement->execute(array(
                'name' => $input['devicename'],
                'token'  => $input['token'],
                'user' => $input['userid'],
            ));
            if (!$status){
                error_log("Mysql query returned: ".var_export($status, true)."!!");
                error_log(var_export($statement, true));
                return $status;
            }

            $result['devicename'] = $input['devicename'];
            $result['token'] = $input['token'];

            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }

    public function update($id, Array $input)
    {
        $statement = "
            UPDATE device
            SET 
                name = :name,
                token  = :password,
                user = :picture,
            WHERE id = :id;
        ";

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute(array(
                'id' => (int) $id,
                'name' => $input['name'],
                'token'  => $input['token'],
                'user' => $input['user'] ?? null,
            ));
            return $statement->rowCount();
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }

    public function delete($id)
    {
        $statement = "
            DELETE FROM device
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