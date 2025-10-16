# PHP Helper Library

A comprehensive PHP helper library providing ready-to-use utilities for everyday application tasks.

## Installation

```bash
composer require mzgs/phphelper:dev-main
```

## Requirements

- PHP 8.3 or higher

## Contents

- [AIChat](#aichat)
- [App](#app)
- [Arrays](#arrays)
- [AuthManager](#authmanager)
- [Config](#config)
- [DB](#db)
- [Date](#date)
- [Files](#files)
- [Format](#format)
- [Http](#http)
- [Logs](#logs)
- [PrettyErrorHandler](#prettyerrorhandler)
- [Str](#str)
- [TwigHelper](#twighelper)

## AIChat

Interact with OpenAI-compatible chat completion endpoints using a minimal fluent wrapper.

### AIChat::init(array $config): void
Configure the HTTP client and default payload.

**Options**
- `base_uri` (string) Base URL for the API, defaults to `https://api.openai.com/v1/`.
- `endpoint` (string) Relative endpoint, defaults to `chat/completions`.
- `model` (string) Default model name, defaults to `gpt-4o-mini`.
- `api_key` (?string) Bearer token used to authorise requests.
- `timeout` (?float) Request timeout in seconds (e.g. `15.0`).
- `headers` (array<string, string>) Extra headers merged into every request.
- `response_format` (string|array|null) Default response format sent to the API.
- `client_options` (array<string, mixed>) Extra options forwarded to the underlying `GuzzleHttp\Client` constructor.

```php
use PhpHelper\AIChat;

AIChat::init([
    'api_key' => getenv('OPENAI_API_KEY'),
    'model' => 'gpt-4o-mini',
    'timeout' => 30,
    'headers' => ['X-Request-Id' => 'phphelper-demo'],
]);
```

### AIChat::setApiKey(?string $apiKey): void
Override the stored API key without touching other configuration.

```php
use PhpHelper\AIChat;

AIChat::setApiKey('sk-test-123');
```

### AIChat::setClient(?GuzzleHttp\Client $client): void
Inject a pre-configured Guzzle client. Pass `null` to rebuild it from the stored config.

```php
use GuzzleHttp\Client;
use PhpHelper\AIChat;

AIChat::setClient(new Client(['base_uri' => 'https://api.example.test/v1/']));
```

### AIChat::reset(): void
Restore the default configuration and dispose of the cached HTTP client.

```php
use PhpHelper\AIChat;

AIChat::reset();
```

### AIChat::chat(array $messages, array $payload = []): array
Execute a raw chat completion request.

```php
use PhpHelper\AIChat;

$messages = [
    ['role' => 'system', 'content' => 'You are a concise assistant.'],
    ['role' => 'user', 'content' => 'Explain closures in PHP.'],
];

$response = AIChat::chat($messages, ['temperature' => 0.4]);
$assistantReply = $response['choices'][0]['message']['content'] ?? '';
```

### AIChat::reply(string $prompt, array $payload = [], array $contextMessages = []): string
Send a convenience request using a plain user prompt and optional preceding context messages.

```php
use PhpHelper\AIChat;

$answer = AIChat::reply('Summarise PSR-4 autoloading in two sentences.');
```

## App

Lightweight environment helpers.

### App::isLocal(): bool
Detect whether the current request targets a localhost host or IP.

```php
use PhpHelper\App;

if (App::isLocal()) {
    error_log('Developer features enabled.');
}
```

### App::isCli(): bool
Detect whether the code executes under CLI or PHPDBG.

```php
use PhpHelper\App;

if (App::isCli()) {
    echo "Running from the terminal" . PHP_EOL;
}
```

### App::isProduction(): bool
True when neither `isLocal()` nor `isCli()` is true.

```php
use PhpHelper\App;

if (App::isProduction()) {
    ini_set('display_errors', '0');
}
```

## Arrays

Utility helpers for nested arrays and collection-style operations.

### Arrays::get(array $array, string $key, mixed $default = null): mixed
Fetch a value using dot notation with a default fallback.

```php
use PhpHelper\Arrays;

$name = Arrays::get(['user' => ['name' => 'Ada']], 'user.name', 'Guest');
```

### Arrays::set(array &$array, string $key, mixed $value): void
Set a value using dot notation, creating intermediate arrays as needed.

```php
use PhpHelper\Arrays;

$config = [];
Arrays::set($config, 'database.host', 'localhost');
```

### Arrays::has(array $array, string $key): bool
Check for the existence of a dot-notated key.

```php
use PhpHelper\Arrays;

$hasEmail = Arrays::has(['user' => ['email' => 'ada@example.com']], 'user.email');
```

### Arrays::forget(array &$array, string $key): void
Remove a value using dot notation.

```php
use PhpHelper\Arrays;

$data = ['user' => ['password' => 'secret']];
Arrays::forget($data, 'user.password');
```

### Arrays::flatten(array $array, int $depth = INF): array
Flatten nested arrays to a single list up to the provided depth.

```php
use PhpHelper\Arrays;

$flat = Arrays::flatten([1, [2, [3, 4]]], 2); // [1, 2, 3, 4]
```

### Arrays::dot(array $array, string $prepend = ''): array
Return a dot-notated representation of a nested array.

```php
use PhpHelper\Arrays;

$dot = Arrays::dot(['settings' => ['theme' => 'dark']]);
```

### Arrays::only(array $array, array $keys): array
Return only the specified keys.

```php
use PhpHelper\Arrays;

$subset = Arrays::only(['id' => 5, 'name' => 'Ada', 'role' => 'admin'], ['id', 'name']);
```

### Arrays::except(array $array, array $keys): array
Return everything except the specified keys.

```php
use PhpHelper\Arrays;

$filtered = Arrays::except(['id' => 5, 'name' => 'Ada', 'role' => 'admin'], ['role']);
```

### Arrays::first(array $array, ?callable $callback = null, mixed $default = null): mixed
Return the first item or first item matching a callback.

```php
use PhpHelper\Arrays;

$firstAdmin = Arrays::first($users, fn ($user) => $user['role'] === 'admin');
```

### Arrays::last(array $array, ?callable $callback = null, mixed $default = null): mixed
Return the last item or last matching a callback.

```php
use PhpHelper\Arrays;

$latest = Arrays::last($events, fn ($event) => $event['status'] === 'published');
```

### Arrays::where(array $array, callable $callback): array
Filter using a callback receiving value and key.

```php
use PhpHelper\Arrays;

$active = Arrays::where($users, fn ($user) => $user['active']);
```

### Arrays::whereEquals(array $array, string $key, mixed $value): array
Filter entries whose nested value equals the given value.

```php
use PhpHelper\Arrays;

$admins = Arrays::whereEquals($users, 'role', 'admin');
```

### Arrays::whereIn(array $array, string $key, array $values): array
Filter entries whose nested value is in the provided list.

```php
use PhpHelper\Arrays;

$specific = Arrays::whereIn($users, 'id', [1, 2, 3]);
```

### Arrays::whereNotIn(array $array, string $key, array $values): array
Filter entries whose nested value is not in the provided list.

```php
use PhpHelper\Arrays;

$others = Arrays::whereNotIn($users, 'id', [1, 2, 3]);
```

### Arrays::pluck(array $array, string $value, ?string $key = null): array
Extract values (and optional keys) from an array of arrays.

```php
use PhpHelper\Arrays;

$usernames = Arrays::pluck($users, 'profile.username');
```

### Arrays::keyBy(array $array, string|callable $key): array
Index the collection by a key or computed callback.

```php
use PhpHelper\Arrays;

$usersByEmail = Arrays::keyBy($users, 'email');
```

### Arrays::groupBy(array $array, string|callable $key): array
Group values by the given key or callback.

```php
use PhpHelper\Arrays;

$grouped = Arrays::groupBy($orders, fn ($order) => $order['status']);
```

### Arrays::sortBy(array $array, string|callable $key, bool $descending = false): array
Return a new array sorted by a key or callback.

```php
use PhpHelper\Arrays;

$sorted = Arrays::sortBy($users, 'profile.last_login', true);
```

### Arrays::isAssoc(array $array): bool
Check whether the array has non-sequential keys.

```php
use PhpHelper\Arrays;

$isAssoc = Arrays::isAssoc(['id' => 1, 'name' => 'Ada']);
```

### Arrays::isSequential(array $array): bool
Check whether the array uses sequential numeric keys.

```php
use PhpHelper\Arrays;

$isSequential = Arrays::isSequential(['first', 'second']);
```

### Arrays::random(array $array, int $number = 1): mixed
Pick one or many random elements.

```php
use PhpHelper\Arrays;

$randomUser = Arrays::random($users);
$twoUsers = Arrays::random($users, 2);
```

### Arrays::shuffle(array $array): array
Shuffle the array while preserving keys.

```php
use PhpHelper\Arrays;

$shuffled = Arrays::shuffle(['a' => 1, 'b' => 2, 'c' => 3]);
```

### Arrays::chunk(array $array, int $size, bool $preserveKeys = false): array
Split the array into equally sized chunks.

```php
use PhpHelper\Arrays;

$chunks = Arrays::chunk(range(1, 6), 2);
```

### Arrays::collapse(array $array): array
Merge an array of arrays into one flat array.

```php
use PhpHelper\Arrays;

$collapsed = Arrays::collapse([[1, 2], [3, 4]]);
```

### Arrays::crossJoin(array ...$arrays): array
Generate the Cartesian product of multiple arrays.

```php
use PhpHelper\Arrays;

$product = Arrays::crossJoin(['S', 'M'], ['Red', 'Blue']);
```

### Arrays::divide(array $array): array
Return `[keys, values]` arrays.

```php
use PhpHelper\Arrays;

[$keys, $values] = Arrays::divide(['id' => 1, 'name' => 'Ada']);
```

### Arrays::replaceRecursive(array $array, array ...$replacements): array
Recursively merge arrays, overriding existing keys.

```php
use PhpHelper\Arrays;

$merged = Arrays::replaceRecursive(['db' => ['host' => 'localhost']], ['db' => ['port' => 3306]]);
```

### Arrays::duplicates(array $array): array
Return values that appear more than once along with their counts.

```php
use PhpHelper\Arrays;

$dupes = Arrays::duplicates(['a', 'b', 'a']);
```

### Arrays::map(array $array, callable $callback): array
Apply a callback while preserving keys.

```php
use PhpHelper\Arrays;

$upper = Arrays::map(['a' => 'foo'], fn ($value) => strtoupper($value));
```

### Arrays::mapRecursive(array $array, callable $callback): array
Recursively map every scalar value.

```php
use PhpHelper\Arrays;

$trimmed = Arrays::mapRecursive(['name' => ' Ada '], fn ($value) => is_string($value) ? trim($value) : $value);
```

### Arrays::wrap(mixed $value): array
Wrap a value in an array unless it is already an array.

```php
use PhpHelper\Arrays;

$wrapped = Arrays::wrap('example');
```

### Arrays::toQuery(array $array): string
Build a RFC 3986 encoded query string.

```php
use PhpHelper\Arrays;

$queryString = Arrays::toQuery(['page' => 2, 'filter' => 'active']);
```

### Arrays::fromQuery(string $query): array
Parse a query string into an array.

```php
use PhpHelper\Arrays;

$params = Arrays::fromQuery('page=2&filter=active');
```

## AuthManager

Simple authentication helper built on top of the `DB` class and PHP sessions.

### AuthManager::init(array $config = []): void
Initialise the authentication subsystem. Requires an active database connection.

**Options**
- `table` (string) Users table name (default `users`).
- `email_column` (string) Email/username column (default `email`).
- `password_column` (string) Password hash column (default `password`).
- `primary_key` (string) Primary key column (default `id`).
- `sessions` (bool) Whether to persist to PHP sessions.
- `session_key` (string) Session key used to store the user payload (default `_auth_user`).
- `remember_me` (bool) Enable remember-me cookies (default `true`).
- `remember_cookie` (string) Cookie name (default `phphelper_remember`).
- `remember_duration` (int) Cookie lifetime in seconds (default 360 days).
- `remember_secret` (string) HMAC secret for remember-me tokens (auto-generated when omitted).
- `remember_options` (array) Cookie options (`path`, `domain`, `secure`, `httponly`, `samesite`).

```php
use PhpHelper\AuthManager;
use PhpHelper\DB;

DB::sqlite(':memory:');
AuthManager::init(['sessions' => true]);
```

### AuthManager::createUsersTable(array $options = []): void
Create a users table with sensible defaults based on the current PDO driver.

**Options**
- `table` (string) Override the table name before creation.
- `email_column` (string) Customise the email column name.
- `password_column` (string) Customise the password hash column name.
- `primary_key` (string) Override the primary key column name.
- `extra_columns` (array<string,string>) Extra column definitions appended to the table.

```php
use PhpHelper\AuthManager;

AuthManager::createUsersTable(['extra_columns' => ['name' => 'TEXT']]);
```

### AuthManager::login(string $email, string $password, bool $remember = false): ?array
Attempt to authenticate a user by email and password.

```php
use PhpHelper\AuthManager;

$user = AuthManager::login('ada@example.com', 'secret', true);
```

### AuthManager::register(string $email, string $password, array $attributes = []): array
Create a new user record and automatically log them in.

```php
use PhpHelper\AuthManager;

$created = AuthManager::register('ada@example.com', 'secret', ['name' => 'Ada']);
```

### AuthManager::user(): ?array
Return the sanitised authenticated user payload or `null`.

```php
use PhpHelper\AuthManager;

$currentUser = AuthManager::user();
```

### AuthManager::isLoggedIn(): bool
Quick boolean check for an authenticated user.

```php
use PhpHelper\AuthManager;

if (!AuthManager::isLoggedIn()) {
    // redirect to login
}
```

## Config

Key/value configuration storage backed by the database.

### Config::init(array $config = []): void
Initialise the helper (requires an existing database connection).

**Options**
- `table` (string) Table name, default `config`.
- `key_column` (string) Key column name, default `config_key`.
- `value_column` (string) Value column name, default `config_value`.

```php
use PhpHelper\Config;
use PhpHelper\DB;

DB::sqlite(':memory:');
Config::init();
```

### Config::createConfigTable(array $options = []): void
Create the configuration table using the current settings.

**Options**
- `table` (string) Override table name during creation.
- `key_column` (string) Override key column name.
- `value_column` (string) Override value column name.

```php
use PhpHelper\Config;

Config::createConfigTable();
```

### Config::set(string $key, ?string $value): void
Store or update a value.

```php
use PhpHelper\Config;

Config::set('mail.driver', 'smtp');
```

### Config::get(string $key, ?string $default = null): ?string
Retrieve a value or return the default when missing.

```php
use PhpHelper\Config;

$driver = Config::get('mail.driver', 'log');
```

### Config::has(string $key): bool
Determine whether a key exists.

```php
use PhpHelper\Config;

if (!Config::has('feature.chat')) {
    Config::set('feature.chat', 'off');
}
```

### Config::delete(string $key): bool
Remove a key. Returns whether a row was deleted.

```php
use PhpHelper\Config;

Config::delete('temporary.flag');
```

### Config::all(): array
Return all configuration pairs ordered by key.

```php
use PhpHelper\Config;

$settings = Config::all();
```

## DB

Thin wrapper around PDO that standardises common database operations.

### DB::connect(string $dsn, ?string $username = null, ?string $password = null, array $options = []): void
Establish a PDO connection with sensible defaults (exceptions, associative fetch mode, native prepares).

```php
use PhpHelper\DB;

DB::connect('mysql:host=127.0.0.1;dbname=app', 'root', 'secret');
```

### DB::mysql(string $dbname, ?string $username = null, ?string $password = null, array $options = []): void
Convenience connector for MySQL / MariaDB.

**Options**
- `host` (string) Hostname, default `127.0.0.1`.
- `port` (int|string|null) TCP port.
- `charset` (?string) Character set, default `utf8mb4`.
- `unix_socket` (?string) Path to a Unix socket.
- `attributes` (array) Extra PDO attributes.

```php
use PhpHelper\DB;

DB::mysql('app', 'appuser', 'secret', ['host' => 'db', 'charset' => 'utf8mb4']);
```

### DB::pgsql(string $dbname, ?string $username = null, ?string $password = null, array $options = []): void
Convenience connector for PostgreSQL.

**Options**
- `host` (string) Hostname, default `127.0.0.1`.
- `port` (int|string|null) Port number.
- `sslmode` (?string) SSL mode string.
- `charset` (?string) Client encoding (applied via DSN options).
- `application_name` (?string) Application name.
- `attributes` (array) Extra PDO attributes.

```php
use PhpHelper\DB;

DB::pgsql('app', 'appuser', 'secret', ['sslmode' => 'require']);
```

### DB::sqlite(string $pathOrDsn = ':memory:', ?string $username = null, ?string $password = null, array $options = []): void
Convenience connector for SQLite files or in-memory databases.

**Options**
- `memory` (bool) Force `:memory:` usage.
- `attributes` (array) Extra PDO attributes.

```php
use PhpHelper\DB;

DB::sqlite(__DIR__ . '/var/app.sqlite');
```

### DB::connected(): bool
Check whether a PDO instance has been initialised.

```php
use PhpHelper\DB;

if (!DB::connected()) {
    DB::sqlite(':memory:');
}
```

### DB::pdo(): PDO
Return the underlying PDO instance (throws when not connected).

```php
use PhpHelper\DB;

$pdo = DB::pdo();
```

### DB::disconnect(): void
Drop the stored PDO connection.

```php
use PhpHelper\DB;

DB::disconnect();
```

### DB::query(string $sql, array $params = []): PDOStatement
Prepare and execute a statement, returning the `PDOStatement`.

```php
use PhpHelper\DB;

$stmt = DB::query('SELECT * FROM users WHERE id = :id', ['id' => 1]);
```

### DB::execute(string $sql, array $params = []): int
Execute a statement and return the affected row count.

```php
use PhpHelper\DB;

$deleted = DB::execute('DELETE FROM sessions WHERE expires_at < :now', ['now' => time()]);
```

### DB::getRow(string $sql, array $params = []): ?array
Fetch the first row as an associative array or `null`.

```php
use PhpHelper\DB;

$user = DB::getRow('SELECT * FROM users WHERE email = :email', ['email' => 'ada@example.com']);
```

### DB::getRows(string $sql, array $params = []): array
Fetch all rows as an array of associative arrays.

```php
use PhpHelper\DB;

$users = DB::getRows('SELECT * FROM users ORDER BY created_at DESC');
```

### DB::getValue(string $sql, array $params = []): mixed
Fetch the first column of the first row.

```php
use PhpHelper\DB;

$count = DB::getValue('SELECT COUNT(*) FROM users WHERE active = 1');
```

### DB::count(string $table, string $where = '', array $params = []): int
Count rows in a table with an optional WHERE clause.

```php
use PhpHelper\DB;

$activeUsers = DB::count('users', 'active = :active', ['active' => 1]);
```

### DB::insert(string $table, array $data): string
Insert a row and return the last insert id (as a string per PDO contract).

```php
use PhpHelper\DB;

$id = DB::insert('users', ['email' => 'ada@example.com', 'password' => 'hash']);
```

### DB::upsert(string $table, array $data, array|string $conflictColumns, ?array $updateColumns = null): void
Insert or update rows using native driver capabilities, falling back to a transactional emulation.

```php
use PhpHelper\DB;

DB::upsert('settings', ['key' => 'theme', 'value' => 'dark'], 'key');
```

### DB::update(string $table, array $data, string $where, array $params = []): int
Update rows matching the WHERE clause; returns the number of affected rows.

```php
use PhpHelper\DB;

$updated = DB::update('users', ['last_login' => time()], 'id = :id', ['id' => 5]);
```

### DB::delete(string $table, string $where, array $params = []): int
Delete rows matching the WHERE clause; returns the number of affected rows.

```php
use PhpHelper\DB;

$deleted = DB::delete('users', 'inactive = 1');
```

### DB::beginTransaction(): void
Begin a transaction on the current PDO connection.

```php
use PhpHelper\DB;

DB::beginTransaction();
```

### DB::commit(): void
Commit the current transaction.

```php
use PhpHelper\DB;

DB::commit();
```

### DB::rollBack(): void
Roll back the current transaction.

```php
use PhpHelper\DB;

DB::rollBack();
```

### DB::transaction(callable $callback): mixed
Execute a callback within a transaction, committing on success and rolling back on exception.

```php
use PhpHelper\DB;

$result = DB::transaction(function () {
    DB::insert('logs', ['message' => 'Example']);
    return true;
});
```

### DB::quoteIdentifier(string $identifier): string
Quote identifiers (table/column names) for the active driver.

```php
use PhpHelper\DB;

$column = DB::quoteIdentifier('users.email');
```

## Date

Date/time convenience helpers.

### Date::ago(DateTimeInterface|int|string $timestamp, bool $full = false): string
Generate a human-readable relative time string.

```php
use PhpHelper\Date;

echo Date::ago('-2 hours'); // "2 hours ago"
```

### Date::format(DateTimeInterface|int|string $timestamp, string $format = 'datetime', ?string $timezone = null): string
Apply preset or custom formatting with optional timezone conversion.

```php
use PhpHelper\Date;

$iso = Date::format('2024-01-01 12:00', 'iso', 'Europe/London');
```

### Date::timestamp(DateTimeInterface|int|string|null $value = null, ?string $timezone = null): int
Normalise a DateTime, timestamp, or parseable string into a Unix timestamp.

```php
use PhpHelper\Date;

$timestamp = Date::timestamp('next monday', 'UTC');
```

## Files

Filesystem helpers for common read/write tasks.

### Files::read(string $path): string|false
Read a file, returning `false` when missing.

```php
use PhpHelper\Files;

$contents = Files::read(__DIR__ . '/storage/app.log');
```

### Files::write(string $path, string $content, int $flags = 0): int|false
Write content to disk (creating directories automatically).

```php
use PhpHelper\Files;

Files::write(__DIR__ . '/storage/app.log', "starting application\n");
```

### Files::append(string $path, string $content): int|false
Append content to a file.

```php
use PhpHelper\Files;

Files::append(__DIR__ . '/storage/app.log', "tick\n");
```

### Files::delete(string $path): bool
Delete a file if it exists.

```php
use PhpHelper\Files;

Files::delete(__DIR__ . '/storage/temp.txt');
```

### Files::copy(string $source, string $dest): bool
Copy a file (creating destination directories when required).

```php
use PhpHelper\Files;

Files::copy(__DIR__ . '/storage/app.log', __DIR__ . '/backup/app.log');
```

### Files::move(string $source, string $dest): bool
Move/rename a file.

```php
use PhpHelper\Files;

Files::move(__DIR__ . '/storage/temp.txt', __DIR__ . '/storage/archive/temp.txt');
```

### Files::exists(string $path): bool
Determine whether a regular file exists.

```php
use PhpHelper\Files;

if (Files::exists(__DIR__ . '/storage/app.log')) {
    // rotate log
}
```

### Files::size(string $path): int|false
Retrieve file size in bytes.

```php
use PhpHelper\Files;

$bytes = Files::size(__DIR__ . '/storage/app.log');
```

### Files::extension(string $path): string
Return the lowercase file extension.

```php
use PhpHelper\Files;

$ext = Files::extension('photo.JPG'); // "jpg"
```

### Files::mimeType(string $path): string|false
Detect the MIME type when the file exists.

```php
use PhpHelper\Files;

$mime = Files::mimeType(__DIR__ . '/storage/app.log');
```

### Files::modifiedTime(string $path): int|false
Retrieve the last modified time as a Unix timestamp.

```php
use PhpHelper\Files;

$mtime = Files::modifiedTime(__DIR__ . '/storage/app.log');
```

### Files::createDirectory(string $path, int $permissions = 0755): bool
Create a directory recursively.

```php
use PhpHelper\Files;

Files::createDirectory(__DIR__ . '/storage/cache');
```

### Files::deleteDirectory(string $path): bool
Delete a directory recursively.

```php
use PhpHelper\Files;

Files::deleteDirectory(__DIR__ . '/storage/cache');
```

### Files::listFiles(string $path, string $pattern = '*'): array
List files matching a glob pattern.

```php
use PhpHelper\Files;

$logs = Files::listFiles(__DIR__ . '/storage', '*.log');
```

### Files::listDirectories(string $path): array
List immediate sub-directories.

```php
use PhpHelper\Files;

$directories = Files::listDirectories(__DIR__ . '/storage');
```

### Files::readJson(string $path): mixed
Decode a JSON file, returning `false` when the file is missing.

```php
use PhpHelper\Files;

$config = Files::readJson(__DIR__ . '/storage/config.json');
```

### Files::writeJson(string $path, mixed $data, int $flags = JSON_PRETTY_PRINT): int|false
Encode data as JSON and write it to disk.

```php
use PhpHelper\Files;

Files::writeJson(__DIR__ . '/storage/config.json', ['debug' => true]);
```

### Files::readCsv(string $path, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): array|false
Read all rows from a CSV file.

```php
use PhpHelper\Files;

$rows = Files::readCsv(__DIR__ . '/storage/users.csv');
```

### Files::writeCsv(string $path, array $data, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): bool
Write rows to a CSV file.

```php
use PhpHelper\Files;

Files::writeCsv(__DIR__ . '/storage/users.csv', [["id", "email"], [1, 'ada@example.com']]);
```

### Files::hash(string $path, string $algo = 'sha256'): string|false
Compute a hash for the file contents.

```php
use PhpHelper\Files;

$checksum = Files::hash(__DIR__ . '/storage/app.log');
```

### Files::isAbsolute(string $path): bool
Determine whether the path is absolute on Unix or Windows.

```php
use PhpHelper\Files;

$isAbsolute = Files::isAbsolute('/var/www');
```

### Files::normalizePath(string $path): string
Normalise path separators to the current platform.

```php
use PhpHelper\Files;

$normalized = Files::normalizePath('storage\\logs/app.log');
```

### Files::joinPaths(string ...$parts): string
Join path segments using the platform-specific separator.

```php
use PhpHelper\Files;

$fullPath = Files::joinPaths(__DIR__, 'storage', 'logs');
```

## Format

Formatting helpers for numbers, durations, and JSON.

### Format::bytes(int $bytes, int $precision = 2, string $system = 'binary', ?array $units = null): string
Convert a byte count into a human readable size.

```php
use PhpHelper\Format;

echo Format::bytes(1_572_864); // "1.50 MB"
```

### Format::number(float|int $value, int $decimals = 0, string $decimalPoint = '.', string $thousandsSep = ','): string
Wrapper around `number_format()` with explicit defaults.

```php
use PhpHelper\Format;

echo Format::number(12345.678, 2); // "12,345.68"
```

### Format::currency(float $amount, string $currency = 'USD', ?string $locale = null, ?int $precision = null): string
Format a currency amount using `NumberFormatter` when available, otherwise fallback formatting.

```php
use PhpHelper\Format;

echo Format::currency(199.99, 'EUR', 'de_DE');
```

### Format::percent(float $value, int $precision = 0, bool $fromFraction = true): string
Create a percent string, optionally treating the value as a fraction.

```php
use PhpHelper\Format;

echo Format::percent(0.256, 1); // "25.6%"
```

### Format::shortNumber(float|int $value, int $precision = 1): string
Produce abbreviations such as K, M, B, T.

```php
use PhpHelper\Format;

echo Format::shortNumber(1250000); // "1.2M"
```

### Format::duration(int $seconds, bool $compact = true): string
Render a duration as compact (`1h 2m`) or verbose (`1 hour, 2 minutes`).

```php
use PhpHelper\Format;

echo Format::duration(3723); // "1h 2m 3s"
```

### Format::hms(int $seconds, bool $withDays = false): string
Return a clock-style string (`HH:MM:SS`) optionally prefixed with days.

```php
use PhpHelper\Format;

echo Format::hms(3661); // "01:01:01"
```

### Format::ordinal(int $number): string
Append the English ordinal suffix (`st`, `nd`, `rd`, `th`).

```php
use PhpHelper\Format;

echo Format::ordinal(23); // "23rd"
```

### Format::parseBytes(string $size): int
Parse human readable sizes such as `"2M"` or `"1.5 GiB"` into bytes.

```php
use PhpHelper\Format;

$bytes = Format::parseBytes('1.5 GiB');
```

### Format::bool(mixed $value, string $true = 'Yes', string $false = 'No', string $null = ''): string
Normalise a value to a human readable boolean label.

```php
use PhpHelper\Format;

echo Format::bool('on'); // "Yes"
```

### Format::json(mixed $value, bool $pretty = true, int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES): string
Encode a value as JSON for display (pretty or compact) with safer escaping.

```php
use PhpHelper\Format;

$json = Format::json(['name' => 'Ada', 'active' => true]);
```

## Http

HTTP response helpers for redirects, downloads, JSON replies, and client inspection.

### Http::redirect(string $url, int $statusCode = 302, bool $exit = true): void
Send a redirect either via headers or a client-side fallback when headers are already sent.

```php
use PhpHelper\Http;

Http::redirect('/dashboard');
```

### Http::download(string $filename, string $mimetype = 'application/octet-stream')
Stream a file to the browser as an attachment.

```php
use PhpHelper\Http;

Http::download(__DIR__ . '/storage/report.pdf', 'application/pdf');
```

### Http::json(mixed $data, int $status = 200, array $headers = []): void
Emit a JSON response with optional status and extra headers.

```php
use PhpHelper\Http;

Http::json(['ok' => true], 201, ['X-Request-Id' => 'abc123']);
```

### Http::clientInfo(): array
Gather best-effort client metadata (IP chain, UA, device flags, request info).

```php
use PhpHelper\Http;

$client = Http::clientInfo();
$primaryIp = $client['ip'];
```

## Logs

Database-backed structured logging helper.

### Logs::configure(array $config = []): void
Configure the logger using a table name and default fields.

**Options**
- `table` (string) Table used for log storage (default `logs`).
- `defaults` (array<string, mixed>) Key/value pairs merged into every record.

```php
use PhpHelper\Logs;

Logs::configure([
    'table' => 'app_logs',
    'defaults' => ['app' => 'frontend'],
]);
```

### Logs::setTable(string $table): void
Change the target table for subsequent log writes.

```php
use PhpHelper\Logs;

Logs::setTable('audit_logs');
```

### Logs::setDefaults(array $defaults): void
Merge additional default context into log records.

```php
use PhpHelper\Logs;

Logs::setDefaults(['environment' => 'testing']);
```

### Logs::clearDefaults(): void
Remove any previously configured defaults.

```php
use PhpHelper\Logs;

Logs::clearDefaults();
```

### Logs::log(string $level, string $message, array $context = [], array $meta = []): string
Persist a log entry and return its primary key.

```php
use PhpHelper\Logs;

$logId = Logs::log('info', 'Background job dispatched', ['job' => 'SyncUsers']);
```

### Logs::debug(string $message, array $context = [], array $meta = []): string
Shortcut for `Logs::log('debug', ...)`.

```php
use PhpHelper\Logs;

Logs::debug('Cache miss', ['key' => 'users:1']);
```

### Logs::info(string $message, array $context = [], array $meta = []): string
Shortcut for an `info` level log.

```php
use PhpHelper\Logs;

Logs::info('User signed in', ['user_id' => 5]);
```

### Logs::notice(string $message, array $context = [], array $meta = []): string
Shortcut for a `notice` level log.

```php
use PhpHelper\Logs;

Logs::notice('Slow response detected', ['route' => '/api']);
```

### Logs::warning(string $message, array $context = [], array $meta = []): string
Shortcut for a `warning` level log.

```php
use PhpHelper\Logs;

Logs::warning('Disk space low', ['free_mb' => 512]);
```

### Logs::error(string $message, array $context = [], array $meta = []): string
Shortcut for an `error` level log.

```php
use PhpHelper\Logs;

Logs::error('Order failed', ['order_id' => 1234]);
```

### Logs::critical(string $message, array $context = [], array $meta = []): string
Shortcut for a `critical` level log.

```php
use PhpHelper\Logs;

Logs::critical('Primary database unavailable');
```

### Logs::alert(string $message, array $context = [], array $meta = []): string
Shortcut for an `alert` level log.

```php
use PhpHelper\Logs;

Logs::alert('Security breach detected', ['ip' => '203.0.113.5']);
```

### Logs::emergency(string $message, array $context = [], array $meta = []): string
Shortcut for an `emergency` level log.

```php
use PhpHelper\Logs;

Logs::emergency('System shutdown imminent');
```

### Logs::createLogsTable(?string $table = null): void
Create the logs table for MySQL or SQLite drivers.

```php
use PhpHelper\Logs;

Logs::createLogsTable();
```

## PrettyErrorHandler

Developer-friendly error handler that renders colourful overlays or CLI traces.

### PrettyErrorHandler::__construct(array $options = [], bool $registerGlobal = true)
Create a handler instance and optionally register it immediately.

**Options**
- `display` (bool) Force enable `display_errors` (default `true`).
- `report` (int) Error reporting level (default `E_ALL`).
- `context_before` (int) Lines of code shown before the error line (default `6`).
- `context_after` (int) Lines shown after the error line (default `4`).
- `show_trace` (bool) Include the stack trace (default `true`).
- `overlay` (bool) Render as an on-page overlay instead of a full HTML page (default `true`).
- `skip_warnings` (bool) Ignore warnings/notice-level errors (default `false`).
- `log_errors` (bool) Append rendered output to `pretty_errors.txt` (default `false`).

```php
use PhpHelper\PrettyErrorHandler;

$handler = new PrettyErrorHandler(['overlay' => false]);
```

### PrettyErrorHandler::enable(array $options = []): PrettyErrorHandler
Convenience factory that constructs and registers the handler in one call.

```php
use PhpHelper\PrettyErrorHandler;

PrettyErrorHandler::enable(['skip_warnings' => true]);
```

### PrettyErrorHandler::register(): void
Register the instance as the global error, exception, and shutdown handler.

```php
use PhpHelper\PrettyErrorHandler;

$handler = new PrettyErrorHandler([], false);
$handler->register();
```

## Str

String manipulation helpers.

### Str::startsWith(string $text, string|array $prefixes, bool $caseSensitive = true): bool
Determine whether the string starts with any of the provided prefixes.

```php
use PhpHelper\Str;

if (Str::startsWith('Laravel', ['Lar', 'Sym'])) {
    // ...
}
```

### Str::endsWith(string $text, string|array $suffixes, bool $caseSensitive = true): bool
Determine whether the string ends with any of the provided suffixes.

```php
use PhpHelper\Str;

$hasPhp = Str::endsWith('index.php', '.php');
```

### Str::contains(string $text, string|array $fragments, bool $caseSensitive = true): bool
Determine whether the string contains any of the fragments.

```php
use PhpHelper\Str;

$mentionsQueue = Str::contains('Queue worker started', 'worker');
```

### Str::slug(string $value, string $separator = '-'): string
Generate a URL-friendly slug.

```php
use PhpHelper\Str;

echo Str::slug('Hello World!'); // "hello-world"
```

### Str::camel(string $value): string
Convert a string to `camelCase`.

```php
use PhpHelper\Str;

echo Str::camel('make_title'); // "makeTitle"
```

### Str::snake(string $value, string $delimiter = '_'): string
Convert a string to `snake_case`.

```php
use PhpHelper\Str;

echo Str::snake('MakeTitle'); // "make_title"
```

### Str::studly(string $value): string
Convert a string to StudlyCase / PascalCase.

```php
use PhpHelper\Str;

echo Str::studly('make_title'); // "MakeTitle"
```

### Str::lower(string $value, ?string $lang = null): string
Convert to lowercase while respecting locale rules when available.

```php
use PhpHelper\Str;

echo Str::lower('İSTANBUL', 'tr');
```

### Str::upper(string $value, ?string $lang = null): string
Convert to uppercase while respecting locale rules when available.

```php
use PhpHelper\Str;

echo Str::upper('istanbul', 'tr');
```

### Str::title(string $value, ?string $lang = null): string
Convert to Title Case, applying locale-aware casing when possible.

```php
use PhpHelper\Str;

echo Str::title('the quick brown fox');
```

### Str::limit(string $value, int $limit = 100, string $end = '...'): string
Truncate a string to the desired character length.

```php
use PhpHelper\Str;

echo Str::limit('Once upon a time in a far away land', 16);
```

### Str::words(string $value, int $words = 100, string $end = '...'): string
Limit a string by words instead of characters.

```php
use PhpHelper\Str;

echo Str::words('This sentence has five words total', 3);
```

### Str::randomString(int $length = 16): string
Generate a cryptographically secure random alpha-numeric string.

```php
use PhpHelper\Str;

$token = Str::randomString(32);
```

### Str::uuid4(): string
Generate a version 4 UUID.

```php
use PhpHelper\Str;

$uuid = Str::uuid4();
```

### Str::isJson(string $value): bool
Determine whether the string contains valid JSON.

```php
use PhpHelper\Str;

$isJson = Str::isJson('{"ok":true}');
```

### Str::normalizeEol(string $value): string
Normalise end-of-line characters to `\n`.

```php
use PhpHelper\Str;

$normalized = Str::normalizeEol("first\r\nsecond");
```

### Str::isEmpty(?string $text, bool $trim = true): bool
Check whether a string is empty (with optional trimming).

```php
use PhpHelper\Str;

$isBlank = Str::isEmpty("  ");
```

### Str::before(string $text, string $search, bool $caseSensitive = true): string
Return everything before the first occurrence of the search string.

```php
use PhpHelper\Str;

echo Str::before('key=value', '='); // "key"
```

### Str::after(string $text, string $search, bool $caseSensitive = true): string
Return everything after the first occurrence of the search string.

```php
use PhpHelper\Str;

echo Str::after('key=value', '='); // "value"
```

### Str::between(string $text, string $from, string $to, bool $caseSensitive = true): ?string
Extract the substring between two delimiters.

```php
use PhpHelper\Str;

$token = Str::between('<id>42</id>', '<id>', '</id>');
```

### Str::replaceFirst(string $text, string $search, string $replace, bool $caseSensitive = true): string
Replace the first occurrence of a substring.

```php
use PhpHelper\Str;

$output = Str::replaceFirst('2024-01-2024', '2024', '2030');
```

### Str::replaceLast(string $text, string $search, string $replace, bool $caseSensitive = true): string
Replace the last occurrence of a substring.

```php
use PhpHelper\Str;

$output = Str::replaceLast('version-1.0.0', '.', '-');
```

### Str::containsAll(string $text, array $needles, bool $caseSensitive = true): bool
Ensure that all provided fragments exist within the string.

```php
use PhpHelper\Str;

$valid = Str::containsAll('First Second Third', ['First', 'Third']);
```

### Str::ensurePrefix(string $text, string $prefix, bool $caseSensitive = true): string
Add the prefix when the string does not already start with it.

```php
use PhpHelper\Str;

$uri = Str::ensurePrefix('admin', '/');
```

### Str::ensureSuffix(string $text, string $suffix, bool $caseSensitive = true): string
Ensure the string ends with the given suffix.

```php
use PhpHelper\Str;

$path = Str::ensureSuffix('/var/www', '/'); // '/var/www/'
```

### Str::squish(string $text): string
Collapse consecutive whitespace to single spaces and trim the string.

```php
use PhpHelper\Str;

$clean = Str::squish("  Hello   world  ");
```

### Str::seoFileName(string $text): string
Produce a SEO-friendly filename that keeps dots and word characters.

```php
use PhpHelper\Str;

$file = Str::seoFileName('Résumé 2024.pdf');
```

### Str::seoUrl(string $text): string
Produce a SEO-friendly URL slug.

```php
use PhpHelper\Str;

$url = Str::seoUrl('Résumé 2024.pdf');
```

### Str::prettyLog(mixed $v): void
Print a prettified value wrapped in `<pre>` tags.

```php
use PhpHelper\Str;

Str::prettyLog(['status' => 'ok']);
```

### Str::prettyLogExit(mixed $v): void
Pretty-print a value, render a black background, and terminate execution.

```php
use PhpHelper\Str;

// Str::prettyLogExit(['debug' => true]);
```

### Str::print_functions(object $obj): void
Dump the methods available on an object.

```php
use PhpHelper\Str;

Str::print_functions(new DateTimeImmutable());
```

### Str::blackBG(): void
Render a blank HTML page with a black background (useful before debug dumps).

```php
use PhpHelper\Str;

Str::blackBG();
```

## TwigHelper

Utilities for bootstrapping and extending Twig environments.

### TwigHelper::init(string|array $paths = [], array $options = [], ?\Twig\Loader\LoaderInterface $loader = null): \Twig\Environment
Create a Twig environment with optional template paths and environment options.

**Path Definitions**
- Pass a string for the default namespace.
- Pass an associative array of `namespace => path` or `namespace => [paths...]` definitions.

**Options** (merged with defaults `cache=false`, `auto_reload=true`, `strict_variables=false`)
- Any `\Twig\Environment` constructor option, such as `cache`, `auto_reload`, `strict_variables`.

```php
use PhpHelper\TwigHelper;

$twig = TwigHelper::init(__DIR__ . '/views', ['cache' => __DIR__ . '/cache/twig']);
```

### TwigHelper::setEnvironment(\Twig\Environment $environment): void
Store an existing environment and register the default helpers.

```php
use PhpHelper\TwigHelper;
use Twig\Environment;

TwigHelper::setEnvironment(new Environment(new Twig\Loader\ArrayLoader([])));
```

### TwigHelper::hasEnvironment(): bool
Check whether an environment has been stored.

```php
use PhpHelper\TwigHelper;

if (!TwigHelper::hasEnvironment()) {
    TwigHelper::init(__DIR__ . '/views');
}
```

### TwigHelper::env(): \Twig\Environment
Retrieve the stored environment (throws when none is configured).

```php
use PhpHelper\TwigHelper;

$env = TwigHelper::env();
```

### TwigHelper::render(string $template, array $context = [], ?\Twig\Environment $environment = null): string
Render a Twig template using the stored or provided environment.

```php
use PhpHelper\TwigHelper;

echo TwigHelper::render('welcome.twig', ['name' => 'Ada']);
```

### TwigHelper::addGlobal(string $name, mixed $value, ?\Twig\Environment $environment = null): void
Register a new global variable available in all templates.

```php
use PhpHelper\TwigHelper;

TwigHelper::addGlobal('appName', 'PHP Helper Demo');
```

### TwigHelper::addFunction(string $name, callable $callable, array $options = [], ?\Twig\Environment $environment = null): void
Add a custom Twig function.

```php
use PhpHelper\TwigHelper;

TwigHelper::addFunction('current_year', fn () => (int) date('Y'));
```

### TwigHelper::addFilter(string $name, callable $callable, array $options = [], ?\Twig\Environment $environment = null): void
Add a custom Twig filter.

```php
use PhpHelper\TwigHelper;

TwigHelper::addFilter('rot13', fn ($value) => str_rot13((string) $value));
```

### TwigHelper::registerDefaults(?\Twig\Environment $environment = null): void
Register the helper-provided filters and functions (formatting, string helpers, etc.).

```php
use PhpHelper\TwigHelper;

TwigHelper::registerDefaults();
```

### TwigHelper::addPath(string $path, ?string $namespace = null, ?\Twig\Environment $environment = null): void
Add an extra template directory to the filesystem loader.

```php
use PhpHelper\TwigHelper;

TwigHelper::addPath(__DIR__ . '/vendor/package/templates', 'pkg');
```

### TwigHelper::clear(): void
Forget the stored environment (useful inside tests).

```php
use PhpHelper\TwigHelper;

TwigHelper::clear();
```

