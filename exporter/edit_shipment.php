<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login(['admin', 'exporter']);

$shipmentId = (int) ($_GET['id'] ?? 0);
$shipment = $shipmentId > 0 ? fetch_shipment($conn, $shipmentId) : null;
$user = current_user();

if (!$shipment || !can_access_shipment($user, $shipment)) {
    set_flash('danger', 'Shipment not found or access denied.');
    redirect_to('exporter/view_shipments.php');
}

if ($user['role'] === 'exporter' && in_array($shipment['shipment_status'], ['Dispatched', 'Delivered'], true)) {
    set_flash('warning', 'Dispatched or delivered shipments cannot be edited by exporters.');
    redirect_to('exporter/shipment_details.php?id=' . $shipmentId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = trim((string) ($_POST['product_name'] ?? ''));
    $destinationCountry = trim((string) ($_POST['destination_country'] ?? ''));
    $quantity = (int) ($_POST['quantity'] ?? 0);
    $shippingMethod = trim((string) ($_POST['shipping_method'] ?? ''));
    $newStatus = $shipment['shipment_status'];

    if ($productName === '' || $destinationCountry === '' || $quantity <= 0 || $shippingMethod === '') {
        set_flash('danger', 'All shipment fields are required.');
        redirect_to('exporter/edit_shipment.php?id=' . $shipmentId);
    }

    if ($user['role'] === 'admin') {
        $postedStatus = (string) ($_POST['shipment_status'] ?? $shipment['shipment_status']);
        if (in_array($postedStatus, shipment_statuses(), true)) {
            $newStatus = $postedStatus;
        }
    }

    $stmt = $conn->prepare(
        'UPDATE shipments
         SET product_name = ?, destination_country = ?, quantity = ?, shipping_method = ?, shipment_status = ?
         WHERE shipment_id = ?'
    );
    $stmt->bind_param('ssissi', $productName, $destinationCountry, $quantity, $shippingMethod, $newStatus, $shipmentId);
    $stmt->execute();

    if ($newStatus !== $shipment['shipment_status']) {
        notify_shipment_stakeholders($conn, $shipmentId, 'Shipment #' . $shipmentId . ' status changed to ' . $newStatus . '.', ['admin', 'logistics', 'customs']);
    }

    set_flash('success', 'Shipment updated successfully.');
    redirect_to('exporter/shipment_details.php?id=' . $shipmentId);
}

$pageTitle = 'Edit Shipment';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="panel-card card">
            <div class="card-body">
                <h2 class="h5 mb-1">Edit Shipment #<?= e((string) $shipment['shipment_id']) ?></h2>
                <p class="page-subtitle mb-4">Update shipment details and keep the workflow data accurate.</p>
                <form method="post" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="product_name">Product Name</label>
                        <input class="form-control" id="product_name" name="product_name" value="<?= e($shipment['product_name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="destination_country">Destination Country</label>
                        <input class="form-control" id="destination_country" name="destination_country" value="<?= e($shipment['destination_country']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="quantity">Quantity</label>
                        <input type="number" min="1" class="form-control" id="quantity" name="quantity" value="<?= e((string) $shipment['quantity']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="shipping_method">Shipping Method</label>
                        <select class="form-select" id="shipping_method" name="shipping_method" required>
                            <?php foreach (['Air Freight', 'Sea Freight', 'Road Freight', 'Rail Freight'] as $method): ?>
                                <option value="<?= e($method) ?>" <?= $shipment['shipping_method'] === $method ? 'selected' : '' ?>><?= e($method) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($user['role'] === 'admin'): ?>
                        <div class="col-12">
                            <label class="form-label" for="shipment_status">Shipment Status</label>
                            <select class="form-select" id="shipment_status" name="shipment_status">
                                <?php foreach (shipment_statuses() as $status): ?>
                                    <option value="<?= e($status) ?>" <?= $shipment['shipment_status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="col-12 d-flex gap-3">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="<?= e(url('exporter/shipment_details.php?id=' . $shipment['shipment_id'])) ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel-card card h-100">
            <div class="card-body">
                <h2 class="h5 mb-1">Current Status</h2>
                <p class="page-subtitle mb-3">Existing workflow state for this shipment.</p>
                <span class="badge text-bg-<?= e(badge_class_for_status((string) $shipment['shipment_status'])) ?> fs-6"><?= e($shipment['shipment_status']) ?></span>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
