<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login(['customs']);
ensure_compliance_records($conn);

$totalShipments = (int) ($conn->query('SELECT COUNT(*) AS total FROM shipments')->fetch_assoc()['total'] ?? 0);
$pendingApprovals = (int) ($conn->query("SELECT COUNT(*) AS total FROM shipments WHERE shipment_status IN ('Documents Uploaded', 'Under Customs Review')")->fetch_assoc()['total'] ?? 0);
$uploadedDocuments = (int) ($conn->query('SELECT COUNT(*) AS total FROM documents')->fetch_assoc()['total'] ?? 0);
$latestStatus = $conn->query('SELECT shipment_status FROM shipments ORDER BY created_at DESC LIMIT 1')->fetch_assoc()['shipment_status'] ?? 'N/A';

$reviewQueue = $conn->query(
    "SELECT s.shipment_id, s.product_name, s.destination_country, s.shipment_status, u.name AS exporter_name, c.compliance_status, c.missing_documents
     FROM shipments s
     JOIN users u ON u.id = s.exporter_id
     LEFT JOIN compliance_checks c ON c.shipment_id = s.shipment_id
     WHERE s.shipment_status IN ('Documents Uploaded', 'Under Customs Review')
     ORDER BY s.created_at ASC
     LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Customs Dashboard';
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
                        <h2 class="h5 mb-1">Review Queue</h2>
                        <p class="page-subtitle mb-0">Shipments waiting for customs action</p>
                    </div>
                    <a href="<?= e(url('customs/review_documents.php')) ?>" class="btn btn-primary btn-sm">Open review page</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Exporter</th>
                            <th>Status</th>
                            <th>Compliance</th>
                            <th>Missing Docs</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($reviewQueue === []): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No shipments waiting for customs review.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reviewQueue as $shipment): ?>
                                <tr>
                                    <td><a href="<?= e(url('exporter/shipment_details.php?id=' . $shipment['shipment_id'])) ?>" class="text-decoration-none">#<?= e((string) $shipment['shipment_id']) ?></a></td>
                                    <td><?= e($shipment['exporter_name']) ?></td>
                                    <td><span class="badge text-bg-<?= e(badge_class_for_status((string) $shipment['shipment_status'])) ?>"><?= e($shipment['shipment_status']) ?></span></td>
                                    <td><span class="badge <?= $shipment['compliance_status'] === 'Compliant' ? 'text-bg-success' : 'text-bg-warning' ?>"><?= e($shipment['compliance_status'] ?? 'Pending') ?></span></td>
                                    <td class="small"><?= e($shipment['missing_documents'] ?? 'Pending') ?></td>
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
                <h2 class="h5 mb-1">Compliance Rule Set</h2>
                <p class="page-subtitle mb-3">Minimum documentation required before approval</p>
                <div class="d-grid gap-3">
                    <?php foreach (required_document_types() as $documentType): ?>
                        <div class="border rounded-4 p-3 bg-light-subtle">
                            <div class="fw-semibold"><?= e($documentType) ?></div>
                            <div class="small text-muted">Required before customs approval can be granted.</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
