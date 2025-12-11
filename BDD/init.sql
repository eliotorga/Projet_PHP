-- Création de la base
CREATE DATABASE IF NOT EXISTS gestion_equipe;
USE gestion_equipe;

-- 1. Table JOUEUR
CREATE TABLE joueur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    numero_licence VARCHAR(50) UNIQUE NOT NULL,
    date_naissance DATE NOT NULL,
    taille_cm INT NOT NULL,
    poids_kg INT NOT NULL,
    statut ENUM('Actif', 'Blessé', 'Suspendu', 'Absent') DEFAULT 'Actif',
    -- On garde le chemin photo si besoin, sinon tu peux l'enlever
    photo VARCHAR(255) NULL 
) ENGINE=InnoDB;

-- 2. Table MATCH (Attention aux backticks `` obligatoires ici)
CREATE TABLE `match` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_heure DATETIME NOT NULL,
    nom_adversaire VARCHAR(100) NOT NULL,
    lieu ENUM('Domicile', 'Extérieur') NOT NULL,
    -- Le score est NULL tant que le match n'est pas joué
    score_equipe INT NULL, 
    score_adversaire INT NULL,
    -- Statut calculé ou explicite pour savoir si le match est passé
    est_termine BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB;

-- 3. Table COMMENTAIRE
-- Permet à l'entraîneur d'ajouter plusieurs notes datées sur un joueur spécifique
CREATE TABLE commentaire (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contenu TEXT NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    joueur_id INT NOT NULL,
    
    -- Liaison avec le joueur
    CONSTRAINT fk_commentaire_joueur 
        FOREIGN KEY (joueur_id) 
        REFERENCES joueur(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB;