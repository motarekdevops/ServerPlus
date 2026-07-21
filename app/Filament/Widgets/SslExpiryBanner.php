<?php

namespace App\Filament\Widgets;

use App\Models\ServerCheck;
use Filament\Widgets\Widget;

class SslExpiryBanner extends Widget
{
    protected static string $view = 'filament.widgets.ssl-expiry-banner';

    protected static ?int $sort = -20;

    protected int | string | array $columnSpan = 'full';

    public function getExpiringCertificates()
    {
        return ServerCheck::where('type', 'ssl')
            ->whereNotNull('domain')
            ->with('server')
            ->get()
            ->map(function ($check) {
                $latest = $check->results()->latest()->first();

                if (! $latest) {
                    return null;
                }

                $daysRemaining = (int) $latest->value;
                $alertThreshold = $check->alert_days_before ?: 15;

                if ($daysRemaining > $alertThreshold) {
                    return null;
                }

                return [
                    'domain' => $check->domain,
                    'server_id' => $check->server->id,
                    'server_name' => $check->server->name,
                    'days_remaining' => $daysRemaining,
                    'is_critical' => $daysRemaining <= 0 || $latest->status === 'critical',
                ];
            })
            ->filter()
            ->values();
    }
}
