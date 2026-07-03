-- Script de création de la base de données et de la table Users
CREATE DATABASE IF NOT EXISTS tp_crud_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE tp_crud_php;

-- Suppression de la table si elle existe
DROP TABLE IF EXISTS users;

-- Création de la table users avec les colonnes nom et prenom
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('guest', 'admin', 'author', 'editor') NOT NULL DEFAULT 'guest',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email_search (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion de données de test (27 utilisateurs)
INSERT INTO users (nom, prenom, email, password, role) VALUES
('Dupont', 'Jean', 'admin@example.com', '$2y$10$ZCIaHR9DAeHxJtMDSIGzROV1GvbCWX4yf6rduETZeK3Bj/wlRByQW', 'admin'),
('Martin', 'Marie', 'author@example.com', '$2y$10$1rKunl7bFmD1nVbX5ak7Se7qUe.Rns7UQOf2U2UKh5/oVORsbh8iy', 'author'),
('Bernard', 'Pierre', 'editor@example.com', '$2y$10$94sRNctRQdr5GLfxH9Ogou6j0.83JgpVi.IvOQX0DQN1nWi.NBI.q', 'editor'),
('Durand', 'Luc', 'guest@example.com', '$2y$10$8nFdJaiJyxTplwDaGnSp/u3WPpqVWFv6Ix0wPjHJxBHBPQW.s5SvW', 'guest'),
('Lefebvre', 'Sophie', 'sophie.lefebvre@example.com', '$2y$10$YourHashedPasswordHere5', 'author'),
('Leclerc', 'Paul', 'paul.leclerc@example.com', '$2y$10$YourHashedPasswordHere6', 'editor'),
('Moreau', 'Anne', 'anne.moreau@example.com', '$2y$10$YourHashedPasswordHere7', 'guest'),
('Girard', 'Thomas', 'thomas.girard@example.com', '$2y$10$YourHashedPasswordHere8', 'admin'),
('André', 'Isabelle', 'isabelle.andre@example.com', '$2y$10$YourHashedPasswordHere9', 'author'),
('Blanc', 'Marc', 'marc.blanc@example.com', '$2y$10$YourHashedPasswordHere10', 'editor'),
('Garnier', 'Véronique', 'veronique.garnier@example.com', '$2y$10$YourHashedPasswordHere11', 'guest'),
('Muller', 'David', 'david.muller@example.com', '$2y$10$YourHashedPasswordHere12', 'author'),
('Laurent', 'Nathalie', 'nathalie.laurent@example.com', '$2y$10$YourHashedPasswordHere13', 'admin'),
('Simon', 'Jacques', 'jacques.simon@example.com', '$2y$10$YourHashedPasswordHere14', 'editor'),
('Michel', 'Catherine', 'catherine.michel@example.com', '$2y$10$YourHashedPasswordHere15', 'guest'),
('Garcia', 'Robert', 'robert.garcia@example.com', '$2y$10$YourHashedPasswordHere16', 'author'),
('Martinez', 'Claire', 'claire.martinez@example.com', '$2y$10$YourHashedPasswordHere17', 'admin'),
('Robinson', 'Christophe', 'christophe.robinson@example.com', '$2y$10$YourHashedPasswordHere18', 'editor'),
('Clark', 'Valérie', 'valerie.clark@example.com', '$2y$10$YourHashedPasswordHere19', 'guest'),
('Rodriguez', 'Alexandre', 'alexandre.rodriguez@example.com', '$2y$10$YourHashedPasswordHere20', 'author'),
('Lewis', 'Patricia', 'patricia.lewis@example.com', '$2y$10$YourHashedPasswordHere21', 'admin'),
('Lee', 'Sébastien', 'sebastien.lee@example.com', '$2y$10$YourHashedPasswordHere22', 'editor'),
('Walker', 'Danielle', 'danielle.walker@example.com', '$2y$10$YourHashedPasswordHere23', 'guest'),
('Hall', 'Franck', 'franck.hall@example.com', '$2y$10$YourHashedPasswordHere24', 'author'),
('Allen', 'Sandrine', 'sandrine.allen@example.com', '$2y$10$YourHashedPasswordHere25', 'admin'),
('Young', 'Hervé', 'herve.young@example.com', '$2y$10$YourHashedPasswordHere26', 'editor'),
('King', 'Michèle', 'michele.king@example.com', '$2y$10$YourHashedPasswordHere27', 'guest');
