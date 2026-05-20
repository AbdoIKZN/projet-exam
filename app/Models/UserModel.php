<?php
/**
 * EventHub Pro — app/Models/UserModel.php
 * Encapsule toutes les requêtes SQL liées aux utilisateurs.
 */

require_once __DIR__ . '/../../core/Database.php';

class UserModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère un utilisateur via son email.
     *
     * @param  string $email
     * @return array|false
     */
    public function getByEmail(string $email)
    {
        $stmt = $this->db->prepare('SELECT id, name, role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }
}
