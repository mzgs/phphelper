<?php

namespace App\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth
{
    private $secret;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'];
    }

    /**
     * Generate a JWT token for a user
     */
    public function generateToken(array $userData): string
    {
        $issuedAt = time();
        $expire   = $issuedAt + 3600; // Token expires in 1 hour

        $payload = [
            'iat'  => $issuedAt,
            'exp'  => $expire,
            'user' => [
                'id'    => $userData['id'],
                'email' => $userData['email'],
            ],
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * Validate a JWT token
     */
    public function validateToken(?string $token): ?array
    {
        if (!$token) {
            return null;
        }

        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return (array) $decoded->user;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Middleware to protect routes
     */
    public function authMiddleware(): callable
    {
        return function ()
        {
            $headers = getallheaders();
            $token   = $headers['Authorization'] ?? null;

            if ($token) {
                // Remove 'Bearer ' from token
                $token = str_replace('Bearer ', '', $token);
            }

            $user = $this->validateToken($token);

            if (!$user) {
                header('HTTP/1.1 401 Unauthorized');
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }

            // Add user data to request for use in controller
            $_REQUEST['user'] = $user;
        };
    }
}