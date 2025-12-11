/******************************************************
 * 1) Création de la base
 ******************************************************/
CREATE DATABASE IF NOT EXISTS gestion_equipe
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE gestion_equipe;



/******************************************************
 * 2) Table : statut
 ******************************************************/
CREATE TABLE statut (
    id_statut INT(11) NOT NULL AUTO_INCREMENT,
    code VARCHAR(10) NOT NULL,
    libelle VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_statut),
    UNIQUE KEY (code)
);



/******************************************************
 * 3) Table : poste
 ******************************************************/
CREATE TABLE poste (
    id_poste INT(11) NOT NULL AUTO_INCREMENT,
    code VARCHAR(10) NOT NULL,
    libelle VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_poste),
    UNIQUE KEY (code)
);



/******************************************************
 * 4) Table : joueur
 ******************************************************/
CREATE TABLE joueur (
    id_joueur INT(11) NOT NULL AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    num_licence VARCHAR(20) UNIQUE,
    date_naissance DATE DEFAULT NULL,
    taille_cm INT(11) DEFAULT NULL,
    poids_kg DECIMAL(5,2) DEFAULT NULL,
    id_statut INT(11) NOT NULL,

    PRIMARY KEY (id_joueur),
    FOREIGN KEY (id_statut) REFERENCES statut(id_statut)
);



/******************************************************
 * 5) Table : commentaire
 ******************************************************/
CREATE TABLE commentaire (
    id_commentaire INT(11) NOT NULL AUTO_INCREMENT,
    id_joueur INT(11) NOT NULL,
    date_commentaire DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    texte TEXT DEFAULT NULL,

    PRIMARY KEY (id_commentaire),
    FOREIGN KEY (id_joueur) REFERENCES joueur(id_joueur)
);



/******************************************************
 * 6) Table : matchs
 ******************************************************/
CREATE TABLE matchs (
    id_match INT(11) NOT NULL AUTO_INCREMENT,
    date_heure DATETIME NOT NULL,
    adversaire VARCHAR(100) NOT NULL,
    lieu ENUM('DOMICILE', 'EXTERIEUR') NOT NULL,
    score_equipe INT(11) DEFAULT NULL,
    score_adverse INT(11) DEFAULT NULL,
    resultat ENUM('VICTOIRE', 'DEFAITE', 'NUL') DEFAULT NULL,
    etat ENUM('A_PREPARER', 'PREPARE', 'JOUE') DEFAULT 'A_PREPARER',

    PRIMARY KEY (id_match)
);



/******************************************************
 * 7) Table : participation
 ******************************************************/
CREATE TABLE participation (
    id_match INT(11) NOT NULL,
    id_joueur INT(11) NOT NULL,
    id_poste INT(11) DEFAULT NULL,
    role ENUM('TITULAIRE', 'REMPLACANT') NOT NULL,
    evaluation TINYINT(4) DEFAULT NULL,

    PRIMARY KEY (id_match, id_joueur),

    FOREIGN KEY (id_match) REFERENCES matchs(id_match) ON DELETE CASCADE,
    FOREIGN KEY (id_joueur) REFERENCES joueur(id_joueur) ON DELETE CASCADE,
    FOREIGN KEY (id_poste) REFERENCES poste(id_poste)
);
