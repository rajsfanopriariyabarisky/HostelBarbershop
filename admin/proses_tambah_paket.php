<?php
include '../config.php';
global $conn;

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nama = trim($_POST['nama'] ?? '');
    $harga = intval($_POST['harga'] ?? 0);
    
    // Validation
    $errors = [];
    
    if(empty($nama) || strlen($nama) < 2){
        $errors[] = "Package name must be at least 2 characters";
    }
    
    if($harga < 1000){
        $errors[] = "Minimum price is Rp 1,000";
    }
    
    // Check duplicate name
    $check = mysqli_query($conn, "SELECT id FROM paket WHERE nama_paket = '" . mysqli_real_escape_string($conn, $nama) . "'");
    if(mysqli_num_rows($check) > 0){
        $errors[] = "Package name already exists";
    }
    
    if(empty($errors)){
        $namaEscaped = mysqli_real_escape_string($conn, $nama);
        $query = "INSERT INTO paket (nama_paket, harga) VALUES ('$namaEscaped', $harga)";
        
        if(mysqli_query($conn, $query)){
            $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Package Added',
                'message' => '"' . htmlspecialchars($nama) . '" has been added successfully.'
            ];
        } else {
            $_SESSION['flash'] = [
                'type' => 'error',
                'title' => 'Database Error',
                'message' => 'Failed to add package. Please try again.'
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