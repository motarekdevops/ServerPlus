<?php

namespace App\Livewire;

use App\Models\Server;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class AddServer extends Component
{
    public string $name = '';
    public string $host = '';
    public int $port = 22;
    public string $username = 'root';
    public string $private_key = '';
    public string $group = '';

    public array $checkTypes = ['cpu', 'ram', 'disk', 'uptime'];
    public array $selectedChecks = ['cpu', 'ram', 'disk'];

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'private_key' => 'required|string',
            'group' => 'nullable|string|max:255',
        ];
    }

    public function save()
    {
        $this->validate();

        $server = Server::create([
            'name' => $this->name,
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'private_key' => $this->private_key,
            'group' => $this->group ?: null,
            'status' => 'unknown',
        ]);

        foreach ($this->selectedChecks as $type) {
            $server->checks()->create([
                'type' => $type,
                'warning_threshold' => 70,
                'critical_threshold' => 90,
                'is_active' => true,
            ]);
        }

        session()->flash('message', 'تم إضافة السيرفر بنجاح!');

        return redirect()->route('dashboard');
    }
}