# PHP Helper Library

A comprehensive PHP helper library providing various utility functions for common tasks. 

## Installation

You can install the library via Composer:

```bash
composer require mzgs/phphelper:dev-main
```

## Requirements

- PHP 8.3 or higher

## Usage

- String helpers are provided by `Str` (e.g., `Str::slug(...)`, `Str::startsWith(...)`).
  - `Str::isEmpty(?string $text, bool $trim = true)` treats null or empty (optionally trimmed) as empty.
- Array helpers are in `Arrays`.
- File helpers are in `Files`.
- Database helpers are in `DB` (PDO-based). Simple usage:

  ```php
  require_once 'src/DB.php';

  // 1) Connect (SQLite in-memory)
  DB::connect('sqlite::memory:');
  DB::execute('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');

  $id = DB::insert('items', ['name' => 'example']);
  $row = DB::getRow('SELECT * FROM items WHERE id = :id', ['id' => $id]);
  $total = DB::count('items');

  // Transactions (atomic group of operations)
  DB::transaction(function () {
      DB::insert('items', ['name' => 'a']);
      DB::insert('items', ['name' => 'b']);
  });
  ```

- MySQL example:

  ```php
  require_once 'src/DB.php';

  // DSN-based
  DB::connect('mysql:host=127.0.0.1;port=3306;dbname=app;charset=utf8mb4', 'username', 'password');

  // Helper builders (host/user/password are optional)
  DB::mysql('app', 'username', 'password', [
      'host' => '127.0.0.1',
      'attributes' => [PDO::ATTR_TIMEOUT => 3],
  ]);

  DB::pgsql('app', 'postgres', 'secret', [
      'host' => 'db',
      'sslmode' => 'require',
  ]);

  DB::sqlite(__DIR__ . '/storage/database.sqlite');
  ```

- Date helpers are in `Dates`:

  ```php
  require_once 'src/Date.php';

  // Relative times
  echo Dates::ago('2024-10-01 12:00:00');          // e.g. "13 days ago"
  echo Dates::ago(time() + 3600);                  // e.g. "in 1 hour"
  echo Dates::ago(new DateTimeImmutable('-3 days'), true); // "3 days ago"

  // Easy formatting
  echo Dates::format('2025-01-02 15:04:05', 'date');             // 2025-01-02
  echo Dates::format('2025-01-02 15:04:05', 'datetime');         // 2025-01-02 15:04
  echo Dates::format('2025-01-02 15:04:05', 'datetime_seconds'); // 2025-01-02 15:04:05
  echo Dates::format('2025-01-02 15:04:05', 'iso');              // 2025-01-02T15:04:05+00:00
  echo Dates::format('2025-01-02 15:04:05', 'rfc2822');          // Thu, 02 Jan 2025 15:04:05 +0000
  echo Dates::format('2025-01-02 15:04:05', 'human_full');       // 2 January 2025, 15:04

  // Custom PHP date() pattern and timezone
  echo Dates::format(time(), 'd/m/Y H:i', 'Europe/Istanbul');
  ```

  Timestamps:

  ```php
  // Current timestamp
  $now = Dates::timestamp();

  // From DateTimeInterface
  $ts1 = Dates::timestamp(new DateTimeImmutable('2025-01-02 15:04:05'));

  // From string (using optional timezone when string lacks TZ)
  $ts2 = Dates::timestamp('2025-01-02 15:04:05', 'Europe/Istanbul');

  // From integer (returns as-is)
  $ts3 = Dates::timestamp(1735820645);
  ```

- HTTP helpers are in `Http`:

  ```php
  require_once 'src/Http.php';

  // Redirect
  // Http::redirect('/login');

  // File download
  // Http::download('/path/to/report.csv', 'text/csv');

  // Client info (best-effort)
  $info = Http::clientInfo();
  // Example keys:
  // ip, ips, is_proxy, user_agent, browser, browser_version,
  // os, engine, device, is_mobile, is_tablet, is_desktop, is_bot,
  // accept_language, languages, accept, referer, method, scheme,
  // host, port, path, query, url
  ```

- Twig templates are rendered via `TwigHelper`:

  ```php
  require_once 'src/TwigHelper.php';

  // Point Twig at your template directories (namespaces optional)
  TwigHelper::init([
      __DIR__ . '/resources/views',
      'emails' => __DIR__ . '/resources/views/emails',
  ], [
      'strict_variables' => true,
  ]);

  // Share globals and add re-usable helpers
  TwigHelper::addGlobal('appName', 'PHP Helper');
  TwigHelper::addFunction('uppercase', fn (string $value): string => strtoupper($value));

  echo TwigHelper::render('welcome.twig', ['user' => 'Ada']);
  echo TwigHelper::render('@emails/reminder.twig', ['user' => 'Bob']);
  ```

  Templates can use the globals and helpers immediately, e.g. `{{ appName }}` or `{{ uppercase(user) }}`. See `examples/index.php` for a simple Bootstrap card rendered with Twig.



- Authentication helpers are in `AuthManager`:

  ```php
  require_once 'src/DB.php';
  require_once 'src/AuthManager.php';

  DB::connect('sqlite::memory:'); // or DB::mysql('app', 'user', 'pass');

  AuthManager::init(DB::pdo(), [
      'table' => 'users',
      'email_column' => 'email',
      'password_column' => 'password_hash',
      // provide a strong secret to enable signed remember-me cookies (ideally from env)
      'remember_secret' => 'change-this-secret',
      'remember_me' => true,
  ]);

  // One-time helper: creates the table if it does not exist (safe to call repeatedly)
  AuthManager::createUsersTable([
      'password_column' => 'password_hash',
      'extra_columns' => ['active' => 'INTEGER DEFAULT 1'],
  ]);

  $newUser = AuthManager::register('new@example.com', 'strong-password', [
      'name' => 'New User',
      'active' => 1,
  ]);

  // Pass true to issue a persistent signed cookie in addition to the PHP session
  $sessionUser = AuthManager::login('new@example.com', 'strong-password', true);
  if ($sessionUser !== null) {
      $currentUser = AuthManager::user(); // password hash removed
  }
  ```

- `init` accepts additional session/remember-me options: toggle sessions (`sessions`), change the session key (`session_key`), configure cookie name/duration/options (`remember_*`, default duration 360 days), and supply an optional `remember_secret` used to sign cookies (a deterministic fallback is generated, but overriding with your own secret is strongly recommended).
- A PHP session is bootstrapped automatically; successful logins are written to `$_SESSION` and (optionally) to a signed cookie for future requests.
- `login` returns the sanitized user record or `null`; use the third argument to control persistent cookies.
- `AuthManager::user()` exposes the current user and `AuthManager::isLoggedIn()` provides a quick boolean check.

## Error Handler

Capture PHP errors/exceptions and render a pretty error page (HTML in web, plaintext in CLI) with file, line, and code snippet highlighting.

Usage (place as early as possible in your entry script):

```php
require_once 'src/PrettyErrorHandler.php';

// Registers global handlers immediately
new PrettyErrorHandler([
    // optional settings (defaults shown)
    'display' => true,          // force display_errors on
    'report' => E_ALL,          // error_reporting level
    'context_before' => 6,      // lines before the error line
    'context_after' => 4,       // lines after the error line
    'show_trace' => true,       // include stack trace
    'overlay' => true,          // render as dismissible overlay (set false for full page)
    'skip_warnings' => false,   // bypass handler for PHP warnings
]);

// Or via static helper
// PrettyErrorHandler::enable();

// Trigger an error to see output (example):
// echo $undefinedVar; // Notice with highlighted snippet
```

## Transactions

`DB::transaction(callable $callback): mixed` wraps your operations in a database transaction. It starts a transaction if none is active, commits on success, and rolls back on exceptions. The method returns whatever your callback returns.

Key points:
- Starts a transaction only when not already inside one.
- Commits on success; rolls back on any thrown exception.
- Returns the callback's return value.
- Supports nesting by joining an existing transaction (no nested commits/rollbacks).

Examples:

```php
// 1) Commit on success and return a value
$newUserId = DB::transaction(function () {
    return DB::insert('users', ['name' => 'Ada']);
});

// 2) Roll back on exception
try {
    DB::transaction(function () {
        DB::execute('UPDATE accounts SET balance = balance - :amt WHERE id = :id', ['amt' => 30, 'id' => 1]);
        throw new RuntimeException('transfer failed');
        // This won't run; entire transaction rolls back
        DB::execute('UPDATE accounts SET balance = balance + :amt WHERE id = :id', ['amt' => 30, 'id' => 2]);
    });
} catch (Throwable $e) {
    // Changes were rolled back automatically
}

// 3) Nested usage (inner joins the outer)
DB::transaction(function () {
    DB::insert('orders', ['ref' => 'A-1']);
    DB::transaction(function () {
        DB::insert('order_items', ['order_id' => 1, 'sku' => 'X']);
    });
});
```

Lower-level methods are also available if needed: `DB::beginTransaction()`, `DB::commit()`, and `DB::rollBack()`.

 
