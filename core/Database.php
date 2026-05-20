<?php
/**
 * EventHub Pro — core/Database.php
 * Patron Singleton pour la connexion PDO à la base de données.
 */

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        require_once __DIR__ . '/../config/db.php';

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('[EventHub] DB Connection failed: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            die(json_encode([
                'success' => false,
                'error'   => 'Erreur de connexion à la base de données.'
            ]));
        }
    }

    /**
     * Récupère l'instance unique du Singleton Database.
     *
     * @return Database
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retourne l'objet PDO connecté.
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}
