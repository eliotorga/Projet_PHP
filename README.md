# Gestion d'Équipe Sportive

Application web de gestion d'équipe sportive développée en PHP pour gérer les joueurs, les matchs et les statistiques.

### URL d'accès
url du site : https://william1234.alwaysdata.net/login.php
```
https://github.com/eliotorga/Projet_PHP.git

```

### Identifiants de connexion
- **Nom d'utilisateur**: `admin`
- **Mot de passe**: `admin`


### Gestion des joueurs
- Ajout, modification et suppression de joueurs
- Suivi du statut (Actif, Blessé, Suspendu, Absent)
- Gestion des postes (Gardien, Défenseur, Milieu, Attaquant)
- Évaluation des performances

### Gestion des matchs
- Création et planification des matchs
- Suivi des résultats (Victoire, Nul, Défaite)
- Composition des équipes
- Suivi des participations

### Statistiques
- Tableau de bord avec statistiques en temps réel
- Performance moyenne des joueurs
- Historique des matchs
- Analyse des résultats
```


## Sécurité

- Sessions sécurisées avec vérification IP et User-Agent
- Protection contre les injections SQL (PDO avec requêtes préparées)
- Délai anti-force brute sur la page de connexion

## Notes importantes

- Les identifiants sont configurés directement dans le code (exigence du projet)
- L'application utilise PDO pour une connexion sécurisée à la base de données
- Le système de session inclut des protections de base contre le détournement de session
