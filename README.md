# PHP Helper Library

A comprehensive PHP helper library providing various utility functions for common tasks. This library requires PHP 8.3 or higher.

## Installation

You can install the package via composer:

```bash
composer require mzgs/phphelper:dev-main
```

Note: Until a stable release is tagged, please use `dev-main` version.

## Features Overview

- Database operations with PDO
- File system operations
- Authentication and session management
- Array manipulation utilities
- General helper functions

## Usage Examples

### Database Helper

```php
use PhpHelper\Database\DatabaseHelper;

// Initialize database connection
$db = DatabaseHelper::getInstance([
    'host'     => 'localhost',
    'username' => 'root',
    'password' => 'secret',
    'database' => 'myapp',
    'port'     => 3306
]);

// Connect to database
$db->connect();

// Insert data
$userId = $db->insert('users', [
    'name'  => 'John Doe',
    'email' => 'john@example.com'
]);

// Select single row
$user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);

// Select multiple rows
$users = $db->fetchAll("SELECT * FROM users WHERE active = ?", [1]);

// Update data
$affected = $db->update(
    'users',
    ['status' => 'active'],
    'id = ?',
    [$userId]
);

// Delete data
$deleted = $db->delete('users', 'id = ?', [$userId]);

// Transaction example
try {
    $db->beginTransaction();
    
    $userId = $db->insert('users', ['name' => 'John']);
    $db->insert('profiles', ['user_id' => $userId]);
    
    $db->commit();
} catch (\Exception $e) {
    $db->rollBack();
    throw $e;
}
```

### File Helper

```php
use PhpHelper\File\FileHelper;

// Read file
$content = FileHelper::read('path/to/file.txt');

// Write file
FileHelper::write('path/to/file.txt', 'Hello World');
FileHelper::write('path/to/file.txt', 'More content', true); // append mode

// Copy file
FileHelper::copy('source.txt', 'destination.txt');

// Move file
FileHelper::move('old/path.txt', 'new/path.txt');

// Delete file
FileHelper::delete('path/to/file.txt');

// Create directory
FileHelper::createDirectory('path/to/directory', 0755);

// Get file information
$ext = FileHelper::getExtension('image.jpg');
$mime = FileHelper::getMimeType('document.pdf');
$size = FileHelper::getSize('large-file.zip');

// Check file properties
$exists = FileHelper::exists('config.json');
$readable = FileHelper::isReadable('data.txt');
$writable = FileHelper::isWritable('log.txt');

// List files
$files = FileHelper::getFiles('directory', '*.php', true); // recursive

// Handle file uploads
$result = FileHelper::upload(
    $_FILES['document'],
    'uploads/doc.pdf',
    ['application/pdf'],
    5 * 1024 * 1024 // 5MB limit
);
```

### Login Helper

```php
use PhpHelper\Auth\LoginHelper;

$auth = LoginHelper::getInstance([
    'table'          => 'users',          // table name
    'identity_field' => 'email',          // login identifier field
    'password_field' => 'password',       // password field
    'session_expire' => 7200,             // session timeout in seconds
    'token_expire'   => 86400,            // password reset token timeout
    'hash_algo'      => PASSWORD_ARGON2ID // password hashing algorithm
]);

// Set database connection
$auth->setDatabase($db);

// Register new user
$user = $auth->register([
    'email'    => 'user@example.com',
    'password' => 'secure_password',
    'name'     => 'John Doe'
]);

// Login
$session = $auth->login('user@example.com', 'secure_password');

// Check login status
if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
}

// Password reset flow
$token = $auth->generateResetToken('user@example.com');
$auth->resetPassword($token, 'new_password');

// Update password
$auth->updatePassword($userId, 'new_password');

// Logout
$auth->logout();
```

### Array Helper

```php
use PhpHelper\Utils\ArrayHelper;

// Dot notation access
$array = [
    'user' => [
        'profile' => [
            'name' => 'John'
        ]
    ]
];

$name = ArrayHelper::get($array, 'user.profile.name');
ArrayHelper::set($array, 'user.profile.age', 25);
ArrayHelper::has($array, 'user.profile.email');
ArrayHelper::remove($array, 'user.profile.name');

// Array manipulation
$only = ArrayHelper::only($array, ['id', 'name']);
$except = ArrayHelper::except($array, ['password']);

// Collection operations with simple keys
$users = [
    ['id' => 1, 'name' => 'John', 'role' => 'admin'],
    ['id' => 2, 'name' => 'Jane', 'role' => 'user'],
    ['id' => 3, 'name' => 'Bob', 'role' => 'user']
];

$names = ArrayHelper::pluck($users, 'name');         // ['John', 'Jane', 'Bob']
$grouped = ArrayHelper::groupBy($users, 'role');     // ['admin' => [...], 'user' => [...]]
$keyedById = ArrayHelper::keyBy($users, 'id');       // [1 => [...], 2 => [...], 3 => [...]]
$unique = ArrayHelper::unique($users, 'role');       // Unique by role

// Collection operations with dot notation for nested arrays
$nested = [
    ['user' => ['profile' => ['name' => 'John', 'role' => 'admin']]],
    ['user' => ['profile' => ['name' => 'Jane', 'role' => 'user']]],
];

$names = ArrayHelper::pluck($nested, 'user.profile.name');         // Get nested names
$byRole = ArrayHelper::groupBy($nested, 'user.profile.role');      // Group by nested role
$keyedByName = ArrayHelper::keyBy($nested, 'user.profile.name');   // Key by nested name
$uniqueRoles = ArrayHelper::unique($nested, 'user.profile.role');  // Unique by nested role

// Array utilities
$flat = ArrayHelper::flatten([1, [2, 3, [4, 5]], 6]);
$chunks = ArrayHelper::chunk($array, 2);
$random = ArrayHelper::random($array, 2);
$shuffled = ArrayHelper::shuffle($array);
$unique = ArrayHelper::unique($array, 'id');

// Get first/last elements
$first = ArrayHelper::first($array);
$last = ArrayHelper::last($array);

// Get first/last elements with default values
$first = ArrayHelper::first($array);           // First element or null
$last = ArrayHelper::last($array, 'default');  // Last element or 'default'

// Get first/last elements matching conditions
$firstAdmin = ArrayHelper::whereFirst($users, 'role', 'admin');
$lastActive = ArrayHelper::whereLast($users, 'active', true);
$olderThan20 = ArrayHelper::whereFirst($users, 'age', 20, '>');

// Using dot notation for nested array properties
$data = [
    ['user' => ['profile' => ['age' => 25, 'role' => 'admin']]],
    ['user' => ['profile' => ['age' => 30, 'role' => 'user']]],
];

$admins = ArrayHelper::where($data, 'user.profile.role', 'admin');
$firstYoung = ArrayHelper::whereFirst($data, 'user.profile.age', 30, '<');
$lastAdmin = ArrayHelper::whereLast($data, 'user.profile.role', 'admin');
```

### General Helper

```php
use PhpHelper\Utils\Helper;

// String manipulation
$uuid = Helper::generateUuid();
$slug = Helper::slugify('Hello World!'); // "hello-world"
$truncated = Helper::truncate('Long text here', 5); // "Lo..."
$masked = Helper::mask('1234567890', 6, 4); // "123456****"
$random = Helper::generateRandomString(16);

// Format utilities
$size = Helper::formatBytes(1024 * 1024); // "1 MB"
$duration = Helper::formatDuration(3600); // "1h 0m"

// Validation
$isEmail = Helper::isValidEmail('test@example.com');
$isUrl = Helper::isValidUrl('https://example.com');
$isIp = Helper::isValidIp('192.168.1.1');

// Security
$safe = Helper::sanitizeString('<script>alert(1)</script>');
$urls = Helper::extractUrls('Visit https://example.com and http://test.com');

// Request utilities
$ip = Helper::getClientIp();
$isAjax = Helper::isAjaxRequest();
$isSsl = Helper::isSsl();
$currentUrl = Helper::getCurrentUrl();

// Browser detection
$userAgent = Helper::parseUserAgent();
/*
[
    'browser' => 'Chrome',
    'os'      => 'Windows',
    'device'  => 'Desktop'
]
*/

// Navigation
Helper::redirectTo('/dashboard');
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Testing

```bash
composer test
```

## Security

If you discover any security related issues, please email security@phphelper.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
