<?php
/**
 * Hostel Barbershop — About Page
 * Minimal, compact layout. No images.
 */

include 'config.php';

$dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$currentDay = $dayNames[date('w')];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About — Hostel Barbershop</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
:root {
    --bg-primary: #050507;
    --bg-secondary: #0c0c10;
    --bg-card: #13131a;
    --bg-hover: #1e1e28;
    --border: rgba(255, 255, 255, 0.14);
    --text-primary: #ffffff;
    --text-secondary: #d5d0c8;
    --text-muted: #9e998f;
    --gold: #e8c87a;
    --gold-light: #f5e6c3;
    --gold-dim: rgba(232, 200, 122, 0.10);
    --gold-border: rgba(232, 200, 122, 0.45);
    --transition: all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
    --ease-smooth: cubic-bezier(0.25,0.46,0.45,0.94);
    --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
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

/* ===== CURSOR ===== */
.cursor {
    position: fixed; top: 0; left: 0;
    width: 10px; height: 10px;
    background: var(--gold);
    border-radius: 50%;
    pointer-events: none;
    z-index: 99999;
    will-change: transform;
    transition: width 0.2s ease, height 0.2s ease, background 0.2s ease;
}
.cursor-follower {
    position: fixed; top: 0; left: 0;
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

/* ===== GRAIN ===== */
.grain {
    position: fixed; top: -10px; left: -10px;
    width: calc(100% + 20px); height: calc(100% + 20px);
    pointer-events: none; z-index: 9997; opacity: 0.035;
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
    position: absolute; left: 0; bottom: 0;
    width: 0%; height: 3px;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
    z-index: 9990; transition: width 0.1s linear;
    box-shadow: 0 0 16px rgba(232,200,122,0.5);
}

/* ===== NAVIGATION ===== */
.nav-wrapper {
    position: fixed; top: 0; left: 0; right: 0;
    z-index: 9000; padding: 24px 0;
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
    max-width: 1280px; margin: 0 auto;
    padding: 0 32px;
    display: flex; align-items: center; justify-content: space-between;
}
.nav-left-group {
    display: flex; align-items: center; gap: 48px;
}
.nav-logo {
    display: flex; align-items: center;
    text-decoration: none; color: var(--text-primary);
    margin-left: -8px;
}
.nav-logo-img {
    height: 52px; width: auto; display: block;
    transition: opacity 0.3s ease, transform 0.3s ease;
    filter: brightness(0.95) contrast(1.1);
}
.nav-logo:hover .nav-logo-img { opacity: 0.85; transform: scale(0.97); }

.nav-links { display: flex; align-items: center; gap: 0; }
.nav-link {
    padding: 10px 24px;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 12px; font-weight: 600;
    letter-spacing: 2.5px; text-transform: uppercase;
    transition: var(--transition); position: relative;
}
.nav-link::after {
    content: ''; position: absolute;
    bottom: 6px; left: 24px; right: 24px;
    height: 1.5px; background: var(--gold);
    transform: scaleX(0); transform-origin: left;
    transition: transform 0.4s var(--ease-smooth);
}
.nav-link:hover, .nav-link.active { color: var(--text-primary); }
.nav-link:hover::after, .nav-link.active::after { transform: scaleX(1); }

.nav-login-btn {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 12px 32px; background: transparent;
    border: 1.5px solid var(--gold-border); color: var(--gold);
    text-decoration: none; font-size: 11px; font-weight: 700;
    letter-spacing: 2.5px; text-transform: uppercase;
    transition: var(--transition); position: relative; overflow: hidden;
}
.nav-login-btn::before {
    content: ''; position: absolute; inset: 0;
    background: var(--gold); transform: scaleX(0); transform-origin: left;
    transition: transform 0.4s var(--ease-smooth);
}
.nav-login-btn:hover { color: var(--bg-primary); }
.nav-login-btn:hover::before { transform: scaleX(1); }
.nav-login-btn span { position: relative; z-index: 1; }

.nav-mobile-toggle {
    display: none; width: 44px; height: 44px;
    align-items: center; justify-content: center;
    background: transparent; border: 1.5px solid var(--border);
    color: var(--text-primary); font-size: 16px;
    cursor: none; transition: var(--transition);
}
.nav-mobile-toggle:hover { border-color: var(--gold-border); color: var(--gold); }

@media(max-width:900px) {
    .nav-links {
        display: none; position: absolute;
        top: 100%; left: 0; right: 0;
        background: rgba(5,5,7,0.98);
        border-bottom: 1px solid var(--border);
        padding: 24px 0; flex-direction: column; gap: 0;
    }
    .nav-links.show { display: flex; }
    .nav-login-btn { display: none; }
    .nav-mobile-toggle { display: flex; }
    .nav-inner { padding: 0 24px; }
    .nav-left-group { gap: 32px; }
}

/* ===== HERO ===== */
.page-hero {
    min-height: 50vh;
    display: flex; align-items: center; justify-content: center;
    position: relative; overflow: hidden;
    padding: 140px 48px 60px;
}
.page-hero-bg {
    position: absolute; inset: 0;
    background:
        radial-gradient(ellipse 60% 50% at 30% 20%, rgba(232,200,122,0.05) 0%, transparent 60%),
        radial-gradient(ellipse 40% 40% at 80% 80%, rgba(232,200,122,0.03) 0%, transparent 60%);
    pointer-events: none;
}
.page-hero-lines {
    position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(232,200,122,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(232,200,122,0.03) 1px, transparent 1px);
    background-size: 120px 120px;
    pointer-events: none;
    mask-image: radial-gradient(ellipse at center, black 15%, transparent 75%);
}
.page-hero-content {
    max-width: 800px; text-align: center;
    position: relative; z-index: 2;
}
.page-hero-eyebrow {
    display: flex; align-items: center; justify-content: center;
    gap: 20px; margin-bottom: 32px;
    opacity: 0;
    animation: heroFadeUp 1s var(--ease-smooth) 0.2s forwards;
}
.page-hero-eyebrow-line {
    width: 60px; height: 1.5px;
    background: linear-gradient(90deg, transparent, var(--gold));
}
.page-hero-eyebrow-line.right {
    background: linear-gradient(90deg, var(--gold), transparent);
}
.page-hero-eyebrow-text {
    font-size: 11px; font-weight: 700;
    letter-spacing: 4px; text-transform: uppercase;
    color: var(--gold);
}
.page-hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(48px, 8vw, 90px);
    font-weight: 300; letter-spacing: -2px;
    line-height: 0.95; margin-bottom: 28px;
    opacity: 0;
    animation: heroFadeUp 1.2s var(--ease-smooth) 0.4s forwards;
    text-shadow: 0 4px 30px rgba(0,0,0,0.5);
}
.page-hero-title em {
    font-style: italic; color: var(--gold);
}
.page-hero-divider {
    display: flex; align-items: center; justify-content: center;
    gap: 24px; margin-bottom: 24px;
    opacity: 0;
    animation: heroFadeUp 1s var(--ease-smooth) 0.6s forwards;
}
.page-hero-divider-line {
    flex: 1; max-width: 120px; height: 1.5px;
    background: rgba(255,255,255,0.15);
}
.page-hero-divider-diamond {
    width: 8px; height: 8px;
    border: 1.5px solid var(--gold);
    transform: rotate(45deg);
}
.page-hero-desc {
    font-size: 16px; font-weight: 500;
    color: var(--text-secondary);
    max-width: 520px; margin: 0 auto;
    line-height: 1.9; letter-spacing: 0.5px;
    opacity: 0;
    animation: heroFadeUp 1s var(--ease-smooth) 0.7s forwards;
}

@keyframes heroFadeUp {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ===== SECTION UTILS ===== */
.section {
    padding: 80px 48px;
    position: relative;
}
.section-inner {
    max-width: 1280px; margin: 0 auto;
}
.section-marker {
    display: flex; align-items: center; gap: 16px;
    margin-bottom: 20px;
}
.section-marker-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 12px; font-weight: 600;
    color: var(--gold); letter-spacing: 2px;
}
.section-marker-line {
    width: 40px; height: 1.5px; background: var(--gold);
}
.section-marker-label {
    font-size: 10px; font-weight: 700;
    letter-spacing: 3px; text-transform: uppercase;
    color: var(--text-muted);
}
.section-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(36px, 5vw, 56px);
    font-weight: 400; letter-spacing: -2px;
    line-height: 1.05; margin-bottom: 24px;
}
.section-title em { font-style: italic; color: var(--gold); }

/* ===== REVEAL ===== */
.reveal {
    opacity: 0; transform: translateY(40px);
    transition: opacity 0.8s var(--ease-smooth), transform 0.8s var(--ease-smooth);
}
.reveal.visible { opacity: 1; transform: translateY(0); }

/* ===== STORY ===== */
.story-section {
    background: var(--bg-secondary);
    border-top: 1.5px solid var(--border);
    border-bottom: 1.5px solid var(--border);
}
.story-content {
    max-width: 720px;
    margin: 0 auto;
}
.story-content p {
    font-size: 15px; font-weight: 500;
    color: var(--text-secondary);
    line-height: 2; letter-spacing: 0.3px;
    margin-bottom: 24px;
    text-align: justify;
    text-justify: inter-word;
}
.story-content p:last-child { margin-bottom: 0; }
.story-quote {
    font-family: 'Cormorant Garamond', serif;
    font-size: 20px; font-weight: 400;
    font-style: italic; color: var(--gold);
    line-height: 1.5; letter-spacing: -0.3px;
    padding: 24px 28px;
    margin: 32px 0;
    border-left: 2px solid var(--gold-border);
    border-right: 2px solid var(--gold-border);
    background: var(--bg-card);
    text-align: center;
}
.story-quote-author {
    font-family: 'Montserrat', sans-serif;
    font-size: 10px; font-weight: 700;
    letter-spacing: 2.5px; text-transform: uppercase;
    color: var(--text-muted); margin-top: 12px;
    font-style: normal;
}

/* ================================================================
   VISIT US — COMPACT 2-COLUMN GRID
   ================================================================ */
.visit-section {
    border-top: 1.5px solid var(--border);
    padding: 80px 48px;
}
.visit-header {
    text-align: center;
    margin-bottom: 48px;
}
.visit-header .section-marker {
    justify-content: center;
    margin-bottom: 20px;
}
.visit-header .section-title {
    margin-bottom: 0;
}

/* Main 2-column grid */
.visit-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 32px;
    align-items: start;
}

/* Left: Hours + Location stacked */
.visit-info-col {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* Right: Map */
.visit-map-col {
    position: sticky;
    top: 100px;
}

/* Card base */
.v-card {
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    position: relative;
    overflow: hidden;
}
.v-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
    transform: scaleX(0); transform-origin: left;
    transition: transform 0.6s var(--ease-out);
}
.v-card.visible::before { transform: scaleX(1); }

/* Card header compact */
.v-card-header {
    display: flex; align-items: center; gap: 14px;
    padding: 24px 28px 18px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.v-card-icon {
    width: 40px; height: 40px;
    border: 1.5px solid var(--gold-border);
    background: var(--gold-dim);
    display: flex; align-items: center; justify-content: center;
    color: var(--gold); font-size: 16px;
    flex-shrink: 0;
}
.v-card-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 24px; font-weight: 400;
    letter-spacing: -0.5px; color: var(--text-primary);
    line-height: 1.1;
}
.v-card-subtitle {
    font-size: 10px; font-weight: 700;
    letter-spacing: 2px; text-transform: uppercase;
    color: var(--text-muted); margin-top: 3px;
}

/* Hours body */
.hours-body {
    padding: 8px 28px 20px;
}
.hours-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    transition: var(--transition);
}
.hours-row:last-child { border-bottom: none; }
.hours-row:hover { background: rgba(232,200,122,0.02); }
.hours-row.active {
    background: rgba(232,200,122,0.04);
    margin: 0 -28px;
    padding: 10px 28px;
    position: relative;
}
.hours-row.active::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0;
    width: 2px; background: var(--gold);
}
.hours-day {
    font-size: 13px; font-weight: 500;
    color: var(--text-secondary);
    display: flex; align-items: center; gap: 10px;
}
.hours-day .today-badge {
    font-size: 8px; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase;
    color: var(--bg-primary);
    background: var(--gold);
    padding: 2px 8px;
    line-height: 1;
}
.hours-time {
    font-size: 13px; font-weight: 600;
    color: var(--text-primary);
    font-variant-numeric: tabular-nums;
}
.hours-row.active .hours-time { color: var(--gold); }

/* Location body */
.location-body {
    padding: 8px 28px 20px;
}
.location-row {
    display: flex; align-items: flex-start; gap: 14px;
    padding: 12px 0;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    transition: var(--transition);
}
.location-row:last-child { border-bottom: none; }
.location-row:hover { background: rgba(232,200,122,0.02); margin: 0 -28px; padding: 12px 28px; }
.location-row-icon {
    width: 32px; height: 32px;
    border: 1.5px solid var(--gold-border);
    background: var(--gold-dim);
    display: flex; align-items: center; justify-content: center;
    color: var(--gold); font-size: 12px;
    flex-shrink: 0;
    transition: var(--transition);
}
.location-row:hover .location-row-icon {
    background: var(--gold); color: var(--bg-primary); border-color: var(--gold);
}
.location-row-content h4 {
    font-size: 10px; font-weight: 700;
    letter-spacing: 1.5px; text-transform: uppercase;
    color: var(--text-primary); margin-bottom: 3px;
}
.location-row-content p,
.location-row-content a {
    font-size: 13px; font-weight: 500;
    color: var(--text-secondary); line-height: 1.6;
    text-decoration: none; transition: var(--transition);
}
.location-row-content a:hover { color: var(--gold); }

/* Map */
.map-box {
    border: 1.5px solid var(--border);
    background: var(--bg-card);
    position: relative;
    overflow: hidden;
}
.map-box::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
    z-index: 2;
}
.map-box iframe {
    width: 100%; height: 100%;
    min-height: 480px;
    display: block;
    filter: grayscale(30%) contrast(1.1);
    transition: filter 0.6s ease;
}
.map-box:hover iframe {
    filter: grayscale(10%) contrast(1.05);
}
.map-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(180deg, rgba(5,5,7,0.15) 0%, transparent 20%, transparent 80%, rgba(5,5,7,0.2) 100%);
    pointer-events: none; z-index: 1;
}

@media(max-width:1024px) {
    .visit-grid { grid-template-columns: 1fr; }
    .visit-map-col { position: static; }
    .map-box iframe { min-height: 320px; }
}
@media(max-width:600px) {
    .hours-row.active { margin: 0 -28px; padding: 10px 28px; }
    .location-row:hover { margin: 0 -28px; padding: 12px 28px; }
    .visit-section, .section { padding: 60px 24px; }
    .page-hero { padding: 120px 24px 40px; }
}

/* ===== CTA ===== */
.cta-section {
    padding: 80px 48px;
    position: relative; overflow: hidden;
    border-top: 1.5px solid var(--border);
}
.cta-bg {
    position: absolute; inset: 0;
    background: radial-gradient(ellipse 60% 50% at 50% 50%, rgba(232,200,122,0.04) 0%, transparent 60%);
    pointer-events: none;
}
.cta-inner {
    max-width: 600px; margin: 0 auto;
    text-align: center; position: relative; z-index: 2;
}
.cta-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(28px, 3.5vw, 40px);
    font-weight: 400; letter-spacing: -2px;
    line-height: 1.05; margin-bottom: 16px;
}
.cta-title em { font-style: italic; color: var(--gold); }
.cta-desc {
    font-size: 14px; font-weight: 500;
    color: var(--text-secondary);
    line-height: 1.8; margin-bottom: 32px;
}
.btn-gold {
    display: inline-flex; align-items: center; gap: 12px;
    padding: 14px 36px; background: var(--gold);
    color: var(--bg-primary); text-decoration: none;
    font-size: 11px; font-weight: 700;
    letter-spacing: 3px; text-transform: uppercase;
    transition: var(--transition); position: relative;
    overflow: hidden; border: none;
}
.btn-gold::before {
    content: ''; position: absolute; inset: 0;
    background: var(--gold-light);
    transform: translateX(-100%);
    transition: transform 0.4s var(--ease-smooth);
}
.btn-gold:hover { transform: translateY(-2px); box-shadow: 0 16px 40px rgba(232,200,122,0.2); }
.btn-gold:hover::before { transform: translateX(0); }
.btn-gold span, .btn-gold i { position: relative; z-index: 1; }

/* ===== FOOTER ===== */
.footer {
    border-top: 1.5px solid var(--border);
    padding: 60px 48px 40px;
}
.footer-inner { max-width: 1280px; margin: 0 auto; }
.footer-top {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 48px; margin-bottom: 48px;
    padding-bottom: 48px;
    border-bottom: 1.5px solid var(--border);
}
.footer-brand-img {
    height: 48px; width: auto; display: block;
    margin-bottom: 16px;
    filter: brightness(0.75) contrast(1.1);
}
.footer-desc {
    font-size: 13px; color: var(--text-muted);
    line-height: 1.8; letter-spacing: 0.3px;
    max-width: 280px;
}
.footer-heading {
    font-size: 10px; font-weight: 700;
    letter-spacing: 3px; text-transform: uppercase;
    color: var(--gold); margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1.5px solid var(--gold-border);
}
.footer-links { display: flex; flex-direction: column; gap: 12px; }
.footer-link {
    font-size: 12px; color: var(--text-muted);
    text-decoration: none; letter-spacing: 0.5px;
    transition: var(--transition);
    display: flex; align-items: center; gap: 10px;
}
.footer-link::before {
    content: ''; width: 0; height: 1.5px;
    background: var(--gold); transition: width 0.3s ease;
}
.footer-link:hover { color: var(--text-primary); }
.footer-link:hover::before { width: 16px; }
.footer-bottom {
    display: flex; align-items: center;
    justify-content: space-between; flex-wrap: wrap; gap: 20px;
}
.footer-copyright {
    font-size: 11px; color: var(--text-muted); letter-spacing: 1px;
}
.footer-copyright span { color: var(--gold); }
.footer-social { display: flex; gap: 12px; }
.footer-social-link {
    width: 36px; height: 36px;
    border: 1.5px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    color: var(--text-muted); font-size: 13px;
    text-decoration: none; transition: var(--transition);
}
.footer-social-link:hover {
    border-color: var(--gold-border); color: var(--gold);
    background: var(--gold-dim);
}

@media(max-width:900px) {
    .footer-top { grid-template-columns: 1fr 1fr; gap: 32px; }
    .footer { padding: 48px 24px 32px; }
}
@media(max-width:600px) {
    .footer-top { grid-template-columns: 1fr; }
    .footer-bottom { flex-direction: column; text-align: center; }
}

/* ===== MARQUEE ===== */
.marquee-section {
    overflow: hidden; padding: 28px 0;
    border-top: 1.5px solid var(--border);
    border-bottom: 1.5px solid var(--border);
    background: var(--bg-secondary);
}
.marquee-track {
    display: flex; width: max-content;
    animation: marqueeScroll 30s linear infinite;
}
.marquee-text {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(36px, 6vw, 64px);
    font-weight: 300; letter-spacing: -2px;
    color: rgba(232,200,122,0.07);
    white-space: nowrap; padding: 0 32px;
    text-transform: uppercase;
}
@keyframes marqueeScroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

/* ===== MISC ===== */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--bg-primary); }
::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); }
::-webkit-scrollbar-thumb:hover { background: var(--gold-border); }

@media(max-width:768px) {
    .section { padding: 60px 24px; }
    .page-hero { padding: 120px 24px 40px; }
    .cta-section { padding: 60px 24px; }
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
                <a href="about.php" class="nav-link active">About</a>
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
<section class="page-hero" id="home">
    <div class="page-hero-bg"></div>
    <div class="page-hero-lines"></div>
    <div class="page-hero-content">
        <div class="page-hero-eyebrow">
            <div class="page-hero-eyebrow-line"></div>
            <span class="page-hero-eyebrow-text">Get To Know Us</span>
            <div class="page-hero-eyebrow-line right"></div>
        </div>
        <h1 class="page-hero-title">
            About<br><em>Hostel</em>
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
        <span class="marquee-text">Heritage &mdash; Craft &mdash; Community &mdash; Precision &mdash; Passion &mdash; </span>
        <span class="marquee-text">Heritage &mdash; Craft &mdash; Community &mdash; Precision &mdash; Passion &mdash; </span>
        <span class="marquee-text">Heritage &mdash; Craft &mdash; Community &mdash; Precision &mdash; Passion &mdash; </span>
        <span class="marquee-text">Heritage &mdash; Craft &mdash; Community &mdash; Precision &mdash; Passion &mdash; </span>
    </div>
</div>

<!-- Story -->
<section class="section story-section" id="story">
    <div class="section-inner">
        <div class="reveal" style="text-align:center;margin-bottom:40px;">
            <div class="section-marker" style="justify-content:center;">
                <span class="section-marker-num">01</span>
                <div class="section-marker-line"></div>
                <span class="section-marker-label">Our Story</span>
                <div class="section-marker-line"></div>
            </div>
            <h2 class="section-title" style="margin-bottom:0;">The Story Behind<br><em>The Blade</em></h2>
        </div>
        <div class="story-content reveal">
            <p>
                Hostel Barbershop was founded in 2020 with a singular vision: to redefine the barbershop experience in Jakarta. What started as a modest two-chair shop has grown into one of the city's most respected destinations for precision grooming. We believe that a great haircut is far more than routine maintenance — it is a statement of identity, confidence, and self-respect.
            </p>
            <p>
                Our team of master barbers brings diverse expertise from across Indonesia. From the precision fades perfected in Cikarang to the artistic scissoring honed in Bali, each artist contributes a unique perspective and specialized technique. This regional diversity is the foundation of our craft, allowing us to match every client with the style that truly represents who they are.
            </p>
            <p>
                Every visit to Hostel Barbershop is designed to be an experience. From the moment you step through our doors, you are welcomed into a space where tradition meets modern aesthetics. Complimentary beverages, premium grooming products, and an atmosphere of genuine camaraderie set us apart from conventional barbershops. We do not simply cut hair — we build relationships, one client at a time.
            </p>
            <div class="story-quote">
                "A haircut can literally decide someone's mood. That is why we treat every single cut as a work of art."
                <div class="story-quote-author">&mdash; Mr. Valen, Co-Founder</div>
            </div>
            <p>
                Today, Hostel Barbershop stands as a testament to what happens when passion meets discipline. Thousands of cuts delivered, countless stories shared, and a community that continues to grow. Whether you are a first-time visitor or a longtime regular, you are part of the Hostel family from the moment you sit in our chair.
            </p>
        </div>
    </div>
</section>

<!-- ================================================================
     VISIT US — COMPACT 2-COLUMN: INFO LEFT | MAP RIGHT
     ================================================================ -->
<section class="visit-section" id="visit">
    <div class="section-inner">

        <div class="visit-header reveal">
            <div class="section-marker" style="justify-content:center;">
                <span class="section-marker-num">02</span>
                <div class="section-marker-line"></div>
                <span class="section-marker-label">Visit Us</span>
                <div class="section-marker-line"></div>
            </div>
            <h2 class="section-title" style="margin-bottom:0;">Hours &<br><em>Location</em></h2>
        </div>

        <div class="visit-grid">

            <!-- LEFT: Hours + Location stacked -->
            <div class="visit-info-col">

                <!-- Hours Card -->
                <div class="v-card reveal" id="hoursCard">
                    <div class="v-card-header">
                        <div class="v-card-icon"><i class="fas fa-clock"></i></div>
                        <div>
                            <div class="v-card-title">Operating Hours</div>
                            <div class="v-card-subtitle">Open 7 Days a Week</div>
                        </div>
                    </div>
                    <div class="hours-body">
                        <div class="hours-row <?= $currentDay === 'Monday' ? 'active' : '' ?>">
                            <div class="hours-day">
                                Mon
                                <?php if ($currentDay === 'Monday'): ?><span class="today-badge">Today</span><?php endif; ?>
                            </div>
                            <div class="hours-time">09:00 — 21:00</div>
                        </div>
                        <div class="hours-row <?= $currentDay === 'Tuesday' ? 'active' : '' ?>">
                            <div class="hours-day">
                                Tue
                                <?php if ($currentDay === 'Tuesday'): ?><span class="today-badge">Today</span><?php endif; ?>
                            </div>
                            <div class="hours-time">09:00 — 21:00</div>
                        </div>
                        <div class="hours-row <?= $currentDay === 'Wednesday' ? 'active' : '' ?>">
                            <div class="hours-day">
                                Wed
                                <?php if ($currentDay === 'Wednesday'): ?><span class="today-badge">Today</span><?php endif; ?>
                            </div>
                            <div class="hours-time">09:00 — 21:00</div>
                        </div>
                        <div class="hours-row <?= $currentDay === 'Thursday' ? 'active' : '' ?>">
                            <div class="hours-day">
                                Thu
                                <?php if ($currentDay === 'Thursday'): ?><span class="today-badge">Today</span><?php endif; ?>
                            </div>
                            <div class="hours-time">09:00 — 21:00</div>
                        </div>
                        <div class="hours-row <?= $currentDay === 'Friday' ? 'active' : '' ?>">
                            <div class="hours-day">
                                Fri
                                <?php if ($currentDay === 'Friday'): ?><span class="today-badge">Today</span><?php endif; ?>
                            </div>
                            <div class="hours-time">09:00 — 21:00</div>
                        </div>
                        <div class="hours-row <?= $currentDay === 'Saturday' ? 'active' : '' ?>">
                            <div class="hours-day">
                                Sat
                                <?php if ($currentDay === 'Saturday'): ?><span class="today-badge">Today</span><?php endif; ?>
                            </div>
                            <div class="hours-time">09:00 — 21:00</div>
                        </div>
                        <div class="hours-row <?= $currentDay === 'Sunday' ? 'active' : '' ?>">
                            <div class="hours-day">
                                Sun
                                <?php if ($currentDay === 'Sunday'): ?><span class="today-badge">Today</span><?php endif; ?>
                            </div>
                            <div class="hours-time">09:00 — 21:00</div>
                        </div>
                    </div>
                </div>

                <!-- Location Card -->
                <div class="v-card reveal delay-1" id="locationCard">
                    <div class="v-card-header">
                        <div class="v-card-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <div class="v-card-title">Find Us</div>
                            <div class="v-card-subtitle">Hostel Barbershop</div>
                        </div>
                    </div>
                    <div class="location-body">
                        <div class="location-row">
                            <div class="location-row-icon"><i class="fas fa-building"></i></div>
                            <div class="location-row-content">
                                <h4>Address</h4>
                                <p>Hostel Streets, Jakarta Pusat, DKI Jakarta</p>
                            </div>
                        </div>
                        <div class="location-row">
                            <div class="location-row-icon"><i class="fas fa-phone-alt"></i></div>
                            <div class="location-row-content">
                                <h4>Phone</h4>
                                <a href="tel:+62123456789">+62 123 456 789</a>
                            </div>
                        </div>
                        <div class="location-row">
                            <div class="location-row-icon"><i class="fab fa-whatsapp"></i></div>
                            <div class="location-row-content">
                                <h4>WhatsApp</h4>
                                <a href="https://wa.me/62123456789" target="_blank">Chat on WhatsApp</a>
                            </div>
                        </div>
                        <div class="location-row">
                            <div class="location-row-icon"><i class="fas fa-envelope"></i></div>
                            <div class="location-row-content">
                                <h4>Email</h4>
                                <a href="mailto:hostelbarbershop@gmail.com">hostelbarbershop@gmail.com</a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT: Map (sticky) -->
            <div class="visit-map-col">
                <div class="map-box reveal delay-2" id="mapBox">
                    <div class="map-overlay"></div>
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d63465.73788544236!2d106.75629964863278!3d-6.1831049!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f431a0a171dd%3A0x9c607baebc815075!2sKonko%20Hostel!5e0!3m2!1sid!2sid!4v1779584186623!5m2!1sid!2sid" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>

        </div>
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
// ===== CURSOR =====
const cursor = document.getElementById('cursor');
const cursorFollower = document.getElementById('cursorFollower');
let mx = 0, my = 0, fx = 0, fy = 0;
let cursorActive = false;

document.addEventListener('mousemove', e => {
    mx = e.clientX; my = e.clientY; cursorActive = true;
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

document.querySelectorAll('a, button, .hours-row, .location-row, .btn-gold').forEach(el => {
    el.addEventListener('mouseenter', () => {
        cursor.classList.add('hover');
        cursorFollower.classList.add('hover');
    }, { passive: true });
    el.addEventListener('mouseleave', () => {
        cursor.classList.remove('hover');
        cursorFollower.classList.remove('hover');
    }, { passive: true });
});

// ===== SCROLL =====
const scrollLine = document.getElementById('scrollLine');
const mainNav = document.getElementById('mainNav');
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

// ===== REVEAL =====
const revealEls = document.querySelectorAll('.reveal');
const revealObs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) entry.target.classList.add('visible');
    });
}, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
revealEls.forEach(el => revealObs.observe(el));

// Card gold line
const cardEls = document.querySelectorAll('.v-card');
const cardObs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) entry.target.classList.add('visible');
    });
}, { threshold: 0.15 });
cardEls.forEach(el => cardObs.observe(el));

// ===== SMOOTH SCROLL =====
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