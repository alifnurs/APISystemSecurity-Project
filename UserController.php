<?php
class UserController {
    private $db;
    private $rateLimiter;
    
    public function __construct($db) {
        $this->db = $db;
        $this->rateLimiter = new RateLimiter();
    }
    
    public function register($data) {
        print_r($data);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            return ['error' => 'Missing required fields'];
        }
        
        
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        
       
        $stmt = $this->db->prepare(
            "INSERT INTO users (email, password, role) VALUES (?, ?, 'user')"
        );
        
        try {
            $stmt->execute([$data['email'], $hashedPassword]);
            return ['message' => 'User registered successfully'];
        } catch(PDOException $e) {
            return ['error' => 'Registration failed'];
        }
    }
    
    public function login($data) {
        if (!isset($data['email']) || !isset($data['password'])) {
            return ['error' => 'Missing credentials'];
        }
        
        $stmt = $this->db->prepare(
            "SELECT id, email, password, role FROM users WHERE email = ?"
        );
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($data['password'], $user['password'])) {
            return ['error' => 'Invalid credentials'];
        }
        
        $token = JWTHandler::generateToken($user);
        return ['token' => $token];
    }
    
    public function getProtectedData($userId) {
        if (!$this->rateLimiter->checkLimit($userId)) {
            return ['error' => 'Rate limit exceeded'];
        }
        
        $stmt = $this->db->prepare(
            "SELECT id, email, role FROM users WHERE id = ?"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
