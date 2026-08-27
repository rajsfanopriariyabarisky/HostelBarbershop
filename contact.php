<?php
/**
 * Hostel Barbershop — Contact Page
 * SendGrid-optimized version
 */

$DEBUG_MODE = true;

error_reporting(E_ALL);
ini_set('display_errors', $DEBUG_MODE ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', 'contact_errors.log');

include 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!file_exists('vendor/autoload.php')) {
    $msg = 'PHPMailer vendor not found. Run: composer require phpmailer/phpmailer';
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => $msg]);
    } else {
        die($msg);
    }
    exit;
}

require 'vendor/autoload.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json; charset=utf-8');

    $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : '';
    $email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
    $phone = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : '';
    $subject = isset($_POST['subject']) ? htmlspecialchars(trim($_POST['subject'])) : '';
    $message = isset($_POST['message']) ? htmlspecialchars(trim($_POST['message'])) : '';

    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields (Name, Email, Message).']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
        exit;
    }

    if (empty($subject)) {
        echo json_encode(['status' => 'error', 'message' => 'Please select a subject.']);
        exit;
    }

    if (!defined('SENDGRID_API_KEY') || empty(SENDGRID_API_KEY) || SENDGRID_API_KEY === 'YOUR_SENDGRID_API_KEY_HERE') {
        echo json_encode(['status' => 'error', 'message' => 'Email service not configured. Please set SENDGRID_API_KEY in config.php']);
        exit;
    }

    if (strpos(SENDGRID_API_KEY, 'SG.') !== 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid SendGrid API Key format. Key must start with "SG."']);
        exit;
    }

    $mail = new PHPMailer(true);

    // Declare these before try so catch can access them
    $fromEmail = defined('FROM_EMAIL') ? FROM_EMAIL : 'noreply@hostelbarbershop.com';
    $fromName = defined('FROM_NAME') ? FROM_NAME : 'Hostel Barbershop';

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.sendgrid.net';
        $mail->SMTPAuth = true;
        $mail->AuthType = 'LOGIN';
        $mail->Username = 'apikey';
        $mail->Password = SENDGRID_API_KEY;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPDebug = $DEBUG_MODE ? 3 : 0;
        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP [$level]: $str");
        };
        $mail->Timeout = 60;
        $mail->SMTPKeepAlive = false;

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $fromEmail = defined('FROM_EMAIL') ? FROM_EMAIL : 'noreply@hostelbarbershop.com';
        $fromName = defined('FROM_NAME') ? FROM_NAME : 'Hostel Barbershop';

        $mail->setFrom($fromEmail, $fromName);
        $mail->addReplyTo($email, $name);
        $mail->addAddress($fromEmail, $fromName);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Contact: ' . $subject . ' from ' . $name;

        $phoneDisplay = !empty($phone) ? $phone : 'Not provided';
        $messageDisplay = nl2br($message);
        $year = date('Y');

        $mail->Body = <<<EMAILBODY
<div style="font-family:'Cormorant Garamond',serif;max-width:600px;margin:0 auto;background:#0a0a0a;color:#f0f0f0;padding:40px;border:1px solid rgba(232,200,122,0.2);">
    <div style="text-align:center;margin-bottom:30px;">
        <h1 style="font-family:'Cormorant Garamond',serif;color:#e8c87a;letter-spacing:4px;margin:0;font-weight:300;font-size:28px;">HOSTEL</h1>
        <p style="color:#9e998f;font-size:11px;letter-spacing:3px;text-transform:uppercase;margin-top:8px;">New Contact Message</p>
    </div>
    <div style="background:#13131a;padding:28px;border:1px solid rgba(255,255,255,0.08);">
        <p style="margin:0 0 14px;font-family:Montserrat,sans-serif;font-size:13px;"><strong style="color:#e8c87a;min-width:80px;display:inline-block;font-weight:600;">Name:</strong> <span style="color:#d5d0c8;">$name</span></p>
        <p style="margin:0 0 14px;font-family:Montserrat,sans-serif;font-size:13px;"><strong style="color:#e8c87a;min-width:80px;display:inline-block;font-weight:600;">Email:</strong> <span style="color:#d5d0c8;">$email</span></p>
        <p style="margin:0 0 14px;font-family:Montserrat,sans-serif;font-size:13px;"><strong style="color:#e8c87a;min-width:80px;display:inline-block;font-weight:600;">Phone:</strong> <span style="color:#d5d0c8;">$phoneDisplay</span></p>
        <p style="margin:0 0 14px;font-family:Montserrat,sans-serif;font-size:13px;"><strong style="color:#e8c87a;min-width:80px;display:inline-block;font-weight:600;">Subject:</strong> <span style="color:#d5d0c8;">$subject</span></p>
        <div style="margin-top:20px;padding-top:20px;border-top:1px solid rgba(255,255,255,0.08);">
            <strong style="color:#e8c87a;display:block;margin-bottom:12px;font-family:Montserrat,sans-serif;font-size:13px;font-weight:600;">Message:</strong>
            <p style="color:#d5d0c8;line-height:1.8;margin:0;font-family:Montserrat,sans-serif;font-size:14px;font-weight:500;">$messageDisplay</p>
        </div>
    </div>
    <div style="text-align:center;margin-top:28px;padding-top:20px;border-top:1px solid rgba(255,255,255,0.08);">
        <p style="color:#9e998f;font-size:11px;letter-spacing:2px;font-family:Montserrat,sans-serif;">Hostel Barbershop &copy; $year</p>
    </div>
</div>
EMAILBODY;

        $mail->send();

        echo json_encode([
            'status' => 'success', 
            'message' => 'Your message has been sent successfully. We will respond within 24 hours.'
        ]);

    } catch (Exception $e) {
        $errorMsg = $mail->ErrorInfo ?: $e->getMessage();
        error_log('Contact Form Error: ' . $errorMsg);
        error_log('SendGrid Debug - API Key starts with: ' . substr(SENDGRID_API_KEY, 0, 10) . '...');
        error_log('SendGrid Debug - Key length: ' . strlen(SENDGRID_API_KEY));
        error_log('SendGrid Debug - From: ' . $fromEmail);
        error_log('SendGrid Debug - To: ' . $fromEmail);

        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to send message. Error: ' . $errorMsg
        ]);
    }

    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact — Hostel Barbershop</title>
<meta name="description" content="Get in touch with Hostel Barbershop. Bookings, collaborations, or just say hello.">

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
:root {
    --bg-primary: #050507;
    --bg-secondary: #0c0c10;
    --bg-card: #13131a;
    --bg-hover: #1e1e28;
    --bg-input: #0e0e14;
    --border: rgba(255, 255, 255, 0.12);
    --border-light: rgba(255, 255, 255, 0.20);
    --border-focus: rgba(232, 200, 122, 0.40);
    --text-primary: #ffffff;
    --text-secondary: #d5d0c8;
    --text-muted: #9e998f;
    --gold: #e8c87a;
    --gold-light: #f5e6c3;
    --gold-dim: rgba(232, 200, 122, 0.08);
    --gold-border: rgba(232, 200, 122, 0.35);
    --danger: #e88484;
    --success: #6ee7a0;
    --radius: 2px;
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
}

.grain {
    position: fixed;
    top: -10px; left: -10px;
    width: calc(100% + 20px);
    height: calc(100% + 20px);
    pointer-events: none;
    z-index: 9997;
    opacity: 0.03;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
}

.scroll-line {
    position: absolute;
    left: 0; bottom: 0;
    width: 0%; height: 3px;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
    z-index: 9990;
    transition: width 0.1s linear;
    box-shadow: 0 0 16px rgba(232,200,122,0.4);
}

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
    cursor: pointer;
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

.page-hero {
    min-height: 55vh;
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
    max-width: 800px;
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
    font-size: clamp(48px, 8vw, 90px);
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

.contact-section {
    padding: 0 48px 120px;
    position: relative;
}

.contact-section::before {
    content: '';
    position: absolute;
    top: 0; left: 50%;
    transform: translateX(-50%);
    width: 1px;
    height: 80px;
    background: linear-gradient(to bottom, transparent, var(--gold-border));
}

.contact-inner {
    max-width: 1280px;
    margin: 0 auto;
}

.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    gap: 80px;
    align-items: start;
}

.contact-info-side {
    position: sticky;
    top: 120px;
}

.contact-info-header {
    margin-bottom: 48px;
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
    font-size: clamp(36px, 5vw, 56px);
    font-weight: 400;
    letter-spacing: -2px;
    line-height: 1.05;
    margin-bottom: 20px;
}

.section-title em {
    font-style: italic;
    color: var(--gold);
}

.contact-info-desc {
    font-size: 15px;
    font-weight: 500;
    color: var(--text-secondary);
    line-height: 1.9;
    max-width: 420px;
}

.contact-cards {
    display: flex;
    flex-direction: column;
    gap: 0;
    border: 1.5px solid var(--border);
}

.contact-card {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    padding: 28px 32px;
    background: var(--bg-card);
    border-bottom: 1.5px solid var(--border);
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.contact-card:last-child { border-bottom: none; }

.contact-card::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 3px;
    background: var(--gold);
    transform: scaleY(0);
    transform-origin: top;
    transition: transform 0.5s var(--ease-out);
}

.contact-card:hover {
    background: var(--bg-hover);
}

.contact-card:hover::before {
    transform: scaleY(1);
}

.contact-card-icon {
    width: 48px;
    height: 48px;
    border: 1.5px solid var(--gold-border);
    background: var(--gold-dim);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gold);
    font-size: 16px;
    flex-shrink: 0;
    transition: var(--transition);
}

.contact-card:hover .contact-card-icon {
    background: var(--gold);
    color: var(--bg-primary);
    border-color: var(--gold);
}

.contact-card-content h4 {
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--text-primary);
    margin-bottom: 6px;
}

.contact-card-content p,
.contact-card-content a {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-secondary);
    line-height: 1.7;
    text-decoration: none;
    transition: var(--transition);
}

.contact-card-content a:hover {
    color: var(--gold);
}

.contact-social {
    margin-top: 40px;
    padding-top: 32px;
    border-top: 1.5px solid var(--border);
}

.contact-social-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 20px;
}

.contact-social-links {
    display: flex;
    gap: 12px;
}

.contact-social-link {
    width: 48px;
    height: 48px;
    border: 1.5px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    font-size: 16px;
    text-decoration: none;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.contact-social-link::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold);
    transform: scale(0);
    transition: transform 0.4s var(--ease-out);
    border-radius: 50%;
}

.contact-social-link:hover {
    border-color: var(--gold);
    color: var(--bg-primary);
}

.contact-social-link:hover::before {
    transform: scale(1);
}

.contact-social-link i {
    position: relative;
    z-index: 1;
}

.form-container {
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    padding: 52px 48px;
    position: relative;
    overflow: hidden;
}

.form-container::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.8s var(--ease-out);
}

.form-container.visible::before {
    transform: scaleX(1);
}

.form-header {
    margin-bottom: 40px;
}

.form-header h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px;
    font-weight: 400;
    letter-spacing: -1px;
    margin-bottom: 8px;
}

.form-header p {
    font-size: 13px;
    color: var(--text-muted);
    letter-spacing: 0.5px;
}

.form-group {
    margin-bottom: 24px;
    position: relative;
}

.form-group:last-of-type {
    margin-bottom: 32px;
}

.form-label {
    display: block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 10px;
    transition: var(--transition);
}

.form-group:focus-within .form-label {
    color: var(--gold);
}

.form-input,
.form-textarea,
.form-select {
    width: 100%;
    background: var(--bg-input);
    border: 1.5px solid var(--border);
    padding: 14px 18px;
    color: var(--text-primary);
    font-size: 14px;
    font-family: 'Montserrat', sans-serif;
    font-weight: 500;
    transition: var(--transition);
    outline: none;
    border-radius: 0;
}

.form-input::placeholder,
.form-textarea::placeholder {
    color: rgba(158, 153, 143, 0.5);
    font-weight: 400;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
    border-color: var(--gold-border);
    background: var(--bg-primary);
    box-shadow: 0 0 0 4px var(--gold-dim);
}

.form-textarea {
    min-height: 140px;
    resize: vertical;
    line-height: 1.7;
}

.form-select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%239e998f' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 16px center;
    padding-right: 40px;
}

.form-select option {
    background: var(--bg-card);
    color: var(--text-primary);
    font-family: 'Montserrat', sans-serif;
}

.submit-btn {
    width: 100%;
    padding: 16px;
    background: var(--gold);
    color: var(--bg-primary);
    border: none;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    cursor: pointer;
    transition: var(--transition);
    font-family: 'Montserrat', sans-serif;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    position: relative;
    overflow: hidden;
}

.submit-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--gold-light);
    transform: translateX(-100%);
    transition: transform 0.5s var(--ease-smooth);
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 20px 50px rgba(232,200,122,0.25);
}

.submit-btn:hover::before {
    transform: translateX(0);
}

.submit-btn span,
.submit-btn i {
    position: relative;
    z-index: 1;
}

.submit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.submit-btn:disabled::before {
    display: none;
}

.loader-bg {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(5,5,7,0.85);
    backdrop-filter: blur(8px);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 24px;
}

.loader-bg.active {
    display: flex;
}

.loader-spinner {
    width: 48px;
    height: 48px;
    border: 2px solid rgba(232,200,122,0.15);
    border-top-color: var(--gold);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

.loader-text {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--gold);
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.modal-bg {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(5,5,7,0.9);
    backdrop-filter: blur(12px);
    z-index: 10001;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.modal-bg.active {
    display: flex;
}

.modal-box {
    background: var(--bg-card);
    border: 1.5px solid var(--border);
    padding: 48px 40px;
    max-width: 420px;
    width: 100%;
    text-align: center;
    position: relative;
    overflow: hidden;
    animation: modalPop 0.5s var(--ease-out);
}

.modal-box::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
}

@keyframes modalPop {
    0% { transform: scale(0.9); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.modal-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    font-size: 24px;
}

.modal-icon.success {
    background: rgba(110, 231, 160, 0.1);
    border: 1.5px solid rgba(110, 231, 160, 0.3);
    color: var(--success);
}

.modal-icon.error {
    background: rgba(232, 132, 132, 0.1);
    border: 1.5px solid rgba(232, 132, 132, 0.3);
    color: var(--danger);
}

.modal-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 28px;
    font-weight: 400;
    letter-spacing: -1px;
    margin-bottom: 12px;
}

.modal-message {
    font-size: 14px;
    color: var(--text-secondary);
    line-height: 1.7;
    margin-bottom: 32px;
}

.modal-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 36px;
    background: transparent;
    border: 1.5px solid var(--gold-border);
    color: var(--gold);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    cursor: pointer;
    transition: var(--transition);
    font-family: 'Montserrat', sans-serif;
}

.modal-btn:hover {
    background: var(--gold);
    color: var(--bg-primary);
    border-color: var(--gold);
}

.marquee-section {
    overflow: hidden;
    padding: 48px 0;
    border-top: 1.5px solid var(--border);
    border-bottom: 1.5px solid var(--border);
    background: var(--bg-secondary);
    margin-bottom: 80px;
}

.marquee-track {
    display: flex;
    width: max-content;
    animation: marqueeScroll 35s linear infinite;
}

.marquee-text {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(40px, 7vw, 80px);
    font-weight: 300;
    letter-spacing: -2px;
    color: rgba(232,200,122,0.06);
    white-space: nowrap;
    padding: 0 48px;
    text-transform: uppercase;
}

@keyframes marqueeScroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

.reveal { opacity: 0; transform: translateY(50px); transition: opacity 0.9s var(--ease-smooth), transform 0.9s var(--ease-smooth); }
.reveal.visible { opacity: 1; transform: translateY(0); }
.reveal-left { opacity: 0; transform: translateX(-50px); transition: opacity 0.9s var(--ease-smooth), transform 0.9s var(--ease-smooth); }
.reveal-left.visible { opacity: 1; transform: translateX(0); }
.reveal-right { opacity: 0; transform: translateX(50px); transition: opacity 0.9s var(--ease-smooth), transform 0.9s var(--ease-smooth); }
.reveal-right.visible { opacity: 1; transform: translateX(0); }
.delay-1 { transition-delay: 0.1s; }
.delay-2 { transition-delay: 0.2s; }
.delay-3 { transition-delay: 0.3s; }
.delay-4 { transition-delay: 0.4s; }

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

::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--bg-primary); }
::-webkit-scrollbar-thumb { background: var(--border-light); }
::-webkit-scrollbar-thumb:hover { background: var(--gold-border); }

@media(max-width:1024px) {
    .contact-grid { grid-template-columns: 1fr; gap: 60px; }
    .contact-info-side { position: static; }
    .contact-cards { max-width: 500px; }
}

@media(max-width:900px) {
    .footer-top { grid-template-columns: 1fr 1fr; gap: 40px; }
    .footer { padding: 60px 24px 40px; }
    .page-hero { padding: 120px 24px 60px; }
    .contact-section { padding: 0 24px 80px; }
    .form-container { padding: 36px 28px; }
}

@media(max-width:600px) {
    .footer-top { grid-template-columns: 1fr; }
    .footer-bottom { flex-direction: column; text-align: center; }
    .page-hero-title { font-size: 40px; }
    .contact-card { padding: 20px 24px; }
    .modal-box { padding: 36px 24px; }
}
</style>
</head>
<body>

<div class="grain"></div>

<nav class="nav-wrapper" id="mainNav">
    <div class="nav-inner">
        <div class="nav-left-group">
            <a href="index.php" class="nav-logo">
                <img src="hostel.png" alt="Hostel Barbershop" class="nav-logo-img">
            </a>
            <div class="nav-links" id="navLinks">
                <a href="index.php" class="nav-link">Home</a>
                <a href="hair_artists.php" class="nav-link">Hair Artists</a>
                <a href="contact.php" class="nav-link active">Contact</a>
                <a href="about.php" class="nav-link">About</a>
            </div>
        </div>
        <a href="login.php" class="nav-login-btn"><span>Sign In</span></a>
        <button class="nav-mobile-toggle" id="mobileToggle" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    <div class="scroll-line" id="scrollLine"></div>
</nav>

<section class="page-hero" id="home">
    <div class="page-hero-bg"></div>
    <div class="page-hero-lines"></div>
    <div class="page-hero-content">
        <div class="page-hero-eyebrow">
            <div class="page-hero-eyebrow-line"></div>
            <span class="page-hero-eyebrow-text">Connect With Us</span>
            <div class="page-hero-eyebrow-line right"></div>
        </div>
        <h1 class="page-hero-title">Let's Start a<br><em>Conversation</em></h1>
        <div class="page-hero-divider">
            <div class="page-hero-divider-line"></div>
            <div class="page-hero-divider-diamond"></div>
            <div class="page-hero-divider-line"></div>
        </div>
</section>

<div class="marquee-section">
    <div class="marquee-track">
        <span class="marquee-text">Reach Out &mdash; Collaborate &mdash; Book Now &mdash; Stay Connected &mdash; </span>
        <span class="marquee-text">Reach Out &mdash; Collaborate &mdash; Book Now &mdash; Stay Connected &mdash; </span>
        <span class="marquee-text">Reach Out &mdash; Collaborate &mdash; Book Now &mdash; Stay Connected &mdash; </span>
        <span class="marquee-text">Reach Out &mdash; Collaborate &mdash; Book Now &mdash; Stay Connected &mdash; </span>
    </div>
</div>

<section class="contact-section" id="contact">
    <div class="contact-inner">
        <div class="contact-grid">
            <div class="contact-info-side reveal-left">
                <div class="contact-info-header">
                    <div class="section-marker">
                        <span class="section-marker-num">01</span>
                        <div class="section-marker-line"></div>
                        <span class="section-marker-label">Get In Touch</span>
                    </div>
                    <h2 class="section-title">Contact<br><em>Information</em></h2>
                    <p class="contact-info-desc">We value every conversation. Reach out through any channel below and our team will get back to you within 24 hours.</p>
                </div>
                <div class="contact-cards">
                    <div class="contact-card">
                        <div class="contact-card-icon"><i class="fas fa-envelope"></i></div>
                        <div class="contact-card-content">
                            <h4>Email</h4>
                            <a href="mailto:hello@hostelbarbershop.com">hostelbarbershop@gmail.com</a>
                        </div>
                    </div>
                    <div class="contact-card">
                        <div class="contact-card-icon"><i class="fas fa-phone-alt"></i></div>
                        <div class="contact-card-content">
                            <h4>Phone</h4>
                            <a href="tel:+6281234567890">+62 123 456 789</a>
                        </div>
                    </div>
                    <div class="contact-card">
                        <div class="contact-card-icon"><i class="fab fa-whatsapp"></i></div>
                        <div class="contact-card-content">
                            <h4>WhatsApp</h4>
                            <a href="https://wa.me/62123456789" target="_blank">Chat on WhatsApp</a>
                        </div>
                    </div>
                </div>
                <div class="contact-social">
                    <div class="contact-social-label">Follow Us</div>
                    <div class="contact-social-links">
                        <a href="https://www.instagram.com/hostelbarber.inc/" class="contact-social-link" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.instagram.com/hostelbarber.inc/" class="contact-social-link" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                        <a href="https://www.instagram.com/hostelbarber.inc/" class="contact-social-link" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            <div class="form-container reveal-right" id="formContainer">
                <div class="form-header">
                    <h3>Send a Message</h3>
                    <p>Fill out the form below and we will respond shortly.</p>
                </div>
                <form id="contactForm">
                    <div class="form-group">
                        <label class="form-label">Your Name *</label>
                        <input type="text" name="name" class="form-input" placeholder="e.g. John Doe" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-input" placeholder="e.g. john@example.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-input" placeholder="e.g. +62 812 3456 7890">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subject *</label>
                        <select name="subject" class="form-select" required>
                            <option value="" disabled selected>Select a subject</option>
                            <option value="Booking Inquiry">Booking Inquiry</option>
                            <option value="General Question">General Question</option>
                            <option value="Collaboration">Collaboration</option>
                            <option value="Feedback">Feedback</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Message *</label>
                        <textarea name="message" class="form-textarea" placeholder="Tell us what is on your mind..." required></textarea>
                    </div>
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <span>Send Message</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<div class="loader-bg" id="loaderBg">
    <div class="loader-spinner"></div>
    <div class="loader-text">Sending...</div>
</div>

<div class="modal-bg" id="modalBg">
    <div class="modal-box">
        <div class="modal-icon" id="modalIcon"><i class="fas fa-check"></i></div>
        <h3 class="modal-title" id="modalTitle">Success!</h3>
        <p class="modal-message" id="modalMessage">Your message has been sent.</p>
        <button class="modal-btn" id="modalBtn"><span>OK</span></button>
    </div>
</div>


<script>
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

const revealEls = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
const revealObs = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) entry.target.classList.add('visible');
    });
}, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
revealEls.forEach(el => revealObs.observe(el));

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

const form = document.getElementById('contactForm');
const submitBtn = document.getElementById('submitBtn');
const loaderBg = document.getElementById('loaderBg');
const modalBg = document.getElementById('modalBg');
const modalIcon = document.getElementById('modalIcon');
const modalTitle = document.getElementById('modalTitle');
const modalMessage = document.getElementById('modalMessage');
const modalBtn = document.getElementById('modalBtn');

function showModal(status, title, message) {
    modalIcon.className = 'modal-icon ' + status;
    modalIcon.innerHTML = status === 'success' 
        ? '<i class="fas fa-check"></i>' 
        : '<i class="fas fa-exclamation"></i>';
    modalTitle.textContent = title;
    modalMessage.textContent = message;
    modalBg.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function hideModal() {
    modalBg.classList.remove('active');
    document.body.style.overflow = '';
}

modalBtn.addEventListener('click', hideModal);
modalBg.addEventListener('click', (e) => {
    if (e.target === modalBg) hideModal();
});

form.addEventListener('submit', (e) => {
    e.preventDefault();
    loaderBg.classList.add('active');
    document.body.style.overflow = 'hidden';
    submitBtn.disabled = true;

    fetch('contact.php', {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => {
        if (!res.ok) throw new Error('Server returned ' + res.status);
        return res.json();
    })
    .then(data => {
        loaderBg.classList.remove('active');
        submitBtn.disabled = false;
        if (data.status === 'success') {
            showModal('success', 'Message Sent!', data.message);
            form.reset();
        } else {
            showModal('error', 'Failed', data.message);
        }
    })
    .catch((err) => {
        loaderBg.classList.remove('active');
        submitBtn.disabled = false;
        showModal('error', 'Error', 'Something went wrong: ' + err.message);
    });
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modalBg.classList.contains('active')) hideModal();
});
</script>

</body>
</html>