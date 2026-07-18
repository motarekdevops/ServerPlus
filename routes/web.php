<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard;
use App\Livewire\AddServer;

Route::get('/', Dashboard::class)->name('dashboard');
Route::get('/servers/add', AddServer::class)->name('servers.add');