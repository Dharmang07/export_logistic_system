<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login(['exporter']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = trim((string) ($_POST['product_name'] ?? ''));
    $destinationCountry = trim((string) ($_POST['destination_country'] ?? ''));
    $quantity = (int) ($_POST['quantity'] ?? 0);
    $shippingMethod = trim((string) ($_POST['shipping_method'] ?? ''));
    $exporterId = (int) current_user()['id'];
    $status = 'Created';

    if ($productName === '' || $destinationCountry === '' || $quantity <= 0 || $shippingMethod === '') {
        set_flash('danger', 'Please complete all shipment fields.');
        redirect_to('exporter/create_shipment.php');
    }

    $stmt = $conn->prepare(
        'INSERT INTO shipments (exporter_id, product_name, destination_country, quantity, shipping_method, shipment_status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->bind_param('ississ', $exporterId, $productName, $destinationCountry, $quantity, $shippingMethod, $status);
    $stmt->execute();

    $shipmentId = (int) $conn->insert_id;
    run_compliance_check($conn, $shipmentId);
    notify_shipment_stakeholders($conn, $shipmentId, 'New shipment #' . $shipmentId . ' created and awaiting document upload.', ['admin']);

    set_flash('success', 'Shipment created successfully.');
    redirect_to('exporter/shipment_details.php?id=' . $shipmentId);
}

$pageTitle = 'Create Shipment';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="panel-card card">
            <div class="card-body">
                <h2 class="h5 mb-1">New Shipment</h2>
                <p class="page-subtitle mb-4">Capture shipment data before uploading export documents.</p>
                <form method="post" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="product_name">Product Name</label>
                        <input class="form-control" id="product_name" name="product_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="destination_country">Destination Country</label>
                        <input class="form-control" id="destination_country" name="destination_country" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="quantity">Quantity</label>
                        <input type="number" min="1" class="form-control" id="quantity" name="quantity" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="shipping_method">Shipping Method</label>
                        <select class="form-select" id="shipping_method" name="shipping_method" required>
                            <option value="">Select shipping method</option>
                            <option value="Air Freight">Air Freight</option>
                            <option value="Sea Freight">Sea Freight</option>
                            <option value="Road Freight">Road Freight</option>
                            <option value="Rail Freight">Rail Freight</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-3">
                        <button type="submit" class="btn btn-primary">Create Shipment</button>
                        <a href="<?= e(url('exporter/view_shipments.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel-card card h-100">
            <div class="card-body">
                <h2 class="h5 mb-1">Workflow After Creation</h2>
                <p class="page-subtitle mb-3">ELDMS will guide the shipment through each required stage.</p>
                <div class="d-grid gap-3">
                    <?php foreach (shipment_statuses() as $workflowStatus): ?>
                        <div class="border rounded-4 p-3 bg-light-subtle">
                            <div class="fw-semibold"><?= e($workflowStatus) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
