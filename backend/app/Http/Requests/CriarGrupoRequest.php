<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CriarGrupoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $metric = $this->input('default_metric', $this->input('metrica_padrao', 'dias_ativos'));
        $isPontos = in_array($metric, ['horas_questoes', 'hours_and_questions', 'pontos', 'points'], true);

        return [
            'name' => ['required_without:nome', 'nullable', 'string', 'min:2', 'max:100'],
            'nome' => ['required_without:name', 'nullable', 'string', 'min:2', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'descricao' => ['nullable', 'string', 'max:500'],
            'default_metric' => ['nullable', 'string', 'in:dias_ativos,active_days,horas_questoes,hours_and_questions,pontos,points'],
            'metrica_padrao' => ['nullable', 'string', 'in:dias_ativos,active_days,horas_questoes,hours_and_questions,pontos,points'],
            'pontos_hora' => $isPontos
                ? ['required_without_all:points_per_hour,weight_hours', 'numeric', 'min:0.01']
                : ['nullable'],
            'points_per_hour' => $isPontos
                ? ['required_without_all:pontos_hora,weight_hours', 'numeric', 'min:0.01']
                : ['nullable'],
            'weight_hours' => $isPontos
                ? ['required_without_all:pontos_hora,points_per_hour', 'numeric', 'min:0.01']
                : ['nullable'],
            'pontos_questao' => $isPontos
                ? ['required_without_all:points_per_question,weight_questions', 'numeric', 'min:0.01']
                : ['nullable'],
            'points_per_question' => $isPontos
                ? ['required_without_all:pontos_questao,weight_questions', 'numeric', 'min:0.01']
                : ['nullable'],
            'weight_questions' => $isPontos
                ? ['required_without_all:pontos_questao,points_per_question', 'numeric', 'min:0.01']
                : ['nullable'],
            'cover_photo' => [
                'nullable',
                'file',
                'mimes:jpeg,jpg,png,webp',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'default_metric.in' => 'Modalidade de competição inválida. São permitidas exclusivamente: "dias_ativos" e "horas_questoes".',
            'metrica_padrao.in' => 'Modalidade de competição inválida. São permitidas exclusivamente: "dias_ativos" e "horas_questoes".',
            'pontos_hora.required_without_all' => 'Os pontos por hora líquida são obrigatórios para a modalidade "Horas líquidas + questões".',
            'pontos_hora.min' => 'Os pontos por hora líquida devem ser maiores que zero.',
            'points_per_hour.min' => 'Os pontos por hora líquida devem ser maiores que zero.',
            'pontos_questao.required_without_all' => 'Os pontos por questão resolvida são obrigatórios para a modalidade "Horas líquidas + questões".',
            'pontos_questao.min' => 'Os pontos por questão resolvida devem ser maiores que zero.',
            'points_per_question.min' => 'Os pontos por questão resolvida devem ser maiores que zero.',
        ];
    }
}
