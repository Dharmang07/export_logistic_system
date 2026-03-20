<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login(['admin', 'customs']);
ensure_compliance_records($conn);

$shipmentFilter = (int) ($_GET['shipment_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipmentId = (int) ($_POST['shipment_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    $shipment = $shipmentId > 0 ? fetch_shipment($conn, $shipmentId) : null;

    if (!$shipment) {
        set_flash('danger', 'Shipment not found.');
        redirect_to('customs/review_documents.php');
    }

    $compliance = run_compliance_check($conn, $shipmentId);

    if ($action === 'review') {
        $status = 'Under Customs Review';
        $stmt = $conn->prepare('UPDATE shipments SET shipment_status = ? WHERE shipment_id = ?');
        $stmt->bind_param('si', $status, $shipmentId);
        $stmt->execute();

        notify_shipment_stakeholders($conn, $shipmentId, 'Shipment #' . $shipmentId . ' moved to customs review.', ['admin']);
        set_flash('success', 'Shipment marked as under customs review.');
        redirect_to('customs/review_documents.php?shipment_id=' . $shipmentId);
    }

    if ($action === 'approve') {
        if ($compliance['compliance_status'] !== 'Compliant') {
            set_flash('warning', 'Cannot approve shipment until all required documents are uploaded.');
            redirect_to('customs/review_documents.php?shipment_id=' . $shipmentId);
        }

        $status = 'Approved';
        $stmt = $conn->prepare('UPDATE shipments SET shipment_status = ? WHERE shipment_id = ?');
        $stmt->bind_param('si', $status, $shipmentId);
        $stmt->execute();

        notify_shipment_stakeholders($conn, $shipmentId, 'Shipment #' . $shipmentId . ' approved by customs.', ['admin', 'logistics']);
        set_flash('success', 'Shipment approved successfully.');
        redirect_to('customs/review_documents.php?shipment_id=' . $shipmentId);
    }
}

if ($shipmentFilter > 0) {
    $stmt = $conn->prepare(
        "SELECT s.shipment_id, s.product_name, s.destination_country, s.shipment_status, u.name AS exporter_name, c.compliance_status, c.missing_documents
         FROM shipments s
         JOIN users u ON u.id = s.exporter_id
         LEFT JOIN compliance_checks c ON c.shipment_id = s.shipment_id
         WHERE s.shipment_id = ?"
    );
    $stmt->bind_param('i', $shipmentFilter);
    $stmt->execute();
    $shipments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $shipments = $conn->query(
        "SELECT s.shipment_id, s.product_name, s.destination_country, s.shipment_status, u.name AS exporter_name, c.compliance_status, c.missing_documents
         FROM shipments s
         JOIN users u ON u.id = s.exporter_id
         LEFT JOIN compliance_checks c ON c.shipment_id = s.shipment_id
         WHERE s.shipment_status IN ('Documents Uploaded', 'Under Customs Review', 'Approved')
         ORDER BY FIELD(s.shipment_status, 'Documents Uploaded', 'Under Customs Review', 'Approved'), s.created_at ASC"
    )->fetch_all(MYSQLI_ASSOC);
}

$selectedDocuments = [];
if ($shipmentFilter > 0) {
    $stmt = $conn->prepare('SELECT document_type, uploaded_at, status, file_path FROM documents WHERE shipment_id = ? ORDER BY uploaded_at DESC');
    $stmt->bind_param('i', $shipmentFilter);
    $stmt->execute();
    $selectedDocuments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$pageTitle = 'Review Documents';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="panel-card card">
            <div class="card-body">
                <h2 class="h5 mb-1">Customs Review Queue</h2>
                <p class="page-subtitle mb-3">Move shipments into review and approve compliant records.</p>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Exporter</th>
                            <th>Status</th>
                            <th>Compliance</th>
                            <th>Missing Docs</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($shipments === []): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No shipments available for customs review.</td></tr>
                        <?php else: ?>
                            <?php foreach ($shipments as $shipment): ?>
                                <tr>
                                    <td><a href="<?= e(url('customs/review_documents.php?shipment_id=' . $shipment['shipment_id'])) ?>" class="text-decoration-none">#<?= e((string) $shipment['shipment_id']) ?></a></td>
                                    <td><?= e($shipment['exporter_name']) ?></td>
                                    <td><span class="badge text-bg-<?= e(badge_class_for_status((string) $shipment['shipment_status'])) ?>"><?= e($shipment['shipment_status']) ?></span></td>
                                    <td><span class="badge <?= $shipment['compliance_status'] === 'Compliant' ? 'text-bg-success' : 'text-bg-warning' ?>"><?= e($shipment['compliance_status'] ?? 'Pending') ?></span></td>
                                    <td class="small"><?= e($shipment['missing_documents'] ?? 'Pending') ?></td>
                                    <td class="d-flex flex-wrap gap-2">
                                        <form method="post">
                                            <input type="hidden" name="shipment_id" value="<?= e((string) $shipment['shipment_id']) ?>">
                                            <input type="hidden" name="action" value="review">
                                            <button type="submit" class="btn btn-outline-secondary btn-sm">Set Review</button>
                                        </form>
                                        <form method="post">
                                            <input type="hidden" name="shipment_id" value="<?= e((string) $shipment['shipment_id']) ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-primary btn-sm">Approve</button>
                                        </form>
                                    </td>
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
                <h2 class="h5 mb-1">Selected Shipment Documents</h2>
                <p class="page-subtitle mb-3">Document list for the currently selected shipment.</p>
                <?php if ($shipmentFilter <= 0): ?>
                    <div class="text-muted">Choose a shipment ID from the table to inspect its documents.</div>
                <?php elseif ($selectedDocuments === []): ?>
                    <div class="text-muted">No documents uploaded for this shipment.</div>
                <?php else: ?>
                    <div class="d-grid gap-3">
                        <?php foreach ($selectedDocuments as $document): ?>
                            <div class="border rounded-4 p-3 bg-light-subtle">
                                <div class="fw-semibold"><?= e($document['document_type']) ?></div>
                                <div class="small text-muted"><?= e(date('d M Y, h:i A', strtotime((string) $document['uploaded_at']))) ?></div>
                                <a href="<?= e(document_url((string) $document['file_path'])) ?>" target="_blank" class="small text-decoration-none">Open file</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
