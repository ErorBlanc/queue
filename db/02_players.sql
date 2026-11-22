SET NAMES utf8mb4;

USE appDB;

CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL,
    is_bot BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO players (name, status, is_bot) VALUES
('Player1', 'active', FALSE),
('Player2', 'active', FALSE),
('Bot1', 'active', TRUE),
('Ilya', 'waiting', FALSE),
('Kabir', 'waiting', FALSE);
