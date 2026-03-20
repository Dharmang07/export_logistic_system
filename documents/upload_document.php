<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login(['admin', 'exporter']);

$user = current_user();

if ($user['role'] === 'exporter') {
    $stmt = $conn->prepare('SELECT shipment_id, product_name, destination_country FROM shipments WHERE exporter_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $shipments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $shipments = $conn->query('SELECT shipment_id, product_name, destination_country FROM shipments ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipmentId = (int) ($_POST['shipment_id'] ?? 0);
    $documentType = (string) ($_POST['document_type'] ?? '');

    if ($shipmentId <= 0 || !in_array($documentType, allowed_document_types(), true)) {
        set_flash('danger', 'Select a shipment and a valid document type.');
        redirect_to('documents/upload_document.php');
    }

    $shipment = fetch_shipment($conn, $shipmentId);
    if (!$shipment || !can_access_shipment($user, $shipment)) {
        set_flash('danger', 'Shipment access denied.');
        redirect_to('documents/upload_document.php');
    }

    try {
        $uploadedFile = save_uploaded_file($_FILES['document_file'] ?? []);
        $status = 'Uploaded';

        $stmt = $conn->prepare(
            'INSERT INTO documents (shipment_id, document_type, file_path, uploaded_at, status)
             VALUES (?, ?, ?, NOW(), ?)'
        );
        $stmt->bind_param('isss', $shipmentId, $documentType, $uploadedFile['relative_path'], $status);
        $stmt->execute();

        run_compliance_check($conn, $shipmentId);
        notify_shipment_stakeholders($conn, $shipmentId, $documentType . ' uploaded for shipment #' . $shipmentId . '.', ['admin', 'customs']);

        set_flash('success', 'Document uploaded successfully.');
        redirect_to('documents/view_documents.php?shipment_id=' . $shipmentId);
    } catch (RuntimeException $exception) {
        set_flash('danger', $exception->getMessage());
        redirect_to('documents/upload_document.php?shipment_id=' . $shipmentId);
    }
}

$selectedShipmentId = (int) ($_GET['shipment_id'] ?? 0);
$pageTitle = 'Upload Document';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="panel-card card">
            <div class="card-body">
                <h2 class="h5 mb-1">Upload Export Document</h2>
                <p class="page-subtitle mb-4">Attach PDF or image files to a shipment record.</p>
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="shipment_id">Shipment</label>
                        <select class="form-select" id="shipment_id" name="shipment_id" required>
                            <option value="">Select shipment</option>
                            <?php foreach ($shipments as $shipment): ?>
                                <option value="<?= e((string) $shipment['shipment_id']) ?>" <?= $selectedShipmentId === (int) $shipment['shipment_id'] ? 'selected' : '' ?>>
                                    #<?= e((string) $shipment['shipment_id']) ?> - <?= e($shipment['product_name']) ?> (<?= e($shipment['destination_country']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="document_type">Document Type</label>
                        <select class="form-select" id="document_type" name="document_type" required>
                            <option value="">Select document type</option>
                            <?php foreach (allowed_document_types() as $documentType): ?>
                                <option value="<?= e($documentType) ?>"><?= e($documentType) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="document_file">Document File</label>
                        <div class="upload-dropzone">
                            <input class="form-control" type="file" id="document_file" name="document_file" accept=".pdf,.png,.jpg,.jpeg,.gif,.webp" data-upload-input data-upload-target="#uploadFileName" required>
                            <div id="uploadFileName" class="small text-muted mt-2">No file selected</div>
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-3">
                        <button type="submit" class="btn btn-primary">Upload Document</button>
                        <a href="<?= e(url('documents/view_documents.php')) ?>" class="btn btn-outline-secondary">View Documents</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel-card card h-100">
            <div class="card-body">
                <h2 class="h5 mb-1">Supported Files</h2>
                <p class="page-subtitle mb-3">Accepted formats for export documentation uploads.</p>
                <ul class="mb-0 ps-3">
                    <li>PDF documents</li>
                    <li>JPG / PNG / GIF / WEBP images</li>
                    <li>Linked to a shipment ID automatically</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
