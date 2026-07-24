-- EcoLot LK database schema
-- Created tables for Users, E-waste Requests, and Feedback

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

CREATE TABLE IF NOT EXISTS ewaste_requests (
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

CREATE TABLE IF NOT EXISTS feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    feedback_type VARCHAR(50) NOT NULL, -- 'Complaint', 'Suggestion', 'Compliment'
    request_id VARCHAR(50) DEFAULT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES ewaste_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
