<?php

declare(strict_types=1);

namespace App\Livewire\Autenticacao;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Toca do Estudo')]
class Entrar extends Component
{
    #[Validate('required|email', message: [
        'required' => 'O endereço de e-mail é obrigatório.',
        'email' => 'Informe um formato de e-mail válido.',
    ])]
    public string $email = '';

    #[Validate('required|string', message: [
        'required' => 'A senha de acesso é obrigatória.',
    ])]
    public string $password = '';

    public bool $remember = false;
    public ?string $errorMessage = null;

    public function mount(): mixed
    {
        if (Auth::check()) {
            return redirect()->route('painel');
        }

        return null;
    }

    public function authenticate(): mixed
    {
        $this->errorMessage = null;
        $this->validate();

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->errorMessage = "Muitas tentativas em pouco tempo. Por segurança, aguarde {$seconds} segundos antes de tentar novamente.";
            return null;
        }

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, 60);
            $this->errorMessage = 'As credenciais informadas estão incorretas.';
            return null;
        }

        RateLimiter::clear($throttleKey);
        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        return redirect()->intended(route('painel'));
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }

    public function render()
    {
        return view('livewire.autenticacao.entrar');
    }
}
