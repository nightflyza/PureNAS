<?php
$config = parse_ini_file(dirname(__FILE__)."/dbconfig.conf");
$dbport = (empty($config['port'])) ? 3306 : $config['port'];

if (extension_loaded('mysqli')) {
    $loginDB = new mysqli($config['host'], $config['username'], $config['password'], $config['database'], $dbport);
    function DB_query($query) {
        global $loginDB;
        $result = $loginDB->query($query);
        return($result);
    }
    function DB_real_escape_string($parametr) {
        global $loginDB;
        $result = $loginDB->real_escape_string($parametr);
        return($result);
    }
    function DB_fetch_array($data) {
        $result = mysqli_fetch_assoc($data);
        return($result);
    }
} else {
   die("mysqli extension is not loaded");
}
