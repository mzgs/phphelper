# PHP Example Application

This is a real-world PHP example application demonstrating:
- Routing with Bramus Router
- Database operations with PDO
- JWT Authentication
- Environment configuration
- MVC pattern implementation

## Setup

1. Install dependencies:
```bash
composer install
```

2. Configure environment:
```bash
cp .env.example .env
```
Then edit `.env` file with your database credentials and JWT secret.

3. Create database schema:
Access `http://localhost:8000/setup-db` to create the required tables.

## Running the Application

Start the development server:
```bash
cd public
php -S localhost:8000
```

## API Endpoints

### Public Routes

#### Register User
```http
POST /register
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "yourpassword"
}
```

#### Login
```http
POST /login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "yourpassword"
}
```

### Protected Routes
(Require Authorization header with Bearer token)

#### Get Profile
```http
GET /profile
Authorization: Bearer your-jwt-token
```

#### Example API Endpoint
```http
GET /api/example
Authorization: Bearer your-jwt-token
```

## Database Connection

The application uses a singleton pattern for database connections to ensure only one connection is maintained throughout the application lifecycle. The connection is automatically closed when the script ends.

Example of using the database connection:

```php
use App\Config\Database;

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
```

## Authentication

JWT (JSON Web Token) authentication is implemented using the `firebase/php-jwt` package. Protected routes require a valid JWT token in the Authorization header.

Example of protecting a route:
```php
$router->before('GET', '/protected-route', $auth->authMiddleware());
$router->get('/protected-route', function() {
    // This code only runs if JWT is valid
    $user = $_REQUEST['user']; // User data from JWT
});
```

## Project Structure

```
example-app/
├── public/
│   └── index.php         # Entry point
├── src/
│   ├── Auth/
│   │   └── Auth.php      # Authentication handling
│   ├── Config/
│   │   └── Database.php  # Database configuration
│   └── Controllers/
│       └── AuthController.php
├── .env.example          # Environment template
├── composer.json         # Dependencies
└── README.md            # This file
