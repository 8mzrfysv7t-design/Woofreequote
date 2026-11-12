# Woofreequote

Código fuente del plugin **WooCommerce Cotizador Online**, versión declarada **1.0**, importado de `Web_Cotizador-Online_Fuente-Funcional.zip` el 11 de noviembre de 2026.

## Requisitos e instalación

Requiere WordPress, WooCommerce activo y productos configurados. No se han verificado versiones mínimas compatibles.

1. Copiar esta carpeta a `wp-content/plugins/woofreequote/`.
2. Activar **WooCommerce Cotizador Online** desde Plugins.
3. Insertar el shortcode `[cotizador_online_form]` en una página.
4. Comprobar el correo de administración de WordPress y la entrega de correo en un entorno de pruebas.

## Funcionamiento

El formulario recoge nombre, correo, producto y cantidad. Consulta los productos de WooCommerce y envía la solicitud por AJAX a WordPress. El servidor calcula precio por cantidad y usa `wp_mail()` para notificar al correo de administración configurado en WordPress. El envío efectivo depende de la configuración de correo del sitio.

No genera PDF ni guarda cotizaciones en una tabla propia. Este código no corresponde al plugin HC Quote Pro 1.5.4 revisado anteriormente.

## Archivos

- `cotizador_hc_.php`: plugin, shortcode y manejador AJAX.
- `css/style.css`: estilos originales.
- `js/scripts.js`: script cargado por el plugin.
- `js/script.js`: copia original conservada; no se carga desde el PHP.

## Estado y pendientes

Se conserva el código fuente original sin modificaciones funcionales. Se excluyeron metadatos de macOS. No se incluyen base de datos, credenciales, respaldos ni cotizaciones.

Antes de desplegar o ampliar su uso, revisar:

- Validación de correo, cantidad positiva y productos permitidos.
- Protección del endpoint público frente a abuso y verificación de solicitudes.
- Escape de los valores de productos al generar HTML.
- Comprobación del resultado de `wp_mail()`: actualmente devuelve éxito sin verificar el envío.
- Respuestas de error explícitas y control de dependencia de WooCommerce.
- Carga de todos los productos sin paginación y carga global de estilos/scripts.

La importación verifica integridad de archivos; no constituye una prueba funcional en WordPress ni un despliegue en producción.

## Licencia

El archivo fuente no declara una licencia. Este repositorio no añade una licencia ni concede derechos adicionales.
