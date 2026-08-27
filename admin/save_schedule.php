<?php
/**
 * @var mysqli $conn
 */
date_default_timezone_set('Asia/Jakarta');  // ← FIX: Set timezone biar tanggal sesuai lokasi
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

if(!isset($_POST['jadwal'])){
    die("Jadwal barber tidak ditemukan");
}

$jadwal = $_POST['jadwal'];

/*
=====================================
REAL TIME - ROLLING 7 HARI DARI TODAY
=====================================
*/

$now = time();
$today = date('Y-m-d', $now);
$maxDate = date('Y-m-d', strtotime('+6 day', $now));

/*
=====================================
AUTO CLEANUP: HAPUS JADWAL YANG UDAH LEWAT
=====================================
*/

$safeToday = mysqli_real_escape_string($conn, $today);
mysqli_query($conn, "DELETE FROM jadwal_barber WHERE tanggal < '$safeToday'");

/*
=====================================
LOOP SEMUA TANGGAL DARI FORM
=====================================
*/

foreach($jadwal as $tanggal => $barbers){

    /*
    =====================================
    VALIDASI TANGGAL - HANYA TERIMA YANG MASUK RANGE
    =====================================
    */

    if($tanggal < $today || $tanggal > $maxDate){
        continue; // Skip tanggal diluar range 7 hari
    }

    /*
    =====================================
    HAPUS JADWAL LAMA UNTUK TANGGAL INI
    =====================================
    */

    $safeTanggal = mysqli_real_escape_string($conn, $tanggal);
    mysqli_query($conn, "DELETE FROM jadwal_barber WHERE tanggal = '$safeTanggal'");

    /*
    =====================================
    CEK ADA BARBER DIPILIH / TIDAK
    =====================================
    */

    if(!is_array($barbers) || empty($barbers)){
        continue; // Kosongin jadwal hari ini kalo ga ada yang dipilih
    }

    /*
    =====================================
    SIMPAN JADWAL BARU
    =====================================
    */

    foreach($barbers as $barberId){

        $barberId = (int)$barberId;

        // Cek barber_id valid (ada di tabel barber)
        $cek = mysqli_query($conn, "SELECT id FROM barber WHERE id = $barberId");
        if(mysqli_num_rows($cek) == 0) continue;

        mysqli_query($conn, "
            INSERT INTO jadwal_barber (barber_id, tanggal) 
            VALUES ($barberId, '$safeTanggal')
        ");

    }

}

/*
=====================================
REDIRECT
=====================================
*/

header("Location: barber.php?success=schedule");
exit;
?>