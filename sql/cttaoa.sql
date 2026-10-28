-- CTTAOA · Carnet digital · Versión 8 (PHP). Importar en phpMyAdmin sobre una base VACÍA.
-- Compatible con MySQL 5.7+ / MariaDB 10.3+. Motor InnoDB, utf8mb4.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS programas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(190) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_programa (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fichas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  numero VARCHAR(20) NOT NULL,
  programa_id INT UNSIGNED NOT NULL,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  creada DATETIME NOT NULL,
  fecha_inicio DATE NULL,
  fecha_fin DATE NULL,
  vencida_procesada TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ficha (numero),
  KEY ix_ficha_fin (fecha_fin),
  CONSTRAINT fk_ficha_programa FOREIGN KEY (programa_id) REFERENCES programas (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS personas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo_persona ENUM('aprendiz','instructor','funcionario','contratista') NOT NULL,
  tipo_documento VARCHAR(4) NOT NULL DEFAULT 'CC',
  numero_documento VARCHAR(20) NOT NULL,
  nombres VARCHAR(100) NOT NULL,
  apellidos VARCHAR(100) NOT NULL,
  fecha_expedicion DATE NULL,
  email VARCHAR(190) NOT NULL DEFAULT '',
  rh VARCHAR(3) NOT NULL DEFAULT '',
  rh_verificado TINYINT(1) NOT NULL DEFAULT 0,
  ficha_id INT UNSIGNED NULL,
  dependencia VARCHAR(200) NOT NULL DEFAULT '',
  estado_registro ENUM('pendiente','registrado','retirado') NOT NULL DEFAULT 'pendiente',
  observaciones TEXT NULL,
  foto VARCHAR(120) NOT NULL DEFAULT '',
  foto_subida_por INT UNSIGNED NULL,
  foto_fecha DATETIME NULL,
  pin_hash VARCHAR(255) NULL,
  pin_configurado TINYINT(1) NOT NULL DEFAULT 0,
  estado_formacion VARCHAR(20) NOT NULL DEFAULT '',
  estado_formacion_fecha DATETIME NULL,
  terminos_fecha DATETIME NULL,
  terminos_version VARCHAR(10) NOT NULL DEFAULT '',
  terminos_ip VARCHAR(45) NOT NULL DEFAULT '',
  creada DATETIME NOT NULL,
  actualizada DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_documento (numero_documento),
  KEY ix_persona_ficha (ficha_id),
  KEY ix_persona_tipo (tipo_persona),
  KEY ix_persona_apellidos (apellidos, nombres),
  CONSTRAINT fk_persona_ficha FOREIGN KEY (ficha_id) REFERENCES fichas (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario VARCHAR(60) NOT NULL,
  nombre VARCHAR(100) NOT NULL DEFAULT '',
  email VARCHAR(190) NOT NULL DEFAULT '',
  persona_id INT UNSIGNED NULL,
  password_hash VARCHAR(255) NOT NULL,
  rol ENUM('principal','admin') NOT NULL DEFAULT 'admin',
  permisos VARCHAR(255) NOT NULL DEFAULT '',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado DATETIME NOT NULL,
  ultimo_acceso DATETIME NULL,
  ultima_ip VARCHAR(45) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuario (usuario),
  UNIQUE KEY uq_usuario_persona (persona_id),
  CONSTRAINT fk_usuario_persona FOREIGN KEY (persona_id) REFERENCES personas (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS carnes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  persona_id INT UNSIGNED NOT NULL,
  token CHAR(12) NOT NULL,
  estado ENUM('pendiente','activo','inactivo') NOT NULL DEFAULT 'pendiente',
  estado_cambiado DATETIME NULL,
  estado_cambiado_por INT UNSIGNED NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_carne_persona (persona_id),
  UNIQUE KEY uq_carne_token (token),
  KEY ix_carne_estado (estado),
  CONSTRAINT fk_carne_persona FOREIGN KEY (persona_id) REFERENCES personas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plantilla (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  regional VARCHAR(100) NOT NULL,
  complejo VARCHAR(200) NOT NULL,
  texto_reverso TEXT NOT NULL,
  etiqueta_firma VARCHAR(60) NOT NULL,
  texto_perdida TEXT NOT NULL,
  logo VARCHAR(120) NOT NULL DEFAULT '',
  firma VARCHAR(120) NOT NULL DEFAULT '',
  actualizada DATETIME NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracion (
  id TINYINT UNSIGNED NOT NULL,
  max_intentos_pin TINYINT UNSIGNED NOT NULL DEFAULT 3,
  bloqueo_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 15,
  recuperacion_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  bloqueo_manual TINYINT(1) NOT NULL DEFAULT 0,
  historial_dias SMALLINT UNSIGNED NOT NULL DEFAULT 7,
  alertas_activas TINYINT(1) NOT NULL DEFAULT 1,
  alerta_fallos SMALLINT UNSIGNED NOT NULL DEFAULT 5,
  copia_horas SMALLINT UNSIGNED NOT NULL DEFAULT 24,
  ultima_limpieza DATETIME NULL,
  ultima_copia DATETIME NULL,
  actualizada DATETIME NULL,
  actualizada_por INT UNSIGNED NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS restablecer_pin (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  persona_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  creado DATETIME NOT NULL,
  vence DATETIME NOT NULL,
  usado TINYINT(1) NOT NULL DEFAULT 0,
  ip VARCHAR(45) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  UNIQUE KEY uq_restablecer_token (token_hash),
  KEY ix_restablecer_persona (persona_id, creado),
  KEY ix_restablecer_ip (ip, creado),
  CONSTRAINT fk_restablecer_persona FOREIGN KEY (persona_id) REFERENCES personas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS intentos_acceso (
  clave CHAR(64) NOT NULL,
  tipo VARCHAR(12) NOT NULL DEFAULT '',
  etiqueta VARCHAR(120) NOT NULL DEFAULT '',
  fallos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  bloqueado_hasta DATETIME NULL,
  ultimo DATETIME NOT NULL,
  PRIMARY KEY (clave),
  KEY ix_intentos_ultimo (ultimo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS accesos_titular (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  persona_id INT UNSIGNED NOT NULL,
  fecha DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY ix_acceso_fecha (fecha),
  KEY ix_acceso_persona (persona_id),
  CONSTRAINT fk_acceso_persona FOREIGN KEY (persona_id) REFERENCES personas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eventos_seguridad (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo VARCHAR(30) NOT NULL,
  ip VARCHAR(45) NOT NULL DEFAULT '',
  detalle VARCHAR(255) NOT NULL DEFAULT '',
  fecha DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY ix_evsec_fecha (fecha),
  KEY ix_evsec_tipo (tipo, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS consultas_verificacion (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  documento VARCHAR(20) NOT NULL,
  persona_id INT UNSIGNED NULL,
  resultado VARCHAR(20) NOT NULL,
  ip VARCHAR(45) NOT NULL DEFAULT '',
  fecha DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY ix_consulta_fecha (fecha),
  KEY ix_consulta_ip (ip, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS eventos_verificacion (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  carne_id INT UNSIGNED NULL,
  resultado VARCHAR(10) NOT NULL,
  fecha DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY ix_evento_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS importaciones (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  archivo VARCHAR(120) NOT NULL,
  nombre_original VARCHAR(200) NOT NULL,
  hoja VARCHAR(100) NOT NULL DEFAULT '',
  estado ENUM('cargado','confirmado','cancelado') NOT NULL DEFAULT 'cargado',
  informe TEXT NULL,
  usuario_id INT UNSIGNED NULL,
  creada DATETIME NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS historial (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED NULL,
  usuario_nombre VARCHAR(60) NOT NULL DEFAULT '',
  accion VARCHAR(60) NOT NULL,
  objeto VARCHAR(60) NOT NULL,
  objeto_id VARCHAR(30) NOT NULL DEFAULT '',
  detalle TEXT NULL,
  fecha DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY ix_hist_objeto (objeto, objeto_id),
  KEY ix_hist_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Filas iniciales
INSERT INTO configuracion (id)
  SELECT 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM configuracion WHERE id = 1);

INSERT INTO plantilla (regional, complejo, texto_reverso, etiqueta_firma, texto_perdida, actualizada)
  SELECT 'Regional Antioquia',
         'Complejo Tecnológico, Turístico y Agroindustrial del Occidente Antioqueño',
         'Este carnet identifica a quien lo porta, únicamente para el cumplimiento de sus funciones y para la obtención de los servicios que la institución presta a sus aprendices.\n\nSe solicita a las autoridades civiles y militares prestarle toda la colaboración para su desempeño.',
         'FIRMA AUTORIZADA',
         'Si por algún motivo este carnet es extraviado, por favor diríjase a la (dirección institucional configurable).',
         NOW()
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM plantilla);

-- Administrador inicial:  usuario = admin   contraseña = Castillo2006
-- (cámbiala apenas entres: menú Mi cuenta)
INSERT INTO usuarios (usuario, nombre, password_hash, rol, permisos, activo, creado)
  SELECT 'admin', 'Administrador', '$2y$10$scvuxKXyWvTIF1TN.IH4fuoyUg/KKWdxcnmZNulLmEpxrIxuPXVmu', 'principal', '', 1, NOW()
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = 'admin');
