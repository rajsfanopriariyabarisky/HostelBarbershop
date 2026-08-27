<?php
/**
 * @var mysqli $conn Database connection from config.php
 */
include '../config.php';
global $conn;

// AUTO EXPIRE
mysqli_query($conn,"
UPDATE booking
SET status='expired'
WHERE status='pending'
AND TIMESTAMPDIFF(SECOND, created_at, NOW()) > 30
");

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];

// DATA BARBER
$barber = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT *
FROM barber
WHERE id='$id'
"));

if(!$barber){
    die("Barber tidak ditemukan");
}

// TOTAL BOOKING PAID SAJA
$totalBooking = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total
FROM booking
WHERE barber_id='$id'
AND status='paid'
"));

// TOTAL PENDAPATAN
$totalIncome = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT SUM(paket.harga) as total
FROM booking

JOIN paket
ON booking.paket_id = paket.id

WHERE booking.barber_id='$id'
AND booking.status='paid'
"));

$income = $totalIncome['total'];

if(!$income){
    $income = 0;
}

// BULAN INI
$currentMonth = date('m');
$currentYear = date('Y');

$monthIncome = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT SUM(paket.harga) as total
FROM booking

JOIN paket
ON booking.paket_id = paket.id

WHERE booking.barber_id='$id'
AND booking.status='paid'

AND MONTH(booking.created_at)='$currentMonth'

AND YEAR(booking.created_at)='$currentYear'
"));

$monthTotal = $monthIncome['total'];

if(!$monthTotal){
    $monthTotal = 0;
}

// TOTAL PENDING
$totalPending = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total
FROM booking
WHERE barber_id='$id'
AND status='pending'
"));

// TOTAL EXPIRED
$totalExpired = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total
FROM booking
WHERE barber_id='$id'
AND status='expired'
"));

// HISTORY BOOKING (PAID SAJA)
$history = mysqli_query($conn,"
SELECT
booking.*,
users.username,
paket.nama_paket,
paket.harga

FROM booking

JOIN users
ON booking.user_id = users.id

JOIN paket
ON booking.paket_id = paket.id

WHERE booking.barber_id='$id'
AND booking.status='paid'

ORDER BY booking.tanggal DESC,
booking.jam DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Statistik Barber</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:#0f0f0f;
    color:white;
    font-family:Arial;
    padding:30px;
}

h1{
    color:gold;
    margin-bottom:30px;
}

.stats{
    display:flex;
    gap:20px;
    flex-wrap:wrap;
    margin-bottom:35px;
}

.card{
    width:250px;
    background:#1b1b1b;
    padding:22px;
    border-radius:16px;
    border:1px solid #2c2c2c;
    transition:.3s;
}

.card:hover{
    transform:translateY(-4px);
    border-color:gold;
    box-shadow:0 0 20px rgba(255,215,0,.15);
}

.card h2{
    color:gold;
    margin-bottom:10px;
    font-size:28px;
}

.card p{
    color:#aaa;
}

.box{
    background:#1b1b1b;
    padding:25px;
    border-radius:18px;
    border:1px solid #2c2c2c;
}

.section-title{
    margin-bottom:20px;
    color:gold;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
    overflow:hidden;
    border-radius:12px;
}

th,td{
    padding:15px;
    text-align:left;
}

th{
    background:gold;
    color:black;
}

tr{
    background:#161616;
    border-bottom:1px solid #2c2c2c;
}

tr:hover{
    background:#222;
}

.paid{
    color:lightgreen;
    font-weight:bold;
}

.pending{
    color:orange;
    font-weight:bold;
}

.expired{
    color:#ff4d4d;
    font-weight:bold;
}

.back{
    display:inline-block;
    margin-top:25px;
    background:gold;
    color:black;
    padding:12px 18px;
    border-radius:10px;
    text-decoration:none;
    font-weight:bold;
    transition:.3s;
}

.back:hover{
    opacity:.85;
}

.empty{
    background:#161616;
    padding:30px;
    border-radius:14px;
    text-align:center;
    color:#888;
    margin-top:20px;
}

</style>

</head>
<body>

<h1>
Statistik <?= $barber['nama'] ?>
</h1>

<div class="stats">

<div class="card">
<h2><?= $totalBooking['total'] ?></h2>
<p>Total Booking Paid</p>
</div>

<div class="card">
<h2>Rp<?= number_format($income) ?></h2>
<p>Total Penghasilan</p>
</div>

<div class="card">
<h2>Rp<?= number_format($monthTotal) ?></h2>
<p>Penghasilan Bulan Ini</p>
</div>

<div class="card">
<h2><?= $totalPending['total'] ?></h2>
<p>Total Pending</p>
</div>

<div class="card">
<h2><?= $totalExpired['total'] ?></h2>
<p>Total Expired</p>
</div>

</div>

<div class="box">

<h2 class="section-title">
Riwayat Booking Paid
</h2>

<?php if(mysqli_num_rows($history) > 0){ ?>

<table>

<tr>
<th>Pelanggan</th>
<th>Paket</th>
<th>Harga</th>
<th>Tanggal</th>
<th>Jam</th>
<th>Status</th>
</tr>

<?php while($d = mysqli_fetch_assoc($history)){ ?>

<tr>

<td><?= $d['username'] ?></td>

<td><?= $d['nama_paket'] ?></td>

<td>
Rp<?= number_format($d['harga']) ?>
</td>

<td><?= $d['tanggal'] ?></td>

<td><?= $d['jam'] ?></td>

<td>

<span class="paid">
PAID
</span>

</td>

</tr>

<?php } ?>

</table>

<?php } else { ?>

<div class="empty">
Belum ada booking yang berhasil dibayar
</div>

<?php } ?>

<a href="dashboard.php" class="back">
Kembali Dashboard
</a>

</div>

</body>
</html>