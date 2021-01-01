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
    private PDO $db;
    private string $dbName = 'user.sqlite';
    private string $dbPath = '/../../../../dbprivate/dbfiles/';

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
     * @param string $name preference
     * @param string $value value
     * @param integer $userId user id
     * @return bool
     */
    public function save($name, $value, $userId)
    {
        // get setting id
        $sql = "SELECT Id FROM Settings WHERE Name = :Name";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':Name', $name);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $settingId = $row['Id'];
        // check if this setting was already set once if not insert it otherwise update
        $sql = "SELECT Id FROM Prefs WHERE SettingId = :SettingId AND UserId = :UserId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':SettingId', $settingId);
        $stmt->bindParam(':UserId', $userId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_null($row['Id'])) {
            $sql = "UPDATE Prefs SET Value = :Value WHERE SettingId = :SettingId AND UserId = :UserId";
        } else {
            $sql = "INSERT INTO Prefs (SettingId, UserId, Value) VALUES (:SettingId, :UserId, :Value)";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':SettingId', $settingId);
        $stmt->bindParam(':UserId', $userId);
        $stmt->bindParam(':Value', $value);

        return $stmt->execute();
    }

    /**
     * Load a user preference.
     * @return string
     * @param string $name preference
     * @param integer $userId
     */
    public function load($name, $userId)
    {
        // get setting id
        $sql = "SELECT Value FROM Prefs WHERE SettingId = (SELECT Id FROM Settings WHERE Name = :Name) AND UserId = :UserId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':Name', $name);
        $stmt->bindParam(':UserId', $userId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['Value'];
    }
}