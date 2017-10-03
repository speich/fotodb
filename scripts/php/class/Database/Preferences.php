<?php

namespace PhotoDatabase\Database;


use PDO;
use PDOException;

/**
 * Class Preferences
 * Store user preferences in a SQLite database
 * @package Database
 */
class Preferences
{
    private $db;
    private $dbName = 'user.sqlite';
    private $dbPath = '/../../../../dbprivate/dbfiles/';

    public function __construct()
    {
        try {
            $this->db = new PDO('sqlite:' . __DIR__ . $this->dbPath . $this->dbName);
        } catch (PDOException $Error) {
            echo $Error->getMessage();
        }
    }

    /**
     * Store user preference.
     * @param string $Name preference
     * @param string $Value value
     * @param integer $UserId user id
     * @return bool
     */
    public function save($Name, $Value, $UserId)
    {

        // get setting id
        $Sql = "SELECT Id FROM Settings WHERE Name = :Name";
        $Stmt = $this->db->prepare($Sql);
        $Stmt->bindParam(':Name', $Name);
        $Stmt->execute();
        $Row = $Stmt->fetch(PDO::FETCH_ASSOC);
        $SettingId = $Row['Id'];
        // check if this setting was already set once if not insert it otherwise update
        $Sql = "SELECT Id FROM Prefs WHERE SettingId = :SettingId AND UserId = :UserId";
        $Stmt = $this->db->prepare($Sql);
        $Stmt->bindParam(':SettingId', $SettingId);
        $Stmt->bindParam(':UserId', $UserId);
        $Stmt->execute();
        $Row = $Stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_null($Row['Id'])) {
            $Sql = "UPDATE Prefs SET Value = :Value WHERE SettingId = :SettingId AND UserId = :UserId";
        } else {
            $Sql = "INSERT INTO Prefs (SettingId, UserId, Value) VALUES (:SettingId, :UserId, :Value)";
        }
        $Stmt = $this->db->prepare($Sql);
        $Stmt->bindParam(':SettingId', $SettingId);
        $Stmt->bindParam(':UserId', $UserId);
        $Stmt->bindParam(':Value', $Value);

        return $Stmt->execute();
    }

    /**
     * Load a user preference.
     * @return
     * @param string $Name preference
     * @param integer $UserId
     */
    public function load($Name, $UserId)
    {
        try {
            $this->db = new PDO('sqlite:' . __DIR__ . '/../dbprivate/dbfiles/' . $this->dbName);
        } catch (PDOException $Error) {
            echo $Error->getMessage();
        }
        // get setting id
        $Sql = "SELECT Value FROM Prefs WHERE SettingId = (SELECT Id FROM Settings WHERE Name = :Name) AND UserId = :UserId";
        $Stmt = $this->db->prepare($Sql);
        $Stmt->bindParam(':Name', $Name);
        $Stmt->bindParam(':UserId', $UserId);
        $Stmt->execute();
        $Row = $Stmt->fetch(PDO::FETCH_ASSOC);

        return $Row['Value'];
    }
}