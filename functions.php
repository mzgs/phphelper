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
