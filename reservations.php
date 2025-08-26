<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?next=reservations.php");
    exit();
}

include 'header.php';

$role = $_SESSION['role'] ?? 'client';
$uid = (int)($_SESSION['user_id'] ?? 0);

$mysqli = new mysqli("localhost", "root", "", "spa_center");
if ($mysqli->connect_error) {
    die("Database connection error: " . $mysqli->connect_error);
}

// First, update status of past reservations to "Completed"
$updatePastReservations = "
    UPDATE reservations 
    SET status = 'Completed' 
    WHERE status IN ('Awaiting', 'Approved') 
    AND DATE(reservation_date) < CURDATE()
";
$mysqli->query($updatePastReservations);

// Also update reservations from today that have passed their time
$updateTodayPastReservations = "
    UPDATE reservations 
    SET status = 'Completed' 
    WHERE status IN ('Awaiting', 'Approved') 
    AND DATE(reservation_date) = CURDATE() 
    AND TIME(reservation_time) < CURTIME()
";
$mysqli->query($updateTodayPastReservations);

// Base SQL query with all fields
$baseSql = "
    SELECT
        r.id,
        r.user_id,
        u.name AS user_name,
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
        DATE_ADD(CONCAT(r.reservation_date, ' ', r.reservation_time), INTERVAL s.duration MINUTE) AS end_datetime
    FROM reservations r
    JOIN users u ON u.id = r.user_id
    JOIN services s ON s.id = r.service_id
    JOIN service_categories c ON c.id = s.category_id
    LEFT JOIN employees e ON e.id = r.employee_id
";

// Client sees only their reservations, staff/admin see all
if ($role === 'client') {
    $sql = $baseSql . " WHERE r.user_id = ? ORDER BY r.reservation_date DESC, r.reservation_time DESC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = $baseSql . " ORDER BY r.reservation_date DESC, r.reservation_time DESC";
    $result = $mysqli->query($sql);
}

function formatDateTime($date, $time) {
    if (!$date || !$time) return '—';
    return date('Y-m-d H:i', strtotime($date . ' ' . $time));
}

function formatDate($date) {
    if (!$date) return '—';
    return date('Y-m-d', strtotime($date));
}

function formatTime($time) {
    if (!$time) return '—';
    return date('H:i', strtotime($time));
}

function getStatusBadge($status) {
    $statusClasses = [
        'Awaiting' => 'status-awaiting',
        'Approved' => 'status-approved',
        'Completed' => 'status-completed',
        'Cancelled' => 'status-cancelled'
    ];
    
    $statusLabels = [
        'Awaiting' => 'Awaiting Approval',
        'Approved' => 'Approved',
        'Completed' => 'Completed',
        'Cancelled' => 'Cancelled'
    ];
    
    $class = $statusClasses[$status] ?? 'status-default';
    $label = $statusLabels[$status] ?? $status;
    
    return "<span class='status-badge {$class}'>{$label}</span>";
}

function getTimeUntilReservation($date, $time) {
    $datetime = strtotime($date . ' ' . $time);
    $now = time();
    $diff = $datetime - $now;
    
    if ($diff <= 0) return 'Past';
    
    $days = floor($diff / 86400);
    $hours = floor(($diff % 86400) / 3600);
    $minutes = floor(($diff % 3600) / 60);
    
    if ($days > 0) return "{$days}d {$hours}h";
    if ($hours > 0) return "{$hours}h {$minutes}m";
    return "{$minutes}m";
}
?>

<div class="page-container" style="max-width: 100vw; margin: 0; padding: 0;">
    <div class="reservations-hero">
        <div class="floating-pool-element"></div>
        <div class="floating-pool-element"></div>
        <div class="floating-pool-element"></div>
        
        <div class="hero-content">
            <h1 class="hero-title">
                <i class="fas fa-calendar-alt"></i>
                My Reservations
            </h1>
            <p class="hero-subtitle">Track your wellness appointments and booking history</p>
        </div>
    </div>

    <div class="reservations-container">
        <div class="reservations-header">
            <div class="header-actions">
                <a href="add_reservation.php" class="new-reservation-btn">
                    <i class="fas fa-plus"></i> New Reservation
                </a>
                <a href="index.php" class="back-home-btn">
                    <i class="fas fa-home"></i> Back Home
                </a>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    Reservation created successfully! It is now awaiting approval.
                </div>
            <?php endif; ?>
        </div>

        <div class="reservations-table-container">
            <table class="reservations-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <?php if ($role !== 'client'): ?>
                            <th>Client</th>
                        <?php endif; ?>
                        <th>Service</th>
                        <th>Category</th>
                        <th>Duration</th>
                        <th>Price</th>
                        <th>Specialist</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                                                 <th>Time Until</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                                $isOwner = ((int)$row['user_id'] === $uid);
                                $timeUntil = getTimeUntilReservation($row['reservation_date'], $row['reservation_time']);
                                
                                $canStaffEdit = ($role === 'admin' || $role === 'employee');
                                $canClientCancel = ($role === 'client' && $isOwner && 
                                                   in_array($row['status'], ['Awaiting', 'Approved']) && 
                                                   strtotime($row['reservation_date'] . ' ' . $row['reservation_time']) > time());
                            ?>
                            <tr class="reservation-row <?= $row['status'] === 'Completed' ? 'completed' : '' ?>">
                                <td class="reservation-id"><?= (int)$row['id'] ?></td>
                                
                                <?php if ($role !== 'client'): ?>
                                    <td class="client-name"><?= htmlspecialchars($row['user_name']) ?></td>
                                <?php endif; ?>
                                
                                <td class="service-name"><?= htmlspecialchars($row['service_name']) ?></td>
                                <td class="category-name"><?= htmlspecialchars($row['category_name']) ?></td>
                                <td class="duration"><?= (int)$row['duration'] ?> min</td>
                                <td class="price">€<?= number_format($row['price'], 2) ?></td>
                                <td class="employee-name">
                                    <?php if ($row['employee_id']): ?>
                                        <?= htmlspecialchars($row['employee_name'] ?? 'Auto-assigned') ?>
                                    <?php else: ?>
                                        <span class="no-employee">No specialist needed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="date"><?= formatDate($row['reservation_date']) ?></td>
                                <td class="time"><?= formatTime($row['reservation_time']) ?></td>
                                <td class="start-time"><?= formatDateTime($row['reservation_date'], $row['reservation_time']) ?></td>
                                <td class="end-time"><?= formatDateTime(date('Y-m-d', strtotime($row['end_datetime'])), date('H:i', strtotime($row['end_datetime']))) ?></td>
                                <td class="status"><?= getStatusBadge($row['status']) ?></td>
                                                                 <td class="time-until"><?= $timeUntil ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                                                         <td colspan="<?= ($role !== 'client') ? 13 : 12 ?>" class="no-reservations">
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <h3>No Reservations Found</h3>
                                    <p>You haven't made any reservations yet. Start your wellness journey today!</p>
                                    <a href="add_reservation.php" class="cta-button">
                                        <i class="fas fa-plus"></i> Make Your First Reservation
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Reservations Page Specific Styles */

/* Smooth scrolling for better experience */
html {
    scroll-behavior: smooth;
}

/* Pool Background for the entire page */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        url('images/pool.jpg'),
        linear-gradient(135deg, rgba(15, 76, 58, 0.85) 0%, rgba(26, 95, 74, 0.8) 50%, rgba(45, 122, 95, 0.75) 100%);
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    z-index: -1;
    filter: blur(2px) brightness(0.7) saturate(1.2);
    transform: scale(1.1);
}

/* Additional pool overlay for depth */
body::after {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 20% 80%, rgba(212, 175, 55, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(45, 122, 95, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(26, 95, 74, 0.1) 0%, transparent 50%);
    z-index: -1;
    pointer-events: none;
}

.reservations-hero {
    background: rgba(15, 76, 58, 0.9);
    padding: 4rem 2rem;
    text-align: center;
    margin-bottom: 2rem;
    border-radius: 20px;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(15px);
    border: 1px solid rgba(212, 175, 55, 0.3);
}

.reservations-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at center, rgba(212, 175, 55, 0.15) 0%, transparent 60%),
        radial-gradient(circle at 30% 70%, rgba(45, 122, 95, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 70% 30%, rgba(26, 95, 74, 0.1) 0%, transparent 50%);
    z-index: 1;
}

/* Floating pool elements */
.reservations-hero::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="pool-ripples" x="0" y="0" width="200" height="200" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="%23ffffff" opacity="0.2"/><circle cx="175" cy="75" r="1.5" fill="%23d4af37" opacity="0.15"/><circle cx="75" cy="175" r="0.8" fill="%23ffffff" opacity="0.18"/><path d="M10 50 Q30 30 50 50 Q70 70 90 50" stroke="%23d4af37" stroke-width="0.3" fill="none" opacity="0.15"/><path d="M150 20 Q170 40 150 60" stroke="%23ffffff" stroke-width="0.2" fill="none" opacity="0.12"/></pattern></defs><rect width="100" height="100" fill="url(%23pool-ripples)"/></svg>');
    background-size: 200px 200px;
    opacity: 0.6;
    z-index: 1;
    animation: poolFloat 20s ease-in-out infinite;
}

@keyframes poolFloat {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-10px) rotate(1deg); }
}

.hero-content {
    position: relative;
    z-index: 3;
    background: rgba(15, 76, 58, 0.1);
    padding: 3rem;
    border-radius: 20px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(212, 175, 55, 0.2);
    box-shadow: 
        0 20px 40px rgba(0, 0, 0, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.hero-title {
    font-family: 'Playfair Display', serif;
    font-size: 3rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 1.5rem;
    text-shadow: 
        3px 3px 6px rgba(0,0,0,0.7),
        0 0 20px rgba(212, 175, 55, 0.3),
        0 0 40px rgba(212, 175, 55, 0.1);
    position: relative;
}

.hero-title::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 100px;
    height: 3px;
    background: linear-gradient(90deg, transparent, #d4af37, transparent);
    border-radius: 2px;
}

.hero-subtitle {
    font-size: 1.3rem;
    color: #ffffff;
    font-weight: 400;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.6);
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

.reservations-container {
    max-width: 95vw;
    margin: 0 auto;
    padding: 0 20px;
    position: relative;
    z-index: 2;
}

.reservations-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 2rem;
    background: rgba(15, 76, 58, 0.9);
    border-radius: 20px;
    border: 1px solid rgba(212, 175, 55, 0.3);
    backdrop-filter: blur(15px);
    box-shadow: 
        0 15px 35px rgba(0, 0, 0, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
    position: relative;
}

.reservations-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 20% 50%, rgba(212, 175, 55, 0.05) 0%, transparent 50%),
        radial-gradient(circle at 80% 50%, rgba(45, 122, 95, 0.05) 0%, transparent 50%);
    border-radius: 20px;
    pointer-events: none;
}

.header-actions {
    display: flex;
    gap: 1rem;
}

.new-reservation-btn, .back-home-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.new-reservation-btn {
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: #0f4c3a;
}

.new-reservation-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
}

.back-home-btn {
    background: rgba(255, 255, 255, 0.1);
    color: #f8f9fa;
    border: 1px solid rgba(212, 175, 55, 0.3);
}

.back-home-btn:hover {
    background: rgba(212, 175, 55, 0.2);
    border-color: #d4af37;
}

.success-message {
    background: rgba(39, 174, 96, 0.2);
    border: 1px solid rgba(39, 174, 96, 0.3);
    color: #2ecc71;
    padding: 1.5rem 2rem;
    border-radius: 15px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 25px rgba(39, 174, 96, 0.2);
    animation: successMessageFadeIn 0.6s ease-out;
    position: relative;
    z-index: 2;
}

@keyframes successMessageFadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.reservations-table-container {
    background: rgba(15, 76, 58, 0.9);
    border-radius: 20px;
    padding: 2rem;
    border: 1px solid rgba(212, 175, 55, 0.3);
    overflow-x: hidden;
    width: 100%;
    backdrop-filter: blur(15px);
    box-shadow: 
        0 20px 40px rgba(0, 0, 0, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
    position: relative;
}

.reservations-table-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 30% 70%, rgba(212, 175, 55, 0.03) 0%, transparent 50%),
        radial-gradient(circle at 70% 30%, rgba(45, 122, 95, 0.03) 0%, transparent 50%);
    border-radius: 20px;
    pointer-events: none;
}

.reservations-table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    overflow: hidden;
    table-layout: fixed;
    position: relative;
    z-index: 2;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.reservations-table th {
    background: rgba(212, 175, 55, 0.2);
    color: #0f4c3a;
    font-weight: 600;
    padding: 1rem 0.75rem;
    text-align: center;
    font-size: 0.9rem;
    white-space: nowrap;
}

/* Column width optimizations - using flexible widths */
.reservations-table th:nth-child(1), /* # */
.reservations-table td:nth-child(1) {
    width: 4%;
    min-width: 40px;
}

.reservations-table th:nth-child(2), /* Client */
.reservations-table td:nth-child(2) {
    width: 8%;
    min-width: 80px;
}

.reservations-table th:nth-child(3), /* Service */
.reservations-table td:nth-child(3) {
    width: 12%;
    min-width: 100px;
}

.reservations-table th:nth-child(4), /* Category */
.reservations-table td:nth-child(4) {
    width: 8%;
    min-width: 80px;
}

.reservations-table th:nth-child(5), /* Duration */
.reservations-table td:nth-child(5) {
    width: 6%;
    min-width: 60px;
}

.reservations-table th:nth-child(6), /* Price */
.reservations-table td:nth-child(6) {
    width: 12%;
    min-width: 60px;
}

.reservations-table th:nth-child(7), /* Specialist */
.reservations-table td:nth-child(7) {
    width: 15%;
    min-width: 120px;
}

.reservations-table th:nth-child(8), /* Date */
.reservations-table td:nth-child(8) {
    width: 6%;
    min-width: 70px;
}

.reservations-table th:nth-child(9), /* Time */
.reservations-table td:nth-child(9) {
    width: 8%;
    min-width: 50px;
}

.reservations-table th:nth-child(10), /* Start */
.reservations-table td:nth-child(10) {
    width: 8%;
    min-width: 80px;
}

.reservations-table th:nth-child(11), /* End */
.reservations-table td:nth-child(11) {
    width: 8%;
    min-width: 80px;
}

.reservations-table th:nth-child(12), /* status */
.reservations-table td:nth-child(12) {
    width: 13%;
    min-width: 80px;
}

.reservations-table th:nth-child(13), /* Time Until */
.reservations-table td:nth-child(13) {
    width: 9%;
    min-width: 130px;
}





.reservations-table td {
    padding: 1rem 0.75rem;
    border-top: 1px solid rgba(212, 175, 55, 0.1);
    color: #f8f9fa;
    font-size: 0.9rem;
    overflow: visible;
    text-overflow: clip;
    white-space: normal;
    word-wrap: break-word;
    line-height: 1.3;
    vertical-align: middle;
    text-align: center;
}

/* Style for "No specialist needed" text */
.no-employee {
    color: #6c757d;
    font-style: italic;
    font-size: 0.8rem;
}

/* Keep status badges on one line */
.status-badge {
    white-space: nowrap;
}

/* End time styling */
.end-time {
    color: #e74c3c;
    font-weight: 500;
}

.reservation-row:hover {
    background: rgba(212, 175, 55, 0.05);
}

.reservation-row.completed {
    opacity: 0.7;
    background: rgba(128, 128, 128, 0.1);
}

/* Ensure consistent row heights with wrapped text */
.reservations-table tbody tr {
    min-height: 60px;
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
    min-width: fit-content;
}

.status-awaiting {
    background: rgba(255, 193, 7, 0.2);
    color: #ffc107;
    border: 1px solid rgba(255, 193, 7, 0.3);
}

.status-approved {
    background: rgba(39, 174, 96, 0.2);
    color: #27ae60;
    border: 1px solid rgba(39, 174, 96, 0.3);
}

.status-completed {
    background: rgba(108, 117, 125, 0.2);
    color: #6c757d;
    border: 1px solid rgba(108, 117, 125, 0.3);
}

.status-cancelled {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.3);
}



.no-employee {
    color: #6c757d;
    font-style: italic;
    font-size: 0.8rem;
}

.no-actions {
    color: #6c757d;
    font-style: italic;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: rgba(15, 76, 58, 0.1);
    border-radius: 20px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(212, 175, 55, 0.2);
    position: relative;
    z-index: 2;
}

.empty-state::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 30% 70%, rgba(212, 175, 55, 0.05) 0%, transparent 50%),
        radial-gradient(circle at 70% 30%, rgba(45, 122, 95, 0.05) 0%, transparent 50%);
    border-radius: 20px;
    pointer-events: none;
}

.empty-state i {
    font-size: 4rem;
    color: #d4af37;
    margin-bottom: 1.5rem;
    text-shadow: 0 0 20px rgba(212, 175, 55, 0.3);
    animation: emptyStateIconFloat 3s ease-in-out infinite;
}

@keyframes emptyStateIconFloat {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.empty-state h3 {
    color: #d4af37;
    margin-bottom: 1rem;
    font-size: 1.8rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.empty-state p {
    color: #ffffff;
    opacity: 0.9;
    margin-bottom: 2rem;
    font-size: 1.1rem;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    line-height: 1.6;
}

.cta-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: #0f4c3a;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.cta-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
}

/* Responsive Design */
@media (max-width: 1200px) {
    .reservations-container {
        max-width: 98vw;
        padding: 0 10px;
    }
    
    .reservations-table th,
    .reservations-table td {
        padding: 0.75rem 0.5rem;
        font-size: 0.85rem;
    }
}

@media (max-width: 768px) {
    .reservations-container {
        max-width: 100vw;
        padding: 0 5px;
    }
    
    .reservations-table-container {
        padding: 1rem;
    }
    
    .reservations-table th,
    .reservations-table td {
        padding: 0.5rem 0.25rem;
        font-size: 0.8rem;
    }
}

@media (max-width: 768px) {
    .reservations-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .header-actions {
        justify-content: center;
    }
    
    .hero-title {
        font-size: 2rem;
    }
}

/* Pool-themed floating elements */
.floating-pool-element {
    position: absolute;
    width: 80px;
    height: 80px;
    background: radial-gradient(circle, rgba(212, 175, 55, 0.1), transparent);
    border-radius: 50%;
    animation: poolElementFloat 8s ease-in-out infinite;
    pointer-events: none;
    z-index: 1;
}

.floating-pool-element:nth-child(1) {
    top: 15%;
    left: 8%;
    animation-delay: 0s;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.08), transparent);
}

.floating-pool-element:nth-child(2) {
    top: 65%;
    right: 12%;
    animation-delay: 3s;
    background: radial-gradient(circle, rgba(212, 175, 55, 0.06), transparent);
}

.floating-pool-element:nth-child(3) {
    bottom: 25%;
    left: 15%;
    animation-delay: 6s;
    background: radial-gradient(circle, rgba(45, 122, 95, 0.08), transparent);
}

@keyframes poolElementFloat {
    0%, 100% { 
        transform: translateY(0px) rotate(0deg) scale(1); 
        opacity: 0.6;
    }
    25% { 
        transform: translateY(-15px) rotate(2deg) scale(1.1); 
        opacity: 0.8;
    }
    50% { 
        transform: translateY(-8px) rotate(-1deg) scale(0.9); 
        opacity: 0.7;
    }
    75% { 
        transform: translateY(-12px) rotate(1deg) scale(1.05); 
        opacity: 0.9;
    }
}

/* Enhanced button hover effects */
.new-reservation-btn:hover,
.back-home-btn:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 
        0 12px 25px rgba(212, 175, 55, 0.4),
        0 0 0 4px rgba(212, 175, 55, 0.2);
}

/* Smooth transitions for all elements */
* {
    transition: all 0.3s ease;
}

/* Enhanced table row hover effects */
.reservation-row:hover {
    background: rgba(212, 175, 55, 0.08);
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

/* Pool ripple effect for status badges */
.status-badge {
    position: relative;
    overflow: hidden;
}

.status-badge::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.6s ease, height 0.6s ease;
}

.status-badge:hover::before {
    width: 200%;
    height: 200%;
}

</style>



<?php
if (isset($stmt) && $stmt) {
    $stmt->close();
}
$mysqli->close();
?>
