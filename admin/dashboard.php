<?php
/**
 * @var mysqli $conn Database connection from config.php
 */
include '../config.php';
global $conn;

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

// SET TIMEZONE JAKARTA
date_default_timezone_set('Asia/Jakarta');

// AUTO EXPIRE — pending lebih dari 30 detik jadi expired
mysqli_query($conn,"
UPDATE booking
SET status='expired'
WHERE status='pending'
AND TIMESTAMPDIFF(SECOND, created_at, NOW()) > 30
");

$today = date('Y-m-d');
$currentMonth = date('m');
$currentYear = date('Y');
$currentHour = date('H:i');

// ==================== STATS ====================

// TOTAL USER
$user = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total FROM users WHERE status='active'
"));

// TOTAL BARBER
$barber = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total FROM barber
"));

// TOTAL PAKET
$paket = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total FROM paket
"));

// BOOKING STATUS BREAKDOWN
$bookingPaid = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total FROM booking WHERE status='paid'
"));
$bookingPending = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total FROM booking WHERE status='pending'
"));
$bookingExpired = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total FROM booking WHERE status='expired'
"));
$totalBookings = ($bookingPaid['total'] ?? 0);

// TOTAL PENDAPATAN
$income = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT SUM(paket.harga) as total_income
FROM booking
JOIN paket ON booking.paket_id = paket.id
WHERE booking.status='paid'
"));
$totalIncome = $income['total_income'] ?? 0;

// PENDAPATAN BULAN INI — BENAR: pakai tanggal appointment
$monthIncome = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT SUM(paket.harga) as total
FROM booking
JOIN paket ON booking.paket_id = paket.id
WHERE booking.status='paid'
AND MONTH(booking.tanggal)='$currentMonth'
AND YEAR(booking.tanggal)='$currentYear'
"));
$totalMonthIncome = $monthIncome['total'] ?? 0;

// PENDAPATAN HARI INI
$todayIncome = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT SUM(paket.harga) as total
FROM booking
JOIN paket ON booking.paket_id = paket.id
WHERE booking.status='paid'
AND booking.tanggal='$today'
"));
$totalTodayIncome = $todayIncome['total'] ?? 0;

// ==================== ON-DUTY TODAY ====================

$onDutyBarbers = [];
$qOnDuty = mysqli_query($conn,"
SELECT barber.id, barber.nama, barber.foto
FROM jadwal_barber
JOIN barber ON jadwal_barber.barber_id = barber.id
WHERE jadwal_barber.tanggal = '$today'
ORDER BY barber.nama ASC
");
while($b = mysqli_fetch_assoc($qOnDuty)){
    $onDutyBarbers[] = $b;
}

// TODAY'S BOOKINGS BY BARBER
$todayBarberBookings = [];
$qTodayBookings = mysqli_query($conn,"
SELECT barber_id, COUNT(*) as total
FROM booking
WHERE tanggal = '$today' AND status = 'paid'
GROUP BY barber_id
");
while($tb = mysqli_fetch_assoc($qTodayBookings)){
    $todayBarberBookings[$tb['barber_id']] = $tb['total'];
}

// BOOKED SLOTS BY BARBER
$bookedSlotsByBarber = [];
$qBookedSlots = mysqli_query($conn,"
SELECT barber_id, jam
FROM booking
WHERE tanggal = '$today' AND status IN ('paid','pending')
");
while($bs = mysqli_fetch_assoc($qBookedSlots)){
    if(!isset($bookedSlotsByBarber[$bs['barber_id']])){
        $bookedSlotsByBarber[$bs['barber_id']] = [];
    }
    $bookedSlotsByBarber[$bs['barber_id']][] = $bs['jam'];
}

// JAM OPERASIONAL
$jamList = [];
$qJam = mysqli_query($conn,"SELECT jam_buka FROM jam_operasional WHERE status='Buka' ORDER BY jam_buka ASC");
while($j = mysqli_fetch_assoc($qJam)){
    $jamList[] = substr($j['jam_buka'], 0, 5);
}

// ==================== BOOKINGS ====================

// BOOKING TERBARU
$latestBooking = mysqli_query($conn,"
SELECT booking.*, users.username, users.photo as user_photo, barber.nama as barber_nama, paket.nama_paket
FROM booking
JOIN users ON booking.user_id = users.id
JOIN barber ON booking.barber_id = barber.id
JOIN paket ON booking.paket_id = paket.id
WHERE booking.status='paid'
ORDER BY booking.id DESC
LIMIT 6
");

// ==================== BARBER STATS ====================

$barberStats = mysqli_query($conn,"
SELECT
barber.id,
barber.nama,
barber.foto,
COUNT(CASE WHEN booking.status='paid' THEN booking.id END) as total_booking,
SUM(CASE WHEN booking.status='paid' THEN paket.harga ELSE 0 END) as total_income,
ROUND(((barber.skill_fade + barber.skill_scissoring + barber.skill_longcut + barber.skill_shortcut + barber.skill_beardcut) / 5), 0) as avg_skill
FROM barber
LEFT JOIN booking ON barber.id = booking.barber_id
LEFT JOIN paket ON booking.paket_id = paket.id
GROUP BY barber.id
ORDER BY total_income DESC
");

// ==================== BARBERSHOP RATING ====================

$ratingStats = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT 
    COUNT(*) as total_reviews,
    ROUND(AVG(rating), 1) as avg_rating,
    COUNT(CASE WHEN rating = 5 THEN 1 END) as star5,
    COUNT(CASE WHEN rating = 4 THEN 1 END) as star4,
    COUNT(CASE WHEN rating = 3 THEN 1 END) as star3,
    COUNT(CASE WHEN rating = 2 THEN 1 END) as star2,
    COUNT(CASE WHEN rating = 1 THEN 1 END) as star1
FROM barbershop_rating
"));

$totalReviews = $ratingStats['total_reviews'] ?? 0;
$avgRating = $ratingStats['avg_rating'] ?? 0;
$star5 = $ratingStats['star5'] ?? 0;
$star4 = $ratingStats['star4'] ?? 0;
$star3 = $ratingStats['star3'] ?? 0;
$star2 = $ratingStats['star2'] ?? 0;
$star1 = $ratingStats['star1'] ?? 0;

// REVIEWS TERBARU (cuma 2 untuk dashboard)
$latestReviews = mysqli_query($conn,"
SELECT 
    br.*,
    u.username,
    u.photo as user_photo
FROM barbershop_rating br
JOIN users u ON br.user_id = u.id
ORDER BY br.created_at DESC
LIMIT 2
");

// ==================== RECENT ACTIVITY LOG ====================

$activityLog = [];

// Helper: track booking activities by booking ID to merge pending->expired
$bookingActivities = [];

// 1. NEW BOOKINGS TODAY (paid & pending)
$qNewBookings = mysqli_query($conn,"
SELECT 
    b.id,
    b.status,
    b.created_at,
    u.username,
    u.photo as user_photo,
    bar.nama as barber_nama,
    p.nama_paket,
    p.harga
FROM booking b
JOIN users u ON b.user_id = u.id
JOIN barber bar ON b.barber_id = bar.id
JOIN paket p ON b.paket_id = p.id
WHERE DATE(b.created_at) = '$today'
ORDER BY b.created_at DESC
LIMIT 15
");
while($row = mysqli_fetch_assoc($qNewBookings)){
    $type = $row['status'] === 'paid' ? 'booking_paid' : 'booking_pending';
    $bookingActivities[$row['id']] = [
        'type' => $type,
        'time' => $row['created_at'],
        'user' => $row['username'],
        'user_photo' => $row['user_photo'],
        'barber' => $row['barber_nama'],
        'package' => $row['nama_paket'],
        'amount' => $row['harga'],
        'id' => $row['id']
    ];
}

// 2. BOOKINGS THAT EXPIRED TODAY — overwrite pending if same booking_id
$qExpiredToday = mysqli_query($conn,"
SELECT 
    b.id,
    b.created_at,
    u.username,
    u.photo as user_photo,
    bar.nama as barber_nama,
    p.nama_paket
FROM booking b
JOIN users u ON b.user_id = u.id
JOIN barber bar ON b.barber_id = bar.id
JOIN paket p ON b.paket_id = p.id
WHERE b.status = 'expired' 
AND DATE(b.created_at) = '$today'
ORDER BY b.created_at DESC
LIMIT 15
");
while($row = mysqli_fetch_assoc($qExpiredToday)){
    // If this booking was previously logged as pending, replace it with expired
    // Use the original created_at time (when booking was made) for sorting
    $bookingActivities[$row['id']] = [
        'type' => 'booking_expired',
        'time' => $row['created_at'],
        'user' => $row['username'],
        'user_photo' => $row['user_photo'],
        'barber' => $row['barber_nama'],
        'package' => $row['nama_paket'],
        'amount' => 0,
        'id' => $row['id']
    ];
}

// Add all booking activities to main log
foreach($bookingActivities as $act){
    $activityLog[] = $act;
}

// 3. NEW USERS REGISTERED TODAY
$qNewUsers = mysqli_query($conn,"
SELECT id, username, photo, created_at
FROM users
WHERE DATE(created_at) = '$today'
ORDER BY created_at DESC
LIMIT 5
");
while($row = mysqli_fetch_assoc($qNewUsers)){
    $activityLog[] = [
        'type' => 'user_registered',
        'time' => $row['created_at'],
        'user' => $row['username'],
        'user_photo' => $row['photo'],
        'barber' => '',
        'package' => '',
        'amount' => 0,
        'id' => $row['id']
    ];
}

// 4. NEW REVIEWS TODAY
$qNewReviews = mysqli_query($conn,"
SELECT 
    br.id,
    br.rating,
    br.review,
    br.created_at,
    u.username,
    u.photo as user_photo
FROM barbershop_rating br
JOIN users u ON br.user_id = u.id
WHERE DATE(br.created_at) = '$today'
ORDER BY br.created_at DESC
LIMIT 5
");
while($row = mysqli_fetch_assoc($qNewReviews)){
    $activityLog[] = [
        'type' => 'new_review',
        'time' => $row['created_at'],
        'user' => $row['username'],
        'user_photo' => $row['user_photo'],
        'barber' => '',
        'package' => '',
        'amount' => $row['rating'],
        'id' => $row['id'],
        'review_text' => $row['review']
    ];
}

// SORT ALL ACTIVITIES BY TIME DESC (newest first)
usort($activityLog, function($a, $b){
    return strtotime($b['time']) - strtotime($a['time']);
});

// TAKE TOP 11 — always show 11 newest, oldest drops when new comes in
$activityLog = array_slice($activityLog, 0, 11);

// ==================== CHART DATA ====================

$chartLabels = [];
$chartData = [];
for($i=6; $i>=0; $i--){
    $date = date('Y-m-d', strtotime("-$i day"));
    $dayLabel = date('D', strtotime($date));
    $chartLabels[] = $dayLabel;
    
    $dayIncome = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT SUM(paket.harga) as total
    FROM booking
    JOIN paket ON booking.paket_id = paket.id
    WHERE booking.status='paid'
    AND booking.tanggal='$date'
    "));
    $chartData[] = $dayIncome['total'] ?? 0;
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | Hostel Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
:root{
    --bg-primary:#0a0a0a;
    --bg-secondary:#111111;
    --bg-tertiary:#161616;
    --bg-card:#1a1a1a;
    --bg-hover:#1e1e1e;
    --bg-input:#141414;
    --border:#2a2a2a;
    --border-light:#3a3a3a;
    --border-focus:#555555;
    --text-primary:#ffffff;
    --text-secondary:#a0a0a0;
    --text-muted:#666666;
    --accent:#ffffff;
    --danger:#ff4444;
    --danger-soft:rgba(255,68,68,0.08);
    --danger-border:rgba(255,68,68,0.2);
    --success:#00ff88;
    --success-soft:rgba(0,255,136,0.08);
    --success-border:rgba(0,255,136,0.2);
    --warning:#ffaa00;
    --warning-soft:rgba(255,170,0,0.08);
    --warning-border:rgba(255,170,0,0.2);
    --info:#4488ff;
    --info-soft:rgba(68,136,255,0.08);
    --info-border:rgba(68,136,255,0.2);
    --gold:#ffd700;
    --gold-soft:rgba(255,215,0,0.08);
    --shadow:0 2px 12px rgba(0,0,0,0.4);
    --shadow-hover:0 4px 24px rgba(0,0,0,0.5);
    --radius:16px;
    --radius-sm:12px;
    --radius-xs:8px;
    --transition:all 0.25s cubic-bezier(0.4,0,0.2,1);
}

*{margin:0;padding:0;box-sizing:border-box;}

body{
    background:var(--bg-primary);
    color:var(--text-primary);
    font-family:'Inter',sans-serif;
    line-height:1.6;
    min-height:100vh;
    overflow-x:hidden;
}

.container{
    max-width:1400px;
    margin:0 auto;
    padding:40px 32px;
}

/* ===== HEADER ===== */
.page-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:32px;
    padding-bottom:24px;
    border-bottom:1px solid var(--border);
    position:relative;
}

.page-header::after{
    content:'';
    position:absolute;
    bottom:-1px;
    left:0;
    width:120px;
    height:1px;
    background:var(--text-primary);
}

.page-title h1{
    font-family:'Space Grotesk',sans-serif;
    font-size:32px;
    font-weight:700;
    letter-spacing:-1.5px;
    line-height:1.2;
    margin-bottom:6px;
}

.page-title p{
    color:var(--text-muted);
    font-size:14px;
    font-weight:400;
}

.header-actions{
    display:flex;
    align-items:center;
    gap:12px;
}

.live-badge{
    display:flex;
    align-items:center;
    gap:8px;
    padding:8px 16px;
    background:var(--bg-tertiary);
    border:1px solid var(--border);
    border-radius:100px;
    font-size:12px;
    font-weight:600;
    color:var(--text-muted);
}

.live-badge::before{
    content:'';
    width:6px;
    height:6px;
    background:var(--success);
    border-radius:50%;
    animation:pulse 2s infinite;
}

@keyframes pulse{
    0%,100%{opacity:1;transform:scale(1);}
    50%{opacity:0.4;transform:scale(0.7);}
}

.logout-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 18px;
    border-radius:var(--radius-xs);
    background:transparent;
    border:1px solid var(--danger-border);
    color:var(--danger);
    text-decoration:none;
    font-size:13px;
    font-weight:600;
    transition:var(--transition);
}

.logout-btn:hover{
    background:var(--danger);
    color:white;
    border-color:var(--danger);
}

/* ===== STATS STRIP ===== */
.stats-strip{
    display:grid;
    grid-template-columns:repeat(5, 1fr);
    gap:16px;
    margin-bottom:24px;
}

@media(max-width:1100px){
    .stats-strip{grid-template-columns:repeat(3,1fr);}
}
@media(max-width:700px){
    .stats-strip{grid-template-columns:repeat(2,1fr);}
}

.stat-item{
    padding:20px;
    background:var(--bg-secondary);
    border:1px solid var(--border);
    border-radius:var(--radius-sm);
    transition:var(--transition);
}

.stat-item:hover{
    border-color:var(--border-light);
    transform:translateY(-2px);
    box-shadow:var(--shadow-hover);
}

.stat-icon{
    width:40px;
    height:40px;
    border-radius:var(--radius-xs);
    background:var(--bg-tertiary);
    border:1px solid var(--border);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:16px;
    color:var(--text-muted);
    margin-bottom:16px;
    transition:var(--transition);
}

.stat-item:hover .stat-icon{
    background:var(--text-primary);
    color:var(--bg-primary);
    border-color:var(--text-primary);
}

.stat-item-value{
    font-family:'Space Grotesk',sans-serif;
    font-size:28px;
    font-weight:700;
    letter-spacing:-1px;
    margin-bottom:8px;
}

.stat-item-label{
    font-size:11px;
    color:var(--text-muted);
    font-weight:600;
    text-transform:uppercase;
    letter-spacing:1px;
    margin-bottom:10px;
}

.stat-item-sub{
    display:flex;
    align-items:center;
    gap:6px;
    font-size:12px;
    color:var(--text-muted);
    font-weight:500;
}

.stat-item-sub .dot{
    width:5px;
    height:5px;
    border-radius:50%;
    display:inline-block;
}

.dot-success{background:var(--success);}
.dot-warning{background:var(--warning);}
.dot-danger{background:var(--danger);}

/* ===== REVENUE COMPACT ===== */
.revenue-compact{
    display:grid;
    grid-template-columns:repeat(3, 1fr);
    gap:16px;
    margin-bottom:24px;
}

@media(max-width:900px){
    .revenue-compact{grid-template-columns:1fr;}
}

.rev-card{
    background:var(--bg-secondary);
    border:1px solid var(--border);
    border-radius:var(--radius-sm);
    padding:24px;
    transition:var(--transition);
    display:flex;
    align-items:center;
    justify-content:space-between;
}

.rev-card:hover{
    border-color:var(--border-light);
    box-shadow:var(--shadow-hover);
}

.rev-info{flex:1;}

.rev-title{
    font-size:11px;
    font-weight:600;
    color:var(--text-muted);
    text-transform:uppercase;
    letter-spacing:1px;
    margin-bottom:8px;
}

.rev-value{
    font-family:'Space Grotesk',sans-serif;
    font-size:24px;
    font-weight:700;
    letter-spacing:-0.5px;
}

.rev-icon{
    width:48px;
    height:48px;
    border-radius:var(--radius-xs);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:18px;
    flex-shrink:0;
}

.rev-card.total .rev-icon{color:var(--success); border:1px solid var(--success-border); background:var(--success-soft);}
.rev-card.month .rev-icon{color:var(--info); border:1px solid var(--info-border); background:var(--info-soft);}
.rev-card.today .rev-icon{color:var(--warning); border:1px solid var(--warning-border); background:var(--warning-soft);}

/* ===== MANAGEMENT MENU ===== */
.menu-section{
    margin-bottom:24px;
}

.menu-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:16px;
}

@media(max-width:1100px){
    .menu-grid{grid-template-columns:repeat(2,1fr);}
}
@media(max-width:600px){
    .menu-grid{grid-template-columns:1fr;}
}

.menu-card{
    display:flex;
    align-items:center;
    gap:16px;
    padding:24px;
    background:var(--bg-secondary);
    border:1px solid var(--border);
    border-radius:var(--radius-sm);
    text-decoration:none;
    color:var(--text-primary);
    transition:var(--transition);
    position:relative;
    overflow:hidden;
}

.menu-card::before{
    content:'';
    position:absolute;
    top:0;
    left:0;
    width:4px;
    height:100%;
    background:var(--text-primary);
    opacity:0;
    transition:opacity 0.3s ease;
}

.menu-card:hover{
    transform:translateY(-2px);
    border-color:var(--border-light);
    box-shadow:var(--shadow-hover);
    background:var(--bg-tertiary);
}

.menu-card:hover::before{
    opacity:1;
}

.menu-icon{
    width:48px;
    height:48px;
    border-radius:var(--radius-xs);
    background:var(--bg-primary);
    border:1px solid var(--border);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
    color:var(--text-primary);
    transition:var(--transition);
    flex-shrink:0;
}

.menu-card:hover .menu-icon{
    background:var(--text-primary);
    color:var(--bg-primary);
    border-color:var(--text-primary);
}

.menu-text{flex:1;min-width:0;}

.menu-title{
    font-family:'Space Grotesk',sans-serif;
    font-size:16px;
    font-weight:700;
    margin-bottom:4px;
}

.menu-desc{
    font-size:13px;
    color:var(--text-muted);
    line-height:1.5;
}

.menu-arrow{
    color:var(--text-muted);
    font-size:14px;
    transition:var(--transition);
}

.menu-card:hover .menu-arrow{
    color:var(--text-primary);
    transform:translateX(4px);
}

/* ===== MAIN GRID ===== */
.main-grid{
    display:grid;
    grid-template-columns:2fr 1fr;
    gap:24px;
    margin-bottom:24px;
}

@media(max-width:1100px){
    .main-grid{grid-template-columns:1fr;}
}

/* ===== SECTION ===== */
.section{
    background:var(--bg-secondary);
    border:1px solid var(--border);
    border-radius:var(--radius-sm);
    padding:24px;
    transition:var(--transition);
}

.section:hover{
    border-color:var(--border-light);
}

.section-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:20px;
}

.section-title{
    font-family:'Space Grotesk',sans-serif;
    font-size:18px;
    font-weight:700;
    letter-spacing:-0.5px;
}

.section-action{
    font-size:13px;
    color:var(--text-muted);
    font-weight:600;
    text-decoration:none;
    transition:var(--transition);
}

.section-action:hover{
    color:var(--text-primary);
}

/* ===== ON-DUTY COMPACT ===== */
.duty-list{
    display:flex;
    flex-direction:column;
    gap:12px;
}

.duty-item{
    display:flex;
    align-items:flex-start;
    gap:16px;
    padding:20px;
    background:var(--bg-tertiary);
    border:1px solid var(--border);
    border-radius:var(--radius-xs);
    transition:var(--transition);
}

.duty-item:hover{
    border-color:var(--border-light);
    background:var(--bg-hover);
}

.duty-avatar{
    width:48px;
    height:48px;
    border-radius:50%;
    object-fit:cover;
    border:1px solid var(--border);
    background:var(--bg-input);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:16px;
    font-weight:700;
    color:var(--text-muted);
    flex-shrink:0;
    overflow:hidden;
    margin-top:2px;
}

.duty-avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.duty-info{
    flex:1;
    min-width:0;
}

.duty-name{
    font-size:16px;
    font-weight:700;
    margin-bottom:6px;
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
}

.duty-name .booked-tag{
    font-size:12px;
    font-weight:700;
    padding:4px 12px;
    border-radius:6px;
    background:var(--danger-soft);
    border:1px solid var(--danger-border);
    color:var(--danger);
}

.duty-name .booked-tag.full{
    background:var(--danger);
    color:white;
    border-color:var(--danger);
}

.duty-name .expired-tag{
    font-size:12px;
    font-weight:700;
    padding:4px 12px;
    border-radius:6px;
    background:var(--bg-input);
    border:1px solid var(--border);
    color:var(--text-muted);
}

.duty-meta{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:13px;
    color:var(--text-muted);
    margin-bottom:10px;
}

.duty-meta .dot{
    width:6px;
    height:6px;
    border-radius:50%;
    display:inline-block;
}

.dot-success{background:var(--success);}
.dot-warning{background:var(--warning);}
.dot-danger{background:var(--danger);}

.duty-slots{
    display:flex;
    gap:6px;
    flex-wrap:wrap;
}

.slot-pill{
    padding:4px 10px;
    border-radius:6px;
    font-size:12px;
    font-weight:600;
    font-family:'Space Grotesk',monospace;
}

.slot-pill.available{
    background:var(--success-soft);
    border:1px solid var(--success-border);
    color:var(--success);
}

.slot-pill.booked{
    background:var(--danger-soft);
    border:1px solid var(--danger-border);
    color:var(--danger);
    text-decoration:line-through;
    opacity:0.5;
}

.slot-pill.expired-time{
    background:var(--bg-input);
    border:1px solid var(--border);
    color:var(--text-muted);
    text-decoration:line-through;
    opacity:0.4;
    position:relative;
}

.slot-pill.expired-time::after{
    content:'';
    position:absolute;
    left:0;
    right:0;
    top:50%;
    height:1px;
    background:var(--text-muted);
    opacity:0.5;
}

.duty-badge{
    padding:6px 14px;
    border-radius:100px;
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:0.5px;
    flex-shrink:0;
    margin-top:4px;
}

.duty-badge.active{
    background:var(--success-soft);
    border:1px solid var(--success-border);
    color:var(--success);
}

.duty-badge.idle{
    background:var(--bg-input);
    border:1px solid var(--border);
    color:var(--text-muted);
}

/* ===== ACTIVITY LOG ===== */
.activity-list{
    display:flex;
    flex-direction:column;
    gap:0;
    position:relative;
}

.activity-list::before{
    content:'';
    position:absolute;
    left:20px;
    top:0;
    bottom:0;
    width:1px;
    background:var(--border);
}

.activity-item{
    display:flex;
    align-items:flex-start;
    gap:14px;
    padding:14px 0;
    position:relative;
    padding-left:8px;
}

.activity-item:first-child{padding-top:0;}
.activity-item:last-child{padding-bottom:0;}

.activity-icon{
    width:24px;
    height:24px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:10px;
    flex-shrink:0;
    z-index:1;
    margin-left:4px;
    margin-top:2px;
}

.activity-icon.paid{
    background:var(--success-soft);
    border:1px solid var(--success-border);
    color:var(--success);
}

.activity-icon.pending{
    background:var(--warning-soft);
    border:1px solid var(--warning-border);
    color:var(--warning);
}

.activity-icon.expired{
    background:var(--danger-soft);
    border:1px solid var(--danger-border);
    color:var(--danger);
}

.activity-icon.user{
    background:var(--info-soft);
    border:1px solid var(--info-border);
    color:var(--info);
}

.activity-icon.review{
    background:var(--gold-soft);
    border:1px solid rgba(255,215,0,0.2);
    color:var(--gold);
}

.activity-content{
    flex:1;
    min-width:0;
}

.activity-text{
    font-size:13px;
    line-height:1.5;
    color:var(--text-secondary);
    margin-bottom:4px;
}

.activity-text strong{
    color:var(--text-primary);
    font-weight:600;
}

.activity-text .amount{
    color:var(--success);
    font-weight:700;
    font-family:'Space Grotesk',sans-serif;
}

.activity-text .rating{
    color:var(--warning);
}

.activity-time{
    font-size:11px;
    color:var(--text-muted);
    font-weight:500;
}

.activity-empty{
    text-align:center;
    padding:32px;
    color:var(--text-muted);
    font-size:14px;
}

/* ===== TABLE ===== */
.table-box{
    overflow:hidden;
    border-radius:var(--radius-xs);
    border:1px solid var(--border);
}

table{
    width:100%;
    border-collapse:collapse;
}

thead th{
    background:var(--bg-tertiary);
    color:var(--text-muted);
    padding:14px 16px;
    text-align:left;
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:0.5px;
    border-bottom:1px solid var(--border);
}

tbody td{
    padding:14px 16px;
    border-bottom:1px solid var(--border);
    font-size:14px;
}

tbody tr{
    transition:var(--transition);
}

tbody tr:hover{
    background:var(--bg-hover);
}

.user-cell{
    display:flex;
    align-items:center;
    gap:10px;
}

.user-avatar{
    width:32px;
    height:32px;
    border-radius:50%;
    background:var(--bg-input);
    border:1px solid var(--border);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:12px;
    color:var(--text-muted);
    font-weight:700;
    overflow:hidden;
}

.user-avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.status-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 12px;
    border-radius:100px;
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:0.5px;
}

.status-paid{
    background:var(--success-soft);
    border:1px solid var(--success-border);
    color:var(--success);
}

/* ===== BARBER RANK ===== */
.rank-list{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.rank-item{
    display:flex;
    align-items:center;
    gap:12px;
    padding:14px;
    background:var(--bg-tertiary);
    border:1px solid var(--border);
    border-radius:var(--radius-xs);
    transition:var(--transition);
}

.rank-item:hover{
    border-color:var(--border-light);
    background:var(--bg-hover);
}

.rank-number{
    font-family:'Space Grotesk',sans-serif;
    font-size:16px;
    font-weight:700;
    color:var(--text-muted);
    min-width:28px;
    text-align:center;
}

.rank-number.top{
    color:var(--text-primary);
}

.rank-avatar{
    width:40px;
    height:40px;
    border-radius:50%;
    object-fit:cover;
    border:1px solid var(--border);
    background:var(--bg-input);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:14px;
    font-weight:700;
    color:var(--text-muted);
    flex-shrink:0;
    overflow:hidden;
}

.rank-avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.rank-info{
    flex:1;
    min-width:0;
}

.rank-name{
    font-size:14px;
    font-weight:700;
    margin-bottom:2px;
}

.rank-meta{
    font-size:12px;
    color:var(--text-muted);
}

.rank-income{
    font-family:'Space Grotesk',sans-serif;
    font-size:14px;
    font-weight:700;
}

/* ===== CHART ===== */
.chart-wrapper{
    position:relative;
    height:240px;
    margin-top:10px;
}

/* ===== RATING SECTION ===== */
.rating-big{
    font-family:'Space Grotesk',sans-serif;
    font-size:48px;
    font-weight:700;
    letter-spacing:-2px;
    line-height:1;
    margin-bottom:8px;
}

.rating-stars{
    display:flex;
    gap:4px;
    margin-bottom:8px;
    font-size:16px;
    color:var(--warning);
}

.rating-count{
    font-size:14px;
    color:var(--text-muted);
    font-weight:500;
}

.rating-bars{
    display:flex;
    flex-direction:column;
    gap:8px;
    flex:1;
}

.rating-bar-row{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:13px;
}

.rating-bar-label{
    min-width:30px;
    font-weight:600;
    color:var(--text-secondary);
    font-family:'Space Grotesk',sans-serif;
    display:flex;
    align-items:center;
    gap:4px;
}

.rating-bar-track{
    flex:1;
    height:8px;
    background:var(--bg-input);
    border-radius:4px;
    overflow:hidden;
}

.rating-bar-fill{
    height:100%;
    background:linear-gradient(90deg, var(--warning), #ffcc00);
    border-radius:4px;
    transition:width 1s cubic-bezier(0.4,0,0.2,1);
}

.rating-bar-count{
    min-width:24px;
    text-align:right;
    font-size:12px;
    color:var(--text-muted);
    font-weight:600;
}

.rating-layout{
    display:flex;
    gap:32px;
    align-items:center;
}

@media(max-width:768px){
    .rating-layout{
        flex-direction:column;
        gap:20px;
        align-items:flex-start;
    }
}

.rating-left{
    text-align:center;
    min-width:100px;
}

/* ===== REVIEW CARD ===== */
.review-card{
    display:flex;
    gap:14px;
    padding:18px;
    background:var(--bg-tertiary);
    border:1px solid var(--border);
    border-radius:var(--radius-xs);
    margin-bottom:10px;
    transition:var(--transition);
}

.review-card:hover{
    border-color:var(--border-light);
}

.review-avatar{
    width:40px;
    height:40px;
    border-radius:50%;
    background:var(--bg-input);
    border:1px solid var(--border);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:14px;
    font-weight:700;
    color:var(--text-muted);
    flex-shrink:0;
    overflow:hidden;
}

.review-avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.review-content{
    flex:1;
    min-width:0;
}

.review-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:6px;
    flex-wrap:wrap;
    gap:8px;
}

.review-user{
    font-size:14px;
    font-weight:700;
}

.review-stars{
    color:var(--warning);
    font-size:12px;
    letter-spacing:1px;
}

.review-text{
    font-size:13px;
    color:var(--text-secondary);
    line-height:1.6;
    margin-bottom:6px;
    word-wrap:break-word;
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
    line-clamp:2;
}

.review-date{
    font-size:12px;
    color:var(--text-muted);
}

/* ===== EMPTY ===== */
.empty-state{
    text-align:center;
    padding:32px;
    color:var(--text-muted);
    font-size:14px;
}

/* ===== ANIMATIONS ===== */
@keyframes fadeUp{
    from{opacity:0;transform:translateY(12px);}
    to{opacity:1;transform:translateY(0);}
}

.stat-item, .rev-card, .menu-card, .section{
    animation:fadeUp 0.4s ease forwards;
}

/* ===== SCROLLBAR ===== */
::-webkit-scrollbar{width:6px;}
::-webkit-scrollbar-track{background:var(--bg-primary);}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px;}
::-webkit-scrollbar-thumb:hover{background:var(--border-light);}

/* ===== RESPONSIVE ===== */
@media(max-width:768px){
    .container{padding:20px 16px;}
    .page-title h1{font-size:26px;}
    .page-header{flex-direction:column;align-items:flex-start;gap:12px;}
    .rating-big{font-size:36px;}
    .duty-avatar{width:40px;height:40px;}
    .rank-avatar{width:36px;height:36px;}
    .review-avatar{width:36px;height:36px;}
}
</style>
</head>

<body>

<div class="container">

    <!-- HEADER -->
    <div class="page-header">
        <div class="page-title">
            <h1>Dashboard.</h1>
            <p>Overview & analytics of your barbershop</p>
        </div>
        <div class="header-actions">
            <div class="live-badge">
                <span></span>
                Live System
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- QUICK STATS -->
    <div class="stats-strip">
        <div class="stat-item">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-item-value"><?= number_format($user['total']) ?></div>
            <div class="stat-item-label">Active Users</div>
            <div class="stat-item-sub">
                <span class="dot dot-success"></span>
                registered customers
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon"><i class="fas fa-cut"></i></div>
            <div class="stat-item-value"><?= number_format($barber['total']) ?></div>
            <div class="stat-item-label">Hair Artists</div>
            <div class="stat-item-sub">
                <span class="dot dot-success"></span>
                on team
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-item-value"><?= number_format($totalBookings) ?></div>
            <div class="stat-item-label">Total Bookings</div>
            <div class="stat-item-sub">
                <span class="dot dot-warning"></span>
                <?= number_format($bookingPending['total']) ?> pending
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-item-value"><?= number_format($bookingPaid['total']) ?></div>
            <div class="stat-item-label">Paid</div>
            <div class="stat-item-sub">
                <span class="dot dot-success"></span>
                completed
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-item-value"><?= number_format($bookingExpired['total']) ?></div>
            <div class="stat-item-label">Expired</div>
            <div class="stat-item-sub">
                <span class="dot dot-danger"></span>
                cancelled
            </div>
        </div>
    </div>

    <!-- REVENUE COMPACT -->
    <div class="revenue-compact">
        <div class="rev-card total">
            <div class="rev-info">
                <div class="rev-title">Total Revenue</div>
                <div class="rev-value">Rp<?= number_format($totalIncome) ?></div>
            </div>
            <div class="rev-icon"><i class="fas fa-wallet"></i></div>
        </div>
        <div class="rev-card month">
            <div class="rev-info">
                <div class="rev-title">This Month</div>
                <div class="rev-value">Rp<?= number_format($totalMonthIncome) ?></div>
            </div>
            <div class="rev-icon"><i class="fas fa-chart-line"></i></div>
        </div>
        <div class="rev-card today">
            <div class="rev-info">
                <div class="rev-title">Today</div>
                <div class="rev-value">Rp<?= number_format($totalTodayIncome) ?></div>
            </div>
            <div class="rev-icon"><i class="fas fa-calendar-day"></i></div>
        </div>
    </div>

    <!-- MANAGEMENT MENU -->
    <div class="menu-section">
        <div class="menu-grid">
            <a href="barber.php" class="menu-card">
                <div class="menu-icon"><i class="fas fa-cut"></i></div>
                <div class="menu-text">
                    <div class="menu-title">Manage Hair Artists</div>
                    <div class="menu-desc">Schedule & skills</div>
                </div>
                <i class="fas fa-chevron-right menu-arrow"></i>
            </a>
            <a href="paket.php" class="menu-card">
                <div class="menu-icon"><i class="fas fa-box-open"></i></div>
                <div class="menu-text">
                    <div class="menu-title">Manage Packages</div>
                    <div class="menu-desc">Services & pricing</div>
                </div>
                <i class="fas fa-chevron-right menu-arrow"></i>
            </a>
            <a href="booking.php" class="menu-card">
                <div class="menu-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="menu-text">
                    <div class="menu-title">Manage Bookings</div>
                    <div class="menu-desc">All appointments</div>
                </div>
                <i class="fas fa-chevron-right menu-arrow"></i>
            </a>
            <a href="users.php" class="menu-card">
                <div class="menu-icon"><i class="fas fa-users-cog"></i></div>
                <div class="menu-text">
                    <div class="menu-title">Manage Users</div>
                    <div class="menu-desc">Customer database</div>
                </div>
                <i class="fas fa-chevron-right menu-arrow"></i>
            </a>
        </div>
    </div>

    <!-- MAIN GRID: ON-DUTY + ACTIVITY LOG -->
    <div class="main-grid">
        
        <!-- ON-DUTY TODAY -->
        <div class="section">
            <div class="section-header">
                <div class="section-title">On-Duty Today</div>
                <span class="section-action"><?= date('d M Y') ?></span>
            </div>
            
            <?php if(count($onDutyBarbers) > 0){ ?>
            <div class="duty-list">
                <?php foreach($onDutyBarbers as $duty){ 
                    $bookingCount = $todayBarberBookings[$duty['id']] ?? 0;
                    $hasBookings = $bookingCount > 0;
                    $bookedJams = $bookedSlotsByBarber[$duty['id']] ?? [];
                    
                    // HITUNG SLOT STATUS
                    $expiredCount = 0;      // kosong + udah lewat jam
                    $bookedCount = count($bookedJams);  // udah ada yang booking
                    $availableCount = 0;    // kosong + jam masih depan
                    
                    foreach($jamList as $jam){
                        $isBooked = in_array($jam, $bookedJams);
                        $isExpired = $jam < $currentHour;
                        
                        if($isBooked){
                            // booked tetep booked meski udah lewat
                            continue;
                        } elseif($isExpired){
                            // kosong + udah lewat = expired
                            $expiredCount++;
                        } else {
                            // kosong + jam depan = available
                            $availableCount++;
                        }
                    }
                    
                    $isFullyBooked = $availableCount == 0;
                    $hasExpiredSlots = $expiredCount > 0;
                    
                    // DOT COLOR LOGIC
                    if($isFullyBooked && $availableCount == 0){
                        $dotClass = 'dot-danger';
                        $freeText = 'No slots free';
                    } elseif($availableCount > 0){
                        $dotClass = 'dot-success';
                        $freeText = $availableCount . '/' . count($jamList) . ' slots free';
                    } else {
                        $dotClass = 'dot-warning';
                        $freeText = '0/' . count($jamList) . ' slots free';
                    }
                ?>
                <div class="duty-item">
                    <?php if($duty['foto']){ ?>
                    <img src="upload/<?= $duty['foto'] ?>" alt="<?= $duty['nama'] ?>" class="duty-avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="duty-avatar" style="display:none;"><?= strtoupper(substr($duty['nama'],0,1)) ?></div>
                    <?php } else { ?>
                    <div class="duty-avatar"><?= strtoupper(substr($duty['nama'],0,1)) ?></div>
                    <?php } ?>
                    
                    <div class="duty-info">
                        <div class="duty-name">
                            <?= $duty['nama'] ?>
                            <?php if($hasBookings){ ?>
                                <?php if($isFullyBooked){ ?>
                                <span class="booked-tag full">Fully Booked</span>
                                <?php } else { ?>
                                <span class="booked-tag"><?= $bookedCount ?> booked</span>
                                <?php } ?>
                            <?php } ?>
                            <?php if($hasExpiredSlots){ ?>
                                <span class="expired-tag"><?= $expiredCount ?> expired</span>
                            <?php } ?>
                        </div>
                        <div class="duty-meta">
                            <span class="dot <?= $dotClass ?>"></span>
                            <?= $freeText ?>
                        </div>
                        <?php if(count($jamList) > 0){ ?>
                        <div class="duty-slots">
                            <?php foreach($jamList as $jam){ 
                                $isBooked = in_array($jam, $bookedJams);
                                $isExpired = !$isBooked && $jam < $currentHour;
                                
                                if($isBooked){
                                    $slotClass = 'booked';
                                } elseif($isExpired){
                                    $slotClass = 'expired-time';
                                } else {
                                    $slotClass = 'available';
                                }
                            ?>
                            <span class="slot-pill <?= $slotClass ?>"><?= $jam ?></span>
                            <?php } ?>
                        </div>
                        <?php } ?>
                    </div>
                    
                    <span class="duty-badge <?= $hasBookings ? 'active' : 'idle' ?>">
                        <?= $hasBookings ? 'Active' : 'Idle' ?>
                    </span>
                </div>
                <?php } ?>
            </div>
            <?php } else { ?>
            <div class="empty-state">
                <i class="fas fa-user-slash" style="font-size:20px;margin-bottom:8px;display:block;"></i>
                No Hair Artists scheduled for today
            </div>
            <?php } ?>
        </div>

        <!-- RECENT ACTIVITY LOG -->
        <div class="section">
            <div class="section-header">
                <div class="section-title">Recent Activity</div>
                <span class="section-action">Today</span>
            </div>
            
            <?php if(count($activityLog) > 0){ ?>
            <div class="activity-list">
                <?php foreach($activityLog as $activity){ 
                    $timeAgo = '';
                    $diff = time() - strtotime($activity['time']);
                    
                    if($diff < 60){
                        $timeAgo = 'Just now';
                    } elseif($diff < 3600){
                        $mins = floor($diff / 60);
                        $timeAgo = $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
                    } elseif($diff < 86400){
                        $hours = floor($diff / 3600);
                        $timeAgo = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
                    } else {
                        $timeAgo = date('H:i', strtotime($activity['time']));
                    }
                    
                    switch($activity['type']){
                        case 'booking_paid':
                            $icon = 'fa-check';
                            $iconClass = 'paid';
                            $text = '<strong>' . htmlspecialchars($activity['user']) . '</strong> paid <span class="amount">Rp' . number_format($activity['amount']) . '</span> for <strong>' . htmlspecialchars($activity['package']) . '</strong> with ' . htmlspecialchars($activity['barber']);
                            break;
                        case 'booking_pending':
                            $icon = 'fa-clock';
                            $iconClass = 'pending';
                            $text = '<strong>' . htmlspecialchars($activity['user']) . '</strong> booked <strong>' . htmlspecialchars($activity['package']) . '</strong> with ' . htmlspecialchars($activity['barber']) . ' (pending)';
                            break;
                        case 'booking_expired':
                            $icon = 'fa-times';
                            $iconClass = 'expired';
                            $text = '<strong>' . htmlspecialchars($activity['user']) . '</strong>\'s booking for <strong>' . htmlspecialchars($activity['package']) . '</strong> expired';
                            break;
                        case 'user_registered':
                            $icon = 'fa-user-plus';
                            $iconClass = 'user';
                            $text = 'New user <strong>' . htmlspecialchars($activity['user']) . '</strong> registered';
                            break;
                        case 'new_review':
                            $icon = 'fa-star';
                            $iconClass = 'review';
                            $stars = str_repeat('<i class="fas fa-star" style="font-size:9px;"></i>', $activity['amount']);
                            $text = '<strong>' . htmlspecialchars($activity['user']) . '</strong> gave a <span class="rating">' . $stars . '</span> review';
                            break;
                        default:
                            $icon = 'fa-circle';
                            $iconClass = 'pending';
                            $text = 'Activity recorded';
                    }
                ?>
                <div class="activity-item">
                    <div class="activity-icon <?= $iconClass ?>">
                        <i class="fas <?= $icon ?>"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text"><?= $text ?></div>
                        <div class="activity-time"><?= $timeAgo ?></div>
                    </div>
                </div>
                <?php } ?>
            </div>
            <?php } else { ?>
            <div class="activity-empty">
                <i class="fas fa-history" style="font-size:20px;margin-bottom:8px;display:block;"></i>
                No activity today
            </div>
            <?php } ?>
        </div>
    </div>

    <!-- SECOND GRID: BOOKINGS + RANKING -->
    <div class="main-grid" style="margin-bottom:24px;">
        
        <!-- RECENT BOOKINGS -->
        <div class="section">
            <div class="section-header">
                <div class="section-title">Recent Bookings</div>
                <a href="booking.php" class="section-action">View All <i class="fas fa-arrow-right" style="font-size:10px;"></i></a>
            </div>
            
            <?php if(mysqli_num_rows($latestBooking) > 0){ ?>
            <div class="table-box">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Barber</th>
                            <th>Package</th>
                            <th>Date</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($d = mysqli_fetch_assoc($latestBooking)){ ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar">
                                        <?php if(!empty($d['user_photo']) && file_exists('../' . $d['user_photo'])){ ?>
                                        <img src="../<?= $d['user_photo'] ?>" alt="<?= htmlspecialchars($d['username']) ?>">
                                        <?php } else { ?>
                                        <?= strtoupper(substr($d['username'],0,1)) ?>
                                        <?php } ?>
                                    </div>
                                    <?= htmlspecialchars($d['username']) ?>
                                </div>
                            </td>
                            <td><?= $d['barber_nama'] ?></td>
                            <td><?= $d['nama_paket'] ?></td>
                            <td><?= date('d M', strtotime($d['tanggal'])) ?></td>
                            <td><?= substr($d['jam'],0,5) ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php } else { ?>
            <div class="empty-state">No recent bookings</div>
            <?php } ?>
        </div>

        <!-- TOP PERFORMERS -->
        <div class="section">
            <div class="section-header">
                <div class="section-title">Top Hair Artists</div>
            </div>
            <div class="rank-list">
                <?php 
                $rank = 1;
                while($b = mysqli_fetch_assoc($barberStats)){ 
                    $isTop = $rank <= 3;
                ?>
                <div class="rank-item">
                    <div class="rank-number <?= $isTop ? 'top' : '' ?>">#<?= $rank ?></div>
                    <?php if($b['foto']){ ?>
                    <img src="upload/<?= $b['foto'] ?>" alt="<?= $b['nama'] ?>" class="rank-avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="rank-avatar" style="display:none;"><?= strtoupper(substr($b['nama'],0,1)) ?></div>
                    <?php } else { ?>
                    <div class="rank-avatar"><?= strtoupper(substr($b['nama'],0,1)) ?></div>
                    <?php } ?>
                    <div class="rank-info">
                        <div class="rank-name"><?= $b['nama'] ?></div>
                        <div class="rank-meta"><?= $b['total_booking'] ?> bookings</div>
                    </div>
                    <div class="rank-income">Rp<?= number_format($b['total_income']) ?></div>
                </div>
                <?php $rank++; } ?>
            </div>
        </div>
    </div>

    <!-- CHART SECTION -->
    <div class="section" style="margin-bottom:24px;">
        <div class="section-header">
            <div class="section-title">Revenue Trend</div>
            <span class="section-action">Last 7 Days</span>
        </div>
        <div class="chart-wrapper">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- RATING & REVIEWS -->
    <div class="main-grid" style="margin-bottom:24px;">
        
        <!-- OVERALL RATING -->
        <div class="section">
            <div class="section-header">
                <div class="section-title">Customer Reviews</div>
                <span class="section-action"><?= $totalReviews ?> reviews</span>
            </div>
            
            <?php if($totalReviews > 0){ ?>
            <div class="rating-layout">
                <div class="rating-left">
                    <div class="rating-big"><?= $avgRating ?></div>
                    <div class="rating-stars">
                        <?php 
                        $fullStars = floor($avgRating);
                        $halfStar = ($avgRating - $fullStars) >= 0.5 ? 1 : 0;
                        $emptyStars = 5 - $fullStars - $halfStar;
                        
                        for($i=0; $i<$fullStars; $i++) echo '<i class="fas fa-star"></i>';
                        if($halfStar) echo '<i class="fas fa-star-half-alt"></i>';
                        for($i=0; $i<$emptyStars; $i++) echo '<i class="far fa-star"></i>';
                        ?>
                    </div>
                    <div class="rating-count"><?= number_format($totalReviews) ?> ratings</div>
                </div>
                
                <div class="rating-bars">
                    <?php 
                    $starCounts = [5=>$star5, 4=>$star4, 3=>$star3, 2=>$star2, 1=>$star1];
                    foreach($starCounts as $star => $count){
                        $pct = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                    ?>
                    <div class="rating-bar-row">
                        <div class="rating-bar-label"><?= $star ?> <i class="fas fa-star" style="font-size:10px;"></i></div>
                        <div class="rating-bar-track">
                            <div class="rating-bar-fill" style="width:<?= $pct ?>%"></div>
                        </div>
                        <div class="rating-bar-count"><?= number_format($count) ?></div>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <?php } else { ?>
            <div class="empty-state">No reviews yet</div>
            <?php } ?>
        </div>

        <!-- LATEST REVIEWS -->
        <div class="section">
            <div class="section-header">
                <div class="section-title">Latest Reviews</div>
                <a href="reviews.php" class="section-action">View All <i class="fas fa-arrow-right" style="font-size:10px;"></i></a>
            </div>
            
            <?php if(mysqli_num_rows($latestReviews) > 0){ ?>
            <div>
                <?php while($rev = mysqli_fetch_assoc($latestReviews)){ 
                    $emptyStars = 5 - $rev['rating'];
                ?>
                <div class="review-card">
                    <div class="review-avatar">
                        <?php if(!empty($rev['user_photo']) && file_exists('../' . $rev['user_photo'])){ ?>
                        <img src="../<?= $rev['user_photo'] ?>" alt="<?= htmlspecialchars($rev['username']) ?>">
                        <?php } else { ?>
                        <?= strtoupper(substr($rev['username'],0,1)) ?>
                        <?php } ?>
                    </div>
                    <div class="review-content">
                        <div class="review-header">
                            <div class="review-user"><?= htmlspecialchars($rev['username']) ?></div>
                            <div class="review-stars">
                                <?php 
                                for($s=0; $s<$rev['rating']; $s++) echo '<i class="fas fa-star"></i>';
                                for($s=0; $s<$emptyStars; $s++) echo '<i class="far fa-star"></i>';
                                ?>
                            </div>
                        </div>
                        <?php if(!empty($rev['review'])){ ?>
                        <div class="review-text">"<?= htmlspecialchars($rev['review']) ?>"</div>
                        <?php } ?>
                        <div class="review-date"><?= date('d M Y • H:i', strtotime($rev['created_at'])) ?></div>
                    </div>
                </div>
                <?php } ?>
            </div>
            <?php } else { ?>
            <div class="empty-state">No reviews yet</div>
            <?php } ?>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
// Revenue Chart
const ctx = document.getElementById('revenueChart').getContext('2d');
const chartLabels = <?= json_encode($chartLabels) ?>;
const chartData = <?= json_encode($chartData) ?>;

const maxVal = Math.max(...chartData, 1);
const gradient = ctx.createLinearGradient(0, 0, 0, 240);
gradient.addColorStop(0, 'rgba(255, 255, 255, 0.12)');
gradient.addColorStop(1, 'rgba(255, 255, 255, 0.01)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartLabels,
        datasets: [{
            label: 'Revenue',
            data: chartData,
            borderColor: '#ffffff',
            backgroundColor: gradient,
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#0a0a0a',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1a1a1a',
                borderColor: '#333333',
                borderWidth: 1,
                titleColor: '#ffffff',
                bodyColor: '#a0a0a0',
                padding: 12,
                cornerRadius: 8,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        return 'Rp' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: {
                    color: '#666666',
                    font: { size: 12, family: 'Space Grotesk' }
                }
            },
            y: {
                display: false,
                min: 0,
                max: maxVal * 1.2
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});
</script>

</body>
</html>