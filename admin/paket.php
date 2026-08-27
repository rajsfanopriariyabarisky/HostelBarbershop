<?php

include '../config.php';
global $conn;

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

// Flash message handler
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// STATS
$stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_paket,
        SUM(harga) as total_value,
        ROUND(AVG(harga), 0) as avg_price,
        MAX(harga) as highest_price
    FROM paket
"));

// GET ALL PACKAGES
$search = $_GET['search'] ?? '';
$searchQuery = '';
if(!empty($search)){
    $searchEscaped = mysqli_real_escape_string($conn, $search);
    $searchQuery = "WHERE nama_paket LIKE '%$searchEscaped%'";
}

$q = mysqli_query($conn, "SELECT * FROM paket $searchQuery ORDER BY id DESC");
$totalResults = mysqli_num_rows($q);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Packages | Barber Management</title>
    
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
        --danger-soft:rgba(255,68,68,0.1);
        --success:#00ff88;
        --success-soft:rgba(0,255,136,0.1);
        --warning:#ffaa00;
        --warning-soft:rgba(255,170,0,0.1);
        --info:#4488ff;
        --info-soft:rgba(68,136,255,0.1);
        --gold:#ffd700;
        --gold-soft:rgba(255,215,0,0.1);
        --shadow:0 4px 24px rgba(0,0,0,0.5);
        --shadow-hover:0 8px 40px rgba(0,0,0,0.6);
        --radius:18px;
        --radius-sm:12px;
        --radius-xs:8px;
        --transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
    }

    *{margin:0;padding:0;box-sizing:border-box;}

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
        align-items:center;
        justify-content:space-between;
        margin-bottom:40px;
        padding-bottom:24px;
        border-bottom:2px solid var(--border);
        position:relative;
        flex-wrap:wrap;
        gap:16px;
    }

    .page-header::after{
        content:'';
        position:absolute;
        bottom:-2px;
        left:0;
        width:200px;
        height:2px;
        background:var(--text-primary);
    }

    .page-title h1{
        font-family:'Space Grotesk',sans-serif;
        font-size:36px;
        font-weight:700;
        letter-spacing:-2px;
        line-height:1.1;
        margin-bottom:6px;
    }

    .page-title p{
        color:var(--text-secondary);
        font-size:14px;
        font-weight:400;
    }

    .header-actions{
        display:flex;
        align-items:center;
        gap:12px;
    }

    .back-btn{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:10px 20px;
        border-radius:var(--radius-xs);
        background:var(--bg-secondary);
        border:2px solid var(--border);
        color:var(--text-secondary);
        text-decoration:none;
        font-size:13px;
        font-weight:600;
        transition:var(--transition);
    }

    .back-btn:hover{
        border-color:var(--border-light);
        color:var(--text-primary);
        transform:translateX(-4px);
    }

    /* ===== STATS GRID ===== */
    .stats-grid{
        display:grid;
        grid-template-columns:repeat(4,1fr);
        gap:16px;
        margin-bottom:32px;
    }

    @media(max-width:1100px){
        .stats-grid{grid-template-columns:repeat(2,1fr);}
    }
    @media(max-width:600px){
        .stats-grid{grid-template-columns:1fr;}
    }

    .stat-card{
        background:var(--bg-secondary);
        border:2px solid var(--border);
        border-radius:var(--radius);
        padding:24px;
        transition:var(--transition);
        position:relative;
        overflow:hidden;
    }

    .stat-card::before{
        content:'';
        position:absolute;
        top:0;
        left:0;
        right:0;
        height:3px;
        background:var(--accent);
        opacity:0;
        transition:opacity 0.3s ease;
    }

    .stat-card:hover{
        transform:translateY(-4px);
        border-color:var(--border-light);
        box-shadow:var(--shadow-hover);
    }

    .stat-card:hover::before{opacity:1;}

    .stat-icon{
        width:44px;
        height:44px;
        border-radius:var(--radius-xs);
        background:var(--bg-tertiary);
        border:2px solid var(--border);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:18px;
        margin-bottom:16px;
        transition:var(--transition);
    }

    .stat-card:hover .stat-icon{
        background:var(--text-primary);
        color:var(--bg-primary);
        border-color:var(--text-primary);
    }

    .stat-icon.gold{color:var(--gold);border-color:rgba(255,215,0,0.3);}
    .stat-card:hover .stat-icon.gold{background:var(--gold);color:var(--bg-primary);}

    .stat-value{
        font-family:'Space Grotesk',sans-serif;
        font-size:28px;
        font-weight:700;
        letter-spacing:-1px;
        margin-bottom:4px;
    }

    .stat-label{
        font-size:12px;
        color:var(--text-muted);
        font-weight:600;
        text-transform:uppercase;
        letter-spacing:0.5px;
    }

    /* ===== MAIN GRID ===== */
    .main-grid{
        display:grid;
        grid-template-columns:380px 1fr;
        gap:24px;
        margin-bottom:32px;
    }

    @media(max-width:1100px){
        .main-grid{grid-template-columns:1fr;}
    }

    /* ===== SECTION ===== */
    .section{
        background:var(--bg-secondary);
        border:2px solid var(--border);
        border-radius:var(--radius);
        padding:28px;
        transition:var(--transition);
    }

    .section:hover{
        border-color:var(--border-light);
        box-shadow:var(--shadow-hover);
    }

    .section-header{
        display:flex;
        align-items:center;
        justify-content:space-between;
        margin-bottom:24px;
        flex-wrap:wrap;
        gap:12px;
    }

    .section-title{
        font-family:'Space Grotesk',sans-serif;
        font-size:18px;
        font-weight:700;
        letter-spacing:-0.5px;
    }

    .section-action{
        font-size:12px;
        color:var(--text-muted);
        font-weight:600;
        text-decoration:none;
        transition:var(--transition);
    }

    .section-action:hover{color:var(--text-primary);}

    /* ===== FORM STYLES ===== */
    .form-group{
        margin-bottom:20px;
    }

    .form-label{
        display:block;
        font-size:12px;
        font-weight:600;
        color:var(--text-secondary);
        text-transform:uppercase;
        letter-spacing:0.5px;
        margin-bottom:8px;
    }

    .form-input{
        width:100%;
        padding:14px 16px;
        background:var(--bg-input);
        border:2px solid var(--border);
        border-radius:var(--radius-xs);
        color:var(--text-primary);
        font-family:'Inter',sans-serif;
        font-size:14px;
        transition:var(--transition);
        outline:none;
    }

    .form-input::placeholder{color:var(--text-muted);}

    .form-input:focus{
        border-color:var(--border-focus);
        background:var(--bg-tertiary);
    }

    .form-input.error{
        border-color:var(--danger);
        background:rgba(255,68,68,0.05);
    }

    .input-hint{
        font-size:11px;
        color:var(--text-muted);
        margin-top:6px;
    }

    .input-hint.error{color:var(--danger);}

    .btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        padding:14px 24px;
        border-radius:var(--radius-xs);
        font-family:'Inter',sans-serif;
        font-size:14px;
        font-weight:700;
        border:none;
        cursor:pointer;
        transition:var(--transition);
        text-decoration:none;
    }

    .btn-primary{
        background:var(--text-primary);
        color:var(--bg-primary);
    }

    .btn-primary:hover{
        transform:translateY(-2px);
        box-shadow:0 4px 20px rgba(255,255,255,0.2);
    }

    .btn-danger{
        background:transparent;
        color:var(--danger);
        border:2px solid rgba(255,68,68,0.3);
    }

    .btn-danger:hover{
        background:var(--danger);
        color:white;
        border-color:var(--danger);
    }

    .btn-icon{
        width:36px;
        height:36px;
        padding:0;
        border-radius:var(--radius-xs);
        background:var(--bg-tertiary);
        border:2px solid var(--border);
        color:var(--text-secondary);
        font-size:14px;
    }

    .btn-icon:hover{
        border-color:var(--border-light);
        color:var(--text-primary);
        background:var(--bg-hover);
    }

    /* ===== SEARCH BAR ===== */
    .search-bar{
        display:flex;
        gap:12px;
        margin-bottom:24px;
    }

    .search-input{
        flex:1;
        padding:12px 16px;
        background:var(--bg-input);
        border:2px solid var(--border);
        border-radius:var(--radius-xs);
        color:var(--text-primary);
        font-family:'Inter',sans-serif;
        font-size:14px;
        outline:none;
        transition:var(--transition);
    }

    .search-input:focus{border-color:var(--border-focus);}

    .search-input::placeholder{color:var(--text-muted);}

    /* ===== PACKAGE GRID ===== */
    .package-grid{
        display:grid;
        grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));
        gap:16px;
    }

    @media(max-width:600px){
        .package-grid{grid-template-columns:1fr;}
    }

    .package-card{
        background:var(--bg-tertiary);
        border:2px solid var(--border);
        border-radius:var(--radius-sm);
        padding:24px;
        transition:var(--transition);
        position:relative;
        overflow:hidden;
    }

    .package-card::before{
        content:'';
        position:absolute;
        top:0;
        left:0;
        right:0;
        height:3px;
        background:var(--gold);
        opacity:0;
        transition:opacity 0.3s ease;
    }

    .package-card:hover{
        transform:translateY(-4px);
        border-color:var(--border-light);
        box-shadow:var(--shadow-hover);
    }

    .package-card:hover::before{opacity:1;}

    .package-icon{
        width:48px;
        height:48px;
        border-radius:var(--radius-xs);
        background:var(--gold-soft);
        border:2px solid rgba(255,215,0,0.2);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:20px;
        color:var(--gold);
        margin-bottom:16px;
    }

    .package-name{
        font-family:'Space Grotesk',sans-serif;
        font-size:18px;
        font-weight:700;
        margin-bottom:8px;
        letter-spacing:-0.5px;
    }

    .package-price{
        font-family:'Space Grotesk',sans-serif;
        font-size:24px;
        font-weight:700;
        color:var(--gold);
        margin-bottom:16px;
    }

    .package-price span{
        font-size:14px;
        color:var(--text-muted);
        font-weight:500;
    }

    .package-actions{
        display:flex;
        gap:8px;
    }

    .package-actions .btn{
        flex:1;
        padding:10px;
        font-size:13px;
    }

    /* ===== EMPTY STATE ===== */
    .empty-state{
        text-align:center;
        padding:60px 20px;
        color:var(--text-muted);
    }

    .empty-state i{
        font-size:48px;
        margin-bottom:16px;
        display:block;
        opacity:0.5;
    }

    .empty-state h3{
        font-family:'Space Grotesk',sans-serif;
        font-size:18px;
        color:var(--text-secondary);
        margin-bottom:8px;
    }

    /* ===== MODAL ===== */
    .modal-overlay{
        display:none;
        position:fixed;
        inset:0;
        background:rgba(0,0,0,0.8);
        backdrop-filter:blur(8px);
        z-index:1000;
        align-items:center;
        justify-content:center;
        padding:20px;
    }

    .modal-overlay.active{display:flex;}

    .modal{
        background:var(--bg-secondary);
        border:2px solid var(--border);
        border-radius:var(--radius);
        padding:32px;
        width:100%;
        max-width:420px;
        animation:modalIn 0.3s ease;
    }

    @keyframes modalIn{
        from{opacity:0;transform:scale(0.95) translateY(10px);}
        to{opacity:1;transform:scale(1) translateY(0);}
    }

    .modal-header{
        display:flex;
        align-items:center;
        justify-content:space-between;
        margin-bottom:24px;
    }

    .modal-title{
        font-family:'Space Grotesk',sans-serif;
        font-size:20px;
        font-weight:700;
    }

    .modal-close{
        width:36px;
        height:36px;
        border-radius:50%;
        background:var(--bg-tertiary);
        border:2px solid var(--border);
        color:var(--text-muted);
        display:flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        font-size:14px;
        transition:var(--transition);
    }

    .modal-close:hover{
        border-color:var(--danger);
        color:var(--danger);
        background:var(--danger-soft);
    }

    /* ===== TOAST ===== */
    .toast-container{
        position:fixed;
        top:24px;
        right:24px;
        z-index:2000;
        display:flex;
        flex-direction:column;
        gap:12px;
    }

    .toast{
        display:flex;
        align-items:center;
        gap:12px;
        padding:16px 20px;
        background:var(--bg-secondary);
        border:2px solid var(--border);
        border-radius:var(--radius-sm);
        box-shadow:var(--shadow-hover);
        animation:toastIn 0.4s ease;
        min-width:300px;
    }

    @keyframes toastIn{
        from{opacity:0;transform:translateX(40px);}
        to{opacity:1;transform:translateX(0);}
    }

    .toast.success{border-color:rgba(0,255,136,0.3);}
    .toast.error{border-color:rgba(255,68,68,0.3);}
    .toast.warning{border-color:rgba(255,170,0,0.3);}

    .toast-icon{
        width:36px;
        height:36px;
        border-radius:50%;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:14px;
        flex-shrink:0;
    }

    .toast.success .toast-icon{background:var(--success-soft);color:var(--success);}
    .toast.error .toast-icon{background:var(--danger-soft);color:var(--danger);}
    .toast.warning .toast-icon{background:var(--warning-soft);color:var(--warning);}

    .toast-content{flex:1;}
    .toast-title{font-weight:700;font-size:14px;margin-bottom:2px;}
    .toast-message{font-size:12px;color:var(--text-muted);}

    /* ===== DELETE CONFIRM ===== */
    .confirm-actions{
        display:flex;
        gap:12px;
        margin-top:24px;
    }

    .confirm-actions .btn{flex:1;}

    /* ===== ANIMATIONS ===== */
    @keyframes fadeUp{
        from{opacity:0;transform:translateY(20px);}
        to{opacity:1;transform:translateY(0);}
    }

    .stat-card, .section, .package-card{
        animation:fadeUp 0.5s ease forwards;
    }

    .stat-card:nth-child(1){animation-delay:0.05s;}
    .stat-card:nth-child(2){animation-delay:0.1s;}
    .stat-card:nth-child(3){animation-delay:0.15s;}
    .stat-card:nth-child(4){animation-delay:0.2s;}

    /* ===== SCROLLBAR ===== */
    ::-webkit-scrollbar{width:6px;}
    ::-webkit-scrollbar-track{background:var(--bg-primary);}
    ::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px;}
    ::-webkit-scrollbar-thumb:hover{background:var(--border-light);}

    /* ===== RESPONSIVE ===== */
    @media(max-width:768px){
        .container{padding:20px 16px;}
        .page-title h1{font-size:28px;}
        .page-header{flex-direction:column;align-items:flex-start;}
        .header-actions{width:100%;}
        .back-btn{width:100%;justify-content:center;}
    }
    </style>
</head>
<body>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toastContainer"></div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Edit Package</div>
            <button class="modal-close" onclick="closeEditModal()"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="proses_edit_paket.php" id="editForm">
            <input type="hidden" name="id" id="editId">
            <div class="form-group">
                <label class="form-label">Package Name</label>
                <input type="text" name="nama" id="editNama" class="form-input" placeholder="e.g. Premium Haircut" required>
            </div>
            <div class="form-group">
                <label class="form-label">Price (Rp)</label>
                <input type="number" name="harga" id="editHarga" class="form-input" placeholder="50000" required min="0">
                <div class="input-hint">Enter price in Rupiah without dots or commas</div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>
    </div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:380px;">
        <div class="modal-header">
            <div class="modal-title" style="color:var(--danger);"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</div>
            <button class="modal-close" onclick="closeDeleteModal()"><i class="fas fa-times"></i></button>
        </div>
        <p style="color:var(--text-secondary);font-size:14px;line-height:1.6;">
            Are you sure you want to delete <strong id="deletePackageName" style="color:var(--text-primary);"></strong>? 
            This action cannot be undone and will affect existing bookings.
        </p>
        <div class="confirm-actions">
            <button class="btn btn-danger" onclick="closeDeleteModal()" style="flex:1;">Cancel</button>
            <a href="#" class="btn btn-primary" id="confirmDeleteBtn" style="flex:1;background:var(--danger);border-color:var(--danger);">
                <i class="fas fa-trash"></i> Delete
            </a>
        </div>
    </div>
</div>

<div class="container">

    <!-- HEADER -->
    <div class="page-header">
        <div class="page-title">
            <h1>Packages.</h1>
            <p>Manage your service packages and pricing</p>
        </div>
        <div class="header-actions">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-box"></i></div>
            <div class="stat-value"><?= number_format($stats['total_paket'] ?? 0) ?></div>
            <div class="stat-label">Total Packages</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon gold"><i class="fas fa-coins"></i></div>
            <div class="stat-value">Rp<?= number_format($stats['total_value'] ?? 0) ?></div>
            <div class="stat-label">Total Value</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color:var(--info);border-color:rgba(68,136,255,0.3);">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-value">Rp<?= number_format($stats['avg_price'] ?? 0) ?></div>
            <div class="stat-label">Average Price</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color:var(--success);border-color:rgba(0,255,136,0.3);">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="stat-value">Rp<?= number_format($stats['highest_price'] ?? 0) ?></div>
            <div class="stat-label">Highest Price</div>
        </div>
    </div>

    <!-- MAIN GRID -->
    <div class="main-grid">
        
        <!-- ADD PACKAGE FORM -->
        <div class="section">
            <div class="section-header">
                <div class="section-title">Add New Package</div>
            </div>
            
            <form method="POST" action="proses_tambah_paket.php" id="addForm">
                <div class="form-group">
                    <label class="form-label">Package Name</label>
                    <input type="text" name="nama" class="form-input" placeholder="e.g. Hostel Haircut" required 
                           maxlength="100" id="addNama">
                    <div class="input-hint">Max 100 characters</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Price (Rp)</label>
                    <input type="number" name="harga" class="form-input" placeholder="50000" required 
                           min="1000" step="1000" id="addHarga">
                    <div class="input-hint">Minimum Rp 1,000. Use multiples of 1000.</div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-plus"></i> Add Package
                </button>
            </form>
        </div>

        <!-- PACKAGE LIST -->
        <div class="section">
            <div class="section-header">
                <div class="section-title">All Packages</div>
                <span class="section-action"><?= $totalResults ?> package(s)</span>
            </div>

            <!-- SEARCH -->
            <div class="search-bar">
                <input type="text" class="search-input" placeholder="Search packages..." 
                       value="<?= htmlspecialchars($search) ?>" id="searchInput"
                       onkeypress="if(event.key==='Enter') doSearch()">
                <button class="btn btn-icon" onclick="doSearch()"><i class="fas fa-search"></i></button>
                <?php if(!empty($search)): ?>
                <a href="paket.php" class="btn btn-icon" title="Clear search"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>

            <?php if($totalResults > 0): ?>
            <div class="package-grid">
                <?php while($d = mysqli_fetch_assoc($q)): ?>
                <div class="package-card">
                    <div class="package-icon"><i class="fas fa-cut"></i></div>
                    <div class="package-name"><?= htmlspecialchars($d['nama_paket']) ?></div>
                    <div class="package-price">
                        Rp<?= number_format($d['harga']) ?>
                        <span>/session</span>
                    </div>
                    <div class="package-actions">
                        <button class="btn btn-icon" style="flex:unset;" 
                                onclick="openEditModal(<?= $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['nama_paket'])) ?>', <?= $d['harga'] ?>)"
                                title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger" style="flex:1;" 
                                onclick="openDeleteModal(<?= $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['nama_paket'])) ?>')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>No packages found</h3>
                <p><?= empty($search) ? 'Start by adding your first package above.' : 'Try a different search term.' ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
// ===== FLASH MESSAGE TOAST =====
<?php if($flash): ?>
showToast('<?= $flash['type'] ?>', '<?= addslashes($flash['title']) ?>', '<?= addslashes($flash['message']) ?>');
<?php endif; ?>

function showToast(type, title, message){
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-times-circle',
        warning: 'fa-exclamation-circle'
    };
    
    toast.innerHTML = `
        <div class="toast-icon"><i class="fas ${icons[type] || icons.success}"></i></div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(40px)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// ===== EDIT MODAL =====
function openEditModal(id, nama, harga){
    document.getElementById('editId').value = id;
    document.getElementById('editNama').value = nama;
    document.getElementById('editHarga').value = harga;
    document.getElementById('editModal').classList.add('active');
}

function closeEditModal(){
    document.getElementById('editModal').classList.remove('active');
}

// ===== DELETE MODAL =====
function openDeleteModal(id, nama){
    document.getElementById('deletePackageName').textContent = nama;
    document.getElementById('confirmDeleteBtn').href = 'hapus_paket.php?id=' + id;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal(){
    document.getElementById('deleteModal').classList.remove('active');
}

// ===== SEARCH =====
function doSearch(){
    const val = document.getElementById('searchInput').value.trim();
    if(val) window.location.href = 'paket.php?search=' + encodeURIComponent(val);
    else window.location.href = 'paket.php';
}

// ===== FORM VALIDATION =====
document.getElementById('addForm').addEventListener('submit', function(e){
    const nama = document.getElementById('addNama').value.trim();
    const harga = parseInt(document.getElementById('addHarga').value);
    
    if(nama.length < 2){
        e.preventDefault();
        showToast('error', 'Validation Error', 'Package name must be at least 2 characters');
        document.getElementById('addNama').focus();
        return;
    }
    
    if(harga < 1000){
        e.preventDefault();
        showToast('error', 'Validation Error', 'Minimum price is Rp 1,000');
        document.getElementById('addHarga').focus();
        return;
    }
});

// Close modals on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e){
        if(e.target === this) this.classList.remove('active');
    });
});

// Escape key to close modals
document.addEventListener('keydown', function(e){
    if(e.key === 'Escape'){
        closeEditModal();
        closeDeleteModal();
    }
});
</script>

</body>
</html>