-- Criação do banco de dados EcoSwap
CREATE DATABASE IF NOT EXISTS ecoswap CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE ecoswap;

-- Tabela de usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    localizacao VARCHAR(255),
    tipo_usuario ENUM('usuario', 'admin') DEFAULT 'usuario',
    ativo BOOLEAN DEFAULT TRUE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de categorias
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,
    ativa BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de itens
CREATE TABLE itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    categoria_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    condicao ENUM('Excelente', 'Muito Bom', 'Bom', 'Regular') NOT NULL,
    imagem_url VARCHAR(500),
    status ENUM('disponivel', 'trocado', 'removido') DEFAULT 'disponivel',
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT
);

-- Tabela de trocas (para futuras implementações)
CREATE TABLE trocas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_ofertado_id INT NOT NULL,
    item_desejado_id INT NOT NULL,
    usuario_ofertante_id INT NOT NULL,
    usuario_receptor_id INT NOT NULL,
    status ENUM('pendente', 'aceita', 'recusada', 'finalizada') DEFAULT 'pendente',
    data_proposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_resposta TIMESTAMP NULL,
    observacoes TEXT,
    FOREIGN KEY (item_ofertado_id) REFERENCES itens(id) ON DELETE CASCADE,
    FOREIGN KEY (item_desejado_id) REFERENCES itens(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_ofertante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_receptor_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Inserção das categorias padrão
INSERT INTO categorias (nome, descricao) VALUES
('Eletrônicos', 'Smartphones, tablets, computadores e acessórios eletrônicos'),
('Roupas', 'Roupas masculinas, femininas e infantis'),
('Casa e Jardim', 'Móveis, decoração e utensílios domésticos'),
('Livros', 'Livros de diversos gêneros e materiais educativos'),
('Esportes', 'Equipamentos esportivos e de atividades físicas'),
('Brinquedos', 'Brinquedos infantis e jogos'),
('Música', 'Instrumentos musicais e equipamentos de áudio'),
('Veículos', 'Bicicletas, patinetes e acessórios para veículos'),
('Beleza', 'Produtos de beleza e cuidados pessoais'),
('Outros', 'Itens que não se encaixam nas outras categorias');

-- Inserção do usuário administrador padrão
-- Senha: admin123 (hash MD5 para simplicidade - em produção usar password_hash)
INSERT INTO usuarios (nome, email, senha, telefone, localizacao, tipo_usuario) VALUES
('Administrador do Sistema', 'admin@ecoswap.com', MD5('admin123'), '(34) 99999-0000', 'Uberlândia, MG', 'admin');

-- Inserção de usuários de teste
INSERT INTO usuarios (nome, email, senha, telefone, localizacao) VALUES
('Maria Silva', 'maria@email.com', MD5('123456'), '(34) 99888-1111', 'Uberlândia, MG'),
('João Santos', 'joao@email.com', MD5('123456'), '(34) 99777-2222', 'Uberlândia, MG'),
('Ana Costa', 'ana@email.com', MD5('123456'), '(34) 99666-3333', 'Uberaba, MG');

-- Inserção de itens de exemplo (usando IDs das categorias e usuários inseridos)
INSERT INTO itens (usuario_id, categoria_id, titulo, descricao, condicao, imagem_url) VALUES
(2, 1, 'iPhone 12 64GB', 'iPhone 12 em excelente estado, pouco uso, com caixa e carregador original. Sem riscos na tela.', 'Excelente', 'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=400'),
(3, 2, 'Jaqueta Jeans Feminina', 'Jaqueta jeans tamanho M, marca famosa, usada poucas vezes. Perfeita para o inverno.', 'Muito Bom', 'https://images.unsplash.com/photo-1551537482-f2075a1d41f2?w=400'),
(4, 4, 'Coleção Harry Potter', 'Todos os 7 livros da saga Harry Potter em português, em bom estado de conservação.', 'Bom', 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=400'),
(2, 5, 'Tênis de Corrida Nike', 'Tênis Nike Air Zoom tamanho 42, usado apenas para caminhadas. Muito confortável.', 'Muito Bom', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400'),
(3, 3, 'Mesa de Centro Vintage', 'Mesa de centro de madeira maciça, estilo vintage. Pequeno desgaste mas muito funcional.', 'Bom', 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=400');

-- Índices para melhor performance
CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_usuarios_tipo ON usuarios(tipo_usuario);
CREATE INDEX idx_itens_usuario ON itens(usuario_id);
CREATE INDEX idx_itens_categoria ON itens(categoria_id);
CREATE INDEX idx_itens_status ON itens(status);
CREATE INDEX idx_trocas_status ON trocas(status);