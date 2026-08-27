<?php
include 'config.php';

if(isset($_POST['submit'])){

    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    $username = $_POST['username'];
    $gender = $_POST['gender'];
    $phone = $_POST['phone'];

    $error = "";

    if(strpos($email, '@') === false){
        $error = "Email harus mengandung @";
    }
    elseif($password != $confirm){
        $error = "Password tidak sama";
    }
    else{

        $cek = mysqli_query($conn,"SELECT * FROM users WHERE email='$email'");

        if(mysqli_num_rows($cek) > 0){
            $error = "Email sudah terdaftar";
        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            mysqli_query($conn,"
                INSERT INTO users 
                (email,password,username,gender,phone)
                VALUES
                ('$email','$hash','$username','$gender','$phone')
            ");

            echo "<script>
                window.onload = function(){
                    document.getElementById('popupSuccess').classList.add('show');
                }
            </script>";
        }
    }

    if($error != ""){
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
<title>Register | Hostel Barbershop</title>

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

/* Back button top-left */
.back-top-left {
    position: fixed;
    top: 24px;
    left: 24px;
    z-index: 100;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    border-radius: 2px;
    color: var(--text-muted);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-decoration: none;
    transition: all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}

.back-top-left:hover {
    border-color: var(--gold-border);
    color: var(--gold);
    background: var(--bg-hover);
}

.back-top-left i {
    font-size: 10px;
}

/* Card */
.card {
    width: 400px;
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
    margin-bottom: 24px;
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

.step-title {
    font-size: 11px;
    color: var(--text-muted);
    margin-bottom: 14px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    font-weight: 600;
}

input, select {
    width: 100%;
    padding: 14px 16px;
    margin-bottom: 12px;
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

input:focus, select:focus {
    border-color: var(--gold-border);
    box-shadow: 0 0 0 4px var(--gold-dim);
}

select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239e998f' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
}

select option {
    background: var(--bg-hover);
    color: var(--text-primary);
}

button {
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
}

button::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}

button:hover {
    color: var(--bg-primary);
}

button:hover::before {
    transform: scaleX(1);
}

button span {
    position: relative;
    z-index: 1;
}

/* Navigation buttons (Back & Create) */
.nav {
    display: flex;
    gap: 12px;
}

.nav button {
    width: 50%;
}

.step {
    display: none;
}

.step.active {
    display: block;
}

.progress {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
}

.bar {
    flex: 1;
    height: 3px;
    background: var(--border);
    border-radius: 2px;
    transition: all 0.4s ease;
}

.bar.active {
    background: var(--gold);
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
}

.popup.error .popup-icon {
    color: var(--danger);
}

.popup.success .popup-icon {
    color: var(--success);
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

@media(max-width: 480px) {
    .card {
        width: 90vw;
        padding: 32px 24px;
    }
    .back-top-left {
        top: 16px;
        left: 16px;
        padding: 8px 14px;
        font-size: 10px;
    }
}
</style>
</head>

<body>

<a href="login.php" class="back-top-left">
    <i class="fas fa-arrow-left"></i> Back
</a>

<div class="card">

<h2>Regi<em>ster</em></h2>

<div class="progress">
    <div class="bar active"></div>
    <div class="bar"></div>
</div>

<form method="POST">

<!-- STEP 1 -->
<div class="step active">

<div class="step-title">Step 1 - Email & Password</div>

<input type="email" name="email" placeholder="Email" required onkeydown="handleEnter(event)">
<input type="password" name="password" placeholder="Password" required onkeydown="handleEnter(event)">
<input type="password" name="confirm_password" placeholder="Confirm Password" required onkeydown="handleEnter(event)">

<button type="button" onclick="nextStep()">
    <span>Next</span>
</button>

</div>

<!-- STEP 2 -->
<div class="step">

<div class="step-title">Step 2 - Account Detail</div>

<input type="text" name="username" placeholder="Username" required>

<select name="gender" required>
    <option value="">Gender</option>
    <option value="Laki-laki">Laki-laki</option>
    <option value="Perempuan">Perempuan</option>
</select>

<input type="tel" name="phone" placeholder="No HP" required maxlength="13" pattern="[0-9]+" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')">

<div class="nav">
    <button type="button" onclick="prevStep()">
        <span>Back</span>
    </button>
    <button name="submit">
        <span>Create</span>
    </button>
</div>

</div>

</form>

</div>

<!-- SUCCESS -->
<div class="popup success" id="popupSuccess">
    <div class="popup-box">
        <div class="popup-icon"><i class="fas fa-check-circle"></i></div>
        <h3>Success</h3>
        <p>Akun berhasil dibuat</p>
        <button class="popup-btn" onclick="goLogin()">Continue</button>
    </div>
</div>

<!-- ERROR -->
<div class="popup error" id="popupError">
    <div class="popup-box">
        <div class="popup-icon"><i class="fas fa-times-circle"></i></div>
        <h3>Failed</h3>
        <p id="errorText">Error</p>
        <button class="popup-btn" onclick="closeError()">Close</button>
    </div>
</div>

<script>

let currentStep = 0;
let steps = document.querySelectorAll(".step");
let bars = document.querySelectorAll(".bar");

function showStep(i){
    steps.forEach(s=>s.classList.remove("active"));
    steps[i].classList.add("active");

    bars.forEach((b,idx)=>{
        b.classList.toggle("active", idx <= i);
    });
}

function nextStep(){

let email = document.querySelector("[name=email]").value;
let pass = document.querySelector("[name=password]").value;
let confirm = document.querySelector("[name=confirm_password]").value;

if(!email.includes("@")){
    showError("Email harus mengandung @");
    return;
}

if(pass !== confirm){
    showError("Password tidak sama");
    return;
}

currentStep = 1;
showStep(currentStep);
}

function handleEnter(e){
if(e.key === "Enter"){
    e.preventDefault();

    if(currentStep === 0){
        nextStep();
    }
}
}

function showError(msg){
let p = document.getElementById('popupError');
document.getElementById('errorText').innerText = msg;
p.classList.add('show');
}

function closeError(){
document.getElementById('popupError').classList.remove('show');
}

function prevStep(){
currentStep = 0;
showStep(currentStep);
}

function goLogin(){
window.location = "login.php";
}

</script>

</body>
</html>