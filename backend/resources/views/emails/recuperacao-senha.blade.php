<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefina sua senha — Toca do Estudo</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; background-color: #0b0f19; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #f1f5f9; }
        .btn-link:hover { background-color: #1d4ed8 !important; }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #0b0f19; color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #0b0f19; width: 100%;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <!-- Container Principal -->
                <table role="presentation" border="0" cellspacing="0" cellpadding="0" style="max-width: 560px; width: 100%; background-color: #151b2b; border: 1px solid #232d42; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);">
                    <!-- Cabeçalho com Logo -->
                    <tr>
                        <td align="center" style="padding: 32px 32px 20px 32px; border-bottom: 1px solid #232d42; background: linear-gradient(180deg, #182033 0%, #151b2b 100%);">
                            <table role="presentation" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <div style="display: inline-block; width: 44px; height: 44px; line-height: 44px; text-align: center; background-color: #1e293b; border: 1px solid #334155; border-radius: 10px; font-size: 22px; margin-bottom: 12px;">
                                            📚
                                        </div>
                                        <div style="font-size: 22px; font-weight: 700; color: #ffffff; letter-spacing: -0.02em;">
                                            Toca<span style="color: #60a5fa;"> do Estudo</span>
                                        </div>
                                        <div style="font-size: 12px; color: #94a3b8; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600;">
                                            Plataforma de Estudos Acadêmicos
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Corpo Principal -->
                    <tr>
                        <td style="padding: 32px;">
                            <h1 style="font-size: 19px; font-weight: 600; color: #ffffff; margin: 0 0 16px 0; letter-spacing: -0.01em;">
                                Recuperação de Senha
                            </h1>

                            <p style="font-size: 14px; line-height: 1.6; color: #cbd5e1; margin: 0 0 16px 0;">
                                Olá, <strong style="color: #ffffff;">{{ $nome }}</strong>!
                            </p>

                            <p style="font-size: 14px; line-height: 1.6; color: #cbd5e1; margin: 0 0 24px 0;">
                                Recebemos uma solicitação para redefinir a senha da sua conta na <strong>Toca do Estudo</strong> associada ao e-mail <span style="color: #60a5fa; word-break: break-all;">{{ $email }}</span>.
                            </p>

                            <!-- Botão Destacado -->
                            <table role="presentation" border="0" cellspacing="0" cellpadding="0" style="margin: 0 auto 28px auto; width: 100%;">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" border="0" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td align="center" style="border-radius: 8px; background-color: #2563eb;">
                                                    <a href="{{ $urlRedefinicao }}" target="_blank" class="btn-link" style="display: inline-block; padding: 14px 32px; font-size: 14px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px; background-color: #2563eb; letter-spacing: 0.01em;">
                                                        Criar nova senha
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Informação de Validade -->
                            <div style="background-color: #1e293b; border-left: 3px solid #60a5fa; border-radius: 4px; padding: 12px 14px; margin-bottom: 24px;">
                                <p style="font-size: 12.5px; line-height: 1.5; color: #94a3b8; margin: 0;">
                                    ⏱️ <strong>Segurança e Validade:</strong> Este link é de uso único, seguro e expira automaticamente em <strong>{{ $minutosExpiracao }} minutos</strong>.
                                </p>
                            </div>

                            <!-- Link Alternativo em Texto Puro -->
                            <p style="font-size: 12.5px; line-height: 1.5; color: #94a3b8; margin: 0 0 8px 0;">
                                Se você estiver com problemas para clicar no botão "Criar nova senha", copie e cole o endereço abaixo diretamente no seu navegador:
                            </p>
                            <p style="font-size: 11.5px; line-height: 1.5; color: #60a5fa; margin: 0 0 24px 0; word-break: break-all; background-color: #0b0f19; padding: 10px 12px; border-radius: 6px; border: 1px solid #1e293b;">
                                <a href="{{ $urlRedefinicao }}" target="_blank" style="color: #60a5fa; text-decoration: underline;">{{ $urlRedefinicao }}</a>
                            </p>

                            <!-- Aviso de Segurança -->
                            <div style="border-top: 1px solid #232d42; padding-top: 20px;">
                                <p style="font-size: 12px; line-height: 1.5; color: #64748b; margin: 0;">
                                    🛡️ <strong>Não solicitou esta alteração?</strong><br>
                                    Se você não realizou esta solicitação, fique tranquilo: nenhuma ação é necessária. Sua senha atual permanecerá segura e inalterada, e o link expirará sozinho.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Rodapé Profissional -->
                    <tr>
                        <td align="center" style="padding: 20px 32px; background-color: #0f1422; border-top: 1px solid #232d42; font-size: 11.5px; color: #64748b; line-height: 1.5;">
                            <p style="margin: 0 0 4px 0;">
                                <strong>Toca do Estudo</strong> — Comunidade e Colaboração para Estudos Acadêmicos
                            </p>
                            <p style="margin: 0;">
                                Este é um e-mail transacional gerado automaticamente via SMTP seguro. Não responda a esta mensagem.<br>
                                © {{ date('Y') }} Toca do Estudo. Todos os direitos reservados.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
