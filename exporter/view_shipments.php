<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login(['admin', 'exporter', 'logistics', 'customs']);
ensure_compliance_records($conn);

$user = current_user();

if ($user['role'] === 'exporter') {
    $stmt = $conn->prepare(
        'SELECT s.shipment_id, s.product_name, s.destination_country, s.quantity, s.shipping_method, s.shipment_status, s.created_at, u.name AS exporter_name, c.compliance_status
         FROM shipments s
         JOIN users u ON u.id = s.exporter_id
         LEFT JOIN compliance_checks c ON c.shipment_id = s.shipment_id
         WHERE s.exporter_id = ?
         ORDER BY s.created_at DESC'
    );
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $shipments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $shipments = $conn->query(
        'SELECT s.shipment_id, s.product_name, s.destination_country, s.quantity, s.shipping_method, s.shipment_status, s.created_at, u.name AS exporter_name, c.compliance_status
         FROM shipments s
         JOIN users u ON u.id = s.exporter_id
         LEFT JOIN compliance_checks c ON c.shipment_id = s.shipment_id
         ORDER BY s.created_at DESC'
    )->fetch_all(MYSQLI_ASSOC);
}

$pageTitle = 'View Shipments';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="panel-card card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">Shipment Register</h2>
                <p class="page-subtitle mb-0">Track every export shipment and open its related workflow pages.</p>
            </div>
            <?php if ($user['role'] === 'exporter'): ?>
                <a href="<?= e(url('exporter/create_shipment.php')) ?>" class="btn btn-primary">Create Shipment</a>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Shipment ID</th>
                    <th>Exporter</th>
                    <th>Product</th>
                    <th>Destination</th>
                    <th>Status</th>
                    <th>Compliance</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($shipments === []): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No shipments available.</td></tr>
                <?php else: ?>
                    <?php foreach ($shipments as $shipment): ?>
                        <tr>
                            <td>#<?= e((string) $shipment['shipment_id']) ?></td>
                            <td><?= e($shipment['exporter_name']) ?></td>
                            <td><?= e($shipment['product_name']) ?></td>
                            <td><?= e($shipment['destination_country']) ?></td>
                            <td><span class="badge text-bg-<?= e(badge_class_for_status((string) $shipment['shipment_status'])) ?>"><?= e($shipment['shipment_status']) ?></span></td>
                            <td><span class="badge <?= $shipment['compliance_status'] === 'Compliant' ? 'text-bg-success' : 'text-bg-warning' ?>"><?= e($shipment['compliance_status'] ?? 'Pending') ?></span></td>
                            <td class="d-flex flex-wrap gap-2">
                                <a href="<?= e(url('exporter/shipment_details.php?id=' . $shipment['shipment_id'])) ?>" class="btn btn-outline-secondary btn-sm">Details</a>
                                <?php if (in_array($user['role'], ['exporter', 'admin'], true)): ?>
                                    <a href="<?= e(url('exporter/edit_shipment.php?id=' . $shipment['shipment_id'])) ?>" class="btn btn-outline-primary btn-sm">Edit</a>
                                <?php endif; ?>
                                <a href="<?= e(url('tracking/track_shipment.php?shipment_id=' . $shipment['shipment_id'])) ?>" class="btn btn-outline-dark btn-sm">Track</a>
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
