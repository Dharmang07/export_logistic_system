<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
ensure_compliance_records($conn);

$monthlyRows = $conn->query(
    "SELECT DATE_FORMAT(created_at, '%b %Y') AS label, COUNT(*) AS total
     FROM shipments
     GROUP BY YEAR(created_at), MONTH(created_at)
     ORDER BY YEAR(created_at), MONTH(created_at)"
)->fetch_all(MYSQLI_ASSOC);

$statusRows = $conn->query(
    'SELECT shipment_status AS label, COUNT(*) AS total
     FROM shipments
     GROUP BY shipment_status
     ORDER BY total DESC'
)->fetch_all(MYSQLI_ASSOC);

$complianceRows = $conn->query(
    'SELECT compliance_status AS label, COUNT(*) AS total
     FROM compliance_checks
     GROUP BY compliance_status'
)->fetch_all(MYSQLI_ASSOC);

$destinationRows = $conn->query(
    'SELECT destination_country AS label, COUNT(*) AS total
     FROM shipments
     GROUP BY destination_country
     ORDER BY total DESC
     LIMIT 5'
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Reports & Analytics';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="panel-card card h-100">
            <div class="card-body">
                <h2 class="h5 mb-1">Total Shipments Per Month</h2>
                <p class="page-subtitle mb-3">Monthly throughput based on shipment creation dates</p>
                <canvas id="reportsMonthlyChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel-card card h-100">
            <div class="card-body">
                <h2 class="h5 mb-1">Shipment Status Distribution</h2>
                <p class="page-subtitle mb-3">Operational load by current status</p>
                <canvas id="reportsStatusChart" height="240"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="panel-card card h-100">
            <div class="card-body">
                <h2 class="h5 mb-1">Compliance Issues</h2>
                <p class="page-subtitle mb-3">Compliant vs non-compliant shipments</p>
                <canvas id="reportsComplianceChart" height="180"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel-card card h-100">
            <div class="card-body">
                <h2 class="h5 mb-1">Top Destinations</h2>
                <p class="page-subtitle mb-3">Highest shipment volumes by destination country</p>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Country</th>
                            <th>Shipments</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($destinationRows === []): ?>
                            <tr><td colspan="2" class="text-center text-muted py-4">No shipment data available.</td></tr>
                        <?php else: ?>
                            <?php foreach ($destinationRows as $row): ?>
                                <tr>
                                    <td><?= e($row['label']) ?></td>
                                    <td><?= e((string) $row['total']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('reportsMonthlyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($monthlyRows, 'label'), JSON_THROW_ON_ERROR) ?>,
        datasets: [{
            label: 'Shipments',
            data: <?= json_encode(array_map('intval', array_column($monthlyRows, 'total')), JSON_THROW_ON_ERROR) ?>,
            backgroundColor: '#1f5d99'
        }]
    },
    options: {responsive: true, maintainAspectRatio: false}
});

new Chart(document.getElementById('reportsStatusChart'), {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($statusRows, 'label'), JSON_THROW_ON_ERROR) ?>,
        datasets: [{
            data: <?= json_encode(array_map('intval', array_column($statusRows, 'total')), JSON_THROW_ON_ERROR) ?>,
            backgroundColor: ['#1f5d99', '#f0ad4e', '#0d2742', '#3cb878', '#d88c2d', '#6c757d']
        }]
    },
    options: {responsive: true, maintainAspectRatio: false}
});

new Chart(document.getElementById('reportsComplianceChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($complianceRows, 'label'), JSON_THROW_ON_ERROR) ?>,
        datasets: [{
            data: <?= json_encode(array_map('intval', array_column($complianceRows, 'total')), JSON_THROW_ON_ERROR) ?>,
            backgroundColor: ['#3cb878', '#d88c2d']
        }]
    },
    options: {responsive: true, maintainAspectRatio: false}
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
