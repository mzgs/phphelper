<?php

namespace App\Controllers;

use App\Config\Database;
use App\Auth\Auth;

class AuthController
{
    private $db;
    private $auth;

    public function __construct()
    {
        $this->db   = Database::getInstance()->getConnection();
        $this->auth = new Auth();
    }

    public function register()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password are required']);
            return;
        }

        $email    = $data['email'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT);

        try {
            $stmt = $this->db->prepare('INSERT INTO users (email, password) VALUES (?, ?)');
            $stmt->execute([$email, $password]);

            $userId = $this->db->lastInsertId();

            $token = $this->auth->generateToken([
                'id'    => $userId,
                'email' => $email,
            ]);

            echo json_encode([
                'token' => $token,
                'user'  => [
                    'id'    => $userId,
                    'email' => $email,
                ],
            ]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Registration failed']);
        }
    }

    public function login()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password are required']);
            return;
        }

        try {
            $stmt = $this->db->prepare('SELECT id, email, password FROM users WHERE email = ?');
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($data['password'], $user['password'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
                return;
            }

            $token = $this->auth->generateToken([
                'id'    => $user['id'],
                'email' => $user['email'],
            ]);

            echo json_encode([
                'token' => $token,
                'user'  => [
                    'id'    => $user['id'],
                    'email' => $user['email'],
                ],
            ]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Login failed']);
        }
    }

    public function getProfile()
    {
        // User data is available from middleware
        $user = $_REQUEST['user'];

        try {
            $stmt = $this->db->prepare('SELECT id, email FROM users WHERE id = ?');
            $stmt->execute([$user['id']]);
            $userData = $stmt->fetch();

            echo json_encode($userData);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch profile']);
        }
    }
}