<?php
date_default_timezone_set('Asia/Jakarta');
include 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$userId = $user['id'];

/*
=====================================
AUTO EXPIRE BOOKING PENDING
=====================================
*/

mysqli_query($conn,"
UPDATE booking
SET status='expired'
WHERE status='pending'
AND created_at IS NOT NULL
AND TIMESTAMPDIFF(SECOND, created_at, NOW()) >= 30
");

/*
=====================================
FILTER & SEARCH
=====================================
*/

$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

/*
=====================================
PAGINATION SETUP
=====================================
*/

$perPage = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;

/*
=====================================
STATS QUERY
=====================================
*/

function countStatus($conn, $userId, $status) {
    $q = mysqli_query($conn, "SELECT COUNT(*) as total FROM booking WHERE user_id='$userId' AND status='$status'");
    $r = mysqli_fetch_assoc($q);
    return $r['total'] ?? 0;
}

$totalBookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM booking WHERE user_id='$userId'"))['total'] ?? 0;
$paidCount = countStatus($conn, $userId, 'paid');
$pendingCount = countStatus($conn, $userId, 'pending');
$expiredCount = countStatus($conn, $userId, 'expired');

/*
=====================================
COUNT TOTAL FILTERED RECORDS FOR PAGINATION
=====================================
*/

$countQuery = "
SELECT COUNT(*) as total
FROM booking b
JOIN paket p ON b.paket_id = p.id
JOIN barber br ON b.barber_id = br.id
WHERE b.user_id='$userId'
";

if($status){
    $countQuery .= " AND b.status='$status'";
}

if($search){
    $countQuery .= " AND (br.nama LIKE '%$search%' OR p.nama_paket LIKE '%$search%')";
}

$countResult = mysqli_query($conn, $countQuery);
$countRow = mysqli_fetch_assoc($countResult);
$totalFiltered = $countRow['total'] ?? 0;

$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
if($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

/*
=====================================
QUERY HISTORY WITH SEARCH & PAGINATION
=====================================
*/

$query = "
SELECT
    b.*,
    p.nama_paket,
    p.harga,
    br.nama as barber_name,
    br.foto as barber_foto
FROM booking b
JOIN paket p ON b.paket_id = p.id
JOIN barber br ON b.barber_id = br.id
WHERE b.user_id='$userId'
";

if($status){
    $query .= " AND b.status='$status'";
}

if($search){
    $query .= " AND (br.nama LIKE '%$search%' OR p.nama_paket LIKE '%$search%')";
}

$query .= " ORDER BY b.id DESC LIMIT $perPage OFFSET $offset";

$q = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>History | Hostel Barbershop</title>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
:root{
    --bg-primary:#050507;
    --bg-secondary:#0c0c10;
    --bg-tertiary:#13131a;
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
    overflow-x:hidden;
    -webkit-font-smoothing:antialiased;
}

/* ===== CONTAINER ===== */
.container{
    position:relative;
    z-index:1;
    max-width:1100px;
    margin:0 auto;
    padding:32px 28px;
    padding-bottom:60px;
}

/* ===== HEADER ===== */
.page-header{
    margin-bottom:32px;
    padding-bottom:24px;
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

.header-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:16px;
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

.page-header h1{
    font-family:'Cormorant Garamond',serif;
    font-size:40px;
    font-weight:300;
    letter-spacing:-2px;
    line-height:1.1;
    color:var(--text-primary);
}

.page-header h1 em{
    font-style:italic;
    color:var(--gold);
}

.page-header p{
    color:var(--text-muted);
    font-size:14px;
    margin-top:8px;
    font-weight:500;
}

/* ===== STATS GRID ===== */
.stats-grid{
    display:grid;
    grid-template-columns:repeat(4, 1fr);
    gap:16px;
    margin-bottom:36px;
}

.stat-card{
    background:var(--bg-card);
    border:1.5px solid var(--border);
    border-radius:var(--radius);
    padding:24px 20px;
    text-align:center;
    transition:var(--transition);
    position:relative;
    overflow:hidden;
    cursor:pointer;
    text-decoration:none;
    color:var(--text-primary);
}

.stat-card::before{
    content:'';
    position:absolute;
    top:0;
    left:0;
    right:0;
    height:3px;
    background:linear-gradient(90deg, transparent, var(--text-dim), transparent);
    opacity:0;
    transition:var(--transition);
}

.stat-card:hover{
    transform:translateY(-4px);
    border-color:var(--gold-border);
    box-shadow:var(--shadow);
}

.stat-card:hover::before{
    opacity:1;
}

.stat-card.active{
    border-color:var(--gold);
    box-shadow:0 0 30px rgba(232,200,122,0.08);
}

.stat-card.active::before{
    opacity:1;
    background:linear-gradient(90deg, transparent, var(--gold), transparent);
}

.stat-icon{
    width:44px;
    height:44px;
    border-radius:var(--radius);
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 12px;
    font-size:16px;
    transition:var(--transition);
}

.stat-icon.total{
    background:var(--bg-hover);
    color:var(--text-primary);
    border:1.5px solid var(--border);
}

.stat-icon.paid{
    background:var(--success-dim);
    color:var(--success);
    border:1.5px solid var(--success-border);
}

.stat-icon.pending{
    background:var(--warning-dim);
    color:var(--warning);
    border:1.5px solid var(--warning-border);
}

.stat-icon.expired{
    background:var(--danger-dim);
    color:var(--danger);
    border:1.5px solid var(--danger-border);
}

.stat-card h2{
    font-family:'Cormorant Garamond',serif;
    font-size:28px;
    font-weight:400;
    letter-spacing:-1px;
    margin-bottom:4px;
}

.stat-card span{
    font-size:10px;
    color:var(--text-muted);
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:2px;
}

/* ===== SEARCH & FILTER BAR ===== */
.control-bar{
    display:flex;
    align-items:center;
    gap:16px;
    margin-bottom:28px;
    flex-wrap:wrap;
}

.search-box{
    position:relative;
    flex:1;
    min-width:280px;
}

.search-box i{
    position:absolute;
    left:16px;
    top:50%;
    transform:translateY(-50%);
    color:var(--text-dim);
    font-size:12px;
    transition:var(--transition);
}

.search-box input:focus + i,
.search-box input:not(:placeholder-shown) + i{
    color:var(--gold);
}

.search-box input{
    width:100%;
    padding:14px 18px 14px 44px;
    background:var(--bg-input);
    border:1.5px solid var(--border);
    color:var(--text-primary);
    border-radius:var(--radius);
    font-size:13px;
    font-family:'Montserrat',sans-serif;
    outline:none;
    transition:var(--transition);
}

.search-box input:focus{
    border-color:var(--gold-border);
    box-shadow:0 0 0 4px var(--gold-dim);
}

.search-box input::placeholder{
    color:var(--text-dim);
}

.filter-chips{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.filter-chip{
    padding:10px 20px;
    border-radius:var(--radius);
    background:var(--bg-card);
    border:1.5px solid var(--border);
    color:var(--text-muted);
    text-decoration:none;
    font-size:11px;
    font-weight:700;
    letter-spacing:1.5px;
    text-transform:uppercase;
    transition:var(--transition);
    display:inline-flex;
    align-items:center;
    gap:8px;
    position:relative;
    overflow:hidden;
    font-family:'Montserrat',sans-serif;
}

.filter-chip::before{
    content:'';
    position:absolute;
    inset:0;
    background:var(--gold);
    transform:scaleX(0);
    transform-origin:left;
    transition:transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}

.filter-chip:hover{
    border-color:var(--gold-border);
    color:var(--text-primary);
}

.filter-chip.active{
    background:var(--gold);
    border-color:var(--gold);
    color:var(--bg-primary);
    box-shadow:0 4px 20px rgba(232,200,122,0.2);
}

.filter-chip.active::before{
    display:none;
}

.filter-chip i{
    font-size:10px;
    position:relative;
    z-index:1;
}

.filter-chip span{
    position:relative;
    z-index:1;
}

/* ===== BOOKING CARDS ===== */
.booking-list{
    display:flex;
    flex-direction:column;
    gap:20px;
}

.booking-card{
    background:var(--bg-card);
    border:1.5px solid var(--border);
    border-radius:var(--radius);
    overflow:hidden;
    transition:var(--transition);
    position:relative;
}

.booking-card::before{
    content:'';
    position:absolute;
    top:0;
    left:0;
    width:4px;
    height:100%;
    opacity:0;
    transition:var(--transition);
}

.booking-card:hover{
    transform:translateY(-4px);
    border-color:var(--gold-border);
    box-shadow:var(--shadow-hover);
}

.booking-card:hover::before{
    opacity:1;
}

.booking-card.paid::before{background:var(--success);}
.booking-card.pending::before{background:var(--warning);}
.booking-card.expired::before{background:var(--danger);}

.card-main{
    padding:24px;
    display:flex;
    gap:20px;
    align-items:flex-start;
}

@media(max-width:640px){
    .card-main{
        flex-direction:column;
        align-items:center;
        text-align:center;
    }
}

.barber-avatar{
    width:72px;
    height:72px;
    border-radius:var(--radius);
    overflow:hidden;
    background:var(--bg-input);
    border:1.5px solid var(--border);
    flex-shrink:0;
    position:relative;
    transition:var(--transition);
}

.booking-card:hover .barber-avatar{
    border-color:var(--gold-border);
    transform:scale(1.03);
}

.barber-avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
    filter:grayscale(20%);
    transition:filter 0.4s ease;
}

.booking-card:hover .barber-avatar img{
    filter:grayscale(0%);
}

.barber-avatar .avatar-placeholder{
    width:100%;
    height:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    color:var(--text-dim);
    font-size:28px;
}

.card-content{
    flex:1;
    min-width:0;
}

.card-header{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
    margin-bottom:12px;
    flex-wrap:wrap;
}

@media(max-width:640px){
    .card-header{
        flex-direction:column;
        align-items:center;
        text-align:center;
    }
}

.card-title{
    font-family:'Cormorant Garamond',serif;
    font-size:22px;
    font-weight:400;
    letter-spacing:-0.5px;
    margin-bottom:4px;
}

.card-subtitle{
    font-size:12px;
    color:var(--text-muted);
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
    font-weight:500;
}

.card-subtitle i{
    font-size:10px;
    color:var(--gold);
}

.status-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 14px;
    border-radius:var(--radius);
    font-size:10px;
    font-weight:700;
    letter-spacing:1.5px;
    text-transform:uppercase;
    flex-shrink:0;
}

.status-badge.paid{
    background:var(--success-dim);
    border:1.5px solid var(--success-border);
    color:var(--success);
}

.status-badge.pending{
    background:var(--warning-dim);
    border:1.5px solid var(--warning-border);
    color:var(--warning);
}

.status-badge.expired{
    background:var(--danger-dim);
    border:1.5px solid var(--danger-border);
    color:var(--danger);
}

.status-badge i{
    font-size:6px;
    animation:pulse 2s infinite;
}

@keyframes pulse{
    0%,100%{opacity:1;}
    50%{opacity:0.3;}
}

.card-details{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));
    gap:12px;
    margin-bottom:16px;
}

.detail-item{
    display:flex;
    align-items:center;
    gap:10px;
    padding:10px 14px;
    background:var(--bg-input);
    border:1.5px solid var(--border);
    border-radius:var(--radius);
    font-size:12px;
    color:var(--text-secondary);
    transition:var(--transition);
}

.detail-item:hover{
    border-color:var(--gold-border);
}

.detail-item i{
    font-size:11px;
    color:var(--gold);
    width:16px;
    text-align:center;
}

.detail-item .label{
    color:var(--text-muted);
    font-size:10px;
    font-weight:700;
    letter-spacing:1.5px;
    text-transform:uppercase;
    display:block;
    margin-bottom:2px;
}

.detail-item .value{
    color:var(--text-primary);
    font-weight:600;
}

.timer-box{
    display:flex;
    align-items:center;
    gap:10px;
    padding:12px 16px;
    background:var(--warning-dim);
    border:1.5px solid var(--warning-border);
    border-radius:var(--radius);
    margin-bottom:16px;
    font-size:12px;
    color:var(--warning);
    font-weight:600;
}

.timer-box i{
    font-size:12px;
    animation:pulse 2s ease-in-out infinite;
}

.timer-box .countdown{
    font-family:'Cormorant Garamond',serif;
    font-weight:600;
    font-size:16px;
    letter-spacing:1px;
}

.card-actions{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.card-actions.center{
    justify-content:center;
}

.btn{
    display:inline-flex;
    align-items:center;
    gap:10px;
    padding:12px 24px;
    border-radius:var(--radius);
    font-family:'Montserrat',sans-serif;
    font-size:10px;
    font-weight:700;
    letter-spacing:2.5px;
    text-transform:uppercase;
    text-decoration:none;
    cursor:pointer;
    transition:var(--transition);
    border:none;
    outline:none;
    position:relative;
    overflow:hidden;
}

.btn::before{
    content:'';
    position:absolute;
    inset:0;
    background:var(--gold-light);
    transform:scaleX(0);
    transform-origin:left;
    transition:transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}

.btn:hover::before{
    transform:scaleX(1);
}

.btn span, .btn i{
    position:relative;
    z-index:1;
}

.btn i{
    font-size:11px;
    transition:transform 0.3s ease;
}

.btn-primary{
    background:var(--gold);
    color:var(--bg-primary);
    box-shadow:0 4px 20px rgba(232,200,122,0.15);
}

.btn-primary:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 30px rgba(232,200,122,0.25);
}

.btn-primary:hover i{
    transform:translateX(3px);
}

.btn-secondary{
    background:transparent;
    color:var(--text-secondary);
    border:1.5px solid var(--border);
}

.btn-secondary::before{
    background:var(--gold);
}

.btn-secondary:hover{
    color:var(--bg-primary);
    border-color:var(--gold);
    transform:translateY(-2px);
}

.btn-secondary:hover i{
    transform:translateX(3px);
}

.btn-disabled{
    background:var(--bg-hover);
    color:var(--text-dim);
    border:1.5px solid var(--border);
    cursor:not-allowed;
}

.btn-disabled::before{
    display:none;
}

/* ===== PAGINATION ===== */
.pagination-wrap{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:16px;
    margin-top:36px;
    padding-top:28px;
    border-top:1.5px solid var(--border);
}

.pagination-info{
    font-size:11px;
    color:var(--text-muted);
    font-weight:500;
    letter-spacing:0.5px;
}

.pagination-info span{
    color:var(--gold);
    font-weight:700;
}

.pagination-nav{
    display:flex;
    align-items:center;
    gap:6px;
}

.pagination-nav a,
.pagination-nav span{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:36px;
    height:36px;
    padding:0 10px;
    border-radius:var(--radius);
    font-size:12px;
    font-weight:700;
    text-decoration:none;
    transition:var(--transition);
    font-family:'Montserrat',sans-serif;
}

.pagination-nav a{
    background:var(--bg-card);
    border:1.5px solid var(--border);
    color:var(--text-secondary);
}

.pagination-nav a:hover{
    border-color:var(--gold-border);
    color:var(--gold);
    transform:translateY(-2px);
}

.pagination-nav span.current{
    background:var(--gold);
    border:1.5px solid var(--gold);
    color:var(--bg-primary);
    box-shadow:0 4px 16px rgba(232,200,122,0.2);
}

.pagination-nav span.dots{
    background:transparent;
    border:none;
    color:var(--text-dim);
    min-width:auto;
    padding:0 4px;
    cursor:default;
}

.pagination-nav span.disabled{
    background:var(--bg-hover);
    border:1.5px solid var(--border);
    color:var(--text-dim);
    cursor:not-allowed;
}

/* ===== EMPTY STATE ===== */
.empty-state{
    text-align:center;
    padding:56px 32px 48px;
    background:var(--bg-card);
    border:1.5px solid var(--border);
    border-radius:var(--radius);
}

.empty-state-icon{
    width:56px;
    height:56px;
    border-radius:var(--radius);
    background:var(--gold-dim);
    border:1.5px solid var(--gold-border);
    display:inline-flex;
    align-items:center;
    justify-content:center;
    margin-bottom:20px;
    transition:var(--transition);
}

.empty-state:hover .empty-state-icon{
    transform:translateY(-2px);
    box-shadow:0 4px 20px rgba(232,200,122,0.1);
}

.empty-state-icon i{
    font-size:20px;
    color:var(--gold);
}

.empty-state h3{
    font-family:'Cormorant Garamond',serif;
    font-size:26px;
    font-weight:400;
    margin-bottom:10px;
    letter-spacing:-0.5px;
}

.empty-state p{
    color:var(--text-muted);
    font-size:13px;
    margin-bottom:28px;
    max-width:340px;
    margin-left:auto;
    margin-right:auto;
    line-height:1.7;
    font-weight:500;
}

.empty-state .btn{
    padding:10px 24px;
    font-size:10px;
    letter-spacing:2px;
    gap:8px;
}

.empty-state .btn i{
    font-size:10px;
}

/* ===== FOOTER ===== */
.footer{
    text-align:center;
    padding-top:40px;
    margin-top:20px;
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

/* ===== ANIMATIONS ===== */
@keyframes fadeUp{
    from{opacity:0;transform:translateY(20px);}
    to{opacity:1;transform:translateY(0);}
}

.stats-grid{animation:fadeUp 0.6s ease forwards;}
.control-bar{animation:fadeUp 0.6s ease 0.1s forwards;opacity:0;}
.booking-card{animation:fadeUp 0.5s ease forwards;opacity:0;}

.booking-card:nth-child(1){animation-delay:0.05s;}
.booking-card:nth-child(2){animation-delay:0.1s;}
.booking-card:nth-child(3){animation-delay:0.15s;}
.booking-card:nth-child(4){animation-delay:0.2s;}
.booking-card:nth-child(5){animation-delay:0.25s;}

/* ===== SCROLLBAR ===== */
::-webkit-scrollbar{width:6px;}
::-webkit-scrollbar-track{background:var(--bg-primary);}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px;}
::-webkit-scrollbar-thumb:hover{background:var(--gold-border);}

/* ===== MOBILE ===== */
@media(max-width:900px){
    .stats-grid{grid-template-columns:repeat(2, 1fr);}
    .container{padding:24px 20px;}
}

@media(max-width:640px){
    .stats-grid{grid-template-columns:repeat(2, 1fr);gap:12px;}
    .stat-card{padding:18px 14px;}
    .stat-card h2{font-size:24px;}
    .page-header h1{font-size:32px;}
    .control-bar{flex-direction:column;align-items:stretch;}
    .search-box{min-width:100%;}
    .filter-chips{overflow-x:auto;padding-bottom:4px;flex-wrap:nowrap;}
    .filter-chip{white-space:nowrap;}
    .card-details{grid-template-columns:1fr 1fr;}
    .card-actions{justify-content:center;}
    .empty-state{padding:44px 24px 36px;}
    .empty-state h3{font-size:22px;}
    .empty-state p{font-size:12px;}
    .empty-state .btn{padding:10px 20px;}
    .pagination-wrap{flex-direction:column;gap:12px;}
    .pagination-nav{flex-wrap:wrap;justify-content:center;}
}
</style>
</head>
<body>

<div class="container">

    <!-- HEADER -->
    <div class="page-header">
        <div class="header-top">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> <span>Back to Dashboard</span>
            </a>
        </div>
        <h1>History.</h1>
        <p>Track all your bookings, payments, and appointments in one place.</p>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
        <a href="history.php" class="stat-card <?= !$status ? 'active' : '' ?>">
            <div class="stat-icon total"><i class="fas fa-calendar"></i></div>
            <h2><?= $totalBookings ?></h2>
            <span>Total Bookings</span>
        </a>
        <a href="history.php?status=paid" class="stat-card <?= $status == 'paid' ? 'active' : '' ?>">
            <div class="stat-icon paid"><i class="fas fa-check-circle"></i></div>
            <h2><?= $paidCount ?></h2>
            <span>Paid</span>
        </a>
        <a href="history.php?status=pending" class="stat-card <?= $status == 'pending' ? 'active' : '' ?>">
            <div class="stat-icon pending"><i class="fas fa-clock"></i></div>
            <h2><?= $pendingCount ?></h2>
            <span>Pending</span>
        </a>
        <a href="history.php?status=expired" class="stat-card <?= $status == 'expired' ? 'active' : '' ?>">
            <div class="stat-icon expired"><i class="fas fa-times-circle"></i></div>
            <h2><?= $expiredCount ?></h2>
            <span>Expired</span>
        </a>
    </div>

    <!-- SEARCH & FILTER -->
    <div class="control-bar">
        <form method="GET" class="search-box" style="display:flex;align-items:center;">
            <input type="text" name="search" placeholder="Search by barber or package..." value="<?= htmlspecialchars($search) ?>">
            <i class="fas fa-search"></i>
            <?php if($status): ?>
            <input type="hidden" name="status" value="<?= $status ?>">
            <?php endif; ?>
        </form>

        <div class="filter-chips">
            <a href="history.php<?= $search ? '?search='.urlencode($search) : '' ?>" class="filter-chip <?= !$status ? 'active' : '' ?>">
                <i class="fas fa-layer-group"></i> <span>All</span>
            </a>
            <a href="history.php?status=pending<?= $search ? '&search='.urlencode($search) : '' ?>" class="filter-chip <?= $status == 'pending' ? 'active' : '' ?>">
                <i class="fas fa-clock"></i> <span>Pending</span>
            </a>
            <a href="history.php?status=paid<?= $search ? '&search='.urlencode($search) : '' ?>" class="filter-chip <?= $status == 'paid' ? 'active' : '' ?>">
                <i class="fas fa-check"></i> <span>Paid</span>
            </a>
            <a href="history.php?status=expired<?= $search ? '&search='.urlencode($search) : '' ?>" class="filter-chip <?= $status == 'expired' ? 'active' : '' ?>">
                <i class="fas fa-times"></i> <span>Expired</span>
            </a>
        </div>
    </div>

    <!-- BOOKING LIST -->
    <div class="booking-list">

        <?php if(mysqli_num_rows($q) > 0){ ?>

        <?php while($d = mysqli_fetch_assoc($q)){ 
            $cardClass = $d['status'];
            $createdTime = strtotime($d['created_at']);
            $expiryTime = $createdTime + 30;
            $remaining = max(0, $expiryTime - time());
        ?>

        <div class="booking-card <?= $cardClass ?>" data-booking-id="<?= $d['id'] ?>" data-created="<?= $createdTime ?>">

            <div class="card-main">

                <div class="barber-avatar">
                    <?php if(!empty($d['barber_foto'])){ ?>
                    <img src="admin/upload/<?= $d['barber_foto'] ?>" alt="<?= $d['barber_name'] ?>">
                    <?php } else { ?>
                    <div class="avatar-placeholder"><i class="fas fa-user"></i></div>
                    <?php } ?>
                </div>

                <div class="card-content">

                    <div class="card-header">
                        <div>
                            <div class="card-title"><?= $d['nama_paket'] ?></div>
                            <div class="card-subtitle">
                                <span><i class="fas fa-hashtag"></i> HSTL #<?= $d['id'] ?></span>
                                <span><i class="fas fa-user"></i> <?= $d['barber_name'] ?></span>
                            </div>
                        </div>
                        <span class="status-badge <?= $cardClass ?>">
                            <?php if($d['status'] == 'paid'): ?>
                            <i class="fas fa-check-circle"></i> Paid
                            <?php elseif($d['status'] == 'pending'): ?>
                            <i class="fas fa-clock"></i> Pending
                            <?php else: ?>
                            <i class="fas fa-times-circle"></i> Expired
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="card-details">
                        <div class="detail-item">
                            <div>
                                <div class="label"><i class="fas fa-calendar"></i> Date</div>
                                <div class="value"><?= date('d M Y', strtotime($d['tanggal'])) ?></div>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div>
                                <div class="label"><i class="fas fa-clock"></i> Time</div>
                                <div class="value"><?= substr($d['jam'], 0, 5) ?></div>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div>
                                <div class="label"><i class="fas fa-tag"></i> Price</div>
                                <div class="value">Rp<?= number_format($d['harga'], 0, ',', '.') ?></div>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div>
                                <div class="label"><i class="fas fa-clock-rotate-left"></i> Booked</div>
                                <div class="value"><?= date('d M H:i', strtotime($d['created_at'])) ?></div>
                            </div>
                        </div>
                    </div>

                    <?php if($d['status'] == 'pending' && $remaining > 0){ ?>
                    <div class="timer-box">
                        <i class="fas fa-hourglass-half"></i>
                        <span>Auto-expires in <span class="countdown" data-remaining="<?= $remaining ?>">00:00</span></span>
                    </div>
                    <?php } ?>

                    <div class="card-actions <?= ($d['status'] == 'expired') ? 'center' : '' ?>">
                        <?php if($d['status'] == 'pending'){ ?>
                        <a href="payment.php?id=<?= $d['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-credit-card"></i> <span>Pay Now</span>
                        </a>
                        <?php } elseif($d['status'] == 'paid'){ ?>
                        <a href="download.php?id=<?= $d['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-download"></i> <span>Download Receipt</span>
                        </a>
                        <a href="booking_detail.php?id=<?= $d['id'] ?>" class="btn btn-secondary">
                            <i class="fas fa-eye"></i> <span>Details</span>
                        </a>
                        <?php } else { ?>
                        <button class="btn btn-disabled" disabled>
                            <i class="fas fa-ban"></i> <span>Expired</span>
                        </button>
                        <?php } ?>
                    </div>

                </div>

            </div>

        </div>

        <?php } ?>

        <?php } else { ?>

        <!-- EMPTY STATE -->
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-inbox"></i>
            </div>
            <h3>No Bookings Found</h3>
            <p><?= $search ? 'No results matching your search. Try different keywords.' : 'You haven\'t made any bookings yet. Start by booking your first cut!' ?></p>
            <a href="booking.php" class="btn btn-primary">
                <i class="fas fa-cut"></i> <span>Book Now</span>
            </a>
        </div>

        <?php } ?>

    </div>

    <!-- PAGINATION -->
    <?php if($totalPages > 1 && mysqli_num_rows($q) > 0){ ?>
    <div class="pagination-wrap">
        <div class="pagination-info">
            Page <span><?php echo $page; ?></span> of <span><?php echo $totalPages; ?></span> &mdash; <span><?php echo $totalFiltered; ?></span> total
        </div>
        <div class="pagination-nav">
            <?php
            $baseParams = [];
            if($status) $baseParams[] = 'status=' . urlencode($status);
            if($search) $baseParams[] = 'search=' . urlencode($search);
            $baseQuery = $baseParams ? implode('&', $baseParams) : '';

            if($page > 1){
                $prevQuery = $baseQuery ? $baseQuery . '&page=' . ($page - 1) : 'page=' . ($page - 1);
                echo '<a href="history.php?' . $prevQuery . '"><i class="fas fa-chevron-left"></i></a>';
            } else {
                echo '<span class="disabled"><i class="fas fa-chevron-left"></i></span>';
            }

            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);

            if($startPage > 1){
                $firstQuery = $baseQuery ? $baseQuery . '&page=1' : 'page=1';
                echo '<a href="history.php?' . $firstQuery . '">1</a>';
                if($startPage > 2){
                    echo '<span class="dots">...</span>';
                }
            }

            for($i = $startPage; $i <= $endPage; $i++){
                if($i == $page){
                    echo '<span class="current">' . $i . '</span>';
                } else {
                    $pageQuery = $baseQuery ? $baseQuery . '&page=' . $i : 'page=' . $i;
                    echo '<a href="history.php?' . $pageQuery . '">' . $i . '</a>';
                }
            }

            if($endPage < $totalPages){
                if($endPage < $totalPages - 1){
                    echo '<span class="dots">...</span>';
                }
                $lastQuery = $baseQuery ? $baseQuery . '&page=' . $totalPages : 'page=' . $totalPages;
                echo '<a href="history.php?' . $lastQuery . '">' . $totalPages . '</a>';
            }

            if($page < $totalPages){
                $nextQuery = $baseQuery ? $baseQuery . '&page=' . ($page + 1) : 'page=' . ($page + 1);
                echo '<a href="history.php?' . $nextQuery . '"><i class="fas fa-chevron-right"></i></a>';
            } else {
                echo '<span class="disabled"><i class="fas fa-chevron-right"></i></span>';
            }
            ?>
        </div>
    </div>
    <?php } ?>

<!-- FOOTER -->
    <div class="footer">
        <p>Hostel Barbershop &copy; <?= date('Y') ?> — Precision in <span>every cut</span></p>
    </div>

</div>

<script>
// Countdown timer for pending bookings
function updateCountdowns(){
    document.querySelectorAll('.countdown').forEach(el => {
        let remaining = parseInt(el.dataset.remaining);
        if(remaining > 0){
            remaining--;
            el.dataset.remaining = remaining;

            let mins = Math.floor(remaining / 60);
            let secs = remaining % 60;
            el.textContent = String(mins).padStart(2,'0') + ':' + String(secs).padStart(2,'0');
        } else {
            el.textContent = '00:00';
            setTimeout(() => location.reload(), 1000);
        }
    });
}

if(document.querySelectorAll('.countdown').length > 0){
    setInterval(updateCountdowns, 1000);
    updateCountdowns();
}
</script>

</body>
</html>