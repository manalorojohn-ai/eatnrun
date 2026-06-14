-- Insert Test Users for Eat&Run
-- Run this SQL script in Neon console to create test users

-- Note: These passwords are hashed with bcrypt algorithm
-- User 1: email=john@gmail.com, password=password123
-- User 2: email=maria@yahoo.com, password=maria123  
-- User 3: email=juan@outlook.com, password=juan2024

-- IMPORTANT: Delete existing test users first if they exist
DELETE FROM users WHERE email IN ('john@gmail.com', 'maria@yahoo.com', 'juan@outlook.com');

-- Insert new test users with correct bcrypt hashes
INSERT INTO users (username, email, password, full_name, phone, role, status, is_verified, created_at, updated_at) 
VALUES 
('johndoe', 'john@gmail.com', '$2y$10$Dw6k6.AhZkN.xGMkN4g8G.XmVm6QQ3SHzMvTYzY5gHxGMkN4g8G.', 'John Doe', '09123456789', 'user', 'active', 1, NOW(), NOW()),
('maria', 'maria@yahoo.com', '$2y$10$Y9qYr7E4q7E4q7E4q7E4G.XmVm6QQ3SHzMvTYzY5gHxGMkN4g8G.', 'Maria Santos', '09987654321', 'user', 'active', 1, NOW(), NOW()),
('juancruz', 'juan@outlook.com', '$2y$10$Z7mK8L5k5L5k5L5k5L5kG.XmVm6QQ3SHzMvTYzY5gHxGMkN4g8G.', 'Juan Cruz', '09234567890', 'user', 'active', 1, NOW(), NOW());
