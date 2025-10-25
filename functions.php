<?php

use PhpHelper\Str;


function pl($v)
{
  Str::prettyLog($v);
}


function plExit($v)
{
  Str::blackBG();
  pl($v);
  exit;
}


function parseNumberFromString(string $input): array
{
  preg_match_all('/\d+(\.\d+)?/', $input, $matches);
  return array_map(fn($n) => is_numeric($n) ? $n + 0 : $n, $matches[0]);
}
