<?php

declare(strict_types=1);

namespace Tests\Testes;

use App\Livewire\Autenticacao\EsqueciSenha;
use App\Livewire\Autenticacao\Entrar;
use App\Livewire\Autenticacao\Cadastrar;
use App\Livewire\Autenticacao\RedefinirSenha;
use App\Models\Usuario;
use App\Notifications\RecuperacaoSenhaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class AutenticacaoLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_de_login_renderiza_com_sucesso(): void
    {
        $resposta = $this->get('/login');

        $resposta->assertStatus(200);
        $resposta->assertSee('Toca');
        $resposta->assertSee('Acessar Plataforma');
    }

    public function test_usuario_pode_se_autenticar_com_credenciais_validas(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Matheus Silva',
            'email' => 'matheus@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        Livewire::test(Entrar::class)
            ->set('email', 'matheus@tocadoestudo.local')
            ->set('password', 'password123')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(route('painel'));

        $this->assertTrue(Auth::check());
        $this->assertEquals($usuario->id, Auth::id());
    }

    public function test_usuario_nao_pode_se_autenticar_com_credenciais_invalidas(): void
    {
        Usuario::create([
            'nome' => 'Matheus Silva',
            'email' => 'matheus@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        Livewire::test(Entrar::class)
            ->set('email', 'matheus@tocadoestudo.local')
            ->set('password', 'senha-incorreta')
            ->call('authenticate')
            ->assertSet('errorMessage', 'As credenciais informadas estão incorretas.');

        $this->assertFalse(Auth::check());
    }

    public function test_cadastro_cria_usuario_e_inicializa_cache_de_estatisticas(): void
    {
        Livewire::test(Cadastrar::class)
            ->set('name', 'Camila Duarte')
            ->set('email', 'camila@tocadoestudo.local')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('painel'));

        $this->assertDatabaseHas('usuarios', [
            'email' => 'camila@tocadoestudo.local',
            'nome' => 'Camila Duarte',
        ]);

        $usuario = Usuario::where('email', 'camila@tocadoestudo.local')->first();
        $this->assertNotNull($usuario);
        $this->assertNotNull($usuario->statsCache);
        $this->assertEquals(0, $usuario->statsCache->total_dias_ativos);
        $this->assertTrue(Auth::check());
    }

    public function test_cadastro_valida_confirmacao_de_senha(): void
    {
        Livewire::test(Cadastrar::class)
            ->set('name', 'Camila Duarte')
            ->set('email', 'camila@tocadoestudo.local')
            ->set('password', 'password123')
            ->set('password_confirmation', 'senha-diferente')
            ->call('register')
            ->assertHasErrors(['password']);

        $this->assertDatabaseMissing('usuarios', [
            'email' => 'camila@tocadoestudo.local',
        ]);
    }

    public function test_recuperacao_de_senha_envia_notificacao_e_protege_contra_enumeracao(): void
    {
        Notification::fake();

        $usuario = Usuario::create([
            'nome' => 'Leonardo Rocha',
            'email' => 'leonardo@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        // Usuário existente
        Livewire::test(EsqueciSenha::class)
            ->set('email', 'leonardo@tocadoestudo.local')
            ->call('sendResetLink')
            ->assertSet('submitted', true)
            ->assertSee('Se o e-mail informado estiver cadastrado');

        Notification::assertSentTo($usuario, RecuperacaoSenhaNotification::class);

        // Usuário inexistente (mesma resposta genérica - OWASP A07)
        Livewire::test(EsqueciSenha::class)
            ->set('email', 'fantasma@tocadoestudo.local')
            ->call('sendResetLink')
            ->assertSet('submitted', true)
            ->assertSee('Se o e-mail informado estiver cadastrado');
    }

    public function test_redefinicao_de_senha_atualiza_a_senha_do_usuario(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Matheus Silva',
            'email' => 'matheus@tocadoestudo.local',
            'password' => Hash::make('senha-antiga'),
        ]);

        $token = Password::broker()->createToken($usuario);

        Livewire::test(RedefinirSenha::class, [
            'token' => $token,
            'email' => 'matheus@tocadoestudo.local',
        ])
            ->assertSet('tokenValid', true)
            ->set('password', 'nova-senha-123')
            ->set('password_confirmation', 'nova-senha-123')
            ->call('resetPassword')
            ->assertSet('success', true);

        $usuario->refresh();
        $this->assertTrue(Hash::check('nova-senha-123', $usuario->password));
    }

    public function test_usuario_autenticado_pode_encerrar_sessao(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Matheus Silva',
            'email' => 'matheus@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($usuario);
        $this->assertTrue(Auth::check());

        $resposta = $this->post('/logout');

        $resposta->assertRedirect(route('login'));
        $this->assertFalse(Auth::check());
    }
}

