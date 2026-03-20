<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login(['exporter']);
ensure_compliance_records($conn);

$userId = (int) current_user()['id'];
$stmt = $conn->prepare('SELECT COUNT(*) AS total FROM shipments WHERE exporter_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$totalShipments = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM shipments WHERE exporter_id = ? AND shipment_status IN ('Documents Uploaded', 'Under Customs Review')");
$stmt->bind_param('i', $userId);
$stmt->execute();
$pendingApprovals = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);

$stmt = $conn->prepare(
    'SELECT COUNT(*) AS total
     FROM documents d
     JOIN shipments s ON s.shipment_id = d.shipment_id
     WHERE s.exporter_id = ?'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$uploadedDocuments = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);

$stmt = $conn->prepare('SELECT shipment_status FROM shipments WHERE exporter_id = ? ORDER BY created_at DESC LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$latestStatus = $stmt->get_result()->fetch_assoc()['shipment_status'] ?? 'N/A';

$stmt = $conn->prepare(
    'SELECT s.shipment_id, s.product_name, s.destination_country, s.shipment_status, s.created_at, c.compliance_status
     FROM shipments s
     LEFT JOIN compliance_checks c ON c.shipment_id = s.shipment_id
     WHERE s.exporter_id = ?
     ORDER BY s.created_at DESC
     LIMIT 6'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$recentShipments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Exporter Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6"><div class="stats-card card h-100"><div class="card-body"><div class="stats-icon mb-3"><i class="bi bi-box-seam"></i></div><p class="text-muted mb-1">Total Shipments</p><h3 class="mb-0"><?= e((string) $totalShipments) ?></h3></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="stats-card card h-100"><div class="card-body"><div class="stats-icon mb-3"><i class="bi bi-hourglass-split"></i></div><p class="text-muted mb-1">Pending Approvals</p><h3 class="mb-0"><?= e((string) $pendingApprovals) ?></h3></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="stats-card card h-100"><div class="card-body"><div class="stats-icon mb-3"><i class="bi bi-folder-check"></i></div><p class="text-muted mb-1">Uploaded Documents</p><h3 class="mb-0"><?= e((string) $uploadedDocuments) ?></h3></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="stats-card card h-100"><div class="card-body"><div class="stats-icon mb-3"><i class="bi bi-signpost-split"></i></div><p class="text-muted mb-1">Shipment Status</p><h3 class="mb-0"><?= e($latestStatus) ?></h3><small class="text-muted">Latest shipment stage</small></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="panel-card card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h5 mb-1">Your Recent Shipments</h2>
                        <p class="page-subtitle mb-0">Monitor document readiness and customs progress</p>
                    </div>
                    <a href="<?= e(url('exporter/create_shipment.php')) ?>" class="btn btn-primary btn-sm">Create Shipment</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Destination</th>
                            <th>Status</th>
                            <th>Compliance</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($recentShipments === []): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No shipments created yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentShipments as $shipment): ?>
                                <tr>
                                    <td>#<?= e((string) $shipment['shipment_id']) ?></td>
                                    <td><?= e($shipment['product_name']) ?></td>
                                    <td><?= e($shipment['destination_country']) ?></td>
                                    <td><span class="badge text-bg-<?= e(badge_class_for_status((string) $shipment['shipment_status'])) ?>"><?= e($shipment['shipment_status']) ?></span></td>
                                    <td><span class="badge <?= $shipment['compliance_status'] === 'Compliant' ? 'text-bg-success' : 'text-bg-warning' ?>"><?= e($shipment['compliance_status'] ?? 'Pending') ?></span></td>
                                    <td><a href="<?= e(url('exporter/shipment_details.php?id=' . $shipment['shipment_id'])) ?>" class="btn btn-outline-secondary btn-sm">Details</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel-card card h-100">
            <div class="card-body">
                <h2 class="h5 mb-1">Quick Actions</h2>
                <p class="page-subtitle mb-3">Complete the export documentation workflow faster</p>
                <div class="d-grid gap-3">
                    <a href="<?= e(url('documents/upload_document.php')) ?>" class="btn btn-outline-primary">Upload Export Documents</a>
                    <a href="<?= e(url('check_compliance.php')) ?>" class="btn btn-outline-secondary">Run Compliance Check</a>
                    <a href="<?= e(url('tracking/track_shipment.php')) ?>" class="btn btn-outline-secondary">Track Shipment Progress</a>
                </div>
                <div class="mt-4 p-3 rounded-4 bg-light-subtle border">
                    <h3 class="h6">Required Core Documents</h3>
                    <ul class="mb-0 ps-3">
                        <?php foreach (required_document_types() as $documentType): ?>
                            <li><?= e($documentType) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
