CREATE DATABASE IF NOT EXISTS mca_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mca_db;

-- Tabela de casos
CREATE TABLE IF NOT EXISTS casos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_crianca VARCHAR(100) NOT NULL,
    idade INT,
    data_desaparecimento DATE,
    local_desaparecimento VARCHAR(200),
    descricao TEXT,
    foto VARCHAR(255),
    contato_responsavel VARCHAR(100),
    telefone VARCHAR(20),
    email VARCHAR(100),
    informacoes_adicionais TEXT,
    status ENUM('ativo', 'resolvido', 'investigando') DEFAULT 'ativo',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de anotações
CREATE TABLE IF NOT EXISTS anotacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caso_id INT,
    titulo VARCHAR(100) NOT NULL,
    conteudo TEXT,
    categoria ENUM('pista', 'avistamento', 'contato', 'observacao', 'urgente') NOT NULL,
    tags TEXT,
    anexos TEXT,
    urgente BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (caso_id) REFERENCES casos(id) ON DELETE CASCADE
);

-- Tabela de dicas
CREATE TABLE IF NOT EXISTS dicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caso_id INT,
    descricao TEXT NOT NULL,
    nome_contato VARCHAR(100),
    telefone_contato VARCHAR(20),
    email_contato VARCHAR(100),
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pendente', 'verificada', 'descartada') DEFAULT 'pendente',
    observacoes TEXT,
    FOREIGN KEY (caso_id) REFERENCES casos(id) ON DELETE CASCADE
);

-- Inserir alguns casos de exemplo
INSERT INTO casos (nome_crianca, idade, data_desaparecimento, local_desaparecimento, descricao, contato_responsavel, telefone, email) VALUES
('Maria Silva', 7, '2024-03-15', 'Shopping Morumbi - São Paulo/SP', 'Criança de 7 anos, cabelos castanhos cacheados, vestia blusa rosa e saia jeans. Desapareceu próximo à praça de alimentação.', 'Ana Silva (mãe)', '(11) 99999-1234', 'ana.silva@email.com'),
('João Santos', 9, '2024-03-20', 'Escola Municipal João XXIII - Rio de Janeiro/RJ', 'Menino de 9 anos, cabelos loiros, olhos azuis. Não retornou para casa após o horário escolar.', 'Carlos Santos (pai)', '(21) 88888-5678', 'carlos.santos@email.com'),
('Ana Paula Oliveira', 6, '2024-04-01', 'Parque da Cidade - Brasília/DF', 'Menina de 6 anos, cabelos longos pretos, vestia vestido amarelo com flores. Sumiu enquanto brincava no playground.', 'Lucia Oliveira (avó)', '(61) 77777-9012', 'lucia.oliveira@email.com'),
('Pedro Henrique Costa', 8, '2024-04-10', 'Praia de Iracema - Fortaleza/CE', 'Menino de 8 anos, moreno, cabelos crespos. Desapareceu durante passeio em família na praia.', 'Roberto Costa (tio)', '(85) 66666-3456', 'roberto.costa@email.com');

-- Inserir algumas anotações de exemplo
INSERT INTO anotacoes (caso_id, titulo, conteudo, categoria, tags, urgente) VALUES
(1, 'Possível avistamento em shopping', 'Testemunha relatou ter visto criança com características similares no Shopping Morumbi, próximo à praça de alimentação. Estava acompanhada de mulher adulta, cabelos loiros.', 'pista', 'Shopping Morumbi,Testemunha,São Paulo', false),
(2, 'Informação anônima - possível localização', 'Denúncia anônima via Disque 100 reportando criança em casa abandonada na Zona Norte. Polícia já foi acionada para verificação.', 'urgente', 'Denúncia Anônima,Polícia Acionada,Zona Norte', true),
(1, 'Câmera de segurança registra criança', 'Imagens de câmera de segurança de loja na Av. Paulista mostram criança com vestido azul às 15:20 do dia 15/03. Imagens foram entregues à polícia para análise.', 'avistamento', 'CFTV,Av. Paulista,Evidência', false),
(3, 'Conversa com familiar', 'Telefonema com a mãe revelou que a criança tinha mencionado querer visitar a avó em outra cidade dias antes do desaparecimento.', 'contato', 'Familiar,Possível Motivo', false),
(1, 'Padrão de comportamento antes do desaparecimento', 'Segundo relatos de professores, a criança estava mais quieta e retraída nas últimas semanas.', 'observacao', 'Comportamento,Escola,Análise', false),
(2, 'Identificação de veículo suspeito', 'Testemunha reportou van branca, sem identificação, circulando próximo à escola nos dias anteriores ao desaparecimento. Placa parcialmente identificada: ABC-1***.', 'pista', 'Veículo Suspeito,Placa,Investigação', false);

-- Inserir algumas dicas de exemplo
INSERT INTO dicas (caso_id, descricao, nome_contato, telefone_contato, status) VALUES
(1, 'Vi uma criança parecida no mercado da esquina ontem à tarde', 'José Silva', '(11) 98765-4321', 'pendente'),
(2, 'Acredito ter visto o menino na estação de metrô da Sé', 'Maria Fernanda', '(11) 97654-3210', 'verificada'),
(3, 'Criança similar foi vista no parque próximo ao shopping', 'Paulo Santos', '(61) 96543-2109', 'pendente');