-- Seed inicial mínimo para la web pública Suave Urban Studio
-- Ejecutar después de database/web_schema.sql

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Configuración base
INSERT INTO web_configuracion (clave, valor_texto, tipo, descripcion)
VALUES
  ('site_name', 'Suave Urban Studio', 'string', 'Nombre principal del sitio web público'),
  ('brand_name', 'Suave Urban Studio', 'string', 'Nombre de marca mostrado en la tienda pública'),
  ('footer_rights', '© 2026 Suave Urban Luxury. Todos los derechos reservados.', 'string', 'Texto legal del footer'),
  ('currency', 'MXN', 'string', 'Moneda principal de la tienda pública'),
  ('whatsapp', '', 'string', 'WhatsApp de contacto público'),
  ('business_hours', '', 'string', 'Horario comercial de atención'),
  ('logo', '', 'string', 'Ruta/URL de logo público')
ON DUPLICATE KEY UPDATE
  valor_texto = VALUES(valor_texto),
  tipo = VALUES(tipo),
  descripcion = VALUES(descripcion),
  updated_at = CURRENT_TIMESTAMP;

-- Categorías base
INSERT INTO web_categorias (nombre, slug, descripcion, activa, menu, footer, orden)
VALUES
  ('Playeras', 'playeras', 'Categoría inicial para playeras.', 1, 1, 1, 10),
  ('Sudaderas', 'sudaderas', 'Categoría inicial para sudaderas.', 1, 1, 1, 20),
  ('Personalizados', 'personalizados', 'Categoría para diseños personalizados.', 1, 1, 1, 30)
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre),
  descripcion = VALUES(descripcion),
  activa = VALUES(activa),
  menu = VALUES(menu),
  footer = VALUES(footer),
  orden = VALUES(orden),
  updated_at = CURRENT_TIMESTAMP;

-- Banner principal activo (sin imagen obligatoria)
INSERT INTO web_banners (
  nombre, posicion, titulo, subtitulo, imagen_url, enlace_url, texto_boton, activo, orden, fecha_inicio, fecha_fin
)
VALUES (
  'Banner principal inicio',
  'home_principal',
  'Suave Urban Studio',
  'Streetwear premium y colecciones exclusivas.',
  '',
  '/colecciones',
  'Ver colecciones',
  1,
  10,
  NULL,
  NULL
)
ON DUPLICATE KEY UPDATE
  titulo = VALUES(titulo),
  subtitulo = VALUES(subtitulo),
  imagen_url = VALUES(imagen_url),
  enlace_url = VALUES(enlace_url),
  texto_boton = VALUES(texto_boton),
  activo = VALUES(activo),
  orden = VALUES(orden),
  updated_at = CURRENT_TIMESTAMP;

-- Cupón de ejemplo inactivo
INSERT INTO web_cupones (
  codigo, tipo_descuento, valor, monto_minimo, usos_maximos, usos_actuales,
  fecha_inicio, fecha_fin, activo
)
VALUES (
  'BIENVENIDO10', 'porcentaje', 10.00, 0.00, NULL, 0, NULL, NULL, 0
)
ON DUPLICATE KEY UPDATE
  tipo_descuento = VALUES(tipo_descuento),
  valor = VALUES(valor),
  monto_minimo = VALUES(monto_minimo),
  activo = VALUES(activo),
  updated_at = CURRENT_TIMESTAMP;
