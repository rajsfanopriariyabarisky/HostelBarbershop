<?php

include '../config.php';
global $conn;

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

$q = mysqli_query($conn,
"
SELECT
booking.*,
users.username,
paket.nama_paket,
barber.nama as barber_nama

FROM booking

JOIN users ON booking.user_id = users.id
JOIN paket ON booking.paket_id = paket.id
JOIN barber ON booking.barber_id = barber.id

WHERE booking.status='expired'

ORDER BY booking.id DESC
"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Booking Expired</title>

    <style>

        body{
            font-family:Arial;
            background:#111;
            color:white;
            padding:30px;
        }

        h1{
            margin-bottom:20px;
        }

        a{
            color:gold;
            text-decoration:none;
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-top:20px;
        }

        th,td{
            border:1px solid #333;
            padding:12px;
            text-align:left;
        }

        th{
            background:gold;
            color:black;
        }

        tr:nth-child(even){
            background:#1e1e1e;
        }

        .expired{
            color:red;
            font-weight:bold;
        }

    </style>
</head>
<body>

<h1>Riwayat Booking Expired</h1>

<a href="dashboard.php">
← Kembali Dashboard
</a>

<br><br>

<table>

<tr>

<th>User</th>
<th>Paket</th>
<th>Barber</th>
<th>Tanggal</th>
<th>Jam</th>
<th>Status</th>

</tr>

<?php while($d = mysqli_fetch_assoc($q)){ ?>

<tr>

<td><?= $d['username'] ?></td>

<td><?= $d['nama_paket'] ?></td>

<td><?= $d['barber_nama'] ?></td>

<td><?= $d['tanggal'] ?></td>

<td><?= $d['jam'] ?></td>

<td>
<span class="expired">
EXPIRED
</span>
</td>

</tr>

<?php } ?>

</table>

</body>
</html>