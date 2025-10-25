# PHP Helper Library

A comprehensive PHP helper library providing ready-to-use utilities for everyday application tasks.

## Installation

```bash
composer require mzgs/phphelper:dev-main
```

## Requirements

- PHP 8.3 or higher

## Table of Contents

1. [AIChat](#aichat) - AI chat integration with OpenAI API
2. [App](#app) - Application environment detection utilities  
3. [Arrays](#arrays) - Array manipulation and utilities
4. [AuthManager](#authmanager) - User authentication and session management
5. [Config](#config) - Configuration management with database storage
6. [Countries](#countries) - Country data with flags, ISO codes, and names
7. [DB](#db) - Database abstraction layer for PDO
8. [Date](#date) - Date and time formatting utilities
9. [Files](#files) - File system operations
10. [Format](#format) - Data formatting utilities
11. [Http](#http) - HTTP utilities and client information
12. [Logs](#logs) - Logging system with database storage
13. [PrettyErrorHandler](#prettyerrorhandler) - Enhanced error display
14. [Str](#str) - String manipulation utilities
15. [TwigHelper](#twighelper) - Twig template engine integration
16. [Example index.php](#example-indexphp) - Complete application setup example

---

## AIChat

AI chat integration providing easy interaction with OpenAI-compatible APIs.

### Configuration Options

```php
AIChat::init([
    'base_uri' => 'https://api.openai.com/v1/',  // API base URL
    'endpoint' => 'chat/completions',             // Chat endpoint
    'model' => 'gpt-4o-mini',                     // Default model
    'api_key' => 'your-api-key',                  // API key
    'timeout' => 15.0,                            // Request timeout
    'headers' => [],                              // Additional headers
    'response_format' => null,                    // Response format
    'client_options' => []                        // Additional Guzzle options
]);
```

### Methods

#### `init(array $config): void`
Configure the AI chat client.

```php
// Basic configuration
AIChat::init([
    'api_key' => 'sk-your-openai-api-key',
    'model' => 'gpt-4'
]);

// Advanced configuration with custom headers
AIChat::init([
    'api_key' => 'sk-your-key',
    'model' => 'gpt-3.5-turbo',
    'timeout' => 30.0,
    'headers' => ['User-Agent' => 'MyApp/1.0'],
    'response_format' => 'json_object'
]);
```

#### `setApiKey(?string $apiKey): void`
Set or update the API key.

```php
AIChat::setApiKey('sk-new-api-key');
```

#### `chat(array $messages, array $payload = []): array`
Send chat messages to the AI API.

```php
// Simple conversation
$messages = [
    ['role' => 'user', 'content' => 'Hello, how are you?']
];
$response = AIChat::chat($messages);
echo $response['choices'][0]['message']['content'];

// With system prompt and temperature
$messages = [
    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
    ['role' => 'user', 'content' => 'Explain quantum physics simply.']
];
$response = AIChat::chat($messages, [
    'temperature' => 0.7,
    'max_tokens' => 150
]);
```

#### `reply(string $prompt, array $payload = [], array $contextMessages = []): string`
Get a direct reply to a prompt.

```php
// Simple reply
$answer = AIChat::reply('What is the capital of France?');
echo $answer; // "Paris"

// With context and parameters
$context = [
    ['role' => 'system', 'content' => 'You are a coding tutor.']
];
$answer = AIChat::reply(
    'How do I create a PHP class?',
    ['temperature' => 0.3],
    $context
);
```

#### `reset(): void`
Reset configuration to defaults.

```php
AIChat::reset();
```

---

## App

Application environment detection utilities.

### Methods

#### `isLocal(): bool`
Check if the application is running in a local environment.

```php
if (App::isLocal()) {
    echo "Running locally";
    // Enable debug mode, detailed logging, etc.
}
```

#### `isCli(): bool`
Determine if the script is running from command line.

```php
if (App::isCli()) {
    echo "Running from command line";
    // CLI-specific logic
}
```

#### `isProduction(): bool`
Check if running in production environment.

```php
if (App::isProduction()) {
    // Production-specific configuration
    ini_set('display_errors', 0);
    error_reporting(0);
}
```

#### `cliMenu(array $options): void`
Render an interactive CLI menu where each option executes a shell command.

```php
use PhpHelper\App;

App::cliMenu([
    'List files' => 'ls -al',
    'Run tests' => [
        'label' => 'Execute PHPUnit suite',
        'command' => 'vendor/bin/phpunit',
    ],
    'Migrate database' => './artisan migrate',
]);
```

- Only runs when invoked from the CLI; it exits early in web contexts.
- Accepts either `label => command` pairs or configuration arrays with a `command` key and optional `label`/`title`.
- Supports PHP callables via `run`/`callback`/`callable` entries for in-process actions.
- Displays the resulting exit code after each command, and supports quitting via `q`, `quit`, or `exit`.

---

## Arrays

Comprehensive array manipulation utilities with dot notation support.

### Methods

#### `get(array $array, string $key, mixed $default = null): mixed`
Get value using dot notation.

```php
$data = [
    'user' => [
        'profile' => [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]
    ]
];

$name = Arrays::get($data, 'user.profile.name');
echo $name; // "John Doe"

$phone = Arrays::get($data, 'user.profile.phone', 'Not provided');
echo $phone; // "Not provided"
```

#### `set(array &$array, string $key, mixed $value): void`
Set value using dot notation.

```php
$data = [];
Arrays::set($data, 'config.database.host', 'localhost');
Arrays::set($data, 'config.database.port', 3306);
// Result: ['config' => ['database' => ['host' => 'localhost', 'port' => 3306]]]
```

#### `has(array $array, string $key): bool`
Check if key exists using dot notation.

```php
$data = ['user' => ['name' => 'John']];
$exists = Arrays::has($data, 'user.name'); // true
$missing = Arrays::has($data, 'user.email'); // false
```

#### `forget(array &$array, string $key): void`
Remove value using dot notation.

```php
$data = ['user' => ['name' => 'John', 'email' => 'john@example.com']];
Arrays::forget($data, 'user.email');
// Result: ['user' => ['name' => 'John']]
```

#### `flatten(array $array, int $depth = INF): array`
Flatten multi-dimensional array.

```php
$nested = [1, [2, 3], [4, [5, 6]]];
$flat = Arrays::flatten($nested);
// Result: [1, 2, 3, 4, 5, 6]

$partialFlat = Arrays::flatten($nested, 1);
// Result: [1, 2, 3, 4, [5, 6]]
```

#### `dot(array $array, string $prepend = ''): array`
Flatten array with dot notation keys.

```php
$data = [
    'app' => [
        'name' => 'MyApp',
        'version' => '1.0'
    ]
];
$dotted = Arrays::dot($data);
// Result: ['app.name' => 'MyApp', 'app.version' => '1.0']
```

#### `only(array $array, array $keys): array`
Get only specified keys.

```php
$data = ['name' => 'John', 'email' => 'john@example.com', 'age' => 30];
$subset = Arrays::only($data, ['name', 'email']);
// Result: ['name' => 'John', 'email' => 'john@example.com']
```

#### `except(array $array, array $keys): array`
Get all except specified keys.

```php
$data = ['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret'];
$safe = Arrays::except($data, ['password']);
// Result: ['name' => 'John', 'email' => 'john@example.com']
```

#### `first(array $array, ?callable $callback = null, mixed $default = null): mixed`
Get first element matching criteria.

```php
$numbers = [1, 2, 3, 4, 5];
$first = Arrays::first($numbers); // 1

$firstEven = Arrays::first($numbers, fn($n) => $n % 2 === 0); // 2
$firstLarge = Arrays::first($numbers, fn($n) => $n > 10, 'none'); // 'none'
```

#### `pluck(array $array, string $value, ?string $key = null): array`
Extract a column of values.

```php
$users = [
    ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
    ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com']
];

$names = Arrays::pluck($users, 'name');
// Result: ['John', 'Jane']

$namesByEmail = Arrays::pluck($users, 'name', 'email');
// Result: ['john@example.com' => 'John', 'jane@example.com' => 'Jane']
```

#### `groupBy(array $array, string|callable $key): array`
Group array by key or callback result.

```php
$products = [
    ['name' => 'Laptop', 'category' => 'Electronics'],
    ['name' => 'Phone', 'category' => 'Electronics'],
    ['name' => 'Book', 'category' => 'Education']
];

$grouped = Arrays::groupBy($products, 'category');
// Result: [
//   'Electronics' => [['name' => 'Laptop', ...], ['name' => 'Phone', ...]],
//   'Education' => [['name' => 'Book', ...]]
// ]
```

#### `sortBy(array $array, string|callable $key, bool $descending = false): array`
Sort array by key or callback.

```php
$users = [
    ['name' => 'John', 'age' => 30],
    ['name' => 'Jane', 'age' => 25],
    ['name' => 'Bob', 'age' => 35]
];

$sorted = Arrays::sortBy($users, 'age');
// Sorted by age ascending

$sortedDesc = Arrays::sortBy($users, 'age', true);
// Sorted by age descending
```

#### `sumBy(array $array, string|callable|null $selector = null): float|int`
Sum numeric values, optionally using a key (with dot notation) or callback.

```php
$orders = [
    ['price' => 12.5, 'quantity' => 2],
    ['price' => 7, 'quantity' => 1],
    ['price' => null, 'quantity' => 4],
];

$totalPrice = Arrays::sumBy($orders, 'price');
// Result: 19.5

$inventoryValue = Arrays::sumBy($orders, fn($order) => ($order['price'] ?? 0) * $order['quantity']);
// Result: 32.0

$plainSum = Arrays::sumBy([1, '2', 3.5]);
// Result: 6.5
```

#### `random(array $array, int $number = 1): mixed`
Get random element(s).

```php
$colors = ['red', 'green', 'blue', 'yellow'];
$randomColor = Arrays::random($colors); // e.g., 'green'
$randomColors = Arrays::random($colors, 2); // e.g., ['red', 'blue']
```

#### `keyBy(array $array, string|callable $key): array`
Key array by specific field value (supports dot notation).

```php
$users = [
    ['id' => 1, 'name' => 'John', 'profile' => ['username' => 'john123']],
    ['id' => 2, 'name' => 'Jane', 'profile' => ['username' => 'jane456']]
];

$byId = Arrays::keyBy($users, 'id');
// Result: [1 => ['id' => 1, 'name' => 'John', ...], 2 => ['id' => 2, 'name' => 'Jane', ...]]

$byUsername = Arrays::keyBy($users, 'profile.username');
// Result: ['john123' => ['id' => 1, ...], 'jane456' => ['id' => 2, ...]]

// Using callback
$byFirstLetter = Arrays::keyBy($users, fn($user) => strtoupper($user['name'][0]));
// Result: ['J' => ['id' => 2, 'name' => 'Jane', ...]] (last John/Jane wins)
```

#### `last(array $array, ?callable $callback = null, mixed $default = null): mixed`
Get last element matching criteria.

```php
$numbers = [1, 2, 3, 4, 5];
$last = Arrays::last($numbers); // 5

$lastEven = Arrays::last($numbers, fn($n) => $n % 2 === 0); // 4
$lastLarge = Arrays::last($numbers, fn($n) => $n > 10, 'none'); // 'none'
```

#### `where(array $array, callable $callback): array`
Filter array using callback.

```php
$users = [
    ['name' => 'John', 'age' => 30, 'active' => true],
    ['name' => 'Jane', 'age' => 25, 'active' => false],
    ['name' => 'Bob', 'age' => 35, 'active' => true]
];

$adults = Arrays::where($users, fn($user) => $user['age'] >= 30);
// Result: [['name' => 'John', ...], ['name' => 'Bob', ...]]
```

#### `whereEquals(array $array, string $key, mixed $value): array`
Filter array where value equals.

```php
$users = [
    ['name' => 'John', 'status' => 'active'],
    ['name' => 'Jane', 'status' => 'inactive'],
    ['name' => 'Bob', 'status' => 'active']
];

$activeUsers = Arrays::whereEquals($users, 'status', 'active');
// Result: [['name' => 'John', ...], ['name' => 'Bob', ...]]
```

#### `whereIn(array $array, string $key, array $values): array`
Filter array where value is in array.

```php
$users = [
    ['name' => 'John', 'role' => 'admin'],
    ['name' => 'Jane', 'role' => 'user'],
    ['name' => 'Bob', 'role' => 'moderator']
];

$privilegedUsers = Arrays::whereIn($users, 'role', ['admin', 'moderator']);
// Result: [['name' => 'John', ...], ['name' => 'Bob', ...]]
```

#### `whereNotIn(array $array, string $key, array $values): array`
Filter array where value is not in array.

```php
$users = [
    ['name' => 'John', 'role' => 'admin'],
    ['name' => 'Jane', 'role' => 'user'],
    ['name' => 'Bob', 'role' => 'banned']
];

$allowedUsers = Arrays::whereNotIn($users, 'role', ['banned', 'suspended']);
// Result: [['name' => 'John', ...], ['name' => 'Jane', ...]]
```

#### `isAssoc(array $array): bool`
Check if array is associative.

```php
$indexed = [1, 2, 3];
$assoc = ['name' => 'John', 'age' => 30];

echo Arrays::isAssoc($indexed); // false
echo Arrays::isAssoc($assoc); // true
```

#### `isSequential(array $array): bool`
Check if array is sequential (list).

```php
$sequential = [1, 2, 3];
$assoc = ['name' => 'John', 'age' => 30];

echo Arrays::isSequential($sequential); // true
echo Arrays::isSequential($assoc); // false
```

#### `shuffle(array $array): array`
Shuffle array preserving keys.

```php
$data = ['a' => 1, 'b' => 2, 'c' => 3];
$shuffled = Arrays::shuffle($data);
// Result: Keys preserved but order randomized, e.g., ['c' => 3, 'a' => 1, 'b' => 2]
```

#### `collapse(array $array): array`
Collapse array of arrays into single array.

```php
$nested = [[1, 2], [3, 4], [5]];
$collapsed = Arrays::collapse($nested);
// Result: [1, 2, 3, 4, 5]
```

#### `crossJoin(array ...$arrays): array`
Cross join arrays.

```php
$colors = ['red', 'blue'];
$sizes = ['S', 'M'];

$combinations = Arrays::crossJoin($colors, $sizes);
// Result: [['red', 'S'], ['red', 'M'], ['blue', 'S'], ['blue', 'M']]
```

#### `divide(array $array): array`
Divide array into keys and values.

```php
$data = ['name' => 'John', 'age' => 30];
[$keys, $values] = Arrays::divide($data);
// $keys = ['name', 'age']
// $values = ['John', 30]
```

#### `replaceRecursive(array $array, array ...$replacements): array`
Recursively replace values in array.

```php
$config = [
    'database' => ['host' => 'localhost', 'port' => 3306],
    'cache' => ['driver' => 'file']
];

$override = [
    'database' => ['host' => 'production.db.com'],
    'cache' => ['driver' => 'redis', 'ttl' => 3600]
];

$merged = Arrays::replaceRecursive($config, $override);
// Result: Nested arrays merged recursively
```

#### `duplicates(array $array): array`
Get duplicate values from array.

```php
$items = ['apple', 'banana', 'apple', 'orange', 'banana', 'apple'];
$dupes = Arrays::duplicates($items);
// Result: ['apple' => 3, 'banana' => 2]
```

#### `map(array $array, callable $callback): array`
Map array preserving keys.

```php
$prices = ['apple' => 1.50, 'banana' => 0.75, 'orange' => 2.00];
$withTax = Arrays::map($prices, fn($price, $key) => $price * 1.1);
// Result: ['apple' => 1.65, 'banana' => 0.825, 'orange' => 2.2]
```

#### `mapRecursive(array $array, callable $callback): array`
Recursively map array.

```php
$data = [
    'user' => ['name' => 'john', 'email' => 'JOHN@EXAMPLE.COM'],
    'meta' => ['tags' => ['PHP', 'javascript']]
];

$normalized = Arrays::mapRecursive($data, fn($value) => is_string($value) ? strtolower($value) : $value);
// Result: All string values converted to lowercase recursively
```

#### `wrap(mixed $value): array`
Wrap value in array if not already an array.

```php
$single = Arrays::wrap('hello'); // ['hello']
$multiple = Arrays::wrap(['a', 'b']); // ['a', 'b']
$null = Arrays::wrap(null); // []
```

#### `toQuery(string $array): string`
Convert array to query string.

```php
$params = ['name' => 'John Doe', 'age' => 30, 'tags' => ['php', 'web']];
$query = Arrays::toQuery($params);
// Result: "name=John%20Doe&age=30&tags%5B0%5D=php&tags%5B1%5D=web"
```

#### `fromQuery(string $query): array`
Parse query string to array.

```php
$query = 'name=John%20Doe&age=30&active=1';
$params = Arrays::fromQuery($query);
// Result: ['name' => 'John Doe', 'age' => '30', 'active' => '1']
```

#### `chunk(array $array, int $size, bool $preserveKeys = false): array`
Split array into chunks.

```php
$numbers = [1, 2, 3, 4, 5, 6, 7];
$chunks = Arrays::chunk($numbers, 3);
// Result: [[1, 2, 3], [4, 5, 6], [7]]
```

---

## AuthManager

Complete user authentication and session management system.

### Configuration Options

```php
AuthManager::init([
    'table' => 'users',                    // Users table name
    'email_column' => 'email',             // Email column name
    'password_column' => 'password',       // Password column name
    'primary_key' => 'id',                 // Primary key column
    'sessions' => true,                    // Enable session storage
    'session_key' => '_auth_user',         // Session key
    'remember_me' => true,                 // Enable remember me
    'remember_cookie' => 'phphelper_remember', // Cookie name
    'remember_duration' => 31104000,       // Cookie lifetime (360 days)
    'remember_secret' => 'your-secret',    // HMAC secret
    'remember_options' => [                // Cookie options
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]
]);
```

### Password Hashing

AuthManager hashes credentials with PHP's `password_hash()` using `PASSWORD_BCRYPT`. Existing hashes are transparently refreshed on login via `password_needs_rehash()` when configuration changes.

### Methods

#### `init(array $config = []): void`
Initialize authentication manager.

```php
// Basic setup
DB::sqlite(':memory:');
AuthManager::init();

// Custom configuration
AuthManager::init([
    'table' => 'members',
    'email_column' => 'username',
    'remember_duration' => 86400 * 7, // 7 days
    'remember_secret' => 'my-secret-key'
]);
```

#### `createUsersTable(array $options = []): void`
Create users table with appropriate schema.

```php
// Create default table
AuthManager::createUsersTable();

// Custom table with extra columns
AuthManager::createUsersTable([
    'table' => 'members',
    'extra_columns' => [
        'first_name' => 'VARCHAR(100)',
        'last_name' => 'VARCHAR(100)',
        'is_active' => 'BOOLEAN DEFAULT TRUE'
    ]
]);
```

#### `register(string $email, string $password, array $attributes = []): array`
Register new user.

```php
// Basic registration
$user = AuthManager::register('john@example.com', 'password123');
echo $user['id']; // User ID

// With additional attributes
$user = AuthManager::register(
    'jane@example.com',
    'secure_password',
    [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'role' => 'admin'
    ]
);
```

#### `login(string $email, string $password, bool $remember = false): ?array`
Authenticate user.

```php
// Basic login
$user = AuthManager::login('john@example.com', 'password123');
if ($user) {
    echo "Welcome, " . $user['email'];
} else {
    echo "Invalid credentials";
}

// With remember me
$user = AuthManager::login('john@example.com', 'password123', true);
```

#### `user(): ?array`
Get currently authenticated user.

```php
$currentUser = AuthManager::user();
if ($currentUser) {
    echo "Logged in as: " . $currentUser['email'];
} else {
    echo "Not logged in";
}
```

#### `isLoggedIn(): bool`
Check if user is authenticated.

```php
if (AuthManager::isLoggedIn()) {
    // Show user dashboard
} else {
    // Redirect to login
}
```

#### `requireAuth(?callable $onUnauthenticated = null): array`
Ensure a user is authenticated.

```php
$user = AuthManager::requireAuth();

// With custom unauthenticated handler
AuthManager::requireAuth(function () {
    Http::redirect('/login');
});
```

---

## Config

Database-backed configuration management system.

### Configuration Options

```php
Config::init([
    'table' => 'config',           // Configuration table name
    'key_column' => 'config_key',  // Key column name
    'value_column' => 'config_value' // Value column name
]);
```

### Methods

#### `init(array $config = []): void`
Initialize configuration system.

```php
DB::sqlite(':memory:');
Config::init();

// Custom table structure
Config::init([
    'table' => 'app_settings',
    'key_column' => 'setting_name',
    'value_column' => 'setting_value'
]);
```

#### `createConfigTable(array $options = []): void`
Create configuration table.

```php
Config::createConfigTable();
```

#### `set(string $key, ?string $value): void`
Set configuration value.

```php
Config::set('app.name', 'My Application');
Config::set('app.version', '1.0.0');
Config::set('database.host', 'localhost');
```

#### `get(string $key, ?string $default = null): ?string`
Get configuration value.

```php
$appName = Config::get('app.name', 'Default App');
$dbHost = Config::get('database.host');
$missing = Config::get('nonexistent.key', 'fallback');
```

#### `has(string $key): bool`
Check if configuration key exists.

```php
if (Config::has('app.debug')) {
    $debug = Config::get('app.debug');
}
```

#### `delete(string $key): bool`
Remove configuration value.

```php
$deleted = Config::delete('old.setting');
if ($deleted) {
    echo "Setting removed";
}
```

#### `all(): array`
Get all configuration values.

```php
$allConfig = Config::all();
foreach ($allConfig as $key => $value) {
    echo "$key = $value\n";
}
```

---

## Countries

Country data with flags, ISO codes, and names.

### Methods

#### `getAll(): array`
Get all countries array.

```php
$countries = Countries::getAll();
```

#### `getByCode(string $code): ?array`
Get country by ISO/ISO3 code.

```php
$country = Countries::getByCode('US'); // ["flag" => "🇺🇸", "name" => "United States"]
$country = Countries::getByCode('USA'); // Same result
$country = Countries::getByCode('DE'); // ["flag" => "🇩🇪", "name" => "Germany"]
```

#### `getByName(string $name): ?array`
Get country by name.

```php
$country = Countries::getByName('France'); // ["flag" => "🇫🇷", "name" => "France"]
```

#### `nameWithFlag(string $code): ?string`
Get formatted name with flag.

```php
echo Countries::nameWithFlag('JP'); // "🇯🇵 Japan"
echo Countries::nameWithFlag('BRA'); // "🇧🇷 Brazil"
```

### Usage Examples

```php
// Country dropdown
$countries = Countries::getAll();
foreach ($countries as $code => $data) {
    if (strlen($code) === 2) { // ISO codes only
        echo "<option value='{$code}'>{$data['flag']} {$data['name']}</option>";
    }
}

// Validate country code
if (Countries::getByCode($_POST['country'])) {
    // Valid country
}

// Display user country
$userCountry = Countries::nameWithFlag($user['country_code']);
echo "From: {$userCountry}";
```

---

## DB

Database abstraction layer providing clean PDO interface.

### Configuration Options

```php
// MySQL connection options
DB::mysql('database_name', 'username', 'password', [
    'host' => '127.0.0.1',        // Database host
    'port' => 3306,               // Port number
    'charset' => 'utf8mb4',       // Character set
    'unix_socket' => null,        // Unix socket path
    'attributes' => [             // PDO attributes
        PDO::ATTR_TIMEOUT => 30
    ]
]);

// PostgreSQL connection options  
DB::pgsql('database_name', 'username', 'password', [
    'host' => '127.0.0.1',
    'port' => 5432,
    'sslmode' => 'prefer',        // SSL mode
    'charset' => 'utf8',
    'application_name' => 'MyApp',
    'attributes' => []
]);

// SQLite connection options
DB::sqlite('/path/to/database.db', null, null, [
    'memory' => false,            // Use in-memory database
    'attributes' => []
]);
```

### Methods

#### `connect(string $dsn, ?string $username = null, ?string $password = null, array $options = []): void`
Establish PDO connection.

```php
// Manual DSN
DB::connect('mysql:host=localhost;dbname=test', 'user', 'pass');
```

#### `mysql(string $dbname, ?string $username = null, ?string $password = null, array $options = []): void`
Connect to MySQL/MariaDB.

```php
// Basic connection
DB::mysql('myapp', 'root', 'password');

// With custom host and port
DB::mysql('myapp', 'user', 'pass', [
    'host' => 'db.example.com',
    'port' => 3307,
    'charset' => 'utf8mb4'
]);

// Unix socket connection
DB::mysql('myapp', 'user', 'pass', [
    'unix_socket' => '/var/run/mysqld/mysqld.sock'
]);
```

#### `pgsql(string $dbname, ?string $username = null, ?string $password = null, array $options = []): void`
Connect to PostgreSQL.

```php
DB::pgsql('myapp', 'postgres', 'password', [
    'host' => 'localhost',
    'port' => 5432,
    'sslmode' => 'require'
]);
```

#### `sqlite(string $pathOrDsn = ':memory:', ?string $username = null, ?string $password = null, array $options = []): void`
Connect to SQLite.

```php
// File database
DB::sqlite('/path/to/database.db');

// In-memory database
DB::sqlite(':memory:');
// or
DB::sqlite('', null, null, ['memory' => true]);
```

#### `query(string $sql, array $params = []): PDOStatement`
Execute query and return statement.

```php
$stmt = DB::query('SELECT * FROM users WHERE age > ?', [18]);
while ($row = $stmt->fetch()) {
    echo $row['name'] . "\n";
}
```

#### `getRow(string $sql, array $params = []): ?array`
Get single row.

```php
$user = DB::getRow('SELECT * FROM users WHERE id = ?', [123]);
if ($user) {
    echo $user['name'];
}
```

#### `getRows(string $sql, array $params = []): array`
Get all matching rows.

```php
$users = DB::getRows('SELECT * FROM users WHERE active = ?', [1]);
foreach ($users as $user) {
    echo $user['name'] . "\n";
}
```

#### `getValue(string $sql, array $params = []): mixed`
Get single scalar value.

```php
$count = DB::getValue('SELECT COUNT(*) FROM users');
$name = DB::getValue('SELECT name FROM users WHERE id = ?', [123]);
```

#### `insert(string $table, array $data): string`
Insert row and return ID.

```php
$id = DB::insert('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'created_at' => date('Y-m-d H:i:s')
]);
echo "Created user with ID: $id";
```

#### `update(string $table, array $data, string $where, array $params = []): int`
Update rows.

```php
$affected = DB::update(
    'users',
    ['last_login' => date('Y-m-d H:i:s')],
    'id = ?',
    [123]
);
echo "Updated $affected rows";
```

#### `upsert(string $table, array $data, array|string $conflictColumns, ?array $updateColumns = null): void`
Insert or update on conflict.

```php
// Insert or update user by email
DB::upsert(
    'users',
    ['email' => 'john@example.com', 'name' => 'John Doe', 'login_count' => 1],
    'email', // conflict column
    ['name', 'login_count'] // columns to update
);
```

#### `delete(string $table, string $where, array $params = []): int`
Delete rows.

```php
$deleted = DB::delete('users', 'active = ?', [0]);
echo "Deleted $deleted inactive users";
```

#### `transaction(callable $callback): mixed`
Execute in transaction.

```php
$result = DB::transaction(function() {
    $userId = DB::insert('users', ['name' => 'John']);
    DB::insert('profiles', ['user_id' => $userId, 'bio' => 'Developer']);
    return $userId;
});
```

#### `cliBackupRestoreOptions(string $dbname, string $dbUser, string $dbPassword, array $options = []): array`
Generate menu-ready callbacks for backing up or restoring a MySQL database from the CLI.

```php
use PhpHelper\{App, DB};

if (App::isCli()) {
    $menu = DB::cliBackupRestoreOptions('phphelper', 'root', 'secret', [
        'defaults' => [
            'file' => 'storage/backups/local.sql',
        ],
        'backup_options' => [
            'mysqldump_path' => '/usr/local/mysql/bin/mysqldump',
        ],
    ]);

    App::cliMenu($menu);
}
```

- Returns an array compatible with `App::cliMenu`, combining either or both "Backup" and "Restore" actions.
- Respects `defaults`, `backup_options`, `restore_options`, and optional `mode` (`backup` or `restore`) to limit which entries are generated.
- Each callback prints progress messages and delegates to `DB::backup()` / `DB::restore()` internally.

---

## Date

Date and time formatting utilities with human-friendly output.

### Methods

#### `ago(\DateTimeInterface|int|string $timestamp, bool $full = false): string`
Get human-readable relative time.

```php
// Recent time
echo Date::ago(time() - 3600); // "1 hour ago"
echo Date::ago('2023-01-01 12:00:00'); // "5 months ago"

// Full format
echo Date::ago(time() - 3665, true); // "1 hour, 1 minute, 5 seconds ago"

// Future time
echo Date::ago(time() + 1800); // "30 minutes from now"
```

#### `format(\DateTimeInterface|int|string $timestamp, string $format = 'datetime', ?string $timezone = null): string`
Format dates with presets or custom patterns.

**Available Presets:**
- `date` → Y-m-d
- `datetime` → Y-m-d H:i  
- `datetime_seconds` → Y-m-d H:i:s
- `time` → H:i
- `time_seconds` → H:i:s
- `iso` → ISO 8601 format
- `rfc2822` → RFC 2822 format
- `rss` → RSS format
- `human` → j M Y
- `human_full` → j F Y, H:i

```php
$now = time();

// Preset formats
echo Date::format($now, 'date'); // "2023-12-25"
echo Date::format($now, 'datetime'); // "2023-12-25 14:30"
echo Date::format($now, 'human'); // "25 Dec 2023"
echo Date::format($now, 'iso'); // "2023-12-25T14:30:00+00:00"

// Custom format
echo Date::format($now, 'l, F j, Y'); // "Monday, December 25, 2023"

// With timezone
echo Date::format($now, 'datetime', 'America/New_York');
echo Date::format('2023-12-25 10:00:00 UTC', 'datetime', 'Asia/Tokyo');
```

#### `timestamp(\DateTimeInterface|int|string|null $value = null, ?string $timezone = null): int`
Get UNIX timestamp.

```php
// Current timestamp
$now = Date::timestamp(); // equivalent to time()

// From various inputs
$ts1 = Date::timestamp('2023-12-25 12:00:00');
$ts2 = Date::timestamp(new DateTime('2023-12-25'));
$ts3 = Date::timestamp('Dec 25, 2023', 'America/New_York');
```

---

## Files

File system operations and utilities.

### Methods

#### `read(string $path): string|false`
Read file contents.

```php
$content = Files::read('/path/to/file.txt');
if ($content !== false) {
    echo $content;
} else {
    echo "File not found or not readable";
}
```

#### `write(string $path, string $content, int $flags = 0): int|false`
Write content to file.

```php
// Basic write
$bytes = Files::write('/path/to/file.txt', 'Hello World');

// Append to file
$bytes = Files::write('/path/to/file.txt', 'More content', FILE_APPEND);

// Write with lock
$bytes = Files::write('/path/to/file.txt', 'Safe content', LOCK_EX);
```

#### `append(string $path, string $content): int|false`
Append content to file.

```php
Files::append('/path/to/log.txt', date('Y-m-d H:i:s') . " - Log entry\n");
```

#### `copy(string $source, string $dest): bool`
Copy file.

```php
$success = Files::copy('/source/file.txt', '/destination/file.txt');
```

#### `move(string $source, string $dest): bool`
Move/rename file.

```php
$success = Files::move('/old/path.txt', '/new/path.txt');
```

#### `delete(string $path): bool`
Delete file.

```php
$deleted = Files::delete('/path/to/unwanted.txt');
```

#### `exists(string $path): bool`
Check if file exists.

```php
if (Files::exists('/path/to/file.txt')) {
    echo "File exists";
}
```

#### `size(string $path): int|false`
Get file size in bytes.

```php
$size = Files::size('/path/to/file.txt');
echo "File size: " . Format::bytes($size);
```

#### `extension(string $path): string`
Get file extension.

```php
$ext = Files::extension('/path/to/document.pdf'); // "pdf"
$ext = Files::extension('image.JPEG'); // "jpeg"
```

#### `mimeType(string $path): string|false`
Get MIME type.

```php
$mime = Files::mimeType('/path/to/image.jpg'); // "image/jpeg"
```

#### `readJson(string $path): mixed`
Read and parse JSON file.

```php
$data = Files::readJson('/path/to/config.json');
if ($data !== false) {
    echo $data['app']['name'];
}
```

#### `writeJson(string $path, mixed $data, int $flags = JSON_PRETTY_PRINT): int|false`
Write data as JSON.

```php
$config = ['app' => ['name' => 'MyApp', 'version' => '1.0']];
Files::writeJson('/path/to/config.json', $config);

// Compact JSON
Files::writeJson('/path/to/data.json', $data, 0);
```

#### `readCsv(string $path, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): array|false`
Read CSV file.

```php
$rows = Files::readCsv('/path/to/data.csv');
foreach ($rows as $row) {
    echo implode(' | ', $row) . "\n";
}

// Custom delimiter
$rows = Files::readCsv('/path/to/data.tsv', "\t");
```

#### `writeCsv(string $path, array $data, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): bool`
Write CSV file.

```php
$data = [
    ['Name', 'Email', 'Age'],
    ['John Doe', 'john@example.com', '30'],
    ['Jane Smith', 'jane@example.com', '25']
];
Files::writeCsv('/path/to/export.csv', $data);
```

#### `hash(string $path, string $algo = 'sha256'): string|false`
Get file hash.

```php
$sha256 = Files::hash('/path/to/file.txt');
$md5 = Files::hash('/path/to/file.txt', 'md5');
```

#### `createDirectory(string $path, int $permissions = 0755): bool`
Create directory recursively.

```php
Files::createDirectory('/path/to/nested/directory');
```

#### `deleteDirectory(string $path): bool`
Delete directory recursively.

```php
Files::deleteDirectory('/path/to/temp/folder');
```

#### `listFiles(string $path, string $pattern = '*'): array`
List files in directory.

```php
$allFiles = Files::listFiles('/path/to/directory');
$phpFiles = Files::listFiles('/path/to/src', '*.php');
$images = Files::listFiles('/uploads', '*.{jpg,png,gif}');
```

---

## Format

Data formatting utilities for human-readable output.

### Methods

#### `bytes(int $bytes, int $precision = 2, string $system = 'binary', ?array $units = null): string`
Format bytes into human-readable sizes.

**System Options:**
- `binary` (default): 1024-based with KB/MB/GB
- `iec`: 1024-based with KiB/MiB/GiB  
- `si`: 1000-based with KB/MB/GB

```php
echo Format::bytes(1536); // "1.50 KB"
echo Format::bytes(1048576); // "1.00 MB"
echo Format::bytes(1536, 1, 'iec'); // "1.5 KiB"
echo Format::bytes(1000, 2, 'si'); // "1.00 KB"

// Custom units
echo Format::bytes(1024, 2, 'binary', ['B', 'Kilobytes', 'Megabytes']);
```

#### `number(float|int $value, int $decimals = 0, string $decimalPoint = '.', string $thousandsSep = ','): string`
Format numbers.

```php
echo Format::number(1234.567); // "1,235"
echo Format::number(1234.567, 2); // "1,234.57"
echo Format::number(1234.567, 2, ',', ' '); // "1 234,57"
```

#### `currency(float $amount, string $currency = 'USD', ?string $locale = null, ?int $precision = null): string`
Format currency amounts.

```php
echo Format::currency(1234.56); // "1,234.56 USD"
echo Format::currency(1234.56, 'EUR', 'de_DE'); // "1.234,56 €"
echo Format::currency(1234.56, 'USD', 'en_US'); // "$1,234.56"
echo Format::currency(1234.5, 'USD', null, 0); // "$1,235"
```

#### `percent(float $value, int $precision = 0, bool $fromFraction = true): string`
Format percentages.

```php
echo Format::percent(0.1234); // "12%"
echo Format::percent(0.1234, 2); // "12.34%"
echo Format::percent(12.34, 1, false); // "12.3%" (already percentage)
```

#### `shortNumber(float|int $value, int $precision = 1): string`
Format large numbers with abbreviations.

```php
echo Format::shortNumber(1234); // "1.2K"
echo Format::shortNumber(1234567); // "1.2M"
echo Format::shortNumber(1234567890); // "1.2B"
echo Format::shortNumber(1500, 0); // "2K"
```

#### `duration(int $seconds, bool $compact = true): string`
Format time duration.

```php
echo Format::duration(3665); // "1h 1m 5s"
echo Format::duration(3665, false); // "1 hour, 1 minute, 5 seconds"
echo Format::duration(90); // "1m 30s"
echo Format::duration(86400); // "1d"
```

#### `hms(int $seconds, bool $withDays = false): string`
Format as HH:MM:SS clock time.

```php
echo Format::hms(3665); // "01:01:05"
echo Format::hms(90065, true); // "1d 01:01:05"
```

#### `ordinal(int $number): string`
Add ordinal suffix to numbers.

```php
echo Format::ordinal(1); // "1st"
echo Format::ordinal(22); // "22nd"
echo Format::ordinal(103); // "103rd"
echo Format::ordinal(11); // "11th"
```

#### `parseBytes(string $size): int`
Parse human-readable size to bytes.

```php
echo Format::parseBytes('1.5K'); // 1536
echo Format::parseBytes('2M'); // 2097152
echo Format::parseBytes('1.5 GB'); // 1610612736
echo Format::parseBytes('500'); // 500
```

#### `bool(mixed $value, string $true = 'Yes', string $false = 'No', string $null = ''): string`
Format boolean values.

```php
echo Format::bool(true); // "Yes"
echo Format::bool(false); // "No"
echo Format::bool(1); // "Yes"
echo Format::bool('true'); // "Yes"
echo Format::bool(null); // ""
echo Format::bool(true, 'Active', 'Inactive'); // "Active"
```

#### `json(mixed $value, bool $pretty = true, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES): string`
Format JSON for display.

```php
$data = ['name' => 'John', 'age' => 30];
echo Format::json($data); // Pretty printed JSON
echo Format::json($data, false); // Compact JSON
```

---

## Http

HTTP utilities and client information detection.

### Methods

#### `redirect(string $url, int $statusCode = 302, bool $exit = true): void`
Redirect to URL.

```php
// Simple redirect
Http::redirect('/dashboard');

// Permanent redirect
Http::redirect('https://example.com', 301);

// Don't exit after redirect
Http::redirect('/page', 302, false);
```

#### `download(string $filename, string $mimetype = 'application/octet-stream')`
Stream file as download.

```php
// Download file
Http::download('/path/to/document.pdf', 'application/pdf');

// Let browser detect MIME type
Http::download('/path/to/image.jpg');
```

#### `json(mixed $data, int $status = 200, array $headers = []): void`
Output JSON response.

```php
// Simple JSON response
Http::json(['status' => 'success', 'data' => $result]);

// With custom status and headers
Http::json(
    ['error' => 'Not found'],
    404,
    ['X-Custom-Header' => 'value']
);
```

#### `clientInfo(): array`
Get comprehensive client information.

```php
$info = Http::clientInfo();

// IP information
echo $info['ip']; // Primary IP address
print_r($info['ips']); // All detected IPs
echo $info['is_proxy'] ? 'Behind proxy' : 'Direct';

// Browser detection
echo $info['browser']; // "Chrome", "Firefox", etc.
echo $info['browser_version']; // "91.0.4472.124"
echo $info['os']; // "Windows", "macOS", "Linux", etc.
echo $info['engine']; // "Blink", "Gecko", "WebKit"

// Device classification
echo $info['device']; // "desktop", "mobile", "tablet", "bot"
echo $info['is_mobile'] ? 'Mobile' : 'Not mobile';
echo $info['is_tablet'] ? 'Tablet' : 'Not tablet';
echo $info['is_bot'] ? 'Bot detected' : 'Human user';

// Language preferences
echo $info['accept_language']; // Raw header
print_r($info['languages']); // Parsed languages by preference

// Request information
echo $info['method']; // "GET", "POST", etc.
echo $info['scheme']; // "http" or "https"
echo $info['host']; // "example.com"
echo $info['port']; // 80, 443, etc.
echo $info['path']; // "/path/to/page"
echo $info['query']; // "param=value"
echo $info['url']; // Full URL
echo $info['referer']; // Referring page
```

---

## Logs

Database-backed logging system with multiple severity levels.

### Configuration Options

```php
Logs::init([
    'table' => 'logs',                    // Log table name
    'context_defaults' => [               // Data merged into every context payload
        'application' => 'MyApp',
        'environment' => 'production',
        'user_id' => getCurrentUserId(),
    ],
    'meta_defaults' => [                  // Data merged into every meta payload
        'request_id' => $_SERVER['HTTP_X_REQUEST_ID'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ],
]);
```

### Methods

#### `init(array $config = []): void`
Initialize logging system.

```php
DB::sqlite(':memory:');
Logs::init([
    'table' => 'application_logs',
    'context_defaults' => [
        'app_version' => '1.0.0',
        'server' => $_SERVER['SERVER_NAME'] ?? 'unknown',
    ],
    'meta_defaults' => [
        'environment' => getenv('APP_ENV') ?: 'local',
    ],
]);
```

#### `createLogsTable(?string $table = null): void`
Create logs table.

```php
Logs::createLogsTable(); // Use configured table
Logs::createLogsTable('custom_logs'); // Custom table name
```

#### `log(string $level, string $message, array $context = [], array $meta = []): string`
Log message with custom level.

```php
$id = Logs::log('custom', 'User action performed', [
    'user_id' => 123,
    'action' => 'file_upload',
    'file_name' => 'document.pdf'
], [
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT']
]);
```

#### Log Level Methods
Pre-defined methods for standard log levels:

```php
// Debug information
Logs::debug('Query executed', ['sql' => 'SELECT * FROM users', 'time' => 0.023]);

// General information
Logs::info('User logged in', ['user_id' => 123, 'email' => 'john@example.com']);

// Success operations
Logs::success('File uploaded successfully', ['filename' => 'document.pdf', 'size' => 1024]);

// Notices
Logs::notice('Configuration changed', ['setting' => 'debug_mode', 'old' => false, 'new' => true]);

// Warnings
Logs::warning('Low disk space', ['disk' => '/var', 'available' => '500MB']);

// Errors
Logs::error('Database connection failed', ['host' => 'localhost', 'error' => 'Connection refused']);

// Critical issues
Logs::critical('Payment processing failed', ['transaction_id' => 'tx_123', 'amount' => 99.99]);

// Alerts requiring immediate attention
Logs::alert('Security breach detected', ['ip' => '192.168.1.100', 'attempts' => 50]);

// Emergency situations
Logs::emergency('System shutdown imminent', ['reason' => 'memory_exhausted']);
```

#### `setContextDefaults(array $defaults): void`
Merge values into every context payload.

```php
Logs::setContextDefaults([
    'user_id' => getCurrentUserId(),
    'session_id' => session_id(),
]);
```

#### `setMetaDefaults(array $defaults): void`
Merge values into every meta payload.

```php
Logs::setMetaDefaults([
    'request_id' => uniqid(),
    'server' => $_SERVER['SERVER_NAME'] ?? 'unknown',
]);
```

---

## PrettyErrorHandler

Enhanced error display for development and debugging.

### Configuration Options

```php
PrettyErrorHandler::init([
    'display' => true,              // Force display errors
    'report' => E_ALL,              // Error reporting level
    'context_before' => 6,          // Lines before error
    'context_after' => 4,           // Lines after error
    'show_trace' => true,           // Include backtrace
    'overlay' => true,              // Render as overlay vs full page
    'skip_warnings' => false,       // Skip PHP warnings
    'log_errors' => false          // Log to pretty_errors.txt
]);
```

### Methods

#### `init(array $options = []): self`
Enable pretty error handling.

```php
// Basic setup for development
PrettyErrorHandler::init();

// Custom configuration
PrettyErrorHandler::init([
    'overlay' => false,        // Full page instead of overlay
    'context_before' => 10,    // More context lines
    'log_errors' => true,      // Save errors to file
    'skip_warnings' => true    // Only show errors and fatals
]);
```

#### `__construct(array $options = [], bool $registerGlobal = true)`
Create handler instance.

```php
// Auto-register
$handler = new PrettyErrorHandler([
    'show_trace' => false,
    'overlay' => true
]);

// Manual registration
$handler = new PrettyErrorHandler(['display' => true], false);
$handler->register();
```

### Features

- **Syntax-highlighted code context** around error line
- **Clean stack traces** with clickable file paths
- **Overlay mode** that doesn't disrupt page layout
- **Copy-to-clipboard** functionality for error details
- **CLI support** with colored output
- **Error logging** to file for production debugging

---

## Str

Comprehensive string manipulation utilities.

### Methods

#### `startsWith(string $text, string|array $prefixes, bool $caseSensitive = true): bool`
Check if string starts with prefix(es).

```php
$url = 'https://example.com';
echo Str::startsWith($url, 'https://'); // true
echo Str::startsWith($url, ['http://', 'https://']); // true
echo Str::startsWith('Hello', 'HELLO', false); // true (case insensitive)
```

#### `endsWith(string $text, string|array $suffixes, bool $caseSensitive = true): bool`
Check if string ends with suffix(es).

```php
$filename = 'document.PDF';
echo Str::endsWith($filename, '.pdf', false); // true
echo Str::endsWith($filename, ['.doc', '.pdf'], false); // true
```

#### `contains(string $text, string|array $fragments, bool $caseSensitive = true): bool`
Check if string contains fragment(s).

```php
$text = 'The quick brown fox';
echo Str::contains($text, 'quick'); // true
echo Str::contains($text, ['cat', 'fox']); // true
echo Str::contains($text, 'QUICK', false); // true
```

#### `slug(string $value, string $separator = '-'): string`
Convert to URL-friendly slug.

```php
echo Str::slug('Hello World!'); // "hello-world"
echo Str::slug('Café & Restaurant', '_'); // "cafe_restaurant"
echo Str::slug('Ñoño García'); // "nono-garcia"
```

#### `camel(string $value): string`
Convert to camelCase.

```php
echo Str::camel('hello_world'); // "helloWorld"
echo Str::camel('user-name'); // "userName"
echo Str::camel('first name'); // "firstName"
```

#### `snake(string $value, string $delimiter = '_'): string`
Convert to snake_case.

```php
echo Str::snake('HelloWorld'); // "hello_world"
echo Str::snake('userName'); // "user_name"
echo Str::snake('XMLParser', '-'); // "xml-parser"
```

#### `studly(string $value): string`
Convert to StudlyCase.

```php
echo Str::studly('hello_world'); // "HelloWorld"
echo Str::studly('user-name'); // "UserName"
```

#### `upper(string $value, ?string $lang = null): string`
Convert to uppercase with locale support.

```php
echo Str::upper('hello world'); // "HELLO WORLD"
echo Str::upper('café', 'tr'); // Turkish locale
```

#### `lower(string $value, ?string $lang = null): string`
Convert to lowercase with locale support.

```php
echo Str::lower('HELLO WORLD'); // "hello world"
echo Str::lower('CAFÉ', 'tr'); // Turkish locale
```

#### `title(string $value, ?string $lang = null): string`
Convert to Title Case.

```php
echo Str::title('hello world'); // "Hello World"
echo Str::title('HELLO WORLD'); // "Hello World"
```

#### `limit(string $value, int $limit = 100, string $end = '...'): string`
Limit string length.

```php
$text = 'This is a very long string that needs to be truncated';
echo Str::limit($text, 20); // "This is a very long..."
echo Str::limit($text, 20, ' [more]'); // "This is a very long [more]"
```

#### `words(string $value, int $words = 100, string $end = '...'): string`
Limit word count.

```php
$text = 'Lorem ipsum dolor sit amet consectetur adipiscing elit';
echo Str::words($text, 4); // "Lorem ipsum dolor sit..."
echo Str::words($text, 3, ' [continue]'); // "Lorem ipsum dolor [continue]"
```

#### `before(string $text, string $search, bool $caseSensitive = true): string`
Get text before first occurrence.

```php
echo Str::before('user@example.com', '@'); // "user"
echo Str::before('Hello World', ' '); // "Hello"
```

#### `after(string $text, string $search, bool $caseSensitive = true): string`
Get text after first occurrence.

```php
echo Str::after('user@example.com', '@'); // "example.com"
echo Str::after('Hello World', ' '); // "World"
```

#### `between(string $text, string $from, string $to, bool $caseSensitive = true): ?string`
Extract text between delimiters.

```php
echo Str::between('Hello [World] Test', '[', ']'); // "World"
echo Str::between('<h1>Title</h1>', '<h1>', '</h1>'); // "Title"
echo Str::between('No match here', '[', ']'); // null
```

#### `replaceFirst(string $text, string $search, string $replace, bool $caseSensitive = true): string`
Replace first occurrence.

```php
echo Str::replaceFirst('foo bar foo', 'foo', 'baz'); // "baz bar foo"
```

#### `replaceLast(string $text, string $search, string $replace, bool $caseSensitive = true): string`
Replace last occurrence.

```php
echo Str::replaceLast('foo bar foo', 'foo', 'baz'); // "foo bar baz"
```

#### `ensurePrefix(string $text, string $prefix, bool $caseSensitive = true): string`
Ensure string starts with prefix.

```php
echo Str::ensurePrefix('example.com', 'https://'); // "https://example.com"
echo Str::ensurePrefix('https://example.com', 'https://'); // "https://example.com"
```

#### `ensureSuffix(string $text, string $suffix, bool $caseSensitive = true): string`
Ensure string ends with suffix.

```php
echo Str::ensureSuffix('filename', '.txt'); // "filename.txt"
echo Str::ensureSuffix('filename.txt', '.txt'); // "filename.txt"
```

#### `squish(string $text): string`
Collapse whitespace and trim.

```php
echo Str::squish("  Hello\n\t World  "); // "Hello World"
```

#### `randomString(int $length = 16): string`
Generate cryptographically secure random string.

```php
echo Str::randomString(); // e.g., "aB3xY9mK2nP7qR8s"
echo Str::randomString(32); // 32-character string
```

#### `uuid4(): string`
Generate UUID v4.

```php
echo Str::uuid4(); // e.g., "f47ac10b-58cc-4372-a567-0e02b2c3d479"
```

#### `isJson(string $value): bool`
Check if string is valid JSON.

```php
echo Str::isJson('{"name":"John"}'); // true
echo Str::isJson('invalid json'); // false
```

#### SEO and Debug Utilities

```php
// SEO-friendly filename
echo Str::seoFileName('My Document (2023).pdf'); // "my-document-2023.pdf"

// SEO-friendly URL
echo Str::seoUrl('Product Category & Items'); // "product-category-items"

// Debug helpers
Str::prettyLog($complexArray); // Pretty print with <pre>
Str::prettyLogExit($data); // Pretty print and exit
```

---

## TwigHelper

Simplified Twig template engine integration.

### Configuration Options

```php
TwigHelper::init([
    // Template directories (can be string or array)
    '/path/to/templates',
    
    // Namespaced paths
    [
        'admin' => '/path/to/admin/templates',
        'email' => '/path/to/email/templates'
    ]
], [
    // Twig environment options
    'cache' => '/path/to/cache',          // Template cache directory
    'auto_reload' => true,                // Auto-reload templates in dev
    'strict_variables' => false,          // Strict variable checking
    'debug' => true                       // Enable debug mode
]);
```

### Methods

#### `init(string|array $paths = [], array $options = [], ?\Twig\Loader\LoaderInterface $loader = null): \Twig\Environment`
Initialize Twig environment.

```php
// Basic setup
TwigHelper::init('/path/to/templates');

// Multiple directories
TwigHelper::init([
    '/path/to/app/templates',
    '/path/to/shared/templates'
]);

// Namespaced templates
TwigHelper::init([
    'app' => '/path/to/app/templates',
    'admin' => '/path/to/admin/templates',
    '/path/to/shared/templates' // default namespace
]);

// With custom options
TwigHelper::init('/templates', [
    'cache' => '/tmp/twig',
    'auto_reload' => App::isLocal(),
    'strict_variables' => true
]);
```

#### `render(string $template, array $context = [], ?\Twig\Environment $environment = null): string`
Render template.

```php
// Basic rendering
$html = TwigHelper::render('page.html.twig', [
    'title' => 'Welcome',
    'user' => AuthManager::user()
]);

// Namespaced template
$html = TwigHelper::render('@admin/dashboard.html.twig', [
    'stats' => $dashboardStats
]);

// Complex context
$html = TwigHelper::render('product/detail.html.twig', [
    'product' => $product,
    'related' => $relatedProducts,
    'reviews' => $reviews,
    'user_can_review' => AuthManager::isLoggedIn()
]);
```

#### `addGlobal(string $name, mixed $value, ?\Twig\Environment $environment = null): void`
Add global variable.

```php
// Make current user available everywhere
TwigHelper::addGlobal('current_user', AuthManager::user());

// Application config
TwigHelper::addGlobal('app_name', Config::get('app.name'));
TwigHelper::addGlobal('app_version', '1.0.0');

// Utility functions
TwigHelper::addGlobal('is_local', App::isLocal());
```

#### `addFunction(string $name, callable $callable, array $options = [], ?\Twig\Environment $environment = null): void`
Register custom function.

```php
// Simple function
TwigHelper::addFunction('asset_url', function($path) {
    return '/assets/' . ltrim($path, '/');
});

// Function with context access
TwigHelper::addFunction('can', function($permission) {
    $user = AuthManager::user();
    return $user && in_array($permission, $user['permissions'] ?? []);
}, ['needs_context' => false]);

// Safe HTML function
TwigHelper::addFunction('render_widget', function($widgetName, $data) {
    return WidgetRenderer::render($widgetName, $data);
}, ['is_safe' => ['html']]);
```

#### `addFilter(string $name, callable $callable, array $options = [], ?\Twig\Environment $environment = null): void`
Register custom filter.

```php
// Custom formatting filter
TwigHelper::addFilter('currency', function($amount, $currency = 'USD') {
    return Format::currency($amount, $currency);
});

// String manipulation filter
TwigHelper::addFilter('excerpt', function($text, $length = 100) {
    return Str::words($text, $length);
});

// Usage in templates:
// {{ price|currency('EUR') }}
// {{ article.content|excerpt(50) }}
```

#### `addPath(string $path, ?string $namespace = null, ?\Twig\Environment $environment = null): void`
Add template directory.

```php
// Add to default namespace
TwigHelper::addPath('/path/to/new/templates');

// Add namespaced path
TwigHelper::addPath('/path/to/plugins/templates', 'plugins');
```

### Built-in Filters and Functions

TwigHelper automatically registers many useful filters and functions:

**Filters:**
- `bytes` - Format::bytes()
- `currency` - Format::currency()
- `ago` - Date::ago()
- `slug` - Str::slug()
- `camel` - Str::camel()
- `snake` - Str::snake()
- `limit` - Str::limit()
- `json_pretty` - Format::json()

**Functions:**
- `format_date` - Date::format()
- `array_get` - Arrays::get()
- `format_bytes` - Format::bytes()
- `with_query_params` - Add/modify URL parameters

**Usage in templates:**
```twig
{# Format file size #}
File size: {{ file.size|bytes }}

{# Format currency #}
Price: {{ product.price|currency('EUR') }}

{# Relative time #}
Posted {{ post.created_at|ago }}

{# Array access with dot notation #}
{{ array_get(config, 'app.name') }}

{# URL manipulation #}
<a href="{{ with_query_params('page', 2) }}">Next Page</a>
<a href="{{ with_query_params({'sort': 'name', 'order': 'desc'}) }}">Sort by Name</a>
```

---

## Best Practices

### Database Usage
```php
// Always initialize DB connection first
DB::sqlite(':memory:');

// Use transactions for multiple operations
DB::transaction(function() {
    $userId = DB::insert('users', $userData);
    DB::insert('profiles', array_merge($profileData, ['user_id' => $userId]));
    return $userId;
});

// Use upsert for insert-or-update scenarios
DB::upsert('settings', [
    'key' => 'theme',
    'value' => 'dark',
    'updated_at' => date('Y-m-d H:i:s')
], 'key');
```

### Error Handling
```php
// Enable pretty errors in development
if (App::isLocal()) {
    PrettyErrorHandler::init([
        'overlay' => true,
        'log_errors' => true
    ]);
}
```

### Logging
```php
// Set up logging with defaults
Logs::init([
    'context_defaults' => [
        'application' => 'MyApp',
        'environment' => App::isProduction() ? 'prod' : 'dev',
        'user_id' => AuthManager::user()['id'] ?? null,
    ],
    'meta_defaults' => [
        'request_id' => Request::id(),
    ],
]);

// Create table once
Logs::createLogsTable();

// Log throughout your application
Logs::info('User logged in', ['user_id' => $user['id']]);
Logs::warning('API rate limit approaching', ['current' => 480, 'limit' => 500]);
```

### String Processing
```php
// Chain string operations
$slug = Str::slug(Str::limit($title, 50));

// Safe file naming
$filename = Str::seoFileName($userInput) . '.' . $extension;

// Validation
if (Str::isJson($input)) {
    $data = json_decode($input, true);
}
```

### Template Organization
```php
// Organize templates by feature
TwigHelper::init([
    'user' => '/templates/user',
    'admin' => '/templates/admin',
    'email' => '/templates/email',
    '/templates/shared' // default namespace
]);

// Use globals for common data
TwigHelper::addGlobal('site_name', Config::get('site.name'));
TwigHelper::addGlobal('current_user', AuthManager::user());
```

---

## Date

The Date class provides utilities for formatting dates and times with human-friendly options.

#### `ago(\DateTimeInterface|int|string $timestamp, bool $full = false): string`
Human friendly relative time (e.g., "2 hours ago", "in 3 days").

```php
use PhpHelper\Date;

echo Date::ago('2023-01-01'); // "11 months ago"
echo Date::ago(time() - 3600); // "1 hour ago"
echo Date::ago('2023-12-25', true); // "1 year, 2 months and 3 days ago"
```

#### `format(\DateTimeInterface|int|string $timestamp, string $format = 'datetime', ?string $timezone = null): string`
Easy date/time formatting with sensible presets.

```php
$date = '2023-12-25 15:30:45';

echo Date::format($date); // "2023-12-25 15:30"
echo Date::format($date, 'date'); // "2023-12-25"
echo Date::format($date, 'human'); // "25 Dec 2023"
echo Date::format($date, 'iso'); // ISO 8601 format
echo Date::format($date, 'Y-m-d H:i:s', 'America/New_York'); // With timezone
```

#### `timestamp(\DateTimeInterface|int|string|null $value = null, ?string $timezone = null): int`
Get a UNIX timestamp from various input types.

```php
echo Date::timestamp(); // Current timestamp
echo Date::timestamp('2023-12-25'); // Timestamp for date
echo Date::timestamp('2023-12-25 15:30', 'Europe/London'); // With timezone
```

---

## Files

The Files class provides comprehensive file and directory operations.

#### `read(string $path): string|false`
Read file contents.

```php
use PhpHelper\Files;

$content = Files::read('/path/to/file.txt');
if ($content !== false) {
    echo $content;
}
```

#### `write(string $path, string $content, int $flags = 0): int|false`
Write contents to file (creates directories if needed).

```php
$bytes = Files::write('/path/to/file.txt', 'Hello World');
$bytes = Files::write('/path/to/file.txt', 'Append this', FILE_APPEND);
```

#### `append(string $path, string $content): int|false`
Append contents to file.

```php
Files::append('/path/to/log.txt', "New log entry\n");
```

#### `delete(string $path): bool`
Delete file.

```php
if (Files::delete('/path/to/old-file.txt')) {
    echo "File deleted successfully";
}
```

#### `copy(string $source, string $dest): bool`
Copy file (creates destination directories if needed).

```php
Files::copy('/source/file.txt', '/backup/file.txt');
```

#### `move(string $source, string $dest): bool`
Move/rename file.

```php
Files::move('/old/path/file.txt', '/new/path/file.txt');
```

#### `exists(string $path): bool`
Check if file exists.

```php
if (Files::exists('/path/to/file.txt')) {
    echo "File exists";
}
```

#### `size(string $path): int|false`
Get file size in bytes.

```php
$bytes = Files::size('/path/to/file.txt');
echo "File size: $bytes bytes";
```

#### `extension(string $path): string`
Get file extension.

```php
echo Files::extension('/path/to/document.pdf'); // "pdf"
```

#### `mimeType(string $path): string|false`
Get file MIME type.

```php
echo Files::mimeType('/path/to/image.jpg'); // "image/jpeg"
```

#### `modifiedTime(string $path): int|false`
Get file modification time.

```php
$timestamp = Files::modifiedTime('/path/to/file.txt');
echo date('Y-m-d H:i:s', $timestamp);
```

#### `createDirectory(string $path, int $permissions = 0755): bool`
Create directory recursively.

```php
Files::createDirectory('/path/to/new/directory');
```

#### `deleteDirectory(string $path): bool`
Delete directory recursively.

```php
Files::deleteDirectory('/path/to/directory');
```

#### `listFiles(string $path, string $pattern = '*'): array`
List files in directory.

```php
$files = Files::listFiles('/path/to/directory');
$phpFiles = Files::listFiles('/src', '*.php');
```

#### `listDirectories(string $path): array`
List directories.

```php
$dirs = Files::listDirectories('/path/to/parent');
```

#### `readJson(string $path): mixed`
Read JSON file.

```php
$data = Files::readJson('/path/to/data.json');
```

#### `writeJson(string $path, mixed $data, int $flags = JSON_PRETTY_PRINT): int|false`
Write JSON file.

```php
$data = ['name' => 'John', 'age' => 30];
Files::writeJson('/path/to/data.json', $data);
```

#### `readCsv(string $path, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): array|false`
Read CSV file.

```php
$rows = Files::readCsv('/path/to/data.csv');
```

#### `writeCsv(string $path, array $data, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): bool`
Write CSV file.

```php
$data = [
    ['Name', 'Age', 'City'],
    ['John', 30, 'New York'],
    ['Jane', 25, 'London']
];
Files::writeCsv('/path/to/data.csv', $data);
```

#### `hash(string $path, string $algo = 'sha256'): string|false`
Get file hash.

```php
$hash = Files::hash('/path/to/file.txt');
$md5 = Files::hash('/path/to/file.txt', 'md5');
```

#### `isAbsolute(string $path): bool`
Check if path is absolute.

```php
echo Files::isAbsolute('/absolute/path'); // true
echo Files::isAbsolute('relative/path'); // false
```

#### `normalizePath(string $path): string`
Normalize path separators.

```php
echo Files::normalizePath('path\\to\\file'); // Uses correct separator for OS
```

#### `joinPaths(string ...$parts): string`
Join path parts.

```php
$path = Files::joinPaths('/base', 'subdir', 'file.txt');
// Result: "/base/subdir/file.txt" (with correct separators)
```

---

## Format

The Format class provides utilities for formatting numbers, bytes, currency, and other data types.

#### `bytes(int $bytes, int $precision = 2, string $system = 'binary', ?array $units = null): string`
Format bytes into human readable string.

```php
use PhpHelper\Format;

echo Format::bytes(1536); // "1.50 KB"
echo Format::bytes(1048576, 1, 'binary'); // "1.0 MB"
echo Format::bytes(1000000, 2, 'si'); // "1.00 MB" (1000-based)
echo Format::bytes(1024, 0, 'iec'); // "1 KiB"
```

#### `number(float|int $value, int $decimals = 0, string $decimalPoint = '.', string $thousandsSep = ','): string`
Standard number formatting.

```php
echo Format::number(1234567.89, 2); // "1,234,567.89"
echo Format::number(1234567.89, 0); // "1,234,568"
```

#### `currency(float $amount, string $currency = 'USD', ?string $locale = null, ?int $precision = null): string`
Format currency amount.

```php
echo Format::currency(1234.56); // "$1,234.56" (if intl available)
echo Format::currency(1234.56, 'EUR', 'de_DE'); // "1.234,56 €"
echo Format::currency(1234.56, 'USD', null, 0); // "$1,235"
```

#### `percent(float $value, int $precision = 0, bool $fromFraction = true): string`
Format percentage.

```php
echo Format::percent(0.1234); // "12%" (from fraction)
echo Format::percent(12.34, 1, false); // "12.3%" (from percentage)
```

#### `shortNumber(float|int $value, int $precision = 1): string`
Humanized abbreviations.

```php
echo Format::shortNumber(1200); // "1.2K"
echo Format::shortNumber(1500000); // "1.5M"
echo Format::shortNumber(2800000000); // "2.8B"
```

#### `duration(int $seconds, bool $compact = true): string`
Human time spans.

```php
echo Format::duration(3661); // "1h 1m 1s"
echo Format::duration(3661, false); // "1 hour, 1 minute, 1 second"
```

#### `hms(int $seconds, bool $withDays = false): string`
Clock format HH:MM:SS.

```php
echo Format::hms(3661); // "01:01:01"
echo Format::hms(90061, true); // "1d 01:01:01"
```

#### `ordinal(int $number): string`
English ordinal suffix.

```php
echo Format::ordinal(1); // "1st"
echo Format::ordinal(22); // "22nd"
echo Format::ordinal(103); // "103rd"
```

#### `parseBytes(string $size): int`
Parse human-readable size into bytes.

```php
echo Format::parseBytes('2M'); // 2097152
echo Format::parseBytes('1.5 GB'); // 1610612736
echo Format::parseBytes('2MiB'); // 2097152
```

#### `bool(mixed $value, string $true = 'Yes', string $false = 'No', string $null = ''): string`
Consistent boolean labels.

```php
echo Format::bool(true); // "Yes"
echo Format::bool(0); // "No"
echo Format::bool('1', 'Active', 'Inactive'); // "Active"
```

#### `json(mixed $value, bool $pretty = true, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES): string`
JSON encode for display.

```php
$data = ['name' => 'John', 'age' => 30];
echo Format::json($data); // Pretty formatted JSON
echo Format::json($data, false); // Compact JSON
```

---

## Http

The Http class provides utilities for HTTP operations, redirects, downloads, and client information.

#### `redirect(string $url, int $statusCode = 302, bool $exit = true): void`
Redirect to a URL.

```php
use PhpHelper\Http;

Http::redirect('/dashboard');
Http::redirect('https://example.com', 301);
Http::redirect('/login', 302, false); // Don't exit after redirect
```

#### `download(string $filename, string $mimetype = 'application/octet-stream')`
Stream a file as download.

```php
Http::download('/path/to/file.pdf', 'application/pdf');
Http::download('/path/to/image.jpg', 'image/jpeg');
```

#### `json(mixed $data, int $status = 200, array $headers = []): void`
Send JSON response.

```php
$data = ['success' => true, 'message' => 'Data saved'];
Http::json($data);
Http::json(['error' => 'Not found'], 404);
Http::json($data, 200, ['X-Custom-Header' => 'value']);
```

#### `clientInfo(): array`
Collect comprehensive client information.

```php
$info = Http::clientInfo();

echo $info['ip']; // Client IP
echo $info['browser']; // Browser name
echo $info['os']; // Operating system
echo $info['device']; // Device type (mobile/tablet/desktop/bot)
echo $info['user_agent']; // Full user agent string

// Check device types
if ($info['is_mobile']) {
    echo "Mobile device detected";
}

// Language preferences
$languages = $info['languages']; // Array of preferred languages

// Request information
echo $info['method']; // HTTP method
echo $info['url']; // Full URL
echo $info['referer']; // Referring URL
```

---

## Logs

The Logs class provides structured database logging with multiple severity levels.

#### `init(array $config = []): void`
Initialize the logger with configuration.

```php
use PhpHelper\Logs;

Logs::init([
    'table' => 'application_logs',
    'context_defaults' => ['app' => 'myapp'],
    'meta_defaults' => ['version' => '1.0']
]);
```

#### `log(string $level, string $message, array $context = [], array $meta = []): string`
Log a message with custom level.

```php
$id = Logs::log('info', 'User logged in', ['user_id' => 123], ['ip' => '192.168.1.1']);
```

#### Convenience Methods
All standard log levels are available as methods:

```php
Logs::debug('Debug information', ['query' => $sql]);
Logs::info('Information message', ['user_id' => 123]);
Logs::success('Operation completed successfully');
Logs::notice('Notice: Cache cleared');
Logs::warning('Warning: Disk space low', ['available' => '10GB']);
Logs::error('Database connection failed', ['host' => 'db.server']);
Logs::critical('Critical system error', ['service' => 'payment']);
Logs::alert('Alert: Security breach detected');
Logs::emergency('Emergency: System shutdown required');
```

#### `createLogsTable(?string $table = null): void`
Create the logs table (MySQL and SQLite supported).

```php
Logs::createLogsTable(); // Uses default table name
Logs::createLogsTable('custom_logs'); // Custom table name
```

#### Configuration Methods

```php
Logs::setTable('custom_logs');
Logs::setContextDefaults(['app' => 'myapp', 'env' => 'production']);
Logs::setMetaDefaults(['server' => $_SERVER['SERVER_NAME']]);
Logs::clearDefaults();
```

---

## PrettyErrorHandler

The PrettyErrorHandler class provides beautiful error pages for development with code context and stack traces.

#### `__construct(array $options = [], bool $registerGlobal = true)`
Create and optionally register error handler.

```php
use PhpHelper\PrettyErrorHandler;

// Simple initialization
$handler = new PrettyErrorHandler();

// With custom options
$handler = new PrettyErrorHandler([
    'display' => true,
    'show_trace' => true,
    'overlay' => true,
    'context_before' => 8,
    'context_after' => 6,
    'skip_warnings' => false,
    'log_errors' => true
], false); // Don't auto-register
$handler->register(); // Register manually
```

#### `init(array $options = []): self`
Static convenience method.

```php
PrettyErrorHandler::init([
    'overlay' => false, // Full page instead of overlay
    'show_trace' => false, // Hide stack trace
    'log_errors' => true // Log to pretty_errors.txt
]);
```

#### Available Options:
- `display` (bool): Force display errors (default: true)
- `report` (int): Error reporting level (default: E_ALL)
- `context_before` (int): Lines before error (default: 6)
- `context_after` (int): Lines after error (default: 4)
- `show_trace` (bool): Include stack trace (default: true)
- `overlay` (bool): Render as overlay vs full page (default: true)
- `skip_warnings` (bool): Skip PHP warnings (default: false)
- `log_errors` (bool): Log to pretty_errors.txt (default: false)

---

## Additional Str Methods

The Str class has additional utility methods not previously documented:

#### `seoFileName(string $text): string`
Create SEO-friendly filename (keeps dots).

```php
use PhpHelper\Str;

echo Str::seoFileName('My Document File.pdf'); // "my-document-file.pdf"
echo Str::seoFileName('Αρχείο με Ελληνικά.doc'); // "archeio-me-ellinika.doc"
```

#### `seoUrl(string $text): string`
Create SEO-friendly URL (removes dots).

```php
echo Str::seoUrl('My Article Title!'); // "my-article-title"
echo Str::seoUrl('Product v2.0 Launch'); // "product-v2-0-launch"
```

#### Debug Helper Methods

#### `prettyLog(mixed $v): void`
Pretty print variable with HTML formatting.

```php
Str::prettyLog($array); // Outputs formatted array in <pre> tags
```

#### `prettyLogExit(mixed $v): void`
Pretty print and exit with black background.

```php
Str::prettyLogExit($debugData); // Debug and stop execution
```

#### `print_functions(object $obj): void`
Display all methods of an object.

```php
Str::print_functions($myObject); // Shows all available methods
```

---

## Example index.php

```php
<?php

require_once 'vendor/autoload.php';

use PhpHelper\{DB, App, AuthManager, Config, Logs, PrettyErrorHandler, TwigHelper, Http};
use Bramus\Router\Router;



// ---- PRODUCTION ----
if (App::isProduction()) {
    PrettyErrorHandler::init(['display' => false, 'log_errors' => true]);
    DB::mysql('phphelper', 'root', '1');

} 
// ---- LOCAL ----
else {
    if (App::isCli()) {
        App::cliMenu(DB::cliBackupRestoreOptions('phphelper', 'root', '1'));
    }
    PrettyErrorHandler::init(['display' => true, 'log_errors' => false]);
    DB::mysql('phphelper', 'root', '1');
}   

// Initialize authentication system
AuthManager::init();
AuthManager::createUsersTable();

// Initialize configuration system
Config::init();
Config::createConfigTable();

// Initialize logging system
Logs::init();
Logs::createLogsTable();

TwigHelper::init('templates');

// ------ Initialize routers --------
$router = new Router();

$router->before('GET|POST|PUT|DELETE', '/admin(/.*)?', fn() => AuthManager::requireAuth(fn() => Http::redirect('/login',302,true)));

// Set custom 404 handler
$router->set404(fn() => throw new \Exception("The requested page could not be found 404: " .
    ($_SERVER['REQUEST_URI'] ?? 'unknown'), 404));

// include 'routes.php';
$router->get('/', function() {
    echo "Homepage";    
});


$router->run();


 
 
```
