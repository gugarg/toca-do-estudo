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
- **Registro visual opcional:** anexo de fotografias convertidas automaticamente para WebP via extensão GD, com remoção de metadados EXIF, nomeação por UUID v4, armazenamento em disco privado com controle de acesso via Policy e visualização ampliada via Lightbox.
- **Comunidades fechadas:** criação de grupos com código de convite, edição de nome e descrição pelo proprietário, bloqueio de entrada duplicada e saída de membros comuns.
- **Classificações e desempate:** duas modalidades fixas definidas na criação do grupo (`Dias Ativos` ou `Horas Líquidas + Questões` com pesos imutáveis), filtros por período (`Semana`, `Mês` e `Geral`), corte temporal pela data de criação do grupo (estudos feitos antes da fundação do grupo não entram no mural nem no ranking, mas estudos realizados a partir da criação passam a contar mesmo ao ingressar depois) e desempate em quatro níveis (minutos exatos, questões resolvidas, Dias Ativos e menor ID de usuário).
- **Mural social e histórico paginado:** mural da comunidade e histórico do painel com paginação numerada (10 estudos por página nos grupos e 5 no painel pessoal), reações predefinidas de incentivo e comentários vinculados à comunidade em que foram feitos (com edição e exclusão restritas ao autor).

## Tecnologias utilizadas

| Camada | Tecnologia | Versão |
| :--- | :--- | :--- |
| Backend | PHP / Laravel | 8.3+ / 11.56 |
| Interface reativa | Livewire / Blade / Alpine.js | 3.x |
| Estilização e build | CSS3 / Vite | Vite 5.4 |
| Banco de dados | MySQL | 8.0+ |
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

# Dependências do backend PHP (Laravel e Livewire)
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

A aplicação ficará disponível em `http://127.0.0.1:8000`. Para gerar o build de produção dos assets e rodar apenas com `php artisan serve`, use `npm run build` dentro de `backend/`.

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

Resultado da execução atual da suíte: **80 testes aprovados (380 asserções)**, distribuídos em:

- Autenticação, painel e perfil (24 testes)
- Comunidades e participação (19 testes)
- Registro de estudos (18 testes)
- Integridade transacional e processamento de imagens (11 testes)
- Classificação e métricas (8 testes)

## Principais rotas

Todas as rotas atendem a interface web em Livewire / Blade:

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
| GET | `/sessoes/{sessao}/foto` | — | Autenticado (`Policy`) |
| POST | `/sair` | `/logout` | Autenticado |

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
