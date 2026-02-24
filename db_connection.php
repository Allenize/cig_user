<?php
$conn = mysqli_connect("localhost", "root", "", "cig_user");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>