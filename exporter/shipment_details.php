<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login(['admin', 'exporter', 'logistics', 'customs']);

$shipmentId = (int) ($_GET['id'] ?? 0);
$shipment = $shipmentId > 0 ? fetch_shipment($conn, $shipmentId) : null;
$user = current_user();

if (!$shipment || !can_access_shipment($user, $shipment)) {
    set_flash('danger', 'Shipment not found or access denied.');
    redirect_to('exporter/view_shipments.php');
}

$compliance = run_compliance_check($conn, $shipmentId);
$documentsStmt = $conn->prepare(
    'SELECT id, document_type, file_path, uploaded_at, status
     FROM documents
     WHERE shipment_id = ?
     ORDER BY uploaded_at DESC'
);
$documentsStmt->bind_param('i', $shipmentId);
$documentsStmt->execute();
$documents = $documentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$statusSteps = shipment_statuses();
$currentStepIndex = array_search($shipment['shipment_status'], $statusSteps, true);
$pageTitle = 'Shipment Details';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="panel-card card h-100">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <h2 class="h4 mb-1">Shipment #<?= e((string) $shipment['shipment_id']) ?></h2>
                        <p class="page-subtitle mb-0"><?= e($shipment['product_name']) ?> to <?= e($shipment['destination_country']) ?></p>
                    </div>
                    <span class="badge text-bg-<?= e(badge_class_for_status((string) $shipment['shipment_status'])) ?> fs-6"><?= e($shipment['shipment_status']) ?></span>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><div class="border rounded-4 p-3 bg-light-subtle"><div class="small text-muted">Exporter</div><div class="fw-semibold"><?= e($shipment['exporter_name']) ?></div></div></div>
                    <div class="col-md-6"><div class="border rounded-4 p-3 bg-light-subtle"><div class="small text-muted">Shipping Method</div><div class="fw-semibold"><?= e($shipment['shipping_method']) ?></div></div></div>
                    <div class="col-md-6"><div class="border rounded-4 p-3 bg-light-subtle"><div class="small text-muted">Quantity</div><div class="fw-semibold"><?= e((string) $shipment['quantity']) ?></div></div></div>
                    <div class="col-md-6"><div class="border rounded-4 p-3 bg-light-subtle"><div class="small text-muted">Created At</div><div class="fw-semibold"><?= e(date('d M Y, h:i A', strtotime((string) $shipment['created_at']))) ?></div></div></div>
                </div>
                <h3 class="h6 mb-3">Workflow Progress</h3>
                <div class="progress-track">
                    <?php foreach ($statusSteps as $index => $status): ?>
                        <?php
                        $class = '';
                        if ($currentStepIndex !== false && $index < $currentStepIndex) {
                            $class = 'completed';
                        } elseif ($currentStepIndex !== false && $index === $currentStepIndex) {
                            $class = 'active';
                        }
                        ?>
                        <div class="progress-step <?= e($class) ?>">
                            <div class="fw-semibold"><?= e($status) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel-card card h-100">
            <div class="card-body">
                <h2 class="h5 mb-1">Compliance Status</h2>
                <p class="page-subtitle mb-3">Automatic verification of mandatory export documents.</p>
                <span class="badge <?= $compliance['compliance_status'] === 'Compliant' ? 'text-bg-success' : 'text-bg-warning' ?> fs-6"><?= e($compliance['compliance_status']) ?></span>
                <div class="mt-3">
                    <div class="small text-muted">Missing Documents</div>
                    <div class="fw-semibold"><?= e($compliance['missing_documents'] === [] ? 'None' : implode(', ', $compliance['missing_documents'])) ?></div>
                </div>
                <div class="d-grid gap-2 mt-4">
                    <?php if (in_array($user['role'], ['exporter', 'admin'], true)): ?>
                        <a href="<?= e(url('documents/upload_document.php?shipment_id=' . $shipment['shipment_id'])) ?>" class="btn btn-primary">Upload Documents</a>
                    <?php endif; ?>
                    <?php if (in_array($user['role'], ['customs', 'admin'], true)): ?>
                        <a href="<?= e(url('customs/review_documents.php?shipment_id=' . $shipment['shipment_id'])) ?>" class="btn btn-outline-secondary">Customs Review</a>
                    <?php endif; ?>
                    <?php if (in_array($user['role'], ['logistics', 'admin'], true)): ?>
                        <a href="<?= e(url('logistics/update_status.php?shipment_id=' . $shipment['shipment_id'])) ?>" class="btn btn-outline-secondary">Update Status</a>
                    <?php endif; ?>
                    <a href="<?= e(url('tracking/track_shipment.php?shipment_id=' . $shipment['shipment_id'])) ?>" class="btn btn-outline-dark">Track Shipment</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="panel-card card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">Linked Documents</h2>
                <p class="page-subtitle mb-0">All files attached to this shipment record.</p>
            </div>
            <a href="<?= e(url('documents/view_documents.php?shipment_id=' . $shipment['shipment_id'])) ?>" class="btn btn-outline-secondary btn-sm">Open document library</a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Uploaded</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($documents === []): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No documents uploaded for this shipment.</td></tr>
                <?php else: ?>
                    <?php foreach ($documents as $document): ?>
                        <tr>
                            <td><?= e($document['document_type']) ?></td>
                            <td><span class="badge text-bg-info"><?= e($document['status']) ?></span></td>
                            <td><?= e(date('d M Y, h:i A', strtotime((string) $document['uploaded_at']))) ?></td>
                            <td class="d-flex flex-wrap gap-2">
                                <a href="<?= e(document_url((string) $document['file_path'])) ?>" class="btn btn-outline-secondary btn-sm" target="_blank">Preview</a>
                                <a href="<?= e(document_url((string) $document['file_path'])) ?>" class="btn btn-outline-primary btn-sm" download>Download</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
