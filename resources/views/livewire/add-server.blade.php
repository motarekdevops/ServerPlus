<div class="p-6 max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">إضافة سيرفر جديد</h1>

    @if (session('message'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-4">
        <div>
            <label class="block text-sm font-medium mb-1">اسم السيرفر</label>
            <input type="text" wire:model="name" class="w-full border rounded p-2">
            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Host / IP</label>
            <input type="text" wire:model="host" class="w-full border rounded p-2">
            @error('host') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Port</label>
                <input type="number" wire:model="port" class="w-full border rounded p-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Username</label>
                <input type="text" wire:model="username" class="w-full border rounded p-2">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">SSH Private Key</label>
            <textarea wire:model="private_key" rows="6" class="w-full border rounded p-2 font-mono text-sm" placeholder="-----BEGIN OPENSSH PRIVATE KEY-----"></textarea>
            @error('private_key') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">المجموعة (اختياري)</label>
            <input type="text" wire:model="group" class="w-full border rounded p-2" placeholder="Production, Database, ...">
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">الفحوصات المطلوبة</label>
            <div class="flex gap-4">
                @foreach ($checkTypes as $type)
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model="selectedChecks" value="{{ $type }}">
                        {{ strtoupper($type) }}
                    </label>
                @endforeach
            </div>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            حفظ السيرفر
        </button>
    </form>
</div>