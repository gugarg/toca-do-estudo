<?php

declare(strict_types=1);

namespace App\Livewire\Autenticacao;

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Toca do Estudo')]
class RedefinirSenha extends Component
{
    #[Url]
    public string $token = '';

    #[Url]
    public string $email = '';

    #[Validate('required|string|min:6|confirmed', message: [
        'required' => 'A nova senha é obrigatória.',
        'min' => 'A nova senha deve conter pelo menos 6 caracteres.',
        'confirmed' => 'A confirmação de nova senha não confere.',
    ])]
    public string $password = '';

    public string $password_confirmation = '';
    public bool $success = false;
    public ?string $errorMessage = null;
    public bool $tokenValid = true;

    public function mount(?string $token = null): void
    {
        if ($token) {
            $this->token = $token;
        }

        if (empty($this->token) && request()->query('token')) {
            $this->token = (string) request()->query('token');
        }

        if (empty($this->email) && request()->query('email')) {
            $this->email = (string) request()->query('email');
        }

        if (empty($this->token) || empty($this->email)) {
            $this->tokenValid = false;
            $this->errorMessage = 'O link de recuperação está incompleto ou inválido.';
            return;
        }

        $usuario = Usuario::where('email', Str::lower(trim($this->email)))->first();
        if (!$usuario || !Password::broker()->tokenExists($usuario, $this->token)) {
            $this->tokenValid = false;
            $this->errorMessage = 'Este link de recuperação é inválido, expirou ou já foi utilizado.';
        }
    }

    public function resetPassword(): void
    {
        $this->errorMessage = null;
        $this->validate();

        $usuario = Usuario::where('email', Str::lower(trim($this->email)))->first();
        if (!$usuario || !Password::broker()->tokenExists($usuario, $this->token)) {
            $this->tokenValid = false;
            $this->errorMessage = 'Este link de recuperação é inválido, expirou ou já foi utilizado. Por favor, solicite um novo link.';
            return;
        }

        $status = Password::broker()->reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function (Usuario $user, string $password) {
                // Criptografia unidirecional com Bcrypt
                $user->password = Hash::make($password);
                $user->setRememberToken(Str::random(60));
                $user->save();

                // Revoga tokens de API Sanctum existentes para invalidar sessões prévias
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $this->success = true;
            $this->password = '';
            $this->password_confirmation = '';
        } else {
            $this->errorMessage = 'Não foi possível redefinir sua senha. O link pode ter expirado ou já ter sido utilizado.';
        }
    }

    public function render()
    {
        return view('livewire.autenticacao.redefinir-senha');
    }
}
