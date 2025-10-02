<?php
// database.php
$mysqli = new mysqli("localhost", "root", "", "spareparts");
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}
return $mysqli;
