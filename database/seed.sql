-- EcoLot LK demo seed data
-- Insert seed rows for users and ewaste_requests

-- Insert Default Public User (Password is 'password123' hashed)
INSERT INTO users (id, first_name, last_name, email, phone, address, password)
VALUES (1, 'Pawani', 'Perera', 'pawani@example.com', '071 234 5678', 'No. 45, Galle Road, Colombo 06', '$2y$10$R9h/lS.wW6gWk92cUXV9/uXU.yL/Hqy0bXFspuYlC.bT2f.H/xV9O')
ON DUPLICATE KEY UPDATE id=id;

-- Insert Mock Pickup Requests for User 1
INSERT INTO ewaste_requests (id, user_id, postal_code_area, collection_date, pickup_address, category, quantity, weight, condition_status, status)
VALUES 
('REQ-2024-00012', 1, 'Pannipitiya (10230)', '2024-05-20', 'No. 45, Galle Road, Colombo 06', 'office', 3, 8.50, 'working', 'Pending'),
('REQ-2024-00011', 1, 'Colombo 05', '2024-05-18', 'No. 45, Galle Road, Colombo 06', 'domestic', 1, 12.00, 'working', 'Completed'),
('REQ-2024-00010', 1, 'Colombo 05', '2024-05-10', 'No. 45, Galle Road, Colombo 06', 'domestic', 6, 4.00, 'working', 'Completed'),
('REQ-2024-00009', 1, 'Pannipitiya (10230)', '2024-05-05', 'No. 45, Galle Road, Colombo 06', 'domestic', 1, 2.00, 'working', 'Cancelled'),
('REQ-2024-00008', 1, 'Colombo 05', '2024-04-28', 'No. 45, Galle Road, Colombo 06', 'office', 4, 15.00, 'working', 'Completed')
ON DUPLICATE KEY UPDATE id=id;
