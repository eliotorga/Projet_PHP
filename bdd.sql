-- ============================
--   CREATION BASE DE DONNEES
-- ============================

CREATE DATABASE IF NOT EXISTS projet_equipe
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE projet_equipe;

-- ============================
--       TABLE JOUEUR
-- ============================

CREATE TABLE joueur (
    id_joueur INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    num_licence VARCHAR(30),
    poids_kg INT,
    taille_cm INT,
    date_naissance DATE,
    statut ENUM('actif', 'blessé', 'suspendu', 'retiré') DEFAULT 'actif',
    num_joueur INT
);

-- ============================
--       TABLE POSTE
-- ============================

CREATE TABLE poste (
    id_poste INT AUTO_INCREMENT PRIMARY KEY,
    nom_poste VARCHAR(50) NOT NULL
);

-- ============================
--    TABLE RELATION AVOIR
--   (un joueur a 1 poste)
-- ============================

CREATE TABLE avoir (
    id_joueur INT NOT NULL,
    id_poste INT NOT NULL,
    PRIMARY KEY (id_joueur),
    CONSTRAINT fk_avoir_joueur FOREIGN KEY (id_joueur)
        REFERENCES joueur(id_joueur) ON DELETE CASCADE,
    CONSTRAINT fk_avoir_poste FOREIGN KEY (id_poste)
        REFERENCES poste(id_poste) ON DELETE CASCADE
);

-- ============================
--         TABLE MATCH
-- ============================

CREATE TABLE `match` (
    id_match INT AUTO_INCREMENT PRIMARY KEY,
    date_heure DATETIME NOT NULL,
    equipe_adverse VARCHAR(100) NOT NULL,
    lieu VARCHAR(100),
    resultat ENUM('G', 'P', 'N') NULL
);

-- ============================
--    TABLE PARTICIPER
-- (joueur participe au match)
-- ============================

CREATE TABLE participer (
    id_joueur INT NOT NULL,
    id_match INT NOT NULL,
    titularisation ENUM('titulaire', 'remplaçant') NOT NULL,
    commentaire TEXT NULL,
    note TINYINT NULL,
    PRIMARY KEY (id_joueur, id_match),
    
    CONSTRAINT fk_participer_joueur FOREIGN KEY (id_joueur)
        REFERENCES joueur(id_joueur) ON DELETE CASCADE,

    CONSTRAINT fk_participer_match FOREIGN KEY (id_match)
        REFERENCES `match`(id_match) ON DELETE CASCADE
);
