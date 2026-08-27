<?php
date_default_timezone_set('Asia/Jakarta');
include 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];

// Fetch user data
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$msg = '';
$error = '';

/* ==================== CHANGE PASSWORD ==================== */
if(isset($_POST['change_password'])){
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    $qPass = mysqli_query($conn, "SELECT password FROM users WHERE id='$userId'");
    $userPass = mysqli_fetch_assoc($qPass);

    if(!password_verify($current, $userPass['password'])){
        $error = "Current password is incorrect";
    } elseif(strlen($new) < 6){
        $error = "New password must be at least 6 characters";
    } elseif($new !== $confirm){
        $error = "Passwords do not match";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password='".mysqli_real_escape_string($conn, $hashed)."' WHERE id='$userId'");
        $msg = "Password changed successfully";
    }
}

/* ==================== DELETE ACCOUNT ==================== */
if(isset($_POST['delete_account'])){
    $confirm = $_POST['confirm_delete'] ?? '';
    if($confirm === 'DELETE'){
        // Only delete the user account — all history (bookings, ratings, photos) is preserved
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Clear session properly before redirect
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();

        header("Location: login.php?deleted=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings | Hostel Barbershop</title>

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
    --danger-soft: rgba(232, 132, 132, 0.08);
    --danger-border: rgba(232, 132, 132, 0.25);
    --warning: #e8c87a;
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

/* ===== MAIN CONTENT ===== */
.settings-main {
    padding: 140px 48px 80px;
    max-width: 720px;
    margin: 0 auto;
}

/* ===== WELCOME ===== */
.welcome-section {
    margin-bottom: 48px;
    position: relative;
}
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

/* ===== BACK LINK ===== */
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
.back-link i {
    font-size: 10px;
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
.card:nth-of-type(2) { animation-delay: 0.2s; }

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
.form-group input {
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
.form-group input:focus {
    border-color: var(--gold-border);
    box-shadow: 0 0 0 4px var(--gold-dim);
}
.form-group input::placeholder {
    color: var(--text-muted);
    opacity: 0.5;
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

/* ===== DIVIDER ===== */
.divider {
    height: 1px;
    background: var(--border);
    margin: 24px 0;
}

/* ===== PASSWORD TOGGLE ===== */
.password-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    padding: 4px 0;
}
.password-toggle span {
    font-size: 12px;
    color: var(--text-secondary);
    font-weight: 600;
    letter-spacing: 1px;
    transition: var(--transition);
}
.password-toggle:hover span {
    color: var(--text-primary);
}
.password-toggle i {
    font-size: 10px;
    color: var(--text-muted);
    transition: var(--transition);
}
.password-form {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s cubic-bezier(0.4,0,0.2,1), opacity 0.3s ease;
    opacity: 0;
}
.password-form.open {
    max-height: 500px;
    opacity: 1;
    margin-top: 20px;
}

/* ===== DANGER ZONE ===== */
.danger-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}
.danger-header i {
    font-size: 14px;
    color: var(--danger);
}
.danger-header h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 20px;
    font-weight: 400;
    letter-spacing: -0.5px;
    color: var(--danger);
}
.danger-desc {
    font-size: 14px;
    color: var(--text-muted);
    line-height: 1.7;
    margin-bottom: 20px;
}

.btn-danger-outline {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: transparent;
    border: 1.5px solid var(--danger-border);
    color: var(--danger);
    font-family: 'Montserrat', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    cursor: pointer;
    transition: var(--transition);
    border-radius: var(--radius);
}
.btn-danger-outline:hover {
    background: var(--danger-soft);
    border-color: var(--danger);
    transform: translateY(-2px);
}

/* ===== ALERT ===== */
.alert {
    padding: 14px 18px;
    border-radius: var(--radius);
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: fadeUp 0.4s ease;
}
.alert-success {
    background: rgba(110, 231, 160, 0.08);
    border: 1.5px solid rgba(110, 231, 160, 0.25);
    color: var(--success);
}
.alert-error {
    background: var(--danger-soft);
    border: 1.5px solid var(--danger-border);
    color: var(--danger);
}

/* ===== MODAL OVERLAY ===== */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(5,5,7,0.92);
    backdrop-filter: blur(20px);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
}
.modal-overlay.active {
    display: flex;
}

/* ===== POPUP CONFIRM ===== */
.popup-confirm {
    background: var(--bg-secondary);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: 48px;
    max-width: 420px;
    width: 100%;
    text-align: center;
    position: relative;
    overflow: hidden;
    animation: popupIn 0.5s cubic-bezier(0.25,0.46,0.45,0.94);
}
.popup-confirm::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--danger);
}
.popup-icon {
    width: 64px; height: 64px;
    border-radius: 50%;
    background: var(--danger-soft);
    border: 1.5px solid var(--danger-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin: 0 auto 20px;
    color: var(--danger);
}
.popup-confirm h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px;
    font-weight: 400;
    margin-bottom: 12px;
}
.popup-confirm p {
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 28px;
    line-height: 1.7;
}
.popup-actions {
    display: flex;
    gap: 12px;
}
.popup-actions button {
    flex: 1;
    padding: 12px;
    border-radius: var(--radius);
    font-family: 'Montserrat', sans-serif;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    cursor: pointer;
    transition: var(--transition);
    border: none;
}
.btn-cancel {
    background: var(--bg-hover);
    color: var(--text-secondary);
    border: 1.5px solid var(--border);
}
.btn-cancel:hover {
    border-color: var(--border-light);
    color: var(--text-primary);
}
.btn-yes {
    background: var(--danger);
    color: white;
}
.btn-yes:hover {
    opacity: 0.9;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(232,132,132,0.3);
}

/* ===== MODAL DELETE ===== */
.modal-delete {
    background: var(--bg-secondary);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: 40px;
    max-width: 460px;
    width: 100%;
    position: relative;
    overflow: hidden;
    animation: popupIn 0.5s cubic-bezier(0.25,0.46,0.45,0.94);
}
.modal-delete::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--danger);
}
.modal-delete h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 26px;
    font-weight: 400;
    margin-bottom: 12px;
}
.modal-delete > p {
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 8px;
    line-height: 1.6;
}
.warning-text {
    font-size: 13px;
    color: var(--danger);
    margin-bottom: 20px;
    padding: 12px 14px;
    background: var(--danger-soft);
    border-radius: var(--radius);
    border-left: 3px solid var(--danger);
    line-height: 1.6;
}
.modal-delete input {
    width: 100%;
    padding: 14px;
    background: var(--bg-input);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    color: var(--text-primary);
    font-family: 'Montserrat', sans-serif;
    font-size: 14px;
    margin-bottom: 20px;
    outline: none;
    transition: var(--transition);
}
.modal-delete input:focus {
    border-color: var(--danger);
    box-shadow: 0 0 0 4px var(--danger-soft);
}
.modal-delete input::placeholder {
    color: var(--text-muted);
    opacity: 0.5;
}

.btn-confirm-delete {
    flex: 1;
    padding: 12px;
    border-radius: var(--radius);
    font-family: 'Montserrat', sans-serif;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    cursor: pointer;
    transition: var(--transition);
    background: var(--danger);
    color: white;
    border: none;
}
.btn-confirm-delete:hover {
    opacity: 0.9;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(232,132,132,0.3);
}

/* ===== FOOTER ===== */
.footer {
    border-top: 1.5px solid var(--border);
    padding: 40px 0 0;
    margin-top: 40px;
}
.footer-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
}
.footer-copyright {
    font-size: 11px;
    color: var(--text-muted);
    letter-spacing: 1px;
}
.footer-copyright span { color: var(--gold); font-weight: 600; }

/* ===== ANIMATIONS ===== */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes popupIn {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

/* ===== SCROLLBAR ===== */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--bg-primary); }
::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: var(--gold-border); }

/* ===== RESPONSIVE ===== */
@media(max-width:900px) {
    .settings-main { padding: 120px 24px 60px; }
    .welcome-title { font-size: 36px; }
    .card { padding: 28px 24px; }
}
@media(max-width:600px) {
    .welcome-title { font-size: 28px; }
    .popup-confirm { padding: 32px 24px; }
    .modal-delete { padding: 32px 24px; }
    .footer-inner { flex-direction: column; text-align: center; }
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

<main class="settings-main">

    <!-- WELCOME -->
    <div class="welcome-section">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <div class="welcome-label">
            <div class="welcome-label-line"></div>
            <span class="welcome-label-text">Settings</span>
        </div>
        <h1 class="welcome-title">Account<br><em>Settings</em></h1>
    </div>

    <!-- ALERTS -->
    <?php if($msg): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>
    <?php if($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- CHANGE PASSWORD -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-lock"></i>
            <h3>Security</h3>
        </div>

        <div class="password-toggle" onclick="togglePassword()">
            <span>Change Password</span>
            <i class="fas fa-chevron-down" id="passArrow"></i>
        </div>

        <div class="password-form" id="passwordForm">
            <div class="divider"></div>
            <form method="POST">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" placeholder="Enter current password" required>
                </div>

                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="Min 6 characters" minlength="6" required>
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Repeat new password" required>
                </div>

                <button type="submit" name="change_password" class="save-btn">
                    <i class="fas fa-key"></i> Update Password
                </button>
            </form>
        </div>
    </div>

    <!-- DELETE ACCOUNT -->
    <div class="card">
        <div class="danger-header">
            <i class="fas fa-trash-alt"></i>
            <h3>Delete Account</h3>
        </div>
        <p class="danger-desc">
            Permanently delete your account. This action cannot be undone.
        </p>
        <button type="button" class="btn-danger-outline" onclick="openPopup()">
            <i class="fas fa-trash-alt"></i> Delete Account
        </button>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <div class="footer-inner">
            <div class="footer-copyright">&copy; <?= date('Y') ?> <span>Hostel Barbershop</span> &mdash; Precision in every cut</div>
        </div>
    </div>

</main>

<!-- POPUP PERTAMA: YAKIN NIH? -->
<div class="modal-overlay" id="popupConfirm">
    <div class="popup-confirm">
        <div class="popup-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3>Yakin nih?</h3>
        <p>Akun bakal dihapus permanen. Gak bisa balik lagi.</p>
        <div class="popup-actions">
            <button type="button" class="btn-cancel" onclick="closePopup()">Cancel</button>
            <button type="button" class="btn-yes" onclick="yesDelete()">Yes, Delete</button>
        </div>
    </div>
</div>

<!-- POPUP KEDUA: KETIK DELETE -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-delete">
        <h3>Delete Account</h3>
        <p>This action <strong style="color:var(--text-primary);">cannot be undone</strong>.</p>
        <div class="warning-text">
            <i class="fas fa-info-circle" style="margin-right:6px;"></i>
            Your account will be deleted.
        </div>
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px;">
            Type <strong style="color:var(--danger);">DELETE</strong> to confirm
        </p>

        <form method="POST" id="deleteForm">
            <input type="text" name="confirm_delete" placeholder="Type DELETE here" autocomplete="off" required>
            <div class="popup-actions">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()" style="flex:1;">Cancel</button>
                <button type="submit" name="delete_account" class="btn-confirm-delete">
                    <i class="fas fa-trash-alt" style="margin-right:6px;"></i>Delete
                </button>
            </div>
        </form>
    </div>
</div>

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

// ===== PASSWORD TOGGLE =====
function togglePassword(){
    const form = document.getElementById('passwordForm');
    const arrow = document.getElementById('passArrow');
    form.classList.toggle('open');
    arrow.style.transform = form.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
}

// ===== DELETE MODALS =====
function openPopup(){
    document.getElementById('popupConfirm').classList.add('active');
}
function closePopup(){
    document.getElementById('popupConfirm').classList.remove('active');
}
function yesDelete(){
    closePopup();
    document.getElementById('deleteModal').classList.add('active');
    setTimeout(() => {
        document.querySelector('input[name="confirm_delete"]').focus();
    }, 100);
}
function closeDeleteModal(){
    document.getElementById('deleteModal').classList.remove('active');
    document.querySelector('input[name="confirm_delete"]').value = '';
}

// Close on overlay click
document.getElementById('popupConfirm').addEventListener('click', function(e){
    if(e.target === this) closePopup();
});
document.getElementById('deleteModal').addEventListener('click', function(e){
    if(e.target === this) closeDeleteModal();
});

// Escape key
document.addEventListener('keydown', function(e){
    if(e.key === 'Escape'){
        closePopup();
        closeDeleteModal();
        navProfileDropdown.classList.remove('show');
    }
});
</script>

</body>
</html>