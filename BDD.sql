/******************************************************
 * 1) Création de la base
 ******************************************************/
DROP DATABASE IF EXISTS gestion_equipe;
CREATE DATABASE gestion_equipe
    CHARACTER SET utf8mb4
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
    UNIQUE KEY(code)
);

INSERT INTO statut (code, libelle) VALUES
('ACT', 'Actif'),
('BLE', 'Blessé'),
('SUS', 'Suspendu'),
('ABS', 'Absent');



/******************************************************
 * 3) Table : poste
 ******************************************************/
CREATE TABLE poste (
    id_poste INT(11) NOT NULL AUTO_INCREMENT,
    code VARCHAR(10) NOT NULL,
    libelle VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_poste),
    UNIQUE KEY(code)
);

INSERT INTO poste (code, libelle) VALUES
('ATT', 'Attaquant'),
('DEF', 'Défenseur'),
('MIL', 'Milieu'),
('GAR', 'Gardien');



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

INSERT INTO joueur (nom, prenom, num_licence, date_naissance, taille_cm, poids_kg, id_statut)
VALUES
('Martin', 'Lucas', 'LIC001', '2002-04-12', 180, 75, 1),
('Dupont', 'Theo', 'LIC002', '2001-11-03', 175, 70, 1),
('Bernard', 'Alex', 'LIC003', '2003-02-08', 190, 82, 1),
('Robert', 'Maxime', 'LIC004', '2002-07-22', 172, 68, 2),
('Olivier', 'Hugo', 'LIC005', '2001-09-15', 185, 79, 1),
('Fontaine', 'Mathis', 'LIC006', '2004-01-29', 177, 72, 1),
('Petit', 'Nicolas', 'LIC007', '2000-05-14', 181, 73, 3),
('Morel', 'Antoine', 'LIC008', '2002-12-21', 178, 71, 1);



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

INSERT INTO commentaire (id_joueur, texte) VALUES
(1, 'Très bonne attitude à l’entraînement.'),
(1, 'Peut améliorer son jeu de tête.'),
(2, 'Excellent sur les derniers entraînements.'),
(3, 'Doit travailler sa vitesse.'),
(5, 'Très impliqué dans le collectif.'),
(6, 'Manque d’explosivité mais bonne vision de jeu.');



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

    PRIMARY KEY(id_match)
);

INSERT INTO matchs (date_heure, adversaire, lieu, score_equipe, score_adverse, resultat, etat)
VALUES
('2025-01-28 20:30:00', 'Montpellier SC', 'EXTERIEUR', 3, 1, 'VICTOIRE', 'JOUE'),
('2025-02-15 18:00:00', 'Toulouse FC', 'DOMICILE', NULL, NULL, NULL, 'A_PREPARER');



/******************************************************
 * 7) Table : participation
 ******************************************************/
CREATE TABLE participation (
    id_match INT(11) NOT NULL,
    id_joueur INT(11) NOT NULL,
    id_poste INT(11) DEFAULT NULL,
    role ENUM('TITULAIRE', 'REMPLACANT') NOT NULL,
    evaluation TINYINT(4) DEFAULT NULL,

    PRIMARY KEY(id_match, id_joueur),

    FOREIGN KEY(id_match) REFERENCES matchs(id_match) ON DELETE CASCADE,
    FOREIGN KEY(id_joueur) REFERENCES joueur(id_joueur) ON DELETE CASCADE,
    FOREIGN KEY(id_poste) REFERENCES poste(id_poste)
);

-- Match JOUE (id_match = 1)
INSERT INTO participation (id_match, id_joueur, id_poste, role, evaluation) VALUES
(1, 1, 1, 'TITULAIRE', 4),
(1, 2, 2, 'TITULAIRE', 5),
(1, 3, 3, 'TITULAIRE', 3),
(1, 5, 1, 'REMPLACANT', 4),
(1, 6, 2, 'REMPLACANT', 3);
