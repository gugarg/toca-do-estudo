<?php

declare(strict_types=1);

namespace Tests\Testes;

use App\Livewire\Painel\VisaoGeral;
use App\Models\Grupo;
use App\Models\SessaoEstudo;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PainelLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_e_redirecionado_para_login_ao_tentar_acessar_painel(): void
    {
        $resposta = $this->get('/dashboard');

        $resposta->assertRedirect(route('login'));
    }

    public function test_usuario_autenticado_pode_visualizar_painel_com_metricas_consolidadas(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Matheus Silva',
            'email' => 'matheus@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $hoje = Carbon::today()->toDateString();
        $ontem = Carbon::yesterday()->toDateString();

        SessaoEstudo::create([
            'usuario_id' => $usuario->id,
            'data_estudo' => $hoje,
            'disciplina' => 'Direito Constitucional',
            'minutos' => 120,
            'questoes' => 45,
            'anotacoes' => 'Revisão de controle de constitucionalidade.',
        ]);

        SessaoEstudo::create([
            'usuario_id' => $usuario->id,
            'data_estudo' => $ontem,
            'disciplina' => 'Direito Administrativo',
            'minutos' => 90,
            'questoes' => 30,
            'anotacoes' => 'Poder de polícia e atos administrativos.',
        ]);

        $this->actingAs($usuario);

        $resposta = $this->get('/dashboard');

        $resposta->assertStatus(200);
        $resposta->assertSee('Matheus');
        $resposta->assertSee('Visão consolidada da sua consistência');
        $resposta->assertSee('Direito Constitucional');
        $resposta->assertSee('Direito Administrativo');
        $resposta->assertSee('Dias Ativos');
        $resposta->assertSee('3.5h'); // 210 minutos = 3.5h
        $resposta->assertSee('75');   // 45 + 30 questões
    }

    public function test_painel_exibe_estado_vazio_quando_usuario_nao_possui_estudos(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Camila Duarte',
            'email' => 'camila@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($usuario);

        Livewire::test(VisaoGeral::class)
            ->assertSee('Nenhum estudo registrado ainda.')
            ->assertSee('Fazer Primeiro Registro');
    }

    public function test_painel_renderiza_grupos_associados_ao_estudante(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Leonardo Rocha',
            'email' => 'leonardo@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $grupo = Grupo::create([
            'proprietario_id' => $usuario->id,
            'nome' => 'Ratos Fiscais 2026',
            'descricao' => 'Grupo de estudos avançados.',
            'codigo_convite' => 'RAT-FISC26',
            'metrica_padrao' => 'horas_questoes',
            'pontos_hora' => 10.0,
            'pontos_questao' => 1.0,
        ]);

        $grupo->members()->attach($usuario->id, ['papel' => 'proprietario', 'ingressou_em' => now()]);

        $this->actingAs($usuario);

        Livewire::test(VisaoGeral::class)
            ->assertSee('Ratos Fiscais 2026')
            ->assertSee('1 membro')
            ->assertDontSee('RAT-FISC26');
    }

    public function test_evento_de_atualizacao_do_painel_recarrega_componente(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Matheus Silva',
            'email' => 'matheus@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($usuario);

        Livewire::test(VisaoGeral::class)
            ->dispatch('session-created')
            ->assertOk();
    }

    public function test_painel_possui_exatamente_um_botao_de_registrar_estudo(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Ana Clara',
            'email' => 'ana@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($usuario);

        $resposta = $this->get('/painel');
        $resposta->assertStatus(200);

        $conteudo = $resposta->getContent();
        $ocorrencias = substr_count($conteudo, 'Registrar Estudo');
        $this->assertSame(1, $ocorrencias, 'O painel deve conter exatamente um botão "Registrar Estudo".');
        $this->assertStringContainsString("abrir-modal-sessao", $conteudo);
    }
}

