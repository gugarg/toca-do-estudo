-- =============================================================================
-- TOCA DO ESTUDO — SCRIPT SQL ESSENCIAL (01_ESSENCIAL.SQL)
-- =============================================================================
-- Finalidade: Inicializar integralmente a estrutura física do banco de dados
--             relacional (DDL) sem nenhuma persona de teste ou dado demonstrativo.
-- SGBD Compatível: MySQL 8.0+ e MariaDB 10.5+ (InnoDB Engine)
-- Collation & Charset: utf8mb4 / utf8mb4_unicode_ci
-- Idioma do Modelo: Português Brasileiro (PT-BR)
-- Normalização: 3FN Estrita com suporte a agregações analíticas sob demanda
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 0. LIMPEZA PREVENTIVA DE ESTRUTURAS EXISTENTES (ORDEM REVERSA DE DEPENDÊNCIAS)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS comentarios_sessao_estudo;
DROP TABLE IF EXISTS reacoes;
DROP TABLE IF EXISTS sessoes_estudo;
DROP TABLE IF EXISTS membros_grupo;
DROP TABLE IF EXISTS grupos;
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS cache_locks;
DROP TABLE IF EXISTS cache;
DROP TABLE IF EXISTS usuarios;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- 1. TABELA: usuarios
-- Registros cadastrais, autenticação segura e perfil dos estudantes.
-- -----------------------------------------------------------------------------
CREATE TABLE usuarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    url_avatar VARCHAR(255) NULL,
    biografia VARCHAR(250) NULL,
    remember_token VARCHAR(100) NULL,
    data_criacao TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    data_edicao TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uk_usuarios_email UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. TABELA: password_reset_tokens
-- Armazenamento com hash seguro de tokens temporários de recuperação de senha.
-- -----------------------------------------------------------------------------
CREATE TABLE password_reset_tokens (
    email VARCHAR(255) NOT NULL PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. TABELA: sessions (Sessões HTTP / Web)
-- Suporte a controle de estado de autenticação via sessão em banco.
-- -----------------------------------------------------------------------------
CREATE TABLE sessions (
    id VARCHAR(255) NOT NULL PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    INDEX idx_sessions_user_id (user_id),
    INDEX idx_sessions_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. TABELAS: cache e cache_locks (Cache Driver de Banco de Dados)
-- Suporte a cache distribuído atômico (CACHE_STORE=database).
-- -----------------------------------------------------------------------------
CREATE TABLE cache (
    `key` VARCHAR(255) NOT NULL PRIMARY KEY,
    `value` MEDIUMTEXT NOT NULL,
    expiration INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cache_locks (
    `key` VARCHAR(255) NOT NULL PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. TABELA: grupos
-- Comunidades e salas de estudo compartilhadas entre alunos.
-- -----------------------------------------------------------------------------
CREATE TABLE grupos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proprietario_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT NULL,
    codigo_convite VARCHAR(12) NOT NULL,
    metrica_padrao VARCHAR(20) NOT NULL DEFAULT 'dias_ativos',
    pontos_hora DECIMAL(8,2) NULL,
    pontos_questao DECIMAL(8,2) NULL,
    data_criacao TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    data_edicao TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uk_grupos_codigo_convite UNIQUE (codigo_convite),
    INDEX idx_grupos_proprietario_id (proprietario_id),
    CONSTRAINT fk_grupos_proprietario FOREIGN KEY (proprietario_id) REFERENCES usuarios (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. TABELA: membros_grupo
-- Relação associativa N:N entre usuários e grupos com controle de papéis.
-- Índices: UNIQUE em (grupo_id, usuario_id) e índice reverso (usuario_id, grupo_id).
-- -----------------------------------------------------------------------------
CREATE TABLE membros_grupo (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    grupo_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    papel VARCHAR(20) NOT NULL DEFAULT 'membro',
    ingressou_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_membros_grupo_usuario (grupo_id, usuario_id),
    INDEX idx_membros_grupo_usuario_grupo (usuario_id, grupo_id),
    CONSTRAINT fk_membros_grupo FOREIGN KEY (grupo_id) REFERENCES grupos (id) ON DELETE CASCADE,
    CONSTRAINT fk_membros_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. TABELA: sessoes_estudo
-- Publicações individuais de estudo acadêmico com comprovação fotográfica.
-- Índices cobrindo auditoria temporal e agregação indexada de feeds/rankings.
-- -----------------------------------------------------------------------------
CREATE TABLE sessoes_estudo (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NOT NULL,
    data_estudo DATE NOT NULL,
    disciplina VARCHAR(100) NOT NULL,
    minutos SMALLINT UNSIGNED NOT NULL,
    questoes SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    anotacoes TEXT NULL,
    caminho_foto VARCHAR(255) NULL,
    compartilhar BOOLEAN NOT NULL DEFAULT TRUE,
    data_criacao TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    data_edicao TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sessoes_usuario_data (usuario_id, data_estudo),
    INDEX idx_sessoes_estudo_feed_ranking (usuario_id, compartilhar, data_estudo),
    CONSTRAINT fk_sessoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. TABELA: reacoes (Kudos / Interações Sociais)
-- Reações acadêmicas únicas por usuário, sessão de estudo e comunidade.
-- -----------------------------------------------------------------------------
CREATE TABLE reacoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sessao_estudo_id BIGINT UNSIGNED NOT NULL,
    grupo_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    tipo_reacao VARCHAR(20) NOT NULL DEFAULT 'fire',
    data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY reacoes_sessao_usuario_grupo_unique (sessao_estudo_id, usuario_id, grupo_id),
    INDEX idx_reacoes_sessao_grupo (sessao_estudo_id, grupo_id),
    CONSTRAINT fk_reacoes_sessao FOREIGN KEY (sessao_estudo_id) REFERENCES sessoes_estudo (id) ON DELETE CASCADE,
    CONSTRAINT fk_reacoes_grupo FOREIGN KEY (grupo_id) REFERENCES grupos (id) ON DELETE CASCADE,
    CONSTRAINT fk_reacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. TABELA: comentarios_sessao_estudo
-- Fórum de discussão e feedback acadêmico vinculado à comunidade onde foi publicado.
-- -----------------------------------------------------------------------------
CREATE TABLE comentarios_sessao_estudo (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sessao_estudo_id BIGINT UNSIGNED NOT NULL,
    grupo_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    conteudo TEXT NOT NULL,
    data_criacao TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    data_edicao TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_comentarios_sessao_data (sessao_estudo_id, data_criacao),
    INDEX idx_comentarios_sessao_grupo_data (sessao_estudo_id, grupo_id, data_criacao),
    INDEX idx_comentarios_usuario (usuario_id),
    CONSTRAINT fk_comentarios_sessao FOREIGN KEY (sessao_estudo_id) REFERENCES sessoes_estudo (id) ON DELETE CASCADE,
    CONSTRAINT fk_comentarios_grupo FOREIGN KEY (grupo_id) REFERENCES grupos (id) ON DELETE CASCADE,
    CONSTRAINT fk_comentarios_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- FIM DO SCRIPT ESSENCIAL — TOCA DO ESTUDO
-- =============================================================================
