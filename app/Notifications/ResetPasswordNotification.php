<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Reset Password — '.config('app.name'))
            ->greeting('Halo!')
            ->line('Anda menerima email ini karena ada permintaan reset password untuk akun panel RDM Online Anda.')
            ->action('Reset Password', $url)
            ->line("Link reset password berlaku selama {$expire} menit.")
            ->line('Jika Anda tidak meminta reset password, abaikan email ini.');
    }

    protected function resetUrl($notifiable): string
    {
        return url(route('user.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
