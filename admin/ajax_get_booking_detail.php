<?php
include '../config.php';
global $conn;

if(!isset($_SESSION['admin'])){
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = intval($_GET['id'] ?? 0);

if($id <= 0){
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$query = "
SELECT b.*, u.username, u.email, u.phone, u.gender,
       p.nama_paket, p.harga,
       bar.nama as barber_nama
FROM booking b
JOIN users u ON b.user_id = u.id
JOIN paket p ON b.paket_id = p.id
JOIN barber bar ON b.barber_id = bar.id
WHERE b.id = $id
LIMIT 1
";

$result = mysqli_query($conn, $query);

if($result && mysqli_num_rows($result) > 0){
    $booking = mysqli_fetch_assoc($result);
    $booking['tanggal_formatted'] = date('d M Y', strtotime($booking['tanggal']));
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'booking' => $booking
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
}
?>