<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Filament\Resources\ServerResource\Widgets\ServerDetailStats;
use App\Filament\Resources\ServerResource\Widgets\ServerMetricsChart;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewServer extends ViewRecord
{
    protected static string $resource = ServerResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ServerDetailStats::make(['record' => $this->record]),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ServerMetricsChart::make(['record' => $this->record]),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([]);
    }
}
