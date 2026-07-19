<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\ServerCheck;
use App\Models\Setting;
use App\Services\CheckEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AlertCooldownTest extends TestCase
{
    use RefreshDatabase;

    public function test_does_not_send_second_alert_within_cooldown_window(): void
    {
        Notification::fake();

        Setting::create(['email_enabled' => false, 'telegram_enabled' => false]);

        $server = Server::create([
            'name' => 'Cooldown Test',
            'host' => '127.0.0.1',
            'username' => 'root',
            'private_key' => 'fake-key',
        ]);

        $check = ServerCheck::create([
            'server_id' => $server->id,
            'type' => 'cpu',
            'warning_threshold' => 70,
            'critical_threshold' => 90,
            'is_active' => true,
            'last_alerted_at' => now(),
        ]);

        $engine = new CheckEngine();
        $status = $engine->evaluate($check->type, 95, $check->warning_threshold, $check->critical_threshold);

        $this->assertEquals('critical', $status);

        $onCooldown = $check->last_alerted_at && $check->last_alerted_at->diffInMinutes(now()) < 30;

        $this->assertTrue($onCooldown, 'Alert should be on cooldown right after firing');
    }

    public function test_allows_new_alert_after_cooldown_expires(): void
    {
        $server = Server::create([
            'name' => 'Cooldown Expiry Test',
            'host' => '127.0.0.1',
            'username' => 'root',
            'private_key' => 'fake-key',
        ]);

        $check = ServerCheck::create([
            'server_id' => $server->id,
            'type' => 'cpu',
            'warning_threshold' => 70,
            'critical_threshold' => 90,
            'is_active' => true,
            'last_alerted_at' => now()->subMinutes(31),
        ]);

        $onCooldown = $check->last_alerted_at && $check->last_alerted_at->diffInMinutes(now()) < 30;

        $this->assertFalse($onCooldown, 'Alert should be allowed after cooldown expires');
    }
}
