<?php

namespace Tests\Unit;

use App\Services\CheckEngine;
use PHPUnit\Framework\TestCase;

class CheckEngineTest extends TestCase
{
    protected CheckEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new CheckEngine();
    }

    public function test_returns_ok_when_value_below_warning(): void
    {
        $status = $this->engine->evaluate('cpu', 50, 70, 90);
        $this->assertEquals('ok', $status);
    }

    public function test_returns_warning_when_value_at_warning_threshold(): void
    {
        $status = $this->engine->evaluate('cpu', 70, 70, 90);
        $this->assertEquals('warning', $status);
    }

    public function test_returns_warning_when_value_between_warning_and_critical(): void
    {
        $status = $this->engine->evaluate('ram', 80, 70, 90);
        $this->assertEquals('warning', $status);
    }

    public function test_returns_critical_when_value_at_critical_threshold(): void
    {
        $status = $this->engine->evaluate('disk', 90, 70, 90);
        $this->assertEquals('critical', $status);
    }

    public function test_returns_critical_when_value_exceeds_critical(): void
    {
        $status = $this->engine->evaluate('disk', 95, 70, 90);
        $this->assertEquals('critical', $status);
    }

    public function test_uptime_is_always_ok_regardless_of_value(): void
    {
        // Uptime is informational only — never critical/warning even with huge values
        $status = $this->engine->evaluate('uptime', 999999, 70, 90);
        $this->assertEquals('ok', $status);
    }

    public function test_uptime_ignores_thresholds_even_at_zero(): void
    {
        $status = $this->engine->evaluate('uptime', 0, 70, 90);
        $this->assertEquals('ok', $status);
    }
}
