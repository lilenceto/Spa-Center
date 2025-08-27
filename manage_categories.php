<?php
// manage_categories.php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?next=manage_categories.php");
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
    header("Location: login.php?next=manage_categories.php");
    exit();
}
$adminCheckStmt->close();

include 'header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            
                
            case 'add_service':
                $category_id = (int)$_POST['category_id'];
                $name = trim($_POST['service_name']);
                $description = trim($_POST['service_description']);
                $duration = (int)$_POST['service_duration'];
                $price = (float)$_POST['service_price'];
                
                                 if (!empty($name) && $category_id > 0 && $duration > 0 && $price > 0) {
                     $stmt = $mysqli->prepare("INSERT INTO services (category_id, name, description, duration, price) VALUES (?, ?, ?, ?, ?)");
                     $stmt->bind_param("issid", $category_id, $name, $description, $duration, $price);
                     if ($stmt->execute()) {
                         $message = "Procedure '$name' added successfully!";
                         $messageType = "success";
                     } else {
                         $message = "Error adding procedure: " . $mysqli->error;
                         $messageType = "error";
                     }
                     $stmt->close();
                 }
                break;
                
                         case 'edit_service':
                 $service_id = (int)$_POST['service_id'];
                 $name = trim($_POST['service_name']);
                 $description = trim($_POST['service_description']);
                 $duration = (int)$_POST['service_duration'];
                 $price = (float)$_POST['service_price'];
                 
                 if (!empty($name) && $service_id > 0 && $duration > 0 && $price > 0) {
                     $stmt = $mysqli->prepare("UPDATE services SET name = ?, description = ?, duration = ?, price = ? WHERE id = ?");
                     $stmt->bind_param("ssidi", $name, $description, $duration, $price, $service_id);
                     if ($stmt->execute()) {
                         $message = "Procedure '$name' updated successfully!";
                         $messageType = "success";
                     } else {
                         $message = "Error updating procedure: " . $mysqli->error;
                         $messageType = "error";
                     }
                     $stmt->close();
                 } else {
                     $message = "Please fill in all required fields correctly.";
                     $messageType = "error";
                 }
                 break;
                
                         case 'delete_service':
                 $service_id = (int)$_POST['service_id'];
                 if ($service_id > 0) {
                     // Check if service has reservations
                     $checkStmt = $mysqli->prepare("SELECT COUNT(*) as reservation_count FROM reservations WHERE service_id = ?");
                     $checkStmt->bind_param("i", $service_id);
                     $checkStmt->execute();
                     $checkResult = $checkStmt->get_result();
                     $reservationCount = $checkResult->fetch_assoc()['reservation_count'];
                     $checkStmt->close();
                     
                     if ($reservationCount > 0) {
                         $message = "Cannot delete procedure: It has $reservationCount reservation(s). Consider deactivating instead.";
                         $messageType = "error";
                     } else {
                         $stmt = $mysqli->prepare("DELETE FROM services WHERE id = ?");
                         $stmt->bind_param("i", $service_id);
                         if ($stmt->execute()) {
                             $message = "Procedure deleted successfully!";
                             $messageType = "success";
                         } else {
                             $message = "Error deleting procedure: " . $mysqli->error;
                             $messageType = "error";
                         }
                         $stmt->close();
                     }
                 }
                 break;
                 
             case 'change_service_category':
                 $service_id = (int)$_POST['service_id'];
                 $new_category_id = (int)$_POST['new_category_id'];
                 
                 if ($service_id > 0 && $new_category_id > 0) {
                     $stmt = $mysqli->prepare("UPDATE services SET category_id = ? WHERE id = ?");
                     $stmt->bind_param("ii", $new_category_id, $service_id);
                     if ($stmt->execute()) {
                         $message = "Procedure moved to new category successfully!";
                         $messageType = "success";
                     } else {
                         $message = "Error moving procedure to new category: " . $mysqli->error;
                         $messageType = "error";
                     }
                     $stmt->close();
                 }
                 break;
        }
    }
}

// Get all categories with service counts
    $categoriesQuery = "
        SELECT
            c.*,
            COUNT(s.id) as service_count
        FROM service_categories c
        LEFT JOIN services s ON c.id = s.category_id
        GROUP BY c.id
        ORDER BY c.name
    ";
$categoriesResult = $mysqli->query($categoriesQuery);

// Get all services for editing
$servicesQuery = "SELECT * FROM services ORDER BY category_id, name";
$servicesResult = $mysqli->query($servicesQuery);
$services = [];
while ($service = $servicesResult->fetch_assoc()) {
    $services[] = $service;
}
?>

<div class="page-container">
    <div class="admin-hero">
        <div class="hero-content">
                         <h1 class="hero-title">
                 <i class="fas fa-concierge-bell"></i>
                 Procedure Management
             </h1>
             <p class="hero-subtitle">Manage spa procedures within existing categories. All changes affect the public website immediately.</p>
            <div class="hero-actions">
                <a href="admin_panel.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Admin Panel
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($message)): ?>
        <div class="message <?= $messageType ?>">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    

    <!-- Add New Procedure -->
    <div class="management-section">
        <div class="section-header">
            <h2><i class="fas fa-plus-circle"></i> Add New Procedure</h2>
        </div>
        <form method="POST" class="management-form">
            <input type="hidden" name="action" value="add_service">
            <div class="form-row">
                <div class="form-group">
                    <label for="service_category">Category *</label>
                    <select id="service_category" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php 
                        $categoriesResult->data_seek(0);
                        while ($category = $categoriesResult->fetch_assoc()): 
                        ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="service_name">Procedure Name *</label>
                    <input type="text" id="service_name" name="service_name" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="service_duration">Duration (minutes) *</label>
                    <input type="number" id="service_duration" name="service_duration" min="15" step="15" required>
                </div>
                <div class="form-group">
                    <label for="service_price">Price (€) *</label>
                    <input type="number" id="service_price" name="service_price" min="0" step="0.01" required>
                </div>
            </div>
            <div class="form-group">
                <label for="service_description">Description</label>
                <textarea id="service_description" name="service_description" rows="3" placeholder="Describe what this procedure includes..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Procedure
            </button>
        </form>
    </div>

         <!-- Procedures List -->
     <div class="management-section">
         <div class="section-header">
             <h2><i class="fas fa-list"></i> Manage Procedures</h2>
         </div>
        
        <?php if (!empty($services)): ?>
            <div class="services-table-container">
                <table class="admin-table">
                                         <thead>
                         <tr>
                             <th>Procedure Name</th>
                             <th>Category</th>
                             <th>Duration</th>
                             <th>Price</th>
                             <th>Description</th>
                             <th>Actions</th>
                         </tr>
                     </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                                             <td><strong><?= htmlspecialchars($service['name']) ?></strong></td>
                             <td>
                                 <?php 
                                 $categoriesResult->data_seek(0);
                                 while ($category = $categoriesResult->fetch_assoc()) {
                                     if ($category['id'] == $service['category_id']) {
                                         echo htmlspecialchars($category['name']);
                                         break;
                                     }
                                 }
                                 ?>
                             </td>
                             <td><?= $service['duration'] ?> min</td>
                             <td>€<?= number_format($service['price'], 2) ?></td>
                             <td><?= htmlspecialchars(substr($service['description'], 0, 100)) ?><?= strlen($service['description']) > 100 ? '...' : '' ?></td>
                             <td>
                                 <div class="action-buttons">
                                     <button onclick="editService(<?= $service['id'] ?>)" class="btn btn-edit" title="Edit Procedure">
                                         <i class="fas fa-edit"></i>
                                     </button>
                                     <button onclick="deleteService(<?= $service['id'] ?>, '<?= htmlspecialchars($service['name']) ?>')" class="btn btn-delete" title="Delete Procedure">
                                         <i class="fas fa-trash"></i>
                                     </button>
                                     <button onclick="editServiceCategory(<?= $service['id'] ?>)" class="btn btn-exchange" title="Change Category">
                                         <i class="fas fa-exchange-alt"></i>
                                     </button>
                                 </div>
                             </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
                 <?php else: ?>
             <div class="empty-state">
                 <i class="fas fa-concierge-bell"></i>
                 <h3>No Procedures Found</h3>
                 <p>Add procedures to your categories to get started.</p>
             </div>
         <?php endif; ?>
    </div>
</div>



 <!-- Edit Procedure Modal -->
 <div id="editServiceModal" class="modal">
     <div class="modal-content">
         <div class="modal-header">
             <h3>Edit Procedure</h3>
             <span class="close">&times;</span>
         </div>
         <form method="POST" class="management-form">
             <input type="hidden" name="action" value="edit_service">
             <input type="hidden" name="service_id" id="edit_service_id">
             <div class="form-group">
                 <label for="edit_service_name">Procedure Name *</label>
                 <input type="text" id="edit_service_name" name="service_name" required>
             </div>
             <div class="form-row">
                 <div class="form-group">
                     <label for="edit_service_duration">Duration (minutes) *</label>
                     <input type="number" id="edit_service_duration" name="service_duration" min="15" step="15" required>
                 </div>
                 <div class="form-group">
                     <label for="edit_service_price">Price (€) *</label>
                     <input type="number" id="edit_service_price" name="service_price" min="0" step="0.01" required>
                 </div>
             </div>
             <div class="form-group">
                 <label for="edit_service_description">Description</label>
                 <textarea id="edit_service_description" name="service_description" rows="3"></textarea>
             </div>
             <div class="modal-actions">
                 <button type="button" class="btn btn-secondary" onclick="closeModal('editServiceModal')">Cancel</button>
                 <button type="submit" class="btn btn-primary">Update Procedure</button>
             </div>
         </form>
     </div>
 </div>

 <!-- Change Procedure Category Modal -->
 <div id="changeCategoryModal" class="modal">
     <div class="modal-content">
         <div class="modal-header">
             <h3>Change Procedure Category</h3>
             <span class="close">&times;</span>
         </div>
         <form method="POST" class="management-form">
             <input type="hidden" name="action" value="change_service_category">
             <input type="hidden" name="service_id" id="change_category_service_id">
             <div class="form-group">
                 <label for="change_category_select">New Category *</label>
                 <select id="change_category_select" name="new_category_id" required>
                     <option value="">Select New Category</option>
                     <?php 
                     $categoriesResult->data_seek(0);
                     while ($category = $categoriesResult->fetch_assoc()): 
                     ?>
                         <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                     <?php endwhile; ?>
                 </select>
             </div>
             <div class="modal-actions">
                 <button type="button" class="btn btn-secondary" onclick="closeModal('changeCategoryModal')">Cancel</button>
                 <button type="submit" class="btn btn-primary">Change Category</button>
             </div>
         </form>
     </div>
 </div>

 <script>
 // Modal functionality
 function openModal(modalId) {
     document.getElementById(modalId).style.display = 'block';
 }

 function closeModal(modalId) {
     document.getElementById(modalId).style.display = 'none';
 }

 // Add form submission handler for edit form
 document.addEventListener('DOMContentLoaded', function() {
     const editForm = document.querySelector('#editServiceModal form');
     if (editForm) {
         editForm.addEventListener('submit', function(e) {
             console.log('Edit form submitted');
             
             // Log form data for debugging
             const formData = new FormData(this);
             for (let [key, value] of formData.entries()) {
                 console.log(`${key}: ${value}`);
             }
             
             // Show loading state
             const submitBtn = this.querySelector('button[type="submit"]');
             const originalText = submitBtn.innerHTML;
             submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
             submitBtn.disabled = true;
             
             // Form will submit normally, but we'll handle the response
             setTimeout(() => {
                 // Re-enable button after a short delay
                 submitBtn.innerHTML = originalText;
                 submitBtn.disabled = false;
             }, 2000);
         });
     }
 });

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Close modal when clicking X
document.querySelectorAll('.close').forEach(function(closeBtn) {
    closeBtn.onclick = function() {
        this.closest('.modal').style.display = 'none';
    }
});

 // Edit procedure
 function editService(serviceId) {
     console.log('Editing service with ID:', serviceId);
     // Fetch the procedure data and populate the modal
     fetch(`get_service.php?id=${serviceId}`)
         .then(response => response.json())
         .then(data => {
             console.log('Service data received:', data);
             if (data.success) {
                 const service = data.service;
                 document.getElementById('edit_service_id').value = service.id;
                 document.getElementById('edit_service_name').value = service.name;
                 document.getElementById('edit_service_description').value = service.description || '';
                 document.getElementById('edit_service_duration').value = service.duration;
                 document.getElementById('edit_service_price').value = service.price;
                 console.log('Modal populated with service data');
                 openModal('editServiceModal');
             } else {
                 console.error('Error loading service:', data.message);
                 alert('Error loading procedure data: ' + data.message);
             }
         })
         .catch(error => {
             console.error('Fetch error:', error);
             alert('Network error occurred while loading procedure data');
         });
 }

 // Change procedure category
 function editServiceCategory(serviceId) {
     document.getElementById('change_category_service_id').value = serviceId;
     openModal('changeCategoryModal');
 }

 // Delete procedure
 function deleteService(serviceId, serviceName) {
     if (confirm(`Are you sure you want to delete the procedure "${serviceName}"? This action cannot be undone.`)) {
         const form = document.createElement('form');
         form.method = 'POST';
         form.innerHTML = `
             <input type="hidden" name="action" value="delete_service">
             <input type="hidden" name="service_id" value="${serviceId}">
         `;
         document.body.appendChild(form);
         form.submit();
     }
 }
</script>

<style>
/* Category Management Styles */
.management-section {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(212, 175, 55, 0.2);
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    backdrop-filter: blur(10px);
}

.section-header {
    margin-bottom: 1.5rem;
    border-bottom: 1px solid rgba(212, 175, 55, 0.3);
    padding-bottom: 1rem;
}

.section-header h2 {
    color: #d4af37;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.management-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    color: #d4af37;
    font-weight: 600;
    font-size: 0.9rem;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 0.75rem;
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.1);
    color: #f8f9fa;
    font-size: 0.9rem;
}

/* Make dropdown text more visible */
.form-group select {
    background: rgba(255, 255, 255, 0.9);
    color: #000000;
}

.form-group select option {
    background: #ffffff;
    color: #000000;
    padding: 0.5rem;
}

.form-group select:focus {
    background: #ffffff;
    color: #000000;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #d4af37;
    box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.2);
}

.form-group select:focus {
    outline: none;
    border-color: #d4af37;
    box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.2);
    background: #ffffff;
    color: #000000;
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

/* Categories Grid */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
}

.category-card {
    background: rgba(15, 76, 58, 0.8);
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
}

.category-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: rgba(212, 175, 55, 0.1);
    border-bottom: 1px solid rgba(212, 175, 55, 0.2);
}

.category-icon {
    width: 50px;
    height: 50px;
    background: rgba(212, 175, 55, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #d4af37;
}

.category-info {
    flex: 1;
}

.category-info h3 {
    color: #f8f9fa;
    margin: 0 0 0.25rem 0;
    font-size: 1.2rem;
}

.service-count {
    color: #d4af37;
    font-size: 0.9rem;
    margin: 0;
}

.category-actions {
    display: flex;
    gap: 0.5rem;
}

.category-body {
    padding: 1.5rem;
}

.category-description {
    color: #f8f9fa;
    margin: 0 0 1rem 0;
    line-height: 1.5;
}

.category-meta {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #d4af37;
    font-size: 0.9rem;
}

.color-preview {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.meta-item a {
    color: #d4af37;
    text-decoration: none;
}

.meta-item a:hover {
    text-decoration: underline;
}

/* Services Table */
.services-table-container {
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    overflow: hidden;
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

.admin-table tr:hover {
    background: rgba(212, 175, 55, 0.05);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.btn-primary {
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: #0f4c3a;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
}

.btn-edit {
    background: rgba(0, 123, 255, 0.8);
    color: white;
    padding: 0.5rem;
    border-radius: 50%;
    width: 35px;
    height: 35px;
    justify-content: center;
}

.btn-delete {
    background: rgba(220, 53, 69, 0.8);
    color: white;
    padding: 0.5rem;
    border-radius: 50%;
    width: 35px;
    height: 35px;
    justify-content: center;
}

.btn-edit:hover,
.btn-delete:hover {
    transform: scale(1.1);
}

 .btn-secondary {
     background: rgba(255, 255, 255, 0.1);
     color: #f8f9fa;
     border: 1px solid rgba(212, 175, 55, 0.3);
 }

 .btn-secondary:hover {
     background: rgba(255, 255, 255, 0.2);
 }

 .btn-exchange {
     background: rgba(255, 193, 7, 0.8);
     color: #0f4c3a;
     padding: 0.5rem;
     border-radius: 50%;
     width: 35px;
     height: 35px;
     justify-content: center;
 }

 .btn-exchange:hover {
     transform: scale(1.1);
     background: rgba(255, 193, 7, 1);
 }

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}

.modal-content {
    background: rgba(15, 76, 58, 0.95);
    margin: 5% auto;
    padding: 0;
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 15px;
    width: 90%;
    max-width: 600px;
    backdrop-filter: blur(15px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid rgba(212, 175, 55, 0.3);
}

.modal-header h3 {
    color: #d4af37;
    margin: 0;
}

.close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s ease;
}

.close:hover {
    color: #d4af37;
}

.modal-content .management-form {
    padding: 1.5rem;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(212, 175, 55, 0.2);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 15px;
    border: 1px solid rgba(255, 255, 255, 0.1);
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
@media (max-width: 768px) {
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        width: 95%;
        margin: 10% auto;
    }
    
    .modal-actions {
        flex-direction: column;
    }
}
</style>

<?php
$mysqli->close();
?>
