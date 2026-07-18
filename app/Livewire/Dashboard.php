<?php

namespace App\Livewire;

use App\Models\Server;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard', [
            'servers' => Server::with(['checks.results' => function ($query) {
                $query->latest()->limit(1);
            }])->get(),
        ]);
    }
}