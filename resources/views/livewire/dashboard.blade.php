<div wire:poll.5s class="p-6">
    <h1 class="text-2xl font-bold mb-6">ServerPlus Dashboard</h1>
    <a href="{{ route('servers.add') }}" class="inline-block bg-blue-600 text-white px-4 py-2 rounded mb-6 hover:bg-blue-700">
    + إضافة سيرفر
</a>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @forelse ($servers as $server)
            <div class="border rounded-lg p-4 shadow-sm
                {{ $server->status === 'online' ? 'border-green-500' : ($server->status === 'offline' ? 'border-red-500' : 'border-gray-300') }}">

                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-bold text-lg">{{ $server->name }}</h3>
                    <span class="px-2 py-1 text-xs rounded-full
                        {{ $server->status === 'online' ? 'bg-green-100 text-green-700' : ($server->status === 'offline' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700') }}">
                        {{ $server->status }}
                    </span>
                </div>

                <p class="text-sm text-gray-500 mb-2">{{ $server->host }}</p>

                @foreach ($server->checks as $check)
                    @php $latest = $check->results->first(); @endphp
                    <div class="flex justify-between text-sm py-1">
                        <span>{{ strtoupper($check->type) }}</span>
                        <span class="font-mono
                            {{ $latest?->status === 'critical' ? 'text-red-600 font-bold' : ($latest?->status === 'warning' ? 'text-yellow-600' : 'text-gray-700') }}">
                            {{ $latest?->value ?? '--' }}%
                        </span>
                    </div>
                @endforeach

                <p class="text-xs text-gray-400 mt-2">
                    آخر فحص: {{ $server->last_checked_at?->diffForHumans() ?? 'لسه ماتفحصش' }}
                </p>
            </div>
        @empty
            <p class="text-gray-500">مفيش سيرفرات مضافة لسه.</p>
        @endforelse
    </div>
</div>