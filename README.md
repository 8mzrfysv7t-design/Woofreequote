# Woofreequote

Deja que tus clientes pidan una cotización desde tu tienda WooCommerce. Eligen un producto, indican la cantidad y dejan su correo. Tú recibes la solicitud con el importe estimado y puedes responder directamente al cliente.

Woofreequote es gratuito. El plugin no cobra suscripciones ni comisiones, no necesita una cuenta externa y no incluye funciones de pago. Utiliza los productos de tu tienda y el correo configurado en WordPress.

## Qué puedes hacer

- Añadir el formulario a una página con `[cotizador_online_form]`.
- Recibir solicitudes de productos simples, publicados y visibles en catálogo y búsqueda.
- Mostrar una estimación calculada en el servidor con el precio del producto y la cantidad solicitada.
- Recorrer el catálogo en páginas de 20 productos.
- Responder al cliente desde el correo recibido mediante la cabecera Reply-To.

El cliente no necesita iniciar sesión. El formulario valida los datos y muestra un error si WordPress no acepta el envío del correo.

## Instalación

Necesitas WordPress y WooCommerce activo. Las pruebas automatizadas cubren la lógica del plugin con dobles de WordPress y WooCommerce; aún falta verificarlo en una instalación real antes de desplegarlo.

1. Descarga el repositorio y copia su carpeta a `wp-content/plugins/woofreequote/`, o instala un ZIP de esa carpeta desde WordPress.
2. Activa **Woofreequote**.
3. Añade `[cotizador_online_form]` a una página.
4. Revisa el correo de administración en los ajustes de WordPress.
5. Envía una solicitud de prueba y comprueba su recepción, el importe y la respuesta al cliente.

El shortcode original sigue funcionando. El archivo principal mantiene el nombre `cotizador_hc_.php` para conservar la ruta del plugin. Los estilos solo afectan al formulario y se cargan cuando se muestra el shortcode.

## Alcance

La cantidad admitida es un número entero de 1 a 10000. Los productos deben tener un precio numérico, incluido cero. Se excluyen productos privados, protegidos con contraseña, ocultos y tipos que requieren selección de opciones, como los variables. Los filtros de visibilidad de WooCommerce también se respetan. Si una página del catálogo solo contiene productos excluidos, permite avanzar a la siguiente.

El importe es una estimación de precio por cantidad, según el precio almacenado en WooCommerce. No calcula envío, descuentos de carrito ni impuestos específicos del cliente. La tienda debe confirmar el importe final y la disponibilidad. No crea pedidos, archivos PDF ni un historial de cotizaciones.

`wp_mail()` aceptando el mensaje no garantiza que llegue a la bandeja de entrada. La entrega depende del correo del sitio; un servicio SMTP externo puede tener su propio coste. Ante un timeout, el formulario avisa de que el correo podría haberse procesado para evitar reintentos a ciegas.

## Protección y privacidad

Las solicitudes requieren POST y un nonce válido. El formulario incluye un campo trampa y un límite básico de cinco intentos válidos por dirección IP durante diez minutos. Cada intento admitido reinicia esa ventana, incluso si falla el correo. La IP se almacena temporalmente como HMAC con una clave del sitio; no se guarda en texto plano por este plugin.

El límite usa transients de WordPress y no es atómico: solicitudes simultáneas pueden superarlo. Un nonce público no autentica al visitante ni detiene bots por sí solo. En sitios con tráfico abusivo, aplica límites adicionales en el proxy o firewall. El plugin usa `REMOTE_ADDR` e ignora cabeceras reenviadas; detrás de un proxy varios visitantes pueden compartir límite.

Excluye la página del formulario de la caché HTML para evitar servir nonces vencidos. No hace falta excluir los archivos CSS y JavaScript. Los datos de contacto se envían al correo de administración; el plugin no los guarda en una tabla propia. La tienda debe informar de ese tratamiento en su política de privacidad.

## Desarrollo y comprobaciones

```sh
php -l cotizador_hc_.php
php tests/regression.php
php tests/no-woocommerce.php
node --check js/scripts.js
node tests/frontend.cjs
```

Las pruebas cubren validación, productos excluidos, manipulación del precio, escape HTML, paginación, límites de envío y fallo de correo. No envían correos reales. GitHub Actions ejecuta las comprobaciones en PHP 8.2, 8.3 y 8.4. Esa matriz no constituye una declaración de compatibilidad completa con WordPress, WooCommerce o temas concretos.

## Origen y licencia

Basado en el archivo `Web_Cotizador-Online_Fuente-Funcional.zip` entregado por Ignacio Montero. La versión actual es 1.1.0; los cambios están en [CHANGELOG.md](CHANGELOG.md).

La distribución se plantea sin coste. El código de origen no incluía una licencia y este repositorio todavía no concede una licencia de redistribución o modificación. La gratuidad no cambia esa situación. El repositorio sigue siendo privado.
