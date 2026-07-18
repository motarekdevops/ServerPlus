<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServer extends CreateRecord
{
    protected static string $resource = ServerResource::class;

    protected array $checkTypes = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->checkTypes = $data['checkTypes'] ?? ['cpu', 'ram', 'disk'];
        unset($data['checkTypes']);

        $data['status'] = 'unknown';

        return $data;
    }

    protected function afterCreate(): void
    {
        foreach ($this->checkTypes as $type) {
            $this->record->checks()->create([
                'type' => $type,
                'warning_threshold' => 70,
                'critical_threshold' => 90,
                'is_active' => true,
            ]);
        }
    }
}