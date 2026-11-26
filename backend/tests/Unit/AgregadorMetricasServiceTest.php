<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Grupo;
use App\Models\SessaoEstudo;
use App\Models\Usuario;
use App\Services\AgregadorMetricasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgregadorMetricasServiceTest extends TestCase
{
    use RefreshDatabase;

    private AgregadorMetricasService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AgregadorMetricasService::class);
    }

    public function test_datas_distintas_de_estudo_sao_contadas_sem_duplicidade(): void
    {
        $user = Usuario::factory()->create();

        // 3 sessões no dia 2026-09-10 (mesmo dia)
        SessaoEstudo::create([
            'user_id' => $user->id,
            'study_date' => '2026-09-10',
            'subject' => 'Direito Administrativo',
            'minutes' => 60,
            'questions' => 20,
        ]);
        SessaoEstudo::create([
            'user_id' => $user->id,
            'study_date' => '2026-09-10',
            'subject' => 'Direito Tributário',
            'minutes' => 45,
            'questions' => 15,
        ]);
        SessaoEstudo::create([
            'user_id' => $user->id,
            'study_date' => '2026-09-10',
            'subject' => 'Contabilidade',
            'minutes' => 30,
            'questions' => 10,
        ]);

        // 1 sessão no dia 2026-09-11 (dia diferente)
        SessaoEstudo::create([
            'user_id' => $user->id,
            'study_date' => '2026-09-11',
            'subject' => 'Auditoria',
            'minutes' => 90,
            'questions' => 40,
        ]);

        $stats = $this->service->obterEstatisticasUsuario($user->id);

        // Exatamente 2 dias ativos (10 e 11) apesar de 4 sessões
        $this->assertEquals(2, $stats->total_dias_ativos);
        // 60 + 45 + 30 + 90 = 225 minutos
        $this->assertEquals(225, $stats->total_minutos);
        // 20 + 15 + 10 + 40 = 85 questões
        $this->assertEquals(85, $stats->total_questoes);
    }

    public function test_consulta_de_estatisticas_e_consistente_entre_chamadas(): void
    {
        $user = Usuario::factory()->create();

        SessaoEstudo::create([
            'user_id' => $user->id,
            'study_date' => '2026-09-15',
            'subject' => 'Português',
            'minutes' => 120,
            'questions' => 50,
        ]);

        $firstRun = $this->service->obterEstatisticasUsuario($user->id);
        $secondRun = $this->service->obterEstatisticasUsuario($user->id);

        $this->assertEquals($firstRun->total_dias_ativos, $secondRun->total_dias_ativos);
        $this->assertEquals($firstRun->total_minutos, $secondRun->total_minutos);
        $this->assertEquals($firstRun->total_questoes, $secondRun->total_questoes);
    }

    public function test_classificacao_ordena_corretamente_por_dias_ativos(): void
    {
        $owner = Usuario::factory()->create(['name' => 'Aluno A']);
        $studentB = Usuario::factory()->create(['name' => 'Aluno B']);

        $group = Grupo::create([
            'proprietario_id' => $owner->id,
            'nome' => 'Grupo Foco',
            'codigo_convite' => 'RAT-FOCO01',
            'metrica_padrao' => 'dias_ativos',
        ]);
        $group->members()->attach([
            $owner->id => ['papel' => 'proprietario', 'ingressou_em' => '2026-09-01 00:00:00'],
            $studentB->id => ['papel' => 'membro', 'ingressou_em' => '2026-09-01 00:00:00'],
        ]);

        // Aluno A: 2 dias ativos
        $s1 = SessaoEstudo::create([
            'user_id' => $owner->id,
            'study_date' => '2026-09-10',
            'subject' => 'Física',
            'minutes' => 60,
            'questions' => 10,
        ]);
        $s2 = SessaoEstudo::create([
            'user_id' => $owner->id,
            'study_date' => '2026-09-12',
            'subject' => 'Física',
            'minutes' => 60,
            'questions' => 10,
        ]);

        // Aluno B: 1 dia ativo mas com muitas horas
        $s3 = SessaoEstudo::create([
            'user_id' => $studentB->id,
            'study_date' => '2026-09-12',
            'subject' => 'Química',
            'minutes' => 300,
            'questions' => 100,
        ]);

        $rankings = $this->service->obterClassificacaoGrupo($group, 'dias_ativos', 'todos');

        // Aluno A deve ser 1º por ter mais dias ativos (2 vs 1)
        $this->assertEquals($owner->id, $rankings[0]['user_id']);
        $this->assertEquals(1, $rankings[0]['position']);
        $this->assertEquals(2, $rankings[0]['score']);

        // Aluno B deve ser 2º
        $this->assertEquals($studentB->id, $rankings[1]['user_id']);
        $this->assertEquals(2, $rankings[1]['position']);
        $this->assertEquals(1, $rankings[1]['score']);
    }

    public function test_classificacao_ordena_corretamente_pela_formula_de_horas_e_questoes(): void
    {
        $user1 = Usuario::factory()->create(['name' => 'Maratonista']);
        $user2 = Usuario::factory()->create(['name' => 'Resolutor']);

        $group = Grupo::create([
            'proprietario_id' => $user1->id,
            'nome' => 'Concurso Fiscal',
            'codigo_convite' => 'RAT-FISCAL',
            'metrica_padrao' => 'horas_questoes',
            'pontos_hora' => 10.0,
            'pontos_questao' => 1.0,
        ]);
        $group->members()->attach([
            $user1->id => ['papel' => 'proprietario', 'ingressou_em' => '2026-09-01 00:00:00'],
            $user2->id => ['papel' => 'membro', 'ingressou_em' => '2026-09-01 00:00:00'],
        ]);

        // User 1: 5 horas (300 min), 10 questões => (5.0 * 10) + (10 * 1) = 60.0 pts
        $s1 = SessaoEstudo::create([
            'user_id' => $user1->id,
            'study_date' => '2026-09-14',
            'subject' => 'Direito',
            'minutes' => 300,
            'questions' => 10,
        ]);

        // User 2: 2 horas (120 min), 150 questões => (2.0 * 10) + (150 * 1) = 170.0 pts
        $s2 = SessaoEstudo::create([
            'user_id' => $user2->id,
            'study_date' => '2026-09-14',
            'subject' => 'Contabilidade',
            'minutes' => 120,
            'questions' => 150,
        ]);

        $rankings = $this->service->obterClassificacaoGrupo($group, 'horas_questoes', 'todos');

        // User 2 deve ser 1º com 170 pontos
        $this->assertEquals($user2->id, $rankings[0]['user_id']);
        $this->assertEquals(1, $rankings[0]['position']);
        $this->assertEquals(170.0, $rankings[0]['score']);

        // User 1 deve ser 2º com 60 pontos
        $this->assertEquals($user1->id, $rankings[1]['user_id']);
        $this->assertEquals(2, $rankings[1]['position']);
        $this->assertEquals(60.0, $rankings[1]['score']);
    }

    public function test_formula_calcula_horas_fracionadas_e_pesos_personalizados(): void
    {
        $user = Usuario::factory()->create(['name' => 'Estudante Fracionado']);

        $group = Grupo::create([
            'proprietario_id' => $user->id,
            'nome' => 'Grupo Pesos Especiais',
            'codigo_convite' => 'RAT-ESPEC',
            'metrica_padrao' => 'horas_questoes',
            'pontos_hora' => 20.0,
            'pontos_questao' => 0.5,
        ]);
        $group->members()->attach($user->id, ['papel' => 'proprietario', 'ingressou_em' => '2026-09-01 00:00:00']);

        // 90 minutos = 1.5 horas líquidas | 30 questões
        // Pontos: (1.5 * 20.0) + (30 * 0.5) = 30.0 + 15.0 = 45.0
        $sessao = SessaoEstudo::create([
            'user_id' => $user->id,
            'study_date' => '2026-09-14',
            'subject' => 'Matemática',
            'minutes' => 90,
            'questions' => 30,
        ]);

        $rankings = $this->service->obterClassificacaoGrupo($group, 'horas_questoes', 'todos');

        $this->assertEquals(45.0, $rankings[0]['score']);
        $this->assertEquals(30.0, $rankings[0]['points_from_hours']);
        $this->assertEquals(15.0, $rankings[0]['points_from_questions']);
        $this->assertEquals(45.0, $rankings[0]['total_points']);
    }

    public function test_criterio_de_desempate_prioriza_horas_em_caso_de_pontuacao_igual(): void
    {
        $userA = Usuario::factory()->create(['name' => 'Mais Horas']);
        $userB = Usuario::factory()->create(['name' => 'Mais Questoes']);

        $group = Grupo::create([
            'proprietario_id' => $userA->id,
            'nome' => 'Empate Térmico',
            'codigo_convite' => 'RAT-EMPATE',
            'metrica_padrao' => 'horas_questoes',
            'pontos_hora' => 10.0,
            'pontos_questao' => 1.0,
        ]);
        $group->members()->attach([
            $userA->id => ['papel' => 'proprietario', 'ingressou_em' => '2026-09-01 00:00:00'],
            $userB->id => ['papel' => 'membro', 'ingressou_em' => '2026-09-01 00:00:00'],
        ]);

        // User A: 4 horas (240 min) e 20 questões => (4 * 10) + (20 * 1) = 60 pts
        $sA = SessaoEstudo::create([
            'user_id' => $userA->id,
            'study_date' => '2026-09-14',
            'subject' => 'Economia',
            'minutes' => 240,
            'questions' => 20,
        ]);

        // User B: 2 horas (120 min) e 40 questões => (2 * 10) + (40 * 1) = 60 pts
        $sB = SessaoEstudo::create([
            'user_id' => $userB->id,
            'study_date' => '2026-09-14',
            'subject' => 'Direito',
            'minutes' => 120,
            'questions' => 40,
        ]);

        $rankings = $this->service->obterClassificacaoGrupo($group, 'horas_questoes', 'todos');

        // Ambos têm 60 pts, mas User A tem mais horas (4.0 vs 2.0), logo vence o desempate
        $this->assertEquals($userA->id, $rankings[0]['user_id']);
        $this->assertEquals(1, $rankings[0]['position']);
        $this->assertEquals(60.0, $rankings[0]['score']);

        $this->assertEquals($userB->id, $rankings[1]['user_id']);
        $this->assertEquals(2, $rankings[1]['position']);
        $this->assertEquals(60.0, $rankings[1]['score']);
    }

    public function test_desempate_preserva_diferenca_de_um_minuto_nas_duas_modalidades(): void
    {
        $menorTempo = Usuario::factory()->create();
        $maiorTempo = Usuario::factory()->create();
        $grupo = Grupo::create([
            'proprietario_id' => $menorTempo->id,
            'nome' => 'Precisão dos minutos',
            'codigo_convite' => 'MIN-EXATO',
            'metrica_padrao' => 'horas_questoes',
            'pontos_hora' => 0.01,
            'pontos_questao' => 0.01,
        ]);
        $grupo->members()->attach([
            $menorTempo->id => ['papel' => 'proprietario', 'ingressou_em' => '2026-09-01 00:00:00'],
            $maiorTempo->id => ['papel' => 'membro', 'ingressou_em' => '2026-09-01 00:00:00'],
        ]);

        foreach ([[$menorTempo, 60, 2], [$maiorTempo, 30, 1], [$maiorTempo, 31, 1]] as [$usuario, $minutos, $questoes]) {
            SessaoEstudo::create([
                'usuario_id' => $usuario->id,
                'data_estudo' => '2026-09-14',
                'disciplina' => 'Matemática',
                'minutos' => $minutos,
                'questoes' => $questoes,
                'compartilhar' => true,
            ]);
        }

        // Ambos exibem 1,0 h e empatam no critério principal de cada modalidade.
        // O participante com 61 minutos deve vencer mesmo tendo o maior ID.
        foreach (['dias_ativos', 'horas_questoes'] as $modalidade) {
            $ranking = $this->service->obterClassificacaoGrupo($grupo, $modalidade, 'geral');
            $this->assertEquals($ranking[0]['score'], $ranking[1]['score']);
            $this->assertEquals($ranking[0]['hours_in_period'], $ranking[1]['hours_in_period']);
            $this->assertSame($maiorTempo->id, $ranking[0]['user_id']);
            $this->assertSame(61, $ranking[0]['minutes_in_period']);
            $this->assertSame(60, $ranking[1]['minutes_in_period']);
        }
    }

    public function test_sessoes_anteriores_a_entrada_no_grupo_nao_contam_no_ranking_nem_no_mural(): void
    {
        $veterano = Usuario::factory()->create(['name' => 'Veterano']);
        $novatoSemEstudoHoje = Usuario::factory()->create(['name' => 'Novato']);

        $grupo = Grupo::create([
            'proprietario_id' => $novatoSemEstudoHoje->id,
            'nome' => 'Desafio Justo',
            'codigo_convite' => 'JUSTO-01',
            'metrica_padrao' => 'horas_questoes',
            'pontos_hora' => 10.0,
            'pontos_questao' => 1.0,
        ]);

        // Ambos entraram em 2026-11-25
        $grupo->members()->attach([
            $novatoSemEstudoHoje->id => ['papel' => 'proprietario', 'ingressou_em' => '2026-11-25 10:00:00'],
            $veterano->id => ['papel' => 'membro', 'ingressou_em' => '2026-11-25 12:00:00'],
        ]);

        // Sessão antiga de 24/11/2026 (anterior à entrada)
        SessaoEstudo::create([
            'usuario_id' => $veterano->id,
            'data_estudo' => '2026-11-24',
            'disciplina' => 'Estudo Passado',
            'minutos' => 300,
            'questoes' => 100,
            'compartilhar' => true,
        ]);

        // Sessão no mesmo dia da entrada (25/11/2026)
        $sessaoValida = SessaoEstudo::create([
            'usuario_id' => $veterano->id,
            'data_estudo' => '2026-11-25',
            'disciplina' => 'Estudo Atual',
            'minutos' => 60,
            'questoes' => 10,
            'compartilhar' => true,
        ]);

        // No mural do grupo, apenas a sessão de 25/11 deve aparecer
        $sessoesMural = $grupo->sessoes()->get();
        $this->assertCount(1, $sessoesMural);
        $this->assertSame($sessaoValida->id, $sessoesMural->first()->id);

        // No ranking geral, veterano deve contabilizar apenas 60 min (10 + 10 = 20 pts) e novato deve aparecer com 0
        $ranking = $this->service->obterClassificacaoGrupo($grupo, 'horas_questoes', 'geral');
        $this->assertCount(2, $ranking);

        $itemVeterano = $ranking->firstWhere('user_id', $veterano->id);
        $itemNovato = $ranking->firstWhere('user_id', $novatoSemEstudoHoje->id);

        $this->assertEquals(60, $itemVeterano['minutes_in_period']);
        $this->assertEquals(10, $itemVeterano['questions_in_period']);
        $this->assertEquals(20.0, $itemVeterano['score']);

        $this->assertNotNull($itemNovato);
        $this->assertEquals(0, $itemNovato['minutes_in_period']);
        $this->assertEquals(0.0, $itemNovato['score']);
    }
}
