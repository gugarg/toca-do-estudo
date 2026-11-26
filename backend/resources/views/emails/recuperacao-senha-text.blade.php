============================================================
TOCA DO ESTUDO — RECUPERAÇÃO DE SENHA
============================================================

Olá, {{ $nome }}!

Recebemos uma solicitação para redefinir a senha da sua conta na Toca do Estudo vinculada ao e-mail: {{ $email }}

Para criar uma nova senha, copie e cole o link abaixo em seu navegador:
{{ $urlRedefinicao }}

AVISO DE SEGURANÇA:
- Este link é de uso único e expira automaticamente em {{ $minutosExpiracao }} minutos.
- Se você não solicitou a redefinição de senha, desconsidere esta mensagem. Sua senha atual continuará válida e sua conta permanecerá segura.

------------------------------------------------------------
Toca do Estudo — Plataforma de Estudos Acadêmicos
Mensagem automática enviada via SMTP. Por favor, não responda.
© {{ date('Y') }} Toca do Estudo. Todos os direitos reservados.
