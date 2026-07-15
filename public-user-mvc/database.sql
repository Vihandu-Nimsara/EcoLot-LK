-- Create Database if not exists
CREATE DATABASE IF NOT EXISTS ecolotlk;
USE ecolotlk;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Pickup Requests Table
CREATE TABLE IF NOT EXISTS requests (
    id VARCHAR(50) PRIMARY KEY,
    user_id INT NOT NULL,
    postal_code_area VARCHAR(100) NOT NULL,
    collection_date DATE NOT NULL,
    pickup_address TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    other_category VARCHAR(100) DEFAULT NULL,
    quantity INT NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    condition_status VARCHAR(50) NOT NULL,
    note TEXT DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'Pending', -- 'Pending', 'Completed', 'Cancelled'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Feedback / Complaints Table
CREATE TABLE IF NOT EXISTS feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    feedback_type VARCHAR(50) NOT NULL, -- 'Complaint', 'Suggestion', 'Compliment'
    request_id VARCHAR(50) DEFAULT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Default User (Password is 'password123' hashed, or plain text for demo: let's use a standard password)
INSERT INTO users (id, first_name, last_name, email, phone, address, password)
VALUES (1, 'Pawani', 'Perera', 'pawani@example.com', '071 234 5678', 'No. 45, Galle Road, Colombo 06', '$2y$10$R9h/lS.wW6gWk92cUXV9/uXU.yL/Hqy0bXFspuYlC.bT2f.H/xV9O')
ON DUPLICATE KEY UPDATE id=id;

-- Insert Mock Pickup Requests for User 1
INSERT INTO requests (id, user_id, postal_code_area, collection_date, pickup_address, category, quantity, weight, condition_status, status)
VALUES 
('REQ-2024-00012', 1, 'Pannipitiya (10230)', '2024-05-20', 'No. 45, Galle Road, Colombo 06', 'office', 3, 8.50, 'working', 'Pending'),
('REQ-2024-00011', 1, 'Colombo 05', '2024-05-18', 'No. 45, Galle Road, Colombo 06', 'domestic', 1, 12.00, 'working', 'Completed'),
('REQ-2024-00010', 1, 'Colombo 05', '2024-05-10', 'No. 45, Galle Road, Colombo 06', 'domestic', 6, 4.00, 'working', 'Completed'),
('REQ-2024-00009', 1, 'Pannipitiya (10230)', '2024-05-05', 'No. 45, Galle Road, Colombo 06', 'domestic', 1, 2.00, 'working', 'Cancelled'),
('REQ-2024-00008', 1, 'Colombo 05', '2024-04-28', 'No. 45, Galle Road, Colombo 06', 'office', 4, 15.00, 'working', 'Completed')
ON DUPLICATE KEY UPDATE id=id;
