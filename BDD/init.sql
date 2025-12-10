-- ===========================================================
--  BASE DE DONNÉES : Gestion Sportive
--  Conforme au MCD fourni + corrections
-- ===========================================================

CREATE DATABASE IF NOT EXISTS gestion_sportive
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_general_ci;

USE gestion_sportive;

-- ===========================================================
-- TABLE : JOUEUR
-- ===========================================================

CREATE TABLE joueur (
    id_joueur INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    num_licence VARCHAR(50) UNIQUE,
    poids_kg DECIMAL(5,2),
    date_naissance DATE,
    taille_cm SMALLINT UNSIGNED,
    statut ENUM('actif', 'inactif') DEFAULT 'actif',
    numero_joueur INT,
    poste_favori VARCHAR(50)
) ENGINE=InnoDB;

-- ===========================================================
-- TABLE : MATCH
-- ===========================================================

CREATE TABLE match_sportif (
    id_match INT AUTO_INCREMENT PRIMARY KEY,
    date_heure DATETIME NOT NULL,
    equipe_adverse VARCHAR(100) NOT NULL,
    lieu VARCHAR(100),
    resultat ENUM('gagne', 'perdu', 'nul') DEFAULT NULL
) ENGINE=InnoDB;

-- ===========================================================
-- TABLE : PARTICIPER (Feuille de match)
-- ===========================================================
-- Clé primaire = (id_match, id_joueur)
-- ===========================================================

CREATE TABLE participer (
    id_match INT NOT NULL,
    id_joueur INT NOT NULL,
    titularisation TINYINT(1) NOT NULL,   -- 1 = titulaire / 0 = remplaçant
    note TINYINT UNSIGNED DEFAULT NULL,   -- note individuelle (0-10)
    poste_terrain VARCHAR(50) NOT NULL,

    PRIMARY KEY (id_match, id_joueur),

    CONSTRAINT fk_participer_match
        FOREIGN KEY (id_match) REFERENCES match_sportif(id_match)
        ON DELETE CASCADE,

    CONSTRAINT fk_participer_joueur
        FOREIGN KEY (id_joueur) REFERENCES joueur(id_joueur)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Index utiles pour statistiques
CREATE INDEX idx_participer_joueur ON participer(id_joueur);
CREATE INDEX idx_participer_match ON participer(id_match);

-- ===========================================================
-- TABLE : COMMENTAIRE_JOUEUR
-- ===========================================================

CREATE TABLE commentaire_joueur (
    id_commentaire INT AUTO_INCREMENT PRIMARY KEY,
    id_joueur INT NOT NULL,
    texte TEXT NOT NULL,
    date_commentaire DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_commentaire_joueur
        FOREIGN KEY (id_joueur) REFERENCES joueur(id_joueur)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Index utile
CREATE INDEX idx_commentaire_joueur ON commentaire_joueur(id_joueur);
