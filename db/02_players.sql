SET NAMES utf8mb4;

USE appDB;

CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL,
    queue_order BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_players_queue_order (queue_order),
    INDEX idx_players_status_order (status, queue_order)
);

INSERT INTO players (name, status, queue_order) VALUES
('Player1', 'active', 1),
('Player2', 'active', 2),
('Bot1', 'active', 3),
('Ilya', 'waiting', 4),
('Kabir', 'waiting', 5);
