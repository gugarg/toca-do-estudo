<?php

declare(strict_types=1);

use App\Livewire\Autenticacao\Cadastrar;
use App\Livewire\Autenticacao\Entrar;
use App\Livewire\Autenticacao\EsqueciSenha;
use App\Livewire\Autenticacao\RedefinirSenha;
use App\Livewire\Grupos\DetalhesGrupo;
use App\Livewire\Painel\VisaoGeral;
use App\Livewire\Perfil\EditarPerfil;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Toca do Estudo — Rotas Web em Português (Stateful Livewire 3)
|--------------------------------------------------------------------------
*/

// Rota responsável por exibir a tela inicial de acesso (login)
Route::get('/', Entrar::class)->name('home');

// Rotas de Autenticação para Convidados
Route::middleware('guest')->group(function () {
    // Rota responsável por exibir o formulário de login para estudantes cadastrados
    Route::get('/entrar', Entrar::class)->name('entrar');
    Route::get('/login', Entrar::class)->name('login');

    // Rota responsável por exibir o formulário de cadastro de um novo usuário estudante
    Route::get('/cadastro', Cadastrar::class)->name('cadastro');
    Route::get('/register', Cadastrar::class)->name('register');

    // Rota responsável por solicitar o link de recuperação de senha por e-mail
    Route::get('/recuperar-senha', EsqueciSenha::class)->name('recuperar-senha');
    Route::get('/forgot-password', EsqueciSenha::class)->name('password.request');

    // Rota responsável por exibir a tela de redefinição de nova senha com o token
    Route::get('/redefinir-senha/{token?}', RedefinirSenha::class)->name('redefinir-senha');
    Route::get('/reset-password/{token?}', RedefinirSenha::class)->name('password.reset');
});

// Encerramento de Sessão
// Rota responsável por encerrar a sessão do usuário com segurança e invalidar o token
Route::post('/sair', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('entrar');
})->name('sair')->middleware('auth');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout')->middleware('auth');

// Rotas Protegidas por Sessão Web
Route::middleware('auth')->group(function () {
    // Rota responsável por exibir o painel principal com as métricas consolidadas de estudo
    Route::get('/painel', VisaoGeral::class)->name('painel');
    Route::get('/dashboard', VisaoGeral::class)->name('dashboard');

    // Rota responsável por exibir a lista de comunidades de estudo do estudante
    Route::get('/comunidades', DetalhesGrupo::class)->name('comunidades');
    Route::get('/groups', DetalhesGrupo::class)->name('groups.index');

    // Rota responsável por exibir o mural e a classificação de uma comunidade específica
    Route::get('/comunidades/{grupo}', DetalhesGrupo::class)->name('comunidades.show');
    Route::get('/groups/{group}', DetalhesGrupo::class)->name('groups.show');

    // Rota responsável por exibir e editar as informações de perfil do estudante
    Route::get('/perfil', EditarPerfil::class)->name('perfil');
    Route::get('/profile', EditarPerfil::class)->name('profile');
});
