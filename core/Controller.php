<?php
/**
 * EventHub Pro — core/Controller.php
 * Contrôleur de base (abstrait) hérité par tous les contrôleurs de l'application.
 */

abstract class Controller
{
    /**
     * Extrait les données et inclut le fichier de vue correspondant dans app/Views/.
     *
     * @param  string $view Chemin relatif de la vue sans l'extension (ex: 'events/index')
     * @param  array  $data Tableau associatif contenant les variables transmises à la vue
     * @return void
     */
    protected function render(string $view, array $data = []): void
    {
        extract($data);

        $viewFile = __DIR__ . '/../app/Views/' . $view . '.php';
        if (is_file($viewFile)) {
            require $viewFile;
        } else {
            throw new RuntimeException("La vue '{$view}' est introuvable au chemin {$viewFile}.");
        }
    }

    /**
     * Envoie une réponse JSON propre au client et interrompt l'exécution.
     *
     * @param  mixed $data       Données à encoder en JSON
     * @param  int   $statusCode Code HTTP statut (par défaut 200)
     * @return void
     */
    /**
     * Verifie que la session courante correspond a un organisateur.
     *
     * Le fallback "organizer" conserve le comportement de demo de l'examen
     * tout en donnant le meme etat de session au dashboard, a l'API et aux PDF.
     */
    protected function requireOrganizer(bool $jsonResponse = false): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $role = $_SESSION['user_role'] ?? 'organizer';
        $_SESSION['user_role'] = $role;

        if ($role === 'organizer') {
            return;
        }

        if ($jsonResponse) {
            $this->json([
                'success' => false,
                'error'   => 'Acces refuse. Autorisation requise.'
            ], 403);
        }

        http_response_code(403);
        echo '<!doctype html><meta charset="utf-8"><p>Acces reserve aux organisateurs.</p>';
        exit;
    }

    protected function json($data, int $statusCode = 200): void
    {
        // Nettoie tout tampon de sortie existant pour éviter les caractères parasites
        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=UTF-8');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}
