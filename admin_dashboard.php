<?php
// admin_dashboard.php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?next=admin_dashboard.php");
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
    header("Location: login.php?next=admin_dashboard.php");
    exit();
}
$adminCheckStmt->close();

include 'header.php';

$mysqli = new mysqli("localhost", "root", "", "spa_center");
if ($mysqli->connect_error) {
    die("Database connection error: " . $mysqli->connect_error);
}

// Get quick statistics
$statsSql = "SELECT 
    status, 
    COUNT(*) as count,
    SUM(CASE WHEN DATE(reservation_date) = CURDATE() THEN 1 ELSE 0 END) as today_count,
    SUM(CASE WHEN DATE(reservation_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) as tomorrow_count
FROM reservations 
GROUP BY status";
$statsResult = $mysqli->query($statsSql);

$stats = [];
$totalToday = 0;
$totalTomorrow = 0;
while ($row = $statsResult->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
    $totalToday += $row['today_count'];
    $totalTomorrow += $row['tomorrow_count'];
}

// Get recent reservations
$recentSql = "SELECT 
    r.id, r.status, r.reservation_date, r.reservation_time,
    u.name AS user_name, s.name AS service_name
FROM reservations r
JOIN users u ON u.id = r.user_id
JOIN services s ON s.id = r.service_id
ORDER BY r.created_at DESC
LIMIT 10";
$recentResult = $mysqli->query($recentSql);

$mysqli->close();
?>

<div class="page-container">
    <div class="admin-hero">
        <div class="hero-content">
            <h1 class="hero-title">
                <i class="fas fa-tachometer-alt"></i>
                Admin Dashboard
            </h1>
            <p class="hero-subtitle">Welcome to your Spa Center management console</p>
            <div class="hero-actions">
                <a href="admin_panel.php" class="btn btn-primary">
                    <i class="fas fa-cogs"></i> Manage Reservations
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?= $stats['Awaiting'] ?? 0 ?></h3>
                <p>Awaiting Approval</p>
                <small>Requires your attention</small>
            </div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?= $stats['Approved'] ?? 0 ?></h3>
                <p>Approved</p>
                <small>Ready for service</small>
            </div>
        </div>
        
        <div class="stat-card info">
            <div class="stat-icon">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalToday ?></h3>
                <p>Today's Total</p>
                <small>All reservations today</small>
            </div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-calendar-week"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalTomorrow ?></h3>
                <p>Tomorrow's Total</p>
                <small>Upcoming reservations</small>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-grid">
            <a href="admin_panel.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-list-alt"></i>
                </div>
                <h3>Manage Reservations</h3>
                <p>View, approve, reject, and manage all reservations</p>
            </a>
            
            <a href="reservations.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3>View All Bookings</h3>
                <p>See all reservations in the system</p>
            </a>
            
            <a href="add_service.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h3>Add New Service</h3>
                <p>Create new spa services and treatments</p>
            </a>
            
            <a href="category.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <h3>Manage Categories</h3>
                <p>Organize services into categories</p>
            </a>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
        <h2>Recent Reservations</h2>
        <div class="activity-list">
            <?php if ($recentResult && $recentResult->num_rows > 0): ?>
                <?php while ($row = $recentResult->fetch_assoc()): ?>
                    <div class="activity-item status-<?= strtolower($row['status']) ?>">
                        <div class="activity-icon">
                            <i class="fas fa-<?= $row['status'] === 'Awaiting' ? 'clock' : ($row['status'] === 'Approved' ? 'check' : 'calendar') ?>"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">
                                <strong><?= htmlspecialchars($row['user_name']) ?></strong> 
                                booked <strong><?= htmlspecialchars($row['service_name']) ?></strong>
                            </div>
                            <div class="activity-details">
                                <?= date('M j, Y', strtotime($row['reservation_date'])) ?> at <?= date('H:i', strtotime($row['reservation_time'])) ?>
                                <span class="status-badge"><?= $row['status'] ?></span>
                            </div>
                        </div>
                        <div class="activity-actions">
                            <a href="admin_panel.php" class="btn-small">Manage</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-activity">
                    <i class="fas fa-inbox"></i>
                    <p>No recent reservations</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- System Status -->
    <div class="system-status">
        <h2>System Status</h2>
        <div class="status-grid">
            <div class="status-item">
                <div class="status-indicator online"></div>
                <div class="status-info">
                    <h4>Database</h4>
                    <p>Connected and operational</p>
                </div>
            </div>
            
            <div class="status-item">
                <div class="status-indicator online"></div>
                <div class="status-info">
                    <h4>Reservation System</h4>
                    <p>All functions working</p>
                </div>
            </div>
            
            <div class="status-item">
                <div class="status-indicator online"></div>
                <div class="status-info">
                    <h4>User Management</h4>
                    <p>Authentication active</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Admin Dashboard Styles */
.admin-hero {
    background: linear-gradient(135deg, rgba(15, 76, 58, 0.9) 0%, rgba(26, 95, 74, 0.9) 100%);
    padding: 3rem 2rem;
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

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 15px;
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.stat-card.primary { border-left: 4px solid #ffc107; }
.stat-card.success { border-left: 4px solid #4caf50; }
.stat-card.info { border-left: 4px solid #2196f3; }
.stat-card.warning { border-left: 4px solid #ff9800; }

.stat-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
}

.stat-card.primary .stat-icon { background: linear-gradient(135deg, #ffc107, #ff9800); }
.stat-card.success .stat-icon { background: linear-gradient(135deg, #4caf50, #45a049); }
.stat-card.info .stat-icon { background: linear-gradient(135deg, #2196f3, #1976d2); }
.stat-card.warning .stat-icon { background: linear-gradient(135deg, #ff9800, #f57c00); }

.stat-content h3 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #f8f9fa;
    margin: 0 0 0.5rem 0;
}

.stat-content p {
    color: #d4af37;
    margin: 0 0 0.25rem 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.stat-content small {
    color: #6c757d;
    font-size: 0.9rem;
}

/* Quick Actions */
.quick-actions {
    margin-bottom: 3rem;
}

.quick-actions h2 {
    color: #d4af37;
    margin-bottom: 1.5rem;
    font-size: 1.8rem;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.action-card {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 15px;
    padding: 2rem;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
    text-align: center;
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    background: rgba(255, 255, 255, 0.15);
}

.action-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #d4af37, #b8941f);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #0f4c3a;
    margin: 0 auto 1.5rem auto;
}

.action-card h3 {
    color: #d4af37;
    margin-bottom: 1rem;
    font-size: 1.3rem;
}

.action-card p {
    color: #f8f9fa;
    opacity: 0.8;
    line-height: 1.5;
}

/* Recent Activity */
.recent-activity {
    margin-bottom: 3rem;
}

.recent-activity h2 {
    color: #d4af37;
    margin-bottom: 1.5rem;
    font-size: 1.8rem;
}

.activity-list {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(212, 175, 55, 0.2);
    border-radius: 15px;
    padding: 1.5rem;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid rgba(212, 175, 55, 0.1);
    transition: all 0.3s ease;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item:hover {
    background: rgba(212, 175, 55, 0.05);
}

.activity-item.status-awaiting { border-left: 4px solid #ffc107; }
.activity-item.status-approved { border-left: 4px solid #4caf50; }
.activity-item.status-completed { border-left: 4px solid #2196f3; }
.activity-item.status-cancelled { border-left: 4px solid #f44336; }

.activity-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(212, 175, 55, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #d4af37;
}

.activity-content {
    flex: 1;
}

.activity-title {
    color: #f8f9fa;
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.activity-details {
    color: #6c757d;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.status-badge {
    background: rgba(212, 175, 55, 0.2);
    color: #d4af37;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
}

.btn-small {
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: #0f4c3a;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-small:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
}

.no-activity {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.no-activity i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

/* System Status */
.system-status h2 {
    color: #d4af37;
    margin-bottom: 1.5rem;
    font-size: 1.8rem;
}

.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.status-item {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 15px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.status-indicator {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #4caf50;
    animation: pulse 2s infinite;
}

.status-indicator.online { background: #4caf50; }
.status-indicator.offline { background: #f44336; }
.status-indicator.warning { background: #ff9800; }

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.status-info h4 {
    color: #d4af37;
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
}

.status-info p {
    color: #f8f9fa;
    margin: 0;
    font-size: 0.9rem;
    opacity: 0.8;
}

/* Responsive */
@media (max-width: 768px) {
    .admin-hero {
        padding: 2rem 1rem;
    }
    
    .hero-title {
        font-size: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .action-grid {
        grid-template-columns: 1fr;
    }
    
    .status-grid {
        grid-template-columns: 1fr;
    }
    
    .activity-item {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
    
    .activity-actions {
        width: 100%;
        text-align: center;
    }
}
</style>
