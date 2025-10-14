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

 
