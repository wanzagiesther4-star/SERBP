<?php

$host = "dpg-dbdkdhl9fdbs73beigq0-a.oregon-postgres.render.com";
$port="5432";
$user = "eugsther";
$password = "ioD2CWJM3f37DMSxzFZgP18QzFUI4LCk";
$dbname = "e_wastedb_ocxc";

$conn = pg_connect("host='$host' port='$port' dbname='$dbname' user='$user' password='$password'");
if ($conn === false) {
    die("Connection failed: Unable to connect to the database.");
}

?>
