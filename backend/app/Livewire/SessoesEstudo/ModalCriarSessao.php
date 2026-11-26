<?php

declare(strict_types=1);

namespace App\Livewire\SessoesEstudo;

use App\Models\SessaoEstudo;
use App\Models\Usuario;
use App\Services\SessaoEstudoService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ModalCriarSessao extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;

    public string $studyDate = '';
    public string $subject = '';
    public int $minutes = 60;
    public int $hours = 1;
    public int $minutesPart = 0;
    public int $questions = 20;
    public string $notes = '';
    public array $selectedGroups = [];
    public bool $compartilhar = true;

    /** @var \Illuminate\Http\UploadedFile|null */
    public $photo = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->studyDate = Carbon::today()->toDateString();
        $this->sincronizarHorasMinutos();
        $this->loadUserGroups();
    }

    #[On('abrir-modal-sessao')]
    public function open(): void
    {
        $this->resetValidation();
        $this->errorMessage = null;
        $this->studyDate = Carbon::today()->toDateString();
        $this->sincronizarHorasMinutos();
        $this->loadUserGroups();
        $this->isOpen = true;
    }

    public function updatedHours($value): void
    {
        $this->hours = max(0, min(12, (int) $value));
        $this->recalcularMinutosTotais();
    }

    public function updatedMinutesPart($value): void
    {
        $min = max(0, (int) $value);
        if ($min >= 60) {
            $this->hours = min(12, $this->hours + intdiv($min, 60));
            $this->minutesPart = $min % 60;
        } else {
            $this->minutesPart = $min;
        }
        $this->recalcularMinutosTotais();
    }

    public function updatedMinutes($value): void
    {
        $this->sincronizarHorasMinutos();
    }

    public function setMinutesPreset(int $totalMinutos): void
    {
        $this->minutes = max(5, min(720, $totalMinutos));
        $this->sincronizarHorasMinutos();
    }

    public function definirMinutos(int $totalMinutos): void
    {
        $this->setMinutesPreset($totalMinutos);
    }

    protected function sincronizarHorasMinutos(): void
    {
        $this->hours = intdiv($this->minutes, 60);
        $this->minutesPart = $this->minutes % 60;
    }

    protected function recalcularMinutosTotais(): void
    {
        $this->minutes = ($this->hours * 60) + $this->minutesPart;
    }

    public function getTempoFormatadoProperty(): string
    {
        $h = intdiv($this->minutes, 60);
        $m = $this->minutes % 60;

        if ($h > 0 && $m > 0) {
            return "{$h}h {$m}min";
        }
        if ($h > 0) {
            return "{$h}h";
        }
        return "{$m}min";
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->reset(['photo', 'errorMessage']);
    }

    public function removePhoto(): void
    {
        $this->photo = null;
    }

    // --- Aliases em Português para Ações do Modal ---

    public function abrir(): void
    {
        $this->open();
    }

    public function fechar(): void
    {
        $this->close();
    }

    public function removerFoto(): void
    {
        $this->removePhoto();
    }

    public function salvar(SessaoEstudoService $sessaoService): void
    {
        $this->save($sessaoService);
    }

    protected function loadUserGroups(): void
    {
        /** @var Usuario $user */
        $user = Auth::user();
        if ($user) {
            $this->selectedGroups = $user->grupos()->pluck('grupos.id')->toArray();
        }
    }

    public function save(SessaoEstudoService $sessaoService): void
    {
        $this->errorMessage = null;
        /** @var Usuario $user */
        $user = Auth::user();

        if (!$user) {
            return;
        }

        $minDate = SessaoEstudo::getDataLimiteRetroativa();
        $today = Carbon::now()->toDateString();

        $this->recalcularMinutosTotais();

        $dadosValidados = $this->validate([
            'studyDate' => [
                'required',
                'date',
                "before_or_equal:{$today}",
                "after_or_equal:{$minDate}",
            ],
            'subject' => ['required', 'string', 'min:2', 'max:100'],
            'minutes' => ['required', 'integer', 'min:5', 'max:720'],
            'questions' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'selectedGroups' => ['nullable', 'array'],
            'selectedGroups.*' => [
                'integer',
                'distinct',
                Rule::exists('membros_grupo', 'grupo_id')->where(fn($q) => $q->where('usuario_id', $user->id)),
            ],
            'photo' => [
                'nullable',
                'image',
                'max:5120', // Máximo 5MB
            ],
        ], [
            'studyDate.before_or_equal' => 'A data de estudo não pode ser futura.',
            'studyDate.after_or_equal' => sprintf('Lançamentos retroativos são permitidos para até %d dias atrás.', SessaoEstudo::LIMITE_DIAS_RETROATIVO),
            'subject.required' => 'A disciplina ou matéria é obrigatória.',
            'subject.min' => 'A disciplina deve ter no mínimo 2 caracteres.',
            'minutes.min' => 'O tempo de estudo deve ser de no mínimo 5 minutos.',
            'minutes.max' => 'O tempo por sessão não pode exceder 720 minutos (12 horas).',
            'questions.max' => 'O número de questões não pode exceder 1.000 por sessão.',
            'selectedGroups.*.exists' => 'Você só pode contabilizar a sessão em grupos dos quais é membro.',
            'photo.max' => 'A foto de comprovação não pode exceder 5MB.',
            'photo.image' => 'Apenas imagens nos formatos JPEG, PNG e WEBP são permitidas.',
        ]);

        $minutosJaRegistrados = (int) SessaoEstudo::where('usuario_id', $user->id)
            ->where('data_estudo', $dadosValidados['studyDate'])
            ->sum('minutos');

        if (($minutosJaRegistrados + $dadosValidados['minutes']) > 1440) {
            $this->addError('minutes', 'A soma dos estudos registrados nesta data não pode ultrapassar 24 horas (1.440 minutos).');
            return;
        }

        try {
            $sessaoService->criarSessao($user, [
                'data_estudo' => $dadosValidados['studyDate'],
                'disciplina' => $dadosValidados['subject'],
                'minutos' => $dadosValidados['minutes'],
                'questoes' => $dadosValidados['questions'] ?? 0,
                'anotacoes' => !empty($dadosValidados['notes']) ? trim($dadosValidados['notes']) : null,
                'compartilhar' => $this->compartilhar,
                'group_ids' => $dadosValidados['selectedGroups'] ?? [],
            ], $this->photo);

            $this->dispatch('session-created');
            $this->close();
            $this->reset(['subject', 'notes', 'photo']);
            $this->minutes = 60;
            $this->sincronizarHorasMinutos();
            $this->questions = 20;
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage() ?: 'Erro ao registrar sessão de estudo.';
        }
    }

    public function render()
    {
        $userGroups = Auth::user() ? Auth::user()->grupos()->get() : collect();

        return view('livewire.sessoes-estudo.modal-criar-sessao', [
            'userGroups' => $userGroups,
            'minDate' => SessaoEstudo::getDataLimiteRetroativa(),
        ]);
    }
}
