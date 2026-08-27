<?php
/**
 * @var mysqli $conn Database connection from config.php
 */
include '../config.php';
global $conn;

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit;
}

// Flash message
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* =========================
   EXPORT HANDLER
========================= */
if(isset($_GET['export'])){
    $exportType = $_GET['export'];
    $exportStatus = $_GET['status'] ?? '';
    $exportSearch = $_GET['search'] ?? '';

    $exportQuery = "
    SELECT u.*, 
           COUNT(b.id) as total_booking,
           SUM(CASE WHEN b.status='paid' THEN p.harga ELSE 0 END) as total_spending
    FROM users u
    LEFT JOIN booking b ON u.id = b.user_id
    LEFT JOIN paket p ON b.paket_id = p.id
    WHERE 1
    ";

    if($exportStatus !== '' && $exportStatus !== 'all'){
        $exportQuery .= " AND u.status='".mysqli_real_escape_string($conn, $exportStatus)."'";
    }
    if($exportSearch !== ''){
        $se = mysqli_real_escape_string($conn, $exportSearch);
        $exportQuery .= " AND (u.username LIKE '%$se%' OR u.email LIKE '%$se%' OR u.phone LIKE '%$se%')";
    }

    $exportQuery .= " GROUP BY u.id ORDER BY u.id DESC";
    $exportResult = mysqli_query($conn, $exportQuery);

    if($exportType === 'excel'){
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="Hostel Barbershop_Users_Report_'.date('Y-m-d_His').'.xls"');

        echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
        <style>
        body{font-family:Calibri,Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px;}
        .header{background:#1a1a1a;color:white;padding:25px;text-align:center;border-radius:8px 8px 0 0;}
        .header h1{font-size:28px;margin:0;letter-spacing:2px;}
        table.data{width:100%;border-collapse:collapse;background:#fff;border-radius:6px;overflow:hidden;margin-top:15px;}
        table.data th{background:#1a1a1a;color:#fff;padding:12px;font-size:11px;text-transform:uppercase;}
        table.data td{padding:10px;border:1px solid #e0e0e0;font-size:12px;}
        table.data tr:nth-child(even){background:#f9f9f9;}
        .status-active{background:#d4edda!important;color:#155724;font-weight:bold;}
        .status-banned{background:#f8d7da!important;color:#721c24;font-weight:bold;}
        .footer{text-align:center;color:#999;font-size:11px;margin-top:20px;}
        </style></head><body>';

        echo '<div class="header"><h1>HOSTEL BARBERSHOP USERS</h1><p>User Report &bull; '.date('d F Y H:i:s').'</p></div>';
        echo '<table class="data"><tr>
            <th>No</th><th>ID</th><th>Username</th><th>Email</th><th>Phone</th><th>Gender</th>
            <th>Status</th><th>Bookings</th><th>Spending</th><th>Joined</th>
        </tr>';

        $no = 1;
        while($row = mysqli_fetch_assoc($exportResult)){
            $statusClass = $row['status'] === 'active' ? 'status-active' : 'status-banned';
            echo '<tr class="'.$statusClass.'">';
            echo '<td>'.$no++.'</td>';
            echo '<td>#'.$row['id'].'</td>';
            echo '<td>'.$row['username'].'</td>';
            echo '<td>'.$row['email'].'</td>';
            echo '<td>'.$row['phone'].'</td>';
            echo '<td>'.$row['gender'].'</td>';
            echo '<td>'.strtoupper($row['status']).'</td>';
            echo '<td>'.$row['total_booking'].'</td>';
            echo '<td>Rp'.number_format($row['total_spending'] ?? 0).'</td>';
            echo '<td>'.$row['created_at'].'</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '<div class="footer">&copy; Hostel Barbershop Management System</div>';
        echo '</body></html>';
        exit;
    }
}

/* =========================
   BULK ACTIONS
========================= */
if(isset($_POST['bulk_action']) && isset($_POST['selected_users'])){
    $action = $_POST['bulk_action'];
    $selected = $_POST['selected_users'];

    if($action === 'ban'){
        $ids = implode(',', array_map('intval', $selected));
        mysqli_query($conn, "UPDATE users SET status='banned' WHERE id IN ($ids)");
        $_SESSION['flash'] = ['type'=>'success','title'=>'Users Banned','message'=>count($selected).' user(s) banned successfully'];
    } elseif($action === 'unban'){
        $ids = implode(',', array_map('intval', $selected));
        mysqli_query($conn, "UPDATE users SET status='active' WHERE id IN ($ids)");
        $_SESSION['flash'] = ['type'=>'success','title'=>'Users Unbanned','message'=>count($selected).' user(s) unbanned successfully'];
    } elseif($action === 'delete'){
        $ids = implode(',', array_map('intval', $selected));
        // Only delete user accounts — all history (bookings, ratings, photos) is preserved
        mysqli_query($conn, "DELETE FROM users WHERE id IN ($ids)");
        $_SESSION['flash'] = ['type'=>'success','title'=>'Users Deleted','message'=>count($selected).' user(s) deleted. All booking history preserved.'];
    }

    header("Location: users.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

/* =========================
   SINGLE ACTIONS
========================= */
if(isset($_POST['toggle_status'])){
    $id = intval($_POST['user_id']);
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE users SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    header("Location: users.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

if(isset($_POST['delete_user'])){
    $id = intval($_POST['user_id']);
    // Only delete the user account — all history (bookings, ratings, photos) is preserved
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $_SESSION['flash'] = ['type'=>'success','title'=>'User Deleted','message'=>'User account deleted. All booking history preserved.'];
    header("Location: users.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

if(isset($_POST['update_user'])){
    $id = intval($_POST['id']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? '';

    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, phone=?, gender=? WHERE id=?");
    $stmt->bind_param("ssssi", $username, $email, $phone, $gender, $id);
    $stmt->execute();

    $_SESSION['flash'] = ['type'=>'success','title'=>'User Updated','message'=>'User profile updated successfully'];
    header("Location: users.php" . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

/* =========================
   FILTERS & PAGINATION
========================= */
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'id';
$order = $_GET['order'] ?? 'DESC';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$allowedSort = ['id', 'username', 'email', 'total_booking', 'total_spending', 'created_at', 'status'];
if(!in_array($sort, $allowedSort)) $sort = 'id';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

/* =========================
   MAIN QUERY
========================= */
$whereClause = "";
$params = [];
$types = "";

if($search !== ''){
    $whereClause .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= "sss";
}

if($statusFilter !== '' && $statusFilter !== 'all'){
    $whereClause .= " AND u.status=?";
    $params[] = $statusFilter;
    $types .= "s";
}

// Count total
$countQuery = "SELECT COUNT(*) as total FROM users u WHERE 1 $whereClause";
$countStmt = $conn->prepare($countQuery);
if(!empty($params)) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);

// Main data query
$query = "
SELECT SQL_CALC_FOUND_ROWS
    u.*,
    COUNT(b.id) as total_booking,
    SUM(CASE WHEN b.status='paid' THEN p.harga ELSE 0 END) as total_spending
FROM users u
LEFT JOIN booking b ON u.id = b.user_id
LEFT JOIN paket p ON b.paket_id = p.id
WHERE 1 $whereClause
GROUP BY u.id
ORDER BY ".mysqli_real_escape_string($conn, $sort)." $order
LIMIT $offset, $perPage
";

$stmt = $conn->prepare($query);
if(!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$data = $stmt->get_result();

/* =========================
   STATS
========================= */
$totalUser = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"));
$totalActive = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE status='active'"));
$totalBanned = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE status='banned'"));
$totalBooking = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM booking"));
$todayRegistered = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE DATE(created_at)=CURDATE()"));

/* =========================
   BOOKING HISTORY HELPER
========================= */
function getUserBookings($conn, $userId){
    $q = mysqli_query($conn, "
        SELECT b.*, p.nama_paket, p.harga, bar.nama as barber_nama, bar.foto as barber_foto
        FROM booking b
        JOIN paket p ON b.paket_id = p.id
        JOIN barber bar ON b.barber_id = bar.id
        WHERE b.user_id = $userId
        ORDER BY b.tanggal DESC, b.jam DESC
    ");
    $bookings = [];
    while($row = mysqli_fetch_assoc($q)) $bookings[] = $row;
    return $bookings;
}

function sortIcon($col, $currentSort, $currentOrder) {
    if($col !== $currentSort) return '<i class="fas fa-sort"></i>';
    return $currentOrder === 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}

/* =========================
   AJAX DETAIL HANDLER
========================= */
if(isset($_GET['ajax_detail'])){
    $detailId = intval($_GET['ajax_detail']);

    $detailQuery = "
    SELECT u.*,
           COUNT(b.id) as total_booking,
           SUM(CASE WHEN b.status='paid' THEN p.harga ELSE 0 END) as total_spending
    FROM users u
    LEFT JOIN booking b ON u.id = b.user_id
    LEFT JOIN paket p ON b.paket_id = p.id
    WHERE u.id = $detailId
    GROUP BY u.id
    LIMIT 1
    ";

    $detailResult = mysqli_query($conn, $detailQuery);

    if(mysqli_num_rows($detailResult) > 0){
        $detail = mysqli_fetch_assoc($detailResult);
        header('Content-Type: application/json');
        echo json_encode([
            'id' => $detail['id'],
            'username' => $detail['username'],
            'email' => $detail['email'],
            'phone' => $detail['phone'],
            'gender' => $detail['gender'],
            'status' => $detail['status'],
            'photo' => $detail['photo'],
            'total_booking' => $detail['total_booking'],
            'total_spending' => $detail['total_spending'],
            'total_spending_formatted' => number_format($detail['total_spending'] ?? 0),
            'created_at' => $detail['created_at'],
            'created_at_formatted' => date('d M Y H:i', strtotime($detail['created_at'])),
            'updated_at' => $detail['updated_at'],
            'updated_at_formatted' => date('d M Y H:i', strtotime($detail['updated_at']))
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'User not found']);
    }
    exit;
}

/* =========================
   AJAX BOOKING HISTORY
========================= */
if(isset($_GET['ajax_history'])){
    $historyId = intval($_GET['ajax_history']);
    $bookings = getUserBookings($conn, $historyId);

    header('Content-Type: application/json');
    echo json_encode($bookings);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users | Hostel Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
:root{
    --bg-primary:#0a0a0a;
    --bg-secondary:#111111;
    --bg-tertiary:#161616;
    --bg-card:#1a1a1a;
    --bg-hover:#1e1e1e;
    --bg-input:#141414;
    --border:#2a2a2a;
    --border-light:#3a3a3a;
    --border-focus:#555555;
    --text-primary:#ffffff;
    --text-secondary:#a0a0a0;
    --text-muted:#666666;
    --accent:#ffffff;
    --danger:#ff4444;
    --danger-soft:rgba(255,68,68,0.08);
    --danger-border:rgba(255,68,68,0.2);
    --success:#00ff88;
    --success-soft:rgba(0,255,136,0.08);
    --warning:#ffaa00;
    --warning-soft:rgba(255,170,0,0.08);
    --info:#4488ff;
    --info-soft:rgba(68,136,255,0.08);
    --gold:#ffd700;
    --gold-soft:rgba(255,215,0,0.08);
    --shadow:0 2px 12px rgba(0,0,0,0.4);
    --shadow-hover:0 4px 24px rgba(0,0,0,0.5);
    --radius:16px;
    --radius-sm:12px;
    --radius-xs:8px;
    --transition:all 0.25s cubic-bezier(0.4,0,0.2,1);
}

*{margin:0;padding:0;box-sizing:border-box;}

body{
    background:var(--bg-primary);
    color:var(--text-primary);
    font-family:'Inter',sans-serif;
    line-height:1.6;
    min-height:100vh;
    overflow-x:hidden;
}

.container{
    max-width:1400px;
    margin:0 auto;
    padding:40px 32px;
}

/* ===== HEADER ===== */
.page-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:32px;
    padding-bottom:24px;
    border-bottom:1px solid var(--border);
    position:relative;
    flex-wrap:wrap;
    gap:16px;
}

.page-header::after{
    content:'';
    position:absolute;
    bottom:-1px;
    left:0;
    width:120px;
    height:1px;
    background:var(--text-primary);
}

.page-title h1{
    font-family:'Space Grotesk',sans-serif;
    font-size:32px;
    font-weight:700;
    letter-spacing:-1.5px;
    line-height:1.2;
    margin-bottom:6px;
}

.page-title p{
    color:var(--text-muted);
    font-size:14px;
    font-weight:400;
}

.header-actions{
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
}

.back-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 18px;
    border-radius:var(--radius-xs);
    background:var(--bg-secondary);
    border:1px solid var(--border);
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

.export-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 18px;
    border-radius:var(--radius-xs);
    background:var(--success-soft);
    border:1px solid rgba(0,255,136,0.3);
    color:var(--success);
    text-decoration:none;
    font-size:13px;
    font-weight:600;
    transition:var(--transition);
    cursor:pointer;
    position:relative;
}

.export-btn:hover{
    background:var(--success);
    color:var(--bg-primary);
    border-color:var(--success);
}

.export-menu{
    display:none;
    position:absolute;
    top:calc(100% + 8px);
    right:0;
    background:var(--bg-secondary);
    border:1px solid var(--border);
    border-radius:var(--radius-xs);
    min-width:180px;
    z-index:100;
    overflow:hidden;
    box-shadow:var(--shadow-hover);
}

.export-menu.active{display:block;}

.export-menu a{
    display:flex;
    align-items:center;
    gap:10px;
    padding:12px 16px;
    color:var(--text-secondary);
    text-decoration:none;
    font-size:13px;
    font-weight:500;
    transition:var(--transition);
}

.export-menu a:hover{
    background:var(--bg-hover);
    color:var(--text-primary);
}

/* ===== STATS STRIP ===== */
.stats-strip{
    display:grid;
    grid-template-columns:repeat(5, 1fr);
    gap:16px;
    margin-bottom:32px;
}

@media(max-width:1100px){.stats-strip{grid-template-columns:repeat(3,1fr);}}
@media(max-width:700px){.stats-strip{grid-template-columns:repeat(2,1fr);}}

.stat-item{
    padding:20px;
    background:var(--bg-secondary);
    border:1px solid var(--border);
    border-radius:var(--radius-sm);
    transition:var(--transition);
}

.stat-item:hover{
    border-color:var(--border-light);
    transform:translateY(-2px);
    box-shadow:var(--shadow-hover);
}

.stat-icon{
    width:40px;
    height:40px;
    border-radius:var(--radius-xs);
    background:var(--bg-tertiary);
    border:1px solid var(--border);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:16px;
    color:var(--text-muted);
    margin-bottom:16px;
    transition:var(--transition);
}

.stat-item:hover .stat-icon{
    background:var(--text-primary);
    color:var(--bg-primary);
    border-color:var(--text-primary);
}

.stat-item-value{
    font-family:'Space Grotesk',sans-serif;
    font-size:28px;
    font-weight:700;
    letter-spacing:-1px;
    margin-bottom:8px;
}

.stat-item-label{
    font-size:11px;
    color:var(--text-muted);
    font-weight:600;
    text-transform:uppercase;
    letter-spacing:1px;
    margin-bottom:10px;
}

.stat-item-sub{
    display:flex;
    align-items:center;
    gap:6px;
    font-size:12px;
    color:var(--text-muted);
    font-weight:500;
}

.stat-item-sub .dot{
    width:5px;
    height:5px;
    border-radius:50%;
    display:inline-block;
}

.dot-success{background:var(--success);}
.dot-warning{background:var(--warning);}
.dot-danger{background:var(--danger);}
.dot-info{background:var(--info);}
.dot-gold{background:var(--gold);}

/* ===== FILTER BAR ===== */
.filter-bar{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    margin-bottom:24px;
    align-items:end;
}

.filter-group{
    display:flex;
    flex-direction:column;
    gap:6px;
}

.filter-label{
    font-size:11px;
    font-weight:600;
    color:var(--text-muted);
    text-transform:uppercase;
    letter-spacing:0.5px;
}

.filter-input, .filter-select{
    padding:12px 16px;
    background:var(--bg-input);
    border:1px solid var(--border);
    border-radius:var(--radius-xs);
    color:var(--text-primary);
    font-family:'Inter',sans-serif;
    font-size:14px;
    outline:none;
    transition:var(--transition);
    min-width:180px;
}

.filter-input:focus, .filter-select:focus{
    border-color:var(--border-focus);
    background:var(--bg-tertiary);
}

.filter-input::placeholder{color:var(--text-muted);}

.search-wrapper{
    position:relative;
    flex:1;
    min-width:250px;
}

.search-wrapper i{
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    color:var(--text-muted);
    font-size:14px;
}

.search-wrapper .filter-input{
    padding-left:40px;
    width:100%;
}

.btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:12px 20px;
    border-radius:var(--radius-xs);
    font-family:'Inter',sans-serif;
    font-size:13px;
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
    border:1px solid rgba(255,68,68,0.3);
}

.btn-danger:hover{
    background:var(--danger);
    color:white;
    border-color:var(--danger);
}

.btn-sm{padding:8px 14px;font-size:12px;}

/* ===== BULK ACTIONS ===== */
.bulk-bar{
    display:flex;
    align-items:center;
    gap:12px;
    padding:16px 20px;
    background:var(--bg-tertiary);
    border:1px solid var(--border);
    border-radius:var(--radius-xs);
    margin-bottom:16px;
    display:none;
}

.bulk-bar.active{display:flex;}

.bulk-bar span{
    font-size:13px;
    color:var(--text-secondary);
}

.bulk-bar strong{
    color:var(--text-primary);
}

/* ===== TABLE ===== */
.table-section{
    background:var(--bg-secondary);
    border:1px solid var(--border);
    border-radius:var(--radius-sm);
    padding:24px;
    transition:var(--transition);
}

.table-section:hover{
    border-color:var(--border-light);
    box-shadow:var(--shadow-hover);
}

.table-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:20px;
    flex-wrap:wrap;
    gap:12px;
}

.table-title{
    font-family:'Space Grotesk',sans-serif;
    font-size:18px;
    font-weight:700;
    letter-spacing:-0.5px;
}

.table-count{
    font-size:12px;
    color:var(--text-muted);
    font-weight:600;
}

.table-box{
    overflow:hidden;
    border-radius:var(--radius-xs);
    border:1px solid var(--border);
}

table{
    width:100%;
    border-collapse:collapse;
}

thead th{
    background:var(--bg-tertiary);
    color:var(--text-secondary);
    padding:14px 16px;
    text-align:left;
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:1px;
    border-bottom:1px solid var(--border);
    cursor:pointer;
    user-select:none;
    transition:var(--transition);
    white-space:nowrap;
}

thead th:hover{
    color:var(--text-primary);
    background:var(--bg-hover);
}

thead th i{
    margin-left:6px;
    font-size:10px;
    opacity:0.5;
}

thead th.active-sort{
    color:var(--text-primary);
}

thead th.active-sort i{
    opacity:1;
}

tbody td{
    padding:14px 16px;
    border-bottom:1px solid var(--border);
    font-size:13px;
    vertical-align:middle;
}

tbody tr{
    transition:var(--transition);
}

tbody tr:hover{
    background:var(--bg-hover);
}

.user-cell{
    display:flex;
    align-items:center;
    gap:12px;
}

.user-avatar{
    width:40px;
    height:40px;
    border-radius:50%;
    background:var(--bg-input);
    border:2px solid var(--border);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:15px;
    color:var(--text-muted);
    font-weight:700;
    overflow:hidden;
    flex-shrink:0;
}

.user-avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.user-info{min-width:0;}

.user-name{
    font-weight:700;
    color:var(--text-primary);
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.user-meta{
    font-size:11px;
    color:var(--text-muted);
    margin-top:2px;
}

.gender-badge{
    display:inline-flex;
    align-items:center;
    gap:4px;
    padding:4px 10px;
    border-radius:6px;
    font-size:11px;
    font-weight:700;
}

.gender-male{
    background:var(--info-soft);
    border:1px solid rgba(68,136,255,0.2);
    color:var(--info);
}

.gender-female{
    background:rgba(255,105,180,0.08);
    border:1px solid rgba(255,105,180,0.2);
    color:#ff69b4;
}

.status-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 14px;
    border-radius:100px;
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:0.5px;
    white-space:nowrap;
}

.status-active{
    background:var(--success-soft);
    border:1px solid rgba(0,255,136,0.3);
    color:var(--success);
}

.status-banned{
    background:var(--danger-soft);
    border:1px solid rgba(255,68,68,0.3);
    color:var(--danger);
}

.spending-tag{
    font-family:'Space Grotesk',sans-serif;
    font-weight:700;
    color:var(--gold);
}

.booking-count{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:4px 10px;
    background:var(--bg-input);
    border:1px solid var(--border);
    border-radius:6px;
    font-size:12px;
    font-weight:600;
    color:var(--text-secondary);
}

.action-btns{
    display:flex;
    gap:6px;
}

.action-btn{
    width:32px;
    height:32px;
    border-radius:var(--radius-xs);
    background:var(--bg-tertiary);
    border:1px solid var(--border);
    color:var(--text-secondary);
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    transition:var(--transition);
    font-size:12px;
}

.action-btn:hover{
    background:var(--text-primary);
    color:var(--bg-primary);
    border-color:var(--text-primary);
}

.action-btn.danger:hover{
    background:var(--danger);
    color:white;
    border-color:var(--danger);
}

.action-btn.warning:hover{
    background:var(--warning);
    color:var(--bg-primary);
    border-color:var(--warning);
}

.action-btn.success:hover{
    background:var(--success);
    color:var(--bg-primary);
    border-color:var(--success);
}

/* ===== PAGINATION ===== */
.pagination{
    display:flex;
    align-items:center;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:16px;
    margin-top:20px;
}

.page-info{
    font-size:13px;
    color:var(--text-muted);
}

.page-info strong{
    color:var(--text-primary);
    font-weight:700;
}

.page-buttons{
    display:flex;
    gap:6px;
}

.page-btn{
    display:flex;
    align-items:center;
    justify-content:center;
    min-width:36px;
    height:36px;
    padding:0 12px;
    background:var(--bg-tertiary);
    border:1px solid var(--border);
    border-radius:var(--radius-xs);
    color:var(--text-secondary);
    font-family:'Inter',sans-serif;
    font-size:13px;
    font-weight:600;
    text-decoration:none;
    transition:var(--transition);
}

.page-btn:hover{
    border-color:var(--border-light);
  color:var(--text-primary);
    background:var(--bg-hover);
}

.page-btn.active{
    background:var(--text-primary);
    color:var(--bg-primary);
    border-color:var(--text-primary);
}

.page-btn.disabled{
    opacity:0.3;
    cursor:not-allowed;
    pointer-events:none;
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

.modal-content{
    background:var(--bg-secondary);
    border:1px solid var(--border);
    border-radius:var(--radius);
    max-width:600px;
    width:100%;
    max-height:90vh;
    overflow-y:auto;
    position:relative;
    animation:fadeUp 0.3s ease;
}

.modal-content.wide{
    max-width:800px;
}

.modal-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:24px 28px;
    border-bottom:1px solid var(--border);
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
    border:1px solid var(--border);
    color:var(--text-secondary);
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    transition:var(--transition);
    font-size:14px;
}

.modal-close:hover{
    background:var(--danger);
    color:white;
    border-color:var(--danger);
}

.modal-body{
    padding:28px;
}

.detail-grid{
    display:grid;
    grid-template-columns:repeat(2, 1fr);
    gap:20px;
}

@media(max-width:600px){
    .detail-grid{grid-template-columns:1fr;}
}

.detail-item{
    display:flex;
    flex-direction:column;
    gap:6px;
}

.detail-label{
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:1px;
    color:var(--text-muted);
}

.detail-value{
    font-size:15px;
    font-weight:600;
    color:var(--text-primary);
}

.detail-value.price{
    color:var(--gold);
    font-family:'Space Grotesk',sans-serif;
    font-size:18px;
}

.detail-divider{
    height:1px;
    background:var(--border);
    margin:20px 0;
    grid-column:1/-1;
}

.modal-form input, .modal-form select{
    width:100%;
    padding:13px 16px;
    margin-bottom:12px;
    background:var(--bg-input);
    border:1px solid var(--border);
    border-radius:var(--radius-xs);
    color:var(--text-primary);
    font-family:'Inter',sans-serif;
    font-size:14px;
    outline:none;
    transition:var(--transition);
}

.modal-form input:focus, .modal-form select:focus{
    border-color:var(--border-focus);
    background:var(--bg-tertiary);
}

/* ===== BOOKING HISTORY IN MODAL ===== */
.booking-list{
    display:flex;
    flex-direction:column;
    gap:10px;
}

.booking-item{
    display:flex;
    align-items:center;
    gap:12px;
    padding:14px;
    background:var(--bg-tertiary);
    border:1px solid var(--border);
    border-radius:var(--radius-xs);
    transition:var(--transition);
}

.booking-item:hover{
    border-color:var(--border-light);
}

.booking-date{
    font-family:'Space Grotesk',monospace;
    font-size:13px;
    font-weight:700;
    color:var(--text-secondary);
    min-width:80px;
}

.booking-info{
    flex:1;
    min-width:0;
}

.booking-package{
    font-weight:700;
    font-size:13px;
    margin-bottom:2px;
}

.booking-barber{
    font-size:12px;
    color:var(--text-muted);
}

.booking-price{
    font-family:'Space Grotesk',sans-serif;
    font-weight:700;
    color:var(--gold);
    font-size:14px;
}

.booking-status{
    padding:4px 10px;
    border-radius:6px;
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
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

/* ===== TOAST ===== */
.toast-container{
    position:fixed;
    top:24px;
    right:24px;
    z-index:9999;
    display:flex;
    flex-direction:column;
    gap:12px;
}

.toast{
    background:var(--bg-secondary);
    border:1px solid var(--border);
    border-radius:var(--radius-sm);
    padding:16px 20px;
    display:flex;
    align-items:center;
    gap:12px;
    min-width:300px;
    max-width:400px;
    box-shadow:var(--shadow-hover);
    animation:slideIn 0.3s ease;
}

.toast.success{border-color:rgba(0,255,136,0.3);}
.toast.error{border-color:rgba(255,68,68,0.3);}

.toast-icon{
    width:36px;
    height:36px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:16px;
    flex-shrink:0;
}

.toast.success .toast-icon{background:var(--success-soft);color:var(--success);}
.toast.error .toast-icon{background:var(--danger-soft);color:var(--danger);}

.toast-content{flex:1;}

.toast-title{font-weight:700;font-size:14px;margin-bottom:2px;}

.toast-message{font-size:12px;color:var(--text-muted);}

@keyframes slideIn{
    from{opacity:0;transform:translateX(40px);}
    to{opacity:1;transform:translateX(0);}
}

@keyframes fadeUp{
    from{opacity:0;transform:translateY(20px);}
    to{opacity:1;transform:translateY(0);}
}

/* ===== SCROLLBAR ===== */
::-webkit-scrollbar{width:6px;}
::-webkit-scrollbar-track{background:var(--bg-primary);}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px;}
::-webkit-scrollbar-thumb:hover{background:var(--border-light);}

/* ===== RESPONSIVE ===== */
@media(max-width:768px){
    .container{padding:20px 16px;}
    .page-title h1{font-size:26px;}
    .page-header{flex-direction:column;align-items:flex-start;}
    .stats-strip{grid-template-columns:repeat(2,1fr);}
    .table-box{overflow-x:auto;}
    table{min-width:900px;}
    .modal-content{margin:0 10px;}
}

/* Checkbox styling */
.user-checkbox{
    width:18px;
    height:18px;
    accent-color:var(--success);
    cursor:pointer;
}

.recent-badge{
    display:inline-flex;
    align-items:center;
    gap:4px;
    padding:3px 8px;
    background:var(--info-soft);
    border:1px solid rgba(68,136,255,0.2);
    color:var(--info);
    border-radius:6px;
    font-size:10px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:0.5px;
    margin-left:8px;
}
</style>
</head>

<body>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toastContainer"></div>

<!-- DETAIL MODAL -->
<div class="modal-overlay" id="detailModal">
    <div class="modal-content wide">
        <div class="modal-header">
            <div class="modal-title">User Profile</div>
            <button class="modal-close" onclick="closeModal('detailModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="detailBody"></div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">Edit User</div>
            <button class="modal-close" onclick="closeModal('editModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST" class="modal-form">
                <input type="hidden" name="id" id="edit_id">
                <div class="detail-label">Username</div>
                <input type="text" name="username" id="edit_username" placeholder="Username" required>
                <div class="detail-label">Email</div>
                <input type="email" name="email" id="edit_email" placeholder="Email" required>
                <div class="detail-label">Phone</div>
                <input type="text" name="phone" id="edit_phone" placeholder="Phone number">
                <div class="detail-label">Gender</div>
                <select name="gender" id="edit_gender">
                    <option value="">Select Gender</option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                </select>
                <button type="submit" name="update_user" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<!-- BOOKING HISTORY MODAL -->
<div class="modal-overlay" id="historyModal">
    <div class="modal-content wide">
        <div class="modal-header">
            <div class="modal-title">Booking History</div>
            <button class="modal-close" onclick="closeModal('historyModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="historyBody"></div>
    </div>
</div>

<div class="container">

    <!-- HEADER -->
    <div class="page-header">
        <div class="page-title">
            <h1>Users.</h1>
            <p>Manage and monitor all registered customers</p>
        </div>
        <div class="header-actions">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <div style="position:relative;">
                <button class="export-btn" onclick="toggleExportMenu()">
                    <i class="fas fa-download"></i> Export
                    <i class="fas fa-chevron-down" style="margin-left:4px;font-size:10px;"></i>
                </button>
                <div class="export-menu" id="exportMenu">
                    <a href="#" onclick="doExport('excel')">
                        <i class="fas fa-file-excel" style="color:var(--success);"></i> Export Excel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats-strip">
        <div class="stat-item">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-item-value"><?= number_format($totalUser['total']) ?></div>
            <div class="stat-item-label">Total Users</div>
            <div class="stat-item-sub">
                <span class="dot dot-info"></span>
                registered customers
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
            <div class="stat-item-value"><?= number_format($totalActive['total']) ?></div>
            <div class="stat-item-label">Active</div>
            <div class="stat-item-sub">
                <span class="dot dot-success"></span>
                can book
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon"><i class="fas fa-user-slash"></i></div>
            <div class="stat-item-value"><?= number_format($totalBanned['total']) ?></div>
            <div class="stat-item-label">Banned</div>
            <div class="stat-item-sub">
                <span class="dot dot-danger"></span>
                restricted
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-item-value"><?= number_format($totalBooking['total']) ?></div>
            <div class="stat-item-label">Total Bookings</div>
            <div class="stat-item-sub">
                <span class="dot dot-gold"></span>
                all time
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
            <div class="stat-item-value"><?= number_format($todayRegistered['total']) ?></div>
            <div class="stat-item-label">New Today</div>
            <div class="stat-item-sub">
                <span class="dot dot-warning"></span>
                just joined
            </div>
        </div>
    </div>

    <!-- FILTERS -->
    <form method="GET" id="filterForm">
        <div class="filter-bar">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" class="filter-input" placeholder="Search username, email, or phone..." 
                       value="<?= htmlspecialchars($search) ?>" name="search" id="searchInput">
            </div>

            <div class="filter-group">
                <span class="filter-label">Status</span>
                <select class="filter-select" name="status" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Users</option>
                    <option value="active" <?= $statusFilter=='active'?'selected':'' ?>>Active Only</option>
                    <option value="banned" <?= $statusFilter=='banned'?'selected':'' ?>>Banned Only</option>
                </select>
            </div>

            <a href="users.php" class="btn btn-danger btn-sm">
                <i class="fas fa-undo"></i> Reset
            </a>
        </div>
    </form>

    <!-- BULK ACTIONS BAR -->
    <div class="bulk-bar" id="bulkBar">
        <span><strong id="selectedCount">0</strong> user(s) selected</span>
        <button type="button" class="btn btn-sm" style="background:var(--warning-soft);color:var(--warning);border:1px solid rgba(255,170,0,0.3);" onclick="doBulkAction('ban')">
            <i class="fas fa-ban"></i> Ban
        </button>
        <button type="button" class="btn btn-sm" style="background:var(--success-soft);color:var(--success);border:1px solid rgba(0,255,136,0.3);" onclick="doBulkAction('unban')">
            <i class="fas fa-check-circle"></i> Unban
        </button>
        <button type="button" class="btn btn-sm" style="background:var(--danger-soft);color:var(--danger);border:1px solid rgba(255,68,68,0.3);" onclick="doBulkAction('delete')">
            <i class="fas fa-trash"></i> Delete
        </button>
    </div>

    <!-- TABLE -->
    <div class="table-section">
        <div class="table-header">
            <div class="table-title">All Users</div>
            <span class="table-count"><?= $totalRows ?> total user(s) &bull; Page <?= $page ?> of <?= $totalPages ?: 1 ?></span>
        </div>

        <form method="POST" id="bulkForm">
            <input type="hidden" name="bulk_action" id="bulkActionInput">
            <div class="table-box">
                <table>
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" class="user-checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                            <th onclick="sortBy('id')">ID <?= sortIcon('id', $sort, $order) ?></th>
                            <th onclick="sortBy('username')">User <?= sortIcon('username', $sort, $order) ?></th>
                            <th>Contact</th>
                            <th onclick="sortBy('total_booking')">Bookings <?= sortIcon('total_booking', $sort, $order) ?></th>
                            <th onclick="sortBy('total_spending')">Spending <?= sortIcon('total_spending', $sort, $order) ?></th>
                            <th onclick="sortBy('status')">Status <?= sortIcon('status', $sort, $order) ?></th>
                            <th onclick="sortBy('created_at')">Joined <?= sortIcon('created_at', $sort, $order) ?></th>
                            <th style="width:160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($data->num_rows > 0): ?>
                            <?php while($u = $data->fetch_assoc()): 
                                $isRecent = date('Y-m-d', strtotime($u['created_at'])) === date('Y-m-d');
                            ?>
                            <tr>
                                <td><input type="checkbox" class="user-checkbox" name="selected_users[]" value="<?= $u['id'] ?>" onchange="updateBulkBar()"></td>
                                <td style="font-family:'Space Grotesk',monospace;font-weight:600;">#<?= $u['id'] ?></td>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar">
                                            <?php if(!empty($u['photo']) && file_exists('../'.$u['photo'])): ?>
                                            <img src="../<?= $u['photo'] ?>" alt="">
                                            <?php else: ?>
                                            <?= strtoupper(substr($u['username'],0,1)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="user-info">
                                            <div class="user-name">
                                                <?= htmlspecialchars($u['username']) ?>
                                                <?php if($isRecent): ?>
                                                <span class="recent-badge"><i class="fas fa-star" style="font-size:8px;"></i> New</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="user-meta">
                                                <?php if($u['gender']): ?>
                                                <span class="gender-badge <?= $u['gender']=='Laki-laki'?'gender-male':'gender-female' ?>">
                                                    <i class="fas <?= $u['gender']=='Laki-laki'?'fa-mars':'fa-venus' ?>"></i>
                                                    <?= $u['gender'] ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:12px;color:var(--text-secondary);">
                                        <div><i class="fas fa-envelope" style="width:14px;color:var(--text-muted);"></i> <?= htmlspecialchars($u['email'] ?? 'N/A') ?></div>
                                        <div style="margin-top:4px;"><i class="fas fa-phone" style="width:14px;color:var(--text-muted);"></i> <?= htmlspecialchars($u['phone'] ?? 'N/A') ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="booking-count">
                                        <i class="fas fa-calendar-check"></i> <?= $u['total_booking'] ?>
                                    </span>
                                </td>
                                <td class="spending-tag">Rp<?= number_format($u['total_spending'] ?? 0) ?></td>
                                <td>
                                    <?php if($u['status'] == 'active'): ?>
                                    <span class="status-badge status-active"><i class="fas fa-check"></i> Active</span>
                                    <?php else: ?>
                                    <span class="status-badge status-banned"><i class="fas fa-ban"></i> Banned</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:12px;color:var(--text-muted);">
                                    <?= date('d M Y', strtotime($u['created_at'])) ?>
                                    <div style="font-size:11px;opacity:0.7;margin-top:2px;"><?= date('H:i', strtotime($u['created_at'])) ?></div>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button type="button" class="action-btn" onclick="showDetail(<?= $u['id'] ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="action-btn" onclick="showHistory(<?= $u['id'] ?>)" title="Booking History">
                                            <i class="fas fa-history"></i>
                                        </button>
                                        <button type="button" class="action-btn" onclick="openEdit(<?= htmlspecialchars(json_encode($u)) ?>)" title="Edit User">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <?php if($u['status'] == 'active'): ?>
                                        <button type="button" class="action-btn warning" onclick="toggleStatus(<?= $u['id'] ?>,'banned')" title="Ban User">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="action-btn success" onclick="toggleStatus(<?= $u['id'] ?>,'active')" title="Unban User">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button type="button" class="action-btn danger" onclick="confirmDelete(<?= $u['id'] ?>)" title="Delete User">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="fas fa-user-slash"></i>
                                        <h3>No users found</h3>
                                        <p>Try adjusting your search or filter criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <!-- PAGINATION -->
        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <div class="page-info">
                Showing <strong><?= $offset + 1 ?>-<?= min($offset + $perPage, $totalRows) ?></strong> of <strong><?= $totalRows ?></strong> users
            </div>
            <div class="page-buttons">
                <?php 
                $baseParams = array_diff_key($_GET, array_flip(['page']));
                $baseUrl = "users.php?" . http_build_query($baseParams);
                $baseUrl .= (strpos($baseUrl, '?') === false ? '?' : '&');

                if($page > 1): 
                ?>
                <a href="<?= $baseUrl ?>page=<?= $page-1 ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
                <?php else: ?>
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                <?php endif; ?>

                <?php 
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);

                if($startPage > 1) echo '<span class="page-btn disabled">...</span>';

                for($i = $startPage; $i <= $endPage; $i++): 
                ?>
                <a href="<?= $baseUrl ?>page=<?= $i ?>" class="page-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
                <?php endfor; 

                if($endPage < $totalPages) echo '<span class="page-btn disabled">...</span>';
                ?>

                <?php if($page < $totalPages): ?>
                <a href="<?= $baseUrl ?>page=<?= $page+1 ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
                <?php else: ?>
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
// ===== FLASH TOAST =====
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
        warning: 'fa-exclamation-circle',
        info: 'fa-info-circle'
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

// ===== EXPORT MENU =====
function toggleExportMenu(){
    document.getElementById('exportMenu').classList.toggle('active');
}

function doExport(type){
    const params = new URLSearchParams(window.location.search);
    params.set('export', type);
    window.location.href = 'users.php?' + params.toString();
}

document.addEventListener('click', function(e){
    if(!e.target.closest('.export-btn') && !e.target.closest('.export-menu')){
        document.getElementById('exportMenu').classList.remove('active');
    }
});

// ===== SEARCH (Enter only) =====
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('keydown', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        document.getElementById('filterForm').submit();
    }
});

// ===== SORTING =====
function sortBy(col){
    const params = new URLSearchParams(window.location.search);
    const currentSort = params.get('sort') || 'id';
    const currentOrder = params.get('order') || 'DESC';

    if(currentSort === col){
        params.set('order', currentOrder === 'ASC' ? 'DESC' : 'ASC');
    } else {
        params.set('sort', col);
        params.set('order', 'ASC');
    }

    window.location.href = 'users.php?' + params.toString();
}

// ===== BULK ACTIONS =====
function toggleSelectAll(){
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('input[name="selected_users[]"]');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateBulkBar();
}

function updateBulkBar(){
    const checked = document.querySelectorAll('input[name="selected_users[]"]:checked');
    const bulkBar = document.getElementById('bulkBar');
    const countEl = document.getElementById('selectedCount');

    countEl.textContent = checked.length;

    if(checked.length > 0){
        bulkBar.classList.add('active');
    } else {
        bulkBar.classList.remove('active');
    }
}

function doBulkAction(action){
    const checked = document.querySelectorAll('input[name="selected_users[]"]:checked');
    if(checked.length === 0) return;

    const titles = {
        ban: 'Ban Selected Users?',
        unban: 'Unban Selected Users?',
        delete: 'Delete Selected Users?'
    };
    const texts = {
        ban: `${checked.length} user(s) will be banned.`,
        unban: `${checked.length} user(s) will be unbanned.`,
        delete: `${checked.length} user(s) will be permanently deleted. This cannot be undone!`
    };
    const icons = { ban: 'warning', unban: 'question', delete: 'warning' };
    const confirmColors = { ban: '#ff9800', unban: '#2ecc71', delete: '#e74c3c' };

    Swal.fire({
        title: titles[action],
        text: texts[action],
        icon: icons[action],
        background: '#1e1e1e',
        color: '#fff',
        showCancelButton: true,
        confirmButtonColor: confirmColors[action],
        cancelButtonColor: '#555',
        confirmButtonText: 'Confirm',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed){
            document.getElementById('bulkActionInput').value = action;
            document.getElementById('bulkForm').submit();
        }
    });
}

// ===== DETAIL MODAL =====
function showDetail(userId){
    const modal = document.getElementById('detailModal');
    const body = document.getElementById('detailBody');

    body.innerHTML = `
        <div style="text-align:center;padding:40px;">
            <i class="fas fa-spinner fa-spin" style="font-size:24px;color:var(--text-muted);"></i>
            <p style="margin-top:16px;color:var(--text-muted);">Loading user details...</p>
        </div>
    `;

    modal.classList.add('active');

    fetch('users.php?ajax_detail=' + userId)
        .then(r => r.json())
        .then(data => {
            if(data.error){
                body.innerHTML = `<div style="text-align:center;padding:40px;color:var(--danger);">${data.error}</div>`;
                return;
            }

            const statusClass = data.status === 'active' ? 'status-active' : 'status-banned';
            const statusIcon = data.status === 'active' ? 'fa-check' : 'fa-ban';
            const genderClass = data.gender === 'Laki-laki' ? 'gender-male' : (data.gender === 'Perempuan' ? 'gender-female' : '');
            const genderIcon = data.gender === 'Laki-laki' ? 'fa-mars' : 'fa-venus';

            body.innerHTML = `
                <div class="detail-grid">
                    <div class="detail-item" style="grid-column:1/-1;display:flex;align-items:center;gap:16px;margin-bottom:10px;">
                        <div class="user-avatar" style="width:64px;height:64px;font-size:24px;">
                            ${data.photo ? `<img src="../${data.photo}" alt="">` : data.username.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <div style="font-family:'Space Grotesk',sans-serif;font-size:22px;font-weight:700;">${data.username}</div>
                            <div style="color:var(--text-muted);font-size:13px;margin-top:4px;">ID #${data.id}</div>
                        </div>
                    </div>
                    <div class="detail-divider"></div>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value">${data.email || 'N/A'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Phone</div>
                        <div class="detail-value">${data.phone || 'N/A'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Gender</div>
                        <div class="detail-value">
                            ${data.gender ? `<span class="gender-badge ${genderClass}"><i class="fas ${genderIcon}"></i> ${data.gender}</span>` : 'N/A'}
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div class="detail-value">
                            <span class="status-badge ${statusClass}"><i class="fas ${statusIcon}"></i> ${data.status.toUpperCase()}</span>
                        </div>
                    </div>
                    <div class="detail-divider"></div>
                    <div class="detail-item">
                        <div class="detail-label">Total Bookings</div>
                        <div class="detail-value">${data.total_booking} booking(s)</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Total Spending</div>
                        <div class="detail-value price">Rp${data.total_spending_formatted}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Joined</div>
                        <div class="detail-value">${data.created_at_formatted}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Last Updated</div>
                        <div class="detail-value">${data.updated_at_formatted}</div>
                    </div>
                </div>
            `;
        })
        .catch(err => {
            body.innerHTML = `<div style="text-align:center;padding:40px;color:var(--danger);">Failed to load user details</div>`;
        });
}

// ===== BOOKING HISTORY =====
function showHistory(userId){
    const modal = document.getElementById('historyModal');
    const body = document.getElementById('historyBody');

    body.innerHTML = `
        <div style="text-align:center;padding:40px;">
            <i class="fas fa-spinner fa-spin" style="font-size:24px;color:var(--text-muted);"></i>
            <p style="margin-top:16px;color:var(--text-muted);">Loading booking history...</p>
        </div>
    `;

    modal.classList.add('active');

    fetch('users.php?ajax_history=' + userId)
        .then(r => r.json())
        .then(bookings => {
            if(bookings.length === 0){
                body.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No bookings yet</h3>
                        <p>This user has not made any bookings.</p>
                    </div>
                `;
                return;
            }

            let html = '<div class="booking-list">';
            bookings.forEach(bk => {
                const statusColors = {
                    'paid': {bg: 'var(--success-soft)', color: 'var(--success)', border: 'rgba(0,255,136,0.2)', icon: 'fa-check'},
                    'pending': {bg: 'var(--warning-soft)', color: 'var(--warning)', border: 'rgba(255,170,0,0.2)', icon: 'fa-clock'},
                    'expired': {bg: 'var(--danger-soft)', color: 'var(--danger)', border: 'rgba(255,68,68,0.2)', icon: 'fa-times'}
                };
                const sc = statusColors[bk.status] || statusColors['pending'];

                html += `
                    <div class="booking-item">
                        <div class="booking-date">${bk.tanggal}</div>
                        <div class="booking-info">
                            <div class="booking-package">${bk.nama_paket}</div>
                            <div class="booking-barber"><i class="fas fa-cut" style="margin-right:4px;"></i> ${bk.barber_nama} &bull; ${bk.jam}</div>
                        </div>
                        <div class="booking-price">Rp${Number(bk.harga).toLocaleString()}</div>
                        <span class="booking-status" style="background:${sc.bg};color:${sc.color};border:1px solid ${sc.border};">
                            <i class="fas ${sc.icon}" style="margin-right:4px;"></i>${bk.status.toUpperCase()}
                        </span>
                    </div>
                `;
            });
            html += '</div>';
            body.innerHTML = html;
        })
        .catch(err => {
            body.innerHTML = `<div style="text-align:center;padding:40px;color:var(--danger);">Failed to load booking history</div>`;
        });
}

// ===== EDIT MODAL =====
function openEdit(data){
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_username').value = data.username;
    document.getElementById('edit_email').value = data.email;
    document.getElementById('edit_phone').value = data.phone || '';
    document.getElementById('edit_gender').value = data.gender || '';

    document.getElementById('editModal').classList.add('active');
}

function closeModal(id){
    document.getElementById(id).classList.remove('active');
}

// ===== SINGLE ACTIONS =====
function confirmDelete(id){
    Swal.fire({
        title: 'Delete user?',
        text: 'This user will be permanently deleted. This action cannot be undone!',
        icon: 'warning',
        background: '#1e1e1e',
        color: '#fff',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#555',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed){
            let form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="user_id" value="${id}">
                <input type="hidden" name="delete_user" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function toggleStatus(id, status){
    const actionText = status === 'banned' ? 'Ban' : 'Unban';
    const actionColor = status === 'banned' ? '#ff9800' : '#2ecc71';

    Swal.fire({
        title: `${actionText} user?`,
        text: `User status will be updated to ${status.toUpperCase()}.`,
        icon: 'question',
        background: '#1e1e1e',
        color: '#fff',
        showCancelButton: true,
        confirmButtonColor: actionColor,
        cancelButtonColor: '#555',
        confirmButtonText: 'Confirm',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed){
            let form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="user_id" value="${id}">
                <input type="hidden" name="status" value="${status}">
                <input type="hidden" name="toggle_status" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// ===== MODAL OVERLAY CLICK =====
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e){
        if(e.target === this){
            this.classList.remove('active');
        }
    });
});

// ===== KEYBOARD SHORTCUTS =====
document.addEventListener('keydown', function(e){
    if(e.key === 'Escape'){
        document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));
        document.getElementById('exportMenu').classList.remove('active');
    }
    if(e.ctrlKey && e.key === 'f'){
        e.preventDefault();
        document.getElementById('searchInput').focus();
    }
});
</script>

</body>
</html>