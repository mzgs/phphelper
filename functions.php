<?php

function dd(...$vars): void
{
    foreach ($vars as $var) {
        var_dump($var);
    }
    die;
}

  function blackBG()
    {
        echo "<!DOCTYPE html><html><head><style>body { background-color: black; color: #eee; }</style></head><body></body></html>";
    }

  function pl($v)
    {
        print ("<pre>" . print_r($v, true) . "</pre>");
    }


      function plExit($v)
    {
        blackBG();
        pl($v);
        exit;
    }
