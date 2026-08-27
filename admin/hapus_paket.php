<?php
include '../config.php';
global $conn;

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);

if($id > 0){
    // Check if package exists and get name for flash message
    $result = mysqli_query($conn, "SELECT nama_paket FROM paket WHERE id = $id");
    
    if($row = mysqli_fetch_assoc($result)){
        $nama = $row['nama_paket'];
        
        // Check if package is used in bookings
        $bookings = mysqli_query($conn, "SELECT COUNT(*) as total FROM booking WHERE paket_id = $id");
        $bookingCount = mysqli_fetch_assoc($bookings)['total'];
        
        if($bookingCount > 0){
            $_SESSION['flash'] = [
                'type' => 'warning',
                'title' => 'Cannot Delete',
                'message' => '"' . htmlspecialchars($nama) . '" is used in ' . $bookingCount . ' booking(s). Remove bookings first or change their package.'
            ];
        } else {
            if(mysqli_query($conn, "DELETE FROM paket WHERE id = $id")){
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'Package Deleted',
                    'message' => '"' . htmlspecialchars($nama) . '" has been deleted successfully.'
                ];
            } else {
                $_SESSION['flash'] = [
                    'type' => 'error',
                    'title' => 'Database Error',
                    'message' => 'Failed to delete package. Please try again.'
                ];
            }
        }
    } else {
        $_SESSION['flash'] = [
            'type' => 'error',
            'title' => 'Not Found',
            'message' => 'Package not found or already deleted.'
        ];
    }
} else {
    $_SESSION['flash'] = [
        'type' => 'error',
        'title' => 'Invalid Request',
        'message' => 'Invalid package ID.'
    ];
}

header("Location: paket.php");
exit;
?>