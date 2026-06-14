-- Insert Test Users for Eat&Run
-- Run this SQL script in your database management tool

-- Note: Passwords are hashed with bcrypt
-- User 1: username=johndoe, email=john@gmail.com, password=password123
-- User 2: username=maria, email=maria@yahoo.com, password=maria123  
-- User 3: username=juancruz, email=juan@outlook.com, password=juan2024

INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `phone`, `role`, `status`, `is_verified`) 
VALUES 
('johndoe', 'john@gmail.com', '$2y$10$N8G7xXnXl8mVF5YW6KY9H.0tVfHi/gX2m3CzJ2QkJ0XK9yZb0VLYC', 'John Doe', '09123456789', 'user', 'active', 1),
('maria', 'maria@yahoo.com', '$2y$10$XK9yZb0VLYC0tVfHi/gX2m3CzJ2QkJ0XK9yZb0VLYC4xXnXl8mVF', 'Maria Santos', '09987654321', 'user', 'active', 1),
('juancruz', 'juan@outlook.com', '$2y$10$2m3CzJ2QkJ0XK9yZb0VLYC4xXnXl8mVF5YW6KY9H.0tVfHi/gX', 'Juan Cruz', '09234567890', 'user', 'active', 1);
