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

 
