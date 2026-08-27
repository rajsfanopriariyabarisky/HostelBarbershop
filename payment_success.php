<?php
include 'config.php';

$id = $_GET['id'];

mysqli_query($conn, "
UPDATE booking 
SET status='paid' 
WHERE id='$id'
");

header("Location: success.php?id=$id");
?>