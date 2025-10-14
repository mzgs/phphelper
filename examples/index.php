<?php

require_once __DIR__ . '/../src/ErrorHandler.php';

ErrorHandler::enable();

echo $undefinedVar; 