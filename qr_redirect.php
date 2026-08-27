<?php
// qr_redirect.php
// QR code scan handler — langsung redirect ke download

// Ambil booking ID dari URL parameter
$bookingId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($bookingId <= 0){
    // Kalo gak ada ID, kasih tau user
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Invalid QR | Barberku</title>
        <style>
            body{background:#0a0a0a;color:#fff;font-family:Inter,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;text-align:center;padding:20px;}
            .box{max-width:400px;}
            .icon{font-size:48px;margin-bottom:16px;}
            h1{font-size:24px;margin-bottom:8px;}
            p{color:#888;margin-bottom:24px;}
            a{color:#00e676;text-decoration:none;font-weight:700;}
        </style>
    </head>
    <body>
        <div class='box'>
            <div class='icon'>⚠️</div>
            <h1>Invalid QR Code</h1>
            <p>This QR code is invalid or expired.</p>
            <a href='dashboard.php'>Go to Dashboard</a>
        </div>
    </body>
    </html>";
    exit;
}

// Langsung redirect ke download.php
header("Location: download.php?id=" . $bookingId);
exit;
?>