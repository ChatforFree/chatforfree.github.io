<?php
// users.php - Handles user operations
session_start();

class UserManager {
    private $filename = 'users.txt';
    private $adminUsername = 'admin';
    private $adminPassword = 'admin';

    public function __construct() {
        // Create users file if it doesn't exist
        if (!file_exists($this->filename)) {
            file_put_contents($this->filename, '');
        }
    }

    // Read users from file
    private function getUsers() {
        $content = file_get_contents($this->filename);
        return $content ? explode("\n", trim($content)) : [];
    }

    // Save users to file
    private function saveUsers($users) {
        file_put_contents($this->filename, implode("\n", $users));
    }

    // Login verification
    public function login($username, $password) {
        if ($username === $this->adminUsername && $password === $this->adminPassword) {
            $_SESSION['user'] = 'admin';
            return ['success' => true, 'isAdmin' => true];
        }

        $users = $this->getUsers();
        foreach ($users as $user) {
            list($savedUsername, $savedPassword) = explode(':', $user);
            if ($username === $savedUsername && $password === $savedPassword) {
                $_SESSION['user'] = $username;
                return ['success' => true, 'isAdmin' => false];
            }
        }

        return ['success' => false];
    }

    // Create new user
    public function createUser($username, $password) {
        if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'admin') {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $users = $this->getUsers();
        
        // Check if username already exists
        foreach ($users as $user) {
            list($savedUsername) = explode(':', $user);
            if ($username === $savedUsername) {
                return ['success' => false, 'message' => 'Username already exists'];
            }
        }

        // Add new user
        $users[] = "$username:$password";
        $this->saveUsers($users);
        return ['success' => true];
    }

    // Get all users
    public function getAllUsers() {
        if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'admin') {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $users = $this->getUsers();
        $userList = [];
        foreach ($users as $user) {
            if (!empty($user)) {
                list($username) = explode(':', $user);
                $userList[] = $username;
            }
        }
        return ['success' => true, 'users' => $userList];
    }

    // Delete user
    public function deleteUser($username) {
        if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'admin') {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $users = $this->getUsers();
        $updatedUsers = [];
        foreach ($users as $user) {
            list($savedUsername) = explode(':', $user);
            if ($username !== $savedUsername) {
                $updatedUsers[] = $user;
            }
        }
        $this->saveUsers($updatedUsers);
        return ['success' => true];
    }

    // Logout
    public function logout() {
        session_destroy();
        return ['success' => true];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userManager = new UserManager();
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'login':
            echo json_encode($userManager->login($data['username'], $data['password']));
            break;
        case 'createUser':
            echo json_encode($userManager->createUser($data['username'], $data['password']));
            break;
        case 'getUsers':
            echo json_encode($userManager->getAllUsers());
            break;
        case 'deleteUser':
            echo json_encode($userManager->deleteUser($data['username']));
            break;
        case 'logout':
            echo json_encode($userManager->logout());
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}
?>
