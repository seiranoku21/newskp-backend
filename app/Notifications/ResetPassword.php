<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class ResetPassword extends Notification
{
    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
		$base_url = config('app.frontend_url');

		$params =  http_build_query(['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

		$reset_page_link = "$base_url/#/index/resetpassword?$params";

        return (new MailMessage)
        ->subject(Lang::get('Reset Password Notification'))
        ->line(Lang::get('You are receiving this email because we received a password reset request for your account.'))
        ->action(Lang::get('Reset Password'), $reset_page_link)
        ->line(Lang::get('This password reset link will expire in :count minutes.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
        ->line(Lang::get('If you did not request a password reset, no further action is required.'));
    }
}