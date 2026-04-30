<?php
class Auth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function login($username, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;
            return true;
        }
        return false;
    }
    
    public function logout() {
        session_unset();
        session_destroy();
        // Use relative path instead of SITE_URL constant
        $base_path = dirname($_SERVER['PHP_SELF']);
        $base_path = rtrim($base_path, '/');
        header('Location: ' . $base_path . '/index.php');
        exit();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            $base_path = dirname($_SERVER['PHP_SELF']);
            $base_path = rtrim($base_path, '/');
            header('Location: ' . $base_path . '/index.php');
            exit();
        }
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username']
            ];
        }
        return null;
    }
}
?>