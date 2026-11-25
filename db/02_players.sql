SET NAMES utf8mb4;

USE appDB;

CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL
);

INSERT INTO players (name, status) VALUES
('Player1', 'active'),
('Player2', 'active'),
('Bot1', 'active'),
('Ilya', 'waiting'),
('Kabir', 'waiting');
