<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Bramus\Router\Router;
use App\Controllers\AuthController;
use App\Auth\Auth;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Set headers for JSON API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$router = new Router();
$auth   = new Auth();

// Initialize controller only when needed
$authController = null;

// Public routes
$router->post('/register', function () use (&$authController)
{
    if (!$authController)
        $authController = new AuthController();
    $authController->register();
});

$router->post('/login', function () use (&$authController)
{
    if (!$authController)
        $authController = new AuthController();
    $authController->login();
});

// Protected routes
$router->before('GET', '/profile', $auth->authMiddleware());
$router->get('/profile', function () use (&$authController)
{
    if (!$authController)
        $authController = new AuthController();
    $authController->getProfile();
});

// Example protected API route
$router->before('GET', '/api/.*', $auth->authMiddleware());
$router->get('/api/example', function ()
{
    echo json_encode([
        'message' => 'This is a protected API endpoint',
        'user'    => $_REQUEST['user'],
    ]);
});

// Database schema endpoint
$router->get('/setup-db', function ()
{
    try {
        $db = \App\Config\Database::getInstance()->getConnection();

        // Create users table
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        echo json_encode(['message' => 'Database setup completed']);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database setup failed: ' . $e->getMessage()]);
    }
});

// Global error handler
set_exception_handler(function ($e)
{
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'type'  => get_class($e),
    ]);
});

$router->run();