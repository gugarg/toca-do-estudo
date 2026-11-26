<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Grupo;
use App\Models\Reacao;
use App\Models\SessaoEstudo;
use App\Models\Usuario;
use App\Services\AgregadorMetricasService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $metricsService = app(AgregadorMetricasService::class);

        // Garante pastas de sessions e avatars no storage
        Storage::disk('public')->makeDirectory('sessions');
        Storage::disk('public')->makeDirectory('avatars');

        // Função auxiliar para gerar um placeholder WebP real via GD
        $createMockWebp = function (string $text, int $colorR, int $colorG, int $colorB): string {
            $img = imagecreatetruecolor(800, 500);
            $bgColor = imagecolorallocate($img, $colorR, $colorG, $colorB);
            $textColor = imagecolorallocate($img, 255, 255, 255);
            imagefill($img, 0, 0, $bgColor);
            imagestring($img, 5, 250, 240, "Toca do Estudo: {$text}", $textColor);

            $filename = 'sessions/' . Str::uuid()->toString() . '.webp';
            ob_start();
            imagewebp($img, null, 80);
            $webpData = ob_get_clean();
            imagedestroy($img);

            Storage::disk('public')->put($filename, $webpData);
            return $filename;
        };

        // Usuários em PT-BR (Matheus e Camila com fotos de perfil reais; Leonardo sem foto para demonstrar a inicial)
        $avatarMatheus = 'avatars/f4c2b2c6-231c-4b8d-8ee8-07f2a3460731.webp';
        $avatarCamila = 'avatars/5ed97bb0-7182-47b3-8cf7-be68b6ca03e4.webp';

        $matheus = Usuario::create([
            'nome' => 'Matheus Silva',
            'email' => 'matheus@tocadoestudo.local',
            'password' => Hash::make('password123'),
            'url_avatar' => Storage::disk('public')->exists($avatarMatheus) ? $avatarMatheus : null,
            'biografia' => 'Focado na Receita Federal. Meta: 100 questões/dia.',
        ]);

        $camila = Usuario::create([
            'nome' => 'Camila Duarte',
            'email' => 'camila@tocadoestudo.local',
            'password' => Hash::make('password123'),
            'url_avatar' => Storage::disk('public')->exists($avatarCamila) ? $avatarCamila : null,
            'biografia' => 'Engenharia Civil - 5º período. Foco e consistência!',
        ]);

        $leonardo = Usuario::create([
            'nome' => 'Leonardo Rocha',
            'email' => 'leonardo@tocadoestudo.local',
            'password' => Hash::make('password123'),
            'url_avatar' => null, // Sem foto: demonstra o avatar automático com a primeira letra do nome ('L')
            'biografia' => 'Transição de carreira para Backend PHP / Fullstack.',
        ]);

        // Grupos de estudo em PT-BR (Modalidades oficiais: 'horas_questoes' e 'dias_ativos')
        $groupFiscais = Grupo::create([
            'proprietario_id' => $matheus->id,
            'nome' => 'Fiscais 2026',
            'descricao' => 'Grupo de estudos focado em concursos fiscais e resolução massiva de exercícios.',
            'codigo_convite' => 'TOCA-FISC26',
            'metrica_padrao' => 'horas_questoes',
            'pontos_hora' => 10.00,
            'pontos_questao' => 1.00,
        ]);

        $groupEng = Grupo::create([
            'proprietario_id' => $camila->id,
            'nome' => 'Engenharia & Café',
            'descricao' => 'Apoio mútuo para cálculo, física e matérias da faculdade.',
            'codigo_convite' => 'TOCA-ENGCIV',
            'metrica_padrao' => 'dias_ativos',
            'pontos_hora' => null,
            'pontos_questao' => null,
        ]);

        $now = Carbon::now();
        $dataIngressoDemo = $now->copy()->subDays(10);

        // Membros (ingressaram antes das sessões de demonstração para aparecerem no mural/ranking)
        $groupFiscais->membros()->attach([
            $matheus->id => ['papel' => 'proprietario', 'ingressou_em' => $dataIngressoDemo],
            $camila->id => ['papel' => 'membro', 'ingressou_em' => $dataIngressoDemo],
            $leonardo->id => ['papel' => 'membro', 'ingressou_em' => $dataIngressoDemo],
        ]);

        $groupEng->membros()->attach([
            $camila->id => ['papel' => 'proprietario', 'ingressou_em' => $dataIngressoDemo],
            $matheus->id => ['papel' => 'membro', 'ingressou_em' => $dataIngressoDemo],
        ]);

        // Sessões de estudo em PT-BR (com compartilhar = true para exibição nos murais dos grupos dos membros)

        // Sessões: Matheus
        $m1 = SessaoEstudo::create([
            'usuario_id' => $matheus->id,
            'data_estudo' => $now->toDateString(),
            'disciplina' => 'Direito Tributário',
            'minutos' => 120,
            'questoes' => 60,
            'anotacoes' => 'Resolução de questões da FGV sobre ICMS e ISS.',
            'caminho_foto' => $createMockWebp('Caderno Tributario - Matheus', 37, 99, 235),
            'compartilhar' => true,
        ]);

        $m2 = SessaoEstudo::create([
            'usuario_id' => $matheus->id,
            'data_estudo' => $now->copy()->subDay()->toDateString(), // Retroativo Ontem
            'disciplina' => 'Direito Constitucional',
            'minutos' => 90,
            'questoes' => 45,
            'anotacoes' => 'Controle difuso e concentrado de constitucionalidade.',
            'caminho_foto' => $createMockWebp('Tela de Questoes - Matheus', 5, 150, 105),
            'compartilhar' => true,
        ]);

        $m3 = SessaoEstudo::create([
            'usuario_id' => $matheus->id,
            'data_estudo' => $now->copy()->subDays(3)->toDateString(), // Retroativo 3 dias
            'disciplina' => 'Contabilidade Geral',
            'minutos' => 150,
            'questoes' => 40,
            'anotacoes' => 'Balanço patrimonial e demonstração de resultado.',
            'caminho_foto' => $createMockWebp('Resumo Contabilidade - Matheus', 217, 119, 6),
            'compartilhar' => true,
        ]);

        // Sessões: Camila
        $c1 = SessaoEstudo::create([
            'usuario_id' => $camila->id,
            'data_estudo' => $now->toDateString(),
            'disciplina' => 'Cálculo II',
            'minutos' => 135,
            'questoes' => 20,
            'anotacoes' => 'Integrais duplas e triplas com coordenadas polares.',
            'caminho_foto' => $createMockWebp('Resumo Colorido Calculo - Camila', 79, 70, 229),
            'compartilhar' => true,
        ]);

        $c2 = SessaoEstudo::create([
            'usuario_id' => $camila->id,
            'data_estudo' => $now->copy()->subDay()->toDateString(),
            'disciplina' => 'Física III',
            'minutos' => 100,
            'questoes' => 15,
            'anotacoes' => 'Eletromagnetismo e Lei de Gauss.',
            'caminho_foto' => $createMockWebp('Formulas Fisica - Camila', 13, 148, 136),
            'compartilhar' => true,
        ]);

        // Sessões: Leonardo
        $l1 = SessaoEstudo::create([
            'usuario_id' => $leonardo->id,
            'data_estudo' => $now->toDateString(),
            'disciplina' => 'Arquitetura PHP / Laravel',
            'minutos' => 60,
            'questoes' => 10,
            'anotacoes' => 'Estudo sobre Injeção de Dependências e Service Layer.',
            'caminho_foto' => $createMockWebp('IDE e Anotacoes - Leonardo', 220, 38, 38),
            'compartilhar' => true,
        ]);

        // Reações vinculadas às respectivas comunidades
        Reacao::create([
            'sessao_estudo_id' => $m1->id,
            'grupo_id' => $groupFiscais->id,
            'usuario_id' => $camila->id,
            'tipo_reacao' => 'fire',
            'created_at' => now(),
        ]);

        Reacao::create([
            'sessao_estudo_id' => $m1->id,
            'grupo_id' => $groupFiscais->id,
            'usuario_id' => $leonardo->id,
            'tipo_reacao' => 'applause',
            'created_at' => now(),
        ]);

        Reacao::create([
            'sessao_estudo_id' => $c1->id,
            'grupo_id' => $groupEng->id,
            'usuario_id' => $matheus->id,
            'tipo_reacao' => 'books',
            'created_at' => now(),
        ]);

        // Comentários nos murais vinculados às respectivas comunidades
        \App\Models\Comentario::create([
            'sessao_estudo_id' => $m1->id,
            'grupo_id' => $groupFiscais->id,
            'usuario_id' => $camila->id,
            'conteudo' => 'Excelente constância, Matheus! Parabéns pelo volume de questões!',
            'created_at' => now()->subMinutes(45),
        ]);

        \App\Models\Comentario::create([
            'sessao_estudo_id' => $m1->id,
            'grupo_id' => $groupFiscais->id,
            'usuario_id' => $leonardo->id,
            'conteudo' => 'Inspirador! Bora manter o ritmo diário.',
            'created_at' => now()->subMinutes(20),
        ]);
    }
}
