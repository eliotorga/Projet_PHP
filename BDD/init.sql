-- Création de la base de données
CREATE DATABASE IF NOT EXISTS gestion_equipe;
USE gestion_equipe;

-- Table utilisateurs
CREATE TABLE utilisateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table joueurs
CREATE TABLE joueurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prenom VARCHAR(50) NOT NULL,
    nom VARCHAR(50) NOT NULL,
    date_naissance DATE,
    taille INT,
    poids DECIMAL(5,2),
    poste_prefere VARCHAR(20),
    est_actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table matchs
CREATE TABLE matchs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    adversaire VARCHAR(100) NOT NULL,
    date_match DATETIME NOT NULL,
    lieu VARCHAR(100),
    resultat ENUM('victoire', 'defaite', 'nul') DEFAULT NULL,
    score_equipe INT,
    score_adversaire INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table compositions (joueurs par match)
CREATE TABLE compositions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    match_id INT,
    joueur_id INT,
    poste VARCHAR(20),
    est_titulaire BOOLEAN DEFAULT TRUE,
    evaluation INT,
    commentaires TEXT,
    FOREIGN KEY (match_id) REFERENCES matchs(id) ON DELETE CASCADE,
    FOREIGN KEY (joueur_id) REFERENCES joueurs(id) ON DELETE CASCADE
);

-- Insertion d'un utilisateur admin (mot de passe: admin123)
INSERT INTO utilisateurs (username, password_hash) 
VALUES ('admin', '$2y$10$Vq5z5qYq5z5qYq5z5qYq5u1w2e3r4t5y6u7i8o9p0');