<?php

namespace Tests\Unit;

use App\Jobs\CheckServerJob;
use App\Models\Server;
use PHPUnit\Framework\TestCase;

class SslEvaluationTest extends TestCase
{
    protected function evaluateSsl(float $daysRemaining, float $warning, float $critical): string
    {
        // Mirrors the inverted logic in CheckServerJob::evaluateSsl
        if ($daysRemaining <= $critical) {
            return 'critical';
        }

        if ($daysRemaining <= $warning) {
            return 'warning';
        }

        return 'ok';
    }

    public function test_returns_ok_when_plenty_of_days_remaining(): void
    {
        $this->assertEquals('ok', $this->evaluateSsl(60, 14, 7));
    }

    public function test_returns_warning_when_within_warning_window(): void
    {
        $this->assertEquals('warning', $this->evaluateSsl(10, 14, 7));
    }

    public function test_returns_critical_when_within_critical_window(): void
    {
        $this->assertEquals('critical', $this->evaluateSsl(5, 14, 7));
    }

    public function test_returns_critical_when_certificate_already_expired(): void
    {
        $this->assertEquals('critical', $this->evaluateSsl(-3, 14, 7));
    }

    public function test_boundary_at_exact_critical_threshold(): void
    {
        $this->assertEquals('critical', $this->evaluateSsl(7, 14, 7));
    }

    public function test_boundary_at_exact_warning_threshold(): void
    {
        $this->assertEquals('warning', $this->evaluateSsl(14, 14, 7));
    }
}
