<?php
/**
 * @var mysqli $conn Database connection from config.php
 */
date_default_timezone_set('Asia/Jakarta');  // ← FIX: Set timezone biar tanggal sesuai lokasi
include '../config.php';
global $conn;

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* BARBER */
$qBarber = mysqli_query($conn,"SELECT * FROM barber ORDER BY id DESC");

/* JAM */
$jamList = [];
$qJam = mysqli_query($conn,"SELECT * FROM jam_operasional ORDER BY jam_buka ASC");
while($j = mysqli_fetch_assoc($qJam)){
    $jamList[] = $j['jam_buka'];
}

/* ============================================
   DATE - REAL TIME ROLLING 7 DAYS FROM TODAY
   ============================================ */
$dates = [];
$now = time();
$today = date('Y-m-d', $now);

for($i = 0; $i < 7; $i++){
    $timestamp = strtotime("+$i day", $now);
    $date = date('Y-m-d', $timestamp);
    $diff = $i;
    
    $dates[] = [
        'full' => $date,
        'day'  => date('l', $timestamp),
        'show' => date('d M Y', $timestamp),
        'diff' => $diff
    ];
}

/* JADWAL - ambil dari DB */
$jadwal = [];
$qJadwal = mysqli_query($conn,"SELECT * FROM jadwal_barber");
while($j = mysqli_fetch_assoc($qJadwal)){
    $jadwal[] = $j['tanggal']."_".$j['barber_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Barber | Admin Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
:root{
    --bg-primary:#0d0d0d;
    --bg-secondary:#141414;
    --bg-tertiary:#1a1a1a;
    --bg-card:#1e1e1e;
    --bg-input:#181818;
    --bg-hover:#252525;
    --border:#333333;
    --border-light:#444444;
    --border-focus:#666666;
    --text-primary:#ffffff;
    --text-secondary:#c0c0c0;
    --text-muted:#777777;
    --accent:#ffffff;
    --danger:#ff4444;
    --danger-hover:#ff2222;
    --success:#00ff88;
    --shadow:0 4px 24px rgba(0,0,0,0.5);
    --shadow-hover:0 8px 40px rgba(0,0,0,0.6);
    --radius:18px;
    --radius-sm:12px;
    --radius-xs:8px;
    --transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:var(--bg-primary);
    color:var(--text-primary);
    font-family:'Inter',sans-serif;
    line-height:1.5;
    min-height:100vh;
    overflow-x:hidden;
}

.container{
    max-width:1440px;
    margin:0 auto;
    padding:40px 32px;
}

/* ===== HEADER ===== */
.page-header{
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    margin-bottom:48px;
    padding-bottom:32px;
    border-bottom:2px solid var(--border);
    position:relative;
}

.page-header::after{
    content:'';
    position:absolute;
    bottom:-2px;
    left:0;
    width:160px;
    height:2px;
    background:var(--text-primary);
}

.page-title h1{
    font-family:'Space Grotesk',sans-serif;
    font-size:40px;
    font-weight:700;
    letter-spacing:-2px;
    line-height:1.1;
    margin-bottom:8px;
}

.page-title h1 span{
    color:var(--text-muted);
    font-weight:300;
}

.page-title p{
    color:var(--text-secondary);
    font-size:15px;
    font-weight:400;
}

.back-btn{
    display:inline-flex;
    align-items:center;
    gap:10px;
    padding:12px 24px;
    border-radius:var(--radius-xs);
    background:transparent;
    border:2px solid var(--border-light);
    color:var(--text-secondary);
    text-decoration:none;
    font-size:13px;
    font-weight:600;
    letter-spacing:0.3px;
    transition:var(--transition);
    position:relative;
    overflow:hidden;
}

.back-btn::before{
    content:'';
    position:absolute;
    inset:0;
    background:var(--text-primary);
    transform:translateX(-100%);
    transition:transform 0.3s ease;
    z-index:-1;
}

.back-btn:hover{
    color:var(--bg-primary);
    border-color:var(--text-primary);
}

.back-btn:hover::before{
    transform:translateX(0);
}

.back-btn i{
    transition:transform 0.3s ease;
}

.back-btn:hover i{
    transform:translateX(-3px);
}

/* ===== SECTIONS ===== */
.section{
    background:var(--bg-secondary);
    border:2px solid var(--border);
    border-radius:var(--radius);
    padding:32px;
    margin-bottom:32px;
    position:relative;
    overflow:hidden;
    transition:var(--transition);
}

.section:hover{
    border-color:var(--border-light);
    box-shadow:var(--shadow-hover);
}

.section-header{
    display:flex;
    align-items:center;
    gap:16px;
    margin-bottom:28px;
}

.section-number{
    font-family:'Space Grotesk',sans-serif;
    font-size:11px;
    font-weight:700;
    color:var(--text-muted);
    letter-spacing:1px;
    text-transform:uppercase;
    padding:6px 12px;
    border:2px solid var(--border);
    border-radius:6px;
    background:var(--bg-tertiary);
}

.section-title-group{
    flex:1;
}

.section-title{
    font-family:'Space Grotesk',sans-serif;
    font-size:22px;
    font-weight:600;
    letter-spacing:-0.5px;
    color:var(--text-primary);
}

.section-subtitle{
    font-size:13px;
    color:var(--text-muted);
    margin-top:4px;
}

/* ===== FORMS ===== */
.form-group{
    margin-bottom:24px;
}

.form-label{
    display:block;
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:1.5px;
    color:var(--text-muted);
    margin-bottom:10px;
}

input[type="text"],
input[type="number"],
input[type="time"],
input[type="file"],
textarea{
    width:100%;
    background:var(--bg-input);
    border:2px solid var(--border);
    color:var(--text-primary);
    padding:14px 18px;
    border-radius:var(--radius-xs);
    font-size:14px;
    font-family:'Inter',sans-serif;
    outline:none;
    transition:var(--transition);
}

input:focus,
textarea:focus{
    border-color:var(--border-focus);
    box-shadow:0 0 0 4px rgba(255,255,255,0.08);
}

input::placeholder,
textarea::placeholder{
    color:var(--text-muted);
}

textarea{
    min-height:120px;
    resize:vertical;
    line-height:1.6;
}

input[type="file"]{
    padding:12px 16px;
    cursor:pointer;
    color:var(--text-secondary);
}

input[type="file"]::file-selector-button{
    background:var(--bg-tertiary);
    border:2px solid var(--border-light);
    color:var(--text-secondary);
    padding:8px 16px;
    border-radius:8px;
    margin-right:14px;
    cursor:pointer;
    font-weight:600;
    font-family:'Inter',sans-serif;
    transition:var(--transition);
}

input[type="file"]::file-selector-button:hover{
    background:var(--text-primary);
    color:var(--bg-primary);
    border-color:var(--text-primary);
}

/* ===== GRID ===== */
.form-grid{
    display:grid;
    grid-template-columns:repeat(5,1fr);
    gap:16px;
}

@media(max-width:1100px){
    .form-grid{grid-template-columns:repeat(3,1fr);}
}
@media(max-width:768px){
    .form-grid{grid-template-columns:repeat(2,1fr);}
}
@media(max-width:480px){
    .form-grid{grid-template-columns:1fr;}
}

/* ===== BUTTONS ===== */
.btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    padding:14px 28px;
    border:none;
    border-radius:var(--radius-xs);
    background:var(--text-primary);
    color:var(--bg-primary);
    font-family:'Space Grotesk',sans-serif;
    font-weight:700;
    font-size:14px;
    letter-spacing:0.5px;
    cursor:pointer;
    transition:var(--transition);
    position:relative;
    overflow:hidden;
}

.btn::after{
    content:'';
    position:absolute;
    inset:0;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent);
    transform:translateX(-100%);
    transition:transform 0.5s ease;
}

.btn:hover{
    transform:translateY(-2px);
    box-shadow:0 4px 20px rgba(255,255,255,0.15);
}

.btn:hover::after{
    transform:translateX(100%);
}

.btn:active{
    transform:translateY(0);
}

.btn-outline{
    background:transparent;
    border:2px solid var(--border-light);
    color:var(--text-secondary);
}

.btn-outline:hover{
    background:var(--text-primary);
    color:var(--bg-primary);
    border-color:var(--text-primary);
}

.btn-danger{
    background:transparent;
    border:2px solid rgba(255,68,68,0.4);
    color:var(--danger);
}

.btn-danger:hover{
    background:var(--danger);
    color:white;
    border-color:var(--danger);
    box-shadow:0 0 20px rgba(255,68,68,0.2);
}

.btn-sm{
    padding:10px 18px;
    font-size:12px;
}

/* ===== JAM ===== */
.jam-list{
    display:flex;
    flex-direction:column;
    gap:12px;
    margin-bottom:20px;
}

.jam-item{
    display:flex;
    gap:12px;
    align-items:center;
    background:var(--bg-input);
    border:2px solid var(--border);
    border-radius:var(--radius-xs);
    padding:10px 14px;
    transition:var(--transition);
}

.jam-item:hover{
    border-color:var(--border-light);
}

.jam-item input[type="time"]{
    flex:1;
    background:transparent;
    border:none;
    padding:4px 8px;
    margin:0;
    font-size:15px;
    font-weight:600;
    font-family:'Space Grotesk',sans-serif;
    color:var(--text-primary);
    letter-spacing:1px;
}

.jam-item input[type="time"]:focus{
    box-shadow:none;
}

/* ===== SCHEDULE ===== */
.schedule-list{
    display:flex;
    flex-direction:column;
    gap:16px;
}

.schedule-card{
    background:var(--bg-tertiary);
    border:2px solid var(--border);
    border-radius:var(--radius-sm);
    padding:20px;
    transition:var(--transition);
    position:relative;
}

.schedule-card:hover{
    border-color:var(--border-light);
}

.schedule-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:16px;
    padding-bottom:12px;
    border-bottom:2px solid var(--border);
}

.schedule-date{
    display:flex;
    flex-direction:column;
    gap:2px;
}

.schedule-date .date{
    font-family:'Space Grotesk',sans-serif;
    font-size:18px;
    font-weight:700;
    letter-spacing:-0.5px;
}

.schedule-date .day{
    font-size:11px;
    color:var(--text-muted);
    text-transform:uppercase;
    letter-spacing:2px;
    font-weight:600;
}

.schedule-badge{
    padding:4px 12px;
    border-radius:100px;
    font-size:10px;
    font-weight:700;
    letter-spacing:0.5px;
    text-transform:uppercase;
}

.badge-today{
    background:var(--text-primary);
    color:var(--bg-primary);
}

.badge-upcoming{
    background:var(--bg-input);
    border:2px solid var(--border);
    color:var(--text-secondary);
}

.check-grid{
    display:grid;
    grid-template-columns:repeat(6,1fr);
    gap:8px;
}

@media(max-width:1200px){
    .check-grid{grid-template-columns:repeat(3,1fr);}
}
@media(max-width:768px){
    .check-grid{grid-template-columns:repeat(2,1fr);}
}

.check-item{
    position:relative;
}

.check-item input[type="checkbox"]{
    position:absolute;
    opacity:0;
    width:0;
    height:0;
}

.check-item label{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:10px 8px;
    background:var(--bg-input);
    border:2px solid var(--border);
    border-radius:var(--radius-xs);
    cursor:pointer;
    font-size:12px;
    font-weight:600;
    color:var(--text-secondary);
    transition:var(--transition);
    text-align:center;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.check-item label::before{
    content:'';
    width:16px;
    height:16px;
    border:2px solid var(--border-light);
    border-radius:4px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:9px;
    color:var(--bg-primary);
    transition:var(--transition);
    flex-shrink:0;
    font-family:'Font Awesome 6 Free';
    font-weight:900;
}

.check-item input:checked + label{
    background:rgba(255,255,255,0.05);
    border-color:var(--text-primary);
    color:var(--text-primary);
}

.check-item input:checked + label::before{
    background:var(--text-primary);
    border-color:var(--text-primary);
    content:'\f00c';
}

.check-item label:hover{
    border-color:var(--border-light);
    background:var(--bg-hover);
}

/* ===== BARBER CARDS ===== */
.barber-grid{
    display:grid;
    grid-template-columns:repeat(6,1fr);
    gap:14px;
}

@media(max-width:1300px){
    .barber-grid{grid-template-columns:repeat(3,1fr);}
}
@media(max-width:768px){
    .barber-grid{grid-template-columns:repeat(2,1fr);}
}
@media(max-width:480px){
    .barber-grid{grid-template-columns:1fr;}
}

.barber-card{
    background:var(--bg-card);
    border:2px solid var(--border);
    border-radius:var(--radius);
    overflow:hidden;
    transition:var(--transition);
    position:relative;
}

.barber-card::after{
    content:'';
    position:absolute;
    top:0;
    left:0;
    right:0;
    height:2px;
    background:var(--text-primary);
    opacity:0;
    transition:opacity 0.3s ease;
}

.barber-card:hover{
    transform:translateY(-3px);
    border-color:var(--border-light);
    box-shadow:var(--shadow-hover);
}

.barber-card:hover::after{
    opacity:1;
}

.card-image{
    position:relative;
    width:100%;
    aspect-ratio:1 / 1;
    overflow:hidden;
    background:var(--bg-input);
}

.card-image img{
    width:100%;
    height:100%;
    object-fit:cover;
    object-position:center;
    transition:opacity 0.3s ease;
}

.card-image::after{
    content:'';
    position:absolute;
    bottom:0;
    left:0;
    right:0;
    height:30%;
    background:linear-gradient(to top,var(--bg-card),transparent);
}

.card-body{
    padding:14px;
}

.card-name{
    font-family:'Space Grotesk',sans-serif;
    font-size:14px;
    font-weight:700;
    letter-spacing:-0.3px;
    margin-bottom:10px;
    display:flex;
    align-items:center;
    gap:6px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.card-name::before{
    content:'';
    width:5px;
    height:5px;
    background:var(--text-primary);
    border-radius:50%;
    opacity:0.5;
    flex-shrink:0;
}

/* Skills */
.skill-group{
    margin-bottom:6px;
}

.skill-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:2px;
}

.skill-label{
    font-size:8px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:1px;
    color:var(--text-muted);
}

.skill-value{
    font-family:'Space Grotesk',sans-serif;
    font-size:10px;
    font-weight:700;
    color:var(--text-primary);
}

.skill-track{
    width:100%;
    height:2px;
    background:var(--bg-input);
    border-radius:10px;
    overflow:hidden;
    position:relative;
}

.skill-fill{
    height:100%;
    background:var(--text-primary);
    border-radius:10px;
    width:0;
    animation:skillLoad 1.2s cubic-bezier(0.4,0,0.2,1) forwards;
    position:relative;
}

.skill-fill::after{
    content:'';
    position:absolute;
    right:0;
    top:0;
    bottom:0;
    width:15px;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3));
}

@keyframes skillLoad{
    from{width:0;opacity:0;}
    to{width:var(--val);opacity:1;}
}

.card-desc{
    font-size:10px;
    color:var(--text-secondary);
    line-height:1.4;
    margin-top:10px;
    padding-top:10px;
    border-top:2px solid var(--border);
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
    line-clamp:2;
}

.card-actions{
    margin-top:10px;
    padding-top:10px;
    border-top:2px solid var(--border);
}

.btn-delete{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    width:100%;
    padding:6px;
    border-radius:var(--radius-xs);
    background:transparent;
    border:2px solid rgba(255,68,68,0.2);
    color:var(--danger);
    text-decoration:none;
    font-size:10px;
    font-weight:700;
    letter-spacing:0.5px;
    text-transform:uppercase;
    transition:var(--transition);
}

.btn-delete:hover{
    background:var(--danger);
    color:white;
    border-color:var(--danger);
    box-shadow:0 0 20px rgba(255,68,68,0.15);
}

/* ===== SCROLLBAR ===== */
::-webkit-scrollbar{
    width:6px;
}
::-webkit-scrollbar-track{
    background:var(--bg-primary);
}
::-webkit-scrollbar-thumb{
    background:var(--border);
    border-radius:3px;
}
::-webkit-scrollbar-thumb:hover{
    background:var(--border-light);
}

/* ===== ANIMATIONS ===== */
@keyframes fadeUp{
    from{opacity:0;transform:translateY(20px);}
    to{opacity:1;transform:translateY(0);}
}

.section{
    animation:fadeUp 0.5s ease forwards;
}

.section:nth-child(2){animation-delay:0.05s;}
.section:nth-child(3){animation-delay:0.1s;}
.section:nth-child(4){animation-delay:0.15s;}
.section:nth-child(5){animation-delay:0.2s;}

/* ===== DIVIDER ===== */
.divider{
    height:2px;
    background:var(--border);
    margin:32px 0;
}
</style>
</head>

<body>

<div class="container">

    <!-- HEADER -->
    <div class="page-header">
        <div class="page-title">
            <h1>Manage <span>Hair Artist.</span></h1>
            <p>Hair Artists management, schedule & operational hours</p>
        </div>
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
    </div>

    <!-- 01. ADD BARBER -->
    <div class="section">
        <div class="section-header">
            <span class="section-number">01</span>
            <div class="section-title-group">
                <div class="section-title">Add Hair Artist</div>
                <div class="section-subtitle">Fill in new hair artist data with skills & profile photo</div>
            </div>
        </div>

        <form method="POST" action="proses_tambah_barber.php" enctype="multipart/form-data">
            
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="nama" placeholder="e.g. Valentino Sirajagukguk" required>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Fade Skill (%)</label>
                    <input type="number" name="fade" placeholder="0 - 100" min="0" max="100" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Scissoring (%)</label>
                    <input type="number" name="scissoring" placeholder="0 - 100" min="0" max="100" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Long Cut (%)</label>
                    <input type="number" name="longcut" placeholder="0 - 100" min="0" max="100" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Short Cut (%)</label>
                    <input type="number" name="shortcut" placeholder="0 - 100" min="0" max="100" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Beard Cut (%)</label>
                    <input type="number" name="beardcut" placeholder="0 - 100" min="0" max="100" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description / Bio</label>
                <textarea name="keterangan" placeholder="Describe skills and experience..." required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Profile Photo</label>
                <input type="file" name="foto" accept="image/*" required>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-plus"></i> Add Hair Artist
            </button>
        </form>
    </div>

    <!-- 02. OPERATIONAL HOURS -->
    <div class="section">
        <div class="section-header">
            <span class="section-number">02</span>
            <div class="section-title-group">
                <div class="section-title">Operational Hours</div>
                <div class="section-subtitle">Set service time slots for the barbershop</div>
            </div>
        </div>

        <form method="POST" action="save_jam.php">
            <div class="jam-list" id="jamContainer">
                <?php foreach($jamList as $jam){ ?>
                <div class="jam-item">
                    <input type="time" name="jam[]" value="<?= $jam ?>" required>
                    <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <?php } ?>

                <?php if(count($jamList)==0){ ?>
                <div class="jam-item">
                    <input type="time" name="jam[]" required>
                    <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <?php } ?>
            </div>

            <div style="display:flex;gap:12px;align-items:center;">
                <button type="button" class="btn btn-outline btn-sm" onclick="tambahJam()">
                    <i class="fas fa-plus"></i> Add Time
                </button>
            </div>
            
            <div class="divider"></div>
            
            <button type="submit" class="btn">
                <i class="fas fa-save"></i> Save Hours
            </button>
        </form>
    </div>

    <!-- 03. SCHEDULE -->
    <div class="section">
        <div class="section-header">
            <span class="section-number">03</span>
            <div class="section-title-group">
                <div class="section-title">Hair Artist Schedule</div>
                <div class="section-subtitle">Assign on-duty hair artists for the next 7 days</div>
            </div>
        </div>

        <form method="POST" action="save_schedule.php">
            <div class="schedule-list">
                <?php foreach($dates as $date){ 
                    $badgeClass = '';
                    $badgeText = '';
                    if($date['diff'] == 0){
                        $badgeClass = 'badge-today';
                        $badgeText = 'Today';
                    } else {
                        $badgeClass = 'badge-upcoming';
                        $badgeText = $date['diff'] . ' days left';
                    }
                ?>
                <div class="schedule-card">
                    <div class="schedule-header">
                        <div class="schedule-date">
                            <span class="date"><?= $date['show'] ?></span>
                            <span class="day"><?= $date['day'] ?></span>
                        </div>
                        <span class="schedule-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                    </div>

                    <div class="check-grid">
                        <?php
                        $qBarber2 = mysqli_query($conn,"SELECT * FROM barber ORDER BY id DESC");
                        while($b = mysqli_fetch_assoc($qBarber2)){
                            $key = $date['full']."_".$b['id'];
                            $checked = in_array($key,$jadwal) ? "checked" : "";
                        ?>
                        <div class="check-item">
                            <input type="checkbox" name="jadwal[<?= $date['full'] ?>][]" value="<?= $b['id'] ?>" id="chk_<?= $key ?>" <?= $checked ?>>
                            <label for="chk_<?= $key ?>">
                                <?= $b['nama'] ?>
                            </label>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>
            </div>

            <div class="divider"></div>

            <button type="submit" class="btn">
                <i class="fas fa-save"></i> Save Schedule
            </button>
        </form>
    </div>

    <!-- 04. BARBER LIST -->
    <div class="section">
        <div class="section-header">
            <span class="section-number">04</span>
            <div class="section-title-group">
                <div class="section-title">Hair Artist List</div>
                <div class="section-subtitle"><?= mysqli_num_rows($qBarber) ?> active barbers in the system</div>
            </div>
        </div>

        <div class="barber-grid">
            <?php 
            mysqli_data_seek($qBarber, 0);
            while($d = mysqli_fetch_assoc($qBarber)){ 
            ?>
            <div class="barber-card">
                <div class="card-image">
                    <img src="upload/<?= $d['foto'] ?>" alt="<?= $d['nama'] ?>" loading="lazy">
                </div>
                <div class="card-body">
                    <div class="card-name"><?= $d['nama'] ?></div>

                    <div class="skill-group">
                        <div class="skill-header">
                            <span class="skill-label">Fade</span>
                            <span class="skill-value"><?= $d['skill_fade'] ?>%</span>
                        </div>
                        <div class="skill-track">
                            <div class="skill-fill" style="--val:<?= $d['skill_fade'] ?>%"></div>
                        </div>
                    </div>

                    <div class="skill-group">
                        <div class="skill-header">
                            <span class="skill-label">Scissor</span>
                            <span class="skill-value"><?= $d['skill_scissoring'] ?>%</span>
                        </div>
                        <div class="skill-track">
                            <div class="skill-fill" style="--val:<?= $d['skill_scissoring'] ?>%"></div>
                        </div>
                    </div>

                    <div class="skill-group">
                        <div class="skill-header">
                            <span class="skill-label">Long Cut</span>
                            <span class="skill-value"><?= $d['skill_longcut'] ?>%</span>
                        </div>
                        <div class="skill-track">
                            <div class="skill-fill" style="--val:<?= $d['skill_longcut'] ?>%"></div>
                        </div>
                    </div>

                    <div class="skill-group">
                        <div class="skill-header">
                            <span class="skill-label">Short Cut</span>
                            <span class="skill-value"><?= $d['skill_shortcut'] ?>%</span>
                        </div>
                        <div class="skill-track">
                            <div class="skill-fill" style="--val:<?= $d['skill_shortcut'] ?>%"></div>
                        </div>
                    </div>

                    <div class="skill-group">
                        <div class="skill-header">
                            <span class="skill-label">Beard</span>
                            <span class="skill-value"><?= $d['skill_beardcut'] ?>%</span>
                        </div>
                        <div class="skill-track">
                            <div class="skill-fill" style="--val:<?= $d['skill_beardcut'] ?>%"></div>
                        </div>
                    </div>

                    <div class="card-desc"><?= $d['keterangan'] ?></div>

                    <div class="card-actions">
                        <a href="hapus_barber.php?id=<?= $d['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this barber?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>

</div>

<script>
function tambahJam(){
    const container = document.getElementById('jamContainer');
    const div = document.createElement('div');
    div.className = 'jam-item';
    div.innerHTML = `
        <input type="time" name="jam[]" required>
        <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">
            <i class="fas fa-trash"></i>
        </button>
    `;
    container.appendChild(div);
    div.querySelector('input').focus();
    div.style.animation = 'fadeUp 0.3s ease';
}
</script>

</body>
</html>