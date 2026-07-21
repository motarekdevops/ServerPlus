<div>
    @php
        $certificates = $this->getExpiringCertificates();
    @endphp

    @if ($certificates->isNotEmpty())
        <div class="space-y-2">
            @foreach ($certificates as $cert)
                <div @class([
                    'flex items-center justify-between rounded-lg p-4 border',
                    'bg-danger-50 border-danger-300 dark:bg-danger-950 dark:border-danger-700' => $cert['is_critical'],
                    'bg-warning-50 border-warning-300 dark:bg-warning-950 dark:border-warning-700' => ! $cert['is_critical'],
                ])>
                    <div class="flex items-center gap-3">
                        <x-filament::icon
                            :icon="$cert['is_critical'] ? 'heroicon-o-shield-exclamation' : 'heroicon-o-clock'"
                            @class([
                                'w-6 h-6',
                                'text-danger-600 dark:text-danger-400' => $cert['is_critical'],
                                'text-warning-600 dark:text-warning-400' => ! $cert['is_critical'],
                            ])
                        />
                        <div>
                            <p @class([
                                'font-semibold',
                                'text-danger-700 dark:text-danger-300' => $cert['is_critical'],
                                'text-warning-700 dark:text-warning-300' => ! $cert['is_critical'],
                            ])>
                                {{ $cert['domain'] }} ({{ $cert['server_name'] }})
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                @if ($cert['days_remaining'] < 0)
                                    SSL certificate expired {{ abs($cert['days_remaining']) }} days ago
                                @else
                                    SSL certificate expires in {{ $cert['days_remaining'] }} days
                                @endif
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('filament.admin.resources.servers.edit', $cert['server_id']) }}">
                        <x-filament::button color="warning" icon="heroicon-o-arrow-path">
                            Renew SSL Certificate
                        </x-filament::button>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div>
