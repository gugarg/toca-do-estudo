<?php

declare(strict_types=1);

namespace Tests\Testes;

use App\Livewire\SessoesEstudo\ModalCriarSessao;
use App\Livewire\SessoesEstudo\CardSessao;
use App\Models\Comentario;
use App\Models\Grupo;
use App\Models\Reacao;
use App\Models\SessaoEstudo;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SessoesEstudoLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function criarGrupo(Usuario $proprietario, array $atributos = []): Grupo
    {
        return Grupo::create(array_merge([
            'proprietario_id' => $proprietario->id,
            'nome' => 'Grupo de Teste ' . uniqid(),
            'codigo_convite' => 'RAT-' . strtoupper(substr(uniqid(), -6)),
            'metrica_padrao' => 'active_days',
        ], $atributos));
    }

    protected function criarSessao(Usuario $usuario, array $atributos = []): SessaoEstudo
    {
        return SessaoEstudo::create(array_merge([
            'usuario_id' => $usuario->id,
            'data_estudo' => Carbon::today()->toDateString(),
            'disciplina' => 'Algoritmos e Dados',
            'minutos' => 60,
            'questoes' => 10,
            'anotacoes' => 'Revisão geral de grafos',
        ], $atributos));
    }

    public function test_usuario_autenticado_pode_criar_sessao_de_estudo_com_grupos(): void
    {
        $usuario = Usuario::factory()->create();
        $grupo = $this->criarGrupo($usuario);
        $grupo->members()->attach($usuario->id, ['papel' => 'membro']);

        $hoje = Carbon::today()->toDateString();

        Livewire::actingAs($usuario)
            ->test(ModalCriarSessao::class)
            ->set('studyDate', $hoje)
            ->set('subject', 'Cálculo Avançado')
            ->set('minutes', 90)
            ->set('questions', 15)
            ->set('notes', 'Estudo de integrais triplas e superfícies.')
            ->set('selectedGroups', [$grupo->id])
            ->call('save')
            ->assertDispatched('session-created');

        $this->assertDatabaseHas('sessoes_estudo', [
            'usuario_id' => $usuario->id,
            'data_estudo' => $hoje,
            'disciplina' => 'Cálculo Avançado',
            'minutos' => 90,
            'questoes' => 15,
        ]);

        $sessao = SessaoEstudo::where('usuario_id', $usuario->id)->first();
        $this->assertTrue($sessao->groups()->where('grupos.id', $grupo->id)->exists());
    }

    public function test_usuario_nao_pode_criar_sessao_com_data_futura(): void
    {
        $usuario = Usuario::factory()->create();
        $dataFutura = Carbon::tomorrow()->toDateString();

        Livewire::actingAs($usuario)
            ->test(ModalCriarSessao::class)
            ->set('studyDate', $dataFutura)
            ->set('subject', 'Física Moderna')
            ->set('minutes', 60)
            ->call('save')
            ->assertHasErrors(['studyDate']);

        $this->assertDatabaseMissing('sessoes_estudo', [
            'usuario_id' => $usuario->id,
            'disciplina' => 'Física Moderna',
        ]);
    }

    public function test_usuario_nao_pode_criar_sessao_com_mais_de_sete_dias_retroativos(): void
    {
        $usuario = Usuario::factory()->create();
        $dataMuitoAntiga = Carbon::today()->subDays(8)->toDateString();

        Livewire::actingAs($usuario)
            ->test(ModalCriarSessao::class)
            ->set('studyDate', $dataMuitoAntiga)
            ->set('subject', 'Química Orgânica')
            ->set('minutes', 45)
            ->call('save')
            ->assertHasErrors(['studyDate']);
    }

    public function test_usuario_pode_criar_sessao_retroativa_dentro_do_limite_de_sete_dias(): void
    {
        $usuario = Usuario::factory()->create();
        $dataValida = Carbon::today()->subDays(7)->toDateString();

        Livewire::actingAs($usuario)
            ->test(ModalCriarSessao::class)
            ->set('studyDate', $dataValida)
            ->set('subject', 'Química Orgânica')
            ->set('minutes', 45)
            ->call('save')
            ->assertHasNoErrors(['studyDate'])
            ->assertDispatched('session-created');
    }

    public function test_usuario_pode_criar_sessao_com_upload_de_foto_convertida_para_webp(): void
    {
        Storage::fake('public');

        $usuario = Usuario::factory()->create();
        $hoje = Carbon::today()->toDateString();
        $imagem = UploadedFile::fake()->image('caderno_estudos.png', 640, 480);

        Livewire::actingAs($usuario)
            ->test(ModalCriarSessao::class)
            ->set('studyDate', $hoje)
            ->set('subject', 'Estruturas de Dados')
            ->set('minutes', 75)
            ->set('photo', $imagem)
            ->call('save')
            ->assertDispatched('session-created');

        $sessao = SessaoEstudo::where('usuario_id', $usuario->id)->first();
        $this->assertNotNull($sessao);
        $this->assertNotNull($sessao->photo_path);
        $this->assertStringEndsWith('.webp', $sessao->photo_path);

        Storage::disk('public')->assertExists($sessao->photo_path);
    }

    public function test_card_de_sessao_pode_alternar_reacao_de_incentivo_kudos(): void
    {
        $usuario = Usuario::factory()->create();
        $autor = Usuario::factory()->create();
        $grupo = $this->criarGrupo($autor);
        $grupo->members()->attach([
            $autor->id => ['papel' => 'proprietario', 'ingressou_em' => now()],
            $usuario->id => ['papel' => 'membro', 'ingressou_em' => now()],
        ]);
        $sessao = $this->criarSessao($autor);

        $teste = Livewire::actingAs($usuario)
            ->test(CardSessao::class, ['session' => $sessao, 'groupId' => $grupo->id]);

        // 1. Adicionar reação fire
        $teste->call('toggleReaction', 'fire')
            ->assertSet('userReaction', 'fire')
            ->assertSet('reactionsCount', 1);

        $this->assertDatabaseHas('reacoes', [
            'sessao_estudo_id' => $sessao->id,
            'grupo_id' => $grupo->id,
            'usuario_id' => $usuario->id,
            'tipo_reacao' => 'fire',
        ]);

        // 2. Clicar novamente na mesma reação deve removê-la
        $teste->call('toggleReaction', 'fire')
            ->assertSet('userReaction', null)
            ->assertSet('reactionsCount', 0);

        $this->assertDatabaseMissing('reacoes', [
            'sessao_estudo_id' => $sessao->id,
            'grupo_id' => $grupo->id,
            'usuario_id' => $usuario->id,
        ]);
    }

    public function test_card_de_sessao_pode_trocar_tipo_de_reacao(): void
    {
        $usuario = Usuario::factory()->create();
        $autor = Usuario::factory()->create();
        $grupo = $this->criarGrupo($autor);
        $grupo->members()->attach([
            $autor->id => ['papel' => 'proprietario', 'ingressou_em' => now()],
            $usuario->id => ['papel' => 'membro', 'ingressou_em' => now()],
        ]);
        $sessao = $this->criarSessao($autor);

        Livewire::actingAs($usuario)
            ->test(CardSessao::class, ['session' => $sessao, 'groupId' => $grupo->id])
            ->call('toggleReaction', 'fire')
            ->assertSet('userReaction', 'fire')
            ->call('toggleReaction', 'brain')
            ->assertSet('userReaction', 'brain')
            ->assertSet('reactionsCount', 1);

        $this->assertDatabaseHas('reacoes', [
            'sessao_estudo_id' => $sessao->id,
            'grupo_id' => $grupo->id,
            'usuario_id' => $usuario->id,
            'tipo_reacao' => 'brain',
        ]);
    }

    public function test_usuario_pode_adicionar_editar_e_excluir_comentario(): void
    {
        $usuario = Usuario::factory()->create();
        $grupo = $this->criarGrupo($usuario);
        $grupo->members()->attach($usuario->id, ['papel' => 'proprietario', 'ingressou_em' => now()]);
        $sessao = $this->criarSessao($usuario);

        $teste = Livewire::actingAs($usuario)
            ->test(CardSessao::class, ['session' => $sessao, 'groupId' => $grupo->id])
            ->set('newCommentText', 'Parabéns pelo ritmo de estudo!')
            ->call('addComment')
            ->assertSet('commentsCount', 1);

        $comentario = Comentario::where('sessao_estudo_id', $sessao->id)
            ->where('grupo_id', $grupo->id)
            ->first();
        $this->assertNotNull($comentario);
        $this->assertSame('Parabéns pelo ritmo de estudo!', $comentario->content);

        // Edição
        $teste->call('startEditComment', $comentario->id)
            ->assertSet('editingCommentId', $comentario->id)
            ->set('editingCommentText', 'Parabéns pelo ritmo e consistência!')
            ->call('saveEditComment');

        $this->assertSame('Parabéns pelo ritmo e consistência!', $comentario->fresh()->content);

        // Exclusão
        $teste->call('deleteComment', $comentario->id)
            ->assertSet('commentsCount', 0);

        $this->assertDatabaseMissing('comentarios_sessao_estudo', [
            'id' => $comentario->id,
        ]);
    }

    public function test_usuario_nao_autorizado_nao_pode_editar_ou_excluir_comentario_de_outro_aluno(): void
    {
        $autor = Usuario::factory()->create();
        $invasor = Usuario::factory()->create();
        $grupo = $this->criarGrupo($autor);
        $grupo->members()->attach([
            $autor->id => ['papel' => 'proprietario', 'ingressou_em' => now()],
            $invasor->id => ['papel' => 'membro', 'ingressou_em' => now()],
        ]);
        $sessao = $this->criarSessao($autor);

        $comentario = Comentario::create([
            'sessao_estudo_id' => $sessao->id,
            'grupo_id' => $grupo->id,
            'usuario_id' => $autor->id,
            'conteudo' => 'Comentário original do autor',
        ]);

        // Tentativa de exclusão por usuário não autorizado
        Livewire::actingAs($invasor)
            ->test(CardSessao::class, ['session' => $sessao, 'groupId' => $grupo->id])
            ->call('deleteComment', $comentario->id)
            ->assertForbidden();

        // O comentário deve permanecer intacto
        $this->assertDatabaseHas('comentarios_sessao_estudo', [
            'id' => $comentario->id,
            'conteudo' => 'Comentário original do autor',
        ]);
    }

    public function test_autor_da_sessao_pode_excluir_o_proprio_estudo(): void
    {
        $autor = Usuario::factory()->create();
        $sessao = $this->criarSessao($autor);

        Livewire::actingAs($autor)
            ->test(CardSessao::class, ['session' => $sessao])
            ->call('deleteSession')
            ->assertDispatched('session-deleted');

        $this->assertDatabaseMissing('sessoes_estudo', [
            'id' => $sessao->id,
        ]);
    }

    public function test_estudante_pode_registrar_duas_sessoes_no_mesmo_dia_e_computar_apenas_um_dia_ativo(): void
    {
        $usuario = Usuario::factory()->create();
        $hoje = Carbon::today()->toDateString();

        $this->criarSessao($usuario, ['data_estudo' => $hoje, 'minutos' => 60]);
        $this->criarSessao($usuario, ['data_estudo' => $hoje, 'minutos' => 45]);

        $stats = app(\App\Services\AgregadorMetricasService::class)->obterEstatisticasUsuario($usuario->id);
        $this->assertEquals(1, $stats->total_dias_ativos);
        $this->assertEquals(105, $stats->total_minutos);
    }

    public function test_exclusao_de_sessao_remove_fisicamente_a_foto_do_disco_e_apaga_em_cascata(): void
    {
        Storage::fake('public');

        $autor = Usuario::factory()->create();
        $grupo = $this->criarGrupo($autor);
        $grupo->members()->attach($autor->id, ['papel' => 'proprietario', 'ingressou_em' => now()]);
        $caminhoFoto = 'sessoes/fotos/comprovacao_teste.webp';
        Storage::disk('public')->put($caminhoFoto, 'conteudo-fake-webp');

        $sessao = $this->criarSessao($autor, ['photo_path' => $caminhoFoto]);
        Comentario::create([
            'sessao_estudo_id' => $sessao->id,
            'grupo_id' => $grupo->id,
            'usuario_id' => $autor->id,
            'conteudo' => 'Comentario',
        ]);

        Livewire::actingAs($autor)
            ->test(CardSessao::class, ['session' => $sessao, 'groupId' => $grupo->id])
            ->call('deleteSession')
            ->assertDispatched('session-deleted');

        $this->assertDatabaseMissing('sessoes_estudo', ['id' => $sessao->id]);
        $this->assertDatabaseMissing('comentarios_sessao_estudo', ['sessao_estudo_id' => $sessao->id]);
        Storage::disk('public')->assertMissing($caminhoFoto);
    }

    public function test_servico_aceita_exatamente_24_horas_e_rejeita_acrescimo(): void
    {
        $usuario = Usuario::factory()->create();
        $hoje = Carbon::today()->toDateString();
        $this->criarSessao($usuario, ['data_estudo' => $hoje, 'minutos' => 600]);
        $this->criarSessao($usuario, ['data_estudo' => $hoje, 'minutos' => 600]);
        $servico = app(\App\Services\SessaoEstudoService::class);
        $dados = ['data_estudo' => $hoje, 'disciplina' => 'Algoritmos', 'minutos' => 240];

        $servico->criarSessao($usuario, $dados);
        $this->assertEquals(1440, $usuario->sessoesEstudo()->where('data_estudo', $hoje)->sum('minutos'));

        try {
            $servico->criarSessao($usuario, array_replace($dados, ['minutos' => 5]));
            $this->fail('O serviço deveria rejeitar o acréscimo acima de 1.440 minutos.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('minutes', $e->errors());
        }

        $registros = SessaoEstudo::where('usuario_id', $usuario->id)->where('data_estudo', $hoje)->get();
        $this->assertCount(3, $registros);
        $this->assertEquals(1440, $registros->sum('minutos'));
    }

    public function test_usuario_nao_pode_ultrapassar_limite_de_24_horas_estudadas_no_mesmo_dia(): void
    {
        $usuario = Usuario::factory()->create();
        $hoje = Carbon::today()->toDateString();

        // Cadastra duas sessões de 10 horas (600 min cada = 20h / 1.200 min) no mesmo dia
        $this->criarSessao($usuario, ['data_estudo' => $hoje, 'minutos' => 600]);
        $this->criarSessao($usuario, ['data_estudo' => $hoje, 'minutos' => 600]);

        // Tenta registrar uma terceira sessão de 10 horas (totalizaria 30h no mesmo dia civil)
        Livewire::actingAs($usuario)
            ->test(ModalCriarSessao::class)
            ->set('studyDate', $hoje)
            ->set('subject', 'Direito Constitucional')
            ->set('minutes', 600)
            ->call('save')
            ->assertHasErrors(['minutes']);

        // Garante que a terceira sessão foi rejeitada e a soma permanece em 1.200 minutos
        $this->assertEquals(1200, SessaoEstudo::where('usuario_id', $usuario->id)->sum('minutos'));
        $this->assertCount(2, SessaoEstudo::where('usuario_id', $usuario->id)->get());
    }

    public function test_comentarios_e_reacoes_ficam_isolados_por_comunidade(): void
    {
        $autor = Usuario::factory()->create(['nome' => 'Matheus']);
        $colega = Usuario::factory()->create(['nome' => 'Camila']);

        $grupoA = $this->criarGrupo($autor, ['nome' => 'Fiscais 2026']);
        $grupoB = $this->criarGrupo($colega, ['nome' => 'Engenharia & Café']);

        $grupoA->members()->attach([
            $autor->id => ['papel' => 'proprietario', 'ingressou_em' => now()],
            $colega->id => ['papel' => 'membro', 'ingressou_em' => now()],
        ]);
        $grupoB->members()->attach([
            $colega->id => ['papel' => 'proprietario', 'ingressou_em' => now()],
            $autor->id => ['papel' => 'membro', 'ingressou_em' => now()],
        ]);

        $sessao = $this->criarSessao($autor, ['compartilhar' => true]);

        // Colega interage no Grupo A
        Livewire::actingAs($colega)
            ->test(CardSessao::class, ['session' => $sessao, 'groupId' => $grupoA->id])
            ->call('toggleReaction', 'fire')
            ->set('newCommentText', 'Comentário exclusivo do mural de Fiscais!')
            ->call('addComment');

        // Mesmo colega interage de forma independente na mesma sessão no Grupo B
        Livewire::actingAs($colega)
            ->test(CardSessao::class, ['session' => $sessao, 'groupId' => $grupoB->id])
            ->call('toggleReaction', 'brain')
            ->set('newCommentText', 'Comentário exclusivo do mural de Engenharia!')
            ->call('addComment');

        // Verifica isolamento visual no card do Grupo A
        $cardGrupoA = Livewire::actingAs($autor)
            ->test(CardSessao::class, ['session' => $sessao, 'groupId' => $grupoA->id])
            ->assertSet('reactionsCount', 1)
            ->assertSet('commentsCount', 1)
            ->call('toggleComments')
            ->assertSee('Comentário exclusivo do mural de Fiscais!')
            ->assertDontSee('Comentário exclusivo do mural de Engenharia!');

        $this->assertArrayHasKey('fire', $cardGrupoA->get('reactionsBreakdown'));
        $this->assertArrayNotHasKey('brain', $cardGrupoA->get('reactionsBreakdown'));

        // Verifica isolamento visual no card do Grupo B
        $cardGrupoB = Livewire::actingAs($autor)
            ->test(CardSessao::class, ['session' => $sessao, 'groupId' => $grupoB->id])
            ->assertSet('reactionsCount', 1)
            ->assertSet('commentsCount', 1)
            ->call('toggleComments')
            ->assertSee('Comentário exclusivo do mural de Engenharia!')
            ->assertDontSee('Comentário exclusivo do mural de Fiscais!');

        $this->assertArrayHasKey('brain', $cardGrupoB->get('reactionsBreakdown'));
        $this->assertArrayNotHasKey('fire', $cardGrupoB->get('reactionsBreakdown'));

        // No painel pessoal (sem groupId), consolida as interações com identificação da comunidade
        Livewire::actingAs($autor)
            ->test(CardSessao::class, ['session' => $sessao])
            ->assertSet('reactionsCount', 2)
            ->assertSet('commentsCount', 2)
            ->call('toggleComments')
            ->assertSee('Fiscais 2026')
            ->assertSee('Engenharia & Café')
            ->set('newCommentText', 'Tentativa fora de grupo')
            ->call('addComment')
            ->assertSet('commentsCount', 2)
            ->assertSet('commentError', 'Comentários devem ser publicados dentro do mural de uma comunidade.');
    }

    public function test_api_exige_grupo_valido_e_vincula_reacoes_e_comentarios_a_comunidade(): void
    {
        $autor = Usuario::factory()->create();
        $colega = Usuario::factory()->create();
        $estranho = Usuario::factory()->create();

        $grupoA = $this->criarGrupo($autor, ['nome' => 'Comunidade Alfa']);
        $grupoB = $this->criarGrupo($autor, ['nome' => 'Comunidade Beta']);

        $grupoA->members()->attach([
            $autor->id => ['papel' => 'proprietario', 'ingressou_em' => now()],
            $colega->id => ['papel' => 'membro', 'ingressou_em' => now()],
        ]);
        $grupoB->members()->attach([
            $autor->id => ['papel' => 'proprietario', 'ingressou_em' => now()],
        ]);

        $sessao = $this->criarSessao($autor, ['compartilhar' => true]);
        $tokenColega = $colega->createToken('api')->plainTextToken;
        $tokenEstranho = $estranho->createToken('api')->plainTextToken;

        // 1. Endpoint genérico sem group_id deve ser rejeitado (422)
        $this->withHeader('Authorization', "Bearer {$tokenColega}")
            ->postJson("/api/v1/study-sessions/{$sessao->id}/comments", [
                'content' => 'Sem grupo',
            ])
            ->assertStatus(422);

        // 2. Colega não pode comentar no Grupo B (do qual não participa)
        $this->withHeader('Authorization', "Bearer {$tokenColega}")
            ->postJson("/api/v1/groups/{$grupoB->id}/study-sessions/{$sessao->id}/comments", [
                'content' => 'Tentativa em grupo alheio',
            ])
            ->assertForbidden();

        // 3. Estranho fora de todos os grupos não pode reagir no Grupo A
        auth()->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$tokenEstranho}")
            ->postJson("/api/v1/groups/{$grupoA->id}/study-sessions/{$sessao->id}/reactions", [
                'reaction_type' => 'fire',
            ])
            ->assertForbidden();

        // 4. Colega reage e comenta corretamente no Grupo A
        auth()->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$tokenColega}")
            ->postJson("/api/v1/groups/{$grupoA->id}/study-sessions/{$sessao->id}/reactions", [
                'reaction_type' => 'applause',
            ])
            ->assertCreated()
            ->assertJsonPath('data.group_id', $grupoA->id);

        $this->withHeader('Authorization', "Bearer {$tokenColega}")
            ->postJson("/api/v1/groups/{$grupoA->id}/study-sessions/{$sessao->id}/comments", [
                'content' => 'Excelente avanço no Grupo Alfa!',
            ])
            ->assertCreated()
            ->assertJsonPath('data.group_id', $grupoA->id);

        // 5. Feed da API do Grupo B não deve retornar reações nem comentários do Grupo A
        auth()->forgetGuards();
        $tokenAutor = $autor->createToken('api')->plainTextToken;
        $this->withHeader('Authorization', "Bearer {$tokenAutor}")
            ->getJson("/api/v1/groups/{$grupoB->id}/feed")
            ->assertOk()
            ->assertJsonPath('data.0.reactions_count', 0)
            ->assertJsonPath('data.0.comments_count', 0);

        $this->withHeader('Authorization', "Bearer {$tokenAutor}")
            ->getJson("/api/v1/groups/{$grupoA->id}/feed")
            ->assertOk()
            ->assertJsonPath('data.0.reactions_count', 1)
            ->assertJsonPath('data.0.comments_count', 1);
    }
}

