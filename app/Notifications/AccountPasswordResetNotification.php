<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountPasswordResetNotification extends Notification
{
    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tautan reset kata sandi KAI-PJL')
            ->greeting('Permintaan reset kata sandi')
            ->line('Gunakan tautan berikut untuk membuat kata sandi baru untuk akun yang diminta.')
            ->action('Atur ulang kata sandi', route('password.reset', [
                'user' => $notifiable->getKey(),
                'token' => $this->token,
            ]))
            ->line('Tautan berlaku selama '.(int) config('auth.passwords.users.expire', 60).' menit dan hanya berlaku untuk akun tujuan.')
            ->line('Jika Anda tidak meminta reset kata sandi, abaikan email ini.');
    }
}
