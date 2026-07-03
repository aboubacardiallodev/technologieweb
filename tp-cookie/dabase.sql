-- Et la table produits :

-- Script de création de la base de données et de la table tp_cookie
CREATE DATABASE IF NOT EXISTS tp_cookie CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE tp_cookie;

-- Suppression de la table si elle existe
DROP TABLE IF EXISTS produits;


CREATE TABLE produits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100),
    prix DECIMAL(6,2),
    stock INT NOT NULL DEFAULT 0
);

INSERT INTO produits (nom, prix, stock) VALUES
('Casque Bluetooth', 79.99, 10),
('Clavier mécanique', 129.90, 8),
('Souris gamer', 59.99, 0),
('Écran 27" 144Hz', 299.00, 5),
('Tapis de souris XXL', 24.90, 20);