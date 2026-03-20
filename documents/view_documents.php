<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login(['admin', 'exporter', 'logistics', 'customs']);

$user = current_user();
$shipmentFilter = (int) ($_GET['shipment_id'] ?? 0);
$previewId = (int) ($_GET['preview'] ?? 0);

if ($user['role'] === 'exporter') {
    $sql = 'SELECT d.id, d.document_type, d.file_path, d.uploaded_at, d.status, s.shipment_id, s.product_name, u.name AS exporter_name
            FROM documents d
            JOIN shipments s ON s.shipment_id = d.shipment_id
            JOIN users u ON u.id = s.exporter_id
            WHERE s.exporter_id = ?';
    if ($shipmentFilter > 0) {
        $sql .= ' AND s.shipment_id = ?';
    }
    $sql .= ' ORDER BY d.uploaded_at DESC';
    $stmt = $conn->prepare($sql);
    if ($shipmentFilter > 0) {
        $stmt->bind_param('ii', $user['id'], $shipmentFilter);
    } else {
        $stmt->bind_param('i', $user['id']);
    }
    $stmt->execute();
    $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    if ($shipmentFilter > 0) {
        $stmt = $conn->prepare(
            'SELECT d.id, d.document_type, d.file_path, d.uploaded_at, d.status, s.shipment_id, s.product_name, u.name AS exporter_name
             FROM documents d
             JOIN shipments s ON s.shipment_id = d.shipment_id
             JOIN users u ON u.id = s.exporter_id
             WHERE s.shipment_id = ?
             ORDER BY d.uploaded_at DESC'
        );
        $stmt->bind_param('i', $shipmentFilter);
        $stmt->execute();
        $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $documents = $conn->query(
            'SELECT d.id, d.document_type, d.file_path, d.uploaded_at, d.status, s.shipment_id, s.product_name, u.name AS exporter_name
             FROM documents d
             JOIN shipments s ON s.shipment_id = d.shipment_id
             JOIN users u ON u.id = s.exporter_id
             ORDER BY d.uploaded_at DESC'
        )->fetch_all(MYSQLI_ASSOC);
    }
}

$previewDocument = null;
foreach ($documents as $document) {
    if ((int) $document['id'] === $previewId) {
        $previewDocument = $document;
        break;
    }
}

if ($previewDocument === null && $documents !== []) {
    $previewDocument = $documents[0];
}

$pageTitle = 'View Documents';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="panel-card card h-100">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Document Library</h2>
                        <p class="page-subtitle mb-0">Preview, download, and audit shipment documentation.</p>
                    </div>
                    <?php if (in_array($user['role'], ['admin', 'exporter'], true)): ?>
                        <a href="<?= e(url('documents/upload_document.php' . ($shipmentFilter > 0 ? '?shipment_id=' . $shipmentFilter : ''))) ?>" class="btn btn-primary btn-sm">Upload Document</a>
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Type</th>
                            <th>Shipment</th>
                            <th>Exporter</th>
                            <th>Uploaded</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($documents === []): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No documents found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($documents as $document): ?>
                                <tr>
                                    <td><?= e($document['document_type']) ?></td>
                                    <td>#<?= e((string) $document['shipment_id']) ?></td>
                                    <td><?= e($document['exporter_name']) ?></td>
                                    <td><?= e(date('d M Y', strtotime((string) $document['uploaded_at']))) ?></td>
                                    <td class="d-flex flex-wrap gap-2">
                                        <a href="<?= e(url('documents/view_documents.php?shipment_id=' . $document['shipment_id'] . '&preview=' . $document['id'])) ?>" class="btn btn-outline-secondary btn-sm">Preview</a>
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
    </div>
    <div class="col-lg-5">
        <div class="panel-card card h-100">
            <div class="card-body">
                <h2 class="h5 mb-1">Document Preview</h2>
                <p class="page-subtitle mb-3">Selected file linked to the shipment record.</p>
                <?php if ($previewDocument === null): ?>
                    <div class="text-muted">Select a document to preview it here.</div>
                <?php else: ?>
                    <div class="mb-3">
                        <div class="fw-semibold"><?= e($previewDocument['document_type']) ?></div>
                        <div class="small text-muted">Shipment #<?= e((string) $previewDocument['shipment_id']) ?> - <?= e($previewDocument['product_name']) ?></div>
                    </div>
                    <?php if (document_is_previewable((string) $previewDocument['file_path'])): ?>
                        <?php $extension = strtolower((string) pathinfo((string) $previewDocument['file_path'], PATHINFO_EXTENSION)); ?>
                        <?php if ($extension === 'pdf'): ?>
                            <iframe class="document-preview" src="<?= e(document_url((string) $previewDocument['file_path'])) ?>"></iframe>
                        <?php else: ?>
                            <img class="document-preview object-fit-contain p-2" src="<?= e(document_url((string) $previewDocument['file_path'])) ?>" alt="Document preview">
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-muted">Preview is not available for this file type.</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
