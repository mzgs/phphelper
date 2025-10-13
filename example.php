<?php

require_once __DIR__ . '/src/DB.php';
require_once __DIR__ . '/src/Str.php';


DB::mysql("test_db", "root", "1");
// print_r(DB::getRows("SELECT * FROM users"));
// echo DB::getValue("SELECT username FROM users LIMIT 1") . PHP_EOL;
echo DB::count("users", "age >= ?", [30]) . PHP_EOL;

echo Str::upper("Başlık türkçe isim çöğş","tr") . PHP_EOL;
echo Str::lower("BAŞLIK TÜRKÇE İSİM ÇÖĞŞ","tr") . PHP_EOL;
echo Str::title("BAŞLIK TÜRKÇE İSİM ÇÖĞŞ","tr") . PHP_EOL;
