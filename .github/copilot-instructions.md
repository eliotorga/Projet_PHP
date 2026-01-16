# Copilot instructions for Projet_PHP

Purpose: Give concise, actionable guidance for AI coding agents working on this PHP web app.

Big picture
- App type: Classic PHP + PDO web app served from `index.php` (no framework). Pages are regular PHP files.
- Components: `modele/` (data access / domain functions), `vues/` (HTML page fragments), top-level route files and feature folders (`joueurs/`, `matchs/`, `stats/`, `feuille_match/`).
- Data flow: controllers/pages include `includes/config.php` (provides `$gestion_sportive` PDO), call functions in `modele/*.php` (usually accept `PDO $db`) and render views from `vues/`.

How to run locally (developer workflow)
- Platform: Designed for XAMPP / Apache on Windows. Place the repo in `htdocs` and visit: `http://localhost/Projet_PHP/index.php`.
- DB: Import `BDD.sql` into MySQL (phpMyAdmin or CLI). Example CLI: `mysql -u root -p gestion_equipe < BDD.sql`.
- Configure DB and admin creds: edit [includes/config.php](includes/config.php). The file defines `$DB_*`, `$AUTH_LOGIN`, and the bcrypt `$AUTH_PASSWORD_HASH` used by `login.php`.

Key security & session conventions
- Auth: Protected pages include [includes/auth_check.php](includes/auth_check.php). It expects `$_SESSION['user_id']` and enforces an inactivity timeout (~200s). Use the same session keys when simulating login.
- Passwords: `includes/config.php` exposes `PASSWORD_ALGO` and `PASSWORD_OPTIONS`; existing admin password is stored as `$AUTH_PASSWORD_HASH`.
- DB access: Always use PDO prepared statements. The project sets `PDO::ATTR_EMULATE_PREPARES => false` in [includes/config.php](includes/config.php).

Code patterns & conventions to follow
- Models: Functions in `modele/*.php` are procedural, named with camelCase (e.g. `getAllPlayers`, `insertPlayer`, `getPlayerProfile`). They typically accept `PDO $db` as the first argument. Call them with the PDO instance from `includes/config.php` (variable: `$gestion_sportive`). Example: `getAllPlayers($gestion_sportive)`.
- Views vs controllers: `vues/` contains view templates (HTML+PHP). Controller pages live at the project root and in feature folders (`joueurs/ajouter_joueur.php`, `matchs/...`) and include `header.php`/`footer.php` from `includes/`.
- Forms: Typical pattern — form POSTs to a controller script in the same folder which calls model functions and redirects back to a list or detail page.
- Messages & redirects: Use `header("Location: /Projet_PHP/...")` (absolute path) — preserve this when creating links.

Files to inspect for examples
- App entry & auth: [index.php](index.php), [login.php](login.php)
- DB & session setup: [includes/config.php](includes/config.php), [includes/auth_check.php](includes/auth_check.php)
- Model examples: [modele/joueur.php](modele/joueur.php) (good examples of prepared statements, stats, comment functions)
- Feature views/controllers: `vues/` and `joueurs/`, `matchs/`, `feuille_match/`

What to change and what to avoid
- Safe edits: UI improvements in `assets/css/*`, small bugfixes in controllers, or adding new model functions that accept `PDO $db`.
- Avoid: changing global session semantics or hardening that would break the existing session keys without updating `login.php` and all protected pages. Avoid changing DB credentials format — update only `includes/config.php`.

Search & quick fixes examples
- To add a new player: follow `vues/ajouter_joueur_view.php` + `joueurs/ajouter_joueur.php` + `modele/joueur.php::insertPlayer`.
- To add a stats widget: reuse `modele/stats.php` functions and add a small include in `vues/stats_view.php`.

If uncertain, ask the user these quick clarifying questions
- Should changes be deployed to a running XAMPP instance or prepared as a patch only?
- Do you want new features to follow the procedural model-functions style or move toward a small MVC refactor?

End — please review and tell me any missing local workflow detail (Docker, custom PHP.ini, or CI) to include.
