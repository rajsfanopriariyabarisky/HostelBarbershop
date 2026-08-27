<?php
date_default_timezone_set('Asia/Jakarta');
include 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

/* ==================== AUTO EXPIRE PENDING BOOKINGS ==================== */
mysqli_query($conn,"
    UPDATE booking
    SET status='expired'
    WHERE status='pending'
    AND created_at IS NOT NULL
    AND TIMESTAMPDIFF(SECOND, created_at, NOW()) >= 30
");

/* ==================== BASE VARIABLES ==================== */
$now = time();
$today = date('Y-m-d', $now);
$currentHour = date('H:i', $now);
$maxDate = date('Y-m-d', strtotime('+6 day', $now));

/* ==================== FETCH TIME SLOTS ==================== */
$jamData = [];
$qJam = mysqli_query($conn,"SELECT * FROM jam_operasional WHERE status='Buka' ORDER BY jam_buka ASC");
while($j = mysqli_fetch_assoc($qJam)){
    $jamData[] = substr($j['jam_buka'],0,5);
}

/* ==================== FETCH BARBER SCHEDULES ==================== */
$jadwalBarber = [];
$qJadwal = mysqli_query($conn,"SELECT * FROM jadwal_barber");
while($j = mysqli_fetch_assoc($qJadwal)){
    $jadwalBarber[] = $j['tanggal']."_".$j['barber_id'];
}

/* ==================== FETCH BOOKED SLOTS ==================== */
$booked = [];
$qBooked = mysqli_query($conn,"SELECT barber_id,tanggal,jam FROM booking WHERE status='pending' OR status='paid'");
while($b = mysqli_fetch_assoc($qBooked)){
    $booked[] = $b['barber_id']."_".$b['tanggal']."_".substr($b['jam'],0,5);
}

/* ==================== FETCH ALL BARBERS ==================== */
$barberData = [];
$qBarber = mysqli_query($conn,"SELECT * FROM barber");
while($b = mysqli_fetch_assoc($qBarber)){
    $barberData[$b['id']] = $b;
}

/* ==================== PRESELECTED VALUES FROM GET ==================== */
$preselectedBarber = isset($_GET['barber']) ? intval($_GET['barber']) : 0;
$preselectedTanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';
$preselectedJam = isset($_GET['jam']) ? $_GET['jam'] : '';
$preselectedPaket = isset($_GET['paket']) ? intval($_GET['paket']) : 0;

/* ==================== USER DATA FOR NAV ==================== */
$userId = $_SESSION['user']['id'];
$qUser = mysqli_query($conn, "SELECT * FROM users WHERE id='$userId'");
$userData = mysqli_fetch_assoc($qUser);
$username = $userData['username'] ?? 'User';
$userPhoto = $userData['photo'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking | Hostel Barbershop</title>
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
    --border-focus: rgba(232, 200, 122, 0.45);
    --text-primary: #ffffff;
    --text-secondary: #d5d0c8;
    --text-muted: #9e998f;
    --text-dim: #6a6560;
    --gold: #e8c87a;
    --gold-light: #f5e6c3;
    --gold-dim: rgba(232, 200, 122, 0.10);
    --gold-border: rgba(232, 200, 122, 0.45);
    --gold-glow: rgba(232, 200, 122, 0.15);
    --success: #6ee7a0;
    --danger: #e88484;
    --warning: #e8c87a;
    --radius: 2px;
    --transition: all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
html { scroll-behavior: smooth; }
body {
    background: var(--bg-primary);
    color: var(--text-primary);
    font-family: 'Montserrat', sans-serif;
    line-height: 1.6;
    min-height: 100vh;
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
}
body.modal-open { overflow: hidden; height: 100vh; }
.container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 140px 32px 200px;
}

/* ===== SCROLL PROGRESS BAR - SYNCED WITH DASHBOARD ===== */
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

/* ===== NAVIGATION - SYNCED WITH DASHBOARD ===== */
.nav-wrapper {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 9000;
    padding: 24px 0;
    transition: var(--transition);
    background: transparent;
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
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    background: transparent;
    border: 1.5px solid var(--border);
    color: var(--text-muted);
    text-decoration: none;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 2px;
    text-transform: uppercase;
    transition: var(--transition);
}
.back-btn:hover {
    border-color: var(--gold-border);
    color: var(--gold);
    background: var(--gold-dim);
}
.back-btn i { transition: transform 0.3s ease; }
.back-btn:hover i { transform: translateX(-4px); }

/* ===== HEADER ===== */
.page-header {
    margin-bottom: 48px;
    padding-bottom: 32px;
    border-bottom: 1.5px solid var(--border);
    position: relative;
}
.page-header::after {
    content: '';
    position: absolute;
    bottom: -1.5px;
    left: 0;
    width: 120px;
    height: 1.5px;
    background: linear-gradient(90deg, var(--gold), transparent);
}
.page-label {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}
.page-label-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 12px;
    font-weight: 600;
    color: var(--gold);
    letter-spacing: 2px;
}
.page-label-line {
    width: 40px;
    height: 1.5px;
    background: var(--gold);
}
.page-label-text {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--text-muted);
}
.page-header h1 {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(40px, 6vw, 64px);
    font-weight: 300;
    letter-spacing: -2px;
    margin-bottom: 12px;
    line-height: 1.05;
}
.page-header h1 em {
    font-style: italic;
    color: var(--gold);
}
.page-header p {
    color: var(--text-muted);
    font-size: 15px;
    font-weight: 500;
    max-width: 480px;
    line-height: 1.8;
    letter-spacing: 0.3px;
}

/* ===== STEP INDICATOR ===== */
.step-indicator {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 48px;
    padding: 20px 24px;
    background: var(--bg-secondary);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
}
.step {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--text-dim);
    transition: var(--transition);
}
.step.active { color: var(--gold); }
.step.completed { color: var(--text-secondary); }
.step-number {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    font-family: 'Cormorant Garamond', serif;
    transition: var(--transition);
    flex-shrink: 0;
}
.step.active .step-number {
    background: var(--gold);
    border-color: var(--gold);
    color: var(--bg-primary);
    box-shadow: 0 0 20px rgba(232,200,122,0.3);
}
.step.completed .step-number {
    background: var(--gold-dim);
    border-color: var(--gold-border);
    color: var(--gold);
}
.step-divider {
    flex: 1;
    height: 1.5px;
    background: var(--border);
    transition: var(--transition);
    max-width: 60px;
}
.step-divider.active {
    background: linear-gradient(90deg, var(--gold), transparent);
}

/* ===== RULES BOX ===== */
.rules-box {
    background: var(--bg-secondary);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: 32px;
    margin-bottom: 48px;
    position: relative;
    overflow: hidden;
}
.rules-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 3px;
    height: 100%;
    background: linear-gradient(180deg, var(--danger), transparent);
    opacity: 0.6;
}
.rules-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
    color: var(--danger);
}
.rules-header i { font-size: 16px; }
.rules-header h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 3px;
    text-transform: uppercase;
}
.rules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 12px;
}
.rule-item {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    padding: 20px;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    transition: var(--transition);
}
.rule-item:hover {
    border-color: rgba(232, 132, 132, 0.25);
    transform: translateY(-2px);
    background: rgba(232, 132, 132, 0.03);
}
.rule-number {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(232, 132, 132, 0.08);
    border: 1.5px solid rgba(232, 132, 132, 0.25);
    color: var(--danger);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 800;
    flex-shrink: 0;
    font-family: 'Cormorant Garamond', serif;
}
.rule-content { flex: 1; }
.rule-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 4px;
    line-height: 1.3;
    letter-spacing: 0.5px;
}
.rule-desc {
    font-size: 12px;
    color: var(--text-muted);
    line-height: 1.6;
    font-weight: 500;
}

/* ===== FORM SECTIONS ===== */
.form-section {
    margin-bottom: 32px;
    position: relative;
    z-index: 1;
}
.form-label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: var(--text-muted);
    margin-bottom: 14px;
}
.form-label i {
    font-size: 10px;
    color: var(--gold);
}
.form-select,
.form-date {
    width: 100%;
    max-width: 400px;
    padding: 16px 20px;
    background: var(--bg-input);
    border: 1.5px solid var(--border);
    color: var(--text-primary);
    border-radius: var(--radius);
    font-size: 14px;
    font-family: 'Montserrat', sans-serif;
    outline: none;
    transition: var(--transition);
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%239e998f' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 18px center;
    background-size: 14px;
    padding-right: 48px;
    font-weight: 500;
}
.form-select:focus,
.form-date:focus {
    border-color: var(--gold-border);
    box-shadow: 0 0 0 4px var(--gold-dim), 0 0 30px rgba(232,200,122,0.08);
}
.form-select option {
    background: var(--bg-secondary);
    color: var(--text-primary);
    padding: 12px;
    font-family: 'Montserrat', sans-serif;
}
.form-date::-webkit-calendar-picker-indicator {
    filter: invert(0.6);
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: var(--transition);
}
.form-date::-webkit-calendar-picker-indicator:hover {
    filter: invert(1);
    background: rgba(255,255,255,0.1);
}

/* ===== BARBER SECTION ===== */
.barber-section {
    margin-bottom: 32px;
    opacity: 0;
    transform: translateY(20px);
    transition: var(--transition);
    pointer-events: none;
    height: 0;
    overflow: hidden;
}
.barber-section.visible {
    opacity: 1;
    transform: translateY(0);
    pointer-events: all;
    height: auto;
    overflow: visible;
}
.barber-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
}
@media(max-width: 640px) {
    .barber-grid { grid-template-columns: 1fr; }
}

/* ===== BARBER CARD ===== */
.barber-card {
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    cursor: pointer;
    transition: var(--transition);
    position: relative;
    display: block;
}
.barber-card:hover:not(.disabled):not(.active) {
    transform: translateY(-8px);
    border-color: var(--gold-border);
    box-shadow: 0 20px 50px rgba(0,0,0,0.6), 0 0 30px rgba(232,200,122,0.05);
}
.barber-card.active:not(.disabled) {
    border-color: var(--gold-border);
    box-shadow:
        0 0 0 1px rgba(232,200,122,0.2),
        0 0 60px rgba(232,200,122,0.1),
        0 12px 40px rgba(0,0,0,0.5);
    transform: translateY(-4px) scale(1.02);
    background: linear-gradient(180deg, var(--bg-card) 0%, rgba(232,200,122,0.04) 100%);
}
.barber-card.active:not(.disabled)::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, var(--gold), var(--gold-light), var(--gold), transparent);
    background-size: 200% 100%;
    animation: shimmer 2s linear infinite;
    z-index: 10;
}
@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
.barber-card.disabled {
    background: rgba(10, 10, 15, 0.4);
    border-color: rgba(255,255,255,0.06);
    cursor: not-allowed;
    opacity: 0.35;
    filter: grayscale(0.6);
}
.barber-card input { display: none; }

/* CARD PHOTO */
.card-photo-area {
    position: relative;
    width: 100%;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    background: var(--bg-input);
}
.card-photo-area img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: grayscale(30%) contrast(1.05);
    transition: all 0.6s cubic-bezier(0.25,0.46,0.45,0.94);
}
.barber-card:hover:not(.disabled) .card-photo-area img {
    transform: scale(1.08);
    filter: grayscale(0%) contrast(1.05);
}
.barber-card.active:not(.disabled) .card-photo-area img {
    transform: scale(1.05);
    filter: grayscale(0%) contrast(1.1);
}
.card-photo-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 40%, rgba(5,5,7,0.95) 100%);
    z-index: 1;
    pointer-events: none;
}
.card-selected-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    padding: 8px 16px;
    border-radius: 100px;
    background: var(--gold);
    color: var(--bg-primary);
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    z-index: 5;
    transform: scale(0) rotate(-180deg);
    transition: all 0.4s cubic-bezier(0.34,1.56,0.64,1);
    box-shadow: 0 4px 15px rgba(232,200,122,0.4);
}
.barber-card.active .card-selected-badge {
    transform: scale(1) rotate(0deg);
}
.card-off-badge {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 10px 24px;
    background: rgba(232, 132, 132, 0.15);
    border: 1.5px solid rgba(232, 132, 132, 0.3);
    color: var(--danger);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    z-index: 5;
    backdrop-filter: blur(8px);
    border-radius: var(--radius);
}
.card-zoom-hint {
    position: absolute;
    top: 16px;
    left: 16px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(5,5,7,0.6);
    border: 1.5px solid var(--border);
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    z-index: 5;
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    cursor: pointer;
    backdrop-filter: blur(4px);
}
.barber-card:hover:not(.disabled) .card-zoom-hint {
    opacity: 1;
    transform: translateY(0);
}
.card-zoom-hint:hover {
    border-color: var(--gold-border);
    color: var(--gold);
    background: rgba(232,200,122,0.15);
}

/* CARD INFO */
.card-info {
    padding: 20px 24px 24px;
    position: relative;
}
.card-info-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 12px;
}
.card-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 24px;
    font-weight: 400;
    letter-spacing: -0.5px;
    line-height: 1.1;
    color: var(--text-primary);
}
.card-role {
    font-size: 10px;
    color: var(--gold);
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    margin-top: 4px;
}
.card-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 100px;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    flex-shrink: 0;
    margin-top: 4px;
}
.card-status.available {
    background: rgba(110, 231, 160, 0.08);
    border: 1.5px solid rgba(110, 231, 160, 0.2);
    color: var(--success);
}
.card-status.off {
    background: rgba(232, 132, 132, 0.08);
    border: 1.5px solid rgba(232, 132, 132, 0.25);
    color: var(--danger);
}

/* SKILL BARS */
.card-skills {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1.5px solid var(--border);
}
.skill-row {
    display: flex;
    align-items: center;
    gap: 10px;
}
.skill-name {
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--text-muted);
    min-width: 52px;
}
.skill-bar-track {
    flex: 1;
    height: 3px;
    background: rgba(255,255,255,0.06);
    border-radius: 50px;
    overflow: hidden;
    position: relative;
}
.skill-bar-fill {
    height: 100%;
    border-radius: 50px;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
    width: 0;
    transition: width 1.2s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    box-shadow: 0 0 8px rgba(232,200,122,0.2);
    position: relative;
}
.skill-bar-fill::after {
    content: '';
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 10px;
    background: linear-gradient(90deg, transparent, var(--gold-light));
    opacity: 0.5;
}
.barber-card.animated .skill-bar-fill {
    width: var(--val);
}
.skill-percent {
    font-family: 'Cormorant Garamond', serif;
    font-size: 14px;
    font-weight: 600;
    color: var(--gold);
    min-width: 30px;
    text-align: right;
}

/* SELECT BUTTON */
.card-select-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 12px;
    margin-top: 16px;
    background: transparent;
    border: 1.5px solid var(--border);
    color: var(--text-muted);
    font-family: 'Montserrat', sans-serif;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    cursor: pointer;
    transition: var(--transition);
    border-radius: var(--radius);
    position: relative;
    overflow: hidden;
}
.card-select-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}
.barber-card:hover:not(.disabled):not(.active) .card-select-btn {
    border-color: var(--gold-border);
    color: var(--gold);
}
.barber-card.active .card-select-btn {
    background: var(--gold);
    border-color: var(--gold);
    color: var(--bg-primary);
}
.barber-card.active .card-select-btn::before {
    transform: scaleX(1);
}
.card-select-btn span,
.card-select-btn i {
    position: relative;
    z-index: 1;
}
.card-select-btn i {
    font-size: 10px;
    transition: transform 0.3s ease;
}
.barber-card.active .card-select-btn i {
    transform: translateX(3px);
}

/* ===== STICKY JAM PANEL ===== */
.jam-sticky-panel {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
    border-top: 1.5px solid var(--border);
    padding: 24px 32px 32px;
    z-index: 100;
    transform: translateY(100%);
    transition: transform 0.5s cubic-bezier(0.25,0.46,0.45,0.94);
    backdrop-filter: blur(30px);
    box-shadow: 0 -20px 60px rgba(0,0,0,0.8);
}
.jam-sticky-panel.visible {
    transform: translateY(0);
}
.jam-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}
.jam-panel-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 12px;
}
.jam-panel-title i {
    color: var(--gold);
    font-size: 14px;
}
.jam-panel-close {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
    font-size: 12px;
}
.jam-panel-close:hover {
    background: rgba(232, 132, 132, 0.08);
    border-color: rgba(232, 132, 132, 0.25);
    color: var(--danger);
    transform: rotate(90deg);
}
.jam-panel-content {
    display: flex;
    align-items: center;
    gap: 24px;
}
.jam-panel-scroll {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding-bottom: 6px;
    scrollbar-width: none;
    flex: 1;
}
.jam-panel-scroll::-webkit-scrollbar { display: none; }

.jam-btn {
    padding: 14px 24px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    background: var(--bg-input);
    color: var(--text-secondary);
    cursor: pointer;
    transition: var(--transition);
    font-family: 'Montserrat', sans-serif;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
    letter-spacing: 0.5px;
}
.jam-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    opacity: 0;
    transition: var(--transition);
    z-index: 0;
}
.jam-btn:hover:not(:disabled) {
    border-color: var(--gold-border);
    color: var(--gold);
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(232,200,122,0.1);
}
.jam-btn:disabled {
    background: rgba(10, 10, 15, 0.5);
    border-color: var(--border);
    color: var(--text-dim);
    cursor: not-allowed;
    text-decoration: line-through;
    opacity: 0.5;
}
.jam-btn.selected {
    background: var(--gold);
    color: var(--bg-primary);
    border-color: var(--gold);
    font-weight: 700;
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(232,200,122,0.3);
}
.jam-btn.selected span {
    color: var(--bg-primary) !important;
    position: relative;
    z-index: 1;
}
.jam-btn span {
    position: relative;
    z-index: 1;
}
.jam-panel-info {
    margin-top: 14px;
    color: var(--text-dim);
    font-size: 12px;
    font-style: italic;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}
.jam-panel-info i { font-size: 10px; }

.booking-btn-panel {
    padding: 14px 32px;
    border: none;
    border-radius: var(--radius);
    background: var(--gold);
    color: var(--bg-primary);
    font-family: 'Cormorant Garamond', serif;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 10px;
    white-space: nowrap;
    flex-shrink: 0;
    box-shadow: 0 4px 20px rgba(232,200,122,0.2);
    position: relative;
    overflow: hidden;
}
.booking-btn-panel::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold-light);
    transform: translateX(-100%);
    transition: transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}
.booking-btn-panel:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(232,200,122,0.3);
}
.booking-btn-panel:hover::before { transform: translateX(0); }
.booking-btn-panel span, .booking-btn-panel i { position: relative; z-index: 1; }

.booking-btn { display: none; }

/* ===== ALERT MODAL ===== */
.alert-overlay {
    position: fixed;
    inset: 0;
    background: rgba(5,5,7,0.92);
    backdrop-filter: blur(20px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    opacity: 0;
    visibility: hidden;
    transition: var(--transition);
}
.alert-overlay.active {
    opacity: 1;
    visibility: visible;
}
.alert-box {
    background: var(--bg-secondary);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: 48px;
    max-width: 420px;
    width: 85%;
    text-align: center;
    position: relative;
    transform: scale(0.9) translateY(20px);
    transition: var(--transition);
    box-shadow: 0 20px 60px rgba(0,0,0,0.8);
}
.alert-overlay.active .alert-box {
    transform: scale(1) translateY(0);
}
.alert-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(232, 132, 132, 0.08);
    border: 1.5px solid rgba(232, 132, 132, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    animation: alertPulse 2s ease-in-out infinite;
}
@keyframes alertPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(232,132,132,0); }
    50% { box-shadow: 0 0 0 16px rgba(232,132,132,0.06); }
}
.alert-icon i {
    font-size: 28px;
    color: var(--danger);
}
.alert-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px;
    font-weight: 400;
    letter-spacing: -1px;
    margin-bottom: 12px;
}
.alert-message {
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 32px;
    line-height: 1.7;
    font-weight: 500;
}
.alert-btn {
    padding: 14px 40px;
    border-radius: var(--radius);
    background: var(--gold);
    color: var(--bg-primary);
    font-family: 'Cormorant Garamond', serif;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    cursor: pointer;
    transition: var(--transition);
    border: none;
    box-shadow: 0 4px 20px rgba(232,200,122,0.2);
    position: relative;
    overflow: hidden;
}
.alert-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold-light);
    transform: translateX(-100%);
    transition: transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}
.alert-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(232,200,122,0.3);
}
.alert-btn:hover::before { transform: translateX(0); }
.alert-btn span { position: relative; z-index: 1; }

/* ===== CONFIRM MODAL ===== */
.confirm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(5,5,7,0.92);
    backdrop-filter: blur(20px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: var(--transition);
}
.confirm-overlay.active {
    opacity: 1;
    visibility: visible;
}
.confirm-box {
    background: var(--bg-secondary);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: 48px;
    max-width: 520px;
    width: 90%;
    text-align: center;
    position: relative;
    transform: scale(0.92) translateY(10px);
    transition: var(--transition);
    box-shadow: 0 20px 60px rgba(0,0,0,0.8);
}
.confirm-overlay.active .confirm-box {
    transform: scale(1) translateY(0);
}
.confirm-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba(232, 200, 122, 0.08);
    border: 1.5px solid var(--gold-border);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    animation: confirmPulse 2s ease-in-out infinite;
}
@keyframes confirmPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(232,200,122,0); }
    50% { box-shadow: 0 0 0 16px rgba(232,200,122,0.06); }
}
.confirm-icon i {
    font-size: 32px;
    color: var(--gold);
}
.confirm-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 32px;
    font-weight: 400;
    letter-spacing: -1px;
    margin-bottom: 8px;
}
.confirm-subtitle {
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 32px;
    line-height: 1.7;
    font-weight: 500;
}
.confirm-rules {
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: 24px;
    margin-bottom: 32px;
    text-align: left;
}
.confirm-rule {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 16px;
    font-size: 13px;
    color: var(--text-secondary);
    line-height: 1.7;
    font-weight: 500;
}
.confirm-rule:last-child { margin-bottom: 0; }
.confirm-rule i {
    color: var(--gold);
    font-size: 14px;
    margin-top: 3px;
    flex-shrink: 0;
}
.confirm-rule strong {
    color: var(--danger);
    font-weight: 700;
}
.confirm-buttons {
    display: flex;
    gap: 16px;
    justify-content: center;
}
.confirm-btn {
    padding: 14px 36px;
    border-radius: var(--radius);
    font-family: 'Cormorant Garamond', serif;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    cursor: pointer;
    transition: var(--transition);
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    position: relative;
    overflow: hidden;
}
.confirm-btn.yes {
    background: var(--gold);
    color: var(--bg-primary);
    box-shadow: 0 4px 20px rgba(232,200,122,0.2);
}
.confirm-btn.yes::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold-light);
    transform: translateX(-100%);
    transition: transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}
.confirm-btn.yes:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(232,200,122,0.3);
}
.confirm-btn.yes:hover::before { transform: translateX(0); }
.confirm-btn.yes span, .confirm-btn.yes i { position: relative; z-index: 1; }
.confirm-btn.no {
    background: transparent;
    border: 1.5px solid var(--border);
    color: var(--text-muted);
}
.confirm-btn.no:hover {
    border-color: var(--danger);
    color: var(--danger);
    background: rgba(232, 132, 132, 0.08);
}

/* ===== IMAGE MODAL ===== */
.image-modal {
    position: fixed;
    inset: 0;
    background: rgba(5,5,7,0.92);
    backdrop-filter: blur(20px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999;
    opacity: 0;
    visibility: hidden;
    transition: var(--transition);
}
.image-modal.active {
    opacity: 1;
    visibility: visible;
}
.image-modal img {
    max-width: 90%;
    max-height: 85vh;
    border-radius: var(--radius);
    border: 1.5px solid var(--border);
    box-shadow: 0 20px 60px rgba(0,0,0,0.8);
}
.close-modal {
    position: absolute;
    top: 28px;
    right: 32px;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    cursor: pointer;
    transition: var(--transition);
    background: var(--bg-secondary);
    border: 1.5px solid var(--border);
    font-size: 14px;
}
.close-modal:hover {
    color: var(--danger);
    border-color: var(--danger);
    background: rgba(232, 132, 132, 0.08);
    transform: rotate(90deg);
}

/* ===== ANIMATIONS ===== */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.rules-box { animation: fadeUp 0.6s ease forwards; }
.form-section { animation: fadeUp 0.6s ease 0.1s forwards; opacity: 0; }
.barber-section { animation: fadeUp 0.6s ease 0.2s forwards; opacity: 0; }
.barber-card {
    animation: fadeUp 0.5s ease forwards;
    opacity: 0;
}
.barber-card:nth-child(1) { animation-delay: 0.05s; }
.barber-card:nth-child(2) { animation-delay: 0.1s; }
.barber-card:nth-child(3) { animation-delay: 0.15s; }
.barber-card:nth-child(4) { animation-delay: 0.2s; }
.barber-card:nth-child(5) { animation-delay: 0.25s; }
.barber-card:nth-child(6) { animation-delay: 0.3s; }

/* ===== SCROLLBAR ===== */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--bg-primary); }
::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: var(--border-light); }

/* ===== MOBILE ===== */
@media(max-width: 768px) {
    .container { padding: 120px 20px 200px; }
    .page-header h1 { font-size: 36px; }
    .rules-grid { grid-template-columns: 1fr; }
    .barber-grid { grid-template-columns: 1fr; }
    .step-indicator { overflow-x: auto; padding: 16px; gap: 12px; }
    .step-divider { width: 24px; flex: none; }
    .jam-panel-content { flex-direction: column; }
    .booking-btn-panel { width: 100%; justify-content: center; }
    .nav-inner { padding: 0 20px; }
    .card-photo-area { aspect-ratio: 4/3; }
}
@media(max-width: 480px) {
    .page-header h1 { font-size: 32px; }
    .back-btn { padding: 8px 16px; font-size: 10px; }
    .rule-item { padding: 16px; }
    .jam-sticky-panel { padding: 16px 20px 24px; }
    .confirm-box { padding: 32px; }
    .alert-box { padding: 32px; }
}
</style>
</head>
<body>
<!-- Navigation -->
<nav class="nav-wrapper" id="mainNav">
    <div class="nav-inner">
        <div class="nav-left-group">
            <a href="dashboard.php" class="nav-logo">
                <img src="hostel.png" alt="Hostel Barbershop" class="nav-logo-img">
            </a>
        </div>
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    <div class="scroll-line" id="scrollLine"></div>
</nav>

<div class="container">

    <div class="page-header">
        <div class="page-label">
            <span class="page-label-num">02</span>
            <div class="page-label-line"></div>
            <span class="page-label-text">Reservation</span>
        </div>
        <h1>Book Your<br><em>Appointment</em></h1>
        <p>Schedule your session with our master barbers. Select your package, date, and preferred artist.</p>
    </div>

    <div class="step-indicator">
        <div class="step active" id="step1">
            <div class="step-number">1</div>
            <span>Package & Date</span>
        </div>
        <div class="step-divider" id="div1"></div>
        <div class="step" id="step2">
            <div class="step-number">2</div>
            <span>Choose Barber</span>
        </div>
        <div class="step-divider" id="div2"></div>
        <div class="step" id="step3">
            <div class="step-number">3</div>
            <span>Select Time</span>
        </div>
    </div>

    <div class="rules-box">
        <div class="rules-header">
            <i class="fas fa-shield-alt"></i>
            <h3>Terms & Conditions</h3>
        </div>
        <div class="rules-grid">
            <div class="rule-item">
                <div class="rule-number">1</div>
                <div class="rule-content">
                    <div class="rule-title">Max Delay 10 Minutes</div>
                    <div class="rule-desc">Late arrivals may result in cancellation. / Keterlambatan dapat menyebabkan pembatalan.</div>
                </div>
            </div>
            <div class="rule-item">
                <div class="rule-number">2</div>
                <div class="rule-content">
                    <div class="rule-title">No-Show = Forfeited</div>
                    <div class="rule-desc">Slot is forfeited if you don't arrive within 10 min. / Slot hangus tanpa refund.</div>
                </div>
            </div>
            <div class="rule-item">
                <div class="rule-number">3</div>
                <div class="rule-content">
                    <div class="rule-title">Auto-Expire 30 Seconds</div>
                    <div class="rule-desc">Pending expires if unpaid. Complete payment immediately. / Kadaluarsa dalam 30 detik.</div>
                </div>
            </div>
            <div class="rule-item">
                <div class="rule-number">4</div>
                <div class="rule-content">
                    <div class="rule-title">7-Day Advance Booking</div>
                    <div class="rule-desc">Bookings up to 7 days in advance only. / Maksimal 7 hari ke depan.</div>
                </div>
            </div>
            <div class="rule-item">
                <div class="rule-number">5</div>
                <div class="rule-content">
                    <div class="rule-title">Tight Schedule Responsibility</div>
                    <div class="rule-desc">Choosing tight schedules is your risk. Booking is forfeited if late. / Tanggung jawab Anda bila mepet & terlambat.</div>
                </div>
            </div>
        </div>
    </div>

    <form id="bookingForm" method="POST" action="process_booking.php">

        <div class="form-section">
            <label class="form-label"><i class="fas fa-cut"></i> Select Package</label>
            <select name="paket" class="form-select" required id="paketSelect">
                <option value="" disabled <?= !$preselectedPaket ? 'selected' : '' ?>>-- choose package --</option>
                <?php
                $q = mysqli_query($conn,"SELECT * FROM paket ORDER BY harga ASC");
                while($d = mysqli_fetch_assoc($q)){
                    $selected = ($preselectedPaket == $d['id']) ? 'selected' : '';
                ?>
                <option value="<?= $d['id'] ?>" <?= $selected ?>><?= $d['nama_paket'] ?> &mdash; Rp<?= number_format($d['harga'], 0, ',', '.') ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="form-section">
            <label class="form-label"><i class="fas fa-calendar"></i> Select Date</label>
            <input type="date" name="tanggal" id="tanggal" class="form-date" 
                   min="<?= $today ?>" 
                   max="<?= $maxDate ?>" 
                   value="<?= $preselectedTanggal ?>" 
                   required 
                   onfocus="this.showPicker()">
        </div>

        <div class="barber-section <?= ($preselectedBarber || $preselectedTanggal) ? 'visible' : '' ?>" id="barberSection">
            <label class="form-label"><i class="fas fa-user"></i> Choose Your Barber</label>
            <div class="barber-grid" id="barberGrid">

                <?php foreach($barberData as $d){ 
                    $isPreselected = ($preselectedBarber == $d['id']);
                ?>

                <label class="barber-card <?= $isPreselected ? 'active' : '' ?>" id="card_<?= $d['id'] ?>" data-barber-id="<?= $d['id'] ?>">

                    <input type="radio" name="barber" value="<?= $d['id'] ?>" required <?= $isPreselected ? 'checked' : '' ?>>

                    <div class="card-photo-area">
                        <img src="admin/upload/<?= $d['foto'] ?>" alt="<?= $d['nama'] ?>">
                        <div class="card-photo-overlay"></div>
                        <div class="card-selected-badge">
                            <i class="fas fa-check"></i> Selected
                        </div>
                        <div class="card-off-badge" id="offBadge_<?= $d['id'] ?>" style="display:none;">
                            <i class="fas fa-ban"></i> Off Duty
                        </div>
                        <div class="card-zoom-hint" onclick="openImage(event, this.parentElement.querySelector('img'))">
                            <i class="fas fa-expand"></i>
                        </div>
                    </div>

                    <div class="card-info">
                        <div class="card-info-header">
                            <div>
                                <div class="card-name"><?= $d['nama'] ?></div>
                                <div class="card-role">Master Barber</div>
                            </div>
                            <span class="card-status available" id="status_<?= $d['id'] ?>">
                                <i class="fas fa-circle" style="font-size:6px;"></i> On Duty
                            </span>
                        </div>

                        <div class="card-skills">
                            <div class="skill-row">
                                <div class="skill-name">Fade</div>
                                <div class="skill-bar-track">
                                    <div class="skill-bar-fill" style="--val:<?= $d['skill_fade'] ?>%"></div>
                                </div>
                                <div class="skill-percent"><?= $d['skill_fade'] ?>%</div>
                            </div>
                            <div class="skill-row">
                                <div class="skill-name">Scissor</div>
                                <div class="skill-bar-track">
                                    <div class="skill-bar-fill" style="--val:<?= $d['skill_scissoring'] ?>%"></div>
                                </div>
                                <div class="skill-percent"><?= $d['skill_scissoring'] ?>%</div>
                            </div>
                            <div class="skill-row">
                                <div class="skill-name">Long</div>
                                <div class="skill-bar-track">
                                    <div class="skill-bar-fill" style="--val:<?= $d['skill_longcut'] ?>%"></div>
                                </div>
                                <div class="skill-percent"><?= $d['skill_longcut'] ?>%</div>
                            </div>
                            <div class="skill-row">
                                <div class="skill-name">Short</div>
                                <div class="skill-bar-track">
                                    <div class="skill-bar-fill" style="--val:<?= $d['skill_shortcut'] ?>%"></div>
                                </div>
                                <div class="skill-percent"><?= $d['skill_shortcut'] ?>%</div>
                            </div>
                            <div class="skill-row">
                                <div class="skill-name">Beard</div>
                                <div class="skill-bar-track">
                                    <div class="skill-bar-fill" style="--val:<?= $d['skill_beardcut'] ?>%"></div>
                                </div>
                                <div class="skill-percent"><?= $d['skill_beardcut'] ?>%</div>
                            </div>
                        </div>

                        <div class="card-select-btn">
                            <span>Select Barber</span>
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>

                </label>

                <?php } ?>

            </div>
        </div>

        <input type="hidden" name="jam" id="jamInput" value="<?= $preselectedJam ?>">

        <button type="button" class="booking-btn" onclick="showConfirmModal()">
            <i class="fas fa-calendar-check"></i> Book Now
        </button>

    </form>

</div>

<div class="jam-sticky-panel <?= ($preselectedBarber && $preselectedTanggal && $preselectedJam) ? 'visible' : '' ?>" id="jamStickyPanel">
    <div class="jam-panel-header">
        <span class="jam-panel-title"><i class="fas fa-clock"></i> Select Time Slot</span>
        <div class="jam-panel-close" onclick="closeJamPanel()"><i class="fas fa-times"></i></div>
    </div>
    <div class="jam-panel-content">
        <div class="jam-panel-scroll" id="jamPanelContainer"></div>
        <button type="button" class="booking-btn-panel" onclick="showConfirmModal()">
            <span>Book Now</span> <i class="fas fa-arrow-right"></i>
        </button>
    </div>
    <p class="jam-panel-info" id="jamPanelInfo"><i class="fas fa-info-circle"></i> Select a time slot to proceed with booking</p>
</div>

<div class="alert-overlay" id="alertModal">
    <div class="alert-box">
        <div class="alert-icon">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <div class="alert-title" id="alertTitle">Oops!</div>
        <div class="alert-message" id="alertMessage">Please fill all required fields.</div>
        <button class="alert-btn" onclick="closeAlertModal()"><span>Got it</span></button>
    </div>
</div>

<div class="confirm-overlay" id="confirmModal">
    <div class="confirm-box">
        <div class="confirm-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="confirm-title">Confirm Your Booking</div>
        <div class="confirm-subtitle">Please review the terms before proceeding with payment.</div>
        <div class="confirm-rules">
            <div class="confirm-rule">
                <i class="fas fa-clock"></i>
                <span>You have <strong>30 seconds</strong> to complete payment after booking. Unpaid bookings will be automatically cancelled.</span>
            </div>
            <div class="confirm-rule">
                <i class="fas fa-hourglass-half"></i>
                <span>If you arrive <strong>more than 10 minutes late</strong> from your scheduled time, your booking will be <strong>forfeited</strong> without refund.</span>
            </div>
            <div class="confirm-rule">
                <i class="fas fa-ban"></i>
                <span>No-shows or failure to arrive within the grace period will result in <strong>immediate forfeiture</strong> of your slot.</span>
            </div>
        </div>
        <div class="confirm-buttons">
            <button class="confirm-btn no" onclick="closeConfirmModal()">
                <i class="fas fa-times"></i> <span>Cancel</span>
            </button>
            <button class="confirm-btn yes" onclick="proceedBooking()">
                <span>Proceed</span> <i class="fas fa-check"></i>
            </button>
        </div>
    </div>
</div>

<div class="image-modal" id="imageModal">
    <div class="close-modal" onclick="closeImage()"><i class="fas fa-times"></i></div>
    <img src="" id="modalImage">
</div>
<script>
let bookedData = <?= json_encode($booked) ?>;
let barberSchedule = <?= json_encode($jadwalBarber) ?>;
let jamData = <?= json_encode($jamData) ?>;
let todayDate = '<?= $today ?>';
let currentTime = '<?= $currentHour ?>';
let preselectedBarber = <?= $preselectedBarber ?>;
let preselectedTanggal = '<?= $preselectedTanggal ?>';
let preselectedJam = '<?= $preselectedJam ?>';
let preselectedPaket = <?= $preselectedPaket ?>;

document.addEventListener('DOMContentLoaded', function(){
    let tanggalInput = document.getElementById("tanggal");
    let paketSelect = document.getElementById("paketSelect");

    // Initialize on load
    if(tanggalInput.value){
        document.getElementById("barberSection").classList.add("visible");
        checkBarberAvailability(tanggalInput.value);

        // If all preselected values exist, auto-render jam panel and select
        if(preselectedBarber && preselectedTanggal){
            setTimeout(() => {
                renderJamPanel();
                setTimeout(() => {
                    let jamBtns = document.querySelectorAll('.jam-btn');
                    jamBtns.forEach(btn => {
                        if(btn.innerText.includes(preselectedJam) && !btn.disabled){
                            document.querySelectorAll('.jam-btn').forEach(b => {
                                if(!b.disabled) b.classList.remove('selected');
                            });
                            btn.classList.add('selected');
                            document.getElementById('jamInput').value = preselectedJam;
                            updateStepIndicator();
                        }
                    });
                }, 100);
            }, 300);
        }
    }

    // Auto-select paket if preselected
    if(preselectedPaket) {
        paketSelect.value = preselectedPaket;
    }

    setTimeout(triggerSkillAnimations, 600);
    updateStepIndicator();
    initNavScroll();
    initScrollProgress();
});

function triggerSkillAnimations() {
    const cards = document.querySelectorAll('.barber-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('animated');
        }, index * 100);
    });
}

function initScrollProgress() {
    const scrollLine = document.getElementById('scrollLine');
    window.addEventListener('scroll', () => {
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const pct = scrollHeight > 0 ? (window.pageYOffset / scrollHeight) * 100 : 0;
        scrollLine.style.width = pct + '%';
    }, { passive: true });
}

function initNavScroll() {
    const nav = document.getElementById('mainNav');
    window.addEventListener('scroll', () => {
        nav.classList.toggle('scrolled', window.pageYOffset > 60);
    }, { passive: true });
}

function updateStepIndicator(){
    let paket = document.querySelector("[name=paket]").value;
    let tanggal = document.getElementById("tanggal").value;
    let barber = document.querySelector("[name=barber]:checked");
    let jam = document.getElementById("jamInput").value;

    document.getElementById('step1').classList.remove('completed', 'active');
    document.getElementById('step2').classList.remove('completed', 'active');
    document.getElementById('step3').classList.remove('completed', 'active');
    document.getElementById('div1').classList.remove('active');
    document.getElementById('div2').classList.remove('active');

    if(paket && tanggal){
        document.getElementById('step1').classList.add('completed');
        document.getElementById('div1').classList.add('active');
        document.getElementById('step2').classList.add('active');
    } else {
        document.getElementById('step1').classList.add('active');
    }

    if(barber){
        document.getElementById('step2').classList.add('completed');
        document.getElementById('step2').classList.remove('active');
        document.getElementById('div2').classList.add('active');
        document.getElementById('step3').classList.add('active');
    }

    if(jam){
        document.getElementById('step3').classList.add('completed');
        document.getElementById('step3').classList.remove('active');
    }
}

document.getElementById("tanggal").addEventListener("change", function(){
    let tanggal = this.value;
    if(tanggal){
        document.getElementById("barberSection").classList.add("visible");
        checkBarberAvailability(tanggal);
        updateStepIndicator();
        setTimeout(triggerSkillAnimations, 400);
        // Re-render jam panel if barber already selected (keeps panel open on date change)
        let barber = document.querySelector("[name=barber]:checked");
        if(barber){
            renderJamPanel();
        }
    } else {
        document.getElementById("barberSection").classList.remove("visible");
        closeJamPanel();
        resetSelections();
    }
});

document.getElementById("paketSelect").addEventListener("change", function(){
    updateStepIndicator();
});

document.querySelectorAll(".barber-card").forEach(card => {
    card.addEventListener("click", function(e){
        if(e.target.closest('.card-zoom-hint')) return;
        if(this.classList.contains('disabled')) return;
        document.querySelectorAll(".barber-card").forEach(c => c.classList.remove("active"));
        this.classList.add("active");
        renderJamPanel();
        updateStepIndicator();
    });
});

function checkBarberAvailability(tanggal){
    document.querySelectorAll(".barber-card").forEach(card => {
        let barberId = card.getAttribute('data-barber-id');
        let isAvailable = barberSchedule.includes(tanggal + "_" + barberId);
        let statusBadge = document.getElementById('status_' + barberId);
        let offBadge = document.getElementById('offBadge_' + barberId);
        let radio = card.querySelector('input[type="radio"]');
        let selectBtn = card.querySelector('.card-select-btn');

        if(!isAvailable){
            card.classList.add('disabled');
            card.classList.remove('active');
            statusBadge.className = 'card-status off';
            statusBadge.innerHTML = '<i class="fas fa-ban" style="font-size:8px;"></i> Off Duty';
            offBadge.style.display = 'flex';
            radio.disabled = true;
            radio.checked = false;
            selectBtn.innerHTML = '<span>Unavailable</span> <i class="fas fa-times"></i>';
        } else {
            card.classList.remove('disabled');
            statusBadge.className = 'card-status available';
            statusBadge.innerHTML = '<i class="fas fa-circle" style="font-size:6px;"></i> On Duty';
            offBadge.style.display = 'none';
            radio.disabled = false;
            selectBtn.innerHTML = '<span>Select Barber</span> <i class="fas fa-arrow-right"></i>';
        }
    });
}

function resetSelections(){
    document.querySelectorAll(".barber-card").forEach(c => {
        c.classList.remove('active');
        c.querySelector('input').checked = false;
    });
    document.getElementById("jamInput").value = "";
    updateStepIndicator();
}

function renderJamPanel(){
    let container = document.getElementById("jamPanelContainer");
    let info = document.getElementById("jamPanelInfo");
    let panel = document.getElementById("jamStickyPanel");
    container.innerHTML = "";
    info.innerHTML = '<i class="fas fa-info-circle"></i> Select a time slot to proceed with booking';

    let barber = document.querySelector("[name=barber]:checked");
    let tanggal = document.getElementById("tanggal").value;

    if(!barber || !tanggal){
        panel.classList.remove("visible");
        return;
    }

    let barberId = barber.value;
    let available = barberSchedule.includes(tanggal + "_" + barberId);

    if(!available){
        info.innerHTML = '<i class="fas fa-ban"></i> This barber is off duty on selected date';
        panel.classList.add("visible");
        return;
    }

    let isToday = (tanggal === todayDate);

    jamData.forEach(jam => {
        let btn = document.createElement("button");
        btn.type = "button";
        btn.className = "jam-btn";
        btn.innerHTML = '<span>' + jam + '</span>';

        if(isToday && jam < currentTime){
            btn.disabled = true;
            btn.innerHTML = '<span>' + jam + ' <i class="fas fa-clock" style="margin-left:4px;font-size:10px;opacity:0.5;"></i></span>';
            btn.title = "This time slot has already passed";
        }

        let key = barberId + "_" + tanggal + "_" + jam;
        let isBooked = bookedData.includes(key);

        if(isBooked){
            btn.disabled = true;
            btn.innerHTML = '<span>' + jam + ' <i class="fas fa-lock" style="margin-left:4px;font-size:10px;opacity:0.5;"></i></span>';
        }

        btn.onclick = function(){
            if(btn.disabled) return;
            
            // Toggle: if already selected, deselect it
            if(btn.classList.contains("selected")){
                btn.classList.remove("selected");
                document.getElementById("jamInput").value = "";
                updateStepIndicator();
                return;
            }
            
            // Otherwise select this one and deselect others
            document.getElementById("jamInput").value = jam;
            container.querySelectorAll(".jam-btn").forEach(b => {
                if(!b.disabled) b.classList.remove("selected");
            });
            btn.classList.add("selected");
            updateStepIndicator();
        };
        container.appendChild(btn);
    });
    
    // Sync visual state with hidden input when re-rendering
    let currentJam = document.getElementById("jamInput").value;
    if(currentJam){
        let matched = false;
        container.querySelectorAll(".jam-btn").forEach(b => {
            if(!b.disabled && b.innerText.trim().startsWith(currentJam)){
                b.classList.add("selected");
                matched = true;
            }
        });
        if(!matched){
            document.getElementById("jamInput").value = "";
            updateStepIndicator();
        }
    }
    
    panel.classList.add("visible");
}

function closeJamPanel(){
    document.getElementById("jamStickyPanel").classList.remove("visible");
    document.getElementById("jamInput").value = "";
    updateStepIndicator();
}

function showAlert(title, message){
    document.getElementById("alertTitle").innerText = title;
    document.getElementById("alertMessage").innerText = message;
    document.getElementById("alertModal").classList.add("active");
    document.body.classList.add("modal-open");
}

function closeAlertModal(){
    document.getElementById("alertModal").classList.remove("active");
    document.body.classList.remove("modal-open");
}

function showConfirmModal(){
    let paket = document.querySelector("[name=paket]").value;
    let barber = document.querySelector("[name=barber]:checked");
    let tanggal = document.getElementById("tanggal").value;
    let jam = document.getElementById("jamInput").value;

    if(!paket || !barber || !tanggal || !jam){
        showAlert("Incomplete Booking", "Please select package, date, barber, and time before proceeding.");
        return;
    }
    if(tanggal === todayDate && jam < currentTime){
        showAlert("Invalid Time", "Cannot book a time slot that has already passed. Please select a future time.");
        return;
    }
    document.getElementById("confirmModal").classList.add("active");
    document.body.classList.add("modal-open");
}

function closeConfirmModal(){
    document.getElementById("confirmModal").classList.remove("active");
    document.body.classList.remove("modal-open");
}

function proceedBooking(){
    document.getElementById("confirmModal").classList.remove("active");
    document.body.classList.remove("modal-open");
    document.getElementById("bookingForm").submit();
}

function openImage(event, imgEl){
    event.stopPropagation();
    event.preventDefault();
    let img = imgEl.src;
    document.getElementById("modalImage").src = img;
    document.getElementById("imageModal").classList.add("active");
    document.body.classList.add("modal-open");
}

function closeImage(){
    document.getElementById("imageModal").classList.remove("active");
    document.body.classList.remove("modal-open");
}

document.getElementById("imageModal").addEventListener("click", function(e){
    if(e.target.id === "imageModal") closeImage();
});
document.getElementById("confirmModal").addEventListener("click", function(e){
    if(e.target.id === "confirmModal") closeConfirmModal();
});
document.getElementById("alertModal").addEventListener("click", function(e){
    if(e.target.id === "alertModal") closeAlertModal();
});
</script>

</body>
</html>