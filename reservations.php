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
                        <th>Actions</th>
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
                                                   $row['status'] === 'Awaiting' && 
                                                   strtotime($row['reservation_date'] . ' ' . $row['reservation_time']) > time() + 86400);
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
                                <td class="end-time"><?= formatDateTime($row['reservation_date'], $row['reservation_time']) ?></td>
                                <td class="status"><?= getStatusBadge($row['status']) ?></td>
                                <td class="time-until"><?= $timeUntil ?></td>
                                <td class="actions">
                                    <?php if ($canStaffEdit): ?>
                                        <div class="action-buttons">
                                            <a href="reservation_edit.php?id=<?= (int)$row['id'] ?>" class="action-btn edit-btn" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                                                                         <?php if ($row['status'] === 'Awaiting'): ?>
                                                 <a href="reservation_status.php?id=<?= (int)$row['id'] ?>&action=approve" class="action-btn approve-btn" title="Approve">
                                                     <i class="fas fa-check"></i>
                                                 </a>
                                                 <a href="#" onclick="confirmCancel(<?= (int)$row['id'] ?>)" class="action-btn cancel-btn" title="Cancel">
                                                     <i class="fas fa-times"></i>
                                                 </a>
                                             <?php endif; ?>
                                        </div>
                                                                         <?php elseif ($canClientCancel): ?>
                                         <a href="#" onclick="confirmCancel(<?= (int)$row['id'] ?>)" class="action-btn cancel-btn" title="Cancel">
                                             <i class="fas fa-times"></i>
                                         </a>
                                    <?php else: ?>
                                        <span class="no-actions">—</span>
                                    <?php endif; ?>
                                </td>
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
.reservations-hero {
    background: linear-gradient(135deg, rgba(15, 76, 58, 0.9) 0%, rgba(26, 95, 74, 0.9) 100%);
    padding: 3rem 2rem;
    text-align: center;
    margin-bottom: 2rem;
    border-radius: 20px;
    position: relative;
    overflow: hidden;
}

.reservations-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="hero-pattern" x="0" y="0" width="50" height="50" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="%23d4af37" opacity="0.1"/><path d="M10 20 Q25 10 40 20" stroke="%23d4af37" stroke-width="0.3" fill="none" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23hero-pattern)"/></svg>');
    background-size: 50px 50px;
    opacity: 0.3;
    z-index: 1;
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-title {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    font-weight: 600;
    color: #d4af37;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.hero-subtitle {
    font-size: 1.1rem;
    color: #f8f9fa;
    opacity: 0.9;
}

.reservations-container {
    max-width: 95vw;
    margin: 0 auto;
    padding: 0 20px;
}

.reservations-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 15px;
    border: 1px solid rgba(212, 175, 55, 0.2);
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
    padding: 1rem 1.5rem;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.reservations-table-container {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 15px;
    padding: 1.5rem;
    border: 1px solid rgba(212, 175, 55, 0.2);
    overflow-x: hidden;
    width: 100%;
}

.reservations-table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    overflow: hidden;
    table-layout: fixed;
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

.reservations-table th:nth-child(11), /* status */
.reservations-table td:nth-child(11) {
    width: 13%;
    min-width: 80px;
}

.reservations-table th:nth-child(12), /* Time Until */
.reservations-table td:nth-child(12) {
    width: 9%;
    min-width: 130px;
}

.reservations-table th:nth-child(13), /*  Actions */
.reservations-table td:nth-child(13) {
    width: 9%;
    min-width: 70px;
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

.action-buttons {
    display: flex;
    gap: 0.5rem;
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
}

.edit-btn {
    background: rgba(0, 123, 255, 0.8);
}

.edit-btn:hover {
    background: rgba(0, 123, 255, 1);
    transform: scale(1.1);
}

.approve-btn {
    background: rgba(39, 174, 96, 0.8);
}

.approve-btn:hover {
    background: rgba(39, 174, 96, 1);
    transform: scale(1.1);
}

.cancel-btn {
    background: rgba(220, 53, 69, 0.8);
}

.cancel-btn:hover {
    background: rgba(220, 53, 69, 1);
    transform: scale(1.1);
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
    margin-bottom: 1.5rem;
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

/* Confirmation Dialog Styles */
.confirmation-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.confirmation-dialog {
    background: linear-gradient(135deg, #0f4c3a, #1a5f4a);
    border: 2px solid #d4af37;
    border-radius: 20px;
    padding: 2rem;
    text-align: center;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
}

.confirmation-dialog h3 {
    color: #d4af37;
    margin-bottom: 1rem;
    font-size: 1.5rem;
}

.confirmation-dialog p {
    color: #f8f9fa;
    margin-bottom: 2rem;
    font-size: 1.1rem;
}

.confirmation-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.confirm-btn, .cancel-dialog-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 25px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.confirm-btn {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
}

.confirm-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
}

.cancel-dialog-btn {
    background: rgba(255, 255, 255, 0.1);
    color: #f8f9fa;
    border: 1px solid rgba(212, 175, 55, 0.3);
}

.cancel-dialog-btn:hover {
    background: rgba(212, 175, 55, 0.2);
    border-color: #d4af37;
}
</style>

<!-- Confirmation Dialog -->
<div id="confirmationOverlay" class="confirmation-overlay">
    <div class="confirmation-dialog">
        <h3><i class="fas fa-exclamation-triangle"></i> Confirm Cancellation</h3>
        <p>Are you sure you want to cancel this reservation? This action cannot be undone.</p>
        <div class="confirmation-buttons">
            <button id="confirmCancelBtn" class="confirm-btn">
                <i class="fas fa-times"></i> Yes, Cancel Reservation
            </button>
            <button onclick="hideConfirmation()" class="cancel-dialog-btn">
                <i class="fas fa-arrow-left"></i> No, Keep Reservation
            </button>
        </div>
    </div>
</div>

<script>
let currentReservationId = null;

function confirmCancel(reservationId) {
    currentReservationId = reservationId;
    document.getElementById('confirmationOverlay').style.display = 'flex';
}

function hideConfirmation() {
    document.getElementById('confirmationOverlay').style.display = 'none';
    currentReservationId = null;
}

document.getElementById('confirmCancelBtn').addEventListener('click', function() {
    if (currentReservationId) {
        // Show loading state
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
        this.disabled = true;
        
        // Make AJAX request to cancel the reservation
        fetch('reservation_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + currentReservationId + '&action=cancel'
        })
        .then(response => response.text())
        .then(data => {
            // Hide confirmation dialog
            hideConfirmation();
            
            // Show success message and reload page
            if (data.includes('success') || data.includes('cancelled')) {
                alert('Reservation cancelled successfully!');
                location.reload();
            } else {
                alert('Error cancelling reservation. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error cancelling reservation. Please try again.');
            hideConfirmation();
        });
    }
});

// Close dialog when clicking outside
document.getElementById('confirmationOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        hideConfirmation();
    }
});
</script>

<?php
if (isset($stmt) && $stmt) {
    $stmt->close();
}
$mysqli->close();
?>
