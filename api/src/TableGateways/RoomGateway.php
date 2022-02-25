<?php
namespace Src\TableGateways;

class RoomGateway {

    private $db = null;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function findAll($userId = null)
    {

        if (isset($userId)) {
            $statement = "
                SELECT 
                    id, name, owner, visible 
                FROM room 
                WHERE 
                visible = 0 
                AND owner = $userId 
                OR visible = 1;
            ";
        } else {
            $statement = "
                SELECT 
                    id, name, owner
                FROM
                    room
                WHERE visible = 1;
            ";
        }
        try {
            $statement = $this->db->prepare($statement);
            $status = $statement->execute();
            if (!$status){
                error_log("Mysql query returned: ".var_export($status, true)."!!");
                error_log(var_export($statement, true));
                error_log($statement->error_get_last());
                return $status;
            }
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function find($id)
    {
        $statement = "
            SELECT 
                id, name, owner, visible, password
            FROM
                room
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
            INSERT INTO room 
                (name, owner, visible, password)
            VALUES
                (:name, :owner, :visible, :password);
        ";

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute(array(
                'name' => $input['name'],
                'owner'  => $input['owner'],
                'visible' => $input['visible'],
                'password' => $input['password'] ?? null,
            ));
            return $statement->rowCount();
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }

    public function update($id, Array $input)
    {
        $statement = "
            UPDATE room
            SET 
                name = :name,
                owner  = :owner,
                visible = :visible,
                password = :password
            WHERE id = :id;
        ";

        try {
            $statement = $this->db->prepare($statement);
            $statement->execute(array(
                'id' => (int) $id,
                'name' => $input['name'],
                'owner'  => $input['owner'],
                'visible' => $input['visible'] ?? null,
                'password' => $input['password'] ?? null,
            ));
            return $statement->rowCount();
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }    
    }

    public function delete($id)
    {
        $statement = "
            DELETE FROM room
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