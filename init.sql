CREATE DATABASE IF NOT EXISTS tweet4x;

USE tweet4x;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NULL,
    date_of_birth DATE NULL,
    profile_picture VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tweets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    content TEXT,
    media_type VARCHAR(10),
    media_path VARCHAR(255),
    retweet_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (retweet_id) REFERENCES tweets(id)
);

CREATE TABLE IF NOT EXISTS mentions (
    tweet_id INT,
    user_id INT,
    PRIMARY KEY (tweet_id, user_id),
    FOREIGN KEY (tweet_id) REFERENCES tweets(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS hashtags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag VARCHAR(50) NOT NULL,
    tweet_id INT,
    FOREIGN KEY (tweet_id) REFERENCES tweets(id)
);

CREATE TABLE IF NOT EXISTS follows (
    follower_id INT,
    followed_id INT,
    PRIMARY KEY (follower_id, followed_id),
    FOREIGN KEY (follower_id) REFERENCES users(id),
    FOREIGN KEY (followed_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type ENUM('mention', 'retweet') NOT NULL,
    tweet_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tweet_id) REFERENCES tweets(id)
);


ALTER TABLE notifications
DROP FOREIGN KEY notifications_ibfk_2;

ALTER TABLE notifications
ADD CONSTRAINT notifications_ibfk_2
FOREIGN KEY (tweet_id) REFERENCES tweets(id) ON DELETE CASCADE;

ALTER TABLE users
ADD COLUMN descripcion TEXT NULL AFTER profile_picture;

CREATE TABLE IF NOT EXISTS registration_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Crear un usuario por defecto con una contraseña hash segura
INSERT INTO users (username, password) VALUES ('admin', '$2y$10$y1rnsLZ6LJf55R0lbqWfweRVPvktr8EmBWxRWG4iD/nsBQT./Reya');
