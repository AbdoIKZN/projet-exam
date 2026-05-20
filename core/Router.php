<?php
/**
 * EventHub Pro — core/Router.php
 * Routeur minimaliste distribuant les requêtes HTTP aux contrôleurs MVC.
 */

class Router
{
    private $routes = [];

    /**
     * Enregistre une route HTTP GET.
     *
     * @param  string       $path    Chemin (ex: '/')
     * @param  string|array $handler Contrôleur et méthode (ex: 'EventController@index' ou ['EventController', 'index'])
     * @return void
     */
    public function get(string $path, $handler): void
    {
        $path = '/' . trim($path, '/');
        $this->routes['GET'][$path] = $handler;
    }

    /**
     * Enregistre une route HTTP POST.
     *
     * @param  string       $path
     * @param  string|array $handler
     * @return void
     */
    public function post(string $path, $handler): void
    {
        $path = '/' . trim($path, '/');
        $this->routes['POST'][$path] = $handler;
    }

    /**
     * Analyse l'URL courante, extrait le chemin et exécute la fonction associée.
     *
     * @return void
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        // Extrait le chemin (sans les paramètres GET ?...)
        $path = parse_url($requestUri, PHP_URL_PATH);

        // Nettoyage de l'URI pour l'environnement EasyPHP (ex: /MVPexam/public/index.php/api/stats)
        $baseDirs = ['/MVPexam/public/index.php', '/MVPexam/index.php', '/MVPexam/public', '/MVPexam'];
        foreach ($baseDirs as $dir) {
            if (strpos($path, $dir) === 0) {
                $path = substr($path, strlen($dir));
                break;
            }
        }

        // Harmonisation
        $path = '/' . trim($path, '/');

        // Recherche d'une route correspondante
        if (isset($this->routes[$method][$path])) {
            $handler = $this->routes[$method][$path];
            if (is_string($handler)) {
                $handler = explode('@', $handler);
            }
            [$controllerClass, $methodName] = $handler;

            // Chargement dynamique du fichier du contrôleur
            $controllerFile = __DIR__ . '/../app/Controllers/' . $controllerClass . '.php';
            if (is_file($controllerFile)) {
                require_once $controllerFile;
            } else {
                http_response_code(500);
                die("Contrôleur introuvable : " . htmlspecialchars($controllerClass));
            }

            if (class_exists($controllerClass)) {
                $controller = new $controllerClass();
                if (method_exists($controller, $methodName)) {
                    $controller->$methodName();
                    return;
                }
            }
        }

        // Si aucune route ne correspond, retour 404
        http_response_code(404);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>404 Not Found</title>';
        echo '<style>body{font-family:sans-serif;text-align:center;padding:50px;background:#f1f5f9;color:#0f1f3d;}h1{font-size:48px;margin-bottom:10px;}p{color:#64748b;}</style></head>';
        echo '<body><h1>404</h1><p>La page demandée n\'existe pas : <strong>' . htmlspecialchars($path) . '</strong></p><a href="/MVPexam/">Retourner à l\'accueil</a></body></html>';
    }
}
