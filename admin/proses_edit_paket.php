<?php
include '../config.php';
global $conn;

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $id = intval($_POST['id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    $harga = intval($_POST['harga'] ?? 0);
    
    // Validation
    $errors = [];
    
    if($id <= 0){
        $errors[] = "Invalid package ID";
    }
    
    if(empty($nama) || strlen($nama) < 2){
        $errors[] = "Package name must be at least 2 characters";
    }
    
    if($harga < 1000){
        $errors[] = "Minimum price is Rp 1,000";
    }
    
    // Check duplicate name (exclude current ID)
    $check = mysqli_query($conn, "SELECT id FROM paket WHERE nama_paket = '" . mysqli_real_escape_string($conn, $nama) . "' AND id != $id");
    if(mysqli_num_rows($check) > 0){
        $errors[] = "Package name already exists";
    }
    
    // Check if package exists
    $exists = mysqli_query($conn, "SELECT id FROM paket WHERE id = $id");
    if(mysqli_num_rows($exists) === 0){
        $errors[] = "Package not found";
    }
    
    if(empty($errors)){
        $namaEscaped = mysqli_real_escape_string($conn, $nama);
        $query = "UPDATE paket SET nama_paket = '$namaEscaped', harga = $harga WHERE id = $id";
        
        if(mysqli_query($conn, $query)){
            $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Package Updated',
                'message' => '"' . htmlspecialchars($nama) . '" has been updated successfully.'
            ];
        } else {
            $_SESSION['flash'] = [
                'type' => 'error',
                'title' => 'Database Error',
                'message' => 'Failed to update package. Please try again.'
            ];
        }
    } else {
        $_SESSION['flash'] = [
            'type' => 'error',
            'title' => 'Validation Failed',
            'message' => implode(', ', $errors)
        ];
    }
}

header("Location: paket.php");
exit;
?>