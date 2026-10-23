<?php

$host = "localhost";
$port="5432";
$user = "postgres";
$password = "Eugsther2006";
$dbname = "e-waste_db";

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
 if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

?>
