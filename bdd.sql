-- Créer la base et l'utiliser
CREATE DATABASE IF NOT EXISTS projet_PHP;
USE projet_equipe;

-- ============================================
-- TABLE JOUEUR
-- ============================================

CREATE TABLE joueur (
    id_joueur INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    num_licence VARCHAR(50),
    poids_kg DECIMAL(5,2),
    date_naissance DATE,
    taille_cm INT,
    statut VARCHAR(50),
    numero_joueur INT,
    poste_favori VARCHAR(100)
) ENGINE=InnoDB;


-- ============================================
-- TABLE MATCHS (nom corrigé car match est un mot réservé)
-- ============================================

CREATE TABLE matchs (
    id_match INT AUTO_INCREMENT PRIMARY KEY,
    date_heure DATETIME NOT NULL,
    equipe_adverse VARCHAR(100) NOT NULL,
    lieu VARCHAR(100),
    resultat VARCHAR(20)
) ENGINE=InnoDB;


-- ============================================
-- TABLE PARTICIPER (association n-n)
-- ============================================

CREATE TABLE participer (
    id_joueur INT NOT NULL,
    id_match INT NOT NULL,
    titularisation BOOLEAN NOT NULL,
    note DECIMAL(3,1),
    nom_poste VARCHAR(100),

    PRIMARY KEY(id_joueur, id_match),

    CONSTRAINT fk_participer_joueur
        FOREIGN KEY (id_joueur) REFERENCES joueur(id_joueur)
        ON DELETE CASCADE,

    CONSTRAINT fk_participer_match
        FOREIGN KEY (id_match) REFERENCES matchs(id_match)
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- ============================================
-- TABLE COMMENTAIRE_JOUEUR
-- ============================================

CREATE TABLE commentaire_joueur (
    id_commentaire INT AUTO_INCREMENT PRIMARY KEY,
    id_joueur INT NOT NULL,
    texte TEXT NOT NULL,
    date_commentaire DATE NOT NULL,

    CONSTRAINT fk_commentaire_joueur
        FOREIGN KEY (id_joueur) REFERENCES joueur(id_joueur)
        ON DELETE CASCADE
) ENGINE=InnoDB;
