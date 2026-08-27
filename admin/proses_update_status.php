<?php
include '../config.php';
global $conn;

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';
$redirect = $_GET['redirect'] ?? '';

$allowedStatus = ['paid', 'pending', 'expired'];

// Validate inputs
if($id <= 0){
    $_SESSION['flash'] = [
        'type' => 'error',
        'title' => 'Invalid Request',
        'message' => 'Invalid booking ID'
    ];
    header("Location: booking.php#allBookings");
    exit;
}

if(!in_array($status, $allowedStatus)){
    $_SESSION['flash'] = [
        'type' => 'error',
        'title' => 'Invalid Status',
        'message' => 'Status must be paid, pending, or expired'
    ];
    header("Location: booking.php#allBookings");
    exit;
}

// Escape status for SQL
$statusEscaped = mysqli_real_escape_string($conn, $status);

// Update status
if(mysqli_query($conn, "UPDATE booking SET status='$statusEscaped' WHERE id=$id")){
    $_SESSION['flash'] = [
        'type' => 'success',
        'title' => 'Status Updated',
        'message' => 'Booking #'.$id.' marked as '.strtoupper($status)
    ];
} else {
    $_SESSION['flash'] = [
        'type' => 'error',
        'title' => 'Update Failed',
        'message' => 'Database error: ' . mysqli_error($conn)
    ];
}

// Build redirect URL
$redirectUrl = 'booking.php';

if(!empty($redirect)){
    // Decode first in case double-encoded
    $redirect = urldecode($redirect);
    
    // Parse query string and hash
    $hashPos = strpos($redirect, '#');
    
    if($hashPos !== false){
        $queryString = substr($redirect, 0, $hashPos);
        $hash = substr($redirect, $hashPos); // includes # character
    } else {
        $queryString = $redirect;
        $hash = '#allBookings';
    }
    
    if(!empty($queryString)){
        $redirectUrl .= '?' . $queryString;
    }
    $redirectUrl .= $hash;
} else {
    $redirectUrl .= '#allBookings';
}

header("Location: " . $redirectUrl);
exit;
?>