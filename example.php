<?php

require_once __DIR__ . '/src/DB.php';


DB::mysql("test_db", "root", "1");
print_r(DB::fetchAll("SELECT * FROM users"));