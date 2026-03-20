<?php
declare(strict_types=1);

function bootstrap_database(string $host, string $user, string $password, string $databaseName): mysqli
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $databaseName)) {
        throw new RuntimeException('Database name contains unsupported characters.');
    }

    $conn = new mysqli($host, $user, $password);
    $conn->set_charset('utf8mb4');

    $quotedDatabaseName = '`' . str_replace('`', '``', $databaseName) . '`';
    $conn->query("CREATE DATABASE IF NOT EXISTS {$quotedDatabaseName} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db($databaseName);

    return $conn;
}

function ensure_database_schema(mysqli $conn): void
{
    $statements = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(30) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_users_email (email),
            KEY idx_users_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS shipments (
            shipment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            exporter_id INT UNSIGNED NOT NULL,
            product_name VARCHAR(150) NOT NULL,
            destination_country VARCHAR(120) NOT NULL,
            quantity INT UNSIGNED NOT NULL,
            shipping_method VARCHAR(60) NOT NULL,
            shipment_status VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (shipment_id),
            KEY idx_shipments_exporter (exporter_id),
            KEY idx_shipments_status (shipment_status),
            KEY idx_shipments_created_at (created_at),
            CONSTRAINT fk_shipments_exporter
                FOREIGN KEY (exporter_id) REFERENCES users(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS documents (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            shipment_id INT UNSIGNED NOT NULL,
            document_type VARCHAR(100) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(40) NOT NULL,
            PRIMARY KEY (id),
            KEY idx_documents_shipment (shipment_id),
            KEY idx_documents_uploaded_at (uploaded_at),
            CONSTRAINT fk_documents_shipment
                FOREIGN KEY (shipment_id) REFERENCES shipments(shipment_id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS compliance_checks (
            shipment_id INT UNSIGNED NOT NULL,
            missing_documents TEXT NOT NULL,
            compliance_status VARCHAR(40) NOT NULL,
            PRIMARY KEY (shipment_id),
            KEY idx_compliance_status (compliance_status),
            CONSTRAINT fk_compliance_shipment
                FOREIGN KEY (shipment_id) REFERENCES shipments(shipment_id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            message TEXT NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_notifications_user (user_id),
            KEY idx_notifications_status (status),
            KEY idx_notifications_created_at (created_at),
            CONSTRAINT fk_notifications_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    foreach ($statements as $statement) {
        $conn->query($statement);
    }

    seed_default_admin_user($conn);
}

function seed_default_admin_user(mysqli $conn): void
{
    $result = $conn->query('SELECT COUNT(*) AS total FROM users');
    $userCount = (int) ($result->fetch_assoc()['total'] ?? 0);

    if ($userCount > 0) {
        return;
    }

    $name = 'System Administrator';
    $email = 'admin@eldms.local';
    $passwordHash = password_hash('Admin@12345', PASSWORD_DEFAULT);
    $role = 'admin';

    $stmt = $conn->prepare('INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->bind_param('ssss', $name, $email, $passwordHash, $role);
    $stmt->execute();
}
