<?php
require_once "db.php";

// Взимаме всички услуги + категории
$services = $mysqli->query("
    SELECT s.id, s.name, c.name AS category_name, c.id AS category_id
    FROM services s
    JOIN service_categories c ON c.id = s.category_id
    ORDER BY c.name, s.name
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <title>Нова резервация</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Нова резервация</h4>
        </div>
        <div class="card-body">

            <form method="POST" action="save_reservation.php">
                <!-- Услуга -->
                <div class="mb-3">
                    <label for="serviceSelect" class="form-label">Услуга:</label>
                    <select name="service_id" id="serviceSelect" class="form-select" required>
                        <option value="">-- Изберете услуга --</option>
                        <?php foreach ($services as $s): ?>
                            <option value="<?= $s['id'] ?>" data-category="<?= $s['category_id'] ?>">
                                <?= htmlspecialchars($s['category_name'] . " - " . $s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Служител -->
                <div class="mb-3" id="employeeSelectDiv" style="display:none;">
                    <label for="employeeSelect" class="form-label">Служител:</label>
                    <select name="employee_id" id="employeeSelect" class="form-select">
                        <!-- ще се зарежда с JS -->
                    </select>
                </div>

                <!-- Дата -->
                <div class="mb-3">
                    <label for="date" class="form-label">Дата:</label>
                    <input type="date" name="reservation_date" id="date" class="form-control" required>
                </div>

                <!-- Час -->
                <div class="mb-3">
                    <label for="time" class="form-label">Час:</label>
                    <input type="time" name="reservation_time" id="time" class="form-control" required>
                </div>

                <!-- Бутон -->
                <button type="submit" class="btn btn-success w-100">Запази</button>
            </form>

        </div>
    </div>
</div>

<script>
document.getElementById("serviceSelect").addEventListener("change", function() {
    const selected = this.options[this.selectedIndex];
    const categoryId = selected.getAttribute("data-category");

    const employeeDiv = document.getElementById("employeeSelectDiv");
    const employeeSelect = document.getElementById("employeeSelect");

    if (!categoryId) {
        employeeDiv.style.display = "none";
        return;
    }

    // Ако е СПА – няма избор на служител
    if (categoryId == 4) {
        employeeDiv.style.display = "none";
        employeeSelect.innerHTML = "";
    } else {
        // AJAX заявка за служители по услуга
        fetch("get_employees.php?service_id=" + selected.value)
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
                } else {
                    employeeDiv.style.display = "none";
                }
            });
    }
});
</script>

</body>
</html>
