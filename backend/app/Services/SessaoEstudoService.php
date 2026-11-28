<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SessaoEstudo;
use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SessaoEstudoService
{
    public function __construct(
        private readonly PipelineImagemService $pipelineImagem,
    ) {}

    /**
     * Registra uma sessão com validação do teto diário protegida contra cadastros concorrentes.
     */
    public function criarSessao(Usuario $user, array $data, ?UploadedFile $photo = null): SessaoEstudo
    {
        $dataEstudo = $data['data_estudo'] ?? $data['study_date'] ?? now()->toDateString();
        $minutosNovos = (int) ($data['minutos'] ?? $data['minutes'] ?? 0);

        return DB::transaction(function () use ($user, $data, $photo, $dataEstudo, $minutosNovos) {
            // A linha do usuário existe mesmo quando ainda não há sessões naquela data.
            // Todos os cadastros deste usuário aguardam este bloqueio até o commit.
            Usuario::whereKey($user->id)->lockForUpdate()->firstOrFail();

            // Leitura bloqueante para consultar os valores atuais após a espera,
            // inclusive quando a transação usa isolamento REPEATABLE READ.
            $minutosJaRegistrados = (int) SessaoEstudo::where('usuario_id', $user->id)
                ->where('data_estudo', $dataEstudo)
                ->lockForUpdate()
                ->pluck('minutos')
                ->sum();

            if (($minutosJaRegistrados + $minutosNovos) > 1440) {
                throw ValidationException::withMessages([
                    'minutes' => 'A soma dos estudos registrados para esta data não pode ultrapassar 24 horas (1.440 minutos).',
                ]);
            }

            $photoPath = null;

            if ($photo !== null) {
                $photoPath = $this->pipelineImagem->processarEConverterParaWebP($photo, 'sessions', 'local');
            }

            $compartilhar = true;
            if (isset($data['compartilhar'])) {
                $compartilhar = (bool) $data['compartilhar'];
            } elseif (isset($data['share'])) {
                $compartilhar = (bool) $data['share'];
            }

            $session = SessaoEstudo::create([
                'usuario_id' => $user->id,
                'data_estudo' => $dataEstudo,
                'disciplina' => $data['disciplina'] ?? $data['subject'] ?? '',
                'minutos' => $minutosNovos,
                'questoes' => (int) ($data['questoes'] ?? $data['questions'] ?? 0),
                'anotacoes' => $data['anotacoes'] ?? $data['notes'] ?? null,
                'caminho_foto' => $photoPath,
                'compartilhar' => $compartilhar,
            ]);

            return $session->load(['grupos', 'usuario']);
        });
    }

    /**
     * Exclui uma sessão de estudo.
     */
    public function excluirSessao(Usuario $user, SessaoEstudo $session): void
    {
        if ($session->usuario_id !== $user->id) {
            throw new AccessDeniedHttpException("Você não tem permissão para excluir este registro.");
        }

        DB::transaction(function () use ($user, $session) {
            Usuario::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $registro = SessaoEstudo::whereKey($session->id)->lockForUpdate()->first();

            // Uma segunda requisição de exclusão não repete a operação.
            if ($registro === null) {
                return;
            }
            if ($registro->usuario_id !== $user->id) {
                throw new AccessDeniedHttpException('Você não tem permissão para excluir este registro.');
            }

            $caminhoFoto = $registro->caminho_foto;

            if ($registro->delete() !== true) {
                throw new \RuntimeException('Não foi possível excluir a sessão de estudo.');
            }

            if ($caminhoFoto) {
                // Só toca no arquivo depois do commit, inclusive em transações externas.
                // O callback não é persistente: uma interrupção pode deixar um arquivo órfão.
                DB::afterCommit(function () use ($caminhoFoto) {
                    try {
                        $disco = Storage::disk('local');
                        if ($disco->exists($caminhoFoto)
                            && (!$disco->delete($caminhoFoto) || $disco->exists($caminhoFoto))) {
                            Log::warning('Não foi possível remover a fotografia de uma sessão excluída.', [
                                'disco' => 'local', 'caminho' => $caminhoFoto,
                            ]);
                        }
                    } catch (\Throwable $erro) {
                        // A sessão já foi excluída; a falha no disco não desfaz o commit.
                        Log::warning('Falha ao remover a fotografia de uma sessão excluída.', [
                            'disco' => 'local', 'caminho' => $caminhoFoto,
                            'exception' => $erro,
                        ]);
                    }
                });
            }
        });
    }
}
