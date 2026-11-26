<?php

declare(strict_types=1);

namespace App\Livewire\Autenticacao;

use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Toca do Estudo')]
class Cadastrar extends Component
{
    #[Validate('required|string|min:2|max:120', message: [
        'required' => 'O nome completo é obrigatório.',
        'min' => 'O nome deve ter no mínimo 2 caracteres.',
        'max' => 'O nome não pode exceder 120 caracteres.',
    ])]
    public string $name = '';

    #[Validate('required|email|max:255|unique:usuarios,email', message: [
        'required' => 'O e-mail é obrigatório.',
        'email' => 'Informe um formato de e-mail válido.',
        'unique' => 'Este e-mail já está em uso por outro estudante.',
    ])]
    public string $email = '';

    #[Validate('required|string|min:6|confirmed', message: [
        'required' => 'A senha de acesso é obrigatória.',
        'min' => 'A senha deve conter no mínimo 6 caracteres.',
        'confirmed' => 'A confirmação de senha não confere.',
    ])]
    public string $password = '';

    public string $password_confirmation = '';
    public ?string $errorMessage = null;

    public function register(): mixed
    {
        $this->errorMessage = null;
        $this->validate();

        $usuario = Usuario::create([
            'nome' => trim($this->name),
            'email' => Str::lower(trim($this->email)),
            'password' => Hash::make($this->password),
        ]);

        Auth::login($usuario);
        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        return redirect()->intended(route('painel'));
    }

    public function render()
    {
        return view('livewire.autenticacao.cadastrar');
    }
}
