<?php
date_default_timezone_set('Asia/Jakarta');
include 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];

/* FETCH USER */
$q = mysqli_query($conn, "SELECT * FROM users WHERE id='$userId'");
$user = mysqli_fetch_assoc($q);

$uploadError = '';
$uploadDir = 'upload/profile/';

/* UPLOAD PHOTO */
if(isset($_POST['upload_photo']) && isset($_FILES['photo'])){
    $file = $_FILES['photo'];

    if($file['error'] !== UPLOAD_ERR_OK){
        $uploadError = "Upload error code: " . $file['error'];
        goto skipUpload;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if(!in_array($ext, $allowed)){
        $uploadError = "Invalid file type. Only JPG, PNG, WEBP allowed.";
        goto skipUpload;
    }

    if($file['size'] > 2*1024*1024){
        $uploadError = "File too large. Max 2MB.";
        goto skipUpload;
    }

    if(!is_dir($uploadDir)){
        if(!mkdir($uploadDir, 0777, true)){
            $uploadError = "Failed to create upload folder. Check permissions.";
            goto skipUpload;
        }
    }

    if(!is_writable($uploadDir)){
        $uploadError = "Upload folder not writable. Check permissions.";
        goto skipUpload;
    }

    $newName = time() . '_' . rand(1000,9999) . '.' . $ext;
    $path = $uploadDir . $newName;

    if(move_uploaded_file($file['tmp_name'], $path)){
        if(!empty($user['photo']) && file_exists($user['photo']) && $user['photo'] !== $path){
            unlink($user['photo']);
        }

        $escPath = mysqli_real_escape_string($conn, $path);
        $update = mysqli_query($conn, "UPDATE users SET photo='$escPath' WHERE id='$userId'");

        if($update){
            header("Location: profile.php?success=photo");
            exit;
        } else {
            $uploadError = "Database error: " . mysqli_error($conn);
        }
    } else {
        $uploadError = "Failed to move uploaded file. Check folder permissions.";
    }
}
skipUpload:

/* UPDATE PROFILE */
if(isset($_POST['save_profile'])){
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $phone    = preg_replace('/[^0-9]/', '', $_POST['phone']);
    $gender   = $_POST['gender'];

    if(strlen($phone) > 13){
        header("Location: profile.php?error=phone_max");
        exit;
    }

    mysqli_query($conn, "
        UPDATE users SET 
        username='".mysqli_real_escape_string($conn, $username)."',
        phone='".mysqli_real_escape_string($conn, $phone)."',
        gender='".mysqli_real_escape_string($conn, $gender)."',
        updated_at=NOW()
        WHERE id='$userId'
    ");

    header("Location: profile.php?success=1");
    exit;
}

/* MESSAGES */
$msg = '';
if(isset($_GET['success'])){
    if($_GET['success'] == '1') $msg = "Profile updated successfully";
    if($_GET['success'] == 'photo') $msg = "Photo updated successfully";
}
if(isset($_GET['error'])){
    if($_GET['error'] == 'phone_max') $msg = "Phone number max 13 digits";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile | Hostel Barbershop</title>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
:root {
    --bg-primary: #050507;
    --bg-secondary: #0c0c10;
    --bg-card: #13131a;
    --bg-hover: #1e1e28;
    --bg-input: #0a0a0f;
    --border: rgba(255, 255, 255, 0.14);
    --border-light: rgba(255, 255, 255, 0.28);
    --text-primary: #ffffff;
    --text-secondary: #d5d0c8;
    --text-muted: #9e998f;
    --gold: #e8c87a;
    --gold-light: #f5e6c3;
    --gold-dim: rgba(232, 200, 122, 0.10);
    --gold-border: rgba(232, 200, 122, 0.45);
    --success: #6ee7a0;
    --danger: #e88484;
    --radius: 2px;
    --transition: all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}

*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

body {
    background: var(--bg-primary);
    color: var(--text-primary);
    font-family: 'Montserrat', sans-serif;
    line-height: 1.6;
    min-height: 100vh;
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
}

/* ===== SCROLL PROGRESS BAR ===== */
.scroll-line {
    position: absolute;
    left: 0;
    bottom: 0;
    width: 0%;
    height: 3px;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
    z-index: 9990;
    transition: width 0.1s linear;
    box-shadow: 0 0 16px rgba(232,200,122,0.5);
}

/* ===== NAVIGATION ===== */
.nav-wrapper {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 9000;
    padding: 24px 0;
    transition: var(--transition);
}
.nav-wrapper.scrolled {
    background: rgba(5,5,7,0.95);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border-bottom: 1px solid var(--border);
    padding: 16px 0;
}
.nav-inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.nav-left-group {
    display: flex;
    align-items: center;
    gap: 48px;
}
.nav-logo {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: var(--text-primary);
    margin-left: -8px;
}
.nav-logo-img {
    height: 52px;
    width: auto;
    display: block;
    transition: opacity 0.3s ease, transform 0.3s ease;
    filter: brightness(0.95) contrast(1.1);
}
.nav-logo:hover .nav-logo-img {
    opacity: 0.85;
    transform: scale(0.97);
}
.nav-links {
    display: flex;
    align-items: center;
    gap: 0;
}
.nav-link {
    padding: 10px 24px;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    transition: var(--transition);
    position: relative;
}
.nav-link::after {
    content: '';
    position: absolute;
    bottom: 6px; left: 24px; right: 24px;
    height: 1.5px;
    background: var(--gold);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}
.nav-link:hover, .nav-link.active { color: var(--text-primary); }
.nav-link:hover::after, .nav-link.active::after { transform: scaleX(1); }
.nav-profile-wrapper { position: relative; }
.nav-profile-trigger {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 16px;
    background: transparent;
    border: 1.5px solid var(--border);
    color: var(--text-secondary);
    cursor: pointer;
    transition: var(--transition);
    font-family: 'Montserrat', sans-serif;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 1px;
}
.nav-profile-trigger:hover {
    border-color: var(--gold-border);
    color: var(--gold);
}
.nav-profile-avatar {
    width: 32px; height: 32px;
    border-radius: 50%;
    object-fit: cover;
    border: 1.5px solid var(--border);
}
.nav-profile-avatar-initial {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: var(--bg-hover);
    border: 1.5px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Cormorant Garamond', serif;
    font-size: 14px;
    font-weight: 600;
    color: var(--gold);
}
.nav-profile-dropdown {
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    background: var(--bg-secondary);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    width: 200px;
    overflow: hidden;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: var(--transition);
    z-index: 99;
    box-shadow: 0 20px 56px rgba(0,0,0,0.5);
}
.nav-profile-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}
.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 1px;
    transition: var(--transition);
    border-bottom: 1px solid var(--border);
    position: relative;
    overflow: hidden;
}
.dropdown-item:last-child {
    border-bottom: none;
    color: var(--danger);
}
.dropdown-item::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold);
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    z-index: -1;
}
.dropdown-item:hover {
    color: var(--bg-primary);
}
.dropdown-item:hover::before {
    transform: translateX(0);
}
.dropdown-item:last-child::before { background: var(--danger); }
.dropdown-item:last-child:hover { color: white; }
.dropdown-item i { font-size: 13px; width: 18px; text-align: center; position: relative; z-index: 1; }
.dropdown-item span { position: relative; z-index: 1; }
.nav-mobile-toggle {
    display: none;
    width: 44px; height: 44px;
    align-items: center; justify-content: center;
    background: transparent;
    border: 1.5px solid var(--border);
    color: var(--text-primary);
    font-size: 16px;
    cursor: pointer;
    transition: var(--transition);
}
.nav-mobile-toggle:hover { border-color: var(--gold-border); color: var(--gold); }
@media(max-width:900px) {
    .nav-links { display: none; position: absolute; top: 100%; left: 0; right: 0; background: rgba(5,5,7,0.98); border-bottom: 1px solid var(--border); padding: 24px 0; flex-direction: column; gap: 0; }
    .nav-links.show { display: flex; }
    .nav-profile-wrapper { display: none; }
    .nav-mobile-toggle { display: flex; }
    .nav-inner { padding: 0 24px; }
    .nav-left-group { gap: 32px; }
}

/* ===== MAIN ===== */
.profile-main {
    padding: 140px 48px 80px;
    max-width: 600px;
    margin: 0 auto;
}

/* ===== WELCOME ===== */
.welcome-section {
    margin-bottom: 48px;
}
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--text-muted);
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 1px;
    margin-bottom: 32px;
    transition: var(--transition);
}
.back-link:hover {
    color: var(--gold);
    transform: translateX(-4px);
}
.back-link i { font-size: 10px; }

.welcome-label {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}
.welcome-label-line {
    width: 40px;
    height: 1.5px;
    background: var(--gold);
}
.welcome-label-text {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--text-muted);
}
.welcome-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(32px, 4vw, 48px);
    font-weight: 300;
    letter-spacing: -1.5px;
    line-height: 1.1;
}
.welcome-title em {
    font-style: italic;
    color: var(--gold);
}

/* ===== PROFILE HEADER ===== */
.profile-header {
    text-align: center;
    margin-bottom: 40px;
    animation: fadeUp 0.5s ease forwards;
    opacity: 0;
}
.avatar-wrapper {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 20px;
}
.avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: var(--bg-input);
    border: 3px solid var(--border);
    overflow: hidden;
    position: relative;
    box-shadow: 0 8px 32px rgba(0,0,0,0.5);
    transition: var(--transition);
}
.avatar:hover {
    border-color: var(--gold-border);
    transform: scale(1.02);
}
.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Cormorant Garamond', serif;
    font-size: 48px;
    font-weight: 700;
    color: var(--text-muted);
    background: linear-gradient(135deg, var(--bg-input), var(--bg-hover));
}
.avatar-upload {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--gold);
    color: var(--bg-primary);
    border: 3px solid var(--bg-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 14px;
    transition: var(--transition);
    box-shadow: 0 2px 12px rgba(0,0,0,0.4);
}
.avatar-upload:hover {
    transform: scale(1.15);
    box-shadow: 0 4px 16px rgba(232,200,122,0.3);
}
.profile-header h1 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px;
    font-weight: 400;
    letter-spacing: -0.5px;
    margin-bottom: 6px;
}
.profile-header p {
    font-size: 14px;
    color: var(--text-muted);
}

/* ===== CARD ===== */
.card {
    background: var(--bg-secondary);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: 36px;
    margin-bottom: 24px;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    animation: fadeUp 0.6s ease forwards;
    opacity: 0;
}
.card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--gold);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.5s cubic-bezier(0.25,0.46,0.45,0.94);
}
.card:hover {
    border-color: var(--gold-border);
    background: var(--bg-hover);
}
.card:hover::before { transform: scaleX(1); }
.card:nth-of-type(1) { animation-delay: 0.1s; }

.card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
}
.card-header i {
    font-size: 14px;
    color: var(--gold);
}
.card-header h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 20px;
    font-weight: 400;
    letter-spacing: -0.5px;
    color: var(--text-primary);
}

/* ===== FORM ===== */
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 10px;
}
.form-group input,
.form-group select {
    width: 100%;
    padding: 14px 16px;
    background: var(--bg-input);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    color: var(--text-primary);
    font-family: 'Montserrat', sans-serif;
    font-size: 13px;
    outline: none;
    transition: var(--transition);
}
.form-group input:focus,
.form-group select:focus {
    border-color: var(--gold-border);
    box-shadow: 0 0 0 4px var(--gold-dim);
}
.form-group input:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: var(--bg-secondary);
}
.form-group input::placeholder,
.form-group select option:first-child {
    color: var(--text-muted);
    opacity: 0.5;
}
.input-hint {
    font-size: 11px;
    color: var(--text-muted);
    margin-top: 6px;
    letter-spacing: 0.3px;
}

.save-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 32px;
    border: none;
    border-radius: var(--radius);
    background: var(--gold);
    color: var(--bg-primary);
    font-family: 'Montserrat', sans-serif;
    font-weight: 700;
    font-size: 11px;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    cursor: pointer;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}
.save-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(232,200,122,0.3);
}
.save-btn i {
    transition: transform 0.3s ease;
}
.save-btn:hover i {
    transform: translateX(3px);
}

/* ===== DEBUG ===== */
.debug-info {
    background: var(--danger-soft);
    border: 1.5px solid var(--danger-border);
    border-radius: var(--radius);
    padding: 14px 18px;
    margin-bottom: 24px;
    font-size: 13px;
    color: var(--danger);
    display: flex;
    align-items: center;
    gap: 10px;
    animation: fadeUp 0.4s ease;
}
.debug-info i {
    font-size: 14px;
}

/* ===== TOAST ===== */
.toast {
    position: fixed;
    top: 24px;
    right: 24px;
    padding: 14px 20px;
    border-radius: var(--radius);
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 8px 32px rgba(0,0,0,0.5);
    z-index: 9999;
    animation: slideIn 0.4s cubic-bezier(0.4,0,0.2,1), fadeOut 0.4s ease 2.6s forwards;
    display: flex;
    align-items: center;
    gap: 10px;
}
.toast.success {
    background: rgba(110, 231, 160, 0.1);
    border: 1.5px solid rgba(110, 231, 160, 0.3);
    color: var(--success);
}
.toast i { font-size: 14px; }

@keyframes slideIn {
    from { transform: translateX(100px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes fadeOut {
    to { opacity: 0; transform: translateY(-10px); }
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ===== FILE INPUT ===== */
.file-input { display: none; }

/* ===== SCROLLBAR ===== */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--bg-primary); }
::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: var(--gold-border); }

/* ===== RESPONSIVE ===== */
@media(max-width:900px) {
    .profile-main { padding: 120px 24px 60px; }
    .welcome-title { font-size: 36px; }
    .card { padding: 28px 24px; }
}
@media(max-width:600px) {
    .welcome-title { font-size: 28px; }
    .avatar-wrapper { width: 100px; height: 100px; }
    .avatar-placeholder { font-size: 40px; }
}
</style>
</head>

<body>

<!-- NAVIGATION -->
<nav class="nav-wrapper" id="mainNav">
    <div class="nav-inner">
        <div class="nav-left-group">
            <a href="dashboard.php" class="nav-logo">
                <img src="hostel.png" alt="Hostel Barbershop" class="nav-logo-img">
            </a>
            <div class="nav-links" id="navLinks">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
            </div>
        </div>
        <div class="nav-profile-wrapper" id="navProfileWrapper">
            <div class="nav-profile-trigger" id="navProfileTrigger">
                <?php if(!empty($user['photo']) && file_exists($user['photo'])): ?>
                <img src="<?= $user['photo'] ?>" alt="<?= htmlspecialchars($user['username']) ?>" class="nav-profile-avatar" onerror="this.style.display='none'; this.parentNode.innerHTML='<div class=nav-profile-avatar-initial><?= strtoupper(substr($user['username'],0,1)) ?></div>';">
                <?php else: ?>
                <div class="nav-profile-avatar-initial"><?= strtoupper(substr($user['username'],0,1)) ?></div>
                <?php endif; ?>
                <span><?= htmlspecialchars($user['username']) ?></span>
                <i class="fas fa-chevron-down" style="font-size:9px; margin-left:4px;"></i>
            </div>
            <div class="nav-profile-dropdown" id="navProfileDropdown">
                <a href="profile.php" class="dropdown-item"><i class="fas fa-user"></i> <span>Account</span></a>
                <a href="settings.php" class="dropdown-item"><i class="fas fa-cog"></i> <span>Settings</span></a>
                <div style="height:1px; background:var(--border); margin:4px 0;"></div>
                <a href="logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        <button class="nav-mobile-toggle" id="mobileToggle" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
    </div>
    <div class="scroll-line" id="scrollLine"></div>
</nav>

<main class="profile-main">

    <!-- WELCOME -->
    <div class="welcome-section">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <div class="welcome-label">
            <div class="welcome-label-line"></div>
            <span class="welcome-label-text">Account</span>
        </div>
        <h1 class="welcome-title">Your<br><em>Profile</em></h1>
    </div>

    <!-- DEBUG ERROR -->
    <?php if($uploadError){ ?>
    <div class="debug-info">
        <i class="fas fa-exclamation-triangle"></i>
        <?= htmlspecialchars($uploadError) ?>
    </div>
    <?php } ?>

    <!-- PROFILE HEADER -->
    <div class="profile-header">
        <div class="avatar-wrapper">
            <div class="avatar">
                <?php 
                $photoPath = $user['photo'] ?? '';
                $photoExists = !empty($photoPath) && file_exists($photoPath);

                if($photoExists){ 
                ?>
                <img src="<?= $photoPath ?>" alt="<?= htmlspecialchars($user['username']) ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="avatar-placeholder" style="display:none;"><?= strtoupper(substr($user['username'],0,1)) ?></div>
                <?php } else { ?>
                <div class="avatar-placeholder"><?= strtoupper(substr($user['username'],0,1)) ?></div>
                <?php } ?>
            </div>

            <form method="POST" enctype="multipart/form-data" style="display:inline;">
                <input type="file" name="photo" id="photoInput" class="file-input" accept="image/*" onchange="this.form.submit()">
                <input type="hidden" name="upload_photo" value="1">
                <button type="button" class="avatar-upload" onclick="document.getElementById('photoInput').click()" title="Change Photo">
                    <i class="fas fa-camera"></i>
                </button>
            </form>
        </div>

        <h1><?= htmlspecialchars($user['username']) ?></h1>
        <p><?= htmlspecialchars($user['email'] ?? 'No email set') ?></p>
    </div>

    <!-- EDIT PROFILE -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-user-edit"></i>
            <h3>Edit Profile</h3>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?= htmlspecialchars($user['email'] ?? 'Not set') ?>" disabled>
                <div class="input-hint">Email cannot be changed</div>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input 
                    type="tel" 
                    name="phone" 
                    value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                    placeholder="08xxxxxxxxxx"
                    pattern="[0-9]*"
                    maxlength="13"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,13)"
                >
                <div class="input-hint">Numbers only, max 13 digits</div>
            </div>

            <div class="form-group">
                <label>Gender</label>
                <select name="gender">
                    <option value="" <?= empty($user['gender']) ? 'selected' : '' ?>>Select gender</option>
                    <option value="Laki-laki" <?= ($user['gender'] ?? '') == 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="Perempuan" <?= ($user['gender'] ?? '') == 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                </select>
            </div>

            <button type="submit" name="save_profile" class="save-btn">
                <i class="fas fa-save" style="margin-right:6px;"></i>Save Changes
            </button>
        </form>
    </div>

</main>

<?php if($msg){ ?>
<div class="toast success">
    <i class="fas fa-check-circle"></i>
    <?= htmlspecialchars($msg) ?>
</div>
<?php } ?>

<script>
// ===== SCROLL & NAV =====
const mainNav = document.getElementById('mainNav');
const scrollLine = document.getElementById('scrollLine');

window.addEventListener('scroll', () => {
    const scrollY = window.pageYOffset;
    const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const pct = scrollHeight > 0 ? (scrollY / scrollHeight) * 100 : 0;
    scrollLine.style.width = pct + '%';
    mainNav.classList.toggle('scrolled', scrollY > 60);
}, { passive: true });

// Mobile toggle
const mobileToggle = document.getElementById('mobileToggle');
const navLinks = document.getElementById('navLinks');
mobileToggle.addEventListener('click', () => {
    navLinks.classList.toggle('show');
    const icon = mobileToggle.querySelector('i');
    icon.className = navLinks.classList.contains('show') ? 'fas fa-times' : 'fas fa-bars';
});

// Profile dropdown
const navProfileTrigger = document.getElementById('navProfileTrigger');
const navProfileDropdown = document.getElementById('navProfileDropdown');
const navProfileWrapper = document.getElementById('navProfileWrapper');

navProfileTrigger.addEventListener('click', function(e) {
    e.stopPropagation();
    navProfileDropdown.classList.toggle('show');
});
document.addEventListener('click', function(e) {
    if (!navProfileWrapper.contains(e.target)) {
        navProfileDropdown.classList.remove('show');
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        navProfileDropdown.classList.remove('show');
    }
});
</script>

</body>
</html>