<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login(['admin', 'logistics']);

$shipmentFilter = (int) ($_GET['shipment_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipmentId = (int) ($_POST['shipment_id'] ?? 0);
    $newStatus = (string) ($_POST['shipment_status'] ?? '');
    $shipment = $shipmentId > 0 ? fetch_shipment($conn, $shipmentId) : null;

    if (!$shipment || !in_array($newStatus, ['Dispatched', 'Delivered'], true)) {
        set_flash('danger', 'Invalid shipment update.');
        redirect_to('logistics/update_status.php');
    }

    $validTransition = ($newStatus === 'Dispatched' && in_array($shipment['shipment_status'], ['Approved', 'Dispatched'], true))
        || ($newStatus === 'Delivered' && in_array($shipment['shipment_status'], ['Dispatched', 'Delivered'], true));

    if (!$validTransition) {
        set_flash('warning', 'That status transition is not allowed.');
        redirect_to('logistics/update_status.php?shipment_id=' . $shipmentId);
    }

    $stmt = $conn->prepare('UPDATE shipments SET shipment_status = ? WHERE shipment_id = ?');
    $stmt->bind_param('si', $newStatus, $shipmentId);
    $stmt->execute();

    if ($newStatus === 'Dispatched') {
        notify_shipment_stakeholders($conn, $shipmentId, 'Shipment #' . $shipmentId . ' has been dispatched.', ['admin']);
    } else {
        notify_shipment_stakeholders($conn, $shipmentId, 'Shipment #' . $shipmentId . ' has been delivered.', ['admin']);
    }

    set_flash('success', 'Shipment status updated to ' . $newStatus . '.');
    redirect_to('logistics/update_status.php?shipment_id=' . $shipmentId);
}

if ($shipmentFilter > 0) {
    $stmt = $conn->prepare(
        "SELECT s.shipment_id, s.product_name, s.destination_country, s.shipment_status, u.name AS exporter_name
         FROM shipments s
         JOIN users u ON u.id = s.exporter_id
         WHERE s.shipment_id = ?"
    );
    $stmt->bind_param('i', $shipmentFilter);
    $stmt->execute();
    $shipments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $shipments = $conn->query(
        "SELECT s.shipment_id, s.product_name, s.destination_country, s.shipment_status, u.name AS exporter_name
         FROM shipments s
         JOIN users u ON u.id = s.exporter_id
         WHERE s.shipment_status IN ('Approved', 'Dispatched')
         ORDER BY FIELD(s.shipment_status, 'Approved', 'Dispatched'), s.created_at ASC"
    )->fetch_all(MYSQLI_ASSOC);
}

$pageTitle = 'Update Shipment Status';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="panel-card card">
    <div class="card-body">
        <h2 class="h5 mb-1">Logistics Status Updates</h2>
        <p class="page-subtitle mb-3">Move approved shipments to dispatched and delivered stages.</p>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Exporter</th>
                    <th>Product</th>
                    <th>Destination</th>
                    <th>Current Status</th>
                    <th>Update</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($shipments === []): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No shipments available for logistics updates.</td></tr>
                <?php else: ?>
                    <?php foreach ($shipments as $shipment): ?>
                        <tr>
                            <td>#<?= e((string) $shipment['shipment_id']) ?></td>
                            <td><?= e($shipment['exporter_name']) ?></td>
                            <td><?= e($shipment['product_name']) ?></td>
                            <td><?= e($shipment['destination_country']) ?></td>
                            <td><span class="badge text-bg-<?= e(badge_class_for_status((string) $shipment['shipment_status'])) ?>"><?= e($shipment['shipment_status']) ?></span></td>
                            <td>
                                <form method="post" class="d-flex gap-2 align-items-center">
                                    <input type="hidden" name="shipment_id" value="<?= e((string) $shipment['shipment_id']) ?>">
                                    <select class="form-select form-select-sm" name="shipment_status">
                                        <?php if ($shipment['shipment_status'] === 'Approved'): ?>
                                            <option value="Dispatched">Dispatched</option>
                                        <?php else: ?>
                                            <option value="Delivered">Delivered</option>
                                        <?php endif; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
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
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
