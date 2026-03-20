# Export Logistics System

A comprehensive web-based logistics management system designed to streamline export operations, customs clearance, and shipment tracking.

## Overview

The Export Logistics System is a multi-role application that facilitates seamless communication and workflow management between exporters, logistics companies, and customs officers. It provides an integrated platform for managing shipments, documents, compliance checks, and real-time tracking.

## Features

### User Roles
- **Admin**: System administration, user management, and reporting
- **Exporter**: Create shipments, upload documents, and track status
- **Logistics Company**: Update shipment status and manage logistics
- **Customs Officer**: Review documents and approve shipments

### Key Features
- **Shipment Management**: Create, edit, and track shipments
- **Document Management**: Upload, store, and manage shipping documents
- **Compliance Checking**: Automated compliance validation for required documents
- **Status Tracking**: Real-time shipment status updates
- **Notifications**: User notifications for shipment updates
- **User Management**: Role-based access control
- **Reporting**: Analytics and reporting for administrators

## Project Structure

```
export-logistics-system/
├── admin/                      # Admin dashboard and management pages
├── assets/                     # CSS, JavaScript, and static assets
├── config/                     # Database configuration
├── customs/                    # Customs officer functionality
├── database/                   # Database files and schemas
├── documents/                  # Document management
├── exporter/                   # Exporter functionality
├── includes/                   # Shared PHP functions and utilities
├── logistics/                  # Logistics company functionality
├── tracking/                   # Shipment tracking
├── uploads/                    # Uploaded files storage
├── index.php                   # Main entry point
├── login.php                   # User authentication
├── register.php                # User registration
├── logout.php                  # User logout
└── check_compliance.php        # Compliance checking page
```

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: HTML, CSS, JavaScript, Bootstrap
- **Server**: Apache (via XAMPP)

## Installation

### Prerequisites
- XAMPP (or equivalent Apache/MySQL setup)
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Setup Instructions

1. Clone the repository:
   ```bash
   git clone https://github.com/Dharmang07/export_logistic_system.git
   ```

2. Place files in `htdocs` directory:
   ```bash
   cp -r export_logistic_system c:/xampp/htdocs/
   ```

3. Import database schema:
   - Open phpMyAdmin
   - Create a new database (e.g., `export_logistics`)
   - Import `config/schema.php` or the SQL file

4. Update database configuration in `config/db.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USERNAME', 'root');
   define('DB_PASSWORD', '');
   define('DB_NAME', 'export_logistics');
   ```

5. Start Apache and MySQL services via XAMPP

6. Access the application:
   ```
   http://localhost/export-logistics-system/
   ```

## Database Schema

The system uses the following main tables:
- `users`: User accounts with role-based access
- `shipments`: Shipment records and status
- `documents`: Document metadata and storage
- `compliance_checks`: Compliance status tracking
- `notifications`: User notifications
- Additional tables for audit and analytics

## Usage

### For Exporters
1. Register and login
2. Create new shipments
3. Upload required documents
4. Check compliance status
5. Track shipment progress

### For Logistics Companies
1. Login to dashboard
2. View assigned shipments
3. Update shipment status
4. View transit details

### For Customs Officers
1. Login to dashboard
2. Review uploaded documents
3. Approve or request modifications
4. Provide clearance

### For Administrators
1. Manage user accounts
2. View system reports
3. Monitor compliance
4. Configure system settings

## Security Features

- Role-based access control (RBAC)
- SQL injection prevention (prepared statements)
- XSS protection (HTML escaping)
- CSRF protection
- Secure session management
- Password hashing

## API-Style Functions

Key helper functions in `includes/functions.php`:
- `login_user()`: User authentication
- `redirect_to()`: Secure redirects
- `require_login()`: Access control
- `save_uploaded_file()`: Secure file uploads
- `run_compliance_check()`: Compliance validation
- `notify_users()`: Notification system

## Contributing

1. Create a feature branch
2. Make your changes
3. Submit a pull request
4. Follow the existing code style and conventions

## Support

For issues or questions, please contact the development team or open an issue on GitHub.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Author

Dharman G

## Acknowledgments

- Built with Bootstrap for responsive UI
- Uses MySQL for data persistence
- Developed using PHP best practices and modern web standards
