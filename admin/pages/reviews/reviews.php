<?php
/**
 * Admin Reviews Management Page
 * Displays and manages customer reviews and ratings
 */

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /admin/login");
    exit();
}

require_once dirname(__DIR__, 3) . '/config/database/db.php';

$page_title = 'Reviews Management';
$current_page = 'reviews';

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query
$query = "SELECT r.*, u.username, u.email, m.name as menu_item_name 
          FROM reviews r 
          LEFT JOIN users u ON r.user_id = u.id 
          LEFT JOIN menu_items m ON r.menu_item_id = m.id 
          WHERE 1=1";

$count_query = "SELECT COUNT(*) as total FROM reviews r 
                LEFT JOIN users u ON r.user_id = u.id 
                LEFT JOIN menu_items m ON r.menu_item_id = m.id 
                WHERE 1=1";

// Apply filters
if ($filter_status !== 'all') {
    $query .= " AND r.status = ?";
    $count_query .= " AND r.status = ?";
}

if (!empty($search_query)) {
    $query .= " AND (r.comment LIKE ? OR u.username LIKE ? OR m.name LIKE ?)";
    $count_query .= " AND (r.comment LIKE ? OR u.username LIKE ? OR m.name LIKE ?)";
}

$query .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";

// Prepare and execute count query
$stmt_count = $conn->prepare($count_query);
if ($filter_status !== 'all' && !empty($search_query)) {
    $search_term = "%$search_query%";
    $stmt_count->bind_param("ssss", $filter_status, $search_term, $search_term, $search_term);
} elseif ($filter_status !== 'all') {
    $stmt_count->bind_param("s", $filter_status);
} elseif (!empty($search_query)) {
    $search_term = "%$search_query%";
    $stmt_count->bind_param("sss", $search_term, $search_term, $search_term);
}
$stmt_count->execute();
$count_result = $stmt_count->get_result();
$total_reviews = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_reviews / $per_page);

// Prepare and execute main query
$stmt = $conn->prepare($query);
if ($filter_status !== 'all' && !empty($search_query)) {
    $search_term = "%$search_query%";
    $stmt->bind_param("sssii", $filter_status, $search_term, $search_term, $search_term, $per_page, $offset);
} elseif ($filter_status !== 'all') {
    $stmt->bind_param("sii", $filter_status, $per_page, $offset);
} elseif (!empty($search_query)) {
    $search_term = "%$search_query%";
    $stmt->bind_param("sssii", $search_term, $search_term, $search_term, $per_page, $offset);
} else {
    $stmt->bind_param("ii", $per_page, $offset);
}
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Include header
include dirname(__DIR__, 3) . '/admin/includes/header.php';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Reviews Management</h1>
        <p>Manage customer reviews and ratings</p>
    </div>

    <!-- Filters -->
    <div class="admin-filters">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label for="status">Status:</label>
                <select name="status" id="status">
                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="search">Search:</label>
                <input type="text" name="search" id="search" placeholder="Search reviews..." value="<?php echo htmlspecialchars($search_query); ?>">
            </div>

            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="?page=1" class="btn btn-secondary">Clear</a>
        </form>
    </div>

    <!-- Reviews Table -->
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Menu Item</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No reviews found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($review['id']); ?></td>
                            <td><?php echo htmlspecialchars($review['username'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($review['menu_item_name'] ?? 'N/A'); ?></td>
                            <td>
                                <div class="rating-display">
                                    <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                        <i class="fas fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars(substr($review['comment'], 0, 50)) . (strlen($review['comment']) > 50 ? '...' : ''); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($review['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($review['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($review['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?action=view&id=<?php echo $review['id']; ?>" class="btn btn-sm btn-info">View</a>
                                    <a href="?action=approve&id=<?php echo $review['id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                    <a href="?action=reject&id=<?php echo $review['id']; ?>" class="btn btn-sm btn-danger">Reject</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=1&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search_query); ?>" class="btn btn-sm">First</a>
                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search_query); ?>" class="btn btn-sm">Previous</a>
            <?php endif; ?>

            <span class="page-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search_query); ?>" class="btn btn-sm">Next</a>
                <a href="?page=<?php echo $total_pages; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search_query); ?>" class="btn btn-sm">Last</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.admin-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.admin-header {
    margin-bottom: 30px;
}

.admin-header h1 {
    font-size: 28px;
    margin-bottom: 5px;
}

.admin-filters {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.filter-form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-weight: 600;
    font-size: 14px;
}

.filter-group select,
.filter-group input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.admin-table-container {
    overflow-x: auto;
    margin-bottom: 30px;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.admin-table thead {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.admin-table th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
}

.admin-table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
}

.admin-table tbody tr:hover {
    background: #f8f9fa;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-approved {
    background: #d4edda;
    color: #155724;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.rating-display {
    color: #ffc107;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.btn {
    padding: 8px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #006C3B;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

.btn-info {
    background: #17a2b8;
    color: white;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    align-items: center;
}

.page-info {
    font-weight: 600;
}

.text-center {
    text-align: center;
}
</style>

<?php
include dirname(__DIR__, 3) . '/admin/includes/footer.php';
?>
