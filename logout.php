<?php
session_name('user_sid');
session_start();
unset($_SESSION['user']);
header("Location: index.php");