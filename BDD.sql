
DROP DATABASE IF EXISTS gestion_equipe;
CREATE DATABASE gestion_equipe
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;
USE gestion_equipe;

CREATE TABLE statut (
    id_statut INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) UNIQUE NOT NULL,
    libelle VARCHAR(50) NOT NULL
);

INSERT INTO statut (code, libelle) VALUES
('ACT','Actif'),
('BLE','Blessé'),
('SUS','Suspendu'),
('ABS','Absent');

CREATE TABLE poste (
    id_poste INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) UNIQUE NOT NULL,
    libelle VARCHAR(50) NOT NULL
);

INSERT INTO poste (code, libelle) VALUES
('GAR','Gardien'),
('DEF','Défenseur'),
('MIL','Milieu'),
('ATT','Attaquant'),
('REM','Remplaçant');

CREATE TABLE joueur (
    id_joueur INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    num_licence VARCHAR(20) UNIQUE NOT NULL,
    date_naissance DATE NOT NULL,
    taille_cm INT NOT NULL,
    poids_kg DECIMAL(5,2) NOT NULL,
    id_statut INT NOT NULL,
    FOREIGN KEY (id_statut) REFERENCES statut(id_statut)
);

INSERT INTO joueur VALUES
(NULL,'Fincan','William','LIC001','2003-06-17',188,81,1),
(NULL,'Torga','Elio','LIC002','2005-02-14',176,70,1),
(NULL,'Martin','Lucas','LIC003','2002-04-12',180,75,1),
(NULL,'Dupont','Theo','LIC004','2001-11-03',175,70,1),
(NULL,'Bernard','Alex','LIC005','2003-02-08',190,82,1),
(NULL,'Olivier','Hugo','LIC006','2001-09-15',185,79,1),
(NULL,'Fontaine','Mathis','LIC007','2004-01-29',177,72,1),
(NULL,'Morel','Antoine','LIC008','2002-12-21',178,71,1),
(NULL,'Leroy','Maxime','LIC009','2001-08-09',174,69,1),
(NULL,'Roux','Julien','LIC010','2002-10-18',182,77,1),
(NULL,'Garnier','Paul','LIC011','2003-03-11',179,74,1),
(NULL,'Chevalier','Leo','LIC012','2004-06-01',176,68,1),
(NULL,'Baron','Tom','LIC013','2002-09-27',183,80,1),
(NULL,'Marchand','Enzo','LIC014','2005-01-05',170,66,1),
(NULL,'Perrin','Nathan','LIC015','2004-04-19',178,72,1);

CREATE TABLE commentaire (
    id_commentaire INT AUTO_INCREMENT PRIMARY KEY,
    id_joueur INT NOT NULL,
    date_commentaire DATETIME DEFAULT CURRENT_TIMESTAMP,
    texte TEXT,
    FOREIGN KEY (id_joueur) REFERENCES joueur(id_joueur)
);

INSERT INTO commentaire (id_joueur, texte)
SELECT id_joueur, 'Bon comportement et implication.' FROM joueur;


CREATE TABLE matchs (
    id_match INT AUTO_INCREMENT PRIMARY KEY,
    date_heure DATETIME NOT NULL,
    adversaire VARCHAR(100) NOT NULL,
    lieu ENUM('DOMICILE','EXTERIEUR') NOT NULL,
    score_equipe INT,
    score_adverse INT,
    resultat ENUM('VICTOIRE','DEFAITE','NUL'),
    etat ENUM('A_PREPARER','PREPARE','JOUE') DEFAULT 'A_PREPARER'
);

INSERT INTO matchs VALUES
(NULL,'2025-01-10 20:30','Montpellier SC','EXTERIEUR',3,1,'VICTOIRE','JOUE'),
(NULL,'2025-01-18 19:00','AS Monaco','DOMICILE',1,1,'NUL','JOUE'),
(NULL,'2025-01-25 21:00','PSG','EXTERIEUR',0,2,'DEFAITE','JOUE'),
(NULL,'2025-02-10 18:00','Toulouse FC','DOMICILE',NULL,NULL,NULL,'A_PREPARER'),
(NULL,'2025-02-18 20:30','OM','EXTERIEUR',NULL,NULL,NULL,'A_PREPARER'),
(NULL,'2025-03-02 17:00','OL','DOMICILE',NULL,NULL,NULL,'A_PREPARER');

CREATE TABLE participation (
    id_match INT NOT NULL,
    id_joueur INT NOT NULL,
    id_poste INT NOT NULL,
    role ENUM('TITULAIRE','REMPLACANT') NOT NULL,
    evaluation TINYINT,
    PRIMARY KEY (id_match, id_joueur),
    FOREIGN KEY (id_match) REFERENCES matchs(id_match) ON DELETE CASCADE,
    FOREIGN KEY (id_joueur) REFERENCES joueur(id_joueur) ON DELETE CASCADE,
    FOREIGN KEY (id_poste) REFERENCES poste(id_poste)
);

ALTER TABLE matchs ADD COLUMN adresse VARCHAR(255) NULL AFTER lieu;

INSERT INTO participation
SELECT
    m.id_match,
    j.id_joueur,
    (SELECT id_poste FROM poste ORDER BY RAND() LIMIT 1),
    'TITULAIRE',
    FLOOR(3 + RAND()*3)
FROM matchs m
JOIN joueur j ON j.id_statut = 1
WHERE m.etat = 'JOUE'
AND j.id_joueur <= 11;

INSERT INTO matchs (date_heure, adversaire, lieu, score_equipe, score_adverse, resultat, etat) VALUES
('2024-09-01 18:00:00','RC Lens','DOMICILE',2,0,'VICTOIRE','JOUE'),
('2024-09-15 20:30:00','Stade Rennais','EXTERIEUR',1,1,'NUL','JOUE'),
('2024-09-29 19:00:00','OGC Nice','DOMICILE',0,1,'DEFAITE','JOUE'),
('2024-10-13 18:00:00','FC Nantes','EXTERIEUR',3,2,'VICTOIRE','JOUE'),
('2024-10-27 21:00:00','LOSC Lille','DOMICILE',2,2,'NUL','JOUE'),
('2024-11-10 18:00:00','RC Strasbourg','EXTERIEUR',1,0,'VICTOIRE','JOUE'),
('2024-11-24 20:45:00','Clermont Foot','DOMICILE',4,1,'VICTOIRE','JOUE'),
('2024-12-08 19:00:00','FC Metz','EXTERIEUR',0,0,'NUL','JOUE'),
('2024-12-22 21:00:00','AS Saint-Étienne','DOMICILE',1,2,'DEFAITE','JOUE'),
('2025-01-05 18:00:00','Montpellier SC','EXTERIEUR',3,1,'VICTOIRE','JOUE');
