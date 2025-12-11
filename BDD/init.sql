-- Création de la base de données
CREATE DATABASE gestion_equipe;
USE gestion_equipe;

-- Table statut (avec données existantes)
CREATE TABLE statut (
    id_statut INT PRIMARY KEY,
    code VARCHAR(10) NOT NULL,
    libelle VARCHAR(50) NOT NULL
);

-- Table poste
CREATE TABLE poste (
    id_poste INT PRIMARY KEY,
    code VARCHAR(10) NOT NULL,
    libelle VARCHAR(50) NOT NULL
);

-- Table joueur
CREATE TABLE joueur (
    id_joueur INT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    num_licence VARCHAR(20) UNIQUE,
    date_naissance DATE,
    taille_cm INT,
    poids_kg DECIMAL(5,2),
    id_statut INT,
    FOREIGN KEY (id_statut) REFERENCES statut(id_statut)
);

-- Table match
CREATE TABLE match (
    id_match INT PRIMARY KEY,
    date_heure DATETIME NOT NULL,
    adversaire VARCHAR(100) NOT NULL,
    lieu VARCHAR(100),
    score_equipe INT,
    score_adverse INT,
    resultat VARCHAR(10),
    etat VARCHAR(20)
);

-- Table commentaire
CREATE TABLE commentaire (
    id_commentaire INT PRIMARY KEY,
    id_joueur INT NOT NULL,
    date_commentaire DATETIME NOT NULL,
    texte TEXT,
    FOREIGN KEY (id_joueur) REFERENCES joueur(id_joueur)
);

-- Table participation (table de liaison match-joueur)
CREATE TABLE participation (
    id_match INT NOT NULL,
    id_joueur INT NOT NULL,
    id_poste INT,
    role VARCHAR(50),
    evaluation VARCHAR(10),
    PRIMARY KEY (id_match, id_joueur),
    FOREIGN KEY (id_match) REFERENCES match(id_match),
    FOREIGN KEY (id_joueur) REFERENCES joueur(id_joueur),
    FOREIGN KEY (id_poste) REFERENCES poste(id_poste)
);

-- Insertion des données existantes pour statut
INSERT INTO statut (id_statut, code, libelle) VALUES
(1, 'ACTIF', 'Actif'),
(2, 'BLESSE', 'Blessé'),
(3, 'SUSPENDU', 'Suspendu'),
(4, 'ABSENT', 'Absent');