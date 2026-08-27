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

/* ==================== USER DATA ==================== */
$userId = $_SESSION['user']['id'];
$qUser = mysqli_query($conn, "SELECT * FROM users WHERE id='$userId'");
$userData = mysqli_fetch_assoc($qUser);

$username = $userData['username'] ?? 'User';
$email = $userData['email'] ?? '';
$userPhoto = $userData['photo'] ?? '';

/* ==================== RATING CHECK ==================== */
$qHasRated = mysqli_query($conn, "SELECT * FROM barbershop_rating WHERE user_id='$userId' LIMIT 1");
$hasRated = mysqli_num_rows($qHasRated) > 0;

$qHasBooked = mysqli_query($conn, "
    SELECT COUNT(*) as total FROM booking 
    WHERE user_id='$userId' AND status = 'paid'
");
$hasBooked = mysqli_fetch_assoc($qHasBooked)['total'] > 0;

$showRatingPopup = ($hasBooked && !$hasRated);

/* ==================== DATE & TIME ==================== */
$today = date('Y-m-d');
$now = time();
$currentTime = date('H:i:s');
$currentHour = date('H:i');

/* ==================== FETCH TIME SLOTS ==================== */
$jamData = [];
$qJam = mysqli_query($conn,"SELECT jam_buka FROM jam_operasional WHERE status='Buka' ORDER BY jam_buka ASC");
while($j = mysqli_fetch_assoc($qJam)){
    $jamData[] = substr($j['jam_buka'],0,5);
}

/* ==================== FETCH BOOKED SLOTS ==================== */
$booked = [];
$qBooked = mysqli_query($conn,"SELECT barber_id,tanggal,jam FROM booking WHERE status='pending' OR status='paid'");
while($b = mysqli_fetch_assoc($qBooked)){
    $booked[] = $b['barber_id']."_".$b['tanggal']."_".substr($b['jam'],0,5);
}

/* ==================== UPCOMING BOOKING ==================== */
$qUpcoming = mysqli_query($conn,"
    SELECT b.*, br.nama as barber_name, br.foto as barber_foto, p.nama_paket 
    FROM booking b
    LEFT JOIN barber br ON b.barber_id = br.id
    LEFT JOIN paket p ON b.paket_id = p.id
    WHERE b.user_id='$userId'
    AND b.status IN ('pending','paid')
    AND CONCAT(b.tanggal,' ',b.jam) > NOW()
    ORDER BY b.tanggal ASC, b.jam ASC
    LIMIT 1
");
$upcoming = mysqli_fetch_assoc($qUpcoming);

/* ==================== HISTORY ==================== */
$qHistory = mysqli_query($conn,"
    SELECT b.*, br.nama as barber_name, p.nama_paket 
    FROM booking b
    LEFT JOIN barber br ON b.barber_id = br.id
    LEFT JOIN paket p ON b.paket_id = p.id
    WHERE b.user_id='$userId'
    ORDER BY b.created_at DESC
    LIMIT 3
");

/* ==================== NEXT AVAILABLE SLOT ==================== */
$nextSlot = null;

$qAllJam = mysqli_query($conn,"
    SELECT jam_buka 
    FROM jam_operasional 
    WHERE status='Buka' 
    ORDER BY jam_buka ASC
");
$allJamList = [];
while($j = mysqli_fetch_assoc($qAllJam)){
    $allJamList[] = $j['jam_buka'];
}

foreach($allJamList as $jamBuka){
    $jamStr = substr($jamBuka, 0, 5);
    $jamFull = $jamBuka;

    if($jamFull <= $currentTime){
        continue;
    }

    $qOnDuty = mysqli_query($conn,"
        SELECT br.id, br.nama, br.foto 
        FROM barber br
        INNER JOIN jadwal_barber jb ON br.id = jb.barber_id
        WHERE jb.tanggal = '$today'
        AND br.id NOT IN (
            SELECT barber_id FROM booking 
            WHERE tanggal = '$today' 
            AND jam = '$jamStr' 
            AND status IN ('pending','paid')
        )
        ORDER BY br.id ASC
        LIMIT 1
    ");

    $availableBarber = mysqli_fetch_assoc($qOnDuty);

    if($availableBarber){
        $nextSlot = [
            'time' => $jamStr,
            'barber' => $availableBarber
        ];
        break;
    }
}

/* ==================== AVAILABLE BARBERS TODAY ==================== */
$qAvailableToday = mysqli_query($conn,"
    SELECT br.id, br.nama, br.foto, br.keterangan,
    ((br.skill_fade + br.skill_scissoring + br.skill_longcut + br.skill_shortcut + br.skill_beardcut) / 5) as avg_skill
    FROM barber br
    INNER JOIN jadwal_barber jb ON br.id = jb.barber_id
    WHERE jb.tanggal = '$today'
    ORDER BY avg_skill DESC
");

/* ==================== PACKETS & TIME SLOTS ==================== */
$qPaketList = mysqli_query($conn, "SELECT id, nama_paket, harga FROM paket ORDER BY harga ASC");
$paketList = [];
while($p = mysqli_fetch_assoc($qPaketList)) {
    $paketList[] = $p;
}

$qJamList = mysqli_query($conn, "SELECT jam_buka FROM jam_operasional WHERE status='Buka' ORDER BY jam_buka ASC");
$jamList = [];
while($j = mysqli_fetch_assoc($qJamList)) {
    $jamList[] = substr($j['jam_buka'], 0, 5);
}

/* ==================== HELPER: CHECK IF BARBER FULLY BOOKED ==================== */
function isBarberFullyBooked($conn, $barberId, $tanggal, $jamData, $booked) {
    $availableCount = 0;
    $isToday = ($tanggal === date('Y-m-d'));
    $currentHour = date('H:i');
    
    foreach($jamData as $jam) {
        // Skip passed time slots for today
        if($isToday && $jam < $currentHour) {
            continue;
        }
        
        $key = $barberId . "_" . $tanggal . "_" . $jam;
        if(!in_array($key, $booked)) {
            $availableCount++;
        }
    }
    
    return ($availableCount === 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | Hostel Barbershop</title>
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
    --warning: #e8c87a;
    --radius: 2px;
    --transition: all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
html { scroll-behavior: smooth; }
/* ===== CUSTOM CURSOR ===== */
.cursor {
    position: fixed;
    top: 0;
    left: 0;
    width: 10px;
    height: 10px;
    background: var(--gold);
    border-radius: 50%;
    pointer-events: none;
    z-index: 99999;
    mix-blend-mode: normal;
    will-change: transform;
    transition: width 0.2s ease, height 0.2s ease, background 0.2s ease;
}

.cursor-follower {
    position: fixed;
    top: 0;
    left: 0;
    width: 36px;
    height: 36px;
    border: 1.5px solid rgba(232,200,122,0.5);
    border-radius: 50%;
    pointer-events: none;
    z-index: 99998;
    will-change: transform;
    transition: width 0.3s ease, height 0.3s ease, border-color 0.3s ease;
}

.cursor.hover { width: 18px; height: 18px; background: var(--gold-light); }
.cursor-follower.hover { width: 52px; height: 52px; border-color: rgba(232,200,122,0.7); }

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

/* ===== RATING POPUP ===== */
.rating-overlay {
    position: fixed;
    inset: 0;
    background: rgba(5,5,7,0.92);
    backdrop-filter: blur(20px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}
.rating-overlay.hidden { display: none; }
.rating-popup {
    background: var(--bg-secondary);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: 48px;
    max-width: 480px;
    width: 100%;
    text-align: center;
    position: relative;
    overflow: hidden;
    animation: popupIn 0.5s cubic-bezier(0.25,0.46,0.45,0.94);
}
.rating-popup::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--gold);
}
@keyframes popupIn {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.rating-popup-icon {
    width: 64px; height: 64px;
    border-radius: 50%;
    background: var(--gold-dim);
    border: 1.5px solid var(--gold-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin: 0 auto 20px;
    color: var(--gold);
}
.rating-popup h2 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 32px;
    font-weight: 400;
    margin-bottom: 8px;
    letter-spacing: -1px;
}
.rating-popup p {
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 28px;
    line-height: 1.7;
}
.rating-popup-stars {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-bottom: 28px;
}
.popup-star {
    width: 52px; height: 52px;
    border-radius: 50%;
    background: transparent;
    border: 1.5px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: var(--text-muted);
    cursor: pointer;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}
.popup-star::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold);
    transform: scale(0);
    transition: transform 0.3s ease;
    border-radius: 50%;
}
.popup-star:hover,
.popup-star.active {
    color: var(--bg-primary);
    border-color: var(--gold);
    transform: scale(1.1);
}
.popup-star:hover::before,
.popup-star.active::before {
    transform: scale(1);
}
.popup-star i { position: relative; z-index: 1; }
.rating-popup-textarea {
    width: 100%;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    color: var(--text-primary);
    padding: 14px 18px;
    border-radius: var(--radius);
    font-size: 14px;
    font-family: 'Montserrat', sans-serif;
    outline: none;
    transition: var(--transition);
    resize: vertical;
    min-height: 100px;
    margin-bottom: 20px;
}
.rating-popup-textarea:focus {
    border-color: var(--gold-border);
    box-shadow: 0 0 0 4px var(--gold-dim);
}
.rating-popup-textarea::placeholder { color: var(--text-muted); }
.rating-popup-submit {
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
.rating-popup-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(232,200,122,0.3);
}
.rating-popup-submit i { transition: transform 0.3s ease; }
.rating-popup-submit:hover i { transform: translateX(3px); }
.rating-popup-submit:disabled { opacity: 0.4; cursor: not-allowed; transform: none; box-shadow: none; }

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

/* ===== DASHBOARD CONTENT ===== */
.dashboard-main {
    padding: 140px 48px 80px;
    max-width: 1280px;
    margin: 0 auto;
}

/* ===== WELCOME SECTION - REDESIGNED ===== */
.welcome-section {
    margin-bottom: 64px;
    position: relative;
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 64px;
    align-items: center;
    min-height: 200px;
}
.welcome-left {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding-top: 8px;
}
.welcome-label {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}
.welcome-label-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 12px;
    font-weight: 600;
    color: var(--gold);
    letter-spacing: 2px;
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
.welcome-desc {
    font-size: 15px;
    font-weight: 500;
    color: var(--text-secondary);
    max-width: 420px;
    line-height: 1.9;
    letter-spacing: 0.3px;
    margin-top: 8px;
}
.welcome-right {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    position: relative;
}
.welcome-right::before {
    content: '';
    position: absolute;
    left: -32px;
    top: 50%;
    transform: translateY(-50%);
    width: 1px;
    height: 80%;
    background: linear-gradient(to bottom, transparent, var(--gold-border), transparent);
}
.welcome-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(32px, 4vw, 48px);
    font-weight: 300;
    letter-spacing: -1.5px;
    line-height: 1.1;
    margin-bottom: 0;
    text-align: left;
}
.welcome-title em {
    font-style: italic;
    color: var(--gold);
    position: relative;
    display: inline-block;
}
.welcome-title em::after {
    content: '';
    position: absolute;
    bottom: 4px;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--gold), transparent);
    opacity: 0.4;
}

@media(max-width:900px) {
    .welcome-section { 
        grid-template-columns: 1fr; 
        gap: 32px; 
        min-height: auto;
    }
    .welcome-right { 
        align-items: flex-start; 
        padding-top: 8px;
    }
    .welcome-right::before { 
        display: none; 
    }
    .welcome-title { 
        font-size: 36px; 
    }
    .welcome-left {
        order: 2;
    }
    .welcome-right {
        order: 1;
    }
    .welcome-desc {
        max-width: 100%;
    }
}
@media(max-width:600px) {
    .welcome-title { 
        font-size: 28px; 
    }
    .welcome-section {
        gap: 24px;
        margin-bottom: 48px;
    }
}

/* ===== BOOKING CTA ===== */
.booking-cta {
    margin-bottom: 56px;
    position: relative;
}
.booking-cta-card {
    position: relative;
    background: var(--bg-secondary);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: 48px 40px;
    overflow: hidden;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 40px;
    text-decoration: none;
    color: var(--text-primary);
}
.booking-cta-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--gold);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.5s cubic-bezier(0.25,0.46,0.45,0.94);
}
.booking-cta-card:hover {
    border-color: var(--gold-border);
    background: var(--bg-hover);
    transform: translateY(-4px);
    box-shadow: 0 20px 56px rgba(0,0,0,0.5);
}
.booking-cta-card:hover::before { transform: scaleX(1); }
.cta-visual {
    position: absolute;
    right: -60px;
    top: -60px;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(232,200,122,0.04) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.cta-content { position: relative; z-index: 1; flex: 1; }
.cta-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    background: var(--gold-dim);
    border: 1.5px solid var(--gold-border);
    padding: 8px 16px;
    border-radius: var(--radius);
    margin-bottom: 20px;
    color: var(--gold);
}
.cta-badge i { font-size: 10px; }
.cta-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(28px, 4vw, 40px);
    font-weight: 400;
    letter-spacing: -1px;
    margin-bottom: 12px;
    line-height: 1.1;
}
.cta-desc {
    font-size: 14px;
    color: var(--text-muted);
    max-width: 400px;
    line-height: 1.8;
    margin-bottom: 0;
}
.cta-action-side {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    flex-shrink: 0;
}
.cta-btn-main {
    width: 80px; height: 80px;
    border-radius: 50%;
    background: var(--gold);
    color: var(--bg-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    transition: var(--transition);
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 24px rgba(232,200,122,0.2);
    position: relative;
    overflow: hidden;
}
.cta-btn-main::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold-light);
    transform: scale(0);
    transition: transform 0.4s ease;
    border-radius: 50%;
}
.cta-btn-main:hover {
    transform: scale(1.1);
    box-shadow: 0 8px 40px rgba(232,200,122,0.35);
}
.cta-btn-main:hover::before { transform: scale(1); }
.cta-btn-main i { position: relative; z-index: 1; transition: transform 0.3s ease; }
.cta-btn-main:hover i { transform: translateX(3px); }
.cta-hint {
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    text-align: center;
    letter-spacing: 1px;
}
.cta-hint span { display: block; font-size: 10px; margin-top: 4px; color: var(--text-muted); opacity: 0.7; }

/* ===== DASHBOARD GRID ===== */
.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 56px;
}

/* ===== SECTION CARD ===== */
.section-card {
    background: var(--bg-secondary);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: 36px;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}
.section-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--gold);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.5s cubic-bezier(0.25,0.46,0.45,0.94);
}
.section-card:hover {
    border-color: var(--gold-border);
    background: var(--bg-hover);
}
.section-card:hover::before { transform: scaleX(1); }
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 28px;
}
.section-header-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 22px;
    font-weight: 400;
    letter-spacing: -0.5px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.section-header-title::before {
    content: '';
    width: 3px;
    height: 20px;
    background: var(--gold);
    border-radius: 2px;
}
.btn-animated {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: transparent;
    border: 1.5px solid var(--border);
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    cursor: pointer;
    font-family: 'Montserrat', sans-serif;
}
.btn-animated::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold);
    transform: translateX(-100%);
    transition: transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}
.btn-animated:hover {
    color: var(--bg-primary);
    border-color: var(--gold);
}
.btn-animated:hover::before { transform: translateX(0); }
.btn-animated span, .btn-animated i { position: relative; z-index: 1; }
.btn-animated i { transition: transform 0.3s ease; font-size: 9px; }
.btn-animated:hover i { transform: translateX(4px); }

/* ===== UPCOMING BOOKING ===== */
.upcoming-empty {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-muted);
}
.upcoming-empty i {
    font-size: 40px;
    margin-bottom: 16px;
    opacity: 0.3;
    color: var(--gold);
}
.upcoming-empty p {
    font-size: 14px;
    line-height: 1.7;
}
.upcoming-empty a {
    color: var(--gold);
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition);
    border-bottom: 1px solid var(--gold-border);
    padding-bottom: 2px;
}
.upcoming-empty a:hover { border-color: var(--gold); }
.upcoming-content {
    display: flex;
    align-items: flex-start;
    gap: 20px;
}
.upcoming-avatar {
    width: 64px; height: 64px;
    border-radius: var(--radius);
    overflow: hidden;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    flex-shrink: 0;
    position: relative;
}
.upcoming-avatar::after {
    content: '';
    position: absolute;
    inset: -3px;
    border-radius: calc(var(--radius) + 3px);
    border: 1.5px solid var(--gold-border);
    opacity: 0;
    transition: var(--transition);
}
.section-card:hover .upcoming-avatar::after { opacity: 0.5; }
.upcoming-avatar img {
    width: 100%; height: 100%;
    object-fit: cover;
    filter: grayscale(20%);
    transition: filter 0.4s ease;
}
.upcoming-avatar:hover img { filter: grayscale(0%); }
.upcoming-info { flex: 1; }
.upcoming-barber {
    font-family: 'Cormorant Garamond', serif;
    font-size: 24px;
    font-weight: 400;
    margin-bottom: 4px;
    letter-spacing: -0.5px;
}
.upcoming-package {
    font-size: 12px;
    color: var(--gold);
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 16px;
    padding: 6px 14px;
    background: var(--gold-dim);
    border: 1.5px solid var(--gold-border);
    display: inline-block;
    border-radius: var(--radius);
}
.upcoming-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}
.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--text-secondary);
    padding: 6px 12px;
    background: var(--bg-card);
    border-radius: var(--radius);
    border: 1.5px solid var(--border);
    font-weight: 500;
    letter-spacing: 0.5px;
}
.meta-item i {
    font-size: 11px;
    color: var(--gold);
}
.status-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: var(--radius);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}
.status-tag.paid {
    background: rgba(110, 231, 160, 0.08);
    border: 1.5px solid rgba(110, 231, 160, 0.25);
    color: var(--success);
}
.status-tag.pending {
    background: rgba(232, 200, 122, 0.08);
    border: 1.5px solid rgba(232, 200, 122, 0.25);
    color: var(--warning);
}
.upcoming-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: var(--radius);
    background: transparent;
    border: 1.5px solid var(--border);
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    cursor: pointer;
    font-family: 'Montserrat', sans-serif;
}
.upcoming-action::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold);
    transform: translateX(-100%);
    transition: transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}
.upcoming-action:hover {
    color: var(--bg-primary);
    border-color: var(--gold);
}
.upcoming-action:hover::before { transform: translateX(0); }
.upcoming-action i, .upcoming-action span { position: relative; z-index: 1; }
.upcoming-action i { transition: transform 0.3s ease; font-size: 9px; }
.upcoming-action:hover i { transform: translateX(4px); }

/* ===== QUICK SLOT ===== */
.quick-slot-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
}
.quick-slot-header i {
    font-size: 10px;
    color: var(--gold);
    animation: pulse 2s ease-in-out infinite;
}
.quick-slot-header span {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2.5px;
    color: var(--gold);
}
.quick-slot-time {
    font-family: 'Cormorant Garamond', serif;
    font-size: 48px;
    font-weight: 300;
    letter-spacing: -2px;
    margin-bottom: 8px;
    color: var(--text-primary);
}
.quick-slot-barber {
    font-size: 14px;
    color: var(--text-secondary);
    margin-bottom: 28px;
    font-weight: 500;
}
.quick-slot-barber strong {
    color: var(--gold);
    font-weight: 600;
}
.quick-slot-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 12px 24px;
    border-radius: var(--radius);
    background: transparent;
    border: 1.5px solid var(--border);
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    cursor: pointer;
    font-family: 'Montserrat', sans-serif;
}
.quick-slot-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold);
    transform: translateX(-100%);
    transition: transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}
.quick-slot-btn:hover {
    color: var(--bg-primary);
    border-color: var(--gold);
    transform: translateY(-3px);
    box-shadow: 0 12px 40px rgba(232,200,122,0.15);
}
.quick-slot-btn:hover::before { transform: translateX(0); }
.quick-slot-btn i, .quick-slot-btn span { position: relative; z-index: 1; }
.quick-slot-btn i { transition: transform 0.3s ease; font-size: 9px; }
.quick-slot-btn:hover i { transform: translateX(4px); }
.quick-slot-empty {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-muted);
}
.quick-slot-empty i {
    font-size: 36px;
    margin-bottom: 16px;
    opacity: 0.3;
    color: var(--gold);
}
.quick-slot-empty p {
    font-size: 14px;
    margin-bottom: 20px;
    line-height: 1.7;
}
.quick-slot-empty a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: transparent;
    color: var(--text-secondary);
    text-decoration: none;
    font-weight: 700;
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    border-radius: var(--radius);
    border: 1.5px solid var(--border);
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    font-family: 'Montserrat', sans-serif;
}
.quick-slot-empty a::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold);
    transform: translateX(-100%);
    transition: transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}
.quick-slot-empty a:hover {
    color: var(--bg-primary);
    border-color: var(--gold);
}
.quick-slot-empty a:hover::before { transform: translateX(0); }
.quick-slot-empty a span, .quick-slot-empty a i { position: relative; z-index: 1; }

/* ===== AVAILABLE BARBERS - REDESIGNED CLEAN ===== */
.available-section { margin-bottom: 56px; }
.available-scroll {
    display: flex;
    gap: 20px;
    overflow-x: auto;
    padding-bottom: 24px;
    scroll-snap-type: x mandatory;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    padding-left: 4px;
    padding-right: 48px;
}
.available-scroll::-webkit-scrollbar { height: 4px; }
.available-scroll::-webkit-scrollbar-track { background: var(--bg-primary); border-radius: 2px; }
.available-scroll::-webkit-scrollbar-thumb { background: var(--gold-border); border-radius: 2px; }
.available-scroll::-webkit-scrollbar-thumb:hover { background: var(--gold); }

/* Drag hint */
.drag-hint {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 10px;
    color: var(--text-muted);
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-bottom: 16px;
    opacity: 0.6;
    transition: opacity 0.3s ease;
}
.drag-hint i {
    font-size: 12px;
    animation: swipeHint 2s ease-in-out infinite;
}
@keyframes swipeHint {
    0%, 100% { transform: translateX(0); }
    50% { transform: translateX(6px); }
}

/* Barber Card - Clean Premium */
.barber-card {
    flex: 0 0 280px;
    background: var(--bg-secondary);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    transition: var(--transition);
    scroll-snap-align: start;
    position: relative;
    cursor: pointer;
    text-decoration: none;
    color: var(--text-primary);
    display: block;
    flex-shrink: 0;
}
.barber-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--gold);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.5s cubic-bezier(0.25,0.46,0.45,0.94);
    z-index: 3;
}
.barber-card:hover {
    transform: translateY(-8px);
    border-color: var(--gold-border);
    background: var(--bg-hover);
    box-shadow: 0 24px 64px rgba(0,0,0,0.6);
}
.barber-card:hover::before { transform: scaleX(1); }

/* Card Top - Image Area */
.barber-card-top {
    position: relative;
    height: 280px;
    overflow: hidden;
    background: var(--bg-card);
}
.barber-card-top img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: grayscale(40%) contrast(1.05);
    transition: all 0.6s cubic-bezier(0.25,0.46,0.45,0.94);
    transform: scale(1);
}
.barber-card:hover .barber-card-top img {
    filter: grayscale(0%) contrast(1.1);
    transform: scale(1.08);
}
.barber-card-top-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, var(--bg-secondary) 0%, transparent 50%, rgba(5,5,7,0.3) 100%);
    z-index: 1;
}

/* Card Bottom - Info Area */
.barber-card-bottom {
    padding: 24px;
    position: relative;
}
.barber-card-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 26px;
    font-weight: 400;
    margin-bottom: 10px;
    letter-spacing: -0.5px;
    line-height: 1.2;
}
.barber-card-role {
    font-size: 12px;
    color: var(--text-muted);
    line-height: 1.7;
    font-weight: 500;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-clamp: 2;
}

/* Book Action - Minimal */
.barber-card-action {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 14px 20px;
    margin-top: 20px;
    background: transparent;
    color: var(--text-secondary);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    font-family: 'Montserrat', sans-serif;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    cursor: pointer;
    text-decoration: none;
}
.barber-card-action::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold);
    transform: translateX(-100%);
    transition: transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
    z-index: 0;
}
.barber-card-action:hover {
    color: var(--bg-primary);
    border-color: var(--gold);
}
.barber-card-action:hover::before { transform: translateX(0); }
.barber-card-action span, .barber-card-action i { position: relative; z-index: 1; }
.barber-card-action i { 
    transition: transform 0.3s ease; 
    font-size: 11px; 
    color: var(--gold);
}
.barber-card-action:hover i { 
    transform: translateX(4px); 
    color: var(--bg-primary);
}

/* Fully Booked State */
.barber-card.fully-booked {
    cursor: not-allowed;
    opacity: 0.5;
    filter: grayscale(0.6);
}
.barber-card.fully-booked:hover {
    transform: none;
    border-color: var(--border);
    background: var(--bg-secondary);
    box-shadow: none;
}
.barber-card.fully-booked::before {
    display: none;
}
.barber-card.fully-booked .barber-card-action {
    background: rgba(232, 132, 132, 0.08);
    border-color: rgba(232, 132, 132, 0.25);
    color: var(--danger);
    cursor: not-allowed;
}
.barber-card.fully-booked .barber-card-action::before {
    display: none;
}
.barber-card.fully-booked .barber-card-action i {
    color: var(--danger);
}
.barber-card.fully-booked:hover .barber-card-action {
    color: var(--danger);
    border-color: rgba(232, 132, 132, 0.25);
}

/* No Barber State */
.available-empty {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-muted);
    width: 100%;
}
.available-empty i {
    font-size: 40px;
    margin-bottom: 16px;
    opacity: 0.5;
    color: var(--gold);
}
.available-empty p {
    font-size: 14px;
    line-height: 1.7;
}

/* ===== MODAL - SELECT TIME & PACKAGE ===== */
.booking-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(5,5,7,0.92);
    backdrop-filter: blur(20px);
    z-index: 99998;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}
.booking-modal-overlay.active {
    opacity: 1;
    visibility: visible;
}
.booking-modal {
    background: var(--bg-secondary);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    max-width: 420px;
    width: 100%;
    position: relative;
    overflow: hidden;
    transform: scale(0.95) translateY(20px);
    transition: all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}
.booking-modal-overlay.active .booking-modal {
    transform: scale(1) translateY(0);
}
.booking-modal::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--gold);
}
.booking-modal-header {
    padding: 32px 32px 0;
    text-align: center;
}
.booking-modal-avatar {
    width: 80px; height: 80px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid var(--gold-border);
    margin: 0 auto 16px;
    background: var(--bg-card);
}
.booking-modal-avatar img {
    width: 100%; height: 100%;
    object-fit: cover;
}
.booking-modal-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px;
    font-weight: 400;
    margin-bottom: 4px;
}
.booking-modal-sub {
    font-size: 12px;
    color: var(--text-muted);
    font-weight: 500;
}
.booking-modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 36px; height: 36px;
    border-radius: 50%;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
    font-size: 14px;
}
.booking-modal-close:hover {
    border-color: var(--gold-border);
    color: var(--gold);
    background: var(--gold-dim);
}
.booking-modal-body {
    padding: 28px 32px 32px;
}
.modal-form-group {
    margin-bottom: 20px;
}
.modal-form-label {
    display: block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 10px;
}
.modal-select {
    width: 100%;
    padding: 14px 16px;
    background: var(--bg-input);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    color: var(--text-primary);
    font-family: 'Montserrat', sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239e998f' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 16px center;
    padding-right: 44px;
}
.modal-select:focus {
    outline: none;
    border-color: var(--gold-border);
    box-shadow: 0 0 0 4px var(--gold-dim);
}
.modal-select option {
    background: var(--bg-secondary);
    color: var(--text-primary);
    padding: 10px;
}
.modal-select option:disabled {
    color: var(--text-dim);
    text-decoration: line-through;
    opacity: 0.5;
}
.modal-submit-btn {
    width: 100%;
    padding: 16px;
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
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 8px;
}
.modal-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(232,200,122,0.3);
}
.modal-submit-btn i { transition: transform 0.3s ease; }
.modal-submit-btn:hover i { transform: translateX(3px); }

/* ===== HISTORY SECTION ===== */
.history-section { margin-bottom: 56px; }
.history-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.history-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 18px 24px;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    transition: var(--transition);
    text-decoration: none;
    color: var(--text-primary);
}
.history-item:hover {
    border-color: var(--gold-border);
    transform: translateX(6px);
    background: var(--bg-hover);
}
.history-icon {
    width: 44px; height: 44px;
    border-radius: var(--radius);
    background: var(--bg-hover);
    border: 1.5px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: var(--gold);
    flex-shrink: 0;
    transition: var(--transition);
}
.history-item:hover .history-icon {
    border-color: var(--gold-border);
    background: var(--gold-dim);
}
.history-info { flex: 1; }
.history-barber {
    font-family: 'Cormorant Garamond', serif;
    font-size: 18px;
    font-weight: 400;
    margin-bottom: 4px;
    letter-spacing: -0.5px;
}
.history-meta {
    font-size: 12px;
    color: var(--text-muted);
    font-weight: 500;
    letter-spacing: 0.5px;
}
.history-status {
    padding: 5px 14px;
    border-radius: var(--radius);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}
.history-status.paid {
    background: rgba(110, 231, 160, 0.08);
    border: 1.5px solid rgba(110, 231, 160, 0.25);
    color: var(--success);
}
.history-status.pending {
    background: rgba(232, 200, 122, 0.08);
    border: 1.5px solid rgba(232, 200, 122, 0.25);
    color: var(--warning);
}
.history-status.expired {
    background: rgba(232, 132, 132, 0.08);
    border: 1.5px solid rgba(232, 132, 132, 0.25);
    color: var(--danger);
}
.history-arrow {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: var(--bg-hover);
    border: 1.5px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    font-size: 11px;
    transition: var(--transition);
    flex-shrink: 0;
}
.history-item:hover .history-arrow {
    background: var(--gold);
    color: var(--bg-primary);
    border-color: var(--gold);
    transform: translateX(4px);
}

/* ===== FOOTER ===== */
.footer {
    border-top: 1.5px solid var(--border);
    padding: 48px 0 0;
    margin-top: 8px;
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
.footer-social { display: flex; gap: 12px; }
.footer-social-link {
    width: 40px; height: 40px;
    border: 1.5px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    font-size: 14px;
    text-decoration: none;
    transition: var(--transition);
}
.footer-social-link:hover {
    border-color: var(--gold-border);
    color: var(--gold);
    background: var(--gold-dim);
}

/* ===== ANIMATIONS ===== */
@keyframes pulse {
    0%,100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.8); }
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.booking-cta { animation: fadeUp 0.6s ease forwards; opacity: 0; }
.dashboard-grid { animation: fadeUp 0.6s ease 0.15s forwards; opacity: 0; }
.available-section { animation: fadeUp 0.6s ease 0.3s forwards; opacity: 0; }
.history-section { animation: fadeUp 0.6s ease 0.45s forwards; opacity: 0; }

/* ===== SCROLLBAR ===== */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--bg-primary); }
::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: var(--gold-border); }

/* ===== RESPONSIVE ===== */
@media(max-width:900px) {
    .dashboard-grid { grid-template-columns: 1fr; }
    .booking-cta-card { flex-direction: column; text-align: center; }
    .cta-action-side { margin-top: 24px; }
    .welcome-title { font-size: 32px; }
    .dashboard-main { padding: 120px 24px 60px; }
    .barber-card { flex: 0 0 260px; }
}
@media(max-width:600px) {
    .upcoming-content { flex-direction: column; align-items: center; text-align: center; }
    .upcoming-meta { justify-content: center; }
    .history-item { flex-wrap: wrap; }
    .history-arrow { margin-left: auto; }
    .footer-inner { flex-direction: column; text-align: center; }
    .barber-card { flex: 0 0 85vw; }
    .barber-card-top { height: 340px; }
    .available-scroll { padding-right: 24px; }
}
</style>
</head>
<body>
<!-- Custom Cursor -->
<div class="cursor" id="cursor"></div>
<div class="cursor-follower" id="cursorFollower"></div>

<?php if($showRatingPopup): ?>
<div class="rating-overlay" id="ratingOverlay">
    <div class="rating-popup">
        <div class="rating-popup-icon"><i class="fas fa-star"></i></div>
        <h2>How was your experience about our system?</h2>
        <p>You have completed your first booking! Please rate your overall experience for using the website at Hostel Barbershop before continuing.</p>
        <form method="POST" action="save_barbershop_rating.php" id="ratingForm">
            <input type="hidden" name="user_id" value="<?= $userId ?>">
            <input type="hidden" name="rating" id="popupRatingValue" value="0">
            <div class="rating-popup-stars">
                <button type="button" class="popup-star" data-value="1" onclick="setPopupRating(1)"><i class="fas fa-star"></i></button>
                <button type="button" class="popup-star" data-value="2" onclick="setPopupRating(2)"><i class="fas fa-star"></i></button>
                <button type="button" class="popup-star" data-value="3" onclick="setPopupRating(3)"><i class="fas fa-star"></i></button>
                <button type="button" class="popup-star" data-value="4" onclick="setPopupRating(4)"><i class="fas fa-star"></i></button>
                <button type="button" class="popup-star" data-value="5" onclick="setPopupRating(5)"><i class="fas fa-star"></i></button>
            </div>
            <textarea name="review" class="rating-popup-textarea" placeholder="Tell us about your experience (optional)..."></textarea>
            <button type="submit" class="rating-popup-submit" id="ratingSubmitBtn" disabled>
                <span>Submit Rating</span> <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- BOOKING MODAL -->
<div class="booking-modal-overlay" id="bookingModal">
    <div class="booking-modal">
        <button class="booking-modal-close" onclick="closeBookingModal()"><i class="fas fa-times"></i></button>
        <div class="booking-modal-header">
            <div class="booking-modal-avatar" id="modalAvatar">
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:28px;"><i class="fas fa-user"></i></div>
            </div>
            <div class="booking-modal-name" id="modalBarberName">Select Barber</div>
            <div class="booking-modal-sub" id="modalBarberRole">Choose your preferred time and package</div>
        </div>
        <div class="booking-modal-body">
            <form id="modalBookingForm" action="booking.php" method="GET">
                <input type="hidden" name="barber" id="modalBarberId" value="">
                <input type="hidden" name="tanggal" id="modalTanggal" value="<?= $today ?>">

                <div class="modal-form-group">
                    <label class="modal-form-label"><i class="far fa-clock" style="margin-right:6px;"></i>Select Time</label>
                    <select name="jam" class="modal-select" id="modalJam" required>
                        <option value="" disabled selected>-- choose time --</option>
                    </select>
                </div>

                <div class="modal-form-group">
                    <label class="modal-form-label"><i class="fas fa-cut" style="margin-right:6px;"></i>Select Package</label>
                    <select name="paket" class="modal-select" id="modalPaket" required>
                        <option value="" disabled selected>-- choose package --</option>
                        <?php foreach($paketList as $paket): ?>
                        <option value="<?= $paket['id'] ?>"><?= $paket['nama_paket'] ?> &mdash; Rp<?= number_format($paket['harga'], 0, ',', '.') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="modal-submit-btn">
                    <span>Continue Booking</span> <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<nav class="nav-wrapper" id="mainNav">
    <div class="nav-inner">
        <div class="nav-left-group">
            <a href="dashboard.php" class="nav-logo">
                <img src="hostel.png" alt="Hostel Barbershop" class="nav-logo-img">
            </a>
            <div class="nav-links" id="navLinks">
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
            </div>
        </div>
        <div class="nav-profile-wrapper" id="navProfileWrapper">
            <div class="nav-profile-trigger" id="navProfileTrigger">
                <?php if(!empty($userPhoto) && file_exists($userPhoto)): ?>
                <img src="<?= $userPhoto ?>" alt="<?= htmlspecialchars($username) ?>" class="nav-profile-avatar" onerror="this.style.display='none'; this.parentNode.innerHTML='<div class=nav-profile-avatar-initial><?= strtoupper(substr($username,0,1)) ?></div>';">
                <?php else: ?>
                <div class="nav-profile-avatar-initial"><?= strtoupper(substr($username,0,1)) ?></div>
                <?php endif; ?>
                <span><?= htmlspecialchars($username) ?></span>
                <i class="fas fa-chevron-down" style="font-size:9px; margin-left:4px;"></i>
            </div>
            <div class="nav-profile-dropdown" id="navProfileDropdown">
                <a href="profile.php" class="dropdown-item"><i class="fas fa-user"></i> <span>Profile</span></a>
                <a href="settings.php" class="dropdown-item"><i class="fas fa-cog"></i> <span>Settings</span></a>
                <div style="height:1px; background:var(--border); margin:4px 0;"></div>
                <a href="logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
        <button class="nav-mobile-toggle" id="mobileToggle" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
    </div>
    <div class="scroll-line" id="scrollLine"></div>
</nav>

<main class="dashboard-main">

    <!-- WELCOME SECTION -->
    <div class="welcome-section">
        <div class="welcome-left">
            <div class="welcome-label">
                <div class="welcome-label-line"></div>
                <span class="welcome-label-text">Dashboard</span>
            </div>
            <h1 class="welcome-title">Welcome,<br><em><?= htmlspecialchars($username) ?></em></h1>
        </div>
        <div class="welcome-right">
            <p class="welcome-desc">Take your time, Hostel is yours baby.</p>
        </div>
    </div>

    <!-- BOOKING CTA -->
    <div class="booking-cta">
        <a href="booking.php" class="booking-cta-card">
            <div class="cta-visual"></div>
            <div class="cta-content">
                <div class="cta-badge"><i class="fas fa-bolt"></i> Instant Booking</div>
                <div class="cta-title">Book Your Cut Now</div>
                <div class="cta-desc">Choose your barber, pick a time slot, and get fresh. No hassle, no waiting in line.</div>
            </div>
            <div class="cta-action-side">
                <div class="cta-btn-main"><i class="fas fa-arrow-right"></i></div>
                <div class="cta-hint">Tap to Start<span>Takes under 30 seconds</span></div>
            </div>
        </a>
    </div>

    <!-- DASHBOARD GRID -->
    <div class="dashboard-grid">

        <!-- UPCOMING BOOKING -->
        <div class="section-card">
            <div class="section-header">
                <span class="section-header-title">Upcoming</span>
                <a href="history.php" class="btn-animated"><span>View All</span> <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php if($upcoming): ?>
            <div class="upcoming-content">
                <div class="upcoming-avatar">
                    <?php if(!empty($upcoming['barber_foto'])): ?>
                    <img src="admin/upload/<?= $upcoming['barber_foto'] ?>" alt="<?= $upcoming['barber_name'] ?>">
                    <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:24px;"><i class="fas fa-user"></i></div>
                    <?php endif; ?>
                </div>
                <div class="upcoming-info">
                    <div class="upcoming-barber"><?= $upcoming['barber_name'] ?? 'Unknown Barber' ?></div>
                    <div class="upcoming-package"><?= $upcoming['nama_paket'] ?? 'Unknown Package' ?></div>
                    <div class="upcoming-meta">
                        <span class="meta-item"><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($upcoming['tanggal'])) ?></span>
                        <span class="meta-item"><i class="fas fa-clock"></i> <?= substr($upcoming['jam'],0,5) ?></span>
                        <span class="status-tag <?= $upcoming['status'] ?>"><?= $upcoming['status'] ?></span>
                    </div>
                    <?php if($upcoming['status'] === 'pending'): ?>
                    <a href="payment.php?id=<?= $upcoming['id'] ?>" class="upcoming-action"><i class="fas fa-credit-card"></i> <span>Payment Now</span></a>
                    <?php else: ?>
                    <a href="booking_detail.php?id=<?= $upcoming['id'] ?>" class="upcoming-action"><i class="fas fa-eye"></i> <span>View Details</span></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="upcoming-empty">
                <i class="fas fa-calendar-times"></i>
                <p>No upcoming bookings.<br><a href="booking.php">Book your first cut now &rarr;</a></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- QUICK AVAILABLE SLOT -->
        <div class="section-card">
            <div class="section-header">
                <span class="section-header-title">Next Slot</span>
                <span style="font-size:12px; color:var(--text-muted);"><?= date('l, d M Y') ?></span>
            </div>
            <?php if($nextSlot): ?>
            <div class="quick-slot-header"><i class="fas fa-circle"></i><span>Available Now</span></div>
            <div class="quick-slot-time"><?= $nextSlot['time'] ?></div>
            <div class="quick-slot-barber">with <strong><?= $nextSlot['barber']['nama'] ?></strong></div>
            <a href="booking.php?barber=<?= $nextSlot['barber']['id'] ?>&tanggal=<?= $today ?>&jam=<?= $nextSlot['time'] ?>" class="quick-slot-btn"><i class="fas fa-bolt"></i> <span>Book This Slot</span></a>
            <?php else: ?>
            <div class="quick-slot-empty">
                <i class="fas fa-calendar-times"></i>
                <p>No slots available for today</p>
                <a href="booking.php" class="btn-animated"><span>Browse other days</span> <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- AVAILABLE BARBERS TODAY -->
    <div class="section-card available-section">
        <div class="section-header">
            <span class="section-header-title">On Duty Today</span>
            <span style="font-size:12px; color:var(--text-muted);"><?= date('l, d M Y') ?></span>
        </div>
        <div class="drag-hint"><i class="fas fa-arrows-left-right"></i> Scroll to explore all barbers</div>
        <div class="available-scroll" id="barberScroll">
            <?php 
            $hasAvailable = false;
            while($avail = mysqli_fetch_assoc($qAvailableToday)): 
                $hasAvailable = true;
                $isFullyBooked = isBarberFullyBooked($conn, $avail['id'], $today, $jamData, $booked);
            ?>
            <div class="barber-card <?= $isFullyBooked ? 'fully-booked' : '' ?>" 
                 <?= !$isFullyBooked ? 'onclick="openBookingModal('.$avail['id'].', \''.addslashes($avail['nama']).'\', \''.addslashes($avail['keterangan']).'\', \''.$avail['foto'].'\');"' : '' ?>>
                <div class="barber-card-top">
                    <?php if(!empty($avail['foto'])): ?>
                    <img src="admin/upload/<?= $avail['foto'] ?>" alt="<?= $avail['nama'] ?>">
                    <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:48px;"><i class="fas fa-user"></i></div>
                    <?php endif; ?>
                    <div class="barber-card-top-overlay"></div>
                </div>
                <div class="barber-card-bottom">
                    <div class="barber-card-name"><?= $avail['nama'] ?></div>
                    <div class="barber-card-role"><?= $avail['keterangan'] ?></div>
                    <?php if($isFullyBooked): ?>
                    <span class="barber-card-action"><span>Fully Booked</span> <i class="fas fa-ban"></i></span>
                    <?php else: ?>
                    <span class="barber-card-action"><span>Book Now</span> <i class="fas fa-arrow-right"></i></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
            <?php if(!$hasAvailable): ?>
            <div class="available-empty">
                <i class="fas fa-store-slash"></i>
                <p>No barbers available today</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- HISTORY SECTION -->
    <div class="section-card history-section">
        <div class="section-header">
            <span class="section-header-title">Recent History</span>
            <a href="history.php" class="btn-animated"><span>View All</span> <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="history-list">
            <?php 
            $hasHistory = false;
            while($h = mysqli_fetch_assoc($qHistory)): 
                $hasHistory = true;
                $statusClass = $h['status'];
            ?>
            <a href="booking_detail.php?id=<?= $h['id'] ?>" class="history-item">
                <div class="history-icon"><i class="fas fa-cut"></i></div>
                <div class="history-info">
                    <div class="history-barber"><?= $h['barber_name'] ?? 'Unknown' ?> &mdash; <?= $h['nama_paket'] ?? 'Unknown' ?></div>
                    <div class="history-meta"><?= date('d M Y', strtotime($h['tanggal'])) ?> &bull; <?= substr($h['jam'],0,5) ?></div>
                </div>
                <span class="history-status <?= $statusClass ?>"><?= $h['status'] ?></span>
                <div class="history-arrow"><i class="fas fa-arrow-right"></i></div>
            </a>
            <?php endwhile; ?>
            <?php if(!$hasHistory): ?>
            <div class="upcoming-empty" style="padding:24px;">
                <i class="fas fa-history" style="font-size:32px; margin-bottom:12px; opacity:0.3;"></i>
                <p>No booking history yet.<br>Start by booking your first cut!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <div class="footer-inner">
            <div class="footer-copyright">&copy; <?= date('Y') ?> <span>Hostel Barbershop</span> &mdash; Precision in every cut</div>
            <div class="footer-social">
                <a href="#" class="footer-social-link" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" class="footer-social-link" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                <a href="#" class="footer-social-link" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
    </div>

</main>

<!-- DYNAMIC DATA FOR MODAL -->
<script>
const jamList = <?= json_encode($jamList) ?>;
const todayStr = "<?= $today ?>";
const currentHourStr = "<?= $currentHour ?>";
const bookedSlots = <?= json_encode($booked) ?>;
const isToday = (todayStr === "<?= date('Y-m-d') ?>");
</script>
<script>
const mainNav = document.getElementById('mainNav');
const scrollLine = document.getElementById('scrollLine');

window.addEventListener('scroll', () => {
    const scrollY = window.pageYOffset;
    const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const pct = scrollHeight > 0 ? (scrollY / scrollHeight) * 100 : 0;
    scrollLine.style.width = pct + '%';
    mainNav.classList.toggle('scrolled', scrollY > 60);
}, { passive: true });

const mobileToggle = document.getElementById('mobileToggle');
const navLinks = document.getElementById('navLinks');
mobileToggle.addEventListener('click', () => {
    navLinks.classList.toggle('show');
    const icon = mobileToggle.querySelector('i');
    icon.className = navLinks.classList.contains('show') ? 'fas fa-times' : 'fas fa-bars';
});
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        navLinks.classList.remove('show');
        mobileToggle.querySelector('i').className = 'fas fa-bars';
    });
});

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

<?php if($showRatingPopup): ?>
let popupRating = 0;
function setPopupRating(value) {
    popupRating = value;
    document.getElementById('popupRatingValue').value = value;
    const stars = document.querySelectorAll('.popup-star');
    stars.forEach((star, index) => {
        if (index < value) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
    const submitBtn = document.getElementById('ratingSubmitBtn');
    if (value > 0) {
        submitBtn.disabled = false;
    }
}
document.getElementById('ratingOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        const popup = document.querySelector('.rating-popup');
        popup.style.animation = 'none';
        setTimeout(() => {
            popup.style.animation = 'popupIn 0.5s cubic-bezier(0.25,0.46,0.45,0.94)';
        }, 10);
    }
});
<?php endif; ?>

/* ===== BOOKING MODAL FUNCTIONS ===== */
function openBookingModal(barberId, barberName, barberRole, barberFoto) {
    document.getElementById('modalBarberId').value = barberId;
    document.getElementById('modalBarberName').textContent = barberName;
    document.getElementById('modalBarberRole').textContent = barberRole || 'Choose your preferred time and package';

    const avatarDiv = document.getElementById('modalAvatar');
    if (barberFoto) {
        avatarDiv.innerHTML = '<img src="admin/upload/' + barberFoto + '" alt="' + barberName + '">';
    } else {
        avatarDiv.innerHTML = '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:28px;"><i class="fas fa-user"></i></div>';
    }

    // Dynamically populate time slots based on selected barber
    const jamSelect = document.getElementById('modalJam');
    jamSelect.innerHTML = '<option value="" disabled selected>-- choose time --</option>';

    jamList.forEach(function(jam) {
        const key = barberId + '_' + todayStr + '_' + jam;
        const isBooked = bookedSlots.indexOf(key) !== -1;
        const isPassed = isToday && jam < currentHourStr;

        if (!isBooked && !isPassed) {
            const option = document.createElement('option');
            option.value = jam;
            option.textContent = jam;
            jamSelect.appendChild(option);
        }
    });

    document.getElementById('modalJam').value = '';
    document.getElementById('modalPaket').value = '';

    document.getElementById('bookingModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeBookingModal() {
    document.getElementById('bookingModal').classList.remove('active');
    document.body.style.overflow = '';
}

document.getElementById('bookingModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeBookingModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeBookingModal();
    }
});

// ===== CUSTOM CURSOR =====
const cursor = document.getElementById('cursor');
const cursorFollower = document.getElementById('cursorFollower');
let mx = 0, my = 0, fx = 0, fy = 0;
let cursorActive = false;

document.addEventListener('mousemove', e => {
    mx = e.clientX;
    my = e.clientY;
    cursorActive = true;
}, { passive: true });

function updateCursor() {
    if (cursorActive) {
        cursor.style.transform = `translate3d(${mx}px, ${my}px, 0) translate(-50%, -50%)`;
        fx += (mx - fx) * 0.15;
        fy += (my - fy) * 0.15;
        cursorFollower.style.transform = `translate3d(${fx}px, ${fy}px, 0) translate(-50%, -50%)`;
    }
    requestAnimationFrame(updateCursor);
}
requestAnimationFrame(updateCursor);

document.querySelectorAll('a, button, .barber-card, .history-item, .upcoming-action, .cta-btn-main, .quick-slot-btn, .btn-animated, .dropdown-item, .nav-profile-trigger, .nav-mobile-toggle, .footer-social-link').forEach(el => {
    el.addEventListener('mouseenter', () => {
        cursor.classList.add('hover');
        cursorFollower.classList.add('hover');
    }, { passive: true });
    el.addEventListener('mouseleave', () => {
        cursor.classList.remove('hover');
        cursorFollower.classList.remove('hover');
    }, { passive: true });
});

// ===== SMOOTH WHEEL HORIZONTAL SCROLL (BARBER CARDS) =====
const barberScroll = document.getElementById('barberScroll');
if (barberScroll) {
    barberScroll.addEventListener('wheel', (e) => {
        if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
            e.preventDefault();
            // Use native smooth scroll behavior
            barberScroll.scrollBy({
                left: e.deltaY * 0.8,
                behavior: 'smooth'
            });
        }
    }, { passive: false });
}

const modalForm = document.getElementById('modalBookingForm');
if (modalForm) {
    modalForm.addEventListener('submit', function(e) {
        const jam = document.getElementById('modalJam').value;
        const paket = document.getElementById('modalPaket').value;

        if (!jam || !paket) {
            e.preventDefault();
            const modal = document.querySelector('.booking-modal');
            modal.style.animation = 'none';
            setTimeout(() => {
                modal.style.animation = 'popupIn 0.3s ease';
            }, 10);
            return false;
        }
    });
}
</script>

</body>
</html>