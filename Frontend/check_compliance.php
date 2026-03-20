<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login(['admin', 'exporter', 'logistics', 'customs']);
ensure_compliance_records($conn);

$user = current_user();
$shipmentFilter = (int) ($_GET['shipment_id'] ?? 0);

if ($user['role'] === 'exporter') {
    $sql = 'SELECT s.shipment_id, s.product_name, s.destination_country, s.shipment_status, u.name AS exporter_name, c.missing_documents, c.compliance_status
            FROM shipments s
            JOIN users u ON u.id = s.exporter_id
            LEFT JOIN compliance_checks c ON c.shipment_id = s.shipment_id
            WHERE s.exporter_id = ?';
    if ($shipmentFilter > 0) {
        $sql .= ' AND s.shipment_id = ?';
    }
    $sql .= ' ORDER BY s.created_at DESC';
    $stmt = $conn->prepare($sql);
    if ($shipmentFilter > 0) {
        $stmt->bind_param('ii', $user['id'], $shipmentFilter);
    } else {
        $stmt->bind_param('i', $user['id']);
    }
    $stmt->execute();
    $checks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    if ($shipmentFilter > 0) {
        $stmt = $conn->prepare(
            'SELECT s.shipment_id, s.product_name, s.destination_country, s.shipment_status, u.name AS exporter_name, c.missing_documents, c.compliance_status
             FROM shipments s
             JOIN users u ON u.id = s.exporter_id
             LEFT JOIN compliance_checks c ON c.shipment_id = s.shipment_id
             WHERE s.shipment_id = ?
             ORDER BY s.created_at DESC'
        );
        $stmt->bind_param('i', $shipmentFilter);
        $stmt->execute();
        $checks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $checks = $conn->query(
            'SELECT s.shipment_id, s.product_name, s.destination_country, s.shipment_status, u.name AS exporter_name, c.missing_documents, c.compliance_status
             FROM shipments s
             JOIN users u ON u.id = s.exporter_id
             LEFT JOIN compliance_checks c ON c.shipment_id = s.shipment_id
             ORDER BY s.created_at DESC'
        )->fetch_all(MYSQLI_ASSOC);
    }
}

$compliantCount = 0;
$nonCompliantCount = 0;
foreach ($checks as $check) {
    if (($check['compliance_status'] ?? '') === 'Compliant') {
        $compliantCount++;
    } else {
        $nonCompliantCount++;
    }
}

$pageTitle = 'Compliance Check';
require_once __DIR__ . '/includes/header.php';
?>
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="stats-card card h-100">
            <div class="card-body">
                <div class="stats-icon mb-3"><i class="bi bi-shield-check"></i></div>
                <p class="text-muted mb-1">Compliant Shipments</p>
                <h3 class="mb-0"><?= e((string) $compliantCount) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stats-card card h-100">
            <div class="card-body">
                <div class="stats-icon mb-3"><i class="bi bi-exclamation-triangle"></i></div>
                <p class="text-muted mb-1">Compliance Warnings</p>
                <h3 class="mb-0"><?= e((string) $nonCompliantCount) ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="panel-card card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">Automatic Compliance Verification</h2>
                <p class="page-subtitle mb-0">Each shipment is checked for Commercial Invoice, Packing List, and Bill of Lading.</p>
            </div>
            <a href="<?= e(url('documents/upload_document.php')) ?>" class="btn btn-outline-primary btn-sm">Upload Missing Documents</a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Shipment ID</th>
                    <th>Exporter</th>
                    <th>Status</th>
                    <th>Compliance</th>
                    <th>Missing Documents</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($checks === []): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No compliance records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($checks as $check): ?>
                        <tr>
                            <td>#<?= e((string) $check['shipment_id']) ?></td>
                            <td><?= e($check['exporter_name']) ?></td>
                            <td><span class="badge text-bg-<?= e(badge_class_for_status((string) $check['shipment_status'])) ?>"><?= e($check['shipment_status']) ?></span></td>
                            <td><span class="badge <?= $check['compliance_status'] === 'Compliant' ? 'text-bg-success' : 'text-bg-warning' ?>"><?= e($check['compliance_status'] ?? 'Pending') ?></span></td>
                            <td><?= e($check['missing_documents'] ?? 'Pending') ?></td>
                            <td class="d-flex flex-wrap gap-2">
                                <a href="<?= e(url('exporter/shipment_details.php?id=' . $check['shipment_id'])) ?>" class="btn btn-outline-secondary btn-sm">Details</a>
                                <a href="<?= e(url('documents/view_documents.php?shipment_id=' . $check['shipment_id'])) ?>" class="btn btn-outline-primary btn-sm">Documents</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
