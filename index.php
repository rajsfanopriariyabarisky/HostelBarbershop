<?php
/**
 * Hostel Barbershop — Public Landing Page
 */

include 'config.php';

function maskName($name) {
    $words = explode(' ', $name);
    $masked = [];
    foreach ($words as $word) {
        $len = strlen($word);
        if ($len <= 2) {
            $masked[] = $word;
        } else {
            $masked[] = substr($word, 0, 2) . str_repeat('*', $len - 2);
        }
    }
    return implode(' ', $masked);
}

/* ==================== FETCH DATA ==================== */

$today = date('Y-m-d');

// Random 3 barbers on duty today
$qRandomOnDuty = mysqli_query($conn, "
    SELECT b.*, 
    ROUND(((b.skill_fade + b.skill_scissoring + b.skill_longcut + b.skill_shortcut + b.skill_beardcut) / 5), 0) as avg_skill
    FROM barber b
    INNER JOIN jadwal_barber jb ON b.id = jb.barber_id
    WHERE jb.tanggal = '$today'
    ORDER BY RAND()
    LIMIT 3
");
$randomOnDutyCount = mysqli_num_rows($qRandomOnDuty);

// Fallback: if no one on duty today, pick random 3 from all barbers
if($randomOnDutyCount == 0) {
    $qRandomOnDuty = mysqli_query($conn, "
        SELECT *, 
        ROUND(((skill_fade + skill_scissoring + skill_longcut + skill_shortcut + skill_beardcut) / 5), 0) as avg_skill
        FROM barber 
        ORDER BY RAND()
        LIMIT 3
    ");
    $randomOnDutyCount = mysqli_num_rows($qRandomOnDuty);
}

$previewBarbers = [];
while($b = mysqli_fetch_assoc($qRandomOnDuty)){
    $previewBarbers[] = $b;
}

$qPackages = mysqli_query($conn, "SELECT * FROM paket ORDER BY harga ASC");

$ratingStats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total_reviews, ROUND(AVG(rating), 1) as avg_rating
    FROM barbershop_rating
"));
$totalReviews = $ratingStats['total_reviews'] ?? 0;
$avgRating = $ratingStats['avg_rating'] ?? 0;

$qReviews = mysqli_query($conn, "
    SELECT br.*, u.username, u.photo as user_photo
    FROM barbershop_rating br
    JOIN users u ON br.user_id = u.id
    ORDER BY br.created_at DESC
    LIMIT 2
");

// On Duty Today - ALL barbers on duty (for the On Duty section)
$qOnDuty = mysqli_query($conn, "
    SELECT b.id, b.nama, b.foto, b.keterangan
    FROM barber b
    INNER JOIN jadwal_barber jb ON b.id = jb.barber_id
    WHERE jb.tanggal = '$today'
    ORDER BY b.nama ASC
");
$onDutyCount = mysqli_num_rows($qOnDuty);

// Check which on-duty barbers are fully booked
$qJam = mysqli_query($conn, "SELECT * FROM jam_operasional WHERE status='Buka' ORDER BY jam_buka ASC");
$jamList = [];
while($j = mysqli_fetch_assoc($qJam)){
    $jamList[] = substr($j['jam_buka'], 0, 5);
}

$currentHour = date('H:i');
$barberBookedStatus = [];

if($onDutyCount > 0){
    mysqli_data_seek($qOnDuty, 0);
    while($duty = mysqli_fetch_assoc($qOnDuty)){
        $bookedJams = [];
        $qBookedSlots = mysqli_query($conn, "SELECT jam FROM booking WHERE barber_id=".$duty['id']." AND tanggal='$today' AND status IN ('paid','pending')");
        while($bs = mysqli_fetch_assoc($qBookedSlots)){
            $bookedJams[] = $bs['jam'];
        }
        $availableSlots = 0;
        foreach($jamList as $jam){
            if(!in_array($jam, $bookedJams) && $currentHour < $jam){
                $availableSlots++;
            }
        }
        $barberBookedStatus[$duty['id']] = ($availableSlots == 0);
    }
}

$totalBookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM booking WHERE status='paid'"))['total'] ?? 0;

$totalBarbersResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM barber");
$totalBarbers = mysqli_fetch_assoc($totalBarbersResult)['total'] ?? 0;

// Star distribution for reviews chart
$starCounts = [];
for($s=5; $s>=1; $s--){
    $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM barbershop_rating WHERE rating=$s"))['c'];
    $starCounts[$s] = $count;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hostel Barbershop — Premium Grooming Experience</title>
<meta name="description" content="Hostel Barbershop — Where precision meets style. Premium haircuts, beard grooming, and the ultimate barber experience in Jakarta.">

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
    cursor: none;
}

/* ===== CUSTOM CURSOR ===== */
.cursor {
    position: fixed;
    top: 0; left: 0;
    width: 10px; height: 10px;
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
    top: 0; left: 0;
    width: 36px; height: 36px;
    border: 1.5px solid rgba(232,200,122,0.5);
    border-radius: 50%;
    pointer-events: none;
    z-index: 99998;
    will-change: transform;
    transition: width 0.3s ease, height 0.3s ease, border-color 0.3s ease;
}
.cursor.hover { width: 18px; height: 18px; background: var(--gold-light); }
.cursor-follower.hover { width: 52px; height: 52px; border-color: rgba(232,200,122,0.7); }

/* ===== GRAIN OVERLAY ===== */
.grain {
    position: fixed;
    top: -10px; left: -10px;
    width: calc(100% + 20px); height: calc(100% + 20px);
    pointer-events: none;
    z-index: 9997;
    opacity: 0.035;
    animation: grainMove 8s steps(10) infinite;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
}
@keyframes grainMove {
    0%, 100% { transform: translate(0, 0); }
    10% { transform: translate(-5px, -5px); }
    20% { transform: translate(5px, 5px); }
    30% { transform: translate(-3px, 3px); }
    40% { transform: translate(3px, -3px); }
    50% { transform: translate(-5px, 2px); }
    60% { transform: translate(5px, -5px); }
    70% { transform: translate(-3px, 5px); }
    80% { transform: translate(3px, 3px); }
    90% { transform: translate(-5px, -2px); }
}

/* ===== SCROLL LINE ===== */
.scroll-line {
    position: absolute;
    left: 0; bottom: 0;
    width: 0%; height: 3px;
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
    /* TAMBAHIN INI ↓ */
    background: rgba(5,5,7,0.3);  /* transparan 30% */
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
.nav-wrapper.scrolled {
    background: rgba(5,5,7,0.85);
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
    height: 52px; width: auto;
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

.nav-login-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 32px;
    background: transparent;
    border: 1.5px solid var(--gold-border);
    color: var(--gold);
    text-decoration: none;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}
.nav-login-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}
.nav-login-btn:hover { color: var(--bg-primary); }
.nav-login-btn:hover::before { transform: scaleX(1); }
.nav-login-btn span { position: relative; z-index: 1; }

.nav-mobile-toggle {
    display: none;
    width: 44px; height: 44px;
    align-items: center; justify-content: center;
    background: transparent;
    border: 1.5px solid var(--border);
    color: var(--text-primary);
    font-size: 16px;
    cursor: none;
    transition: var(--transition);
}
.nav-mobile-toggle:hover { border-color: var(--gold-border); color: var(--gold); }

@media(max-width:900px) {
    .nav-links {
        display: none;
        position: absolute;
        top: 100%; left: 0; right: 0;
        background: rgba(5,5,7,0.98);
        border-bottom: 1px solid var(--border);
        padding: 24px 0;
        flex-direction: column;
        gap: 0;
    }
    .nav-links.show { display: flex; }
    .nav-login-btn { display: none; }
    .nav-mobile-toggle { display: flex; }
    .nav-inner { padding: 0 24px; }
    .nav-left-group { gap: 32px; }
}

/* ===== HERO ===== */
.hero {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    padding: 120px 48px 60px;
}
.hero-bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 80% 60% at 50% 0%, rgba(232,200,122,0.06) 0%, transparent 60%),
        radial-gradient(ellipse 40% 40% at 80% 80%, rgba(232,200,122,0.03) 0%, transparent 60%);
    pointer-events: none;
}
.hero-lines {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(232,200,122,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(232,200,122,0.04) 1px, transparent 1px);
    background-size: 100px 100px;
    pointer-events: none;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}
.hero-vertical-text {
    position: absolute;
    left: 24px;
    top: 50%;
    transform: translateY(-50%) rotate(-90deg);
    transform-origin: center center;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: var(--text-muted);
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 16px;
}
.hero-vertical-text::before {
    content: '';
    width: 40px;
    height: 1.5px;
    background: var(--text-muted);
}
.hero-content {
    max-width: 1000px;
    text-align: center;
    position: relative;
    z-index: 2;
}
.hero-eyebrow {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    margin-bottom: 32px;
    opacity: 0;
    animation: heroFadeUp 1s cubic-bezier(0.25,0.46,0.45,0.94) 0.2s forwards;
}
.hero-eyebrow-line {
    width: 60px;
    height: 1.5px;
    background: linear-gradient(90deg, transparent, var(--gold));
}
.hero-eyebrow-line.right {
    background: linear-gradient(90deg, var(--gold), transparent);
}
.hero-eyebrow-text {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: var(--gold);
}
.hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(56px, 9vw, 120px);
    font-weight: 300;
    letter-spacing: -2px;
    line-height: 0.95;
    margin-bottom: 32px;
    opacity: 0;
    animation: heroFadeUp 1.2s cubic-bezier(0.25,0.46,0.45,0.94) 0.4s forwards;
    text-shadow: 0 4px 30px rgba(0,0,0,0.5);
}
.hero-title em {
    font-style: italic;
    color: var(--gold);
    display: block;
}
.hero-divider {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 24px;
    margin-bottom: 24px;
    opacity: 0;
    animation: heroFadeUp 1s cubic-bezier(0.25,0.46,0.45,0.94) 0.6s forwards;
}
.hero-divider-line {
    flex: 1;
    max-width: 120px;
    height: 1.5px;
    background: var(--border-light);
}
.hero-divider-diamond {
    width: 8px; height: 8px;
    border: 1.5px solid var(--gold);
    transform: rotate(45deg);
}
.hero-desc {
    font-size: 16px;
    font-weight: 500;
    color: var(--text-secondary);
    max-width: 480px;
    margin: 0 auto 36px;
    line-height: 1.8;
    letter-spacing: 0.5px;
    opacity: 0;
    animation: heroFadeUp 1s cubic-bezier(0.25,0.46,0.45,0.94) 0.7s forwards;
}
.hero-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    opacity: 0;
    animation: heroFadeUp 1s cubic-bezier(0.25,0.46,0.45,0.94) 0.85s forwards;
}
.btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 16px 40px;
    background: var(--gold);
    color: var(--bg-primary);
    text-decoration: none;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    border: none;
}
.btn-primary::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold-light);
    transform: translateX(-100%);
    transition: transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 20px 56px rgba(232,200,122,0.3); }
.btn-primary:hover::before { transform: translateX(0); }
.btn-primary span, .btn-primary i { position: relative; z-index: 1; }
.btn-ghost {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 15px 40px;
    background: transparent;
    border: 1.5px solid var(--border-light);
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    transition: var(--transition);
}
.btn-ghost:hover { border-color: var(--gold-border); color: var(--gold); transform: translateY(-2px); }
.hero-stats {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 56px;
    padding-top: 36px;
    position: relative;
    opacity: 0;
    animation: heroFadeUp 1s cubic-bezier(0.25,0.46,0.45,0.94) 1s forwards;
}
.hero-stats::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 280px;
    height: 1.5px;
    background: linear-gradient(90deg, transparent, var(--border-light), transparent);
}
.hero-stat {
    flex: 1;
    text-align: center;
    padding: 0 32px;
    position: relative;
}
.hero-stat + .hero-stat::before {
    content: '';
    position: absolute;
    left: 0; top: 50%;
    transform: translateY(-50%);
    width: 1.5px; height: 40px;
    background: var(--border);
}
.hero-stat-value {
    font-family: 'Cormorant Garamond', serif;
    font-size: 44px;
    font-weight: 600;
    letter-spacing: -2px;
    line-height: 1;
    color: var(--gold);
    margin-bottom: 6px;
    text-shadow: 0 2px 20px rgba(232,200,122,0.2);
}
.hero-stat-label {
    font-size: 10px;
    color: var(--text-muted);
    font-weight: 600;
    letter-spacing: 2.5px;
    text-transform: uppercase;
}
@media(max-width:600px) {
    .hero { padding: 120px 24px 80px; }
    .hero-stats { gap: 0; }
    .hero-stat { padding: 0 16px; }
    .hero-stat-value { font-size: 36px; }
    .hero-vertical-text { display: none; }
}
@keyframes heroFadeUp {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ===== SECTION UTILS ===== */
.section {
    padding: 120px 48px;
    position: relative;
}
.section-inner {
    max-width: 1280px;
    margin: 0 auto;
}
.section-marker {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}
.section-marker-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 12px;
    font-weight: 600;
    color: var(--gold);
    letter-spacing: 2px;
}
.section-marker-line {
    width: 40px;
    height: 1.5px;
    background: var(--gold);
}
.section-marker-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--text-muted);
}
.section-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(44px, 6vw, 76px);
    font-weight: 400;
    letter-spacing: -2px;
    line-height: 1.05;
    margin-bottom: 24px;
}
.section-title em {
    font-style: italic;
    color: var(--gold);
}

/* ===== REVEAL ANIMATIONS ===== */
.reveal { opacity: 0; transform: translateY(60px); transition: opacity 0.9s cubic-bezier(0.25,0.46,0.45,0.94), transform 0.9s cubic-bezier(0.25,0.46,0.45,0.94); }
.reveal.visible { opacity: 1; transform: translateY(0); }
.reveal-left { opacity: 0; transform: translateX(-60px); transition: opacity 0.9s cubic-bezier(0.25,0.46,0.45,0.94), transform 0.9s cubic-bezier(0.25,0.46,0.45,0.94); }
.reveal-left.visible { opacity: 1; transform: translateX(0); }
.reveal-right { opacity: 0; transform: translateX(60px); transition: opacity 0.9s cubic-bezier(0.25,0.46,0.45,0.94), transform 0.9s cubic-bezier(0.25,0.46,0.45,0.94); }
.reveal-right.visible { opacity: 1; transform: translateX(0); }
.reveal-scale { opacity: 0; transform: scale(0.92); transition: opacity 0.9s cubic-bezier(0.25,0.46,0.45,0.94), transform 0.9s cubic-bezier(0.25,0.46,0.45,0.94); }
.reveal-scale.visible { opacity: 1; transform: scale(1); }
.delay-1 { transition-delay: 0.1s; }
.delay-2 { transition-delay: 0.2s; }
.delay-3 { transition-delay: 0.3s; }
.delay-4 { transition-delay: 0.4s; }
.delay-5 { transition-delay: 0.5s; }

/* ===== ABOUT / STORY ===== */
.about-section {
    background: var(--bg-secondary);
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
}
.about-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 80px;
    align-items: stretch;
}
.about-visual {
    position: relative;
    overflow: hidden;
    border-radius: var(--radius);
    border: none;
    width: 100%;
    height: 100%;
    min-height: 100%;
}
.about-visual-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    background: var(--bg-hover);
}
.about-visual-img img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center center;
    filter: grayscale(15%) contrast(1.05);
    transition: transform 0.8s cubic-bezier(0.25,0.46,0.45,0.94), filter 0.8s ease;
    display: block;
}
.about-visual:hover .about-visual-img img {
    transform: scale(1.03);
    filter: grayscale(0%) contrast(1.05);
}
.about-visual-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 50%, rgba(5,5,7,0.85) 100%);
    z-index: 2;
    pointer-events: none;
}
.about-visual-caption {
    position: absolute;
    bottom: 28px; left: 28px; right: 28px;
    z-index: 3;
}
.about-visual-caption-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(232,200,122,0.12);
    border: 1.5px solid var(--gold-border);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 12px;
}
.about-visual-caption-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 30px;
    font-weight: 400;
    line-height: 1.2;
    letter-spacing: -1px;
    color: var(--text-primary);
}

/* ===== JUSTIFY FIX ===== */
.about-content {
    width: 100%;
}
.about-content p {
    font-size: 15px;
    font-weight: 500;
    color: var(--text-secondary);
    line-height: 1.9;
    letter-spacing: 0.3px;
    margin-bottom: 24px;
    text-align: justify !important;
    text-justify: inter-word !important;
    -webkit-hyphens: auto;
    -moz-hyphens: auto;
    -ms-hyphens: auto;
    hyphens: auto;
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: normal;
    display: block;
    width: 100%;
}

.about-features {
    display: flex;
    flex-direction: column;
    gap: 0;
    margin-top: 40px;
    border-top: 1.5px solid var(--border);
}
.about-feature {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    padding: 24px 0;
    border-bottom: 1.5px solid var(--border);
    transition: var(--transition);
}
.about-feature:hover .about-feature-num {
    color: var(--gold);
}
.about-feature-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 22px;
    font-weight: 600;
    color: var(--text-muted);
    min-width: 32px;
    transition: var(--transition);
    padding-top: 2px;
}
.about-feature-text h4 {
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-bottom: 6px;
    color: var(--text-primary);
}
.about-feature-text p {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-muted);
    margin: 0;
    line-height: 1.7;
    letter-spacing: 0.3px;
    text-align: left;
}
@media(max-width:900px) {
    .about-grid { grid-template-columns: 1fr; gap: 48px; }
    .about-visual-img { aspect-ratio: 16/9; }
}

/* ===== PACKAGES ===== */
.packages-section {
    border-top: 1.5px solid var(--border);
}
.packages-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0;
    border: 1.5px solid var(--border);
}
.package-card {
    padding: 44px 32px;
    border-right: 1.5px solid var(--border);
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background: var(--bg-card);
}
.package-card:last-child { border-right: none; }
.package-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--gold);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.5s cubic-bezier(0.25,0.46,0.45,0.94);
}
.package-card:hover {
    background: var(--bg-hover);
}
.package-card:hover::before { transform: scaleX(1); }
.package-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 12px;
    color: var(--text-muted);
    letter-spacing: 2px;
    margin-bottom: 32px;
}
.package-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 24px;
    font-weight: 500;
    letter-spacing: -0.5px;
    margin-bottom: 16px;
    line-height: 1.2;
    color: var(--text-primary);
    flex: 1;
}
.package-price {
    font-family: 'Cormorant Garamond', serif;
    font-size: 32px;
    font-weight: 700;
    letter-spacing: -1px;
    color: var(--gold);
    margin-bottom: 28px;
}
.package-price small {
    font-size: 12px;
    color: var(--text-muted);
    font-family: 'Montserrat', sans-serif;
    font-weight: 400;
    letter-spacing: 0;
    display: block;
    margin-top: 4px;
}
.package-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 0;
    background: transparent;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    border-top: 1.5px solid var(--border);
    transition: var(--transition);
    margin-top: auto;
}
.package-btn i { font-size: 9px; transition: transform 0.3s ease; }
.package-card:hover .package-btn { color: var(--gold); border-color: var(--gold-border); }
.package-card:hover .package-btn i { transform: translateX(4px); }
@media(max-width:1100px) {
    .packages-grid { grid-template-columns: repeat(3,1fr); }
    .package-card:nth-child(3) { border-right: none; }
    .package-card:nth-child(4) { border-top: 1.5px solid var(--border); }
    .package-card:nth-child(5) { border-top: 1.5px solid var(--border); border-right: none; }
}
@media(max-width:700px) {
    .packages-grid { grid-template-columns: 1fr; }
    .package-card { border-right: none; border-bottom: 1.5px solid var(--border); }
}
/* ===== TEAM / ARTISTS - CLEAN & VISUALLY PLEASING ===== */
.artists-section {
    background: var(--bg-secondary);
    border-top: 1.5px solid var(--border);
    border-bottom: 1.5px solid var(--border);
}
.artists-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

/* Clean card with subtle elegance */
.artist-card {
    position: relative;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    text-decoration: none;
    color: var(--text-primary);
    display: block;
    transition: var(--transition);
    cursor: pointer;
}

.artist-card:hover {
    border-color: var(--gold-border);
    transform: translateY(-8px);
    box-shadow: 0 24px 56px rgba(0,0,0,0.55);
}

.artist-card-header {
    position: relative;
    aspect-ratio: 3/4;
    overflow: hidden;
    background: var(--bg-hover);
}

.artist-card-header img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: grayscale(30%) contrast(1.1);
    transition: transform 0.8s cubic-bezier(0.25,0.46,0.45,0.94), filter 0.6s ease;
}

.artist-card:hover .artist-card-header img {
    transform: scale(1.08);
    filter: grayscale(0%) contrast(1.15);
}

.artist-header-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 40%, rgba(5,5,7,0.95) 100%);
    z-index: 1;
    pointer-events: none;
    transition: background 0.5s ease;
}

.artist-card:hover .artist-header-overlay {
    background: linear-gradient(180deg, transparent 20%, rgba(5,5,7,0.98) 100%);
}

/* Availability badge */
.artist-availability-badge {
    position: absolute;
    top: 16px;
    left: 16px;
    z-index: 3;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: var(--success-dim);
    border: 1px solid var(--success-border);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--success);
    backdrop-filter: blur(8px);
}

.artist-availability-badge .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--success);
    animation: pulse 2s infinite;
}

/* Hover info - bio only, no button */
.artist-hover-info {
    position: absolute;
    inset: 0;
    background: rgba(5,5,7,0.88);
    backdrop-filter: blur(6px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 32px;
    opacity: 0;
    transition: opacity 0.5s ease;
    z-index: 4;
    text-align: center;
}

.artist-card:hover .artist-hover-info {
    opacity: 1;
}

.artist-hover-bio {
    font-size: 13px;
    color: var(--text-secondary);
    line-height: 1.9;
    display: -webkit-box;
    -webkit-line-clamp: 5;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-clamp: 2;
}

/* Card body info */
.artist-card-body {
    padding: 24px;
    position: relative;
    z-index: 2;
    margin-top: -60px;
    background: linear-gradient(180deg, transparent 0%, var(--bg-card) 30%);
}

.artist-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px;
    font-weight: 500;
    letter-spacing: -0.5px;
    line-height: 1.1;
    color: var(--text-primary);
    margin-bottom: 8px;
}

.artist-role {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: var(--gold);
    display: flex;
    align-items: center;
    gap: 10px;
}

.artist-role::before {
    content: '';
    width: 20px;
    height: 1.5px;
    background: var(--gold);
}

/* Specialty tags */
.artist-specialties {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 16px;
}

.artist-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: var(--gold-dim);
    border: 1px solid var(--gold-border);
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--gold);
}

.artists-footer {
    display: flex;
    justify-content: center;
    margin-top: 48px;
}

.link-elegant {
    display: inline-flex;
    align-items: center;
    gap: 16px;
    text-decoration: none;
    color: var(--text-secondary);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    transition: var(--transition);
    padding-bottom: 4px;
    position: relative;
}
.link-elegant::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0;
    width: 0%;
    height: 1.5px;
    background: var(--gold);
    transition: width 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}
.link-elegant:hover { color: var(--gold); }
.link-elegant:hover::after { width: 100%; }
.link-elegant i { font-size: 11px; transition: transform 0.3s ease; }
.link-elegant:hover i { transform: translateX(6px); }

@media(max-width:900px) {
    .artists-grid { grid-template-columns: 1fr 1fr; }
    .artist-card-header { aspect-ratio: 3/4; }
}
@media(max-width:600px) {
    .artists-grid { grid-template-columns: 1fr; }
}

/* ===== ON DUTY ===== */
.duty-section {
    border-top: 1.5px solid var(--border);
}
.duty-grid {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 64px;
    align-items: start;
}
.duty-text p {
    font-size: 15px;
    font-weight: 500;
    color: var(--text-secondary);
    line-height: 1.9;
    margin-bottom: 28px;
    max-width: 440px;
    text-align: justify;
    text-justify: inter-word;
    -webkit-hyphens: auto;
    -moz-hyphens: auto;
    -ms-hyphens: auto;
    hyphens: auto;
}

/* SLOTS AVAILABLE - GREEN */
.duty-status {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    border: 1.5px solid var(--success-border);
    background: var(--success-dim);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: var(--success);
    white-space: nowrap;
}
.duty-status-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--success);
    animation: pulse 2s infinite;
}
.duty-status.full {
    border-color: var(--danger-border);
    background: var(--danger-dim);
    color: var(--danger);
}
.duty-status.full .duty-status-dot {
    background: var(--danger);
    animation: pulse 1.5s infinite;
}
.duty-status.closed {
    border-color: rgba(232,100,100,0.25);
    background: rgba(232,100,100,0.08);
    color: var(--danger);
}
.duty-status.closed .duty-status-dot {
    background: var(--danger);
    animation: none;
}

.on-duty-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

/* On duty card - normal */
.on-duty-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 18px;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    transition: var(--transition);
    min-width: 0;
}
.on-duty-card:hover {
    border-color: var(--gold-border);
    background: var(--bg-hover);
    transform: translateY(-2px);
}

/* On duty card - FULLY BOOKED (strikethrough/eliminated) */
.on-duty-card.fully-booked {
    opacity: 0.45;
    border-color: rgba(232,100,100,0.2);
    background: rgba(232,100,100,0.04);
    position: relative;
}
.on-duty-card.fully-booked::after {
    content: '';
    position: absolute;
    inset: 0;
    background: repeating-linear-gradient(
        45deg,
        transparent,
        transparent 8px,
        rgba(232,100,100,0.06) 8px,
        rgba(232,100,100,0.06) 16px
    );
    pointer-events: none;
}
.on-duty-card.fully-booked .on-duty-name {
    text-decoration: line-through;
    text-decoration-color: var(--danger);
    text-decoration-thickness: 1.5px;
    color: var(--text-muted);
}
.on-duty-card.fully-booked .on-duty-sub {
    color: var(--danger);
    font-size: 9px;
}
.on-duty-card.fully-booked .on-duty-avatar,
.on-duty-card.fully-booked .on-duty-avatar-initial {
    filter: grayscale(80%) brightness(0.6);
    border-color: rgba(232,100,100,0.2);
}

.on-duty-avatar {
    width: 40px; height: 40px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    filter: grayscale(30%);
    border: 1.5px solid var(--border);
    transition: var(--transition);
}
.on-duty-avatar-initial {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: var(--bg-hover);
    border: 1.5px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Cormorant Garamond', serif;
    font-size: 18px;
    font-weight: 600;
    color: var(--gold);
    flex-shrink: 0;
    transition: var(--transition);
}
.on-duty-name {
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.3px;
    color: var(--text-primary);
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: var(--transition);
}
.on-duty-sub {
    font-size: 10px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-top: 2px;
    transition: var(--transition);
}
.duty-empty {
    padding: 40px 0;
    color: var(--text-muted);
    font-size: 13px;
    letter-spacing: 1px;
}
@media(max-width:900px) {
    .duty-grid { grid-template-columns: 1fr; gap: 40px; }
    .on-duty-cards { grid-template-columns: repeat(2, 1fr); }
}
@media(max-width:600px) {
    .on-duty-cards { grid-template-columns: 1fr; }
}

/* ===== REVIEWS ===== */
.reviews-section {
    background: var(--bg-secondary);
    border-top: 1.5px solid var(--border);
    border-bottom: 1.5px solid var(--border);
}
.reviews-grid {
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 80px;
    align-items: start;
}
.reviews-summary {
    position: sticky;
    top: 100px;
    padding: 44px;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
}
.reviews-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 104px;
    font-weight: 300;
    letter-spacing: -4px;
    line-height: 1;
    color: var(--gold);
    margin-bottom: 8px;
}
.reviews-stars { display: flex; gap: 4px; color: var(--gold); font-size: 16px; margin-bottom: 8px; }
.reviews-count {
    font-size: 11px;
    color: var(--text-muted);
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-bottom: 32px;
    padding-bottom: 28px;
    border-bottom: 1.5px solid var(--border);
}
.reviews-bars { display: flex; flex-direction: column; gap: 14px; }
.review-bar-row { display: flex; align-items: center; gap: 10px; }
.review-bar-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    min-width: 36px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.review-bar-label i { font-size: 9px; color: var(--gold); }
.review-bar-track {
    flex: 1; height: 3px;
    background: var(--border);
    overflow: hidden;
}
.review-bar-fill {
    height: 100%;
    background: var(--gold);
    transition: width 1.2s cubic-bezier(0.25,0.46,0.45,0.94);
}
.review-bar-count {
    font-size: 11px;
    color: var(--text-muted);
    min-width: 20px;
    text-align: right;
    font-weight: 600;
}
.review-cards { display: flex; flex-direction: column; gap: 16px; }
.review-card {
    padding: 36px;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    transition: var(--transition);
}
.review-card:hover {
    border-color: var(--gold-border);
    transform: translateY(-4px);
    box-shadow: 0 20px 56px rgba(0,0,0,0.5);
}
.review-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}
.review-user-info { display: flex; align-items: center; gap: 14px; }
.review-avatar {
    width: 48px; height: 48px;
    border: 1.5px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Cormorant Garamond', serif;
    font-size: 22px;
    font-weight: 600;
    color: var(--gold);
    background: var(--bg-hover);
    overflow: hidden;
    flex-shrink: 0;
}
.review-avatar img { width: 100%; height: 100%; object-fit: cover; filter: grayscale(20%); }
.review-username { font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 2px; }
.review-date { font-size: 10px; color: var(--text-muted); letter-spacing: 1px; text-transform: uppercase; }
.review-stars { color: var(--gold); font-size: 12px; letter-spacing: 2px; }
.review-text { font-size: 15px; font-weight: 500; color: var(--text-secondary); line-height: 1.8; font-style: italic; }
.review-quote-mark {
    font-family: 'Cormorant Garamond', serif;
    font-size: 52px;
    line-height: 0.5;
    color: var(--gold-border);
    display: block;
    margin-bottom: 8px;
}
@media(max-width:900px) {
    .reviews-grid { grid-template-columns: 1fr; gap: 40px; }
    .reviews-summary { position: static; }
    .reviews-num { font-size: 72px; }
}

/* ===== FOOTER ===== */
.footer {
    border-top: 1.5px solid var(--border);
    padding: 80px 48px 48px;
}
.footer-inner { max-width: 1280px; margin: 0 auto; }
.footer-top {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 64px;
    margin-bottom: 64px;
    padding-bottom: 64px;
    border-bottom: 1.5px solid var(--border);
}
.footer-brand-img {
    height: 52px;
    width: auto;
    display: block;
    margin-bottom: 20px;
    filter: brightness(0.75) contrast(1.1);
}
.footer-desc {
    font-size: 13px;
    color: var(--text-muted);
    line-height: 1.9;
    letter-spacing: 0.3px;
    max-width: 300px;
}
.footer-heading {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 1.5px solid var(--gold-border);
}
.footer-links { display: flex; flex-direction: column; gap: 14px; }
.footer-link {
    font-size: 12px;
    color: var(--text-muted);
    text-decoration: none;
    letter-spacing: 0.5px;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 10px;
}
.footer-link::before {
    content: '';
    width: 0;
    height: 1.5px;
    background: var(--gold);
    transition: width 0.3s ease;
}
.footer-link:hover { color: var(--text-primary); }
.footer-link:hover::before { width: 16px; }
.footer-bottom {
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
.footer-copyright span { color: var(--gold); }
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
@media(max-width:900px) {
    .footer-top { grid-template-columns: 1fr 1fr; gap: 40px; }
    .footer { padding: 60px 24px 40px; }
}
@media(max-width:600px) {
    .footer-top { grid-template-columns: 1fr; }
    .footer-bottom { flex-direction: column; text-align: center; }
}

/* ===== MISC ===== */
@keyframes pulse {
    0%,100%{opacity:1;transform:scale(1);}
    50%{opacity:0.4;transform:scale(0.7);}
}
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--bg-primary); }
::-webkit-scrollbar-thumb { background: var(--border-light); }
::-webkit-scrollbar-thumb:hover { background: var(--gold-border); }
@media(max-width:768px) {
    .section { padding: 80px 24px; }
}
.parallax-img { will-change: transform; }
</style>
</head>
<body>

<!-- Custom Cursor -->
<div class="cursor" id="cursor"></div>
<div class="cursor-follower" id="cursorFollower"></div>

<!-- Grain overlay -->
<div class="grain"></div>

<!-- Navigation -->
<nav class="nav-wrapper" id="mainNav">
    <div class="nav-inner">
        <div class="nav-left-group">
            <a href="index.php" class="nav-logo">
                <img src="hostel.png" alt="Hostel Barbershop" class="nav-logo-img">
            </a>
            <div class="nav-links" id="navLinks">
                <a href="index.php" class="nav-link active">Home</a>
                <a href="hair_artists.php" class="nav-link">Hair Artists</a>
                <a href="contact.php" class="nav-link">Contact</a>
                <a href="about.php" class="nav-link">About</a>
            </div>
        </div>
        <a href="login.php" class="nav-login-btn">
            <span>Sign In</span>
        </a>
        <button class="nav-mobile-toggle" id="mobileToggle" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    <div class="scroll-line" id="scrollLine"></div>
</nav>

<!-- Hero -->
<section class="hero" id="home">
    <div class="hero-bg"></div>
    <div class="hero-lines"></div>
    <div class="hero-vertical-text">Hostel Barbershop &mdash; Since 2020</div>
    <div class="hero-content">
        <div class="hero-eyebrow">
            <div class="hero-eyebrow-line"></div>
            <span class="hero-eyebrow-text">Premium Grooming Experience</span>
            <div class="hero-eyebrow-line right"></div>
        </div>
        <h1 class="hero-title">
            Timeless Style<br>
            <em>Modern Craft</em>
        </h1>
        <div class="hero-divider">
            <div class="hero-divider-line"></div>
            <div class="hero-divider-diamond"></div>
            <div class="hero-divider-line"></div>
        </div>
        <p class="hero-desc">
            Experience the art of grooming at Hostel Barbershop. Our hair artists combine traditional techniques with modern aesthetics to deliver cuts that define your character.
        </p>
        <div class="hero-actions">
            <a href="login.php" class="btn-primary">
                <span>Book Appointment</span>
                <i class="fas fa-arrow-right"></i>
            </a>
            <a href="#about" class="btn-ghost">
                <span>Discover More</span>
            </a>
        </div>
        <div class="hero-stats">
            <div class="hero-stat">
                <div class="hero-stat-value"><?= $totalBarbers ?></div>
                <div class="hero-stat-label">Hair Artists</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-value"><?= $totalBookings ?>+</div>
                <div class="hero-stat-label">Cuts Delivered</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-value"><?= $avgRating > 0 ? $avgRating : '5.0' ?></div>
                <div class="hero-stat-label">Star Rating</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-value"><?= count($jamList) ?></div>
                <div class="hero-stat-label">Daily Slots</div>
            </div>
        </div>
    </div>
</section>

<!-- About / Story -->
<section class="section about-section" id="about">
    <div class="section-inner">
        <div class="about-grid">
            <div class="reveal-left" style="height:100%;">
                <div class="about-visual">
                    <div class="about-visual-img parallax-img" data-parallax="0.15">
                        <img src="barbershop.png" alt="Hostel Barbershop" onerror="this.parentElement.style.background='var(--bg-hover)'">
                    </div>
                    <div class="about-visual-overlay"></div>
                    <div class="about-visual-caption">
                        <div class="about-visual-caption-tag">
                            <i class="fas fa-award"></i>
                            Since 2020
                        </div>
                        <div class="about-visual-caption-title">Jakarta's Finest<br>Barbershop</div>
                    </div>
                </div>
            </div>
            <div class="reveal-right">
                <div class="section-marker">
                    <span class="section-marker-num">01</span>
                    <div class="section-marker-line"></div>
                    <span class="section-marker-label">Our Story</span>
                </div>
                <h2 class="section-title">Crafted for the<br><em>Modern Gentleman</em></h2>
                <div class="about-content">
                    <p>Hostel Barbershop was founded with a singular vision: to redefine the barbershop experience in Jakarta. We believe a great haircut is more than just grooming &mdash; it is a statement of identity, confidence, and self-respect that speaks volumes before you even say a word.</p>
                    <p>Our team of six hair artists brings diverse expertise from across Indonesia. From the precision fades of Mr. Jatuy to the artistic scissoring of Mr. Valen, each artist specializes in distinct techniques to match your unique style and personality perfectly.</p>
                </div>
                <div class="about-features">
                    <div class="about-feature">
                        <div class="about-feature-num">01</div>
                        <div class="about-feature-text">
                            <h4>Precision Cutting</h4>
                            <p>Every cut is tailored to your face shape, hair texture, and personal style.</p>
                        </div>
                    </div>
                    <div class="about-feature">
                        <div class="about-feature-num">02</div>
                        <div class="about-feature-text">
                            <h4>Premium Experience</h4>
                            <p>Modern space with complimentary beverages and premium grooming products.</p>
                        </div>
                    </div>
                    <div class="about-feature">
                        <div class="about-feature-num">03</div>
                        <div class="about-feature-text">
                            <h4>Efficient Booking</h4>
                            <p>Book your slot online in under 30 seconds. No waiting, no hassle.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Packages -->
<section class="section packages-section" id="packages">
    <div class="section-inner">
        <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:48px;flex-wrap:wrap;gap:24px;">
            <div class="reveal">
                <div class="section-marker">
                    <span class="section-marker-num">02</span>
                    <div class="section-marker-line"></div>
                    <span class="section-marker-label">Services</span>
                </div>
                <h2 class="section-title" style="margin-bottom:0;">Our <em>Packages</em></h2>
            </div>
            <p class="reveal" style="font-size:13px;color:var(--text-muted);max-width:320px;line-height:1.8;text-align:right;margin-bottom:0;">From signature cuts to luxury packages &mdash; quality for every gentleman.</p>
        </div>
        <div class="packages-grid reveal">
            <?php
            $pkgNums = ['01','02','03','04','05'];
            $i = 0;
            mysqli_data_seek($qPackages, 0);
            while($pkg = mysqli_fetch_assoc($qPackages)):
            ?>
            <div class="package-card">
                <div class="package-num"><?= $pkgNums[$i] ?? '0'.($i+1) ?></div>
                <div class="package-name"><?= htmlspecialchars($pkg['nama_paket']) ?></div>
                <div class="package-price">
                    Rp<?= number_format($pkg['harga']) ?>
                    <small>per session</small>
                </div>
                <a href="login.php" class="package-btn">
                    Book Session <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <?php $i++; endwhile; ?>
        </div>
    </div>
</section>
<!-- Artists / Team - CLEAN & NICE -->
<section class="section artists-section" id="artists">
    <div class="section-inner">
        <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:64px;flex-wrap:wrap;gap:24px;">
            <div class="reveal">
                <div class="section-marker">
                    <span class="section-marker-num">03</span>
                    <div class="section-marker-line"></div>
                    <span class="section-marker-label">The Team</span>
                </div>
                <h2 class="section-title" style="margin-bottom:0;">Our Hair <em>Artists</em></h2>
            </div>
            <p class="reveal" style="font-size:13px;color:var(--text-muted);max-width:300px;line-height:1.8;margin-bottom:0;">Each artist brings unique expertise and passion to every cut.</p>
        </div>

        <div class="artists-grid">
            <?php foreach($previewBarbers as $idx => $barber): ?>
            <a href="hair_artists.php" class="artist-card reveal delay-<?= $idx+1 ?>">
                <div class="artist-card-header">
                    <?php if(!empty($barber['foto'])): ?>
                    <img src="admin/upload/<?= htmlspecialchars($barber['foto']) ?>" alt="<?= htmlspecialchars($barber['nama']) ?>" loading="lazy">
                    <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:64px;font-family:'Cormorant Garamond',serif;font-weight:300;">
                        <?= strtoupper(substr($barber['nama'], 3, 1)) ?>
                    </div>
                    <?php endif; ?>
                    <div class="artist-header-overlay"></div>
                    <div class="artist-availability-badge">
                        <div class="dot"></div>
                        On Duty Today
                    </div>
                    <!-- Hover overlay - bio only, NO button -->
                    <div class="artist-hover-info">
                        <div class="artist-hover-bio">
                            <?= htmlspecialchars($barber['keterangan']) ?>
                        </div>
                    </div>
                </div>

                <div class="artist-card-body">
                    <div class="artist-name"><?= htmlspecialchars($barber['nama']) ?></div>
                    <div class="artist-role">Hair Artist</div>
                    <div class="artist-specialties">
                        <span class="artist-tag"><i class="fas fa-check" style="font-size:7px;"></i>Certified</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="artists-footer reveal">
            <a href="hair_artists.php" class="link-elegant">
                <span>View All Artists</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- On Duty Today -->
<section class="section duty-section" id="schedule">
    <div class="section-inner">
        <div class="duty-grid">
            <div class="reveal-left">
                <div class="section-marker">
                    <span class="section-marker-num">04</span>
                    <div class="section-marker-line"></div>
                    <span class="section-marker-label">Availability</span>
                </div>
                <h2 class="section-title">On Duty <em>Today</em></h2>
                <p>Our hair artists are scheduled on a rotating basis to ensure you always have access to the best talent available. Walk-ins welcome &mdash; but booking online guarantees your slot.</p>
                <?php
                $totalAvailableSlots = 0;
                $totalBarbersOnDuty = $onDutyCount;
                $totalPossibleSlots = $totalBarbersOnDuty * count($jamList);

                if($totalBarbersOnDuty > 0){
                    mysqli_data_seek($qOnDuty, 0);
                    while($duty = mysqli_fetch_assoc($qOnDuty)){
                        $bookedJams = [];
                        $qBookedSlots = mysqli_query($conn, "SELECT jam FROM booking WHERE barber_id=".$duty['id']." AND tanggal='$today' AND status IN ('paid','pending')");
                        while($bs = mysqli_fetch_assoc($qBookedSlots)){
                            $bookedJams[] = $bs['jam'];
                        }
                        foreach($jamList as $jam){
                            if(!in_array($jam, $bookedJams) && $currentHour < $jam){
                                $totalAvailableSlots++;
                            }
                        }
                    }
                }

                $hasSlots = $totalAvailableSlots > 0;
                $isFullyBooked = ($totalPossibleSlots > 0 && $totalAvailableSlots == 0);
                $isClosed = $totalBarbersOnDuty == 0;
                ?>
            </div>

            <div class="reveal-right">
                <?php if($onDutyCount > 0): ?>
                <div class="duty-status <?= $isClosed ? 'closed' : ($isFullyBooked ? 'full' : '') ?>" style="margin-bottom:20px;">
                    <div class="duty-status-dot"></div>
                    <?php if($isClosed){ echo 'No Barbers On Duty'; }
                          elseif($isFullyBooked){ echo 'Fully Booked'; }
                          else { echo 'Slots Information'; }
                    ?>
                </div>
                <div class="on-duty-cards">
                    <?php
                    mysqli_data_seek($qOnDuty, 0);
                    while($duty = mysqli_fetch_assoc($qOnDuty)):
                        $isFullyBookedBarber = $barberBookedStatus[$duty['id']] ?? false;
                    ?>
                    <div class="on-duty-card <?= $isFullyBookedBarber ? 'fully-booked' : '' ?>">
                        <?php if(!empty($duty['foto'])): ?>
                        <img src="admin/upload/<?= htmlspecialchars($duty['foto']) ?>" alt="<?= htmlspecialchars($duty['nama']) ?>" class="on-duty-avatar">
                        <?php else: ?>
                        <div class="on-duty-avatar-initial"><?= strtoupper(substr($duty['nama'],0,1)) ?></div>
                        <?php endif; ?>
                        <div>
                            <div class="on-duty-name"><?= htmlspecialchars($duty['nama']) ?></div>
                            <div class="on-duty-sub"><?= $isFullyBookedBarber ? 'Fully Booked' : 'On Duty' ?></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="duty-empty">
                    <p>No Hair Artists are scheduled for today.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Reviews -->
<section class="section reviews-section" id="reviews">
    <div class="section-inner">
        <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:64px;flex-wrap:wrap;gap:24px;">
            <div class="reveal">
                <div class="section-marker">
                    <span class="section-marker-num">05</span>
                    <div class="section-marker-line"></div>
                    <span class="section-marker-label">Testimonials</span>
                </div>
                <h2 class="section-title" style="margin-bottom:0;">What Clients <em>Say</em></h2>
            </div>
            <a href="reviews.php" class="link-elegant reveal" style="margin-bottom:4px;">
                <span>View All Reviews</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="reviews-grid">
            <div class="reviews-summary reveal">
                <div class="reviews-num"><?= $avgRating > 0 ? $avgRating : '5.0' ?></div>
                <div class="reviews-stars">
                    <?php
                    $fullStars = floor($avgRating);
                    $halfStar = ($avgRating - $fullStars) >= 0.5 ? 1 : 0;
                    $emptyStars = 5 - $fullStars - $halfStar;
                    for($i=0; $i<$fullStars; $i++) echo '<i class="fas fa-star"></i>';
                    if($halfStar) echo '<i class="fas fa-star-half-alt"></i>';
                    for($i=0; $i<$emptyStars; $i++) echo '<i class="far fa-star"></i>';
                    if($avgRating == 0) for($i=0; $i<5; $i++) echo '<i class="fas fa-star"></i>';
                    ?>
                </div>
                <div class="reviews-count"><?= number_format($totalReviews) ?> verified reviews</div>

                <?php if($totalReviews > 0): ?>
                <div class="reviews-bars">
                    <?php foreach($starCounts as $star => $count):
                        $pct = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                    ?>
                    <div class="review-bar-row">
                        <div class="review-bar-label"><?= $star ?><i class="fas fa-star"></i></div>
                        <div class="review-bar-track">
                            <div class="review-bar-fill" style="width:<?= $pct ?>%"></div>
                        </div>
                        <div class="review-bar-count"><?= $count ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="review-cards">
                <?php if(mysqli_num_rows($qReviews) > 0):
                    $delay = 0;
                    mysqli_data_seek($qReviews, 0);
                    while($rev = mysqli_fetch_assoc($qReviews)):
                        $delay++;
                        $emptyStars = 5 - $rev['rating'];
                ?>
                <div class="review-card reveal delay-<?= $delay ?>">
                    <div class="review-header">
                        <div class="review-user-info">
                            <div class="review-avatar">
                                <?php if(!empty($rev['user_photo'])): ?>
                                <img src="<?= htmlspecialchars($rev['user_photo']) ?>" alt="">
                                <?php else: ?>
                                <?= strtoupper(substr($rev['username'],0,1)) ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="review-username"><?= htmlspecialchars(maskName($rev['username'])) ?></div>
                                <div class="review-date"><?= date('d M Y', strtotime($rev['created_at'])) ?></div>
                            </div>
                        </div>
                        <div class="review-stars">
                            <?php
                            for($s=0; $s<$rev['rating']; $s++) echo '<i class="fas fa-star"></i>';
                            for($s=0; $s<$emptyStars; $s++) echo '<i class="far fa-star"></i>';
                            ?>
                        </div>
                    </div>
                    <?php if(!empty($rev['review'])): ?>
                    <span class="review-quote-mark">"</span>
                    <div class="review-text"><?= htmlspecialchars($rev['review']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
                <?php else: ?>
                <div style="padding:48px;color:var(--text-muted);font-size:13px;letter-spacing:1px;border:1.5px solid var(--border);text-align:center;">
                    <p>Be the first to leave a review after your visit.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="footer-inner">
        <div class="footer-top">
            <div>
                <img src="hostel.png" alt="Hostel Barbershop" class="footer-brand-img">
                <p class="footer-desc">Jakarta's premier destination for precision grooming. Where every cut tells a story and every client leaves with confidence.</p>
            </div>
            <div>
                <div class="footer-heading">Navigate</div>
                <div class="footer-links">
                    <a href="index.php" class="footer-link">Home</a>
                    <a href="hair_artists.php" class="footer-link">Hair Artists</a>
                    <a href="contact.php" class="footer-link">Contact</a>
                    <a href="login.php" class="footer-link">Book Now</a>
                </div>
            </div>
            <div>
                <div class="footer-heading">Services</div>
                <div class="footer-links">
                    <a href="#packages" class="footer-link">Signature Haircut</a>
                    <a href="#packages" class="footer-link">Premium Haircut</a>
                    <a href="#packages" class="footer-link">Sultan Package</a>
                    <a href="#packages" class="footer-link">Ambatucut</a>
                </div>
            </div>
            <div>
                <div class="footer-heading">Connect</div>
                <div class="footer-links">
                    <a href="https://www.instagram.com/hostelbarber.inc" class="footer-link">Instagram</a>
                    <a href="https://www.instagram.com/hostelbarber.inc" class="footer-link">TikTok</a>
                    <a href="https://www.instagram.com/hostelbarber.inc" class="footer-link">WhatsApp</a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-copyright">
                &copy; <?= date('Y') ?> <span>Hostel Barbershop</span> &mdash; Precision in every cut
            </div>
            <div class="footer-social">
                <a href="https://www.instagram.com/hostelbarber.inc" class="footer-social-link" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="https://www.instagram.com/hostelbarber.inc" class="footer-social-link" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                <a href="https://www.instagram.com/hostelbarber.inc" class="footer-social-link" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
    </div>
<script>
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

document.querySelectorAll('a, button, .artist-card, .package-card, .on-duty-card').forEach(el => {
    el.addEventListener('mouseenter', () => {
        cursor.classList.add('hover');
        cursorFollower.classList.add('hover');
    }, { passive: true });
    el.addEventListener('mouseleave', () => {
        cursor.classList.remove('hover');
        cursorFollower.classList.remove('hover');
    }, { passive: true });
});

// ===== OPTIMIZED SCROLL SYSTEM =====
const scrollLine = document.getElementById('scrollLine');
const mainNav = document.getElementById('mainNav');
const parallaxEls = document.querySelectorAll('.parallax-img');

let parallaxData = [];
function updateParallaxCache() {
    parallaxData = [];
    parallaxEls.forEach(el => {
        const container = el.closest('.about-visual');
        if (container) {
            const rect = container.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            parallaxData.push({
                el: el,
                containerTop: rect.top + scrollTop,
                containerHeight: rect.height,
                speed: parseFloat(el.dataset.parallax || 0.1)
            });
        }
    });
}

updateParallaxCache();
window.addEventListener('resize', updateParallaxCache, { passive: true });

let lastScrollY = window.pageYOffset;
let scrollScheduled = false;

window.addEventListener('scroll', () => {
    lastScrollY = window.pageYOffset;
    if (!scrollScheduled) {
        scrollScheduled = true;
        requestAnimationFrame(tick);
    }
}, { passive: true });

function tick() {
    scrollScheduled = false;
    const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const pct = scrollHeight > 0 ? (lastScrollY / scrollHeight) * 100 : 0;
    scrollLine.style.width = pct + '%';
    mainNav.classList.toggle('scrolled', lastScrollY > 60);
    const windowCenter = window.innerHeight / 2;
    parallaxData.forEach(data => {
        const centerY = (data.containerTop - lastScrollY) + data.containerHeight / 2;
        const offset = (centerY - windowCenter) * data.speed;
        data.el.style.transform = `translate3d(0, ${offset}px, 0)`;
    });
}

// ===== MOBILE TOGGLE =====
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

// ===== SCROLL REVEAL =====
const revealEls = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');
const revealObs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -60px 0px' });
revealEls.forEach(el => revealObs.observe(el));

// ===== SMOOTH SCROLL FOR ANCHORS =====
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', e => {
        const href = anchor.getAttribute('href');
        if (href !== '#') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                const offset = mainNav.offsetHeight + 20;
                const top = target.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        }
    });
});
</script>
</body>
</html>