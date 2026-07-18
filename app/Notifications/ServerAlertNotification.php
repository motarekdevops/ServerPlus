<?php

namespace App\Notifications;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServerAlertNotification extends Notification
{
    use Queueable;

    public function __construct(public Alert $alert) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🔴 ServerPulse Alert: ' . $this->alert->rule_triggered)
            ->greeting('Server Alert Triggered')
            ->line($this->alert->message)
            ->line('Server: ' . $this->alert->server->name)
            ->line('Host: ' . $this->alert->server->host)
            ->line('Triggered at: ' . $this->alert->created_at->format('Y-m-d H:i:s'));
    }
}
