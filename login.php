<?php
include 'config.php';

if(isset($_POST['login'])){

    $email = $_POST['email'];
    $password = $_POST['password'];

    $q = mysqli_query($conn,"
        SELECT * FROM users WHERE email='$email'
    ");

    $data = mysqli_fetch_assoc($q);

    if($data && password_verify($password, $data['password'])){

        if(isset($data['status']) && $data['status'] == 'banned'){

            echo "<script>
                window.onload = function(){
                    showError('Akun kamu telah dibanned admin');
                }
            </script>";

        }else{

            $_SESSION['user'] = [
                'id' => $data['id'],
                'email' => $data['email'],
                'username' => $data['username']
            ];

            echo "<script>window.location='dashboard.php';</script>";
            exit;

        }

    } else {

        $error = "Email atau password salah";

        echo "<script>
            window.onload = function(){
                showError('$error');
            }
        </script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login | Hostel Barbershop</title>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
:root {
    --bg-primary: #050507;
    --bg-secondary: #0c0c10;
    --bg-card: #13131a;
    --bg-hover: #1e1e28;
    --border: rgba(255, 255, 255, 0.14);
    --border-light: rgba(255, 255, 255, 0.28);
    --text-primary: #ffffff;
    --text-secondary: #d5d0c8;
    --text-muted: #9e998f;
    --gold: #e8c87a;
    --gold-light: #f5e6c3;
    --gold-dim: rgba(232, 200, 122, 0.10);
    --gold-border: rgba(232, 200, 122, 0.45);
    --gold-glow: rgba(232, 200, 122, 0.25);
    --success: #6ee7a0;
    --success-dim: rgba(110, 231, 160, 0.12);
    --success-border: rgba(110, 231, 160, 0.35);
    --danger: #e88484;
    --danger-dim: rgba(232, 100, 100, 0.12);
    --danger-border: rgba(232, 100, 100, 0.35);
    --warning: #e8c87a;
    --radius: 2px;
    --transition: all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body {
    background: var(--bg-primary);
    color: var(--text-primary);
    font-family: 'Montserrat', sans-serif;
    line-height: 1.6;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    -webkit-font-smoothing: antialiased;
}

/* Logo & Home above card */
.home-top-left {
    position: fixed;
    top: 24px;
    left: 24px;
    z-index: 100;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    border-radius: 2px;
    color: var(--text-muted);
    font-size: 14px;
    text-decoration: none;
    transition: all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}

.home-top-left:hover {
    border-color: var(--gold-border);
    color: var(--gold);
    background: var(--bg-hover);
}

.login-header {
    text-align: center;
    margin-bottom: 28px;
}

.login-header img {
    width: 90px;
    height: 90px;
    object-fit: contain;
    border-radius: 14px;
    display: block;
    margin: 0 auto 14px;
    filter: brightness(0.95) contrast(1.1);
}

.home-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    border-radius: 2px;
    color: var(--text-muted);
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-decoration: none;
    transition: all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
    cursor: pointer;
}

.home-btn:hover {
    border-color: var(--gold-border);
    color: var(--gold);
    background: var(--bg-hover);
}

.home-btn i {
    font-size: 11px;
}

/* Card */
.card {
    width: 380px;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    padding: 40px 36px;
    border-radius: 2px;
    position: relative;
    overflow: hidden;
}

.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
    opacity: 0.6;
}

h2 {
    text-align: center;
    margin-bottom: 32px;
    font-family: 'Cormorant Garamond', serif;
    font-size: 36px;
    font-weight: 400;
    letter-spacing: -1px;
    color: var(--text-primary);
}

h2 em {
    font-style: italic;
    color: var(--gold);
}

input {
    width: 100%;
    padding: 14px 16px;
    margin-bottom: 14px;
    border-radius: 2px;
    border: 1.5px solid var(--border);
    background: var(--bg-hover);
    color: var(--text-primary);
    font-family: 'Montserrat', sans-serif;
    font-size: 13px;
    outline: none;
    transition: all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}

input::placeholder {
    color: var(--text-muted);
}

input:focus {
    border-color: var(--gold-border);
    box-shadow: 0 0 0 4px var(--gold-dim);
}

/* Password field wrapper */
.password-wrap {
    position: relative;
    margin-bottom: 14px;
}

.password-wrap input {
    margin-bottom: 0;
    padding-right: 44px;
}

.toggle-password {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    width: 44px;
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.3s ease;
}

.toggle-password:hover {
    color: var(--gold);
}

button[name="login"] {
    width: 100%;
    padding: 14px;
    border: 1.5px solid var(--gold-border);
    border-radius: 2px;
    background: transparent;
    color: var(--gold);
    font-family: 'Montserrat', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
    position: relative;
    overflow: hidden;
    margin-top: 8px;
}

button[name="login"]::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}

button[name="login"]:hover {
    color: var(--bg-primary);
}

button[name="login"]:hover::before {
    transform: scaleX(1);
}

button[name="login"] span {
    position: relative;
    z-index: 1;
}

.register-link {
    text-align: center;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1.5px solid var(--border);
}

.register-link span {
    color: var(--text-muted);
    font-size: 12px;
}

.register-link a {
    color: var(--text-secondary);
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    margin-left: 4px;
    transition: color 0.3s ease;
}

.register-link a:hover {
    color: var(--gold);
}

/* Popup */
.popup {
    position: fixed;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(5,5,7,0.85);
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 999;
}

.popup.show {
    opacity: 1;
    visibility: visible;
}

.popup-box {
    width: 320px;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    border-radius: 2px;
    padding: 32px;
    text-align: center;
    transform: scale(0.95);
    transition: transform 0.3s ease;
}

.popup.show .popup-box {
    transform: scale(1);
}

.popup-icon {
    font-size: 32px;
    margin-bottom: 14px;
    color: var(--danger);
}

.popup h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 24px;
    font-weight: 400;
    margin-bottom: 8px;
    color: var(--text-primary);
}

.popup p {
    font-size: 13px;
    color: var(--text-muted);
    line-height: 1.6;
}

.popup-btn {
    margin-top: 20px;
    width: 100%;
    padding: 12px;
    border: 1.5px solid var(--border);
    border-radius: 2px;
    background: var(--bg-hover);
    color: var(--text-secondary);
    font-family: 'Montserrat', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.3s ease;
}

.popup-btn:hover {
    border-color: var(--gold-border);
    color: var(--gold);
    background: var(--bg-hover);
}

@keyframes shake {
    0% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    50% { transform: translateX(5px); }
    75% { transform: translateX(-3px); }
    100% { transform: translateX(0); }
}

.shake {
    animation: shake 0.3s ease;
}

@media(max-width: 480px) {
    .card {
        width: 90vw;
        padding: 32px 24px;
    }
}
</style>
</head>

<body>

<a href="index.php" class="home-top-left">
    <i class="fas fa-home"></i>
</a>

<div class="login-header">
    <img src="hostel.png" alt="Hostel Barbershop">
</div>

<div class="card">

    <h2>Sign <em>In</em></h2>

    <form method="POST">

        <input type="email" name="email" placeholder="Email" required>

        <div class="password-wrap">
            <input type="password" name="password" id="passwordInput" placeholder="Password" required>
            <button type="button" class="toggle-password" onclick="togglePassword()">
                <i class="fas fa-eye" id="toggleIcon"></i>
            </button>
        </div>

        <button name="login">
            <span>Login</span>
        </button>

        <div class="register-link">
            <span>Belum punya akun?</span>
            <a href="register.php">Register</a>
        </div>

    </form>

</div>

<!-- ERROR POPUP -->
<div class="popup" id="popupError">
    <div class="popup-box">
        <div class="popup-icon"><i class="fas fa-times-circle"></i></div>
        <h3>Login Failed</h3>
        <p id="errorText">Error</p>
        <button class="popup-btn" onclick="closeError()">Close</button>
    </div>
</div>

<script>

function togglePassword(){
    const input = document.getElementById('passwordInput');
    const icon = document.getElementById('toggleIcon');

    if(input.type === 'password'){
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function showError(msg) {
    let p = document.getElementById('popupError');
    document.getElementById('errorText').innerText = msg;
    p.classList.add('show');
    let box = document.querySelector('.popup-box');
    box.classList.remove('shake');
    void box.offsetWidth;
    box.classList.add('shake');
}

function closeError() {
    document.getElementById('popupError').classList.remove('show');
}

</script>

</body>
</html>