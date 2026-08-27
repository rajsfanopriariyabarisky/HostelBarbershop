<?php
session_name('admin_sid');
session_start();

// Flash message
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$error = '';

if(isset($_POST['login'])){
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if($username === 'adminbarber' && $password === 'barberku'){
        $_SESSION['admin'] = true;

        if(isset($_POST['remember'])){
            setcookie('admin_remember', '1', time() + 30 * 24 * 60 * 60, '/');
        }

        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Hostel Barbershop</title>

    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
    *{margin:0;padding:0;box-sizing:border-box;}

    body{
        background:#0c0c0e;
        color:#e2e2e5;
        font-family:'Inter',sans-serif;
        min-height:100vh;
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .login-container{
        width:100%;
        max-width:400px;
        padding:0 24px;
    }

    .login-card{
        background:#131316;
        border:1px solid #1e1e22;
        border-radius:20px;
        padding:48px 36px;
    }

    /* Logo */
    .logo-section{
        text-align:center;
        margin-bottom:40px;
    }

    .logo-mark{
        width:64px;
        height:64px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        margin-bottom:20px;
        cursor:pointer;
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .logo-mark:hover{
        transform: scale(1.15) rotate(-5deg);
    }

    .logo-mark:active{
        transform: scale(0.95) rotate(0deg);
    }

    .logo-mark img{
        width:64px;
        height:64px;
        object-fit:contain;
        filter: brightness(1.1);
        transition: filter 0.3s ease;
    }

    .logo-mark:hover img{
        filter: brightness(1.3) drop-shadow(0 0 12px rgba(255,255,255,0.15));
    }

    .logo-title{
        font-family:'Space Grotesk',sans-serif;
        font-size:24px;
        font-weight:600;
        letter-spacing:-0.5px;
        margin-bottom:6px;
        color:#f0f0f2;
    }

    .logo-subtitle{
        color:#555;
        font-size:13px;
        font-weight:400;
    }

    /* Alert */
    .alert-box{
        display:flex;
        align-items:center;
        gap:10px;
        padding:12px 16px;
        border-radius:12px;
        margin-bottom:24px;
        font-size:13px;
        font-weight:500;
    }

    .alert-error{
        background:rgba(255,68,68,0.06);
        border:1px solid rgba(255,68,68,0.12);
        color:#e06060;
    }

    .alert-success{
        background:rgba(0,255,136,0.06);
        border:1px solid rgba(0,255,136,0.12);
        color:#4ade80;
    }

    .alert-box i{
        font-size:14px;
    }

    /* Inputs */
    .field-group{
        margin-bottom:20px;
    }

    .field-label{
        display:block;
        font-size:11px;
        font-weight:600;
        color:#555;
        text-transform:uppercase;
        letter-spacing:1.5px;
        margin-bottom:8px;
    }

    .field-wrap{
        position:relative;
    }

    .field-wrap i.left{
        position:absolute;
        left:14px;
        top:50%;
        transform:translateY(-50%);
        color:#444;
        font-size:14px;
        transition: color 0.2s ease;
    }

    .field-input{
        width:100%;
        height:48px;
        padding:0 14px 0 42px;
        background:#1a1a1e;
        border:1px solid #25252a;
        border-radius:12px;
        color:#e2e2e5;
        font-family:'Inter',sans-serif;
        font-size:14px;
        outline:none;
        transition:all 0.2s ease;
    }

    .field-input::placeholder{
        color:#3a3a40;
    }

    .field-input:focus{
        border-color:#3a3a45;
        background:#1e1e22;
    }

    .field-input:focus + i.left{
        color:#666;
    }

    /* Password field with toggle */
    .field-wrap.password .field-input{
        padding-right:42px;
    }

    .toggle-password{
        position:absolute;
        right:0;
        top:0;
        height:100%;
        width:42px;
        background:none;
        border:none;
        color:#444;
        cursor:pointer;
        font-size:13px;
        display:flex;
        align-items:center;
        justify-content:center;
        transition:color 0.2s ease;
    }

    .toggle-password:hover{
        color:#777;
    }

    /* Button */
    .submit-btn{
        width:100%;
        height:48px;
        background:#e2e2e5;
        color:#0c0c0e;
        border:none;
        border-radius:12px;
        font-family:'Inter',sans-serif;
        font-size:14px;
        font-weight:600;
        cursor:pointer;
        transition:all 0.2s ease;
        display:flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        margin-top:8px;
    }

    .submit-btn:hover{
        background:#fff;
        transform: translateY(-2px);
    }

    .submit-btn:active{
        transform:scale(0.97) translateY(0);
    }

    /* Footer */
    .login-footer{
        text-align:center;
        margin-top:32px;
        padding-top:24px;
        border-top:1px solid #1a1a1e;
    }

    .login-footer p{
        font-size:12px;
        color:#333;
    }

    .login-footer strong{
        color:#555;
        font-weight:600;
    }

    /* Responsive */
    @media(max-width:480px){
        .login-card{
            padding:36px 24px;
            border-radius:0;
            border-left:none;
            border-right:none;
        }
    }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-card">

            <div class="logo-section">
                <div class="logo-mark">
                    <img src="hostel.png" alt="Hostel Barbershop">
                </div>
                <div class="logo-title">Hostel Barbershop</div>
                <div class="logo-subtitle">Admin Dashboard</div>
            </div>

            <?php if($flash): ?>
            <div class="alert-box alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
            <?php endif; ?>

            <?php if($error): ?>
            <div class="alert-box alert-error">
                <i class="fas fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST">

                <div class="field-group">
                    <label class="field-label">Username</label>
                    <div class="field-wrap">
                        <input 
                            type="text" 
                            name="username" 
                            class="field-input" 
                            placeholder="Enter your username"
                            value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                            required
                            autocomplete="username"
                        >
                        <i class="fas fa-user left"></i>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Password</label>
                    <div class="field-wrap password">
                        <input 
                            type="password" 
                            name="password" 
                            id="passwordInput"
                            class="field-input" 
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <i class="fas fa-lock left"></i>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" name="login" class="submit-btn">
                    <i class="fas fa-arrow-right-to-bracket"></i>
                    Sign In
                </button>

            </form>

            <div class="login-footer">
                <p><strong>Hostel Barbershop</strong> Management System</p>
            </div>

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

    window.addEventListener('load', function(){
        const u = document.querySelector('input[name="username"]');
        if(u && !u.value) u.focus();
    });
    </script>

</body>
</html>