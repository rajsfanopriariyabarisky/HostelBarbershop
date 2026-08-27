<?php

include '../config.php';
global $conn;

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];

mysqli_query($conn, "DELETE FROM barber WHERE id='$id'");

header("Location: barber.php");
exit;
?>