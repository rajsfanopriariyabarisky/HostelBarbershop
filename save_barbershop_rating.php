<?php
include 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $user_id = intval($_POST['user_id']);
    $rating = intval($_POST['rating']);
    $review = mysqli_real_escape_string($conn, $_POST['review'] ?? '');
    
    if($user_id != $_SESSION['user']['id']){
        header("Location: dashboard.php");
        exit;
    }
    
    if($rating < 1 || $rating > 5){
        header("Location: dashboard.php");
        exit;
    }
    
    mysqli_query($conn, "
        INSERT INTO barbershop_rating (user_id, rating, review) 
        VALUES ('$user_id', '$rating', '$review')
    ");
    
    header("Location: dashboard.php");
    exit;
}
?>