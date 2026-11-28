-- =============================================================================
-- TOCA DO ESTUDO — SCRIPT DE PERSONAS DE TESTE (02_PERSONAS_TESTE.SQL)
-- =============================================================================
-- Finalidade: Povoar o banco com as 3 personas acadêmicas do Trabalho de
--             Conclusão de Curso (TCC), seus grupos, publicações e reações.
-- Pré-requisito: Execução prévia do script 'sql/01_essencial.sql'.
-- Idempotência: Pode ser executado múltiplas vezes sem duplicar registros ou
--               gerar erros de chave única (ON DUPLICATE KEY UPDATE).
--
-- Credenciais das Contas de Demonstração (Senha padrão: password123):
-- 1. Matheus Silva:    matheus@tocadoestudo.local   | Senha: password123
-- 2. Camila Duarte:    camila@tocadoestudo.local    | Senha: password123
-- 3. Leonardo Rocha:   leonardo@tocadoestudo.local  | Senha: password123
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. INSERÇÃO DAS PERSONAS DE TESTE (USUÁRIOS)
-- Hash de Senha: Bcrypt com Custo 12 correspondente à senha 'password123'
-- -----------------------------------------------------------------------------
INSERT INTO usuarios (id, nome, email, password, biografia) VALUES
(1, 'Matheus Silva', 'matheus@tocadoestudo.local', '$2y$12$/.u7LUuDT4IVrV5tJmt8zu59izK2lbJKgmS97b1G7FT3Q2Th.jKXO', 'Focado na Receita Federal. Meta: 100 questões/dia.'),
(2, 'Camila Duarte', 'camila@tocadoestudo.local', '$2y$12$Rm0uf0h/4/c5UUZSRy7tw.IMNkGGiZJFlZXG32kEpfoBRWbNU3ASq', 'Engenharia Civil - 5º período. Foco e consistência!'),
(3, 'Leonardo Rocha', 'leonardo@tocadoestudo.local', '$2y$12$ErHtoKJ8HEW0HAloZgDpMuEE8ugUsGxMnMEmGboXmMTrNT5LzlZeu', 'Transição de carreira para Backend PHP / Fullstack.')
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    biografia = VALUES(biografia),
    password = VALUES(password);

-- -----------------------------------------------------------------------------
-- 2. INSERÇÃO DAS COMUNIDADES / GRUPOS DE ESTUDO
-- -----------------------------------------------------------------------------
INSERT INTO grupos (id, proprietario_id, nome, descricao, codigo_convite, metrica_padrao, pontos_hora, pontos_questao, data_criacao) VALUES
(1, 1, 'Fiscais 2026', 'Grupo de estudos focado em concursos fiscais e resolução massiva de exercícios.', 'TOCA-FISC26', 'horas_questoes', 10.00, 1.00, '2026-09-01 00:00:00'),
(2, 2, 'Engenharia & Café', 'Apoio mútuo para cálculo, física e matérias da faculdade.', 'TOCA-ENGCIV', 'dias_ativos', NULL, NULL, '2026-09-01 00:00:00'),
(3, 3, 'Devs Noturnos & Algoritmos', 'Grupo de consistência para quem estuda programação após o expediente.', 'TOCA-CODE88', 'dias_ativos', NULL, NULL, '2026-09-01 00:00:00')
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    descricao = VALUES(descricao),
    codigo_convite = VALUES(codigo_convite),
    metrica_padrao = VALUES(metrica_padrao),
    pontos_hora = VALUES(pontos_hora),
    pontos_questao = VALUES(pontos_questao),
    data_criacao = VALUES(data_criacao);

-- -----------------------------------------------------------------------------
-- 3. INSERÇÃO DOS MEMBROS NAS COMUNIDADES
-- -----------------------------------------------------------------------------
INSERT INTO membros_grupo (grupo_id, usuario_id, papel, ingressou_em) VALUES
(1, 1, 'proprietario', '2026-09-01 00:00:00'),
(1, 2, 'membro', '2026-09-01 00:00:00'),
(1, 3, 'membro', '2026-09-01 00:00:00'),
(2, 2, 'proprietario', '2026-09-01 00:00:00'),
(2, 1, 'membro', '2026-09-01 00:00:00'),
(3, 3, 'proprietario', '2026-09-01 00:00:00'),
(3, 1, 'membro', '2026-09-01 00:00:00')
ON DUPLICATE KEY UPDATE
    papel = VALUES(papel),
    ingressou_em = VALUES(ingressou_em);

-- -----------------------------------------------------------------------------
-- 4. INSERÇÃO DE SESSÕES DE ESTUDO ACADÊMICAS
-- -----------------------------------------------------------------------------
INSERT INTO sessoes_estudo (id, usuario_id, data_estudo, disciplina, minutos, questoes, anotacoes, caminho_foto, compartilhar) VALUES
(1, 1, '2026-09-12', 'Direito Tributário', 120, 45, 'Resolução de questões discursivas sobre hipóteses de suspensão do crédito tributário.', 'sessions/sample_tributario.webp', TRUE),
(2, 1, '2026-09-13', 'Contabilidade Geral', 90, 30, 'Balanço patrimonial e demonstração de resultados de exercícios anteriores.', 'sessions/sample_contabilidade.webp', TRUE),
(3, 1, '2026-09-14', 'Auditoria Fiscal', 150, 70, 'Procedimentos de amostragem estatística em auditorias aduaneiras.', 'sessions/sample_auditoria.webp', TRUE),
(4, 2, '2026-09-13', 'Cálculo Numérico', 180, 20, 'Deduções teóricas do Método de Newton-Raphson aplicadas a matrizes.', 'sessions/sample_calculo.webp', TRUE),
(5, 2, '2026-09-14', 'Mecânica dos Solos', 120, 15, 'Ensaio de compactação e determinação da umidade ótima de Proctor.', 'sessions/sample_solos.webp', TRUE),
(6, 3, '2026-09-14', 'Estruturas de Dados', 75, 12, 'Implementação de árvores binárias balanceadas e tabelas hash em TypeScript.', 'sessions/sample_arvores.webp', TRUE)
ON DUPLICATE KEY UPDATE
    disciplina = VALUES(disciplina),
    minutos = VALUES(minutos),
    questoes = VALUES(questoes),
    anotacoes = VALUES(anotacoes),
    compartilhar = VALUES(compartilhar);

-- -----------------------------------------------------------------------------
-- 5. REAÇÕES SOCIAIS / KUDOS ACADÊMICOS (VINCULADAS À COMUNIDADE)
-- -----------------------------------------------------------------------------
INSERT INTO reacoes (sessao_estudo_id, grupo_id, usuario_id, tipo_reacao) VALUES
(1, 1, 2, 'applause'),
(1, 1, 3, 'fire'),
(2, 1, 3, 'books'),
(3, 1, 2, 'target'),
(4, 2, 1, 'fire'),
(5, 2, 1, 'applause')
ON DUPLICATE KEY UPDATE
    tipo_reacao = VALUES(tipo_reacao);

-- -----------------------------------------------------------------------------
-- 6. COMENTÁRIOS DE DEMONSTRAÇÃO (VINCULADOS À COMUNIDADE)
-- -----------------------------------------------------------------------------
INSERT INTO comentarios_sessao_estudo (id, sessao_estudo_id, grupo_id, usuario_id, conteudo) VALUES
(1, 1, 1, 2, 'Excelente resumo, Matheus! A jurisprudência sobre moratória sempre cai em prova.'),
(2, 1, 1, 3, 'Muito bom! Consistência inspiradora!'),
(3, 4, 2, 1, 'Cálculo Numérico exige muita dedicação. Parabéns pela sessão!')
ON DUPLICATE KEY UPDATE
    conteudo = VALUES(conteudo);

-- =============================================================================
-- FIM DO SCRIPT DE PERSONAS — TOCA DO ESTUDO
-- =============================================================================
