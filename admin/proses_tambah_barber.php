<?php
/**
 * @var mysqli $conn
 */
include '../config.php';

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

if($_SERVER['REQUEST_METHOD'] != 'POST'){
    header("Location: barber.php");
    exit;
}

/*
=====================================
AMBIL DATA
=====================================
*/
$nama         = trim($_POST['nama'] ?? '');
$fade         = (int) ($_POST['fade'] ?? 0);
$scissoring   = (int) ($_POST['scissoring'] ?? 0);
$longcut      = (int) ($_POST['longcut'] ?? 0);
$shortcut     = (int) ($_POST['shortcut'] ?? 0);
$beardcut     = (int) ($_POST['beardcut'] ?? 0);
$keterangan   = trim($_POST['keterangan'] ?? '');

if(empty($nama) || empty($keterangan)){
    die("ERROR: Nama dan keterangan kosong");
}

/*
=====================================
CEK FILE
=====================================
*/
if(!isset($_FILES['foto'])){
    die("ERROR: Foto tidak masuk");
}

if($_FILES['foto']['error'] != 0){
    die("ERROR UPLOAD: ".$_FILES['foto']['error']);
}

$fileName = $_FILES['foto']['name'];
$tmpName  = $_FILES['foto']['tmp_name'];
$fileSize = $_FILES['foto']['size'];

$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

$allowed = ['jpg','jpeg','png','webp'];

if(!in_array($ext,$allowed)){
    die("ERROR: format file salah");
}

/*
=====================================
UPLOAD PATH FIX (INI PENTING)
=====================================
*/
// HARUS sama dengan barber.php => admin/upload/
$uploadDir = __DIR__ . "/upload/";

if(!is_dir($uploadDir)){
    mkdir($uploadDir,0777,true);
}

$foto = time().'_'.rand(1000,9999).'.'.$ext;
$uploadPath = $uploadDir.$foto;

if(!move_uploaded_file($tmpName,$uploadPath)){
    die("ERROR: gagal move file ke folder upload");
}

/*
=====================================
INSERT DATABASE (DEBUG MODE)
=====================================
*/
$stmt = $conn->prepare("
INSERT INTO barber
(nama, skill_fade, skill_scissoring, skill_longcut, skill_shortcut, skill_beardcut, keterangan, foto)
VALUES
(?, ?, ?, ?, ?, ?, ?, ?)
");

if(!$stmt){
    die("PREPARE ERROR: ".$conn->error);
}

$stmt->bind_param(
    "siiiisss",
    $nama,
    $fade,
    $scissoring,
    $longcut,
    $shortcut,
    $beardcut,
    $keterangan,
    $foto
);

if($stmt->execute()){
    header("Location: barber.php");
    exit;
}else{
    die("INSERT ERROR: ".$stmt->error);
}