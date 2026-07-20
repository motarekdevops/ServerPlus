<?php

namespace App\Filament\Resources\ServerResource\Widgets;

use App\Models\Server;
use Filament\Widgets\ChartWidget;

class ServerMetricsChart extends ChartWidget
{
    public ?Server $record = null;

    protected static ?string $heading = 'Resource Usage History';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        // Only percentage-based metrics belong on this chart.
        // Uptime is measured in seconds and would break the Y-axis scale.
        $checks = $this->record->checks()->whereIn('type', ['cpu', 'ram', 'disk'])->get();

        $datasets = [];
        $labels = [];

        $colors = [
            'cpu' => '#f59e0b',
            'ram' => '#3b82f6',
            'disk' => '#10b981',
        ];

        foreach ($checks as $check) {
            $results = $check->results()->latest()->limit(20)->get()->reverse();

            if ($labels === []) {
                $labels = $results->pluck('created_at')->map(fn ($d) => $d->format('H:i:s'))->toArray();
            }

            $datasets[] = [
                'label' => strtoupper($check->type),
                'data' => $results->pluck('value')->toArray(),
                'borderColor' => $colors[$check->type] ?? '#999',
                'backgroundColor' => 'transparent',
                'tension' => 0.3,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
