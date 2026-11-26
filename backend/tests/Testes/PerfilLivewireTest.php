<?php

declare(strict_types=1);

namespace Tests\Testes;

use App\Livewire\Perfil\EditarPerfil;
use App\Models\Usuario;
use App\Notifications\RecuperacaoSenhaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PerfilLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_pode_acessar_pagina_de_perfil(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect(route('login'));
    }

    public function test_usuario_autenticado_pode_acessar_pagina_de_perfil_e_ver_detalhes(): void
    {
        $user = Usuario::factory()->create([
            'nome' => 'Lucas Mendonça',
            'email' => 'lucas@tocadoestudo.local',
            'biografia' => 'Focado em aprovação no ITA.',
        ]);

        $response = $this->actingAs($user)->get('/profile');
        $response->assertOk();
        $response->assertSee('Lucas Mendonça');
        $response->assertSee('lucas@tocadoestudo.local');

        Livewire::actingAs($user)
            ->test(EditarPerfil::class)
            ->assertSet('name', 'Lucas Mendonça')
            ->assertSet('email', 'lucas@tocadoestudo.local')
            ->assertSet('bio', 'Focado em aprovação no ITA.');
    }

    public function test_usuario_pode_atualizar_informacoes_do_perfil(): void
    {
        $user = Usuario::factory()->create([
            'nome' => 'Lucas Antigo',
            'email' => 'antigo@tocadoestudo.local',
            'biografia' => 'Bio antiga',
        ]);

        Livewire::actingAs($user)
            ->test(EditarPerfil::class)
            ->set('name', 'Lucas Novo')
            ->set('email', 'novo@tocadoestudo.local')
            ->set('bio', 'Nova bio focada em medicina.')
            ->call('save')
            ->assertSet('feedbackMessage', 'Perfil acadêmico atualizado com sucesso!')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('usuarios', [
            'id' => $user->id,
            'nome' => 'Lucas Novo',
            'email' => 'novo@tocadoestudo.local',
            'biografia' => 'Nova bio focada em medicina.',
        ]);
    }

    public function test_sistema_utiliza_exclusivamente_fuso_horario_de_brasilia(): void
    {
        $user = Usuario::factory()->create();

        // O accessor de timezone do usuário sempre reporta o padrão nacional
        $this->assertSame('America/Sao_Paulo', $user->timezone);

        // A configuração global da aplicação está padronizada em America/Sao_Paulo (UTC-3)
        $this->assertSame('America/Sao_Paulo', config('app.timezone'));
    }

    public function test_usuario_nao_pode_utilizar_email_de_outro_usuario(): void
    {
        $userA = Usuario::factory()->create(['email' => 'usera@tocadoestudo.local']);
        $userB = Usuario::factory()->create(['email' => 'userb@tocadoestudo.local']);

        Livewire::actingAs($userA)
            ->test(EditarPerfil::class)
            ->set('email', 'userb@tocadoestudo.local')
            ->call('save')
            ->assertHasErrors(['email']);

        $this->assertDatabaseHas('usuarios', [
            'id' => $userA->id,
            'email' => 'usera@tocadoestudo.local',
        ]);
    }

    public function test_usuario_pode_manter_proprio_email_ao_salvar(): void
    {
        $user = Usuario::factory()->create(['email' => 'permanente@tocadoestudo.local']);

        Livewire::actingAs($user)
            ->test(EditarPerfil::class)
            ->set('name', 'Novo Nome')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('usuarios', [
            'id' => $user->id,
            'nome' => 'Novo Nome',
            'email' => 'permanente@tocadoestudo.local',
        ]);
    }

    public function test_usuario_pode_enviar_foto_de_perfil_e_converte_para_webp(): void
    {
        Storage::fake('public');

        $user = Usuario::factory()->create();
        $image = UploadedFile::fake()->image('minha_foto.png', 400, 400);

        Livewire::actingAs($user)
            ->test(EditarPerfil::class)
            ->set('avatar', $image)
            ->call('save')
            ->assertSet('feedbackMessage', 'Perfil acadêmico atualizado com sucesso!');

        $freshUser = $user->fresh();
        $this->assertNotNull($freshUser->url_avatar);
        $this->assertStringEndsWith('.webp', $freshUser->url_avatar);

        Storage::disk('public')->assertExists($freshUser->url_avatar);
    }

    public function test_usuario_pode_remover_foto_de_perfil(): void
    {
        Storage::fake('public');

        $caminhoFake = 'avatars/avatar_teste.webp';
        Storage::disk('public')->put($caminhoFake, 'conteudo');

        $user = Usuario::factory()->create([
            'url_avatar' => $caminhoFake,
        ]);

        Livewire::actingAs($user)
            ->test(EditarPerfil::class)
            ->call('removeAvatar')
            ->assertSet('avatarUrl', null)
            ->assertSet('feedbackMessage', 'Foto de perfil removida com sucesso.');

        $this->assertNull($user->fresh()->url_avatar);
        Storage::disk('public')->assertMissing($caminhoFake);
    }

    public function test_usuario_pode_solicitar_link_de_redefinicao_de_senha_a_partir_do_perfil(): void
    {
        Notification::fake();

        $user = Usuario::factory()->create(['email' => 'seguranca@tocadoestudo.local']);

        Livewire::actingAs($user)
            ->test(EditarPerfil::class)
            ->call('sendPasswordResetLink')
            ->assertSet('passwordResetFeedback', 'Enviamos o link de redefinição de senha para seguranca@tocadoestudo.local. Verifique sua caixa de entrada.');

        Notification::assertSentTo($user, RecuperacaoSenhaNotification::class);
    }
}

