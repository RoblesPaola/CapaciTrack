CREATE DATABASE IF NOT EXISTS capacitrack1;
USE capacitrack1;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255),
  rol ENUM('admin','moderador','usuario') NOT NULL
);

CREATE TABLE cursos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(150),
  descripcion TEXT,
  creado_por INT,
  FOREIGN KEY (creado_por) REFERENCES usuarios(id)
);

CREATE TABLE evaluaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  curso_id INT,
  pregunta TEXT,
  respuesta_correcta VARCHAR(255),
  FOREIGN KEY (curso_id) REFERENCES cursos(id)
);
