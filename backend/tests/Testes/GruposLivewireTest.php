<?php

declare(strict_types=1);

namespace Tests\Testes;

use App\Livewire\Grupos\DetalhesGrupo;
use App\Livewire\SessoesEstudo\CardSessao;
use App\Models\Grupo;
use App\Models\SessaoEstudo;
use App\Models\Usuario;
use App\Services\GrupoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
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

        try {
            app(GrupoService::class)->atualizarGrupo($dono, $grupo, [
                'pontos_hora' => 20.0,
                'pontos_questao' => 5.0,
            ]);
            $this->fail('O serviço deveria rejeitar alteração de pesos após a criação.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('imutáveis', $e->getMessage());
        }

        try {
            $grupo->update([
                'pontos_hora' => 20.0,
                'pontos_questao' => 5.0,
            ]);
            $this->fail('O modelo Grupo deveria bloquear alteração direta de pesos.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('imutáveis', $e->getMessage());
        }

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

    public function test_sessao_anterior_a_criacao_do_grupo_nao_aparece_no_mural_nem_aceita_interacao(): void
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

        // No mural do grupo novo, a sessão de ontem (anterior à criação do grupo) NÃO aparece
        $this->actingAs($estudanteB);
        Livewire::test(DetalhesGrupo::class, ['group' => $grupoNovo])
            ->assertDontSee('Direito Penal Antigo')
            ->assertSee('Nenhum estudo publicado neste grupo ainda.');

        // Pelas regras de autorização (Policy), Estudante B não pode ver nem interagir na sessão anterior à criação do grupo
        $this->assertFalse(Gate::forUser($estudanteB)->allows('view', $sessaoAntiga));
        $this->assertFalse(Gate::forUser($estudanteB)->allows('interagirNoGrupo', [$sessaoAntiga, $grupoNovo]));

        // Mesmo tentando interagir via componente Livewire no contexto do grupo novo, recebe 403 e não persiste
        Livewire::actingAs($estudanteB)
            ->test(CardSessao::class, ['session' => $sessaoAntiga, 'groupId' => $grupoNovo->id])
            ->set('newCommentText', 'Tentativa em sessão pré-grupo')
            ->call('addComment')
            ->assertForbidden();

        $this->assertDatabaseMissing('comentarios_sessao_estudo', [
            'sessao_estudo_id' => $sessaoAntiga->id,
            'grupo_id' => $grupoNovo->id,
        ]);

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

        $this->assertTrue(Gate::forUser($estudanteB)->allows('interagirNoGrupo', [$sessaoHoje, $grupoNovo]));

        Livewire::actingAs($estudanteB)
            ->test(CardSessao::class, ['session' => $sessaoHoje, 'groupId' => $grupoNovo->id])
            ->set('newCommentText', 'Bons estudos hoje!')
            ->call('addComment');

        $this->assertDatabaseHas('comentarios_sessao_estudo', [
            'sessao_estudo_id' => $sessaoHoje->id,
            'grupo_id' => $grupoNovo->id,
            'usuario_id' => $estudanteB->id,
            'conteudo' => 'Bons estudos hoje!',
        ]);
    }

    public function test_sessao_apos_criacao_do_grupo_aparece_no_mural_e_aceita_interacao_mesmo_entrando_depois(): void
    {
        $estudanteA = Usuario::factory()->create(['nome' => 'Estudante A']);
        $estudanteB = Usuario::factory()->create(['nome' => 'Estudante B']);

        // Grupo fundado há 5 dias
        $dataCriacaoGrupo = now()->subDays(5);
        $grupoExistente = Grupo::create([
            'proprietario_id' => $estudanteB->id,
            'nome' => 'Grupo Já Existente',
            'codigo_convite' => 'EXIS-2026',
            'metrica_padrao' => 'dias_ativos',
        ]);
        $grupoExistente->forceFill([
            'data_criacao' => $dataCriacaoGrupo,
            'data_edicao' => $dataCriacaoGrupo,
        ])->save();

        // Estudante A estudou há 2 dias (DEPOIS da criação do grupo, mas ANTES de ingressar nele)
        $sessaoEntreCriacaoEEntrada = SessaoEstudo::create([
            'usuario_id' => $estudanteA->id,
            'data_estudo' => now()->subDays(2)->toDateString(),
            'disciplina' => 'Direito Constitucional Pós-Criação',
            'minutos' => 90,
            'questoes' => 30,
            'compartilhar' => true,
        ]);

        // Estudante A ingressa hoje no grupo
        $grupoExistente->members()->attach([
            $estudanteB->id => ['papel' => 'proprietario', 'ingressou_em' => $dataCriacaoGrupo],
            $estudanteA->id => ['papel' => 'membro', 'ingressou_em' => now()],
        ]);

        // A sessão feita após a criação do grupo deve aparecer no mural e permitir comentários
        $this->actingAs($estudanteB);
        Livewire::test(DetalhesGrupo::class, ['group' => $grupoExistente])
            ->assertSee('Direito Constitucional Pós-Criação');

        $this->assertTrue(Gate::forUser($estudanteB)->allows('view', $sessaoEntreCriacaoEEntrada));
        $this->assertTrue(Gate::forUser($estudanteB)->allows('interagirNoGrupo', [$sessaoEntreCriacaoEEntrada, $grupoExistente]));

        Livewire::actingAs($estudanteB)
            ->test(CardSessao::class, ['session' => $sessaoEntreCriacaoEEntrada, 'groupId' => $grupoExistente->id])
            ->set('newCommentText', 'Bem-vindo ao grupo!')
            ->call('addComment');

        $this->assertDatabaseHas('comentarios_sessao_estudo', [
            'sessao_estudo_id' => $sessaoEntreCriacaoEEntrada->id,
            'grupo_id' => $grupoExistente->id,
            'usuario_id' => $estudanteB->id,
            'conteudo' => 'Bem-vindo ao grupo!',
        ]);
    }

    public function test_mural_da_comunidade_pagina_sessoes_de_dez_em_dez_e_reseta_ao_trocar_grupo(): void
    {
        $estudante = Usuario::factory()->create(['nome' => 'Maratonista']);
        $dataFundacao = now()->subDays(15);

        $grupoA = Grupo::create([
            'proprietario_id' => $estudante->id,
            'nome' => 'Comunidade Lotada',
            'codigo_convite' => 'TOCA-PAG01',
            'metrica_padrao' => 'dias_ativos',
        ]);
        $grupoA->forceFill(['data_criacao' => $dataFundacao, 'data_edicao' => $dataFundacao])->save();
        $grupoA->members()->attach($estudante->id, ['papel' => 'proprietario', 'ingressou_em' => $dataFundacao]);

        $grupoB = Grupo::create([
            'proprietario_id' => $estudante->id,
            'nome' => 'Comunidade Recente',
            'codigo_convite' => 'TOCA-PAG02',
            'metrica_padrao' => 'dias_ativos',
        ]);
        $grupoB->forceFill(['data_criacao' => now(), 'data_edicao' => now()])->save();
        $grupoB->members()->attach($estudante->id, ['papel' => 'proprietario', 'ingressou_em' => now()]);

        // 12 estudos nos últimos dias (Item 01 mais antigo -> Item 12 mais recente)
        for ($i = 1; $i <= 12; $i++) {
            SessaoEstudo::create([
                'usuario_id' => $estudante->id,
                'data_estudo' => now()->subDays(12 - $i)->toDateString(),
                'disciplina' => sprintf('Estudo Mural %02d', $i),
                'minutos' => 60,
                'questoes' => 15,
                'compartilhar' => true,
            ]);
        }

        $this->actingAs($estudante);

        $componente = Livewire::test(DetalhesGrupo::class, ['group' => $grupoA])
            ->assertSee('Estudo Mural 12')
            ->assertSee('Estudo Mural 03')
            ->assertDontSee('Estudo Mural 02')
            ->assertDontSee('Estudo Mural 01')
            ->assertSee('Anterior')
            ->assertSee('Próxima');

        // Página 2 exibe os 2 estudos restantes do grupo A
        $componente->call('gotoPage', 2)
            ->assertSee('Estudo Mural 02')
            ->assertSee('Estudo Mural 01')
            ->assertDontSee('Estudo Mural 12');

        // Ao trocar para o grupo B (que foi criado hoje e só possui o Estudo Mural 12), reseta para a página 1
        $componente->call('selectGroup', $grupoB->id)
            ->assertSee('Estudo Mural 12')
            ->assertDontSee('Estudo Mural 01');
    }
}

