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

// ============================================
// FILTERS & PAGINATION
// ============================================
$search = $_GET['search'] ?? '';
$ratingFilter = $_GET['rating'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Validate sort
$allowedSort = ['created_at', 'rating', 'username'];
if(!in_array($sort, $allowedSort)) $sort = 'created_at';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Base query
$query = "
SELECT SQL_CALC_FOUND_ROWS
    br.*,
    u.username,
    u.photo as user_photo,
    u.email
FROM barbershop_rating br
LEFT JOIN users u ON br.user_id = u.id
WHERE 1
";

// Apply filters
if($ratingFilter !== '' && $ratingFilter !== 'all'){
    $query .= " AND br.rating = '".mysqli_real_escape_string($conn, $ratingFilter)."'";
}

if($search !== ''){
    $searchEscaped = mysqli_real_escape_string($conn, $search);
    $query .= " AND (u.username LIKE '%$searchEscaped%' OR br.review LIKE '%$searchEscaped%')";
}

$query .= " ORDER BY br.".mysqli_real_escape_string($conn, $sort)." $order";
$query .= " LIMIT $offset, $perPage";

$q = mysqli_query($conn, $query);
$totalResult = mysqli_query($conn, "SELECT FOUND_ROWS() as total");
$totalRows = mysqli_fetch_assoc($totalResult)['total'];
$totalPages = ceil($totalRows / $perPage);

// Stats
$statsQuery = "
SELECT 
    COUNT(*) as total_reviews,
    ROUND(AVG(rating), 1) as avg_rating,
    COUNT(CASE WHEN rating = 5 THEN 1 END) as star5,
    COUNT(CASE WHEN rating = 4 THEN 1 END) as star4,
    COUNT(CASE WHEN rating = 3 THEN 1 END) as star3,
    COUNT(CASE WHEN rating = 2 THEN 1 END) as star2,
    COUNT(CASE WHEN rating = 1 THEN 1 END) as star1
FROM barbershop_rating
";
$statsResult = mysqli_query($conn, $statsQuery);
$stats = mysqli_fetch_assoc($statsResult);

$totalReviews = $stats['total_reviews'] ?? 0;
$avgRating = $stats['avg_rating'] ?? 0;
$star5 = $stats['star5'] ?? 0;
$star4 = $stats['star4'] ?? 0;
$star3 = $stats['star3'] ?? 0;
$star2 = $stats['star2'] ?? 0;
$star1 = $stats['star1'] ?? 0;

function sortIcon($col, $currentSort, $currentOrder) {
    if($col !== $currentSort) return '<i class="fas fa-sort"></i>';
    return $currentOrder === 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews | Hostel Admin</title>

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
        --danger-soft:rgba(255,68,68,0.1);
        --success:#00ff88;
        --success-soft:rgba(0,255,136,0.1);
        --warning:#ffaa00;
        --warning-soft:rgba(255,170,0,0.1);
        --info:#4488ff;
        --info-soft:rgba(68,136,255,0.1);
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
        flex-wrap:wrap;
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

    /* ===== RATING OVERVIEW ===== */
    .overview-grid{
        display:grid;
        grid-template-columns:1fr 2fr;
        gap:24px;
        margin-bottom:32px;
    }

    @media(max-width:900px){
        .overview-grid{grid-template-columns:1fr;}
    }

    .rating-card{
        background:var(--bg-secondary);
        border:2px solid var(--border);
        border-radius:var(--radius);
        padding:32px;
        transition:var(--transition);
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        text-align:center;
    }

    .rating-card:hover{
        border-color:var(--border-light);
        box-shadow:var(--shadow-hover);
    }

    .rating-big{
        font-family:'Space Grotesk',sans-serif;
        font-size:72px;
        font-weight:700;
        letter-spacing:-3px;
        line-height:1;
        margin-bottom:12px;
    }

    .rating-stars{
        display:flex;
        gap:6px;
        margin-bottom:12px;
        font-size:22px;
        color:var(--warning);
    }

    .rating-count{
        font-size:14px;
        color:var(--text-muted);
        font-weight:500;
    }

    .rating-bars{
        background:var(--bg-secondary);
        border:2px solid var(--border);
        border-radius:var(--radius);
        padding:32px;
        transition:var(--transition);
        display:flex;
        flex-direction:column;
        gap:12px;
        justify-content:center;
    }

    .rating-bars:hover{
        border-color:var(--border-light);
        box-shadow:var(--shadow-hover);
    }

    .rating-bar-row{
        display:flex;
        align-items:center;
        gap:12px;
        font-size:13px;
    }

    .rating-bar-label{
        min-width:40px;
        font-weight:700;
        color:var(--text-secondary);
        font-family:'Space Grotesk',sans-serif;
        display:flex;
        align-items:center;
        gap:4px;
    }

    .rating-bar-track{
        flex:1;
        height:10px;
        background:var(--bg-input);
        border-radius:5px;
        overflow:hidden;
    }

    .rating-bar-fill{
        height:100%;
        background:linear-gradient(90deg, var(--warning), #ffcc00);
        border-radius:5px;
        transition:width 1s cubic-bezier(0.4,0,0.2,1);
    }

    .rating-bar-count{
        min-width:30px;
        text-align:right;
        font-size:12px;
        color:var(--text-muted);
        font-weight:700;
    }

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
        border:2px solid var(--border);
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
        border:2px solid rgba(255,68,68,0.3);
    }

    .btn-danger:hover{
        background:var(--danger);
        color:white;
        border-color:var(--danger);
    }

    .btn-sm{padding:8px 14px;font-size:12px;}

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

    /* ===== REVIEWS GRID ===== */
    .reviews-grid{
        display:grid;
        grid-template-columns:repeat(auto-fill, minmax(380px, 1fr));
        gap:16px;
        margin-bottom:24px;
    }

    @media(max-width:600px){
        .reviews-grid{grid-template-columns:1fr;}
    }

    .review-card-full{
        background:var(--bg-secondary);
        border:2px solid var(--border);
        border-radius:var(--radius-sm);
        padding:24px;
        transition:var(--transition);
        display:flex;
        gap:16px;
    }

    .review-card-full:hover{
        border-color:var(--border-light);
        transform:translateY(-2px);
        box-shadow:var(--shadow-hover);
    }

    .review-avatar-lg{
        width:48px;
        height:48px;
        border-radius:50%;
        background:var(--bg-input);
        border:2px solid var(--border);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:16px;
        font-weight:700;
        color:var(--text-muted);
        flex-shrink:0;
        overflow:hidden;
    }

    .review-avatar-lg img{
        width:100%;
        height:100%;
        object-fit:cover;
    }

    .review-body{
        flex:1;
        min-width:0;
    }

    .review-header-row{
        display:flex;
        align-items:center;
        justify-content:space-between;
        margin-bottom:8px;
        flex-wrap:wrap;
        gap:8px;
    }

    .review-user-name{
        font-size:15px;
        font-weight:700;
        color:var(--text-primary);
    }

    .review-stars-row{
        display:flex;
        gap:3px;
        font-size:13px;
        color:var(--warning);
    }

    .review-text-full{
        font-size:14px;
        color:var(--text-secondary);
        line-height:1.7;
        margin-bottom:12px;
        word-wrap:break-word;
    }

    .review-meta-row{
        display:flex;
        align-items:center;
        justify-content:space-between;
        font-size:12px;
        color:var(--text-muted);
    }

    .review-id{
        font-family:'Space Grotesk',monospace;
        font-size:11px;
        background:var(--bg-input);
        padding:4px 10px;
        border-radius:6px;
        border:1px solid var(--border);
    }

    /* ===== PAGINATION ===== */
    .pagination{
        display:flex;
        align-items:center;
        justify-content:space-between;
        flex-wrap:wrap;
        gap:16px;
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
        border:2px solid var(--border);
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

    /* ===== EMPTY STATE ===== */
    .empty-state{
        text-align:center;
        padding:60px 20px;
        color:var(--text-muted);
        grid-column:1/-1;
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

    /* ===== ANIMATIONS ===== */
    @keyframes fadeUp{
        from{opacity:0;transform:translateY(20px);}
        to{opacity:1;transform:translateY(0);}
    }

    .rating-card, .rating-bars, .review-card-full{
        animation:fadeUp 0.5s ease forwards;
    }

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
        .filter-bar{flex-direction:column;}
        .filter-group{width:100%;}
        .filter-input, .filter-select{width:100%;}
        .rating-big{font-size:48px;}
    }
    </style>
</head>
<body>

<div class="container">

    <!-- HEADER -->
    <div class="page-header">
        <div class="page-title">
            <h1>Reviews.</h1>
            <p>All customer feedback and ratings</p>
        </div>
        <div class="header-actions">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- RATING OVERVIEW -->
    <div class="overview-grid">
        <div class="rating-card">
            <div class="rating-big"><?= $avgRating ?></div>
            <div class="rating-stars">
                <?php 
                $fullStars = floor($avgRating);
                $halfStar = ($avgRating - $fullStars) >= 0.5 ? 1 : 0;
                $emptyStars = 5 - $fullStars - $halfStar;
                
                for($i=0; $i<$fullStars; $i++) echo '<i class="fas fa-star"></i>';
                if($halfStar) echo '<i class="fas fa-star-half-alt"></i>';
                for($i=0; $i<$emptyStars; $i++) echo '<i class="far fa-star"></i>';
                ?>
            </div>
            <div class="rating-count"><?= number_format($totalReviews) ?> total reviews</div>
        </div>

        <div class="rating-bars">
            <?php 
            $starCounts = [5=>$star5, 4=>$star4, 3=>$star3, 2=>$star2, 1=>$star1];
            foreach($starCounts as $star => $count){
                $pct = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
            ?>
            <div class="rating-bar-row">
                <div class="rating-bar-label"><?= $star ?> <i class="fas fa-star" style="font-size:10px;color:var(--warning);"></i></div>
                <div class="rating-bar-track">
                    <div class="rating-bar-fill" style="width:<?= $pct ?>%"></div>
                </div>
                <div class="rating-bar-count"><?= number_format($count) ?></div>
            </div>
            <?php } ?>
        </div>
    </div>

    <!-- FILTER BAR -->
    <form method="GET" action="reviews.php" id="filterForm">
        <div class="filter-bar">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" class="filter-input" placeholder="Search user or review content..." 
                       value="<?= htmlspecialchars($search) ?>" name="search" id="searchInput">
            </div>
            
            <div class="filter-group">
                <span class="filter-label">Rating</span>
                <select class="filter-select" name="rating" onchange="document.getElementById('filterForm').submit()">
                    <option value="all" <?= $ratingFilter=='' || $ratingFilter=='all'?'selected':'' ?>>All Ratings</option>
                    <option value="5" <?= $ratingFilter=='5'?'selected':'' ?>>5 Stars</option>
                    <option value="4" <?= $ratingFilter=='4'?'selected':'' ?>>4 Stars</option>
                    <option value="3" <?= $ratingFilter=='3'?'selected':'' ?>>3 Stars</option>
                    <option value="2" <?= $ratingFilter=='2'?'selected':'' ?>>2 Stars</option>
                    <option value="1" <?= $ratingFilter=='1'?'selected':'' ?>>1 Star</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-filter"></i> Filter
            </button>
            
            <a href="reviews.php" class="btn btn-danger btn-sm">
                <i class="fas fa-undo"></i> Reset
            </a>
        </div>
    </form>

    <!-- REVIEWS GRID -->
    <div class="reviews-grid">
        <?php if(mysqli_num_rows($q) > 0): ?>
            <?php while($rev = mysqli_fetch_assoc($q)): 
                $emptyStars = 5 - $rev['rating'];
            ?>
            <div class="review-card-full">
                <div class="review-avatar-lg">
                    <?php if(!empty($rev['user_photo']) && file_exists('../' . $rev['user_photo'])): ?>
                    <img src="../<?= $rev['user_photo'] ?>" alt="<?= !empty($rev['username']) ? htmlspecialchars($rev['username']) : 'Deleted User' ?>">
                    <?php else: ?>
                    <?= !empty($rev['username']) ? strtoupper(substr($rev['username'],0,1)) : '?' ?>
                    <?php endif; ?>
                </div>
                <div class="review-body">
                    <div class="review-header-row">
                        <div class="review-user-name"><?= !empty($rev['username']) ? htmlspecialchars($rev['username']) : 'Deleted User' ?></div>
                        <div class="review-stars-row">
                            <?php 
                            for($s=0; $s<$rev['rating']; $s++) echo '<i class="fas fa-star"></i>';
                            for($s=0; $s<$emptyStars; $s++) echo '<i class="far fa-star"></i>';
                            ?>
                        </div>
                    </div>
                    <?php if(!empty($rev['review'])): ?>
                    <div class="review-text-full">"<?= htmlspecialchars($rev['review']) ?>"</div>
                    <?php else: ?>
                    <div class="review-text-full" style="color:var(--text-muted);font-style:italic;">No written review</div>
                    <?php endif; ?>
                    <div class="review-meta-row">
                        <span><?= date('d M Y • H:i', strtotime($rev['created_at'])) ?></span>
                        <span class="review-id">#<?= $rev['id'] ?></span>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-comment-slash"></i>
                <h3>No reviews found</h3>
                <p>Try adjusting your filters or search criteria.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- PAGINATION -->
    <?php if($totalPages > 1): ?>
    <div class="pagination">
        <div class="page-info">
            Showing <strong><?= $offset + 1 ?>-<?= min($offset + $perPage, $totalRows) ?></strong> of <strong><?= $totalRows ?></strong> reviews
        </div>
        <div class="page-buttons">
            <?php 
            $baseParams = array_diff_key($_GET, array_flip(['page']));
            $baseUrl = "reviews.php?".http_build_query($baseParams);
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

<script>
// ===== SEARCH AUTO SUBMIT =====
const searchInput = document.getElementById('searchInput');
let searchTimeout;

searchInput.addEventListener('input', function(e){
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        if(this.value.length >= 2 || this.value.length === 0){
            document.getElementById('filterForm').submit();
        }
    }, 600);
});

searchInput.addEventListener('keypress', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        clearTimeout(searchTimeout);
        document.getElementById('filterForm').submit();
    }
});

// ===== SORTING =====
function sortBy(col){
    const params = new URLSearchParams(window.location.search);
    const currentSort = params.get('sort') || 'created_at';
    const currentOrder = params.get('order') || 'DESC';
    
    if(currentSort === col){
        params.set('order', currentOrder === 'ASC' ? 'DESC' : 'ASC');
    } else {
        params.set('sort', col);
        params.set('order', 'ASC');
    }
    
    window.location.href = 'reviews.php?' + params.toString();
}
</script>

</body>
</html>