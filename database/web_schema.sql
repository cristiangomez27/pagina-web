-- Suave Urban Studio - Esquema de base de datos pública
-- Compatible con MySQL 8+ y MariaDB 10.4+ (Hostinger)

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS web_configuracion (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  clave VARCHAR(120) NOT NULL,
  valor_texto LONGTEXT NULL,
  tipo ENUM('string','number','boolean','json') NOT NULL DEFAULT 'string',
  descripcion VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_web_configuracion_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_categorias (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(140) NOT NULL,
  slug VARCHAR(170) NOT NULL,
  descripcion TEXT NULL,
  imagen_portada VARCHAR(255) NULL,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  menu TINYINT(1) NOT NULL DEFAULT 1,
  footer TINYINT(1) NOT NULL DEFAULT 1,
  orden INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_web_categorias_slug (slug),
  KEY idx_web_categorias_activa_orden (activa, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_productos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  categoria_id BIGINT UNSIGNED NOT NULL,
  nombre VARCHAR(180) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  sku VARCHAR(80) NULL,
  descripcion_corta TEXT NULL,
  descripcion_larga MEDIUMTEXT NULL,
  precio DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  precio_oferta DECIMAL(12,2) NULL,
  stock INT NOT NULL DEFAULT 0,
  tallas VARCHAR(255) NULL,
  colores VARCHAR(255) NULL,
  imagen_principal VARCHAR(255) NULL,
  destacado TINYINT(1) NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  orden INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_web_productos_slug (slug),
  UNIQUE KEY uk_web_productos_sku (sku),
  KEY idx_web_productos_categoria_activo (categoria_id, activo),
  KEY idx_web_productos_destacado (destacado),
  CONSTRAINT fk_web_productos_categoria
    FOREIGN KEY (categoria_id) REFERENCES web_categorias(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_web_productos_precios CHECK (precio >= 0 AND (precio_oferta IS NULL OR precio_oferta >= 0)),
  CONSTRAINT chk_web_productos_stock CHECK (stock >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_producto_imagenes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  producto_id BIGINT UNSIGNED NOT NULL,
  imagen_url VARCHAR(255) NOT NULL,
  alt_text VARCHAR(180) NULL,
  orden INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_web_producto_imagenes_producto_orden (producto_id, orden),
  CONSTRAINT fk_web_producto_imagenes_producto
    FOREIGN KEY (producto_id) REFERENCES web_productos(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_clientes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL,
  telefono VARCHAR(30) NULL,
  password_hash VARCHAR(255) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  ultimo_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_web_clientes_email (email),
  KEY idx_web_clientes_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_direcciones (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id BIGINT UNSIGNED NOT NULL,
  alias VARCHAR(80) NULL,
  nombre_recibe VARCHAR(160) NOT NULL,
  telefono_recibe VARCHAR(30) NULL,
  calle VARCHAR(180) NOT NULL,
  numero_ext VARCHAR(20) NULL,
  numero_int VARCHAR(20) NULL,
  colonia VARCHAR(120) NOT NULL,
  ciudad VARCHAR(120) NOT NULL,
  estado VARCHAR(120) NOT NULL,
  codigo_postal VARCHAR(15) NOT NULL,
  pais VARCHAR(80) NOT NULL DEFAULT 'México',
  referencia TEXT NULL,
  es_predeterminada TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_web_direcciones_cliente (cliente_id),
  KEY idx_web_direcciones_predeterminada (cliente_id, es_predeterminada),
  CONSTRAINT fk_web_direcciones_cliente
    FOREIGN KEY (cliente_id) REFERENCES web_clientes(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_favoritos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id BIGINT UNSIGNED NOT NULL,
  producto_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_web_favoritos_cliente_producto (cliente_id, producto_id),
  KEY idx_web_favoritos_producto (producto_id),
  CONSTRAINT fk_web_favoritos_cliente
    FOREIGN KEY (cliente_id) REFERENCES web_clientes(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_web_favoritos_producto
    FOREIGN KEY (producto_id) REFERENCES web_productos(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_carritos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id BIGINT UNSIGNED NULL,
  session_token VARCHAR(120) NULL,
  estado ENUM('activo','convertido','abandonado') NOT NULL DEFAULT 'activo',
  moneda CHAR(3) NOT NULL DEFAULT 'MXN',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_web_carritos_session_token (session_token),
  KEY idx_web_carritos_cliente_estado (cliente_id, estado),
  KEY idx_web_carritos_estado_updated (estado, updated_at),
  CONSTRAINT fk_web_carritos_cliente
    FOREIGN KEY (cliente_id) REFERENCES web_clientes(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_carrito_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  carrito_id BIGINT UNSIGNED NOT NULL,
  producto_id BIGINT UNSIGNED NOT NULL,
  cantidad INT UNSIGNED NOT NULL DEFAULT 1,
  precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  talla VARCHAR(60) NULL,
  color VARCHAR(60) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_web_carrito_item_unico (carrito_id, producto_id, talla, color),
  KEY idx_web_carrito_items_producto (producto_id),
  CONSTRAINT fk_web_carrito_items_carrito
    FOREIGN KEY (carrito_id) REFERENCES web_carritos(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_web_carrito_items_producto
    FOREIGN KEY (producto_id) REFERENCES web_productos(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_web_carrito_items_cantidad CHECK (cantidad > 0),
  CONSTRAINT chk_web_carrito_items_precio CHECK (precio_unitario >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_cupones (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(60) NOT NULL,
  tipo_descuento ENUM('porcentaje','monto_fijo') NOT NULL,
  valor DECIMAL(12,2) NOT NULL,
  monto_minimo DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  usos_maximos INT UNSIGNED NULL,
  usos_actuales INT UNSIGNED NOT NULL DEFAULT 0,
  fecha_inicio DATETIME NULL,
  fecha_fin DATETIME NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_web_cupones_codigo (codigo),
  KEY idx_web_cupones_activo_fechas (activo, fecha_inicio, fecha_fin),
  CONSTRAINT chk_web_cupones_valor CHECK (valor >= 0),
  CONSTRAINT chk_web_cupones_minimo CHECK (monto_minimo >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_pedidos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  folio VARCHAR(40) NOT NULL,
  cliente_id BIGINT UNSIGNED NULL,
  direccion_id BIGINT UNSIGNED NULL,
  cupon_id BIGINT UNSIGNED NULL,
  carrito_id BIGINT UNSIGNED NULL,
  estado ENUM('pendiente_pago','pagado_autorizado','por_surtir','listo_entrega','entregado','cancelado') NOT NULL DEFAULT 'pendiente_pago',
  moneda CHAR(3) NOT NULL DEFAULT 'MXN',
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  descuento DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  costo_envio DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  nombre_cliente VARCHAR(160) NOT NULL,
  email_cliente VARCHAR(190) NULL,
  telefono_cliente VARCHAR(30) NULL,
  direccion_envio TEXT NULL,
  notas_cliente TEXT NULL,
  referencia_pago VARCHAR(120) NULL,
  pagado_at DATETIME NULL,
  cancelado_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_web_pedidos_folio (folio),
  KEY idx_web_pedidos_estado_created (estado, created_at),
  KEY idx_web_pedidos_cliente (cliente_id),
  KEY idx_web_pedidos_cupon (cupon_id),
  CONSTRAINT fk_web_pedidos_cliente
    FOREIGN KEY (cliente_id) REFERENCES web_clientes(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_web_pedidos_direccion
    FOREIGN KEY (direccion_id) REFERENCES web_direcciones(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_web_pedidos_cupon
    FOREIGN KEY (cupon_id) REFERENCES web_cupones(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_web_pedidos_carrito
    FOREIGN KEY (carrito_id) REFERENCES web_carritos(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_web_pedidos_totales CHECK (subtotal >= 0 AND descuento >= 0 AND costo_envio >= 0 AND total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_pedido_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pedido_id BIGINT UNSIGNED NOT NULL,
  producto_id BIGINT UNSIGNED NULL,
  sku VARCHAR(80) NULL,
  nombre_producto VARCHAR(180) NOT NULL,
  cantidad INT UNSIGNED NOT NULL DEFAULT 1,
  precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  talla VARCHAR(60) NULL,
  color VARCHAR(60) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_web_pedido_items_pedido (pedido_id),
  KEY idx_web_pedido_items_producto (producto_id),
  CONSTRAINT fk_web_pedido_items_pedido
    FOREIGN KEY (pedido_id) REFERENCES web_pedidos(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_web_pedido_items_producto
    FOREIGN KEY (producto_id) REFERENCES web_productos(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_web_pedido_items_cantidad CHECK (cantidad > 0),
  CONSTRAINT chk_web_pedido_items_montos CHECK (precio_unitario >= 0 AND subtotal >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_pagos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pedido_id BIGINT UNSIGNED NOT NULL,
  proveedor VARCHAR(60) NOT NULL,
  metodo VARCHAR(60) NOT NULL,
  estado ENUM('pendiente','autorizado','rechazado','reembolsado','cancelado') NOT NULL DEFAULT 'pendiente',
  monto DECIMAL(12,2) NOT NULL,
  moneda CHAR(3) NOT NULL DEFAULT 'MXN',
  referencia_externa VARCHAR(190) NULL,
  payload_json JSON NULL,
  pagado_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_web_pagos_pedido_estado (pedido_id, estado),
  KEY idx_web_pagos_referencia (referencia_externa),
  CONSTRAINT fk_web_pagos_pedido
    FOREIGN KEY (pedido_id) REFERENCES web_pedidos(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT chk_web_pagos_monto CHECK (monto >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_contactos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(160) NOT NULL,
  whatsapp VARCHAR(30) NULL,
  correo VARCHAR(190) NULL,
  tipo_consulta VARCHAR(80) NOT NULL,
  mensaje TEXT NOT NULL,
  ip_origen VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  estado ENUM('nuevo','atendido','cerrado') NOT NULL DEFAULT 'nuevo',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_web_contactos_estado_created (estado, created_at),
  KEY idx_web_contactos_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_banners (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(140) NOT NULL,
  posicion VARCHAR(80) NOT NULL,
  titulo VARCHAR(180) NULL,
  subtitulo VARCHAR(255) NULL,
  imagen_url VARCHAR(255) NOT NULL,
  enlace_url VARCHAR(255) NULL,
  texto_boton VARCHAR(80) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  orden INT NOT NULL DEFAULT 0,
  fecha_inicio DATETIME NULL,
  fecha_fin DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_web_banners_posicion_activo_orden (posicion, activo, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_reset_tokens (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id BIGINT UNSIGNED NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expira_at DATETIME NOT NULL,
  usado_at DATETIME NULL,
  ip_solicitud VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_web_reset_tokens_hash (token_hash),
  KEY idx_web_reset_tokens_cliente_expira (cliente_id, expira_at),
  CONSTRAINT fk_web_reset_tokens_cliente
    FOREIGN KEY (cliente_id) REFERENCES web_clientes(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
