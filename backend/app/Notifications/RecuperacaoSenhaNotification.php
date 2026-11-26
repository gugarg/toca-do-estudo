<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecuperacaoSenhaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $urlRedefinicao
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $nome = $notifiable->nome ?? $notifiable->name ?? 'Estudante';
        $minutosExpiracao = (int) config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Redefina sua senha')
            ->view('emails.recuperacao-senha', [
                'nome' => $nome,
                'email' => $notifiable->email ?? '',
                'urlRedefinicao' => $this->urlRedefinicao,
                'minutosExpiracao' => $minutosExpiracao,
            ])
            ->text('emails.recuperacao-senha-text', [
                'nome' => $nome,
                'email' => $notifiable->email ?? '',
                'urlRedefinicao' => $this->urlRedefinicao,
                'minutosExpiracao' => $minutosExpiracao,
            ]);
    }
}
