<?php

/**
 * Created by PhpStorm.
 * User: Juan
 * Date: 5/17/2018
 * Time: 10:00 AM
 */
class DBHelper
{
    //Database server name
    protected $servername;
    //Database username
    protected $username;
    //Database Password
    protected $password;
    //Database Name
    protected $mainDB;
    //Database Connection
    protected $conn;

    function __construct(){
        $this->servername = "localhost";
        $this->username = "root";
        $this->password = "notroot";
        $this->mainDB = "cartodatabase";

        if($this->getConn() == null)
            $this->DB_CONNECT(null);
    }

    public function getConn(){
        return $this->conn;
    }

    public function setConn($conn){
        $this->conn = $conn;
    }

    public function getServer(){
        return $this->servername;
    }

    public function getUser(){
        return $this->username;
    }

    public function getPwd(){
        return $this->password;
    }
    /**********************************************
     * Function: DB_CONNECT
     * Description: Connect to XAMPP db. localhost/phpadmin All functions connect to db using this.
     * Parameter(s):
     * $db (string) - name of database to be connected to
     * Return value(s):
     * $conn (object) - return connection object if success
     * - return -1 if failed
     ***********************************************/
    function DB_CONNECT($db)
    {
        if ($db == "" || $db == null) //empty parameter = default = bandocatdb
            $db = "cartoDatabase";
        try {
            $this->conn = new PDO("mysql:host=".$this->getServer().";dbname=".$db, $this->getUser(), $this->getPwd());

            // set the PDO error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        }
        catch(PDOException $e)
        {
            echo json_encode("Connection failed: " . $e->getMessage());
        }
    }

    function DB_CLOSE(){
        $this->setConn(null);
    }

    function SP_INSERT_IDEA($iAuthor, $iIdea){
        $this->getConn()->exec('USE '.$this->mainDB);

        $call = $this->getConn()->prepare("CALL SP_INSERT_IDEA(?,?)");

        if(!$call)
            trigger_error("SQL failed: ".$this->getConn()->errorCode()." - ".$this->conn->errorInfo()[0]);

        $call->bindParam(1, $iAuthor, PDO::PARAM_STR, strlen($iAuthor));
        $call->bindParam(2, $iIdea, PDO::PARAM_STR, strlen($iIdea));

        $call->execute();
        if($call)
            return true;
        else
            return false;
    }

    ############IDEAS############
    /**********************************************
     * Function: SP_SELECTALL_IDEA()
     * Description: Fetch all the info from the db ideas table
     * Parameter(s):
     * $db () - NONE
     * Return value(s): $result (Array) - return the an array with row info
     * - return -1 if failed
     ***********************************************/
    function SP_SELECTALL_IDEA(){
        $call = $this->getConn()->prepare("SELECT `Author`, `Idea`, `Time`, `ID` FROM `ideas`");
        $call->execute();
        $result = $call->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    /**********************************************
     * Function: SP_DELETE_IDEA()
     * Description: Deletes row entry by given ID
     * Parameter(s):
     * $iID (int) - Selected and posted hidden Id from the ideas table
     * Return value(s): $result (Array) - return the an array with row info
     * - return -1 if failed
     ***********************************************/
    function SP_DELETE_IDEA($iID){
        $this->getConn()->exec('USE '.$this->mainDB);
        $call = $this->getConn()->prepare("CALL SP_DELETE_IDEA(?)");

        if(!$call)
            trigger_error("SQL failed: ".$this->getConn()->errorCode()." - ".$this->conn->errorInfo()[0]);

        $call->bindParam(1, $iID, PDO::PARAM_STR, strlen($iID));
        $call->execute();
        if($call)
            return true;
        else
            return false;
    }
}