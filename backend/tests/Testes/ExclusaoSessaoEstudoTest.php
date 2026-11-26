<?php

declare(strict_types=1);

namespace Tests\Testes;

use App\Models\Comentario;
use App\Models\Grupo;
use App\Models\Reacao;
use App\Models\SessaoEstudo;
use App\Models\Usuario;
use App\Services\AgregadorMetricasService;
use App\Services\SessaoEstudoService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

class ExclusaoSessaoEstudoTest extends TestCase
{
    // Commits reais são necessários para verificar os callbacks afterCommit.
    use DatabaseTruncation;

    private function criarSessao(): SessaoEstudo
    {
        Storage::fake('public');
        $usuario = Usuario::factory()->create();
        $caminho = 'sessions/exclusao-teste.webp';
        Storage::disk('public')->put($caminho, 'foto-teste');

        return SessaoEstudo::create([
            'usuario_id' => $usuario->id,
            'data_estudo' => now()->toDateString(),
            'disciplina' => 'Algoritmos',
            'minutos' => 60,
            'questoes' => 10,
            'caminho_foto' => $caminho,
            'compartilhar' => true,
        ]);
    }

    public function test_exclusao_remove_foto_dependentes_e_atualiza_metricas(): void
    {
        $sessao = $this->criarSessao();
        $autor = $sessao->usuario;
        $grupo = Grupo::create([
            'proprietario_id' => $autor->id,
            'nome' => 'Grupo Exclusão',
            'codigo_convite' => 'EXC-0001',
            'metrica_padrao' => 'dias_ativos',
        ]);
        $grupo->membros()->attach($autor->id, ['papel' => 'proprietario', 'ingressou_em' => now()]);

        Comentario::create([
            'sessao_estudo_id' => $sessao->id,
            'grupo_id' => $grupo->id,
            'usuario_id' => $autor->id,
            'conteudo' => 'Teste',
        ]);
        Reacao::create([
            'sessao_estudo_id' => $sessao->id,
            'grupo_id' => $grupo->id,
            'usuario_id' => $autor->id,
            'tipo_reacao' => 'fire',
        ]);

        app(SessaoEstudoService::class)->excluirSessao($autor, $sessao);

        $this->assertDatabaseMissing('sessoes_estudo', ['id' => $sessao->id]);
        $this->assertDatabaseMissing('comentarios_sessao_estudo', ['sessao_estudo_id' => $sessao->id]);
        $this->assertDatabaseMissing('reacoes', ['sessao_estudo_id' => $sessao->id]);
        Storage::disk('public')->assertMissing($sessao->caminho_foto);
        $metricas = app(AgregadorMetricasService::class)->obterEstatisticasUsuario($autor->id);
        $this->assertSame(0, $metricas->total_minutos);
        $this->assertSame(0, $metricas->total_questoes);
        $this->assertSame(0, $metricas->total_dias_ativos);
    }

    public function test_falha_na_transacao_preserva_sessao_e_foto(): void
    {
        $sessao = $this->criarSessao();
        SessaoEstudo::deleted(function () {
            throw new RuntimeException('Falha simulada antes do commit');
        });

        try {
            app(SessaoEstudoService::class)->excluirSessao($sessao->usuario, $sessao);
            $this->fail('A transação deveria falhar.');
        } catch (RuntimeException $erro) {
            $this->assertSame('Falha simulada antes do commit', $erro->getMessage());
        }

        $this->assertDatabaseHas('sessoes_estudo', ['id' => $sessao->id]);
        Storage::disk('public')->assertExists($sessao->caminho_foto);
    }

    public function test_foto_so_e_removida_apos_commit_da_transacao_externa(): void
    {
        $sessao = $this->criarSessao();
        DB::beginTransaction();
        try {
            app(SessaoEstudoService::class)->excluirSessao($sessao->usuario, $sessao);
            Storage::disk('public')->assertExists($sessao->caminho_foto);
            DB::commit();
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
        Storage::disk('public')->assertMissing($sessao->caminho_foto);
    }

    public function test_rollback_externo_descarta_remocao_da_fotografia(): void
    {
        $sessao = $this->criarSessao();
        DB::beginTransaction();
        try {
            app(SessaoEstudoService::class)->excluirSessao($sessao->usuario, $sessao);
        } finally {
            DB::rollBack();
        }
        $this->assertDatabaseHas('sessoes_estudo', ['id' => $sessao->id]);
        Storage::disk('public')->assertExists($sessao->caminho_foto);
    }

    public function test_falha_de_armazenamento_preserva_arquivo_e_registra_aviso(): void
    {
        $sessao = $this->criarSessao();
        $discoReal = Storage::disk('public');
        Log::spy();
        $discoComFalha = Mockery::mock();
        $discoComFalha->shouldReceive('exists')->with($sessao->caminho_foto)->once()->andReturn(true);
        $discoComFalha->shouldReceive('delete')->with($sessao->caminho_foto)->once()->andReturn(false);
        Storage::set('public', $discoComFalha);

        app(SessaoEstudoService::class)->excluirSessao($sessao->usuario, $sessao);

        $this->assertDatabaseMissing('sessoes_estudo', ['id' => $sessao->id]);
        $discoReal->assertExists($sessao->caminho_foto);

        Log::shouldHaveReceived('warning')->once();

    }

    public function test_excecao_no_armazenamento_nao_desfaz_exclusao_da_sessao(): void
    {
        $sessao = $this->criarSessao();
        $discoReal = Storage::disk('public');
        Log::spy();
        $discoComFalha = Mockery::mock();
        $discoComFalha->shouldReceive('exists')->once()->andReturn(true);
        $discoComFalha->shouldReceive('delete')->once()->andThrow(new RuntimeException('Armazenamento indisponível'));
        Storage::set('public', $discoComFalha);

        app(SessaoEstudoService::class)->excluirSessao($sessao->usuario, $sessao);

        $this->assertDatabaseMissing('sessoes_estudo', ['id' => $sessao->id]);
        $discoReal->assertExists($sessao->caminho_foto);
    }

    public function test_foto_ja_ausente_nao_impede_exclusao_da_sessao(): void
    {
        $sessao = $this->criarSessao();
        Storage::disk('public')->delete($sessao->caminho_foto);
        app(SessaoEstudoService::class)->excluirSessao($sessao->usuario, $sessao);
        $this->assertDatabaseMissing('sessoes_estudo', ['id' => $sessao->id]);
    }

    public function test_outro_usuario_nao_pode_excluir_sessao_nem_fotografia(): void
    {
        $sessao = $this->criarSessao();
        $outro = Usuario::factory()->create();
        try {
            app(SessaoEstudoService::class)->excluirSessao($outro, $sessao);
            $this->fail('A exclusão deveria ser recusada.');
        } catch (AccessDeniedHttpException $erro) {
            $this->assertSame(403, $erro->getStatusCode());
        }
        $this->assertDatabaseHas('sessoes_estudo', ['id' => $sessao->id]);
        Storage::disk('public')->assertExists($sessao->caminho_foto);
    }
}
