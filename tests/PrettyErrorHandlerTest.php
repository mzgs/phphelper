<?php

declare(strict_types=1);

namespace Tests;

use PhpHelper\PrettyErrorHandler;
use PHPUnit\Framework\TestCase;

class PrettyErrorHandlerTest extends TestCase
{
    public function testEnableAndDisableRestoreIniSettings(): void
    {
        $initialDisplay = ini_get('display_errors');
        $initialReporting = error_reporting();

        $handler = PrettyErrorHandler::enable();

        try {
            $this->assertInstanceOf(PrettyErrorHandler::class, $handler);
            $this->assertSame('1', ini_get('display_errors'));
        } finally {
            PrettyErrorHandler::disable();
        }

        $this->assertSame($initialDisplay, ini_get('display_errors'));
        $this->assertSame($initialReporting, error_reporting());
    }
}
