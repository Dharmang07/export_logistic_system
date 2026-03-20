<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);
ensure_compliance_records($conn);

$totalShipments = (int) ($conn->query('SELECT COUNT(*) AS total FROM shipments')->fetch_assoc()['total'] ?? 0);
$pendingApprovals = (int) ($conn->query("SELECT COUNT(*) AS total FROM shipments WHERE shipment_status IN ('Documents Uploaded', 'Under Customs Review')")->fetch_assoc()['total'] ?? 0);
$uploadedDocuments = (int) ($conn->query('SELECT COUNT(*) AS total FROM documents')->fetch_assoc()['total'] ?? 0);
$latestStatus = $conn->query('SELECT shipment_status FROM shipments ORDER BY created_at DESC LIMIT 1')->fetch_assoc()['shipment_status'] ?? 'N/A';

$recentShipments = $conn->query(
    'SELECT s.shipment_id, s.product_name, s.destination_country, s.shipment_status, s.created_at, u.name AS exporter_name
     FROM shipments s
     JOIN users u ON u.id = s.exporter_id
     ORDER BY s.created_at DESC
     LIMIT 6'
)->fetch_all(MYSQLI_ASSOC);

$recentDocuments = $conn->query(
    'SELECT d.document_type, d.uploaded_at, s.shipment_id, u.name AS exporter_name
     FROM documents d
     JOIN shipments s ON s.shipment_id = d.shipment_id
     JOIN users u ON u.id = s.exporter_id
     ORDER BY d.uploaded_at DESC
     LIMIT 6'
)->fetch_all(MYSQLI_ASSOC);

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

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stats-card card h-100">
            <div class="card-body">
                <div class="stats-icon mb-3"><i class="bi bi-box-seam"></i></div>
                <p class="text-muted mb-1">Total Shipments</p>
                <h3 class="mb-0"><?= e((string) $totalShipments) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stats-card card h-100">
            <div class="card-body">
                <div class="stats-icon mb-3"><i class="bi bi-hourglass-split"></i></div>
                <p class="text-muted mb-1">Pending Approvals</p>
                <h3 class="mb-0"><?= e((string) $pendingApprovals) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stats-card card h-100">
            <div class="card-body">
                <div class="stats-icon mb-3"><i class="bi bi-folder-check"></i></div>
                <p class="text-muted mb-1">Uploaded Documents</p>
                <h3 class="mb-0"><?= e((string) $uploadedDocuments) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stats-card card h-100">
            <div class="card-body">
                <div class="stats-icon mb-3"><i class="bi bi-signpost-split"></i></div>
                <p class="text-muted mb-1">Shipment Status</p>
                <h3 class="mb-0"><?= e($latestStatus) ?></h3>
                <small class="text-muted">Most recent workflow stage</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="panel-card card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h5 mb-1">Shipment Volume</h2>
                        <p class="page-subtitle mb-0">Monthly export shipment creation trend</p>
                    </div>
                    <a href="<?= e(url('admin/reports.php')) ?>" class="btn btn-outline-primary btn-sm">View full reports</a>
                </div>
                <canvas id="adminShipmentsChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel-card card h-100">
            <div class="card-body">
                <h2 class="h5 mb-1">Status Distribution</h2>
                <p class="page-subtitle mb-3">Current shipment lifecycle mix</p>
                <canvas id="adminStatusChart" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="panel-card table-card card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h5 mb-1">Recent Shipments</h2>
                        <p class="page-subtitle mb-0">Latest exporter activity across the platform</p>
                    </div>
                    <a href="<?= e(url('exporter/view_shipments.php')) ?>" class="btn btn-outline-secondary btn-sm">Open shipments</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Shipment ID</th>
                            <th>Exporter</th>
                            <th>Destination</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($recentShipments === []): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No shipments found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentShipments as $shipment): ?>
                                <tr>
                                    <td><a href="<?= e(url('exporter/shipment_details.php?id=' . $shipment['shipment_id'])) ?>" class="text-decoration-none">#<?= e((string) $shipment['shipment_id']) ?></a></td>
                                    <td><?= e($shipment['exporter_name']) ?></td>
                                    <td><?= e($shipment['destination_country']) ?></td>
                                    <td><span class="badge text-bg-<?= e(badge_class_for_status((string) $shipment['shipment_status'])) ?>"><?= e($shipment['shipment_status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel-card card h-100">
            <div class="card-body">
                <h2 class="h5 mb-1">Recent Document Uploads</h2>
                <p class="page-subtitle mb-3">Latest document activity requiring monitoring</p>
                <div class="d-grid gap-3">
                    <?php if ($recentDocuments === []): ?>
                        <div class="text-muted">No documents uploaded yet.</div>
                    <?php else: ?>
                        <?php foreach ($recentDocuments as $document): ?>
                            <div class="d-flex justify-content-between align-items-start border rounded-4 p-3 bg-light-subtle">
                                <div>
                                    <div class="fw-semibold"><?= e($document['document_type']) ?></div>
                                    <div class="small text-muted">Shipment #<?= e((string) $document['shipment_id']) ?> by <?= e($document['exporter_name']) ?></div>
                                </div>
                                <div class="small text-muted"><?= e(date('d M Y', strtotime((string) $document['uploaded_at']))) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="mt-4">
                    <h3 class="h6 mb-2">Compliance Snapshot</h3>
                    <canvas id="adminComplianceChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('adminShipmentsChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($monthlyRows, 'label'), JSON_THROW_ON_ERROR) ?>,
        datasets: [{
            label: 'Shipments',
            data: <?= json_encode(array_map('intval', array_column($monthlyRows, 'total')), JSON_THROW_ON_ERROR) ?>,
            borderColor: '#1f5d99',
            backgroundColor: 'rgba(31, 93, 153, 0.15)',
            fill: true,
            tension: 0.35
        }]
    },
    options: {responsive: true, maintainAspectRatio: false}
});

new Chart(document.getElementById('adminStatusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($statusRows, 'label'), JSON_THROW_ON_ERROR) ?>,
        datasets: [{
            data: <?= json_encode(array_map('intval', array_column($statusRows, 'total')), JSON_THROW_ON_ERROR) ?>,
            backgroundColor: ['#1f5d99', '#f0ad4e', '#0d2742', '#3cb878', '#d88c2d', '#6c757d']
        }]
    },
    options: {responsive: true, maintainAspectRatio: false}
});

new Chart(document.getElementById('adminComplianceChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($complianceRows, 'label'), JSON_THROW_ON_ERROR) ?>,
        datasets: [{
            label: 'Checks',
            data: <?= json_encode(array_map('intval', array_column($complianceRows, 'total')), JSON_THROW_ON_ERROR) ?>,
            backgroundColor: ['#3cb878', '#d88c2d']
        }]
    },
    options: {responsive: true, maintainAspectRatio: false}
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
