<?php
date_default_timezone_set('Asia/Jakarta');
include 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id <= 0){
    header("Location: booking.php");
    exit;
}

$q = mysqli_query($conn, "
SELECT b.*, p.nama_paket, p.harga, br.nama as barber, br.foto as barber_foto, br.keterangan as barber_desc
FROM booking b
JOIN paket p ON b.paket_id = p.id
JOIN barber br ON b.barber_id = br.id
WHERE b.id = '$id' AND b.user_id = '".$_SESSION['user']['id']."'
");

if(mysqli_num_rows($q) == 0){
    header("Location: booking.php");
    exit;
}

$d = mysqli_fetch_assoc($q);

$orderId = 'HSTL-' . str_pad($d['id'], 4, '0', STR_PAD_LEFT);
$bookingDate = date('d M Y', strtotime($d['tanggal']));
$bookingTime = $d['jam'] . ' WIB';
$createdAt = date('d M Y, H:i', strtotime($d['created_at']));
$arrivalTime = date('H:i', strtotime('-15 minutes', strtotime($d['tanggal'] . ' ' . $d['jam'])));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success | Hostel Barbershop</title>
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
            --success-dim: rgba(110, 231, 160, 0.08);
            --success-border: rgba(110, 231, 160, 0.25);
            --warning: #e8c87a;
            --warning-dim: rgba(232, 200, 122, 0.08);
            --warning-border: rgba(232, 200, 122, 0.25);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: 
                radial-gradient(circle at 30% 20%, rgba(232, 200, 122, 0.04) 0%, transparent 50%),
                radial-gradient(circle at 70% 80%, rgba(110, 231, 160, 0.03) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .container {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
        }

        /* Success Header */
        .success-header {
            text-align: center;
            margin-bottom: 14px;
            animation: fadeIn 0.5s ease;
        }

        .success-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--success-dim);
            border: 1.5px solid var(--success-border);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            animation: circlePop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes circlePop {
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .success-circle i {
            font-size: 26px;
            color: var(--success);
        }

        .success-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            font-weight: 400;
            letter-spacing: -1px;
            margin-bottom: 3px;
        }

        .success-subtitle {
            color: var(--text-muted);
            font-size: 12px;
        }

        /* Reminder Banner */
        .reminder-banner {
            background: linear-gradient(135deg, var(--warning-dim) 0%, var(--bg-card) 100%);
            border: 1px solid var(--warning-border);
            border-radius: 14px;
            padding: 12px 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            position: relative;
            animation: slideDown 0.5s ease 0.3s both;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .reminder-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 3px;
            height: 100%;
            background: var(--warning);
            border-radius: 14px 0 0 14px;
        }

        .reminder-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: var(--warning-dim);
            border: 1px solid var(--warning-border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--warning);
            font-size: 13px;
            flex-shrink: 0;
            animation: bellShake 2s ease-in-out infinite;
        }

        @keyframes bellShake {
            0%, 100% { transform: rotate(0deg); }
            10% { transform: rotate(6deg); }
            20% { transform: rotate(-6deg); }
            30% { transform: rotate(3deg); }
            40% { transform: rotate(-3deg); }
            50% { transform: rotate(0deg); }
        }

        .reminder-text {
            flex: 1;
            font-size: 12px;
            line-height: 1.5;
            color: var(--text-secondary);
        }

        .reminder-text strong {
            color: var(--warning);
        }

        /* Card */
        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            animation: fadeIn 0.5s ease 0.2s both;
        }

        /* Card Header */
        .card-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .order-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .order-label {
            font-size: 10px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .order-value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            font-weight: 600;
            color: var(--success);
            letter-spacing: 0.5px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 100px;
            background: var(--success-dim);
            border: 1px solid var(--success-border);
            color: var(--success);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .status-badge i {
            font-size: 6px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        /* Card Body */
        .card-body {
            padding: 14px 20px;
        }

        /* Barber */
        .barber-section {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .barber-avatar {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            object-fit: cover;
            border: 1.5px solid var(--border);
            background: var(--bg-card);
            flex-shrink: 0;
        }

        .barber-info h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px;
            font-weight: 400;
            margin-bottom: 1px;
            letter-spacing: -0.5px;
        }

        .barber-info p {
            font-size: 11px;
            color: var(--text-muted);
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-clamp: 2;
        }

        /* Details Compact */
        .details-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-bottom: 12px;
        }

        .detail-box {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px 8px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .detail-box:hover {
            border-color: var(--gold-border);
        }

        .detail-icon-small {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-size: 10px;
            margin: 0 auto 5px;
        }

        .detail-label-small {
            font-size: 9px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .detail-value-small {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-clamp: 2;
        }

        /* Total */
        .total-section {
            background: linear-gradient(135deg, var(--success-dim) 0%, var(--bg-card) 100%);
            border: 1px solid var(--success-border);
            border-radius: 14px;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .total-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 3px;
            height: 100%;
            background: var(--success);
        }

        .total-label-group {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .total-label {
            font-size: 11px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .total-sublabel {
            font-size: 10px;
            color: var(--text-muted);
        }

        .total-value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 24px;
            font-weight: 400;
            color: var(--success);
            letter-spacing: -0.5px;
        }

        /* Card Footer */
        .card-footer {
            padding: 10px 20px;
            border-top: 1px solid var(--border);
            background: var(--bg-card);
        }

        .meta-info {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: var(--text-muted);
        }

        .meta-info i {
            font-size: 9px;
            color: var(--gold);
        }

        /* Actions */
        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 12px;
            animation: fadeIn 0.5s ease 0.5s both;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 13px 16px;
            border-radius: 14px;
            font-family: 'Montserrat', sans-serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background: var(--gold);
            color: var(--bg-primary);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(232,200,122,0.2);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            border-color: var(--gold);
            color: var(--gold);
            background: var(--gold-dim);
            transform: translateY(-2px);
        }

        .btn i {
            font-size: 13px;
        }

        /* Dashboard Link */
        .dashboard-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 12px;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease 0.6s both;
        }

        .dashboard-link:hover {
            color: var(--gold);
        }

        .dashboard-link i {
            font-size: 10px;
        }

        /* Confetti */
        .confetti-container {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 9999;
            overflow: hidden;
        }

        .confetti {
            position: absolute;
            width: 8px;
            height: 8px;
            opacity: 0;
            animation: confettiFall 3s ease-out forwards;
        }

        .confetti:nth-child(1) { left: 10%; background: var(--gold); animation-delay: 0.1s; }
        .confetti:nth-child(2) { left: 20%; background: var(--success); animation-delay: 0.2s; width: 6px; height: 6px; }
        .confetti:nth-child(3) { left: 30%; background: var(--gold); animation-delay: 0.3s; width: 10px; height: 5px; }
        .confetti:nth-child(4) { left: 40%; background: var(--text-primary); animation-delay: 0.4s; width: 5px; height: 10px; }
        .confetti:nth-child(5) { left: 50%; background: var(--gold); animation-delay: 0.5s; }
        .confetti:nth-child(6) { left: 60%; background: var(--success); animation-delay: 0.6s; width: 6px; height: 6px; }
        .confetti:nth-child(7) { left: 70%; background: var(--gold); animation-delay: 0.7s; width: 10px; height: 5px; }
        .confetti:nth-child(8) { left: 80%; background: var(--text-primary); animation-delay: 0.8s; width: 5px; height: 10px; }
        .confetti:nth-child(9) { left: 90%; background: var(--gold); animation-delay: 0.9s; }

        @keyframes confettiFall {
            0% { opacity: 1; transform: translateY(-100px) rotate(0deg); }
            100% { opacity: 0; transform: translateY(100vh) rotate(720deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Responsive */
        @media (max-width: 480px) {
            body { padding: 8px; }
            .container { max-width: 100%; }
            .success-title { font-size: 24px; }
            .details-row { grid-template-columns: 1fr; }
            .detail-box { display: flex; align-items: center; gap: 10px; text-align: left; padding: 8px 12px; }
            .detail-icon-small { margin: 0; }
            .actions { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Confetti -->
    <div class="confetti-container" id="confetti">
        <div class="confetti"></div>
        <div class="confetti"></div>
        <div class="confetti"></div>
        <div class="confetti"></div>
        <div class="confetti"></div>
        <div class="confetti"></div>
        <div class="confetti"></div>
        <div class="confetti"></div>
        <div class="confetti"></div>
    </div>

    <div class="container">
        <!-- Success Header -->
        <div class="success-header">
            <div class="success-circle">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="success-title">Payment Successful!</h1>
            <p class="success-subtitle">Your booking has been confirmed</p>
        </div>

        <!-- Reminder Banner -->
        <div class="reminder-banner">
            <div class="reminder-icon">
                <i class="fas fa-bell"></i>
            </div>
            <div class="reminder-text">
                <strong>Reminder:</strong> Please arrive <strong>15 minutes before</strong> your schedule. Be there by <strong><?= $arrivalTime ?> WIB</strong>.
            </div>
        </div>

        <!-- Receipt Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header">
                <div class="order-info">
                    <span class="order-label">Order ID</span>
                    <span class="order-value"><?= $orderId ?></span>
                </div>
                <div class="status-badge">
                    <i class="fas fa-circle"></i> Paid
                </div>
            </div>

            <!-- Body -->
            <div class="card-body">
                <!-- Barber -->
                <div class="barber-section">
                    <img src="admin/upload/<?= $d['barber_foto'] ?>" 
                         alt="<?= $d['barber'] ?>" 
                         class="barber-avatar"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($d['barber']) ?>&background=13131a&color=e8c87a&size=128'">
                    <div class="barber-info">
                        <h3><?= $d['barber'] ?></h3>
                        <p><?= $d['barber_desc'] ?></p>
                    </div>
                </div>

                <!-- Details Grid -->
                <div class="details-row">
                    <div class="detail-box">
                        <div class="detail-icon-small"><i class="fas fa-box"></i></div>
                        <div class="detail-label-small">Package</div>
                        <div class="detail-value-small"><?= $d['nama_paket'] ?></div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-icon-small"><i class="fas fa-calendar"></i></div>
                        <div class="detail-label-small">Date</div>
                        <div class="detail-value-small"><?= $bookingDate ?></div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-icon-small"><i class="fas fa-clock"></i></div>
                        <div class="detail-label-small">Time</div>
                        <div class="detail-value-small"><?= $bookingTime ?></div>
                    </div>
                </div>

                <!-- Total -->
                <div class="total-section">
                    <div class="total-label-group">
                        <span class="total-label">Total</span>
                        <span class="total-sublabel">Paid</span>
                    </div>
                    <span class="total-value">Rp<?= number_format($d['harga'], 0, ',', '.') ?></span>
                </div>
            </div>

            <!-- Footer -->
            <div class="card-footer">
                <div class="meta-info">
                    <i class="fas fa-clock"></i>
                    <span>Paid on <?= $createdAt ?></span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="actions">
            <a href="download.php?id=<?= $id ?>" class="btn btn-primary">
                <i class="fas fa-download"></i>
                Download
            </a>
            <a href="history.php" class="btn btn-secondary">
                <i class="fas fa-history"></i>
                History
            </a>
        </div>

        <!-- Dashboard Link -->
        <a href="dashboard.php" class="dashboard-link">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>
    </div>

    <script>
        setTimeout(() => {
            document.getElementById('confetti').style.display = 'none';
        }, 3500);
    </script>

</body>
</html>