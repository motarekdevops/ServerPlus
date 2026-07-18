<?php

namespace App\Services;

class CheckEngine
{
    public function evaluate(string $type, float $value, float $warning, float $critical): string
    {
        // Uptime is informational only, not a percentage — never mark it critical/warning
        if ($type === 'uptime') {
            return 'ok';
        }

        if ($value >= $critical) {
            return 'critical';
        }

        if ($value >= $warning) {
            return 'warning';
        }

        return 'ok';
    }
}
