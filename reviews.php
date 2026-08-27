<?php
/**
 * Hostel Barbershop — Reviews Page
 * Full reviews listing with stats, consistent with index.php design
 */

include 'config.php';

/* ==================== FETCH DATA ==================== */

// Rating stats
$ratingStats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total_reviews, ROUND(AVG(rating), 1) as avg_rating
    FROM barbershop_rating
"));
$totalReviews = $ratingStats['total_reviews'] ?? 0;
$avgRating = $ratingStats['avg_rating'] ?? 0;

// Star distribution
$starCounts = [];
for($s = 5; $s >= 1; $s--){
    $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM barbershop_rating WHERE rating=$s"))['c'];
    $starCounts[$s] = $count;
}

// All reviews with user info
$qReviews = mysqli_query($conn, "
    SELECT br.*, u.username, u.photo as user_photo
    FROM barbershop_rating br
    LEFT JOIN users u ON br.user_id = u.id
    ORDER BY br.created_at DESC
");

// Total bookings for stats
$totalBookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM booking WHERE status='paid'"))['total'] ?? 0;

// Name censoring function for privacy
function censorName($name) {
    $words = explode(' ', trim($name));
    $censored = [];
    foreach($words as $word) {
        $len = mb_strlen($word);
        if($len <= 2) {
            $censored[] = $word;
        } else {
            $visible = mb_substr($word, 0, 2);
            $hidden = str_repeat('*', $len - 2);
            $censored[] = $visible . $hidden;
        }
    }
    return implode(' ', $censored);
}

// Total barbers
$totalBarbers = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM barber"));

// Time slots
$qJam = mysqli_query($conn, "SELECT * FROM jam_operasional WHERE status='Buka' ORDER BY jam_buka ASC");
$jamList = [];
while($j = mysqli_fetch_assoc($qJam)){
    $jamList[] = substr($j['jam_buka'], 0, 5);
}

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reviews | Hostel Barbershop</title>
<meta name="description" content="Read what our clients say about Hostel Barbershop. Verified reviews from real customers.">

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
    cursor: none;
}

/* Hide default cursor on all interactive elements */
a, button, input, textarea, select, .review-card, .footer-social-link,
.nav-link, .nav-login-btn, .nav-mobile-toggle, .footer-link,
.back-home-btn, .load-more-btn, .review-avatar {
    cursor: none;
}

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

/* ===== GRAIN OVERLAY ===== */
.grain {
    position: fixed;
    top: -10px;
    left: -10px;
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
    left: 0;
    bottom: 0;
    width: 0%;
    height: 3px;
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
    bottom: 6px;
    left: 24px; right: 24px;
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

/* ===== PAGE HEADER / HERO ===== */
.page-hero {
    min-height: 50vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    padding: 160px 48px 80px;
    text-align: center;
}

.page-hero-bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 60% 50% at 50% 0%, rgba(232,200,122,0.05) 0%, transparent 60%);
    pointer-events: none;
}

.page-hero-lines {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(232,200,122,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(232,200,122,0.03) 1px, transparent 1px);
    background-size: 100px 100px;
    pointer-events: none;
    mask-image: radial-gradient(ellipse at center, black 20%, transparent 80%);
}

.page-hero-content {
    position: relative;
    z-index: 2;
    max-width: 800px;
}

.page-hero-eyebrow {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    margin-bottom: 28px;
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
    font-size: clamp(48px, 7vw, 88px);
    font-weight: 300;
    letter-spacing: -2px;
    line-height: 1;
    margin-bottom: 20px;
}

.page-hero-title em {
    font-style: italic;
    color: var(--gold);
}

.page-hero-desc {
    font-size: 15px;
    font-weight: 500;
    color: var(--text-secondary);
    max-width: 480px;
    margin: 0 auto;
    line-height: 1.9;
    letter-spacing: 0.3px;
}

/* ===== BREADCRUMB ===== */
.breadcrumb {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-top: 32px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--text-muted);
}

.breadcrumb a {
    color: var(--text-muted);
    text-decoration: none;
    transition: var(--transition);
    position: relative;
}

.breadcrumb a:hover { color: var(--gold); }

.breadcrumb-sep {
    color: var(--border-light);
    font-size: 10px;
}

.breadcrumb-current { color: var(--gold); }

/* ===== SECTION UTILS ===== */
.section {
    padding: 80px 48px;
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
.delay-6 { transition-delay: 0.6s; }

/* ===== STATS BAR ===== */
.stats-bar {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0;
    border: 1.5px solid var(--border);
    margin-bottom: 80px;
}

.stat-item {
    padding: 40px 24px;
    text-align: center;
    border-right: 1.5px solid var(--border);
    transition: var(--transition);
}

.stat-item:last-child { border-right: none; }

.stat-item:hover { background: var(--bg-hover); }

.stat-value {
    font-family: 'Cormorant Garamond', serif;
    font-size: 48px;
    font-weight: 600;
    letter-spacing: -2px;
    line-height: 1;
    color: var(--gold);
    margin-bottom: 8px;
    text-shadow: 0 2px 20px rgba(232,200,122,0.2);
}

.stat-label {
    font-size: 10px;
    color: var(--text-muted);
    font-weight: 600;
    letter-spacing: 2.5px;
    text-transform: uppercase;
}

@media(max-width:768px) {
    .stats-bar { grid-template-columns: repeat(2, 1fr); }
    .stat-item:nth-child(2) { border-right: none; }
    .stat-item:nth-child(1), .stat-item:nth-child(2) { border-bottom: 1.5px solid var(--border); }
    .stat-value { font-size: 36px; }
}

/* ===== RATING SUMMARY ===== */
.rating-summary-section {
    background: var(--bg-secondary);
    border-top: 1.5px solid var(--border);
    border-bottom: 1.5px solid var(--border);
}

.rating-summary-grid {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 80px;
    align-items: start;
}

.rating-big {
    text-align: center;
    padding: 48px;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    position: sticky;
    top: 100px;
}

.rating-big-number {
    font-family: 'Cormorant Garamond', serif;
    font-size: 120px;
    font-weight: 300;
    letter-spacing: -6px;
    line-height: 1;
    color: var(--gold);
    margin-bottom: 12px;
    text-shadow: 0 4px 30px rgba(232,200,122,0.2);
}

.rating-big-stars {
    display: flex;
    justify-content: center;
    gap: 6px;
    color: var(--gold);
    font-size: 20px;
    margin-bottom: 12px;
}

.rating-big-count {
    font-size: 12px;
    color: var(--text-muted);
    letter-spacing: 1.5px;
    text-transform: uppercase;
    font-weight: 600;
}

.rating-big-divider {
    width: 60px;
    height: 1.5px;
    background: var(--border);
    margin: 28px auto;
}

.rating-big-text {
    font-size: 13px;
    color: var(--text-secondary);
    line-height: 1.8;
    font-weight: 500;
}

.rating-bars-wrapper {
    display: flex;
    flex-direction: column;
    gap: 18px;
    padding-top: 12px;
}

.rating-bar-row {
    display: flex;
    align-items: center;
    gap: 16px;
}

.rating-bar-label {
    font-size: 13px;
    font-weight: 700;
    color: var(--text-secondary);
    min-width: 80px;
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: 0.5px;
}

.rating-bar-label i {
    font-size: 11px;
    color: var(--gold);
}

.rating-bar-track {
    flex: 1;
    height: 6px;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    overflow: hidden;
    position: relative;
}

.rating-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
    transition: width 1.2s cubic-bezier(0.25,0.46,0.45,0.94);
    box-shadow: 0 0 12px rgba(232,200,122,0.3);
}

.rating-bar-count {
    font-size: 13px;
    color: var(--text-muted);
    min-width: 32px;
    text-align: right;
    font-weight: 600;
}

.rating-bar-percent {
    font-size: 11px;
    color: var(--text-muted);
    min-width: 40px;
    text-align: right;
    font-weight: 600;
    letter-spacing: 0.5px;
}

@media(max-width:900px) {
    .rating-summary-grid { grid-template-columns: 1fr; gap: 48px; }
    .rating-big { position: static; }
    .rating-big-number { font-size: 80px; }
}

/* ===== REVIEWS LIST ===== */
.reviews-list-section {
    border-top: 1.5px solid var(--border);
}

.reviews-header-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: 48px;
    flex-wrap: wrap;
    gap: 24px;
}

.reviews-count-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    background: var(--gold-dim);
    border: 1.5px solid var(--gold-border);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: var(--gold);
}

.reviews-count-badge i { font-size: 10px; }

.reviews-grid-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.review-card {
    padding: 36px;
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.review-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--gold);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.5s cubic-bezier(0.25,0.46,0.45,0.94);
}

.review-card:hover {
    border-color: var(--gold-border);
    transform: translateY(-4px);
    box-shadow: 0 20px 56px rgba(0,0,0,0.5);
}

.review-card:hover::before { transform: scaleX(1); }

.review-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.review-card-user {
    display: flex;
    align-items: center;
    gap: 14px;
}

.review-card-avatar {
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
    transition: var(--transition);
}

.review-card:hover .review-card-avatar {
    border-color: var(--gold-border);
}

.review-card-avatar img {
    width: 100%; height: 100%;
    object-fit: cover;
    filter: grayscale(20%);
}

.review-card-username {
    font-size: 14px;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 2px;
}

.review-card-date {
    font-size: 10px;
    color: var(--text-muted);
    letter-spacing: 1px;
    text-transform: uppercase;
}

.review-card-stars {
    color: var(--gold);
    font-size: 12px;
    letter-spacing: 2px;
}

.review-card-text {
    font-size: 15px;
    font-weight: 500;
    color: var(--text-secondary);
    line-height: 1.8;
    font-style: italic;
    position: relative;
    padding-left: 20px;
    border-left: 2px solid var(--gold-border);
}

.review-card-text p {
    margin: 0;
}

.review-card-empty {
    text-align: center;
    padding: 80px 20px;
    color: var(--text-muted);
    grid-column: 1 / -1;
}

.review-card-empty i {
    font-size: 48px;
    margin-bottom: 20px;
    opacity: 0.3;
    color: var(--gold);
}

.review-card-empty p {
    font-size: 14px;
    line-height: 1.8;
}

@media(max-width:900px) {
    .reviews-grid-list { grid-template-columns: 1fr; }
}

/* ===== LOAD MORE ===== */
.load-more-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 48px;
}

.load-more-btn {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 16px 40px;
    background: transparent;
    border: 1.5px solid var(--border);
    color: var(--text-secondary);
    font-family: 'Montserrat', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.load-more-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold);
    transform: translateX(-100%);
    transition: transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
}

.load-more-btn:hover {
    color: var(--bg-primary);
    border-color: var(--gold);
}

.load-more-btn:hover::before { transform: translateX(0); }

.load-more-btn span, .load-more-btn i { position: relative; z-index: 1; }

.load-more-btn i { transition: transform 0.3s ease; font-size: 10px; }

.load-more-btn:hover i { transform: translateX(4px); }

/* ===== CTA SECTION ===== */
.cta-section {
    background: var(--bg-secondary);
    border-top: 1.5px solid var(--border);
    border-bottom: 1.5px solid var(--border);
    text-align: center;
}

.cta-inner {
    max-width: 600px;
    margin: 0 auto;
}

.cta-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(32px, 5vw, 52px);
    font-weight: 300;
    letter-spacing: -1.5px;
    line-height: 1.1;
    margin-bottom: 20px;
}

.cta-title em {
    font-style: italic;
    color: var(--gold);
}

.cta-desc {
    font-size: 15px;
    color: var(--text-secondary);
    line-height: 1.9;
    margin-bottom: 36px;
    font-weight: 500;
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
::-webkit-scrollbar-thumb { background: var(--border-light); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: var(--gold-border); }

@media(max-width:768px) {
    .section { padding: 60px 24px; }
    .page-hero { padding: 140px 24px 60px; }
}
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
                <a href="hair_artists.php" class="nav-link">Hair Artists</a>
                <a href="contact.php" class="nav-link">Contact</a>
                <a href="about.php" class="nav-link">About</a>
            </div>
        </div>

        <a href="login.php" class="nav-login-btn">
            <span>Login</span>
        </a>

        <button class="nav-mobile-toggle" id="mobileToggle" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    <div class="scroll-line" id="scrollLine"></div>
</nav>

<!-- Page Hero -->
<section class="page-hero">
    <div class="page-hero-bg"></div>
    <div class="page-hero-lines"></div>

    <div class="page-hero-content">
        <div class="page-hero-eyebrow">
            <div class="page-hero-eyebrow-line"></div>
            <span class="page-hero-eyebrow-text">Verified Testimonials</span>
            <div class="page-hero-eyebrow-line right"></div>
        </div>

        <h1 class="page-hero-title">Client <em>Reviews</em></h1>

        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <span class="breadcrumb-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="breadcrumb-current">Reviews</span>
        </div>
    </div>
</section>

<!-- Rating Summary -->
<section class="section rating-summary-section">
    <div class="section-inner">
        <div class="rating-summary-grid">
            <div class="rating-big reveal-left">
                <div class="rating-big-number"><?= $avgRating > 0 ? $avgRating : '5.0' ?></div>
                <div class="rating-big-stars">
                    <?php
                    $fullStars = floor($avgRating);
                    $halfStar = ($avgRating - $fullStars) >= 0.5 ? 1 : 0;
                    $emptyStars = 5 - $fullStars - $halfStar;
                    for($i = 0; $i < $fullStars; $i++) echo '<i class="fas fa-star"></i>';
                    if($halfStar) echo '<i class="fas fa-star-half-alt"></i>';
                    for($i = 0; $i < $emptyStars; $i++) echo '<i class="far fa-star"></i>';
                    if($avgRating == 0) for($i = 0; $i < 5; $i++) echo '<i class="fas fa-star"></i>';
                    ?>
                </div>
                <div class="rating-big-count"><?= number_format($totalReviews) ?> verified reviews</div>
                <div class="rating-big-divider"></div>
                <div class="rating-big-text">
                    Our clients consistently rate their experience as exceptional. Every review reflects our commitment to precision, comfort, and style.
                </div>
            </div>

            <div class="rating-bars-wrapper reveal-right">
                <?php foreach($starCounts as $star => $count):
                    $pct = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                ?>
                <div class="rating-bar-row">
                    <div class="rating-bar-label"><?= $star ?> <i class="fas fa-star"></i></div>
                    <div class="rating-bar-track">
                        <div class="rating-bar-fill" data-width="<?= $pct ?>"></div>
                    </div>
                    <div class="rating-bar-count"><?= $count ?></div>
                    <div class="rating-bar-percent"><?= round($pct) ?>%</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Reviews List -->
<section class="section reviews-list-section">
    <div class="section-inner">
        <div class="reviews-header-row">
            <div class="reveal">
                <div class="section-marker">
                    <span class="section-marker-num">01</span>
                    <div class="section-marker-line"></div>
                    <span class="section-marker-label">All Reviews</span>
                </div>
            </div>
            <div class="reviews-count-badge reveal">
                <i class="fas fa-comments"></i>
                <?= number_format($totalReviews) ?> reviews
            </div>
        </div>

        <div class="reviews-grid-list" id="reviewsGrid">
            <?php if(mysqli_num_rows($qReviews) > 0):
                $delay = 0;
                mysqli_data_seek($qReviews, 0);
                while($rev = mysqli_fetch_assoc($qReviews)):
                    $delay++;
                    if($delay > 6) $delay = 6;
                    $emptyStars = 5 - $rev['rating'];
            ?>
            <div class="review-card reveal delay-<?= $delay ?>" data-review>
                <div class="review-card-header">
                    <div class="review-card-user">
                        <div class="review-card-avatar">
                            <?php if(!empty($rev['user_photo'])): ?>
                            <img src="<?= htmlspecialchars($rev['user_photo']) ?>" alt="">
                            <?php else: ?>
                            <?= !empty($rev['username']) ? strtoupper(substr(censorName($rev['username']), 0, 1)) : '?' ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="review-card-username"><?= !empty($rev['username']) ? htmlspecialchars(censorName($rev['username'])) : 'Deleted User' ?></div>
                            <div class="review-card-date"><?= date('d M Y', strtotime($rev['created_at'])) ?></div>
                        </div>
                    </div>
                    <div class="review-card-stars">
                        <?php
                        for($s = 0; $s < $rev['rating']; $s++) echo '<i class="fas fa-star"></i>';
                        for($s = 0; $s < $emptyStars; $s++) echo '<i class="far fa-star"></i>';
                        ?>
                    </div>
                </div>
                <?php if(!empty($rev['review'])): ?>
                <div class="review-card-text">
                    <p>"<?= htmlspecialchars($rev['review']) ?>"</p>
                </div>
                <?php else: ?>
                <div class="review-card-text">
                    <p style="color:var(--text-muted); font-style:normal;"><i class="fas fa-check-circle" style="color:var(--success); margin-right:6px;"></i>Verified booking — no written review</p>
                </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
            <?php else: ?>
            <div class="review-card-empty">
                <i class="fas fa-comment-slash"></i>
                <p>No reviews yet.<br>Be the first to share your experience after your visit.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="section cta-section">
    <div class="section-inner">
        <div class="cta-inner reveal">
            <h2 class="cta-title">Ready for Your <em>Transformation?</em></h2>
            <p class="cta-desc">
                Join hundreds of satisfied clients who trust Hostel Barbershop for their grooming needs. Book your appointment today.
            </p>
            <a href="login.php" class="btn-primary">
                <span>Book Appointment</span>
                <i class="fas fa-arrow-right"></i>
            </a>
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
                    <a href="index.php#packages" class="footer-link">Signature Haircut</a>
                    <a href="index.php#packages" class="footer-link">Premium Haircut</a>
                    <a href="index.php#packages" class="footer-link">Sultan Package</a>
                    <a href="index.php#packages" class="footer-link">Ambatucut</a>
                </div>
            </div>
            <div>
                <div class="footer-heading">Connect</div>
                <div class="footer-links">
                    <a href="#" class="footer-link">Instagram</a>
                    <a href="#" class="footer-link">TikTok</a>
                    <a href="#" class="footer-link">WhatsApp</a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-copyright">
                &copy; <?= date('Y') ?> <span>Hostel Barbershop</span> &mdash; Precision in every cut
            </div>
            <div class="footer-social">
                <a href="#" class="footer-social-link" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" class="footer-social-link" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                <a href="#" class="footer-social-link" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
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
        fx += (mx - fx) * 0.15;
        fy += (my - fy) * 0.15;
        cursorFollower.style.transform = `translate3d(${fx}px, ${fy}px, 0) translate(-50%, -50%)`;
    }
    requestAnimationFrame(updateCursor);
}
requestAnimationFrame(updateCursor);

document.querySelectorAll('a, button, .review-card, .footer-social-link, .nav-link, .nav-login-btn, .nav-mobile-toggle, .footer-link, .btn-primary, .load-more-btn, .stat-item').forEach(el => {
    el.addEventListener('mouseenter', () => {
        cursor.classList.add('hover');
        cursorFollower.classList.add('hover');
    }, { passive: true });
    el.addEventListener('mouseleave', () => {
        cursor.classList.remove('hover');
        cursorFollower.classList.remove('hover');
    }, { passive: true });
});

// ===== SCROLL SYSTEM =====
const scrollLine = document.getElementById('scrollLine');
const mainNav = document.getElementById('mainNav');

window.addEventListener('scroll', () => {
    const scrollY = window.pageYOffset;
    const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const pct = scrollHeight > 0 ? (scrollY / scrollHeight) * 100 : 0;
    scrollLine.style.width = pct + '%';
    mainNav.classList.toggle('scrolled', scrollY > 60);
}, { passive: true });

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
            // Animate rating bars when visible
            entry.target.querySelectorAll('.rating-bar-fill[data-width]').forEach(bar => {
                bar.style.width = bar.dataset.width + '%';
            });
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -60px 0px' });

revealEls.forEach(el => revealObs.observe(el));

// Also animate rating bars in the summary section
const ratingBars = document.querySelectorAll('.rating-bar-fill[data-width]');
const barObs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.width = entry.target.dataset.width + '%';
        }
    });
}, { threshold: 0.2 });
ratingBars.forEach(bar => barObs.observe(bar));
</script>

</body>
</html>