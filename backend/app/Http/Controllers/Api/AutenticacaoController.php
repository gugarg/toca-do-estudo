<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AutenticarRequest;
use App\Http\Requests\CadastroRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\Usuario;
use App\Notifications\RecuperacaoSenhaNotification;
use App\Services\PipelineImagemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AutenticacaoController extends Controller
{
    public function __construct(
        private readonly PipelineImagemService $pipelineImagem,
    ) {}

    public function register(CadastroRequest $request): JsonResponse
    {
        $dados = $request->validated();
        $dados['password'] = Hash::make($dados['password']);

        $usuario = Usuario::create($dados);

        $token = $usuario->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Conta de estudante criada com sucesso.',
            'data' => [
                'user' => new UsuarioResource($usuario),
                'token' => $token,
            ],
        ], 201);
    }

    public function login(AutenticarRequest $request): JsonResponse
    {
        $credenciais = $request->validated();
        $usuario = Usuario::where('email', $credenciais['email'])->first();

        if (!$usuario || !Hash::check($credenciais['password'], $usuario->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais informadas estão incorretas.'],
            ]);
        }

        $token = $usuario->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login realizado com sucesso.',
            'data' => [
                'user' => new UsuarioResource($usuario),
                'token' => $token,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UsuarioResource($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sessão encerrada com sucesso.',
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $dadosValidados = $request->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:120'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('usuarios', 'email')->ignore($usuario->id)],
            'bio' => ['nullable', 'string', 'max:250'],
            'avatar' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'email.unique' => 'Este endereço de e-mail já está sendo utilizado por outra conta.',
            'email.email' => 'Informe um formato de e-mail válido.',
        ]);

        if ($request->hasFile('avatar')) {
            $dadosValidados['avatar_url'] = $this->pipelineImagem->processarEConverterParaWebP(
                $request->file('avatar'),
                'avatars'
            );
        }

        $usuario->update($dadosValidados);

        return response()->json([
            'success' => true,
            'message' => 'Perfil atualizado com sucesso.',
            'data' => new UsuarioResource($usuario),
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'O endereço de e-mail é obrigatório.',
            'email.email' => 'Informe um formato de e-mail válido.',
        ]);

        $usuario = Usuario::where('email', $request->email)->first();

        if ($usuario) {
            $token = Password::broker()->createToken($usuario);
            $baseUrl = rtrim((string) (config('app.url_site') ?: env('URL_SITE') ?: config('app.url') ?: config('app.frontend_url')), '/');
            $urlRedefinicao = "{$baseUrl}/redefinir-senha?token={$token}&email=" . urlencode($usuario->email);

            try {
                $usuario->notify(new RecuperacaoSenhaNotification($urlRedefinicao));
            } catch (\Throwable $e) {
                Log::error('Erro ao enviar e-mail de recuperação de senha via API: ' . $e->getMessage(), [
                    'email_mascarado' => Str::mask($usuario->email, '*', 3, -4),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possível enviar o e-mail de recuperação no momento. Tente novamente mais tarde.',
                ], 500);
            }
        }

        // Retornamos sempre a mesma resposta genérica, existindo ou não o e-mail, para impedir que invasores descubram contas ativas no sistema (mitigação contra enumeração de contas - OWASP)
        return response()->json([
            'success' => true,
            'message' => 'Se o e-mail informado estiver cadastrado em nossa plataforma, você receberá um link com as instruções para redefinir sua senha em instantes.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'token.required' => 'O token de recuperação é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'A nova senha é obrigatória.',
            'password.min' => 'A nova senha deve conter pelo menos 6 caracteres.',
            'password.confirmed' => 'A confirmação de senha não confere.',
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Usuario $user, string $password) {
                $user->password = Hash::make($password);
                $user->setRememberToken(Str::random(60));
                $user->save();

                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Sua senha foi redefinida com sucesso! Você já pode realizar o login com suas novas credenciais.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Este link de recuperação é inválido ou já expirou. Por favor, solicite um novo link.',
            'errors' => [
                'token' => ['Token de recuperação inválido ou expirado.'],
            ],
        ], 422);
    }

    public function validateResetToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
        ], [
            'token.required' => 'O token é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        $usuario = Usuario::where('email', $request->email)->first();

        if (!$usuario || !Password::broker()->tokenExists($usuario, $request->token)) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Este link de recuperação é inválido ou já expirou.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'valid' => true,
            'message' => 'Token válido para redefinição de senha.',
        ]);
    }
}
