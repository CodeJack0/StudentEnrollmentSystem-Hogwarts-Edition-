<?php

$host = 'localhost'; 
$dbname = 'act_db'; 
$username = 'root'; 
$password = ''; 


function getDbConnection() {
    global $host, $dbname, $username, $password;

    $conn = new mysqli($host, $username, $password, $dbname);

   
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}
?>
