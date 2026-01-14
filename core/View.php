<?php
/**
 * Classe View - Moteur de templates simple
 * Gère le rendu des vues avec ou sans layout
 */
class View {
    /**
     * Rend une vue sans layout
     * @param string $view Chemin de la vue (relatif à /views/)
     * @param array $data Données à passer à la vue
     */
    public static function render(string $view, array $data = []): void {
        extract($data);
        $viewPath = __DIR__ . "/../views/$view";

        if (!file_exists($viewPath)) {
            die("Vue introuvable : $viewPath");
        }

        require $viewPath;
    }

    /**
     * Rend une vue avec le layout complet (header + footer)
     * @param string $view Chemin de la vue (relatif à /views/)
     * @param array $data Données à passer à la vue
     */
    public static function renderWithLayout(string $view, array $data = []): void {
        extract($data);

        // Récupération des messages flash
        $success_message = Response::getSuccess();
        $error_message = Response::getError();
        $info_message = Response::getInfo();

        $viewPath = __DIR__ . "/../views/$view";

        if (!file_exists($viewPath)) {
            die("Vue introuvable : $viewPath");
        }

        require __DIR__ . "/../views/layouts/header.php";
        require $viewPath;
        require __DIR__ . "/../views/layouts/footer.php";
    }

    /**
     * Rend une vue sans header ni footer (pour AJAX ou composants)
     * @param string $view Chemin de la vue (relatif à /views/)
     * @param array $data Données à passer à la vue
     */
    public static function renderPartial(string $view, array $data = []): void {
        self::render($view, $data);
    }

    /**
     * Retourne le contenu d'une vue sous forme de string (sans l'afficher)
     * @param string $view Chemin de la vue (relatif à /views/)
     * @param array $data Données à passer à la vue
     * @return string Contenu de la vue
     */
    public static function capture(string $view, array $data = []): string {
        ob_start();
        self::render($view, $data);
        return ob_get_clean();
    }
}
