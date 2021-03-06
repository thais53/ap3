<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

class MailVerifyEmailNotification extends Notification
{
    /**
     * The callback that should be used to build the mail message.
     *
     * @var \Closure|null
     */
    public static $toMailCallback;

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $verificationUrl);
        }

        // $link = url( "/pages/reset-password/".$this->token."/".$notifiable->getEmailForVerification() );

        return (new MailMessage)
            ->subject(Lang::get('Verification de votre adresse e-mail'))
            ->greeting('Bonjour ' . $notifiable->firstname . ' ' . $notifiable->lastname . ' !')
            ->line('Afin de vous connecter, vous devrez utiliser votre identifiant :')
            ->line($notifiable->login)
            ->line('Vous pourrez le modifier une fois connecté.')
            ->line(Lang::get('Cliquez sur le lien ci-dessous afin de confirmer votre adresse e-mail.'))
            ->action(Lang::get('Vérifier l\'adresse e-mail'), $verificationUrl)
            ->line(Lang::get("Si vous n'avez pas fait de demande, veuillez ignorer ce message."));
    }

    /**
     * Set a callback that should be used when building the notification mail message.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function toMailUsing($callback)
    {
        static::$toMailCallback = $callback;
    }
}
