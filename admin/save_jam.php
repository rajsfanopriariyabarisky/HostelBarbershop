<?php
/**
 * @var mysqli $conn
 */

include '../config.php';

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

/*
=====================================
VALIDASI
=====================================
*/

if(!isset($_POST['jam'])){
    die("Jam tidak ditemukan");
}

$jamList = $_POST['jam'];

if(!is_array($jamList)){
    die("Format jam tidak valid");
}

/*
=====================================
HAPUS JAM LAMA
=====================================
*/

mysqli_query($conn,"
TRUNCATE TABLE jam_operasional
");

/*
=====================================
SIMPAN JAM BARU
=====================================
*/

foreach($jamList as $jam){

    $jam = trim($jam);

    if($jam == ''){
        continue;
    }

    /*
    =====================================
    FORMAT JAM
    =====================================
    */

    $jam = date('H:i:s',strtotime($jam));

    mysqli_query($conn,"
    INSERT INTO jam_operasional(jam_buka)
    VALUES('$jam')
    ");

}

/*
=====================================
REDIRECT
=====================================
*/

header("Location: barber.php?success=jam");
exit;
?>