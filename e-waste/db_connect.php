<?php

$host = "localhost";
$port="5432";
$user = "postgres";
$password = "Eugsther2006";
$dbname = "e-waste_db";

$conn = pg_connect("host='$host' port='$port' dbname='$dbname' user='$user' password='$password'");
if ($conn === false) {
    die("Connection failed: Unable to connect to the database.");
}

?>
