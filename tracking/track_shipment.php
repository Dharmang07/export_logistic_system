<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login(['admin', 'exporter', 'logistics', 'customs']);

$user = current_user();

if ($user['role'] === 'exporter') {
    $stmt = $conn->prepare('SELECT shipment_id, product_name, destination_country FROM shipments WHERE exporter_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $availableShipments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $availableShipments = $conn->query('SELECT shipment_id, product_name, destination_country FROM shipments ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);
}

$shipmentId = (int) ($_GET['shipment_id'] ?? 0);
if ($shipmentId <= 0 && $availableShipments !== []) {
    $shipmentId = (int) $availableShipments[0]['shipment_id'];
}

$shipment = $shipmentId > 0 ? fetch_shipment($conn, $shipmentId) : null;

if ($shipment && !can_access_shipment($user, $shipment)) {
    set_flash('danger', 'Shipment access denied.');
    redirect_to('tracking/track_shipment.php');
}

$progressLabels = ['Created', 'Documents Uploaded', 'Under Customs Review', 'Dispatched', 'Delivered'];
$progressPercentages = [
    'Created' => 10,
    'Documents Uploaded' => 35,
    'Under Customs Review' => 55,
    'Approved' => 70,
    'Dispatched' => 85,
    'Delivered' => 100,
];
$progressWidth = $shipment ? ($progressPercentages[$shipment['shipment_status']] ?? 0) : 0;

$pageTitle = 'Track Shipment';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="panel-card card h-100">
            <div class="card-body">
                <h2 class="h5 mb-1">Select Shipment</h2>
                <p class="page-subtitle mb-3">Choose a shipment to view its timeline.</p>
                <form method="get" class="d-grid gap-3">
                    <div>
                        <label class="form-label" for="shipment_id">Shipment</label>
                        <select class="form-select" id="shipment_id" name="shipment_id" onchange="this.form.submit()">
                            <?php foreach ($availableShipments as $availableShipment): ?>
                                <option value="<?= e((string) $availableShipment['shipment_id']) ?>" <?= $shipmentId === (int) $availableShipment['shipment_id'] ? 'selected' : '' ?>>
                                    #<?= e((string) $availableShipment['shipment_id']) ?> - <?= e($availableShipment['product_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <noscript><button type="submit" class="btn btn-primary">Track</button></noscript>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="timeline-card card h-100">
            <div class="card-body">
                <?php if (!$shipment): ?>
                    <div class="text-muted">No shipment available for tracking.</div>
                <?php else: ?>
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h2 class="h4 mb-1">Shipment #<?= e((string) $shipment['shipment_id']) ?></h2>
                            <p class="page-subtitle mb-0"><?= e($shipment['product_name']) ?> bound for <?= e($shipment['destination_country']) ?></p>
                        </div>
                        <span class="badge text-bg-<?= e(badge_class_for_status((string) $shipment['shipment_status'])) ?> fs-6"><?= e($shipment['shipment_status']) ?></span>
                    </div>
                    <div class="progress mb-4" style="height: 14px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= e((string) $progressWidth) ?>%" aria-valuenow="<?= e((string) $progressWidth) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="progress-track mb-4">
                        <?php foreach ($progressLabels as $label): ?>
                            <?php
                            $statusOrder = [
                                'Created' => 0,
                                'Documents Uploaded' => 1,
                                'Under Customs Review' => 2,
                                'Approved' => 2,
                                'Dispatched' => 3,
                                'Delivered' => 4,
                            ];
                            $labelOrder = [
                                'Created' => 0,
                                'Documents Uploaded' => 1,
                                'Under Customs Review' => 2,
                                'Dispatched' => 3,
                                'Delivered' => 4,
                            ];
                            $currentOrder = $statusOrder[$shipment['shipment_status']] ?? 0;
                            $targetOrder = $labelOrder[$label];
                            $class = '';
                            if ($targetOrder < $currentOrder) {
                                $class = 'completed';
                            } elseif ($targetOrder === $currentOrder) {
                                $class = 'active';
                            }
                            ?>
                            <div class="progress-step <?= e($class) ?>">
                                <div class="fw-semibold"><?= e($label) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($shipment['shipment_status'] === 'Approved'): ?>
                        <div class="alert alert-info">Shipment approved by customs and awaiting dispatch.</div>
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="border rounded-4 p-3 bg-light-subtle"><div class="small text-muted">Exporter</div><div class="fw-semibold"><?= e($shipment['exporter_name']) ?></div></div></div>
                        <div class="col-md-6"><div class="border rounded-4 p-3 bg-light-subtle"><div class="small text-muted">Shipping Method</div><div class="fw-semibold"><?= e($shipment['shipping_method']) ?></div></div></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
