<?php
/**
 * Hostel Barbershop — Hair Artists Page
 * Meet Our Hair Artists — Full cinematic experience with scroll-driven animations
 * Consistent with index.php aesthetic, optimized for smooth performance
 */

include 'config.php';

/* ==================== FETCH DATA ==================== */

$qBarbers = mysqli_query($conn, "
    SELECT *,
    ROUND(((skill_fade + skill_scissoring + skill_longcut + skill_shortcut + skill_beardcut) / 5), 0) as avg_skill
    FROM barber
    ORDER BY id ASC
");

$barbers = [];
while($b = mysqli_fetch_assoc($qBarbers)){
    $barbers[] = $b;
}

$ratingStats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total_reviews, ROUND(AVG(rating), 1) as avg_rating
    FROM barbershop_rating
"));
$totalReviews = $ratingStats['total_reviews'] ?? 0;
$avgRating = $ratingStats['avg_rating'] ?? 0;

$totalBookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM booking WHERE status='paid'"))['total'] ?? 0;
$totalBarbers = count($barbers);

$today = date('Y-m-d');
$qOnDuty = mysqli_query($conn, "
    SELECT b.id, b.nama, b.foto
    FROM barber b
    INNER JOIN jadwal_barber jb ON b.id = jb.barber_id
    WHERE jb.tanggal = '$today'
    ORDER BY b.nama ASC
");
$onDutyIds = [];
while($od = mysqli_fetch_assoc($qOnDuty)){
    $onDutyIds[] = $od['id'];
}

$qJam = mysqli_query($conn, "SELECT * FROM jam_operasional WHERE status='Buka' ORDER BY jam_buka ASC");
$jamList = [];
while($j = mysqli_fetch_assoc($qJam)){
    $jamList[] = substr($j['jam_buka'], 0, 5);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meet Our Hair Artists — Hostel Barbershop</title>
<meta name="description" content="Meet the master barbers behind Hostel Barbershop. Discover their unique skills, specialties, and book your preferred artist.">

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
    --gold-glow: rgba(232, 200, 122, 0.15);
    --success: #6ee7a0;
    --danger: #e88484;
    --radius: 2px;
    --radius-sm: 2px;
    --transition: all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
    --ease-out-expo: cubic-bezier(0.16, 1, 0.3, 1);
    --ease-smooth: cubic-bezier(0.25,0.46,0.45,0.94);
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
    width: calc(100% + 20px);
    height: calc(100% + 20px);
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
    transition: transform 0.4s var(--ease-smooth);
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
    transition: transform 0.4s var(--ease-smooth);
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

/* ===== PAGE HERO ===== */
.page-hero {
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    padding: 140px 48px 80px;
}

.page-hero-bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 60% 50% at 30% 20%, rgba(232,200,122,0.05) 0%, transparent 60%),
        radial-gradient(ellipse 40% 40% at 80% 80%, rgba(232,200,122,0.03) 0%, transparent 60%);
    pointer-events: none;
}

.page-hero-lines {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(232,200,122,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(232,200,122,0.03) 1px, transparent 1px);
    background-size: 120px 120px;
    pointer-events: none;
    mask-image: radial-gradient(ellipse at center, black 15%, transparent 75%);
}

.page-hero-content {
    max-width: 900px;
    text-align: center;
    position: relative;
    z-index: 2;
}

.page-hero-eyebrow {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    margin-bottom: 32px;
    opacity: 0;
    animation: heroFadeUp 1s var(--ease-smooth) 0.2s forwards;
}

.page-hero-eyebrow-line {
    width: 60px;
    height: 1.5px;
    background: linear-gradient(90deg, transparent, var(--gold));
}

.page-hero-eyebrow-line.right {
    background: linear-gradient(90deg, var(--gold), transparent);
}

.page-hero-eyebrow-text {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: var(--gold);
}

.page-hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(48px, 8vw, 100px);
    font-weight: 300;
    letter-spacing: -2px;
    line-height: 0.95;
    margin-bottom: 28px;
    opacity: 0;
    animation: heroFadeUp 1.2s var(--ease-smooth) 0.4s forwards;
    text-shadow: 0 4px 30px rgba(0,0,0,0.5);
}

.page-hero-title em {
    font-style: italic;
    color: var(--gold);
}

.page-hero-divider {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 24px;
    margin-bottom: 24px;
    opacity: 0;
    animation: heroFadeUp 1s var(--ease-smooth) 0.6s forwards;
}

.page-hero-divider-line {
    flex: 1;
    max-width: 120px;
    height: 1.5px;
    background: var(--border-light);
}

.page-hero-divider-diamond {
    width: 8px; height: 8px;
    border: 1.5px solid var(--gold);
    transform: rotate(45deg);
}

.page-hero-desc {
    font-size: 16px;
    font-weight: 500;
    color: var(--text-secondary);
    max-width: 520px;
    margin: 0 auto;
    line-height: 1.9;
    letter-spacing: 0.5px;
    opacity: 0;
    animation: heroFadeUp 1s var(--ease-smooth) 0.7s forwards;
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
.reveal { opacity: 0; transform: translateY(60px); transition: opacity 0.9s var(--ease-smooth), transform 0.9s var(--ease-smooth); }
.reveal.visible { opacity: 1; transform: translateY(0); }
.reveal-left { opacity: 0; transform: translateX(-60px); transition: opacity 0.9s var(--ease-smooth), transform 0.9s var(--ease-smooth); }
.reveal-left.visible { opacity: 1; transform: translateX(0); }
.reveal-right { opacity: 0; transform: translateX(60px); transition: opacity 0.9s var(--ease-smooth), transform 0.9s var(--ease-smooth); }
.reveal-right.visible { opacity: 1; transform: translateX(0); }
.reveal-scale { opacity: 0; transform: scale(0.92); transition: opacity 0.9s var(--ease-smooth), transform 0.9s var(--ease-smooth); }
.reveal-scale.visible { opacity: 1; transform: scale(1); }
.delay-1 { transition-delay: 0.1s; }
.delay-2 { transition-delay: 0.2s; }
.delay-3 { transition-delay: 0.3s; }
.delay-4 { transition-delay: 0.4s; }
.delay-5 { transition-delay: 0.5s; }
.delay-6 { transition-delay: 0.6s; }

/* ===== ARTISTS SHOWCASE ===== */
.artists-showcase {
    background: var(--bg-secondary);
    border-top: 1.5px solid var(--border);
    border-bottom: 1.5px solid var(--border);
    padding: 120px 48px;
    position: relative;
    overflow: hidden;
}

.artists-showcase::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--gold-border), transparent);
    opacity: 0.5;
}

.artists-showcase-inner {
    max-width: 1280px;
    margin: 0 auto;
}

.artists-showcase-header {
    text-align: center;
    margin-bottom: 100px;
}

.artists-showcase-header .section-marker {
    justify-content: center;
    margin-bottom: 24px;
}

.artists-showcase-header .section-title {
    margin-bottom: 20px;
}

.artists-showcase-header p {
    font-size: 15px;
    color: var(--text-secondary);
    max-width: 500px;
    margin: 0 auto;
    line-height: 1.9;
}

/* ===== ARTIST ROW ===== */
.artist-showcase-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    border: 1.5px solid var(--border);
    margin-bottom: 48px;
    background: var(--bg-card);
    position: relative;
    overflow: hidden;
}

.artist-showcase-row:last-child {
    margin-bottom: 0;
}

.artist-showcase-row:nth-child(even) {
    direction: rtl;
}

.artist-showcase-row:nth-child(even) > * {
    direction: ltr;
}

.artist-showcase-photo {
    position: relative;
    overflow: hidden;
    min-height: 520px;
    background: var(--bg-hover);
}

.artist-showcase-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: grayscale(30%) contrast(1.1);
    transition: transform 1.2s var(--ease-out-expo), filter 0.8s ease;
    display: block;
}

.artist-showcase-row:hover .artist-showcase-photo img {
    transform: scale(1.05);
    filter: grayscale(0%) contrast(1.1);
}

.artist-showcase-photo-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, rgba(5,5,7,0.6) 0%, transparent 50%);
    z-index: 1;
    pointer-events: none;
}

.artist-showcase-row:nth-child(even) .artist-showcase-photo-overlay {
    background: linear-gradient(270deg, rgba(5,5,7,0.6) 0%, transparent 50%);
}

.artist-showcase-photo-frame {
    position: absolute;
    inset: 20px;
    border: 1px solid rgba(232,200,122,0.2);
    z-index: 2;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.6s ease;
}

.artist-showcase-row:hover .artist-showcase-photo-frame {
    opacity: 1;
}

.artist-showcase-photo-num {
    position: absolute;
    top: 40px;
    left: 40px;
    font-family: 'Cormorant Garamond', serif;
    font-size: 72px;
    font-weight: 300;
    color: rgba(232,200,122,0.15);
    line-height: 1;
    z-index: 3;
    transition: color 0.4s ease;
}

.artist-showcase-row:nth-child(even) .artist-showcase-photo-num {
    left: auto;
    right: 40px;
}

.artist-showcase-row:hover .artist-showcase-photo-num {
    color: rgba(232,200,122,0.3);
}

.artist-showcase-info {
    padding: 60px 56px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
}

.artist-showcase-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: var(--gold-dim);
    border: 1.5px solid var(--gold-border);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 24px;
    width: fit-content;
}

.artist-showcase-badge.on-duty::before {
    content: '';
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--gold);
    animation: pulse 2s infinite;
}

.artist-showcase-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(36px, 4vw, 56px);
    font-weight: 400;
    letter-spacing: -1px;
    line-height: 1.05;
    margin-bottom: 8px;
    color: var(--text-primary);
}

.artist-showcase-role {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.artist-showcase-role::after {
    content: '';
    flex: 1;
    max-width: 60px;
    height: 1.5px;
    background: var(--gold-border);
}

.artist-showcase-bio {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-secondary);
    line-height: 1.9;
    margin-bottom: 36px;
    max-width: 420px;
}

/* ===== SKILL BARS ===== */
.artist-showcase-skills {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 36px;
}

.skill-showcase-row {
    display: flex;
    align-items: center;
    gap: 16px;
}

.skill-showcase-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--text-muted);
    min-width: 80px;
}

.skill-showcase-track {
    flex: 1;
    height: 4px;
    background: rgba(255,255,255,0.06);
    position: relative;
    overflow: hidden;
    border-radius: 2px;
}

.skill-showcase-fill {
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 0%;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
    border-radius: 2px;
    transition: width 1.4s var(--ease-out-expo);
    box-shadow: 0 0 12px rgba(232,200,122,0.3);
}

.skill-showcase-fill.animated {
    width: var(--skill-width);
}

.skill-showcase-val {
    font-family: 'Cormorant Garamond', serif;
    font-size: 18px;
    font-weight: 600;
    color: var(--gold);
    min-width: 32px;
    text-align: right;
}

.artist-showcase-actions {
    display: flex;
    gap: 16px;
    align-items: center;
}

.btn-gold {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 14px 32px;
    background: var(--gold);
    color: var(--bg-primary);
    text-decoration: none;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    border: none;
}

.btn-gold::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold-light);
    transform: translateX(-100%);
    transition: transform 0.4s var(--ease-smooth);
}

.btn-gold:hover { transform: translateY(-2px); box-shadow: 0 16px 40px rgba(232,200,122,0.25); }
.btn-gold:hover::before { transform: translateX(0); }
.btn-gold span, .btn-gold i { position: relative; z-index: 1; }

.btn-outline {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 13px 28px;
    background: transparent;
    border: 1.5px solid var(--border);
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    transition: var(--transition);
}

.btn-outline:hover { border-color: var(--gold-border); color: var(--gold); transform: translateY(-2px); }

@media(max-width:900px) {
    .artist-showcase-row {
        grid-template-columns: 1fr;
        margin-bottom: 32px;
    }
    .artist-showcase-row:nth-child(even) {
        direction: ltr;
    }
    .artist-showcase-photo {
        min-height: 360px;
    }
    .artist-showcase-info {
        padding: 40px 32px;
    }
    .artist-showcase-photo-overlay {
        background: linear-gradient(180deg, transparent 40%, rgba(5,5,7,0.9) 100%) !important;
    }
}

@media(max-width:600px) {
    .artist-showcase-info {
        padding: 32px 24px;
    }
    .artist-showcase-name {
        font-size: 32px;
    }
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
    .section, .artists-showcase { padding: 80px 24px; }
    .page-hero { padding: 120px 24px 60px; }
}

/* ===== MARQUEE ===== */
.marquee-section {
    overflow: hidden;
    padding: 40px 0;
    border-top: 1.5px solid var(--border);
    border-bottom: 1.5px solid var(--border);
    background: var(--bg-secondary);
}

.marquee-track {
    display: flex;
    width: max-content;
    animation: marqueeScroll 30s linear infinite;
}

.marquee-text {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(48px, 8vw, 96px);
    font-weight: 300;
    letter-spacing: -2px;
    color: rgba(232,200,122,0.08);
    white-space: nowrap;
    padding: 0 40px;
    text-transform: uppercase;
}

@keyframes marqueeScroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

/* ===== PARALLAX ===== */
.parallax-img { will-change: transform; }

/* ===== LINK ELEGANT ===== */
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
                <a href="index.php" class="nav-link">Home</a>
                <a href="hair_artists.php" class="nav-link active">Hair Artists</a>
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

<!-- Page Hero -->
<section class="page-hero" id="home">
    <div class="page-hero-bg"></div>
    <div class="page-hero-lines"></div>

    <div class="page-hero-content">
        <div class="page-hero-eyebrow">
            <div class="page-hero-eyebrow-line"></div>
            <span class="page-hero-eyebrow-text">The Masters Behind The Blade</span>
            <div class="page-hero-eyebrow-line right"></div>
        </div>

        <h1 class="page-hero-title">
            Meet Our<br>
            <em>Hair Artists</em>
        </h1>

        <div class="page-hero-divider">
            <div class="page-hero-divider-line"></div>
            <div class="page-hero-divider-diamond"></div>
            <div class="page-hero-divider-line"></div>
        </div>
    </div>
</section>

<!-- Marquee -->
<div class="marquee-section">
    <div class="marquee-track">
        <span class="marquee-text">Precision &mdash; Style &mdash; Artistry &mdash; Craft &mdash; Passion &mdash; Mastery &mdash; </span>
        <span class="marquee-text">Precision &mdash; Style &mdash; Artistry &mdash; Craft &mdash; Passion &mdash; Mastery &mdash; </span>
        <span class="marquee-text">Precision &mdash; Style &mdash; Artistry &mdash; Craft &mdash; Passion &mdash; Mastery &mdash; </span>
        <span class="marquee-text">Precision &mdash; Style &mdash; Artistry &mdash; Craft &mdash; Passion &mdash; Mastery &mdash; </span>
    </div>
</div>

<!-- Artists Showcase -->
<section class="artists-showcase" id="artists">
    <div class="artists-showcase-inner">
        <div class="artists-showcase-header reveal">
            <div class="section-marker">
                <div class="section-marker-line"></div>
                <span class="section-marker-label">The Roster</span>
                <div class="section-marker-line"></div>
            </div>
            <h2 class="section-title">Our <em>Artists</em></h2>
            <p>Each barber brings a unique perspective and specialized skill set. Hover over any card to explore their craft in detail.</p>
        </div>

        <?php
        $artistNums = ['01', '02', '03', '04', '05', '06'];
        foreach($barbers as $idx => $barber):
            $skills = [
                'Fade' => (int)$barber['skill_fade'],
                'Scissor' => (int)$barber['skill_scissoring'],
                'Long Cut' => (int)$barber['skill_longcut'],
                'Short Cut' => (int)$barber['skill_shortcut'],
                'Beard' => (int)$barber['skill_beardcut'],
            ];
            arsort($skills);
            $topSkills = array_slice($skills, 0, 5, true);
            $isOnDuty = in_array($barber['id'], $onDutyIds);
        ?>
        <div class="artist-showcase-row reveal delay-<?= ($idx % 3) + 1 ?>" data-artist-id="<?= $barber['id'] ?>">
            <div class="artist-showcase-photo">
                <?php if(!empty($barber['foto'])): ?>
                <img src="admin/upload/<?= htmlspecialchars($barber['foto']) ?>" alt="<?= htmlspecialchars($barber['nama']) ?>" loading="lazy" class="parallax-img" data-parallax="0.03">
                <?php else: ?>
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:120px;font-family:'Cormorant Garamond',serif;font-weight:300;">
                    <?= strtoupper(substr($barber['nama'], 3, 1)) ?>
                </div>
                <?php endif; ?>
                <div class="artist-showcase-photo-overlay"></div>
                <div class="artist-showcase-photo-frame"></div>
                <div class="artist-showcase-photo-num"><?= $artistNums[$idx] ?></div>
            </div>

            <div class="artist-showcase-info">
                <div class="artist-showcase-badge <?= $isOnDuty ? 'on-duty' : '' ?>">
                    <?php if($isOnDuty): ?>
                    <i class="fas fa-circle" style="font-size:6px;"></i>
                    <?php endif; ?>
                    <?= $isOnDuty ? 'On Duty Today' : 'Master Barber' ?>
                </div>

                <h3 class="artist-showcase-name"><?= htmlspecialchars($barber['nama']) ?></h3>
                <div class="artist-showcase-role">Hair Artist</div>

                <p class="artist-showcase-bio"><?= htmlspecialchars($barber['keterangan']) ?></p>

                <div class="artist-showcase-skills">
                    <?php foreach($topSkills as $sName => $sVal): ?>
                    <div class="skill-showcase-row">
                        <div class="skill-showcase-label"><?= $sName ?></div>
                        <div class="skill-showcase-track">
                            <div class="skill-showcase-fill" data-width="<?= $sVal ?>" style="--skill-width: <?= $sVal ?>%;"></div>
                        </div>
                        <div class="skill-showcase-val"><?= $sVal ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="artist-showcase-actions">
                    <a href="login.php" class="btn-gold">
                        <span>Book With <?= htmlspecialchars(explode(' ', $barber['nama'])[1] ?? $barber['nama']) ?></span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="index.php#schedule" class="btn-outline">
                        <span>Check Schedule</span>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="footer-inner">
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
</footer>

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
        fx += (mx - fx) * 0.12;
        fy += (my - fy) * 0.12;
        cursorFollower.style.transform = `translate3d(${fx}px, ${fy}px, 0) translate(-50%, -50%)`;
    }
    requestAnimationFrame(updateCursor);
}
requestAnimationFrame(updateCursor);

document.querySelectorAll('a, button, .artist-showcase-row, .btn-gold, .btn-outline').forEach(el => {
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
        const container = el.closest('.artist-showcase-photo');
        if (container) {
            const rect = container.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            parallaxData.push({
                el: el,
                containerTop: rect.top + scrollTop,
                containerHeight: rect.height,
                speed: parseFloat(el.dataset.parallax || 0.03)
            });
        }
    });
}

updateParallaxCache();
window.addEventListener('resize', updateParallaxCache, { passive: true });

let lastScrollY = window.pageYOffset;
let scrollScheduled = false;
let ticking = false;

window.addEventListener('scroll', () => {
    lastScrollY = window.pageYOffset;
    if (!scrollScheduled) {
        scrollScheduled = true;
        requestAnimationFrame(tick);
    }
}, { passive: true });

function tick() {
    scrollScheduled = false;

    // Scroll progress line
    const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const pct = scrollHeight > 0 ? (lastScrollY / scrollHeight) * 100 : 0;
    scrollLine.style.width = pct + '%';

    // Nav background
    mainNav.classList.toggle('scrolled', lastScrollY > 60);

    // Subtle parallax - only when element is near viewport
    const viewportTop = lastScrollY - window.innerHeight;
    const viewportBottom = lastScrollY + window.innerHeight * 2;

    parallaxData.forEach(data => {
        const elemTop = data.containerTop;
        const elemBottom = data.containerTop + data.containerHeight;

        // Only animate if element is visible or near viewport
        if (elemBottom > viewportTop && elemTop < viewportBottom) {
            const windowCenter = lastScrollY + window.innerHeight / 2;
            const centerY = elemTop + data.containerHeight / 2;
            const offset = (centerY - windowCenter) * data.speed;
            data.el.style.transform = `translate3d(0, ${offset}px, 0)`;
        }
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
}, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
revealEls.forEach(el => revealObs.observe(el));

// ===== SKILL BARS ANIMATION =====
const skillRows = document.querySelectorAll('.artist-showcase-row');
const skillObs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.querySelectorAll('.skill-showcase-fill[data-width]').forEach((bar, i) => {
                setTimeout(() => {
                    bar.classList.add('animated');
                }, 200 + i * 100);
            });
        }
    });
}, { threshold: 0.15 });
skillRows.forEach(row => skillObs.observe(row));

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