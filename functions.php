<?php

use PhpHelper\Str;


function blackBG()
    {
        echo "<!DOCTYPE html><html><head><style>body { background-color: black; color: #eee; }</style></head><body></body></html>";
    }

  function pl($v)
    {
       Str::prettyLog($v);
    }


      function plExit($v)
    {
        blackBG();
        pl($v);
        exit;
    }
