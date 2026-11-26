<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AutenticacaoController;
use App\Http\Controllers\Api\ComentarioController;
use App\Http\Controllers\Api\PainelController;
use App\Http\Controllers\Api\GrupoController;
use App\Http\Controllers\Api\SessaoEstudoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Toca do Estudo RESTful API Routes (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('throttle:60,1')->group(function () {

    // =========================================================================
    // 1. AUTENTICAÇÃO PÚBLICA (Com proteção contra força bruta - OWASP A07)
    // =========================================================================

    // Rota responsável por cadastrar um novo usuário estudante
    // Rota responsável por autenticar e gerar o token de acesso do usuário
    // Rota responsável por enviar as instruções de recuperação de senha por e-mail
    // Rota responsável por redefinir a senha utilizando o token de recuperação
    // Rota responsável por validar se um token de recuperação de senha continua válido
    Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
        Route::post('/register', [AutenticacaoController::class, 'register']);
        Route::post('/login', [AutenticacaoController::class, 'login']);
        Route::post('/forgot-password', [AutenticacaoController::class, 'forgotPassword']);
        Route::post('/reset-password', [AutenticacaoController::class, 'resetPassword']);
        Route::post('/validate-reset-token', [AutenticacaoController::class, 'validateResetToken']);
    });

    // Aliases em Português para Autenticação
    Route::prefix('autenticacao')->middleware('throttle:10,1')->group(function () {
        Route::post('/cadastrar', [AutenticacaoController::class, 'register']);
        Route::post('/entrar', [AutenticacaoController::class, 'login']);
        Route::post('/recuperar-senha', [AutenticacaoController::class, 'forgotPassword']);
        Route::post('/redefinir-senha', [AutenticacaoController::class, 'resetPassword']);
        Route::post('/validar-token', [AutenticacaoController::class, 'validateResetToken']);
    });

    // =========================================================================
    // 2. ROTAS PROTEGIDAS POR TOKEN SANCTUM
    // =========================================================================
    Route::middleware('auth:sanctum')->group(function () {

        // Usuário Autenticado e Sessão
        // Rota responsável por retornar os dados do usuário autenticado
        // Rota responsável por revogar o token atual e encerrar a sessão
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AutenticacaoController::class, 'me']);
            Route::post('/logout', [AutenticacaoController::class, 'logout']);
        });

        Route::prefix('autenticacao')->group(function () {
            Route::get('/meu-usuario', [AutenticacaoController::class, 'me']);
            Route::post('/sair', [AutenticacaoController::class, 'logout']);
        });

        // Rota responsável por atualizar os dados cadastrais e foto de perfil do estudante
        Route::patch('/users/profile', [AutenticacaoController::class, 'updateProfile']);
        Route::patch('/usuarios/perfil', [AutenticacaoController::class, 'updateProfile']);

        // Painel e Métricas Consolidadas
        // Rota responsável por retornar o resumo analítico e métricas do painel
        Route::get('/dashboard/overview', [PainelController::class, 'overview']);
        Route::get('/painel/visao-geral', [PainelController::class, 'overview']);

        // Sessões de Estudo, Mídia WebP e Reações Sociais (Kudos)
        // Rota responsável por listar, criar, visualizar e excluir sessões de estudo
        Route::apiResource('study-sessions', SessaoEstudoController::class)->only(['index', 'store', 'show', 'destroy']);
        Route::post('/study-sessions/{study_session}/kudos', [SessaoEstudoController::class, 'addKudo']);
        Route::delete('/study-sessions/{study_session}/kudos', [SessaoEstudoController::class, 'removeKudo']);
        Route::post('/study-sessions/{study_session}/reactions', [SessaoEstudoController::class, 'addKudo']);
        Route::delete('/study-sessions/{study_session}/reactions', [SessaoEstudoController::class, 'removeKudo']);

        // Aliases em Português para Sessões de Estudo
        Route::apiResource('sessoes-estudo', SessaoEstudoController::class)->only(['index', 'store', 'show', 'destroy'])->parameters(['sessoes-estudo' => 'study_session']);
        Route::post('/sessoes-estudo/{study_session}/reacoes', [SessaoEstudoController::class, 'addKudo']);
        Route::delete('/sessoes-estudo/{study_session}/reacoes', [SessaoEstudoController::class, 'removeKudo']);

        // Comentários nas Publicações de Estudo (por comunidade ou com group_id)
        Route::get('/groups/{group}/study-sessions/{study_session}/comments', [ComentarioController::class, 'indexInGroup']);
        Route::post('/groups/{group}/study-sessions/{study_session}/comments', [ComentarioController::class, 'storeInGroup']);
        Route::post('/groups/{group}/study-sessions/{study_session}/reactions', [SessaoEstudoController::class, 'addKudoInGroup']);
        Route::delete('/groups/{group}/study-sessions/{study_session}/reactions', [SessaoEstudoController::class, 'removeKudoInGroup']);
        Route::post('/groups/{group}/study-sessions/{study_session}/kudos', [SessaoEstudoController::class, 'addKudoInGroup']);
        Route::delete('/groups/{group}/study-sessions/{study_session}/kudos', [SessaoEstudoController::class, 'removeKudoInGroup']);

        Route::get('/grupos/{group}/sessoes-estudo/{study_session}/comentarios', [ComentarioController::class, 'indexInGroup']);
        Route::post('/grupos/{group}/sessoes-estudo/{study_session}/comentarios', [ComentarioController::class, 'storeInGroup']);
        Route::post('/grupos/{group}/sessoes-estudo/{study_session}/reacoes', [SessaoEstudoController::class, 'addKudoInGroup']);
        Route::delete('/grupos/{group}/sessoes-estudo/{study_session}/reacoes', [SessaoEstudoController::class, 'removeKudoInGroup']);

        // Rota responsável por listar comentários de uma sessão de estudo
        Route::get('/study-sessions/{study_session}/comments', [ComentarioController::class, 'index']);
        // Rota responsável por adicionar um novo comentário em uma sessão de estudo
        Route::post('/study-sessions/{study_session}/comments', [ComentarioController::class, 'store']);
        // Rota responsável por atualizar o conteúdo de um comentário
        Route::patch('/comments/{comment}', [ComentarioController::class, 'update']);
        // Rota responsável por excluir um comentário existente
        Route::delete('/comments/{comment}', [ComentarioController::class, 'destroy']);

        // Aliases em Português para Comentários
        Route::get('/sessoes-estudo/{study_session}/comentarios', [ComentarioController::class, 'index']);
        Route::post('/sessoes-estudo/{study_session}/comentarios', [ComentarioController::class, 'store']);
        Route::patch('/comentarios/{comment}', [ComentarioController::class, 'update']);
        Route::delete('/comentarios/{comment}', [ComentarioController::class, 'destroy']);

        // Grupos e Comunidades de Estudo
        // Rota responsável por entrar em um grupo através de código de convite
        Route::post('/groups/join', [GrupoController::class, 'join']);
        // Rota responsável por listar, criar, visualizar e atualizar grupos de estudo
        Route::apiResource('groups', GrupoController::class)->only(['index', 'store', 'show', 'update']);
        // Rota responsável por sair de uma comunidade de estudo
        Route::post('/groups/{group}/leave', [GrupoController::class, 'leave']);
        // Rota responsável por obter o mural de publicações do grupo
        Route::get('/groups/{group}/feed', [GrupoController::class, 'feed']);
        // Rota responsável por obter a classificação e ranking dos membros do grupo
        Route::get('/groups/{group}/leaderboard', [GrupoController::class, 'leaderboard']);

        // Aliases em Português para Comunidades
        Route::post('/grupos/ingressar', [GrupoController::class, 'join']);
        Route::apiResource('grupos', GrupoController::class)->only(['index', 'store', 'show', 'update'])->parameters(['grupos' => 'group']);
        Route::post('/grupos/{group}/sair', [GrupoController::class, 'leave']);
        Route::get('/grupos/{group}/mural', [GrupoController::class, 'feed']);
        Route::get('/grupos/{group}/classificacao', [GrupoController::class, 'leaderboard']);
    });
});
