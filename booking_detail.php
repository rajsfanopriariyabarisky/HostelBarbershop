<?php
date_default_timezone_set('Asia/Jakarta');
include 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$userId = $user['id'];

$bookingId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if(!$bookingId){
    header("Location: history.php");
    exit;
}

$q = mysqli_query($conn, "
    SELECT 
        b.*,
        p.nama_paket,
        p.harga,
        br.nama as barber_name,
        br.foto as barber_foto,
        br.keterangan as barber_bio,
        br.skill_fade,
        br.skill_scissoring,
        br.skill_longcut,
        br.skill_shortcut,
        br.skill_beardcut
    FROM booking b
    JOIN paket p ON b.paket_id = p.id
    JOIN barber br ON b.barber_id = br.id
    WHERE b.id = '$bookingId' AND b.user_id = '$userId'
");

if(mysqli_num_rows($q) == 0){
    header("Location: history.php");
    exit;
}

$booking = mysqli_fetch_assoc($q);

$avgSkill = round(($booking['skill_fade'] + $booking['skill_scissoring'] + $booking['skill_longcut'] + $booking['skill_shortcut'] + $booking['skill_beardcut']) / 5);

// Format ID
$orderId = 'HSTL-' . str_pad($booking['id'], 5, '0', STR_PAD_LEFT);

// QR Redirect URL — hanya dihitung, tampil kalo paid aja
$baseUrl = "https://" . $_SERVER['HTTP_HOST'];
$scriptPath = dirname($_SERVER['PHP_SELF']);
$qrRedirectUrl = $baseUrl . $scriptPath . "/qr_redirect.php?id=" . $booking['id'];
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrRedirectUrl);

// Timer for pending
$createdTime = strtotime($booking['created_at']);
$expiryTime = $createdTime + 30;
$remaining = max(0, $expiryTime - time());

// Timeline logic
$bookingDateTime = strtotime($booking['tanggal'] . ' ' . $booking['jam']);
$now = time();
$isPast = $now > $bookingDateTime;

// Specialty tags based on highest skill
$skills = [
    'Fade' => $booking['skill_fade'],
    'Scissor' => $booking['skill_scissoring'],
    'Long Cut' => $booking['skill_longcut'],
    'Short Cut' => $booking['skill_shortcut'],
    'Beard' => $booking['skill_beardcut']
];
arsort($skills);
$topSkills = array_slice(array_keys($skills), 0, 3);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booking Details | Hostel Barbershop</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
:root{
    --bg-primary:#050507;
    --bg-secondary:#0c0c10;
    --bg-card:#13131a;
    --bg-hover:#1e1e28;
    --bg-input:#0a0a0f;
    --border:rgba(255, 255, 255, 0.14);
    --border-light:rgba(255, 255, 255, 0.28);
    --text-primary:#ffffff;
    --text-secondary:#d5d0c8;
    --text-muted:#9e998f;
    --text-dim:#6b665e;
    --gold:#e8c87a;
    --gold-light:#f5e6c3;
    --gold-dim:rgba(232, 200, 122, 0.10);
    --gold-border:rgba(232, 200, 122, 0.45);
    --success:#6ee7a0;
    --success-dim:rgba(110, 231, 160, 0.08);
    --success-border:rgba(110, 231, 160, 0.25);
    --danger:#e88484;
    --danger-dim:rgba(232, 132, 132, 0.08);
    --danger-border:rgba(232, 132, 132, 0.25);
    --warning:#e8c87a;
    --warning-dim:rgba(232, 200, 122, 0.08);
    --warning-border:rgba(232, 200, 122, 0.25);
    --shadow:0 4px 24px rgba(0,0,0,0.4);
    --shadow-hover:0 8px 40px rgba(0,0,0,0.5);
    --radius:2px;
    --transition:all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:var(--bg-primary);
    color:var(--text-primary);
    font-family:'Montserrat',sans-serif;
    line-height:1.5;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:24px;
    overflow-x:hidden;
    -webkit-font-smoothing:antialiased;
}

body::before{
    content:'';
    position:fixed;
    inset:0;
    background:
        radial-gradient(circle at 20% 30%, rgba(232,200,122,0.03) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(232,200,122,0.02) 0%, transparent 50%);
    pointer-events:none;
    z-index:0;
}

.container{
    position:relative;
    z-index:1;
    width:100%;
    max-width:720px;
}

/* Header */
.page-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:28px;
    padding-bottom:20px;
    border-bottom:1.5px solid var(--border);
    position:relative;
}

.page-header::after{
    content:'';
    position:absolute;
    bottom:-1.5px;
    left:0;
    width:80px;
    height:2px;
    background:linear-gradient(90deg, var(--gold), transparent);
}

.page-header-left h1{
    font-family:'Cormorant Garamond',serif;
    font-size:32px;
    font-weight:300;
    letter-spacing:-1.5px;
    line-height:1.1;
    color:var(--text-primary);
}

.page-header-left h1 em{
    font-style:italic;
    color:var(--gold);
}

.page-header-left p{
    color:var(--text-muted);
    font-size:13px;
    margin-top:6px;
    font-weight:500;
}

.back-btn{
    display:inline-flex;
    align-items:center;
    gap:10px;
    padding:10px 20px;
    background:transparent;
    border:1.5px solid var(--border);
    color:var(--text-muted);
    text-decoration:none;
    font-size:10px;
    font-weight:700;
    letter-spacing:2px;
    text-transform:uppercase;
    transition:var(--transition);
    position:relative;
    overflow:hidden;
    font-family:'Montserrat',sans-serif;
}

.back-btn::before{
    content:'';
    position:absolute;
    inset:0;
    background:var(--gold);
    transform:translateX(-100%);
    transition:transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}

.back-btn:hover{
    color:var(--bg-primary);
    border-color:var(--gold);
}

.back-btn:hover::before{
    transform:translateX(0);
}

.back-btn i, .back-btn span{
    position:relative;
    z-index:1;
}

.back-btn i{
    font-size:9px;
    transition:transform 0.3s ease;
}

.back-btn:hover i{
    transform:translateX(-4px);
}

/* Status Badge */
.status-hero{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 16px;
    border-radius:var(--radius);
    font-size:10px;
    font-weight:700;
    letter-spacing:2px;
    text-transform:uppercase;
    margin-bottom:20px;
    border:1.5px solid var(--border);
}

.status-hero.paid{
    background:var(--success-dim);
    border-color:var(--success-border);
    color:var(--success);
    box-shadow:0 0 20px rgba(110,231,160,0.06);
}

.status-hero.pending{
    background:var(--warning-dim);
    border-color:var(--warning-border);
    color:var(--warning);
}

.status-hero.expired{
    background:var(--danger-dim);
    border-color:var(--danger-border);
    color:var(--danger);
}

.status-hero i{
    font-size:10px;
}

/* Main Grid Layout */
.main-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
    margin-bottom:16px;
}

/* Cards */
.card{
    background:var(--bg-card);
    border:1.5px solid var(--border);
    border-radius:var(--radius);
    overflow:hidden;
    transition:var(--transition);
    display:flex;
    flex-direction:column;
}

.card:hover{
    border-color:var(--gold-border);
    box-shadow:var(--shadow);
}

.card-header-small{
    padding:14px 18px;
    border-bottom:1.5px solid var(--border);
    display:flex;
    align-items:center;
    gap:10px;
}

.card-header-small i{
    font-size:12px;
    color:var(--gold);
}

.card-header-small h3{
    font-family:'Montserrat',sans-serif;
    font-size:10px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:2.5px;
    color:var(--text-muted);
}

.card-body{
    padding:20px;
    flex:1;
    display:flex;
    flex-direction:column;
}

/* Booking Info - Left Side */
.info-list{
    display:flex;
    flex-direction:column;
    gap:0;
}

.info-row{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    padding:14px 0;
    border-bottom:1.5px solid var(--border);
    gap:16px;
}

.info-row:last-child{
    border-bottom:none;
}

.info-row:first-child{
    padding-top:0;
}

.info-label{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:10px;
    color:var(--text-muted);
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:1.5px;
    flex-shrink:0;
    padding-top:2px;
}

.info-label i{
    font-size:10px;
    color:var(--gold);
    width:16px;
    text-align:center;
}

.info-value{
    font-size:13px;
    font-weight:600;
    color:var(--text-primary);
    text-align:right;
    line-height:1.5;
    word-break:break-word;
}

.info-value.id{
    font-family:'Montserrat',sans-serif;
    font-size:12px;
    color:var(--text-muted);
    letter-spacing:1px;
}

.info-value.price{
    font-family:'Cormorant Garamond',serif;
    font-size:20px;
    font-weight:600;
    color:var(--gold);
    letter-spacing:-0.5px;
    line-height:1.2;
}

/* Package specific styling */
.info-value.package{
    font-size:13px;
    font-weight:600;
    color:var(--text-primary);
    max-width:200px;
    line-height:1.6;
}

/* Barber - Right Side */
.barber-profile{
    display:flex;
    align-items:center;
    gap:14px;
    margin-bottom:18px;
}

.barber-avatar-large{
    width:56px;
    height:56px;
    border-radius:var(--radius);
    overflow:hidden;
    background:var(--bg-input);
    border:1.5px solid var(--border);
    flex-shrink:0;
    transition:var(--transition);
}

.barber-avatar-large:hover{
    border-color:var(--gold-border);
}

.barber-avatar-large img{
    width:100%;
    height:100%;
    object-fit:cover;
    filter:grayscale(20%);
    transition:filter 0.4s ease;
}

.barber-avatar-large:hover img{
    filter:grayscale(0%);
}

.barber-avatar-large .placeholder{
    width:100%;
    height:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    color:var(--text-dim);
    font-size:24px;
}

.barber-info h4{
    font-family:'Cormorant Garamond',serif;
    font-size:20px;
    font-weight:400;
    margin-bottom:3px;
    letter-spacing:-0.5px;
}

.barber-info p{
    font-size:11px;
    color:var(--text-muted);
    line-height:1.5;
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
    line-clamp: 2;
}

/* Skill Bars */
.skills-section{
    margin-bottom:18px;
}

.skill-item{
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:8px;
}

.skill-item:last-child{
    margin-bottom:0;
}

.skill-name{
    font-size:10px;
    color:var(--text-muted);
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:1px;
    width:70px;
    flex-shrink:0;
}

.skill-bar-track{
    flex:1;
    height:3px;
    background:var(--bg-input);
    border-radius:50px;
    overflow:hidden;
}

.skill-bar-fill{
    height:100%;
    background:linear-gradient(90deg, var(--gold), var(--gold-light));
    border-radius:50px;
    transition:width 1s cubic-bezier(0.25,0.46,0.45,0.94);
}

.skill-value{
    font-size:10px;
    color:var(--gold);
    font-weight:700;
    width:30px;
    text-align:right;
}

/* Specialty Tags */
.specialty-tags{
    display:flex;
    flex-wrap:wrap;
    gap:6px;
    margin-bottom:16px;
}

.tag{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 12px;
    border-radius:var(--radius);
    background:var(--gold-dim);
    border:1.5px solid var(--gold-border);
    color:var(--gold);
    font-size:9px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:1px;
}

.tag i{
    font-size:8px;
}

/* Average Skill */
.avg-skill{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:12px 14px;
    background:var(--bg-input);
    border:1.5px solid var(--border);
    border-radius:var(--radius);
    margin-top:auto;
}

.avg-skill-label{
    font-size:10px;
    color:var(--text-muted);
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:1.5px;
}

.avg-skill-value{
    font-family:'Cormorant Garamond',serif;
    font-size:18px;
    font-weight:600;
    color:var(--gold);
    letter-spacing:-0.5px;
}

/* QR Section */
.qr-card{
    display:flex;
    align-items:center;
    gap:24px;
    padding:20px;
    background:var(--bg-card);
    border:1.5px solid var(--border);
    border-radius:var(--radius);
    margin-bottom:16px;
    transition:var(--transition);
}

.qr-card:hover{
    border-color:var(--gold-border);
}

.qr-left{
    flex-shrink:0;
}

.qr-code{
    width:120px;
    height:120px;
    border-radius:var(--radius);
    overflow:hidden;
    background:var(--bg-input);
    border:1.5px solid var(--border);
    padding:8px;
}

.qr-code img{
    width:100%;
    height:100%;
    object-fit:contain;
}

.qr-right{
    flex:1;
    display:flex;
    flex-direction:column;
    gap:10px;
}

.qr-title{
    font-family:'Cormorant Garamond',serif;
    font-size:22px;
    font-weight:400;
    letter-spacing:-0.5px;
}

.qr-desc{
    font-size:12px;
    color:var(--text-muted);
    line-height:1.6;
}

.qr-id{
    font-family:'Montserrat',sans-serif;
    font-size:11px;
    font-weight:600;
    color:var(--text-dim);
    letter-spacing:2px;
    text-transform:uppercase;
}

.share-link{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 18px;
    border-radius:var(--radius);
    background:transparent;
    border:1.5px solid var(--border);
    color:var(--text-muted);
    font-size:10px;
    font-weight:700;
    letter-spacing:1.5px;
    text-transform:uppercase;
    cursor:pointer;
    transition:var(--transition);
    position:relative;
    overflow:hidden;
    font-family:'Montserrat',sans-serif;
    width:fit-content;
}

.share-link::before{
    content:'';
    position:absolute;
    inset:0;
    background:var(--gold);
    transform:translateX(-100%);
    transition:transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}

.share-link:hover{
    color:var(--bg-primary);
    border-color:var(--gold);
}

.share-link:hover::before{
    transform:translateX(0);
}

.share-link i, .share-link span{
    position:relative;
    z-index:1;
}

.share-link i{
    font-size:10px;
}

/* Terms */
.terms-mini{
    background:var(--bg-card);
    border:1.5px solid var(--border);
    border-radius:var(--radius);
    padding:18px 20px;
    transition:var(--transition);
}

.terms-mini:hover{
    border-color:var(--gold-border);
}

.terms-header{
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:14px;
    color:var(--warning);
    font-size:10px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:2px;
}

.terms-header i{
    font-size:11px;
}

.terms-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
}

.term-item{
    display:flex;
    gap:10px;
    align-items:flex-start;
    font-size:11px;
    color:var(--text-muted);
    line-height:1.5;
}

.term-item i{
    color:var(--gold);
    font-size:9px;
    margin-top:3px;
    flex-shrink:0;
}

/* Footer */
.footer{
    text-align:center;
    padding-top:32px;
    margin-top:16px;
    border-top:1.5px solid var(--border);
}

.footer p{
    font-size:11px;
    color:var(--text-dim);
    letter-spacing:1px;
}

.footer span{
    color:var(--gold);
    font-weight:600;
}

/* Toast */
.toast{
    position:fixed;
    bottom:24px;
    right:24px;
    padding:14px 22px;
    background:var(--bg-card);
    border:1.5px solid var(--gold-border);
    border-radius:var(--radius);
    color:var(--text-primary);
    font-size:12px;
    font-weight:600;
    display:flex;
    align-items:center;
    gap:10px;
    box-shadow:var(--shadow-hover);
    transform:translateY(100px);
    opacity:0;
    transition:var(--transition);
    z-index:1000;
}

.toast.show{
    transform:translateY(0);
    opacity:1;
}

.toast i{
    color:var(--gold);
    font-size:12px;
}

/* Animations */
@keyframes fadeUp{
    from{opacity:0;transform:translateY(20px);}
    to{opacity:1;transform:translateY(0);}
}

.page-header{animation:fadeUp 0.5s ease forwards;}
.status-hero{animation:fadeUp 0.5s ease 0.1s both;}
.main-grid{animation:fadeUp 0.5s ease 0.2s both;}
.qr-card{animation:fadeUp 0.5s ease 0.3s both;}
.terms-mini{animation:fadeUp 0.5s ease 0.4s both;}

/* Mobile */
@media(max-width:640px){
    body{padding:16px;}
    .main-grid{grid-template-columns:1fr;}
    .page-header{flex-direction:column;gap:16px;align-items:flex-start;}
    .qr-card{flex-direction:column;text-align:center;}
    .qr-left{margin:0 auto;}
    .terms-grid{grid-template-columns:1fr;}
    .share-link{margin:0 auto;}
    .specialty-tags{justify-content:center;}
    .avg-skill{justify-content:center;gap:16px;}
    .barber-profile{justify-content:center;}
    .info-row{
        flex-direction:column;
        gap:8px;
        align-items:flex-start;
        padding:12px 0;
    }
    .info-value{
        text-align:left;
        max-width:100%;
    }
    .info-value.package{
        max-width:100%;
    }
    .info-value.price{font-size:18px;}
    .info-label{padding-top:0;}
}
</style>
</head>
<body>

<div class="container">

    <!-- Header -->
    <div class="page-header">
        <div class="page-header-left">
            <h1>Booking <em>Details.</em></h1>
            <p>Your appointment information</p>
        </div>
        <a href="history.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> <span>Back</span>
        </a>
    </div>

    <!-- Status -->
    <div class="status-hero <?= $booking['status'] ?>">
        <?php if($booking['status'] == 'paid'): ?>
        <i class="fas fa-check-circle"></i> Confirmed & Paid
        <?php elseif($booking['status'] == 'pending'): ?>
        <i class="fas fa-clock"></i> Awaiting Payment
        <?php else: ?>
        <i class="fas fa-times-circle"></i> Expired
        <?php endif; ?>
    </div>

    <!-- Main Grid: 2 Columns -->
    <div class="main-grid">

        <!-- Left: Booking Info -->
        <div class="card">
            <div class="card-header-small">
                <i class="fas fa-receipt"></i>
                <h3>Booking Info</h3>
            </div>
            <div class="card-body">
                <div class="info-list">
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-hashtag"></i> ID</div>
                        <div class="info-value id"><?= $orderId ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-box"></i> Package</div>
                        <div class="info-value package"><?= $booking['nama_paket'] ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-calendar"></i> Date</div>
                        <div class="info-value"><?= date('D, d M Y', strtotime($booking['tanggal'])) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-clock"></i> Time</div>
                        <div class="info-value"><?= substr($booking['jam'], 0, 5) ?> WIB</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-tag"></i> Price</div>
                        <div class="info-value price">Rp<?= number_format($booking['harga'], 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Barber Info -->
        <div class="card">
            <div class="card-header-small">
                <i class="fas fa-user-tie"></i>
                <h3>Your Barber</h3>
            </div>
            <div class="card-body">
                <div class="barber-profile">
                    <div class="barber-avatar-large">
                        <?php if(!empty($booking['barber_foto'])): ?>
                        <img src="admin/upload/<?= $booking['barber_foto'] ?>" alt="<?= $booking['barber_name'] ?>">
                        <?php else: ?>
                        <div class="placeholder"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="barber-info">
                        <h4><?= $booking['barber_name'] ?></h4>
                        <p><?= $booking['barber_bio'] ?></p>
                    </div>
                </div>

                <!-- Skill Bars -->
                <div class="skills-section">
                    <div class="skill-item">
                        <span class="skill-name">Fade</span>
                        <div class="skill-bar-track">
                            <div class="skill-bar-fill" style="width:<?= $booking['skill_fade'] ?>%"></div>
                        </div>
                        <span class="skill-value"><?= $booking['skill_fade'] ?>%</span>
                    </div>
                    <div class="skill-item">
                        <span class="skill-name">Scissor</span>
                        <div class="skill-bar-track">
                            <div class="skill-bar-fill" style="width:<?= $booking['skill_scissoring'] ?>%"></div>
                        </div>
                        <span class="skill-value"><?= $booking['skill_scissoring'] ?>%</span>
                    </div>
                    <div class="skill-item">
                        <span class="skill-name">Long</span>
                        <div class="skill-bar-track">
                            <div class="skill-bar-fill" style="width:<?= $booking['skill_longcut'] ?>%"></div>
                        </div>
                        <span class="skill-value"><?= $booking['skill_longcut'] ?>%</span>
                    </div>
                    <div class="skill-item">
                        <span class="skill-name">Short</span>
                        <div class="skill-bar-track">
                            <div class="skill-bar-fill" style="width:<?= $booking['skill_shortcut'] ?>%"></div>
                        </div>
                        <span class="skill-value"><?= $booking['skill_shortcut'] ?>%</span>
                    </div>
                    <div class="skill-item">
                        <span class="skill-name">Beard</span>
                        <div class="skill-bar-track">
                            <div class="skill-bar-fill" style="width:<?= $booking['skill_beardcut'] ?>%"></div>
                        </div>
                        <span class="skill-value"><?= $booking['skill_beardcut'] ?>%</span>
                    </div>
                </div>

                <!-- Specialty Tags -->
                <div class="specialty-tags">
                    <?php foreach($topSkills as $skill): ?>
                    <span class="tag"><i class="fas fa-star"></i> <?= $skill ?></span>
                    <?php endforeach; ?>
                </div>

                <!-- Average Skill -->
                <div class="avg-skill">
                    <span class="avg-skill-label">Overall Rating</span>
                    <span class="avg-skill-value"><?= $avgSkill ?>%</span>
                </div>
            </div>
        </div>

    </div>

    <?php if($booking['status'] === 'paid'): ?>
    <!-- QR Section — cuma muncul kalo udah bayar -->
    <div class="qr-card">
        <div class="qr-left">
            <div class="qr-code">
                <img src="<?= $qrUrl ?>" alt="QR Code">
            </div>
        </div>
        <div class="qr-right">
            <div class="qr-title">Scan to Download Receipt</div>
            <div class="qr-desc">Use your phone camera to scan this QR code. You'll be redirected to download your payment receipt instantly.</div>
            <div class="qr-id"><?= $orderId ?></div>
            <button class="share-link" onclick="copyBookingLink()">
                <i class="fas fa-share-alt"></i> <span>Copy Link</span>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Terms -->
    <div class="terms-mini">
        <div class="terms-header">
            <i class="fas fa-shield-alt"></i>
            <span>Terms & Conditions</span>
        </div>
        <div class="terms-grid">
            <div class="term-item">
                <i class="fas fa-clock"></i>
                <span>Arrive max 10 minutes late</span>
            </div>
            <div class="term-item">
                <i class="fas fa-ban"></i>
                <span>No-shows = forfeited</span>
            </div>
            <div class="term-item">
                <i class="fas fa-qrcode"></i>
                <span>Show QR at reception</span>
            </div>
            <div class="term-item">
                <i class="fas fa-phone"></i>
                <span>Contact to reschedule</span>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Hostel Barbershop &copy; <?= date('Y') ?> — Precision in <span>every cut</span></p>
    </div>

</div>

<!-- Toast -->
<div class="toast" id="toast">
    <i class="fas fa-check-circle"></i>
    <span id="toastMessage">Link copied!</span>
</div>

<script>
function copyBookingLink(){
    let url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
        showToast('Booking link copied!');
    }).catch(() => {
        showToast('Failed to copy');
    });
}

function showToast(msg){
    let toast = document.getElementById('toast');
    document.getElementById('toastMessage').textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

// Animate skill bars on load
window.addEventListener('load', () => {
    document.querySelectorAll('.skill-bar-fill').forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 100);
    });
});
</script>

</body>
</html>