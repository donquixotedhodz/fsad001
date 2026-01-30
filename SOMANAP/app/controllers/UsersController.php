<?php

class UsersController {
    protected $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all users
     */
    public function getAllUsers($limit = null, $offset = 0) {
        try {
            $query = "SELECT id, username, full_name, role, created_at FROM users ORDER BY created_at DESC";
            
            if ($limit) {
                $query .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching users: " . $e->getMessage());
        }
    }

    /**
     * Get total count of users
     */
    public function getUserCount() {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM users");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (Exception $e) {
            throw new Exception("Error counting users: " . $e->getMessage());
        }
    }

    /**
     * Get user by ID
     */
    public function getUserById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT id, username, full_name, role FROM users WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching user: " . $e->getMessage());
        }
    }

    /**
     * Add new user
     */
    public function addUser($username, $full_name, $password, $role) {
        try {
            // Check if username already exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                throw new Exception("Username already exists");
            }

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->conn->prepare("INSERT INTO users (username, full_name, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $full_name, $hashedPassword, $role]);
            
            return $this->conn->lastInsertId();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Update user
     */
    public function updateUser($id, $full_name, $role, $password = null) {
        try {
            if ($password) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->conn->prepare("UPDATE users SET full_name = ?, role = ?, password = ? WHERE id = ?");
                $stmt->execute([$full_name, $role, $hashedPassword, $id]);
            } else {
                $stmt = $this->conn->prepare("UPDATE users SET full_name = ?, role = ? WHERE id = ?");
                $stmt->execute([$full_name, $role, $id]);
            }
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Error updating user: " . $e->getMessage());
        }
    }

    /**
     * Delete user
     */
    public function deleteUser($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Error deleting user: " . $e->getMessage());
        }
    }

    /**
     * Get users by role
     */
    public function getUsersByRole($role) {
        try {
            $stmt = $this->conn->prepare("SELECT id, username, full_name, role FROM users WHERE role = ? ORDER BY full_name ASC");
            $stmt->execute([$role]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching users by role: " . $e->getMessage());
        }
    }
}
?>
