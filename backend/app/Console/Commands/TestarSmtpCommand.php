<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestarSmtpCommand extends Command
{
    /**
     * O nome e a assinatura do comando.
     *
     * @var string
     */
    protected $signature = 'mail:test-smtp {destinatario? : Endereço de e-mail de destino para o teste}';

    /**
     * A descrição do comando.
     *
     * @var string
     */
    protected $description = 'Testa a conectividade e o envio de e-mails através do SMTP do Gmail configurado no sistema';

    public function handle(): int
    {
        $this->info('============================================================');
        $this->info('    Toca do Estudo — Diagnóstico de Conexão SMTP (Gmail)         ');
        $this->info('============================================================');
        $this->newLine();

        $emailSmtp = config('mail.mailers.smtp.username') ?: env('EMAIL_SMTP');
        $senhaSmtp = config('mail.mailers.smtp.password') ?: env('CODIGO_SMTP_PASS');
        $host = config('mail.mailers.smtp.host') ?: 'smtp.gmail.com';
        $port = (int) (config('mail.mailers.smtp.port') ?: 587);
        $encryption = config('mail.mailers.smtp.encryption') ?: 'tls';
        $urlSite = config('app.url_site') ?: env('URL_SITE', 'Não configurado');

        $this->table(
            ['Parâmetro', 'Valor Configurado', 'Status'],
            [
                ['EMAIL_SMTP / Usuário', $emailSmtp ?: '(Vazio)', !empty($emailSmtp) ? '✅ OK' : '❌ Não Definido'],
                ['CODIGO_SMTP_PASS', !empty($senhaSmtp) ? '••••••••••••••••' : '(Vazio)', !empty($senhaSmtp) ? '✅ Configurada' : '⚠️ Pendente (Senha de App)'],
                ['Servidor SMTP', $host, '✅ smtp.gmail.com'],
                ['Porta', (string) $port, $port === 587 || $port === 465 ? '✅ ' . $port : '⚠️ ' . $port],
                ['Criptografia', strtoupper($encryption), '✅ ' . strtoupper($encryption)],
                ['URL_SITE', $urlSite, !empty($urlSite) ? '✅ ' . $urlSite : '⚠️ Não Definida'],
                ['Mailer Ativo', config('mail.default'), config('mail.default') === 'smtp' ? '✅ smtp' : 'ℹ️ ' . config('mail.default')],
            ]
        );

        $this->newLine();

        if (empty($emailSmtp)) {
            $this->error('ERRO: A variável EMAIL_SMTP não está definida.');
            $this->line('Defina EMAIL_SMTP=seu-email@gmail.com no seu arquivo .env');
            return Command::FAILURE;
        }

        if (empty($senhaSmtp)) {
            $this->warn('ATENÇÃO: A variável CODIGO_SMTP_PASS (Senha de App do Google) não foi configurada.');
            $this->line('Para enviar e-mails reais através do Gmail:');
            $this->line('1. Acesse https://myaccount.google.com/apppasswords');
            $this->line('2. Crie uma Senha de App para "Toca do Estudo"');
            $this->line('3. Cole o código de 16 letras em CODIGO_SMTP_PASS no arquivo .env');
            $this->line('Sem essa senha, a aplicação utiliza o modo log em storage/logs/laravel.log por segurança.');
            $this->newLine();

            if (!$this->confirm('Deseja testar apenas a abertura do socket de rede com smtp.gmail.com?', true)) {
                return Command::INVALID;
            }
        }

        // Teste de conectividade TCP/Socket
        $this->line("Testando conexão de rede com {$host}:{$port}...");
        $socket = @fsockopen($host, $port, $errno, $errstr, 5);

        if (!$socket) {
            $this->error("FALHA DE REDE: Não foi possível conectar a {$host}:{$port}.");
            $this->error("Erro ({$errno}): {$errstr}");
            $this->line('Verifique se seu provedor, firewall ou antivírus está bloqueando conexões de saída na porta ' . $port);
            return Command::FAILURE;
        }

        fclose($socket);
        $this->info("✅ Conexão TCP estabelecida com sucesso com {$host}:{$port}!");
        $this->newLine();

        if (empty($senhaSmtp)) {
            $this->info('Socket OK! Para testar o envio de mensagens reais, preencha CODIGO_SMTP_PASS no .env.');
            return Command::SUCCESS;
        }

        // Teste de Envio de E-mail Real
        $destinatario = $this->argument('destinatario') ?: $emailSmtp;
        $this->line("Tentando enviar e-mail de teste para: <comment>{$destinatario}</comment>...");

        try {
            Mail::mailer('smtp')->raw(
                "Olá!\n\nEste é um e-mail de teste disparado pela Toca do Estudo para validar a integração com o Gmail SMTP.\n\nServidor: {$host}:{$port} ({$encryption})\nURL da Aplicação: {$urlSite}\nData/Hora: " . now()->format('d/m/Y H:i:s') . "\n\nTudo pronto para a recuperação de senha!",
                function ($message) use ($destinatario, $emailSmtp) {
                    $message->to($destinatario)
                        ->from($emailSmtp, 'Toca do Estudo — Teste SMTP')
                        ->subject('Toca do Estudo — Teste de Conexão SMTP Bem-sucedido');
                }
            );

            $this->info("🎉 SUCESSO! E-mail de teste enviado com sucesso para {$destinatario}!");
            $this->line('Verifique a caixa de entrada (ou pastas de Spam/Promoções) de ' . $destinatario);
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('FALHA NO ENVIO SMTP: ' . $e->getMessage());
            $this->newLine();
            $this->line('Possíveis causas e soluções:');
            $this->line('1. Senha de App incorreta: O Gmail exige Senha de App de 16 caracteres gerada em https://myaccount.google.com/apppasswords');
            $this->line('2. Verificação em 2 etapas desativada na conta Google (obrigatória para gerar Senha de App)');
            $this->line('3. Limite diário de envio do Gmail atingido');
            return Command::FAILURE;
        }
    }
}
