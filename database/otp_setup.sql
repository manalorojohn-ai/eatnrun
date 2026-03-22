-- Add is_verified column to users table if it doesn't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS is_verified TINYINT(1) DEFAULT 0;

-- Create table for storing OTP codes if it doesn't exist
CREATE TABLE IF NOT EXISTS email_verification (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB; 