<?php

class MainController {
    protected $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Redirect to login if not authenticated
     */
    public static function requireAuth() {
        if (!self::isAuthenticated()) {
            header("Location: index.php");
            exit();
        }
    }

    /**
     * Load a view file
     */
    public function loadView($viewPath, $data = []) {
        extract($data);
        require_once $viewPath;
    }

    /**
     * Get user data from session
     */
    public static function getUserData() {
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null
        ];
    }

    /**
     * Set current page
     */
    public static function setCurrentPage($page) {
        $_SESSION['currentPage'] = $page;
    }

    /**
     * Get current page
     */
    public static function getCurrentPage() {
        return $_SESSION['currentPage'] ?? 'dashboard';
    }
}

?>
