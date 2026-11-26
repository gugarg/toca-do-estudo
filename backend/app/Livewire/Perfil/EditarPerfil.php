<?php

declare(strict_types=1);

namespace App\Livewire\Perfil;

use App\Models\Usuario;
use App\Services\AgregadorMetricasService;
use App\Services\PipelineImagemService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Toca do Estudo')]
class EditarPerfil extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $bio = '';

    /** @var \Illuminate\Http\UploadedFile|null */
    public $avatar = null;
    public ?string $avatarUrl = null;
    public ?string $croppedAvatarData = null;

    public ?string $feedbackMessage = null;
    public ?string $feedbackError = null;
    public ?string $passwordResetFeedback = null;
    public ?string $passwordResetError = null;

    public function mount(): void
    {
        $this->loadUserData();
    }

    protected function loadUserData(): void
    {
        /** @var Usuario $user */
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $this->name = (string) ($user->nome ?? '');
        $this->email = (string) $user->email;
        $this->bio = (string) ($user->biografia ?? '');
        $this->avatarUrl = $user->url_avatar;
    }

    public function updatedAvatar(): void
    {
        $this->validate([
            'avatar' => ['nullable', 'image', 'max:10240'], // Máximo 10MB
        ], [
            'avatar.image' => 'A foto deve ser uma imagem válida (JPEG, PNG ou WEBP).',
            'avatar.max' => 'A imagem de perfil não pode ultrapassar 10MB.',
            'avatar.uploaded' => 'Falha no envio da foto. O arquivo pode ter ultrapassado o limite ou o formato não foi reconhecido.',
        ]);
    }

    public function restore(): void
    {
        $this->resetValidation();
        $this->avatar = null;
        $this->croppedAvatarData = null;
        $this->feedbackMessage = null;
        $this->feedbackError = null;
        $this->passwordResetFeedback = null;
        $this->passwordResetError = null;
        $this->loadUserData();
    }

    public function removeAvatar(): void
    {
        /** @var Usuario $user */
        $user = Auth::user();
        if (!$user) {
            return;
        }

        if ($user->url_avatar) {
            Storage::disk('public')->delete($user->url_avatar);
            $user->url_avatar = null;
            $user->save();
        }

        $this->avatar = null;
        $this->avatarUrl = null;
        $this->feedbackMessage = 'Foto de perfil removida com sucesso.';
    }

    public function save(PipelineImagemService $pipelineImagem): void
    {
        $this->feedbackMessage = null;
        $this->feedbackError = null;

        /** @var Usuario $user */
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('usuarios', 'email')->ignore($user->id),
            ],
            'bio' => ['nullable', 'string', 'max:250'],
            'avatar' => ['nullable', 'image', 'max:10240'],
            'croppedAvatarData' => ['nullable', 'string'],
        ], [
            'name.required' => 'O nome completo é obrigatório.',
            'name.min' => 'O nome deve ter no mínimo 2 caracteres.',
            'email.required' => 'O endereço de e-mail é obrigatório.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'email.unique' => 'Este e-mail já está sendo utilizado por outro estudante.',
            'bio.max' => 'A biografia não pode ultrapassar 250 caracteres.',
            'avatar.max' => 'A foto de perfil não pode ultrapassar 10MB.',
            'avatar.uploaded' => 'Falha no envio da foto. O arquivo pode ter ultrapassado o limite ou o formato não foi reconhecido.',
        ]);

        try {
            // Processa avatar cortado pelo editor visual ou arquivo original
            $avatarProcessed = false;

            if (!empty($this->croppedAvatarData)) {
                // O usuário utilizou o editor de corte — recebemos dados base64
                if ($user->url_avatar) {
                    Storage::disk('public')->delete($user->url_avatar);
                }

                $novoCaminho = $pipelineImagem->processarBase64ParaWebP($this->croppedAvatarData, 'avatars');
                $user->url_avatar = $novoCaminho;
                $this->avatarUrl = $novoCaminho;
                $avatarProcessed = true;
            } elseif ($this->avatar !== null) {
                // Fallback: upload direto sem corte
                if ($user->url_avatar) {
                    Storage::disk('public')->delete($user->url_avatar);
                }

                $novoCaminho = $pipelineImagem->processarEConverterParaWebP($this->avatar, 'avatars');
                $user->url_avatar = $novoCaminho;
                $this->avatarUrl = $novoCaminho;
                $avatarProcessed = true;
            }

            if ($avatarProcessed) {
                $this->avatar = null;
                $this->croppedAvatarData = null;
            }

            $user->nome = trim($validated['name']);
            $user->email = trim($validated['email']);
            $user->biografia = !empty($validated['bio']) ? trim($validated['bio']) : null;
            $user->save();

            $this->feedbackMessage = 'Perfil acadêmico atualizado com sucesso!';
        } catch (\Throwable $e) {
            $this->feedbackError = $e->getMessage() ?: 'Erro ao salvar alterações no perfil.';
        }
    }

    public function sendPasswordResetLink(): void
    {
        $this->passwordResetFeedback = null;
        $this->passwordResetError = null;

        /** @var Usuario $user */
        $user = Auth::user();
        if (!$user) {
            return;
        }

        // Rate limit para evitar spam de envio de e-mails
        $limiterKey = 'reset-link-profile:' . $user->id;
        if (RateLimiter::tooManyAttempts($limiterKey, 3)) {
            $seconds = RateLimiter::availableIn($limiterKey);
            $this->passwordResetError = "Muitas tentativas. Aguarde {$seconds} segundos antes de solicitar novamente.";
            return;
        }

        RateLimiter::hit($limiterKey, 300);

        Password::broker()->sendResetLink(['email' => $user->email]);

        $this->passwordResetFeedback = "Enviamos o link de redefinição de senha para {$user->email}. Verifique sua caixa de entrada.";
    }

    // --- Aliases em Português para Ações de Perfil ---

    public function salvar(PipelineImagemService $pipelineImagem): void
    {
        $this->save($pipelineImagem);
    }

    public function restaurar(): void
    {
        $this->restore();
    }

    public function removerAvatar(): void
    {
        $this->removeAvatar();
    }

    public function solicitarRedefinicaoSenha(): void
    {
        $this->requestPasswordReset();
    }

    public function render(AgregadorMetricasService $metricasService)
    {
        /** @var Usuario $user */
        $user = Auth::user();
        $stats = $metricasService->obterEstatisticasUsuario($user->id);

        $summary = [
            'total_active_days' => $stats->total_dias_ativos ?? 0,
            'total_hours' => $stats->total_hours ?? 0,
            'total_questions' => $stats->total_questoes ?? 0,
            'groups_count' => $user->grupos()->count(),
        ];

        return view('livewire.perfil.editar-perfil', [
            'user' => $user,
            'summary' => $summary,
        ]);
    }
}
