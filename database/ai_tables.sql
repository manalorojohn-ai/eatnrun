-- Chat History Table
CREATE TABLE chat_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    user_message TEXT NOT NULL,
    ai_response TEXT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- User Preferences Table
ALTER TABLE users 
ADD COLUMN dietary_preferences TEXT,
ADD COLUMN favorite_cuisines TEXT,
ADD COLUMN ai_personalization BOOLEAN DEFAULT TRUE;

-- AI Analytics Table
CREATE TABLE ai_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    interaction_type VARCHAR(50),
    user_id INT,
    query_type VARCHAR(50),
    response_time FLOAT,
    satisfaction_rating INT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- AI Rate Limiting Table
CREATE TABLE ai_rate_limits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    request_count INT DEFAULT 0,
    last_reset DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
); 