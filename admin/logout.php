<?php
session_name('admin_sid');
session_start();

// Clear remember me cookie if exists
if(isset($_COOKIE['admin_remember'])){
    setcookie('admin_remember', '', time() - 3600, '/');
}

// Clear all session data
unset($_SESSION['admin']);

// Redirect with flash message
$_SESSION['flash'] = ['type' => 'success', 'title' => 'Logged Out', 'message' => 'You have been logged out successfully.'];

header("Location: login.php");
exit;