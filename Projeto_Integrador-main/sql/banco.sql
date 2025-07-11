CREATE DATABASE IF NOT EXISTS banco_central;
USE banco_central;

CREATE TABLE IF NOT EXISTS gestores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  cnpj VARCHAR(20),
  foto_perfil VARCHAR(255) NULL DEFAULT NULL,
  tentativas_login INT DEFAULT 0,
  bloqueado_ate DATETIME NULL DEFAULT NULL,
  token_recuperacao VARCHAR(255) NULL DEFAULT NULL,
  token_expira_em DATETIME NULL DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS convites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gestor_id INT NOT NULL,
  codigo VARCHAR(255) NOT NULL UNIQUE,
  data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
  usado BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (gestor_id) REFERENCES gestores(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  convite_id INT UNIQUE,
  data_vinculacao DATETIME NULL DEFAULT NULL,
  foto_perfil VARCHAR(255) NULL DEFAULT NULL,
  tentativas_login INT DEFAULT 0,
  bloqueado_ate DATETIME NULL DEFAULT NULL,
  token_recuperacao VARCHAR(255) NULL DEFAULT NULL,
  token_expira_em DATETIME NULL DEFAULT NULL,
  FOREIGN KEY (convite_id) REFERENCES convites(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS postagens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  gestor_id INT NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  descricao TEXT,
  imagem VARCHAR(255),
  data_publicacao DATE,
  redes_sociais VARCHAR(255),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('Aguardando Análise', 'Aprovado', 'Revisar', 'Reprovado') NOT NULL DEFAULT 'Aguardando Análise',
  feedback_cliente TEXT NULL,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
  FOREIGN KEY (gestor_id) REFERENCES gestores(id) ON DELETE CASCADE
);
