-- Create database
CREATE DATABASE IF NOT EXISTS rtims_db;
USE rtims_db;

-- Users table (Public drivers)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    licence_no VARCHAR(50) UNIQUE NOT NULL,
    plate_no VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Traffic Officers table
CREATE TABLE officers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    badge_number VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admins table
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Offences table (predefined offences with fines)
CREATE TABLE offences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    keyword VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    amount_tzs DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Incidents table (recorded offences)
CREATE TABLE incidents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    offence_id INT NOT NULL,
    officer_id INT NOT NULL,
    location TEXT NOT NULL,
    image_path VARCHAR(255),
    control_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('Pending', 'Paid', 'Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (offence_id) REFERENCES offences(id) ON DELETE CASCADE,
    FOREIGN KEY (officer_id) REFERENCES officers(id) ON DELETE CASCADE
);

-- Insert sample offences
INSERT INTO offences (keyword, description, amount_tzs) VALUES
('overspeeding', 'Driving above the speed limit', 50000.00),
('wrong lane', 'Driving on the wrong lane', 30000.00),
('wrong turn', 'Making an illegal turn', 25000.00),
('parking', 'Illegal parking', 15000.00),
('seatbelt', 'Not wearing seatbelt', 20000.00),
('phone', 'Using phone while driving', 40000.00),
('drunk driving', 'Driving under influence of alcohol', 100000.00),
('no license', 'Driving without valid license', 80000.00),
('red light', 'Running red light', 45000.00),
('no insurance', 'Driving without insurance', 60000.00);

-- Insert sample admin
INSERT INTO admins (name, username, password) VALUES
('System Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample officer
INSERT INTO officers (name, username, password, badge_number) VALUES
('Officer John Mwalimu', 'officer1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'TPF001');

-- Insert sample user
INSERT INTO users (name, licence_no, plate_no, password) VALUES
('John Doe', 'DL123456789', 'T123ABC', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
