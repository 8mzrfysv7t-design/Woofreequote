# Cambios

## 1.1.0

- Identidad y descripción de Woofreequote como plugin gratuito.
- Validación de POST, nonce, campos escalares, correo, producto y cantidad.
- Restricción a productos simples, públicos, visibles y con precio válido.
- Escape de productos en HTML y mensajes del cliente insertados como texto.
- Campo trampa y límite básico de cinco intentos por IP cada diez minutos.
- Errores HTTP/JSON explícitos para dependencia ausente, datos inválidos, límite y fallo de correo.
- Correo en texto plano con Reply-To del cliente y estado de envío comprobado.
- Catálogo paginado de 20 productos y formularios con identificadores únicos.
- Assets cargados con el shortcode, CSS acotado al plugin y eliminación del JavaScript duplicado.
- Bloqueo de doble envío mientras la solicitud está pendiente, timeout y estado accesible.
- Pruebas de regresión y workflow de GitHub Actions.

## 1.0

Importación del código original, sin modificaciones funcionales.
