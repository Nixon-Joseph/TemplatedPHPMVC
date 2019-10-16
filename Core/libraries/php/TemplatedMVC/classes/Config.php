<?php
class Config {
    public $DBServer;
    public $DBName;
    public $DBUser;
    public $DBPassword;
    public $SessionId;

    function __construct($dbServ, $dbName, $dbUser, $dbPass, $sessionId = "SID") {
        $this->DBServer = $dbServ;
        $this->DBName = $dbName;
        $this->DBUser = $dbUser;
        $this->DBPassword = $dbPass;
        $this->SessionId = $sessionId;
    }
}
?>