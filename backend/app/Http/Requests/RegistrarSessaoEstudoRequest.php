<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\SessaoEstudo;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarSessaoEstudoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $minDate = SessaoEstudo::getDataLimiteRetroativa();
        $today = Carbon::now()->toDateString();
        $userId = $this->user()?->id;

        return [
            'study_date' => [
                'required',
                'date',
                "before_or_equal:{$today}",
                "after_or_equal:{$minDate}",
            ],
            'subject' => ['required', 'string', 'min:2', 'max:100'],
            'minutes' => ['required', 'integer', 'min:5', 'max:720'],
            'questions' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'compartilhar' => ['nullable', 'boolean'],
            'share' => ['nullable', 'boolean'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('membros_grupo', 'grupo_id')->where(function ($query) use ($userId) {
                    $query->where('usuario_id', $userId);
                }),
            ],
            'photo' => [
                'nullable',
                'file',
                'mimes:jpeg,jpg,png,webp',
                'max:5120', // Máximo 5MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'study_date.before_or_equal' => 'A data de estudo não pode ser futura.',
            'study_date.after_or_equal' => sprintf('Lançamentos retroativos são permitidos para até %d dias atrás.', SessaoEstudo::LIMITE_DIAS_RETROATIVO),
            'minutes.min' => 'O tempo de estudo deve ser de no mínimo 5 minutos.',
            'minutes.max' => 'O tempo por sessão não pode exceder 720 minutos (12 horas).',
            'questions.max' => 'O número de questões não pode exceder 1.000 por sessão.',
            'group_ids.*.exists' => 'Você só pode contabilizar a sessão em grupos dos quais é membro.',
            'group_ids.*.distinct' => 'Grupos duplicados não são permitidos.',
            'photo.max' => 'A foto de comprovação não pode exceder 5MB.',
            'photo.mimes' => 'Apenas imagens nos formatos JPEG, PNG e WEBP são permitidas.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $userId = $this->user()?->id;
            $studyDate = $this->input('study_date');
            $minutes = (int) $this->input('minutes');

            if ($userId && $studyDate && $minutes > 0) {
                $minutosExistentes = (int) SessaoEstudo::where('usuario_id', $userId)
                    ->where('data_estudo', $studyDate)
                    ->sum('minutos');

                if (($minutosExistentes + $minutes) > 1440) {
                    $validator->errors()->add(
                        'minutes',
                        'A soma dos estudos registrados para esta data não pode ultrapassar 24 horas (1.440 minutos).'
                    );
                }
            }
        });
    }
}
