<?php include 'config.php';

$id = $_POST['id'];

mysqli_query($conn, "UPDATE booking SET status='paid' WHERE id='$id'");

header("Location: success.php?id=$id");
?>