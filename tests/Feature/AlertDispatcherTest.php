<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Server;
use App\Models\Setting;
use App\Services\AlertDispatcher;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AlertDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_does_not_send_email_when_disabled(): void
    {
        Notification::fake();

        Setting::create(['email_enabled' => false, 'telegram_enabled' => false]);

        $server = Server::create([
            'name' => 'Test Server',
            'host' => '127.0.0.1',
            'username' => 'root',
            'private_key' => 'fake-key',
        ]);

        $alert = Alert::create([
            'server_id' => $server->id,
            'rule_triggered' => 'cpu exceeded critical threshold',
            'message' => 'Test alert',
        ]);

        $dispatcher = new AlertDispatcher(new TelegramService());
        $dispatcher->dispatch($alert);

        Notification::assertNothingSent();
    }

    public function test_sends_email_when_enabled_with_address(): void
    {
        Notification::fake();

        Setting::create([
            'email_enabled' => true,
            'email_address' => 'test@example.com',
            'telegram_enabled' => false,
        ]);

        $server = Server::create([
            'name' => 'Test Server',
            'host' => '127.0.0.1',
            'username' => 'root',
            'private_key' => 'fake-key',
        ]);

        $alert = Alert::create([
            'server_id' => $server->id,
            'rule_triggered' => 'cpu exceeded critical threshold',
            'message' => 'Test alert',
        ]);

        $dispatcher = new AlertDispatcher(new TelegramService());
        $dispatcher->dispatch($alert);

        Notification::assertSentOnDemand(\App\Notifications\ServerAlertNotification::class);
    }
}
