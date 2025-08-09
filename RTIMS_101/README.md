# Road Traffic Incident Management System (RTIMS)

A comprehensive web-based system for managing road traffic incidents in Tanzania.

## Features

### 🚗 Public Users (Drivers)
- Register with driving licence and car plate number
- View assigned traffic offences
- See offence details including control numbers and fines in TZS
- Track payment status

### 👮‍♂️ Traffic Officers
- Record traffic incidents with location and evidence photos
- Smart offence matching system
- Assign incidents to drivers by licence number
- View recorded incident history

### ⚙️ System Administrators
- Manage users, officers, and offences
- View system-wide statistics
- Export incident reports
- Control system configurations

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP (Plain, no framework)
- **Database**: MySQL
- **File Upload**: PHP file handling for evidence images

## Installation

1. **Database Setup**
   \`\`\`bash
   # Import the database schema
   mysql -u root -p < database/rtims_schema.sql
   \`\`\`

2. **Configuration**
   - Update database credentials in `config/database.php`
   - Ensure `uploads/` directory has write permissions

3. **Web Server**
   - Place files in your web server directory
   - Ensure PHP and MySQL are running

## Default Login Credentials

### Administrator
- **Username**: admin
- **Password**: password

### Traffic Officer
- **Username**: officer1
- **Password**: password

### Driver (Sample)
- **Licence Number**: DL123456789
- **Password**: password

## Key Features

### 🔍 Smart Offence Matching
The system automatically suggests matching offences based on officer input using keyword and description matching.

### 🏷️ Tanzanian Control Numbers
Each incident gets a unique control number in the format:
`GOV-TZ-RTIMS-YYYY-XXXXX`

### 💰 TZS Currency Support
All fines are displayed in Tanzanian Shillings (TZS) with proper formatting.

### 📱 Responsive Design
Mobile-friendly interface for use on various devices.

### 🖼️ Evidence Management
Support for uploading and managing incident evidence photos.

## File Structure

\`\`\`
rtims/
├── admin/              # Admin dashboard and management
├── api/               # API endpoints
├── assets/            # CSS, JS, and static files
├── auth/              # Authentication system
├── config/            # Database configuration
├── database/          # SQL schema and setup
├── officer/           # Officer dashboard and tools
├── uploads/           # Uploaded evidence images
├── user/              # Driver dashboard
└── index.php          # Main login page
\`\`\`

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- File upload validation and security
- Session-based authentication
- Role-based access control

## Contributing

This is a final year project for educational purposes. Feel free to fork and enhance for your own learning.

## License

Educational use only. Not for commercial deployment without proper security audit.
