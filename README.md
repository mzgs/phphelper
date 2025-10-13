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
  
  // 1) One-line URL style (simplest)
  DB::connectUrl('sqlite::memory:');
  DB::execute('CREATE TABLE items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
  
  $id = DB::insert('items', ['name' => 'example']);
  $row = DB::fetch('SELECT * FROM items WHERE id = :id', ['id' => $id]);
  
  // Transactions
  DB::transaction(function () {
      DB::insert('items', ['name' => 'a']);
      DB::insert('items', ['name' => 'b']);
  });
  ```

- MySQL example:

  ```php
  require_once 'src/DB.php';

  // URL-based (recommended)
  DB::connectUrl('mysql://username:password@127.0.0.1:3306/app?charset=utf8mb4');
  ```

 
