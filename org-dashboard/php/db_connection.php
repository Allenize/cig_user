<?php
$host     = getenv('MYSQLHOST')     ?: getenv('DB_HOST')     ?: 'localhost';
$user     = getenv('MYSQLUSER')     ?: getenv('DB_USER')     ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';
$database = getenv('MYSQLDATABASE') ?: getenv('DB_NAME')     ?: 'cig_system';
$port     = getenv('MYSQLPORT')     ?: getenv('DB_PORT')     ?: 3306;

$conn = mysqli_connect($host, $user, $password, $database, (int)$port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>