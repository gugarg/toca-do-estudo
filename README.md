# Toca do Estudo

Aplicação web colaborativa para registro e acompanhamento de rotinas de estudo em grupos fechados. Desenvolvida como Trabalho de Conclusão de Curso (TCC) do Curso Superior de Tecnologia em Análise e Desenvolvimento de Sistemas do IFRO (Campus Porto Velho Calama).

## Sumário

- [Visão geral](#visão-geral)
- [Principais funcionalidades](#principais-funcionalidades)
- [Tecnologias utilizadas](#tecnologias-utilizadas)
- [Pré-requisitos](#pré-requisitos)
- [Instalação e execução local](#instalação-e-execução-local)
- [Usuários de teste](#usuários-de-teste)
- [Testes automatizados](#testes-automatizados)
- [Principais rotas](#principais-rotas)
- [Configuração de e-mail (SMTP)](#configuração-de-e-mail-smtp)
- [Problemas comuns](#problemas-comuns)

## Visão geral

A plataforma organiza o acompanhamento de estudos em comunidades fechadas com foco em métricas acumuladas, sem penalizar ausências com perda do histórico. O sistema opera em arquitetura monolítica com Laravel 11, Livewire 3, Alpine.js e MySQL 8.0, adotando o horário oficial de Brasília (`America/Sao_Paulo`, UTC-3) para todas as validações de data e fechamentos de período.

## Principais funcionalidades

- **Autenticação e perfil:** cadastro, login por e-mail e senha, recuperação de senha por token temporário e edição de nome, biografia e avatar.
- **Registro de estudos:** lançamento de sessões com data, disciplina, duração (entre 5 minutos e 12 horas), questões resolvidas (0 a 1.000), anotações e controle individual de compartilhamento nos murais e rankings.
- **Regras de consistência:** limite diário acumulado de 24 horas (1.440 minutos) validado em transação com bloqueio no banco, janela retroativa de até 7 dias corridos e contagem de Dias Ativos por datas distintas (múltiplas sessões no mesmo dia somam horas e questões, mas contam apenas 1 Dia Ativo).
- **Registro visual opcional:** anexo de fotografias convertidas automaticamente para WebP via extensão GD, com remoção de metadados EXIF, nomeação por UUID v4 e visualização ampliada via Lightbox.
- **Comunidades fechadas:** criação de grupos com código de convite, edição de nome e descrição pelo proprietário, bloqueio de entrada duplicada e saída de membros comuns.
- **Classificações e desempate:** duas modalidades fixas definidas na criação do grupo (`Dias Ativos` ou `Horas Líquidas + Questões` com pesos imutáveis), filtros por período (`Semana`, `Mês` e `Geral`), isolamento temporal à data de ingresso do membro (estudos anteriores à entrada não contam retroativamente) e desempate em quatro níveis (minutos exatos, questões resolvidas, Dias Ativos e menor ID de usuário).
- **Mural social:** exibição das 20 sessões compartilhadas mais recentes da comunidade, reações predefinidas de incentivo e comentários vinculados à comunidade em que foram feitos (com edição e exclusão restritas ao autor).
- **API HTTP (`/api/v1`):** endpoints JSON protegidos por tokens Bearer com Laravel Sanctum para consultas, sessões, comentários, reações e comunidades.

## Tecnologias utilizadas

| Camada | Tecnologia | Versão |
| :--- | :--- | :--- |
| Backend | PHP / Laravel | 8.3+ / 11.56 |
| Interface reativa | Livewire / Blade / Alpine.js | 3.x |
| Estilização e build | CSS3 / Vite | Vite 5.4 |
| Banco de dados | MySQL | 8.0+ |
| Autenticação de API | Laravel Sanctum | Integrado |
| Processamento de imagem | PHP GD | Nativo PHP |
| Testes automatizados | PHPUnit | 10.5 |

## Pré-requisitos

- PHP 8.3+ (mínimo 8.2) com extensões habilitadas: `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo_mysql` e `curl`
- Composer 2.x
- Node.js 20+ e npm 10+
- MySQL 8.0+

> **Nota para Windows:** execute `php --ini` para localizar o `php.ini` e confirme que as extensões `gd` e `pdo_mysql` estão descomentadas antes de instalar as dependências.

## Instalação e execução local

### 1. Clonar o repositório e instalar dependências

```bash
git clone https://github.com/gugarg/toca-do-estudo.git
cd toca-do-estudo/backend

# Dependências do backend PHP (Laravel, Livewire, Sanctum)
composer install

# Dependências de build dos assets estáticos (Vite)
npm install
```

### 2. Configurar variáveis de ambiente

Dentro da pasta `backend`, crie o arquivo `.env` a partir do modelo e gere a chave da aplicação:

```bash
cp .env.example .env
php artisan key:generate
```
*(No Windows PowerShell, utilize `Copy-Item .env.example .env`.)*

Ajuste no `backend/.env` os dados do banco local (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) e confirme `APP_URL=http://127.0.0.1:8000` e `APP_TIMEZONE=America/Sao_Paulo`.

### 3. Criar bancos de dados, migrar e gerar link de storage

Crie no MySQL os bancos da aplicação e da suíte de testes:

```sql
CREATE DATABASE IF NOT EXISTS toca_do_estudo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS toca_do_estudo_testes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Em seguida, dentro da pasta `backend`, rode as migrations com os dados de teste e crie o link simbólico para imagens:

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

### 4. Iniciar o servidor local

Dentro da pasta `backend`, execute o comando oficial do Composer para iniciar o servidor PHP e o Vite simultaneamente:

```bash
cd backend
composer dev
```
*(No Windows, também é possível executar `dev.bat` na raiz.)*

A aplicação ficará disponível em `http://127.0.0.1:8000` e a API em `http://127.0.0.1:8000/api/v1`. Para gerar o build de produção dos assets e rodar apenas com `php artisan serve`, use `npm run build` dentro de `backend/`.

## Usuários de teste

O seeder cadastra três perfis fictícios utilizados na verificação do sistema (senha padrão para todos: `password123`):

| Usuário | E-mail | Comunidade inicial | Código de convite |
| :--- | :--- | :--- | :--- |
| **Matheus Silva** | `matheus@tocadoestudo.local` | Fiscais 2026 / Engenharia & Café | `TOCA-FISC26` |
| **Camila Duarte** | `camila@tocadoestudo.local` | Engenharia & Café / Fiscais 2026 | `TOCA-ENGCIV` |
| **Leonardo Rocha** | `leonardo@tocadoestudo.local` | Fiscais 2026 | `TOCA-FISC26` |

## Testes automatizados

A suíte utiliza um banco MySQL isolado (`toca_do_estudo_testes`), configurado em `backend/phpunit.xml`. Nunca aponte os testes para o banco principal, pois a estrutura é recriada durante a execução.

```bash
cd backend
php artisan test
```

Resultado da execução documentada no TCC: **76 testes aprovados (333 asserções)**, distribuídos em:

- Autenticação, painel e perfil (23 testes)
- Comunidades e participação (17 testes)
- Registro de estudos (16 testes)
- Integridade transacional e processamento de imagens (11 testes)
- Classificação e métricas (9 testes)

## Principais rotas

### Interface Web (Livewire / Blade)

| Método | Rota | Alias | Acesso |
| :--- | :--- | :--- | :--- |
| GET | `/entrar` | `/login` | Público |
| GET | `/cadastro` | `/register` | Público |
| GET | `/recuperar-senha` | `/forgot-password` | Público |
| GET | `/redefinir-senha/{token}` | `/reset-password/{token}` | Público |
| GET | `/painel` | `/dashboard` | Autenticado |
| GET | `/comunidades` | `/groups` | Autenticado |
| GET | `/comunidades/{grupo}` | `/groups/{group}` | Autenticado |
| GET | `/perfil` | `/profile` | Autenticado |
| POST | `/sair` | `/logout` | Autenticado |

### API REST (`/api/v1`)

| Método | Rota após `/api/v1` | Finalidade |
| :--- | :--- | :--- |
| POST | `/auth/login` | Autenticar e emitir token Bearer |
| GET / POST | `/study-sessions` | Listar ou registrar sessões do usuário autenticado |
| GET / DELETE | `/study-sessions/{id}` | Consultar sessão autorizada ou excluir sessão própria |
| GET / POST | `/groups/{group}/study-sessions/{id}/comments` | Listar ou publicar comentários vinculados à comunidade |
| GET / POST | `/study-sessions/{id}/comments` | Listar ou publicar comentários (requer `group_id` no envio) |
| PATCH / DELETE | `/comments/{id}` | Editar ou excluir comentário próprio |
| POST / DELETE | `/groups/{group}/study-sessions/{id}/reactions` | Adicionar ou remover reação própria na comunidade |
| POST / DELETE | `/study-sessions/{id}/reactions` | Adicionar ou remover reação própria (requer `group_id`) |
| GET | `/groups/{id}` | Consultar detalhes da comunidade |
| GET | `/groups/{id}/feed` | Consultar mural da comunidade |
| GET | `/groups/{id}/leaderboard` | Consultar classificação da comunidade |

## Configuração de e-mail (SMTP)

O envio de e-mails de recuperação de senha suporta SMTP (como Gmail com Senha de App de 16 caracteres) por meio das variáveis `EMAIL_SMTP`, `CODIGO_SMTP_PASS` e `URL_SITE` em `backend/.env`.

Se `CODIGO_SMTP_PASS` ficar vazio, o sistema usa automaticamente o driver `log`, gravando os e-mails e links de redefinição em `backend/storage/logs/laravel.log`. Para testar a conexão SMTP pelo terminal:

```bash
cd backend
php artisan mail:test-smtp --to=seu-email@gmail.com
```

## Problemas comuns

- **`Call to undefined function imagewebp()`:** habilite `extension=gd` no `php.ini` e reinicie o servidor.
- **Erro 1314 ao rodar `php artisan storage:link` no Windows:** execute o PowerShell como Administrador ou ative o Modo de Desenvolvedor do Windows.
- **Fotos não carregam na interface:** confirme se `php artisan storage:link` foi executado em `backend/` e se `APP_URL` no `.env` coincide com a URL acessada no navegador.
- **Erro de conexão MySQL (2002):** verifique se o serviço do MySQL está ativo na porta 3306 e se os bancos `toca_do_estudo` e `toca_do_estudo_testes` foram criados.
