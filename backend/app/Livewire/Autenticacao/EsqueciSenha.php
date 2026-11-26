<?php

declare(strict_types=1);

namespace App\Livewire\Autenticacao;

use App\Models\Usuario;
use App\Notifications\RecuperacaoSenhaNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Toca do Estudo')]
class EsqueciSenha extends Component
{
    #[Validate('required|email', message: [
        'required' => 'O endereço de e-mail é obrigatório.',
        'email' => 'Informe um formato de e-mail válido.',
    ])]
    public string $email = '';

    public bool $submitted = false;
    public ?string $errorMessage = null;

    public function sendResetLink(): void
    {
        $this->errorMessage = null;
        $this->validate();

        $normalizedEmail = Str::lower(trim($this->email));
        $throttleKey = 'forgot-password|' . $normalizedEmail . '|' . request()->ip();
        $ipThrottleKey = 'forgot-password-ip|' . request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->errorMessage = "Muitas solicitações para este e-mail. Por segurança, aguarde {$seconds} segundos antes de tentar novamente.";
            return;
        }

        if (RateLimiter::tooManyAttempts($ipThrottleKey, 10)) {
            $seconds = RateLimiter::availableIn($ipThrottleKey);
            $this->errorMessage = "Muitas solicitações a partir deste dispositivo. Por segurança, aguarde {$seconds} segundos antes de tentar novamente.";
            return;
        }

        RateLimiter::hit($throttleKey, 300);
        RateLimiter::hit($ipThrottleKey, 300);

        $usuario = Usuario::where('email', $normalizedEmail)->first();

        if ($usuario) {
            try {
                $token = Password::broker()->createToken($usuario);

                // Constrói o link oficial utilizando URL_SITE ou rota pública da aplicação
                $baseUrl = rtrim((string) (config('app.url_site') ?: env('URL_SITE') ?: config('app.url') ?: url('/')), '/');
                $urlRedefinicao = "{$baseUrl}/redefinir-senha?token={$token}&email=" . urlencode($usuario->email);

                $usuario->notify(new RecuperacaoSenhaNotification($urlRedefinicao));
            } catch (\Throwable $e) {
                // Registra o erro técnico sem expor segredos ou tokens
                Log::error('Falha no envio de e-mail de recuperação de senha via SMTP: ' . $e->getMessage(), [
                    'email_mascarado' => Str::mask($normalizedEmail, '*', 3, -4),
                    'exception' => get_class($e),
                ]);

                $this->errorMessage = 'Não foi possível enviar o e-mail de recuperação no momento devido a uma falha no serviço SMTP. Por favor, tente novamente mais tarde.';
                return;
            }
        }

        // Resposta genérica segura para prevenir enumeração de usuários (OWASP)
        $this->submitted = true;
    }

    public function novaSolicitacao(): void
    {
        $this->submitted = false;
        $this->errorMessage = null;
    }

    public function render()
    {
        return view('livewire.autenticacao.esqueci-senha');
    }
}
