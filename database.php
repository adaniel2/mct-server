<?php
declare(strict_types=1);

$host = "localhost:3307";
$dbname = "catalogue";
$username = "root";
$password = "";

$mysqli = new mysqli($host, $username, $password, $dbname);

if ($mysqli->connect_errno) {
    die("Connection error: " . $mysqli->connect_error);
}

return $mysqli;