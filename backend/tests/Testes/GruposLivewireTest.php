<?php

declare(strict_types=1);

namespace Tests\Testes;

use App\Livewire\Grupos\DetalhesGrupo;
use App\Models\Grupo;
use App\Models\SessaoEstudo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class GruposLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_pode_acessar_pagina_de_comunidades(): void
    {
        $resposta = $this->get('/groups');

        $resposta->assertRedirect(route('login'));
    }

    public function test_estudante_nao_membro_nao_pode_acessar_comunidade_privada(): void
    {
        $dono = Usuario::create([
            'nome' => 'Camila Duarte',
            'email' => 'camila@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $aluno = Usuario::create([
            'nome' => 'Matheus Silva',
            'email' => 'matheus@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $grupo = Grupo::create([
            'proprietario_id' => $dono->id,
            'nome' => 'Engenharia Civil',
            'codigo_convite' => 'RAT-ENGCIV',
            'metrica_padrao' => 'dias_ativos',
        ]);
        $grupo->members()->attach($dono->id, ['papel' => 'proprietario', 'ingressou_em' => now()]);

        $this->actingAs($aluno);

        $resposta = $this->get("/groups/{$grupo->id}");

        $resposta->assertStatus(403);
    }

    public function test_estudante_membro_pode_visualizar_detalhes_e_mural_do_grupo(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Matheus Silva',
            'email' => 'matheus@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $grupo = Grupo::create([
            'proprietario_id' => $usuario->id,
            'nome' => 'Ratos Fiscais 2026',
            'descricao' => 'Grupo de estudos focado na Receita Federal.',
            'codigo_convite' => 'RAT-FISC26',
            'metrica_padrao' => 'horas_questoes',
            'pontos_hora' => 10.0,
            'pontos_questao' => 1.0,
        ]);
        $grupo->members()->attach($usuario->id, ['papel' => 'proprietario', 'ingressou_em' => now()]);

        $sessao = SessaoEstudo::create([
            'usuario_id' => $usuario->id,
            'data_estudo' => now()->toDateString(),
            'disciplina' => 'Direito Tributário',
            'minutos' => 120,
            'questoes' => 50,
            'anotacoes' => 'Resolução de questões discursivas.',
            'compartilhar' => true,
        ]);

        $this->actingAs($usuario);

        $resposta = $this->get("/groups/{$grupo->id}");

        $resposta->assertStatus(200);
        $resposta->assertSee('Ratos Fiscais 2026');
        $resposta->assertSee('RAT-FISC26');
        $resposta->assertSee('Direito Tributário');
        $resposta->assertSee('50 questões');
    }

    public function test_estudante_pode_alternar_entre_abas_de_mural_e_ranking(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Matheus Silva',
            'email' => 'matheus@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $grupo = Grupo::create([
            'proprietario_id' => $usuario->id,
            'nome' => 'Ratos Fiscais 2026',
            'codigo_convite' => 'RAT-FISC26',
            'metrica_padrao' => 'active_days',
        ]);
        $grupo->members()->attach($usuario->id, ['papel' => 'proprietario', 'ingressou_em' => now()]);

        $this->actingAs($usuario);

        Livewire::test(DetalhesGrupo::class, ['group' => $grupo])
            ->assertSet('activeTab', 'feed')
            ->call('switchTab', 'leaderboard')
            ->assertSet('activeTab', 'leaderboard')
            ->assertSee('Ranking da Comunidade');
    }

    public function test_estudante_pode_criar_novo_grupo_na_modalidade_dias_ativos(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Leonardo Rocha',
            'email' => 'leonardo@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($usuario);

        Livewire::test(DetalhesGrupo::class)
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->set('newGroupName', 'Backend PHP Moderno')
            ->set('newGroupDescription', 'Grupo focado em Laravel e Livewire.')
            ->set('newGroupMetric', 'dias_ativos')
            ->call('createGroup')
            ->assertSet('showCreateModal', false)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('grupos', [
            'nome' => 'Backend PHP Moderno',
            'proprietario_id' => $usuario->id,
            'metrica_padrao' => 'dias_ativos',
        ]);

        $novoGrupo = Grupo::where('nome', 'Backend PHP Moderno')->first();
        $this->assertNotNull($novoGrupo);
        $this->assertMatchesRegularExpression('/^(TOCA|EJ|RAT)-/', $novoGrupo->codigo_convite);
        $this->assertTrue($novoGrupo->members()->where('usuarios.id', $usuario->id)->exists());
    }

    public function test_estudante_pode_criar_novo_grupo_na_modalidade_horas_questoes_com_pesos(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Beatriz Santos',
            'email' => 'beatriz@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($usuario);

        Livewire::test(DetalhesGrupo::class)
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->set('newGroupName', 'Tribunais Federais')
            ->set('newGroupDescription', 'Foco em horas líquidas e questões discursivas.')
            ->set('newGroupMetric', 'horas_questoes')
            ->set('newGroupPointsPerHour', 15.5)
            ->set('newGroupPointsPerQuestion', 2.0)
            ->call('createGroup')
            ->assertSet('showCreateModal', false)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('grupos', [
            'nome' => 'Tribunais Federais',
            'proprietario_id' => $usuario->id,
            'metrica_padrao' => 'horas_questoes',
            'pontos_hora' => 15.50,
            'pontos_questao' => 2.00,
        ]);
    }

    public function test_nao_permite_criar_grupo_com_modalidade_obsoleta_ou_invalida(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Carlos Alberto',
            'email' => 'carlos@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($usuario);

        // Tenta criar com 'hours' (antiga modalidade descontinuada)
        Livewire::test(DetalhesGrupo::class)
            ->call('openCreateModal')
            ->set('newGroupName', 'Grupo Antigo')
            ->set('newGroupMetric', 'hours')
            ->call('createGroup')
            ->assertHasErrors(['newGroupMetric']);

        // Tenta criar com 'questions' (antiga modalidade descontinuada)
        Livewire::test(DetalhesGrupo::class)
            ->call('openCreateModal')
            ->set('newGroupName', 'Grupo Questões')
            ->set('newGroupMetric', 'questions')
            ->call('createGroup')
            ->assertHasErrors(['newGroupMetric']);

        $this->assertDatabaseMissing('grupos', [
            'nome' => 'Grupo Antigo',
        ]);
    }

    public function test_nao_permite_criar_grupo_por_pontos_sem_pesos_positivos_validos(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Fernanda Lima',
            'email' => 'fernanda@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($usuario);

        // Pesos zerados ou negativos
        Livewire::test(DetalhesGrupo::class)
            ->call('openCreateModal')
            ->set('newGroupName', 'Grupo Pesos Invalidos')
            ->set('newGroupMetric', 'horas_questoes')
            ->set('newGroupPointsPerHour', 0)
            ->set('newGroupPointsPerQuestion', -5)
            ->call('createGroup')
            ->assertHasErrors(['newGroupPointsPerHour', 'newGroupPointsPerQuestion']);

        $this->assertDatabaseMissing('grupos', [
            'nome' => 'Grupo Pesos Invalidos',
        ]);
    }

    public function test_estudante_pode_ingressar_em_grupo_com_codigo_de_convite_valido(): void
    {
        $dono = Usuario::create([
            'nome' => 'Camila Duarte',
            'email' => 'camila@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $grupo = Grupo::create([
            'proprietario_id' => $dono->id,
            'nome' => 'Cálculo Avançado',
            'codigo_convite' => 'RAT-CALC26',
            'metrica_padrao' => 'horas_questoes',
            'pontos_hora' => 10.0,
            'pontos_questao' => 1.0,
        ]);
        $grupo->members()->attach($dono->id, ['papel' => 'proprietario', 'ingressou_em' => now()]);

        $novoAluno = Usuario::create([
            'nome' => 'Matheus Silva',
            'email' => 'matheus@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($novoAluno);

        Livewire::test(DetalhesGrupo::class)
            ->call('openJoinModal')
            ->assertSet('showJoinModal', true)
            ->set('inviteCode', 'RAT-CALC26')
            ->call('joinGroup')
            ->assertSet('showJoinModal', false)
            ->assertHasNoErrors();

        $this->assertTrue($grupo->members()->where('usuarios.id', $novoAluno->id)->exists());
    }

    public function test_estudante_recebe_erro_ao_tentar_ingressar_com_codigo_invalido(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Matheus Silva',
            'email' => 'matheus@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($usuario);

        $teste = Livewire::test(DetalhesGrupo::class)
            ->set('inviteCode', 'RAT-INEXISTENTE')
            ->call('joinGroup')
            ->assertSet('showJoinModal', true);

        $this->assertNotNull($teste->get('modalError'));
    }

    public function test_estudante_nao_pode_ingressar_duas_vezes_no_mesmo_grupo(): void
    {
        $dono = Usuario::create([
            'nome' => 'Camila Duarte',
            'email' => 'camila@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $grupo = Grupo::create([
            'proprietario_id' => $dono->id,
            'nome' => 'Cálculo Avançado',
            'codigo_convite' => 'RAT-CALC26',
            'metrica_padrao' => 'horas_questoes',
            'pontos_hora' => 10.0,
            'pontos_questao' => 1.0,
        ]);
        $grupo->members()->attach($dono->id, ['papel' => 'proprietario', 'ingressou_em' => now()]);

        $membro = Usuario::create([
            'nome' => 'Leonardo Rocha',
            'email' => 'leonardo@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);
        $grupo->members()->attach($membro->id, ['papel' => 'membro', 'ingressou_em' => now()]);

        $this->actingAs($membro);

        // Tenta ingressar novamente no mesmo grupo onde já é membro
        $teste = Livewire::test(DetalhesGrupo::class)
            ->set('inviteCode', 'RAT-CALC26')
            ->call('joinGroup')
            ->assertSet('showJoinModal', true);

        $this->assertSame('Você já é membro deste grupo de estudos.', $teste->get('modalError'));
        $this->assertSame(1, $grupo->members()->where('usuarios.id', $membro->id)->count(), 'A associação não pode ser duplicada.');
    }

    public function test_membro_pode_sair_do_grupo(): void
    {
        $dono = Usuario::create([
            'nome' => 'Camila Duarte',
            'email' => 'camila@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $membro = Usuario::create([
            'nome' => 'Leonardo Rocha',
            'email' => 'leonardo@tocadoestudo.local',
            'password' => Hash::make('password123'),
        ]);

        $grupo = Grupo::create([
            'proprietario_id' => $dono->id,
            'nome' => 'Grupo de Testes',
            'codigo_convite' => 'RAT-TEST01',
            'metrica_padrao' => 'active_days',
        ]);
        $grupo->members()->attach($dono->id, ['papel' => 'proprietario', 'ingressou_em' => now()]);
        $grupo->members()->attach($membro->id, ['papel' => 'membro', 'ingressou_em' => now()]);

        $this->actingAs($membro);

        Livewire::test(DetalhesGrupo::class, ['group' => $grupo])
            ->call('openLeaveModal')
            ->assertSet('showLeaveModal', true)
            ->call('leaveGroup')
            ->assertSet('showLeaveModal', false);

        $this->assertFalse($grupo->members()->where('usuarios.id', $membro->id)->exists());
    }

    public function test_pesos_do_grupo_sao_imutaveis_e_rejeitam_alteracao_apos_criacao(): void
    {
        $dono = Usuario::factory()->create();
        $grupo = Grupo::create([
            'proprietario_id' => $dono->id,
            'nome' => 'Grupo Imutavel',
            'codigo_convite' => 'RAT-IMUT01',
            'metrica_padrao' => 'horas_questoes',
            'pontos_hora' => 10.0,
            'pontos_questao' => 1.0,
        ]);
        $grupo->members()->attach($dono->id, ['papel' => 'proprietario', 'ingressou_em' => now()]);

        $token = $dono->createToken('token')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/groups/{$grupo->id}", [
                'pontos_hora' => 20.0,
                'pontos_questao' => 5.0,
            ]);

        $this->assertEquals(10.0, (float) $grupo->fresh()->pontos_hora);
        $this->assertEquals(1.0, (float) $grupo->fresh()->pontos_questao);
    }

    public function test_proprietario_nao_pode_sair_do_grupo_mesmo_sendo_o_unico_integrante(): void
    {
        $dono = Usuario::factory()->create([
            'nome' => 'Líder Fundador',
            'email' => 'lider@tocadoestudo.local',
        ]);

        $grupo = Grupo::create([
            'proprietario_id' => $dono->id,
            'nome' => 'Comunidade Exclusiva',
            'codigo_convite' => 'TOCA-LID01',
            'metrica_padrao' => 'active_days',
        ]);
        $grupo->members()->attach($dono->id, ['papel' => 'proprietario', 'ingressou_em' => now()]);

        // Garante a pré-condição: o proprietário é o único integrante da comunidade
        $this->assertEquals(1, $grupo->members()->count());
        $this->assertTrue($grupo->members()->where('usuarios.id', $dono->id)->exists());

        $this->actingAs($dono);

        // 1. Na interface Livewire: a UI exibe o selo de "Líder da Comunidade"
        $componente = Livewire::test(DetalhesGrupo::class, ['group' => $grupo]);
        $componente->assertSee('Líder da Comunidade');

        // 2. Se o líder tentar acionar a saída da comunidade:
        $componente->call('openLeaveModal')
            ->assertSet('showLeaveModal', true)
            ->call('leaveGroup');

        $componente->assertSet(
            'modalError',
            'Você não pode sair desta comunidade porque é o proprietário, mesmo que seja o único integrante. A transferência de titularidade e a exclusão da comunidade ainda não estão disponíveis.'
        );

        // 3. Integridade relacional no banco de dados: o usuário permanece como membro único
        $this->assertTrue($grupo->fresh()->members()->where('usuarios.id', $dono->id)->exists());
        $this->assertEquals(1, $grupo->fresh()->members()->count());

        // 4. Verificação no nível de serviço (GrupoService): lança exceção com mensagem explicativa
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Você não pode sair desta comunidade porque é o proprietário, mesmo que seja o único integrante.');
        app(\App\Services\GrupoService::class)->sairGrupo($dono, $grupo);
    }

    public function test_proprietario_pode_alterar_nome_da_comunidade_atraves_das_configuracoes(): void
    {
        $dono = Usuario::factory()->create([
            'nome' => 'Líder do Grupo',
            'email' => 'dono@tocadoestudo.local',
        ]);

        $grupo = Grupo::create([
            'proprietario_id' => $dono->id,
            'nome' => 'Nome Antigo',
            'descricao' => 'Descrição inicial',
            'codigo_convite' => 'TOCA-CFG01',
            'metrica_padrao' => 'active_days',
        ]);
        $grupo->members()->attach($dono->id, ['papel' => 'proprietario', 'ingressou_em' => now()]);

        $this->actingAs($dono);

        Livewire::test(DetalhesGrupo::class, ['group' => $grupo])
            ->call('openEditModal')
            ->assertSet('showEditModal', true)
            ->assertSet('editGroupName', 'Nome Antigo')
            ->set('editGroupName', 'Carreiras Policiais e Federais')
            ->set('editGroupDescription', 'Grupo focado em preparação para editais da PF e PRF.')
            ->call('updateGroupSettings')
            ->assertSet('showEditModal', false)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('grupos', [
            'id' => $grupo->id,
            'nome' => 'Carreiras Policiais e Federais',
            'descricao' => 'Grupo focado em preparação para editais da PF e PRF.',
        ]);
    }

    public function test_membro_comum_nao_pode_alterar_nome_da_comunidade(): void
    {
        $dono = Usuario::factory()->create();
        $membro = Usuario::factory()->create();

        $grupo = Grupo::create([
            'proprietario_id' => $dono->id,
            'nome' => 'Grupo Oficial',
            'codigo_convite' => 'TOCA-NOPE1',
            'metrica_padrao' => 'active_days',
        ]);
        $grupo->members()->attach($dono->id, ['papel' => 'proprietario', 'ingressou_em' => now()]);
        $grupo->members()->attach($membro->id, ['papel' => 'membro', 'ingressou_em' => now()]);

        $this->actingAs($membro);

        $teste = Livewire::test(DetalhesGrupo::class, ['group' => $grupo])
            ->set('editGroupName', 'Nome Hackeado')
            ->call('updateGroupSettings');

        $this->assertSame('Apenas o líder proprietário pode alterar as configurações da comunidade.', $teste->get('modalError'));

        $this->assertDatabaseHas('grupos', [
            'id' => $grupo->id,
            'nome' => 'Grupo Oficial',
        ]);
    }

    public function test_sessao_anterior_a_criacao_ou_entrada_no_grupo_nao_aparece_no_mural_nem_aceita_interacao(): void
    {
        $estudanteA = Usuario::factory()->create(['nome' => 'Estudante A']);
        $estudanteB = Usuario::factory()->create(['nome' => 'Estudante B']);

        // Estudante A registrou uma sessão ontem (dia 24)
        $sessaoAntiga = SessaoEstudo::create([
            'usuario_id' => $estudanteA->id,
            'data_estudo' => now()->subDay()->toDateString(),
            'disciplina' => 'Direito Penal Antigo',
            'minutos' => 120,
            'questoes' => 40,
            'compartilhar' => true,
        ]);

        // Estudante B cria um novo grupo hoje (dia 25) e Estudante A ingressa hoje
        $grupoNovo = Grupo::create([
            'proprietario_id' => $estudanteB->id,
            'nome' => 'Grupo Recém Criado',
            'codigo_convite' => 'NOVO-2026',
            'metrica_padrao' => 'dias_ativos',
        ]);
        $grupoNovo->members()->attach([
            $estudanteB->id => ['papel' => 'proprietario', 'ingressou_em' => now()],
            $estudanteA->id => ['papel' => 'membro', 'ingressou_em' => now()],
        ]);

        // No mural do grupo novo, a sessão de ontem NÃO aparece
        $this->actingAs($estudanteB);
        Livewire::test(DetalhesGrupo::class, ['group' => $grupoNovo])
            ->assertDontSee('Direito Penal Antigo')
            ->assertSee('Nenhum estudo publicado neste grupo ainda.');

        // Na API, Estudante B não pode ver nem comentar na sessão anterior à entrada no grupo
        $tokenB = $estudanteB->createToken('api')->plainTextToken;
        $this->withHeader('Authorization', "Bearer {$tokenB}")
            ->getJson("/api/v1/study-sessions/{$sessaoAntiga->id}")
            ->assertForbidden();

        $this->withHeader('Authorization', "Bearer {$tokenB}")
            ->postJson("/api/v1/groups/{$grupoNovo->id}/study-sessions/{$sessaoAntiga->id}/comments", [
                'content' => 'Tentativa em sessão pré-grupo',
            ])
            ->assertForbidden();

        // Estudante A registra uma sessão HOJE (dia 25) — esta deve aparecer e permitir comentários
        $sessaoHoje = SessaoEstudo::create([
            'usuario_id' => $estudanteA->id,
            'data_estudo' => now()->toDateString(),
            'disciplina' => 'Direito Civil Atual',
            'minutos' => 90,
            'questoes' => 25,
            'compartilhar' => true,
        ]);

        Livewire::test(DetalhesGrupo::class, ['group' => $grupoNovo])
            ->assertSee('Direito Civil Atual')
            ->assertDontSee('Direito Penal Antigo');

        $this->withHeader('Authorization', "Bearer {$tokenB}")
            ->postJson("/api/v1/groups/{$grupoNovo->id}/study-sessions/{$sessaoHoje->id}/comments", [
                'content' => 'Bons estudos hoje!',
            ])
            ->assertCreated();
    }
}

