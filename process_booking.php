<?php
include 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];

$paket   = $_POST['paket'];
$barber  = $_POST['barber'];
$tanggal = $_POST['tanggal'];
$jam     = $_POST['jam'];

/*
=====================================
CEK SLOT SUDAH DIBOOKING?
=====================================
*/

$check = mysqli_query($conn,"
SELECT *
FROM booking
WHERE barber_id='$barber'
AND tanggal='$tanggal'
AND jam='$jam'
AND (
status='pending'
OR status='paid'
)
");

if(mysqli_num_rows($check) > 0){

    echo "
    <script>
    alert('Slot sudah dipakai');
    window.location='booking.php';
    </script>
    ";

    exit;
}

/*
=====================================
INSERT BOOKING
=====================================
*/

mysqli_query($conn,"
INSERT INTO booking
(
user_id,
paket_id,
barber_id,
tanggal,
jam,
status,
created_at
)

VALUES
(
'$userId',
'$paket',
'$barber',
'$tanggal',
'$jam',
'pending',
NOW()
)
");

/*
=====================================
AMBIL ID BOOKING TERBARU
=====================================
*/

$bookingId = mysqli_insert_id($conn);

/*
=====================================
REDIRECT PAYMENT
=====================================
*/

header("Location: payment.php?id=".$bookingId);
exit;
?>