<?php
session_start();
require_once "db.php";

$preselectedServiceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

// Взимаме всички услуги + категории (за fallback, ако няма избрана услуга)
$services = $mysqli->query("
    SELECT s.id, s.name, s.description, s.duration, s.price, c.name AS category_name, c.id AS category_id
    FROM services s
    JOIN service_categories c ON c.id = s.category_id
    ORDER BY c.name, s.name
")->fetch_all(MYSQLI_ASSOC);

$selectedService = null;
if ($preselectedServiceId > 0) {
    $stmt = $mysqli->prepare("SELECT s.id, s.name, s.description, s.duration, s.price, c.name AS category_name, c.id AS category_id FROM services s JOIN service_categories c ON c.id = s.category_id WHERE s.id = ?");
    $stmt->bind_param("i", $preselectedServiceId);
    $stmt->execute();
    $selectedService = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

include "header.php";
?>

<!-- Main Content -->
<div class="page-container">
    <div class="reservation-hero">
        <div class="hero-content">
            <h1 class="hero-title">
                <i class="fas fa-calendar-plus"></i>
                Book Your Wellness Experience
            </h1>
            <p class="hero-subtitle">Choose your perfect time and let us take care of the rest</p>
        </div>
    </div>

    <div class="reservation-container">
        <div class="reservation-card">
            <div class="card-header">
                <h2><i class="fas fa-spa"></i> New Reservation</h2>
                <p class="card-subtitle">Complete your booking in just a few steps</p>
            </div>

            <form method="POST" action="save_reservation.php" class="reservation-form">
                <!-- Service Selection -->
                <?php if ($selectedService): ?>
                    <input type="hidden" name="service_id" id="serviceIdHidden" value="<?= (int)$selectedService['id'] ?>">
                    <div class="form-section">
                        <div class="section-header">
                            <i class="fas fa-star"></i>
                            <h3>Selected Service</h3>
                        </div>
                        <div class="service-display">
                            <div class="service-info">
                                <h4 class="service-name"><?= htmlspecialchars($selectedService['category_name'] . " - " . $selectedService['name']) ?></h4>
                                <p class="service-description"><?= htmlspecialchars($selectedService['description'] ?? '') ?></p>
                            </div>
                            <div class="service-details">
                                <span class="detail-badge duration">
                                    <i class="fas fa-clock"></i>
                                    <?= (int)$selectedService['duration'] ?> min
                                </span>
                                <span class="detail-badge price">
                                    <i class="fas fa-euro-sign"></i>
                                    €<?= number_format((float)$selectedService['price'], 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="form-section">
                        <div class="section-header">
                            <i class="fas fa-list"></i>
                            <h3>Choose Your Service</h3>
                        </div>
                        <div class="form-group">
                            <label for="serviceSelect" class="form-label">Service Category & Type</label>
                            <select name="service_id" id="serviceSelect" class="form-select" required>
                                <option value="">-- Select your preferred service --</option>
                                <?php foreach ($services as $s): ?>
                                    <option value="<?= $s['id'] ?>" data-category="<?= $s['category_id'] ?>">
                                        <?= htmlspecialchars($s['category_name'] . " - " . $s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Hidden employee field for SPA services -->
                <input type="hidden" name="employee_id" id="employeeIdHidden" value="">

                <!-- Employee Selection -->
                <div class="form-section" id="employeeSelectDiv" style="display:none;">
                    <div class="section-header">
                        <i class="fas fa-user-tie"></i>
                        <h3>Choose Your Specialist</h3>
                    </div>
                    <div class="form-group">
                        <label for="employeeSelect" class="form-label">Available Specialists</label>
                        <select name="employee_id" id="employeeSelect" class="form-select">
                            <option value="">-- Select your preferred specialist --</option>
                        </select>
                    </div>
                </div>

                <!-- SPA Services Info -->
                <div class="form-section" id="spaInfoDiv" style="display:none;">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-magic"></i>
                        </div>
                        <div class="info-content">
                            <h4>Automatic Specialist Assignment</h4>
                            <p>Our experienced specialists will be automatically assigned based on availability. Sit back and relax while we handle the details.</p>
                        </div>
                    </div>
                </div>

                <!-- Date Selection -->
                <div class="form-section" id="dateSelectDiv" style="display:none;">
                    <div class="section-header">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>Select Your Preferred Date</h3>
                    </div>
                    <div class="form-group">
                        <label for="dateSelect" class="form-label">Available Dates</label>
                        <select name="reservation_date" id="dateSelect" class="form-select" required>
                            <option value="">-- Choose your preferred date --</option>
                        </select>
                    </div>
                </div>

                <!-- Time Selection -->
                <div class="form-section" id="timeSelectDiv" style="display:none;">
                    <div class="section-header">
                        <i class="fas fa-clock"></i>
                        <h3>Choose Your Time</h3>
                    </div>
                    <div class="form-group">
                        <label for="timeSelect" class="form-label">Available Time Slots</label>
                        <div class="time-selection-container">
                            <select name="reservation_time" id="timeSelect" class="form-select" required>
                                <option value="">-- Select your preferred time --</option>
                            </select>
                            <button type="button" id="refreshBtn" class="refresh-button" title="Refresh available slots">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <div class="time-info">
                            <div class="info-item">
                                <i class="fas fa-info-circle"></i>
                                <span id="lastUpdate">Last updated: Never</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-sync"></i>
                                <span id="autoRefreshStatus">Auto-refresh: ON</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-list-ul"></i>
                                <span id="slotsCount">Available slots: 0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="form-section submit-section">
                    <button type="submit" class="submit-button">
                        <i class="fas fa-check-circle"></i>
                        Confirm Reservation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Reservation Page Specific Styles */
.reservation-hero {
    background: linear-gradient(135deg, rgba(15, 76, 58, 0.9) 0%, rgba(26, 95, 74, 0.9) 100%);
    padding: 4rem 2rem;
    text-align: center;
    margin-bottom: 3rem;
    border-radius: 20px;
    position: relative;
    overflow: hidden;
}

.reservation-hero::before {
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
    font-size: 3rem;
    font-weight: 600;
    color: #d4af37;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.hero-subtitle {
    font-size: 1.2rem;
    color: #f8f9fa;
    opacity: 0.9;
}

.reservation-container {
    max-width: 800px;
    margin: 0 auto;
}

.reservation-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    border: 1px solid rgba(212, 175, 55, 0.2);
}

.card-header {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid rgba(212, 175, 55, 0.3);
}

.card-header h2 {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    color: #d4af37;
    margin-bottom: 0.5rem;
}

.card-subtitle {
    color: #f8f9fa;
    font-size: 1.1rem;
    opacity: 0.8;
}

.form-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 15px;
    border: 1px solid rgba(212, 175, 55, 0.1);
}

.section-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.section-header i {
    color: #d4af37;
    font-size: 1.5rem;
}

.section-header h3 {
    color: #d4af37;
    font-size: 1.3rem;
    font-weight: 600;
}

.service-display {
    background: rgba(212, 175, 55, 0.1);
    border-radius: 15px;
    padding: 1.5rem;
    border: 1px solid rgba(212, 175, 55, 0.2);
}

.service-name {
    font-size: 1.4rem;
    color: #f8f9fa;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.service-description {
    color: #f8f9fa;
    opacity: 0.8;
    margin-bottom: 1rem;
    line-height: 1.6;
}

.service-details {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.detail-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(212, 175, 55, 0.2);
    border: 1px solid rgba(212, 175, 55, 0.3);
    border-radius: 25px;
    color: #d4af37;
    font-weight: 600;
    font-size: 0.9rem;
}

.info-card {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    background: rgba(26, 95, 74, 0.2);
    border: 1px solid rgba(26, 95, 74, 0.3);
    border-radius: 15px;
    padding: 1.5rem;
}

.info-icon {
    color: #2ecc71;
    font-size: 1.5rem;
    margin-top: 0.2rem;
}

.info-content h4 {
    color: #2ecc71;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.info-content p {
    color: #f8f9fa;
    opacity: 0.9;
    line-height: 1.6;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.8rem;
    color: #d4af37;
    font-weight: 600;
    font-size: 1rem;
}

.form-select {
    width: 100%;
    padding: 1rem 1.5rem;
    border: 2px solid rgba(212, 175, 55, 0.3);
    border-radius: 15px;
    background: rgba(255, 255, 255, 0.1);
    color: #f8f9fa;
    font-size: 1rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.form-select:focus {
    outline: none;
    border-color: #d4af37;
    box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.1);
    background: rgba(255, 255, 255, 0.15);
}

.form-select option {
    background: #0f4c3a;
    color: #f8f9fa;
    padding: 0.5rem;
}

.time-selection-container {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.time-selection-container .form-select {
    flex: 1;
}

.refresh-button {
    padding: 1rem;
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: #0f4c3a;
    border: none;
    border-radius: 15px;
    cursor: pointer;
    font-size: 1.2rem;
    transition: all 0.3s ease;
    min-width: 60px;
}

.refresh-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
}

.time-info {
    display: flex;
    gap: 2rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #f8f9fa;
    opacity: 0.8;
    font-size: 0.9rem;
}

.info-item i {
    color: #d4af37;
}

.submit-section {
    text-align: center;
    border: none;
    background: none;
    padding: 1rem;
}

.submit-button {
    background: linear-gradient(135deg, #d4af37, #b8941f);
    color: #0f4c3a;
    border: none;
    padding: 1.2rem 3rem;
    border-radius: 50px;
    font-size: 1.2rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 8px 25px rgba(212, 175, 55, 0.3);
}

.submit-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(212, 175, 55, 0.4);
}

.submit-button:active {
    transform: translateY(-1px);
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .reservation-card {
        padding: 1.5rem;
        margin: 0 1rem;
    }
    
    .time-info {
        flex-direction: column;
        gap: 1rem;
    }
    
    .service-details {
        flex-direction: column;
    }
    
    .time-selection-container {
        flex-direction: column;
    }
    
    .refresh-button {
        width: 100%;
        min-width: auto;
    }
}

/* Loading States */
.form-select:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Animation for form sections */
.form-section {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Success state for form elements */
.form-select:valid {
    border-color: #27ae60;
}

/* Error state for form elements */
.form-select:invalid:not(:placeholder-shown) {
    border-color: #e74c3c;
}
</style>

<script>
(function() {
    const employeeDiv = document.getElementById("employeeSelectDiv");
    const employeeSelect = document.getElementById("employeeSelect");
    const dateDiv = document.getElementById("dateSelectDiv");
    const dateSelect = document.getElementById("dateSelect");
    const timeDiv = document.getElementById("timeSelectDiv");
    const timeSelect = document.getElementById("timeSelect");
    const refreshBtn = document.getElementById("refreshBtn");
    const lastUpdate = document.getElementById("lastUpdate");
    const autoRefreshStatus = document.getElementById("autoRefreshStatus");
    const slotsCount = document.getElementById("slotsCount");
    
    let autoRefreshInterval = null;
    let currentServiceId = null;
    let currentEmployeeId = null;
    let currentDate = null;

    function updateLastUpdate() {
        const now = new Date();
        lastUpdate.textContent = `Last updated: ${now.toLocaleTimeString()}`;
    }

    function updateSlotsCount(count) {
        slotsCount.textContent = `Available slots: ${count}`;
    }

    function startAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
        }
        
        // Auto-refresh every 30 seconds if we have service and date (employee is optional for SPA)
        if (currentServiceId && currentDate) {
            autoRefreshInterval = setInterval(() => {
                if (currentServiceId && currentDate) {
                    loadTimeSlots(currentServiceId, currentEmployeeId, currentDate, true);
                }
            }, 30000); // 30 seconds
            
            autoRefreshStatus.textContent = "Auto-refresh: ON";
            autoRefreshStatus.className = "text-success";
        }
    }

    function stopAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
        autoRefreshStatus.textContent = "Auto-refresh: OFF";
        autoRefreshStatus.className = "text-muted";
    }

    function loadEmployeesForService(serviceId) {
        currentServiceId = serviceId;
        currentEmployeeId = null;
        currentDate = null;
        
        if (!serviceId) {
            employeeDiv.style.display = "none";
            document.getElementById("spaInfoDiv").style.display = "none";
            dateDiv.style.display = "none";
            dateSelect.innerHTML = "";
            timeDiv.style.display = "none";
            timeSelect.innerHTML = "";
            updateSlotsCount(0); // Reset slots count
            stopAutoRefresh();
            return;
        }
        
        // Check if this is a SPA service (category 4)
        let isSPAService = false;
        const serviceSelect = document.getElementById("serviceSelect");
        if (serviceSelect) {
            const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
            isSPAService = selectedOption && selectedOption.dataset.category === "4";
        } else {
            // For preselected services, check the PHP variable
            const preselectedService = <?= $selectedService ? json_encode($selectedService) : 'null' ?>;
            isSPAService = preselectedService && preselectedService.category_id == 4;
        }
        
        if (isSPAService) {
            // For SPA services, hide employee selection and show info
            employeeDiv.style.display = "none";
            document.getElementById("spaInfoDiv").style.display = "block";
            clearHiddenEmployeeField(); // Clear hidden field for SPA
            
            // Load available dates directly (we'll handle employee assignment in backend)
            loadAvailableDatesForSPA(serviceId);
        } else {
            // For other services, show employee selection
            document.getElementById("spaInfoDiv").style.display = "none";
            
            fetch("get_employees.php?service_id=" + serviceId)
                .then(r => r.json())
                .then(data => {
                    employeeSelect.innerHTML = "";
                    if (data.length > 0) {
                        data.forEach(emp => {
                            const opt = document.createElement("option");
                            opt.value = emp.id;
                            opt.textContent = emp.name;
                            employeeSelect.appendChild(opt);
                        });
                        employeeDiv.style.display = "block";
                        
                        // Load available dates for the first employee
                        if (data.length > 0) {
                            loadAvailableDates(serviceId, data[0].id);
                        }
                    } else {
                        employeeDiv.style.display = "none";
                        dateDiv.style.display = "none";
                        timeDiv.style.display = "none";
                        updateSlotsCount(0); // Reset slots count
                        stopAutoRefresh();
                    }
                });
        }
    }

    function loadAvailableDatesForSPA(serviceId) {
        currentServiceId = serviceId; // Set the current service ID
        currentEmployeeId = null; // No employee selection for SPA
        currentDate = null;
        
        if (!serviceId) {
            dateDiv.style.display = "none";
            dateSelect.innerHTML = "";
            timeDiv.style.display = "none";
            timeSelect.innerHTML = "";
            updateSlotsCount(0); // Reset slots count
            stopAutoRefresh();
            return;
        }
        
        fetch(`get_available_dates.php?service_id=${serviceId}`)
            .then(r => r.json())
            .then(dates => {
                dateSelect.innerHTML = "";
                if (Array.isArray(dates) && dates.length > 0) {
                    dates.forEach(dateInfo => {
                        const opt = document.createElement("option");
                        opt.value = dateInfo.date;
                        const weekdayNames = ['', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                        opt.textContent = `${dateInfo.date} (${weekdayNames[dateInfo.weekday]}) - ${dateInfo.start_time} to ${dateInfo.end_time}`;
                        dateSelect.appendChild(opt);
                    });
                    dateDiv.style.display = "block";
                    
                    // Load time slots for the first available date
                    if (dates.length > 0) {
                        loadTimeSlots(serviceId, null, dates[0].date); // Pass null for employeeId
                    }
                } else {
                    dateDiv.style.display = "none";
                    timeDiv.style.display = "none";
                    updateSlotsCount(0); // Reset slots count
                    stopAutoRefresh();
                }
            });
    }

    // Update hidden employee field when employee is selected
    function updateHiddenEmployeeField() {
        const employeeSelect = document.getElementById("employeeSelect");
        const hiddenField = document.getElementById("employeeIdHidden");
        if (employeeSelect && hiddenField) {
            hiddenField.value = employeeSelect.value || "";
        }
    }

    // Update hidden employee field when SPA service is selected
    function clearHiddenEmployeeField() {
        const hiddenField = document.getElementById("employeeIdHidden");
        if (hiddenField) {
            hiddenField.value = "";
        }
    }

    function loadAvailableDates(serviceId, employeeId) {
        currentEmployeeId = employeeId;
        currentDate = null;
        
        if (!serviceId || !employeeId) {
            dateDiv.style.display = "none";
            dateSelect.innerHTML = "";
            timeDiv.style.display = "none";
            timeSelect.innerHTML = "";
            updateSlotsCount(0); // Reset slots count
            stopAutoRefresh();
            return;
        }
        
        fetch(`get_available_dates.php?service_id=${serviceId}&employee_id=${employeeId}`)
            .then(r => r.json())
            .then(dates => {
                dateSelect.innerHTML = "";
                if (Array.isArray(dates) && dates.length > 0) {
                    dates.forEach(dateInfo => {
                        const opt = document.createElement("option");
                        opt.value = dateInfo.date;
                        const weekdayNames = ['', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                        opt.textContent = `${dateInfo.date} (${weekdayNames[dateInfo.weekday]}) - ${dateInfo.start_time} to ${dateInfo.end_time}`;
                        dateSelect.appendChild(opt);
                    });
                    dateDiv.style.display = "block";
                    
                    // Load time slots for the first available date
                    if (dates.length > 0) {
                        loadTimeSlots(serviceId, employeeId, dates[0].date);
                    }
                } else {
                    dateDiv.style.display = "none";
                    timeDiv.style.display = "none";
                    updateSlotsCount(0); // Reset slots count
                    stopAutoRefresh();
                }
            });
    }

    function loadTimeSlots(serviceId, employeeId, date, isAutoRefresh = false) {
        currentDate = date;
        
        if (!serviceId || !date) {
            timeDiv.style.display = "none";
            timeSelect.innerHTML = "";
            updateSlotsCount(0); // Reset slots count
            stopAutoRefresh();
            return;
        }
        
        // Show loading state
        if (!isAutoRefresh) {
            timeSelect.innerHTML = '<option value="">Loading...</option>';
        } else {
            // For auto-refresh, preserve current selection if possible
            const currentSelection = timeSelect.value;
            if (currentSelection) {
                timeSelect.innerHTML = '<option value="">Refreshing...</option>';
            }
        }
        
        // For SPA services, we don't need employeeId
        const url = employeeId ? 
            `get_available_slots.php?service_id=${serviceId}&employee_id=${employeeId}&date=${date}` :
            `get_available_slots.php?service_id=${serviceId}&date=${date}`;
        
        fetch(url)
            .then(r => r.json())
            .then(slots => {
                timeSelect.innerHTML = "";
                if (Array.isArray(slots) && slots.length > 0) {
                    let previouslySelectedSlot = null;
                    if (isAutoRefresh) {
                        previouslySelectedSlot = timeSelect.getAttribute('data-previous-selection');
                    }
                    
                    slots.forEach((time, index) => {
                        const opt = document.createElement("option");
                        opt.value = time;
                        opt.textContent = time;
                        
                        // If this was the previously selected slot, select it again
                        if (previouslySelectedSlot && time === previouslySelectedSlot) {
                            opt.selected = true;
                        }
                        
                        timeSelect.appendChild(opt);
                    });
                    
                    // Store current selection for next auto-refresh
                    if (timeSelect.value) {
                        timeSelect.setAttribute('data-previous-selection', timeSelect.value);
                    }
                    
                    timeDiv.style.display = "block";
                    updateSlotsCount(slots.length); // Update slots count
                    startAutoRefresh(); // Start auto-refresh when we have time slots
                    
                } else {
                    const opt = document.createElement("option");
                    opt.value = "";
                    opt.textContent = "No available time slots";
                    timeSelect.appendChild(opt);
                    timeDiv.style.display = "block";
                    updateSlotsCount(0); // Reset slots count
                    stopAutoRefresh();
                }
                
                if (!isAutoRefresh) {
                    updateLastUpdate();
                } else {
                    // For auto-refresh, update timestamp but don't show loading
                    updateLastUpdate();
                }
            })
            .catch(error => {
                timeSelect.innerHTML = '<option value="">Error loading slots</option>';
                timeDiv.style.display = "block";
                updateSlotsCount(0); // Reset slots count
                stopAutoRefresh();
            });
    }

    // Manual refresh button
    refreshBtn.addEventListener("click", function() {
        if (currentServiceId && currentDate) {
            loadTimeSlots(currentServiceId, currentEmployeeId, currentDate);
        }
    });

    // Event listeners
    if (document.getElementById("serviceSelect")) {
        document.getElementById("serviceSelect").addEventListener("change", function() {
            const selected = this.options[this.selectedIndex];
            const serviceId = selected.value;
            loadEmployeesForService(serviceId);
        });
    }
    
    employeeSelect.addEventListener("change", function() {
        const serviceId = document.getElementById("serviceIdHidden") ? document.getElementById("serviceIdHidden").value : document.getElementById("serviceSelect").value;
        const employeeId = this.value;
        updateHiddenEmployeeField(); // Update hidden field
        loadAvailableDates(serviceId, employeeId);
    });
    
    dateSelect.addEventListener("change", function() {
        const serviceId = document.getElementById("serviceIdHidden") ? document.getElementById("serviceIdHidden").value : document.getElementById("serviceSelect").value;
        const employeeId = employeeSelect.value;
        const date = this.value;
        loadTimeSlots(serviceId, employeeId, date);
    });

    // If we have a preselected service, load employees immediately
    <?php if ($selectedService): ?>
        // Check if preselected service is SPA
        const preselectedService = <?= json_encode($selectedService) ?>;
        if (preselectedService.category_id == 4) {
            // For SPA services, load dates directly
            clearHiddenEmployeeField(); // Clear hidden field for SPA
            loadAvailableDatesForSPA(preselectedService.id);
        } else {
            // For regular services, load employees
            loadEmployeesForService(preselectedService.id);
        }
    <?php endif; ?>

    // Form submission handling
    document.querySelector('form').addEventListener('submit', function(e) {
        const serviceId = document.getElementById("serviceIdHidden") ? 
            document.getElementById("serviceIdHidden").value : 
            document.getElementById("serviceSelect").value;
        const employeeId = document.getElementById("employeeSelect").value || 
            document.getElementById("employeeIdHidden").value;
        const date = document.getElementById("dateSelect").value;
        const time = document.getElementById("timeSelect").value;
        
        console.log('Form submission:', { serviceId, employeeId, date, time });
        console.log('Form action:', this.action);
        console.log('Form method:', this.method);
        console.log('Current service ID:', currentServiceId);
        console.log('Current employee ID:', currentEmployeeId);
        console.log('Current date:', currentDate);
        
        // Allow normal form submission
    });
})();
</script>

<?php include "footer.php"; ?>
