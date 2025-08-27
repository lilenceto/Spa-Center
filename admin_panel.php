<?php
// admin_panel.php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?next=admin_panel.php");
    exit();
}

// Check if user has admin role
$mysqli = new mysqli("localhost", "root", "", "spa_center");
if ($mysqli->connect_error) {
    die("Database connection error: " . $mysqli->connect_error);
}

$adminCheckStmt = $mysqli->prepare("
    SELECT COUNT(*) as is_admin 
    FROM user_roles ur 
    JOIN roles r ON ur.role_id = r.id 
    WHERE ur.user_id = ? AND r.name = 'admin'
");
$adminCheckStmt->bind_param("i", $_SESSION['user_id']);
$adminCheckStmt->execute();
$adminResult = $adminCheckStmt->get_result();
$adminCheck = $adminResult->fetch_assoc();

if ($adminCheck['is_admin'] == 0) {
    $adminCheckStmt->close();
    $mysqli->close();
    header("Location: login.php?next=admin_panel.php");
    exit();
}
$adminCheckStmt->close();

include 'header.php';

$mysqli = new mysqli("localhost", "root", "", "spa_center");
if ($mysqli->connect_error) {
    die("Database connection error: " . $mysqli->connect_error);
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $bulkAction = $_POST['bulk_action'];
    $selectedIds = $_POST['selected_reservations'] ?? [];
    
    if (!empty($selectedIds) && in_array($bulkAction, ['approve', 'reject', 'delete'])) {
        $successCount = 0;
        
        foreach ($selectedIds as $reservationId) {
            $id = (int)$reservationId;
            
            if ($bulkAction === 'approve') {
                $stmt = $mysqli->prepare("UPDATE reservations SET status = 'Approved' WHERE id = ? AND status = 'Awaiting'");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) $successCount++;
                $stmt->close();
            } elseif ($bulkAction === 'reject') {
                $stmt = $mysqli->prepare("UPDATE reservations SET status = 'Cancelled' WHERE id = ? AND status = 'Awaiting'");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) $successCount++;
                $stmt->close();
            } elseif ($bulkAction === 'delete') {
                // Delete related records first
                $mysqli->query("DELETE FROM reservation_status_history WHERE reservation_id = $id");
                $stmt = $mysqli->prepare("DELETE FROM reservations WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) $successCount++;
                $stmt->close();
            }
        }
        
        $message = "Successfully processed $successCount reservations";
        $messageType = "success";
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$dateFilter = $_GET['date'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';

// Build the SQL query
$baseSql = "
    SELECT 
        r.id,
        r.user_id,
        u.name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone,
        s.name AS service_name,
        s.duration,
        s.price,
        c.name AS category_name,
        e.name AS employee_name,
        r.employee_id,
        r.reservation_date,
        r.reservation_time,
        r.status,
        r.created_at,
        CONCAT(r.reservation_date, ' ', r.reservation_time) AS start_datetime,
        ADDTIME(CONCAT(r.reservation_date, ' ', r.reservation_time), SEC_TO_TIME(s.duration * 60)) AS end_datetime
    FROM reservations r
    JOIN users u ON u.id = r.user_id
    JOIN services s ON s.id = r.service_id
    JOIN service_categories c ON c.id = s.category_id
    LEFT JOIN employees e ON e.id = r.employee_id
    WHERE 1=1
";

$params = [];
$types = "";

// Add status filter
if ($statusFilter !== 'all') {
    $baseSql .= " AND r.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

// Add date filter
if ($dateFilter !== 'all') {
    if ($dateFilter === 'today') {
        $baseSql .= " AND DATE(r.reservation_date) = CURDATE()";
    } elseif ($dateFilter === 'tomorrow') {
        $baseSql .= " AND DATE(r.reservation_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
    } elseif ($dateFilter === 'week') {
        $baseSql .= " AND r.reservation_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($dateFilter === 'past') {
        $baseSql .= " AND r.reservation_date < CURDATE()";
    }
}

// Add search filter
if (!empty($searchTerm)) {
    $baseSql .= " AND (u.name LIKE ? OR u.email LIKE ? OR s.name LIKE ? OR c.name LIKE ?)";
    $searchPattern = "%$searchTerm%";
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $types .= "ssss";
}

$baseSql .= " ORDER BY r.reservation_date ASC, r.reservation_time ASC";

// Execute query
$stmt = $mysqli->prepare($baseSql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get statistics
$statsSql = "SELECT 
    status, 
    COUNT(*) as count,
    SUM(CASE WHEN DATE(reservation_date) = CURDATE() THEN 1 ELSE 0 END) as today_count
FROM reservations 
GROUP BY status";
$statsResult = $mysqli->query($statsSql);

$stats = [];
$totalToday = 0;
while ($row = $statsResult->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
    $totalToday += $row['today_count'];
}
?>

<div class="page-container">
    <div class="admin-hero">
        <div class="hero-content">
            <h1 class="hero-title">
                <i class="fas fa-cogs"></i>
                Admin Panel
            </h1>
            <p class="hero-subtitle">Manage reservations, approve requests, and monitor system activity</p>
            <div class="hero-actions">
                <a href="admin_dashboard.php" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt"></i> Quick Dashboard
                </a>
                         <a href="manage_categories.php" class="btn btn-primary">
             <i class="fas fa-concierge-bell"></i> Manage Procedures
         </a>
            </div>
        </div>
    </div>

    <!-- Statistics Dashboard -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon awaiting">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?= $stats['Awaiting'] ?? 0 ?></h3>
                <p>Awaiting Approval</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon approved">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?= $stats['Approved'] ?? 0 ?></h3>
                <p>Approved</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon completed">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <h3><?= $stats['Completed'] ?? 0 ?></h3>
                <p>Completed</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon cancelled">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?= $stats['Cancelled'] ?? 0 ?></h3>
                <p>Cancelled</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon today">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalToday ?></h3>
                <p>Today's Total</p>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-container">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <label for="status">Status:</label>
                <select name="status" id="status">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="Awaiting" <?= $statusFilter === 'Awaiting' ? 'selected' : '' ?>>Awaiting</option>
                    <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="date">Date:</label>
                <select name="date" id="date">
                    <option value="all" <?= $dateFilter === 'all' ? 'selected' : '' ?>>All Dates</option>
                    <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="tomorrow" <?= $dateFilter === 'tomorrow' ? 'selected' : '' ?>>Tomorrow</option>
                    <option value="week" <?= $dateFilter === 'week' ? 'selected' : '' ?>>This Week</option>
                    <option value="past" <?= $dateFilter === 'past' ? 'selected' : '' ?>>Past</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="search">Search:</label>
                <input type="text" name="search" id="search" placeholder="Name, email, service..." value="<?= htmlspecialchars($searchTerm) ?>">
            </div>
            
            <button type="submit" class="filter-btn">
                <i class="fas fa-search"></i> Filter
            </button>
            
            <a href="admin_panel.php" class="clear-filters-btn">
                <i class="fas fa-times"></i> Clear
            </a>
        </form>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($message)): ?>
        <div class="message <?= $messageType ?>">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Reservations Table -->
    <div class="reservations-container">
        <div class="table-header">
            <h2>Reservations Management</h2>
            <div class="bulk-actions">
                <form method="POST" id="bulkForm" onsubmit="return confirm('Are you sure you want to perform this action on the selected reservations?')">
                    <select name="bulk_action" required>
                        <option value="">Bulk Actions</option>
                        <option value="approve">Approve Selected</option>
                        <option value="reject">Reject Selected</option>
                        <option value="delete">Delete Selected</option>
                    </select>
                    <button type="submit" class="bulk-action-btn">Apply</button>
                </form>
            </div>
        </div>

        <div class="table-container" style="width: 100%; overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()"></th>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Contact</th>
                        <th>Service</th>
                        <th>Category</th>
                                                 <th>Date & Time</th>
                         <th>End Time</th>
                         <th>Duration</th>
                         <th>Price</th>
                         <th>Specialist</th>
                         <th>Status</th>
                         <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="reservation-row status-<?= strtolower($row['status']) ?>">
                                <td>
                                    <input type="checkbox" name="selected_reservations[]" value="<?= $row['id'] ?>" form="bulkForm">
                                </td>
                                <td class="reservation-id"><?= $row['id'] ?></td>
                                <td class="client-info">
                                    <strong><?= htmlspecialchars($row['user_name']) ?></strong>
                                    <small>ID: <?= $row['user_id'] ?></small>
                                </td>
                                <td class="contact-info">
                                    <div><?= htmlspecialchars($row['user_email']) ?></div>
                                    <div><?= htmlspecialchars($row['user_phone'] ?? 'N/A') ?></div>
                                </td>
                                <td class="service-info">
                                    <strong><?= htmlspecialchars($row['service_name']) ?></strong>
                                </td>
                                <td class="category"><?= htmlspecialchars($row['category_name']) ?></td>
                                                                 <td class="datetime">
                                     <div class="date"><?= date('M j, Y', strtotime($row['reservation_date'])) ?></div>
                                     <div class="time"><?= date('H:i', strtotime($row['reservation_time'])) ?></div>
                                 </td>
                                 <td class="end-time">
                                     <div class="time"><?= date('H:i', strtotime($row['end_datetime'])) ?></div>
                                 </td>
                                 <td class="duration"><?= $row['duration'] ?> min</td>
                                <td class="price">€<?= number_format($row['price'], 2) ?></td>
                                <td class="specialist">
                                    <?php if ($row['employee_id']): ?>
                                        <?= htmlspecialchars($row['employee_name']) ?>
                                    <?php else: ?>
                                        <span class="no-specialist">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="status">
                                    <select class="status-select" onchange="changeStatus(<?= $row['id'] ?>, this.value)" data-current="<?= $row['status'] ?>">
                                        <option value="Awaiting" <?= $row['status'] === 'Awaiting' ? 'selected' : '' ?>>Awaiting</option>
                                        <option value="Approved" <?= $row['status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
                                        <option value="Completed" <?= $row['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="Cancelled" <?= $row['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </td>
                                <td class="created">
                                    <?= date('M j, H:i', strtotime($row['created_at'])) ?>
                                </td>
                                                                 <td class="actions">
                                     <div class="action-buttons">
                                         <button onclick="deleteReservation(<?= $row['id'] ?>)" class="action-btn delete-btn" title="Delete">
                                             <i class="fas fa-trash"></i>
                                         </button>
                                     </div>
                                 </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="13" class="no-reservations">
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <h3>No Reservations Found</h3>
                                    <p>No reservations match your current filters.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function deleteReservation(id) {
    if (confirm('Are you sure you want to delete this reservation? This action cannot be undone.')) {
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        fetch('reservation_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}&action=delete`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
                button.innerHTML = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred');
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
}

function toggleAllCheckboxes() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('input[name="selected_reservations[]"]');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function changeStatus(reservationId, newStatus) {
    const select = event.target;
    const currentStatus = select.getAttribute('data-current');
    
    if (newStatus === currentStatus) return; // No change
    
    if (confirm(`Are you sure you want to change the status from "${currentStatus}" to "${newStatus}"?`)) {
        // Show loading state
        select.disabled = true;
        const originalValue = select.value;
        
        fetch('reservation_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${reservationId}&action=status&status=${newStatus}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the data attribute
                select.setAttribute('data-current', newStatus);
                // Show success feedback
                select.style.borderColor = '#4caf50';
                setTimeout(() => {
                    select.style.borderColor = '';
                }, 2000);
            } else {
                alert('Error: ' + data.message);
                select.value = currentStatus; // Revert to original
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred');
            select.value = currentStatus; // Revert to original
        })
        .finally(() => {
            select.disabled = false;
        });
    } else {
        // Revert to original value if user cancels
        select.value = currentStatus;
    }
}
</script>

<?php
$stmt->close();
$mysqli->close();

function getStatusBadge($status) {
    $statusClasses = [
        'Awaiting' => 'status-awaiting',
        'Approved' => 'status-approved',
        'Completed' => 'status-completed',
        'Cancelled' => 'status-cancelled'
    ];
    
    $statusLabels = [
        'Awaiting' => 'Awaiting',
        'Approved' => 'Approved',
        'Completed' => 'Completed',
        'Cancelled' => 'Cancelled'
    ];
    
    $class = $statusClasses[$status] ?? 'status-default';
    $label = $statusLabels[$status] ?? $status;
    
    return "<span class='status-badge {$class}'>{$label}</span>";
}
?>

<style>
/* Admin Panel Styles */
.page-container {
    max-width: 100vw;
    width: 100%;
    padding: 0 3rem;
    margin: 0 auto;
}

.admin-hero {
    background: linear-gradient(135deg, rgba(15, 76, 58, 0.9) 0%, rgba(26, 95, 74, 0.9) 100%);
    padding: 2rem 1rem;
    text-align: center;
    margin-bottom: 2rem;
    border-radius: 20px;
    position: relative;
    overflow: hidden;
}

.hero-title {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    font-weight: 600;
    color: #d4af37;
    margin-bottom: 1rem;
}

.hero-subtitle {
    font-size: 1.1rem;
    color: #f8f9fa;
    opacity: 0.9;
}

.hero-actions {
    margin-top: 1.5rem;
}

.hero-actions .btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: rgba(212, 175, 55, 0.2);
    border: 1px solid rgba(212, 175, 55, 0.3);
    color: #d4af37;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.hero-actions .btn:hover {
    background: rgba(212, 175, 55, 0.3);
    border-color: rgba(212, 175, 55, 0.5);
    transform: translateY(-2px);
}

/* Statistics Dashboard */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 15px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    backdrop-filter: blur(10px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-icon.awaiting { background: linear-gradient(135deg, #ffc107, #ff9800); }
.stat-icon.approved { background: linear-gradient(135deg, #4caf50, #45a049); }
.stat-icon.completed { background: linear-gradient(135deg, #2196f3, #1976d2); }
.stat-icon.cancelled { background: linear-gradient(135deg, #f44336, #d32f2f); }
.stat-icon.today { background: linear-gradient(135deg, #9c27b0, #7b1fa2); }

.stat-content h3 {
    font-size: 2rem;
    font-weight: 700;
    color: #f8f9fa;
    margin: 0;
}

.stat-content p {
    color: #d4af37;
    margin: 0;
    font-size: 0.9rem;
}

/* Filters */
.filters-container {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(212, 175, 55, 0.2);
    border-radius: 15px;
    padding: 1rem;
    margin-bottom: 2rem;
}

.filters-form {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: end;
    width: 100%;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex: 3;
}

.filter-group label {
    color: #d4af37;
    font-weight: 600;
    font-size: 0.9rem;
}

.filter-group select,
.filter-group input {
    padding: 0.75rem;
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.1);
    color: #f8f9fa;
    min-width: 0;
    flex: 2;
}

/* Make dropdown options more visible */
.filter-group select option {
    background: #ffffff;
    color: #000000;
    padding: 0.5rem;
}

.filter-group select option:hover {
    background: #f8f9fa;
    color: #000000;
}

.filter-group select option:checked {
    background: #d4af37;
    color: #000000;
}

.filter-btn,
.clear-filters-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.filter-btn {
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: #0f4c3a;
}

.clear-filters-btn {
    background: rgba(255, 255, 255, 0.1);
    color: #f8f9fa;
    border: 1px solid rgba(212, 175, 55, 0.3);
}

.filter-btn:hover,
.clear-filters-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

/* Ensure filter fields expand to fill available space */
.filter-group select,
.filter-group input {
    width: 100%;
    box-sizing: border-box;
}

/* Messages */
.message {
    padding: 1rem 1.5rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.message.success {
    background: rgba(39, 174, 96, 0.2);
    border: 1px solid rgba(39, 174, 96, 0.3);
    color: #2ecc71;
}

.message.error {
    background: rgba(220, 53, 69, 0.2);
    border: 1px solid rgba(220, 53, 69, 0.3);
    color: #dc3545;
}

/* Table */
.reservations-container {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(212, 175, 55, 0.2);
    border-radius: 15px;
    padding: 1rem;
    width: 100%;
    max-width: 100%;
    margin: 0;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.table-header h2 {
    color: #d4af37;
    margin: 0;
}

.bulk-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.bulk-actions select {
    padding: 0.5rem;
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 5px;
    background: rgba(255, 255, 255, 0.1);
    color: #f8f9fa;
}

/* Make bulk action dropdown options more visible */
.bulk-actions select option {
    background: #ffffff;
    color: #000000;
    padding: 0.5rem;
}

.bulk-actions select option:hover {
    background: #f8f9fa;
    color: #000000;
}

.bulk-actions select option:checked {
    background: #d4af37;
    color: #000000;
}

.bulk-action-btn {
    padding: 0.5rem 1rem;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
}

.bulk-action-btn:hover {
    background: #c82333;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    overflow: hidden;
    table-layout: auto;
    min-width: 100%;
}

.admin-table th {
    background: rgba(212, 175, 55, 0.2);
    color: #0f4c3a;
    font-weight: 600;
    padding: 1rem 0.75rem;
    text-align: left;
    font-size: 0.9rem;
}

.admin-table td {
    padding: 1rem 0.75rem;
    border-top: 1px solid rgba(212, 175, 55, 0.1);
    color: #f8f9fa;
    font-size: 0.9rem;
}

.reservation-row:hover {
    background: rgba(212, 175, 55, 0.05);
}

.status-awaiting { border-left: 4px solid #ffc107; }
.status-approved { border-left: 4px solid #4caf50; }
.status-completed { border-left: 4px solid #2196f3; }
.status-cancelled { border-left: 4px solid #f44336; }

.client-info strong { display: block; color: #d4af37; }
.client-info small { color: #6c757d; font-size: 0.8rem; }

.contact-info div { margin-bottom: 0.25rem; }
.contact-info div:last-child { margin-bottom: 0; }

.service-info strong { color: #d4af37; }

.datetime .date { font-weight: 600; color: #d4af37; }
.datetime .time { color: #d4af37; font-size: 0.9rem; }

.end-time .time { 
    color: #e74c3c; 
    font-size: 0.9rem; 
    font-weight: 500;
}

.no-specialist {
    color: #6c757d;
    font-style: italic;
    font-size: 0.8rem;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    text-decoration: none;
    color: white;
    font-size: 0.8rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.approve-btn { background: rgba(39, 174, 96, 0.8); }
.reject-btn { background: rgba(220, 53, 69, 0.8); }
.edit-btn { background: rgba(0, 123, 255, 0.8); }
.delete-btn { background: rgba(108, 117, 125, 0.8); }

.action-btn:hover {
    transform: scale(1.1);
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.status-awaiting { background: rgba(255, 193, 7, 0.2); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.3); }
.status-approved { background: rgba(39, 174, 96, 0.2); color: #27ae60; border: 1px solid rgba(39, 174, 96, 0.3); }
.status-completed { background: rgba(108, 117, 125, 0.2); color: #6c757d; border: 1px solid rgba(108, 117, 125, 0.3); }
.status-cancelled { background: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3); }

/* Status Select Dropdown */
.status-select {
    padding: 0.25rem 0.5rem;
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 5px;
    background: rgba(255, 255, 255, 0.1);
    color: #f8f9fa;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 100px;
}

.status-select:focus {
    outline: none;
    border-color: #d4af37;
    box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.2);
}

.status-select:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.status-select option {
    background: #ffffff;
    color: #000000;
    padding: 0.5rem;
}

.status-select option:hover {
    background: #f8f9fa;
    color: #000000;
}

.status-select option:checked {
    background: #d4af37;
    color: #000000;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
}

.empty-state i {
    font-size: 3rem;
    color: #6c757d;
    margin-bottom: 1rem;
}

.empty-state h3 {
    color: #d4af37;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #f8f9fa;
    opacity: 0.8;
}

/* Responsive */
@media (max-width: 1200px) {
    .stats-container {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .filters-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group select,
    .filter-group input {
        min-width: auto;
        flex: none;
    }
}

@media (max-width: 768px) {
    .admin-hero {
        padding: 2rem 1rem;
    }
    
    .hero-title {
        font-size: 2rem;
    }
    
    .table-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .bulk-actions {
        justify-content: center;
    }
    
    .admin-table {
        font-size: 0.8rem;
    }
    
    .admin-table th,
    .admin-table td {
        padding: 0.5rem 0.25rem;
    }
}
</style>
