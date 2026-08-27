<?php
date_default_timezone_set('Asia/Jakarta');
include 'config.php';
include 'midtrans_config.php';

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

$transaction_details = [
    'order_id' => 'BOOK-'.$d['id'].'-'.time(),
    'gross_amount' => $d['harga'],
];

$item_details = [
    [
        'id' => $d['paket_id'],
        'price' => $d['harga'],
        'quantity' => 1,
        'name' => $d['nama_paket']
    ]
];

$customer_details = [
    'first_name' => $_SESSION['user']['username'] ?? 'Customer'
];

$params = [
    'transaction_details' => $transaction_details,
    'item_details' => $item_details,
    'customer_details' => $customer_details
];

$snapToken = \Midtrans\Snap::getSnapToken($params);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | Hostel Barbershop</title>
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
        --success: #6ee7a0;
        --danger: #e88484;
        --warning: #e8c87a;
        --radius: 2px;
        --transition: all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
    }
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        background: var(--bg-primary);
        color: var(--text-primary);
        font-family: 'Montserrat', sans-serif;
        line-height: 1.6;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        -webkit-font-smoothing: antialiased;
    }

    /* ===== PAYMENT CARD ===== */
    .payment-card {
        width: 100%;
        max-width: 480px;
        background: var(--bg-secondary);
        border: 1.5px solid var(--border);
        border-radius: var(--radius);
        padding: 40px;
        position: relative;
        overflow: hidden;
        animation: fadeUp 0.5s ease forwards;
        opacity: 0;
    }
    .payment-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: var(--gold);
    }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ===== BACK LINK ===== */
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 24px;
        color: var(--text-muted);
        text-decoration: none;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        transition: var(--transition);
    }
    .back-link:hover { color: var(--gold); }
    .back-link i {
        font-size: 10px;
        transition: transform 0.3s ease;
    }
    .back-link:hover i { transform: translateX(-4px); }

    /* ===== HEADER ===== */
    .card-header {
        text-align: center;
        margin-bottom: 28px;
    }
    .card-header-icon {
        width: 56px; height: 56px;
        border-radius: 50%;
        background: var(--gold-dim);
        border: 1.5px solid var(--gold-border);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin: 0 auto 16px;
        color: var(--gold);
    }
    .card-header h1 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 28px;
        font-weight: 400;
        letter-spacing: -1px;
        margin-bottom: 4px;
    }
    .card-header p {
        font-size: 13px;
        color: var(--text-muted);
    }
    .order-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        border-radius: var(--radius);
        background: var(--bg-card);
        border: 1.5px solid var(--border);
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 14px;
        font-weight: 600;
        letter-spacing: 1px;
    }
    .order-badge .dot {
        width: 6px; height: 6px;
        background: var(--warning);
        border-radius: 50%;
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }

    /* ===== BARBER INFO ===== */
    .barber-info {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 20px;
        background: var(--bg-card);
        border: 1.5px solid var(--border);
        border-radius: var(--radius);
        margin-bottom: 20px;
    }
    .barber-avatar {
        width: 56px; height: 56px;
        border-radius: var(--radius);
        object-fit: cover;
        border: 1.5px solid var(--border);
        filter: grayscale(20%);
        transition: filter 0.4s ease;
        flex-shrink: 0;
    }
    .barber-info:hover .barber-avatar { filter: grayscale(0%); }
    .barber-text h3 {
        font-family: 'Cormorant Garamond', serif;
        font-size: 20px;
        font-weight: 400;
        letter-spacing: -0.5px;
        margin-bottom: 2px;
    }
    .barber-text p {
        font-size: 12px;
        color: var(--text-muted);
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-clamp: 1;
    }

    /* ===== DETAILS ===== */
    .details {
        margin-bottom: 20px;
    }
    .detail-row {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 14px 0;
        border-bottom: 1.5px solid var(--border);
    }
    .detail-row:last-child {
        border-bottom: none;
    }
    .detail-icon {
        width: 36px; height: 36px;
        border-radius: var(--radius);
        background: var(--bg-card);
        border: 1.5px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--gold);
        font-size: 12px;
        flex-shrink: 0;
        margin-top: 2px;
        transition: var(--transition);
    }
    .detail-row:hover .detail-icon {
        border-color: var(--gold-border);
        background: var(--gold-dim);
    }
    .detail-content {
        flex: 1;
        min-width: 0;
    }
    .detail-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 4px;
    }
    .detail-value {
        font-weight: 600;
        font-size: 13px;
        color: var(--text-primary);
        letter-spacing: 0.3px;
        line-height: 1.5;
        word-break: break-word;
    }

    /* ===== TOTAL ===== */
    .total-box {
        background: var(--bg-card);
        border: 1.5px solid var(--border);
        border-radius: var(--radius);
        padding: 20px;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
    }
    .total-box::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 2px;
        background: linear-gradient(90deg, var(--gold), transparent);
        opacity: 0.5;
    }
    .total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .total-label {
        font-size: 11px;
        color: var(--text-muted);
        font-weight: 600;
        letter-spacing: 2px;
        text-transform: uppercase;
    }
    .total-value {
        font-size: 28px;
        font-weight: 300;
        font-family: 'Cormorant Garamond', serif;
        letter-spacing: -1px;
        color: var(--gold);
    }

    /* ===== SECURITY ===== */
    .security {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-bottom: 20px;
        color: var(--text-muted);
        font-size: 11px;
        letter-spacing: 0.5px;
    }
    .security i {
        color: var(--gold);
        font-size: 12px;
    }

    /* ===== PAY BUTTON ===== */
    .pay-btn {
        width: 100%;
        padding: 16px 24px;
        border: none;
        border-radius: var(--radius);
        background: var(--gold);
        color: var(--bg-primary);
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        font-size: 11px;
        letter-spacing: 2.5px;
        text-transform: uppercase;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }
    .pay-btn::before {
        content: '';
        position: absolute;
        inset: 0;
        background: var(--gold-light);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
    }
    .pay-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 40px rgba(232,200,122,0.25);
    }
    .pay-btn:hover::before { transform: scaleX(1); }
    .pay-btn span, .pay-btn i { position: relative; z-index: 1; }
    .pay-btn i { font-size: 13px; }
    .pay-btn:active { transform: translateY(0); }

    /* Loading */
    .pay-btn.loading {
        pointer-events: none;
        color: transparent;
    }
    .pay-btn.loading::after {
        content: '';
        position: absolute;
        width: 18px; height: 18px;
        border: 2px solid var(--bg-primary);
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* ===== FOOTER ===== */
    .footer {
        text-align: center;
        margin-top: 20px;
        color: var(--text-muted);
        font-size: 10px;
        letter-spacing: 0.5px;
    }
    .footer a {
        color: var(--text-secondary);
        text-decoration: none;
        border-bottom: 1px solid var(--border);
        transition: var(--transition);
    }
    .footer a:hover {
        color: var(--gold);
        border-color: var(--gold-border);
    }

    /* ===== RESPONSIVE ===== */
    @media(max-width: 520px) {
        body { padding: 16px; }
        .payment-card { padding: 28px; }
        .card-header h1 { font-size: 24px; }
        .total-value { font-size: 24px; }
        .barber-info { padding: 16px; }
        .detail-row { gap: 12px; padding: 12px 0; }
    }
    </style>
</head>
<body>

    <div class="payment-card">
        <!-- Back -->
        <a href="booking.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Booking
        </a>

        <!-- Header -->
        <div class="card-header">
            <div class="card-header-icon"><i class="fas fa-credit-card"></i></div>
            <h1>Complete Payment</h1>
            <p>Review your booking details</p>
            <div class="order-badge">
                <span class="dot"></span>
                <span>HSTL #<?= $d['id'] ?></span>
            </div>
        </div>

        <!-- Barber -->
        <div class="barber-info">
            <img src="admin/upload/<?= $d['barber_foto'] ?>" 
                 alt="<?= $d['barber'] ?>" 
                 class="barber-avatar"
                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($d['barber']) ?>&background=13131a&color=e8c87a&size=128'">
            <div class="barber-text">
                <h3><?= $d['barber'] ?></h3>
                <p><?= $d['barber_desc'] ?></p>
            </div>
        </div>

        <!-- Details -->
        <div class="details">
            <div class="detail-row">
                <div class="detail-icon"><i class="fas fa-box"></i></div>
                <div class="detail-content">
                    <div class="detail-label">Package</div>
                    <div class="detail-value"><?= $d['nama_paket'] ?></div>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-icon"><i class="fas fa-calendar"></i></div>
                <div class="detail-content">
                    <div class="detail-label">Date</div>
                    <div class="detail-value"><?= date('d M Y', strtotime($d['tanggal'])) ?></div>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-icon"><i class="fas fa-clock"></i></div>
                <div class="detail-content">
                    <div class="detail-label">Time</div>
                    <div class="detail-value"><?= $d['jam'] ?> WIB</div>
                </div>
            </div>
        </div>

        <!-- Total -->
        <div class="total-box">
            <div class="total-row">
                <span class="total-label">Total Payment</span>
                <span class="total-value">Rp<?= number_format($d['harga'], 0, ',', '.') ?></span>
            </div>
        </div>

        <!-- Security -->
        <div class="security">
            <i class="fas fa-shield-alt"></i>
            <span>Secure & encrypted by Midtrans</span>
        </div>

        <!-- Pay Button -->
        <button id="pay-button" class="pay-btn">
            <i class="fas fa-lock"></i> <span>Pay Now</span>
        </button>

        <!-- Footer -->
        <div class="footer">
            By continuing, you agree to our <a href="#">Terms & Conditions</a>
        </div>
    </div>

<!-- Midtrans Snap -->
<script src="https://app.sandbox.midtrans.com/snap/snap.js"
data-client-key="SB-Mid-client-XX49s46zZYjgbua7"></script>

<script>
document.getElementById('pay-button').onclick = function () {
    const btn = this;
    btn.classList.add('loading');
    
    snap.pay('<?= $snapToken ?>', {
        onSuccess: function(result){
            window.location.href = "payment_success.php?id=<?= $d['id'] ?>";
        },
        onPending: function(result){
            btn.classList.remove('loading');
            alert("Payment pending. Please check your email for payment instructions.");
        },
        onError: function(result){
            btn.classList.remove('loading');
            alert("Payment failed. Please try again.");
        },
        onClose: function(){
            btn.classList.remove('loading');
        }
    });
};
</script>

</body>
</html>