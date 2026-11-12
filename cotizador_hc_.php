<?php
/**
 * Plugin Name: Woofreequote
 * Description: Recibe solicitudes de cotización desde tu tienda WooCommerce. Gratis, sin suscripciones ni comisiones del plugin.
 * Version: 1.1.0
 * Author: Ignacio Montero
 * Requires Plugins: woocommerce
 */
if (!defined('ABSPATH')) { exit; }

function wfq_error($message, $status = 400) {
    wp_send_json_error(array('message' => $message), $status);
}

function wfq_ready() {
    return function_exists('wc_get_products') && function_exists('wc_get_product');
}

function wfq_allowed_product($product) {
    return $product && $product->get_status() === 'publish'
        && $product->is_type('simple') && $product->get_catalog_visibility() === 'visible'
        && $product->is_visible() && !post_password_required($product->get_id())
        && $product->get_price() !== '' && is_numeric($product->get_price())
        && is_finite((float) $product->get_price()) && (float) $product->get_price() >= 0;
}

function cotizador_online_form_shortcode() {
    if (!wfq_ready()) {
        return '<p>El formulario de cotización no está disponible en este momento.</p>';
    }
    wp_enqueue_style('woofreequote', plugin_dir_url(__FILE__) . 'css/style.css', array(), '1.1.0');
    wp_enqueue_script('woofreequote', plugin_dir_url(__FILE__) . 'js/scripts.js', array('jquery'), '1.1.0', true);
    $page = isset($_GET['wfq_page']) && is_string($_GET['wfq_page'])
        ? max(1, min(10000, (int) $_GET['wfq_page'])) : 1;
    $results = wc_get_products(array(
        'status' => 'publish', 'type' => 'simple', 'visibility' => 'visible',
        'limit' => 20, 'page' => $page, 'paginate' => true, 'orderby' => 'ID', 'order' => 'ASC',
    ));
    $id = wp_unique_id('wfq-') . '-';
    $available = array_filter($results->products, 'wfq_allowed_product');
    ob_start();
    // Shortcodes can render after wp_head, including inside page builders.
    wp_print_styles('woofreequote');
    ?>
    <section class="wfq">
        <form class="wfq-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post">
            <input type="hidden" name="action" value="cotizador_online">
            <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('woofreequote')); ?>">
            <label for="<?php echo esc_attr($id); ?>name">Nombre</label>
            <input id="<?php echo esc_attr($id); ?>name" name="name" maxlength="120" autocomplete="name" required>
            <label for="<?php echo esc_attr($id); ?>email">Correo electrónico</label>
            <input id="<?php echo esc_attr($id); ?>email" name="email" type="email" maxlength="254" autocomplete="email" required>
            <label for="<?php echo esc_attr($id); ?>product">Producto</label>
            <select id="<?php echo esc_attr($id); ?>product" name="product" required>
                <option value="">Selecciona un producto</option>
                <?php foreach ($available as $product) : ?>
                    <?php if (wfq_allowed_product($product)) : ?>
                        <option value="<?php echo esc_attr($product->get_id()); ?>"><?php echo esc_html($product->get_name()); ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <?php if (!$available) : ?><p>No hay productos disponibles en esta página. Revisa las otras páginas del catálogo.</p><?php endif; ?>
            <label for="<?php echo esc_attr($id); ?>quantity">Cantidad</label>
            <input id="<?php echo esc_attr($id); ?>quantity" name="quantity" type="number" min="1" max="10000" step="1" value="1" required>
            <div class="wfq-trap" aria-hidden="true">
                <label for="<?php echo esc_attr($id); ?>website">Deja este campo vacío</label>
                <input id="<?php echo esc_attr($id); ?>website" name="website" tabindex="-1" autocomplete="off">
            </div>
            <p>Usaremos tus datos para responder esta solicitud. El importe es una estimación; el total final puede incluir impuestos y envío.</p>
            <button type="submit">Solicitar cotización</button>
            <p class="wfq-result" role="status" aria-live="polite" aria-atomic="true"></p>
            <noscript>Activa JavaScript para enviar el formulario sin salir de esta página.</noscript>
        </form>
        <nav aria-label="Páginas de productos">
            <?php if ($page > 1) : ?><a href="<?php echo esc_url(add_query_arg('wfq_page', $page - 1)); ?>">Productos anteriores</a><?php endif; ?>
            <span><?php echo esc_html('Página ' . $page . ' de ' . max(1, $results->max_num_pages)); ?></span>
            <?php if ($page < $results->max_num_pages) : ?><a href="<?php echo esc_url(add_query_arg('wfq_page', $page + 1)); ?>">Más productos</a><?php endif; ?>
        </nav>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode('cotizador_online_form', 'cotizador_online_form_shortcode');

function cotizador_online_handle_request() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        wfq_error('Esta solicitud requiere POST.', 405);
    }
    if (!wfq_ready()) { wfq_error('El cotizador no está disponible.', 503); }
    foreach (array('nonce', 'name', 'email', 'product', 'quantity') as $field) {
        if (!isset($_POST[$field]) || !is_string($_POST[$field])) {
            wfq_error('Completa todos los campos del formulario.');
        }
    }
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'woofreequote')) {
        wfq_error('La sesión del formulario venció. Recarga la página e inténtalo otra vez.', 403);
    }
    if (isset($_POST['website']) && (!is_string($_POST['website']) || $_POST['website'] !== '')) {
        wfq_error('No se pudo procesar la solicitud.');
    }
    $name = sanitize_text_field(wp_unslash($_POST['name']));
    $email = trim(wp_unslash($_POST['email']));
    $product_id = filter_var($_POST['product'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => 10000)));
    if ($name === '' || strlen($name) > 480 || strlen($email) > 254 || !is_email($email) || !$product_id || !$quantity) {
        wfq_error('Revisa el nombre, correo, producto y cantidad (entre 1 y 10000).');
    }
    $product = wc_get_product($product_id);
    if (!wfq_allowed_product($product)) { wfq_error('Este producto no está disponible para cotizar.', 422); }
    $total = (float) $product->get_price() * $quantity;
    if (!is_finite($total)) { wfq_error('No se pudo calcular el importe.', 422); }
    $recipient = get_option('admin_email');
    if (!is_email($recipient)) { wfq_error('El correo de la tienda no está configurado.', 503); }
    // Basic abuse protection. Trust only the peer address, never client-supplied forwarding headers.
    $key = 'wfq_' . hash_hmac('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), wp_salt('nonce'));
    $attempts = (int) get_transient($key);
    if ($attempts >= 5) { wfq_error('Has enviado varias solicitudes. Espera 10 minutos e inténtalo otra vez.', 429); }
    set_transient($key, $attempts + 1, 10 * MINUTE_IN_SECONDS);
    $formatted = html_entity_decode(wp_strip_all_tags(wc_price($total)), ENT_QUOTES, 'UTF-8');
    $message = "Nombre: $name\nCorreo: $email\nProducto: " . wp_strip_all_tags($product->get_name())
        . "\nID del producto: $product_id\nCantidad: $quantity\nImporte estimado: $formatted\n"
        . 'Estimación basada en el precio del catálogo. Confirmar impuestos, envío y disponibilidad con el cliente.';
    $sent = wp_mail($recipient, 'Nueva solicitud de cotización', $message, array('Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $email));
    if (!$sent) { wfq_error('No pudimos enviar la solicitud. Inténtalo más tarde o contacta con la tienda.', 502); }
    wp_send_json_success(array('message' => 'Tu solicitud fue aceptada para envío. La tienda responderá al correo indicado.', 'total_price' => $formatted));
}
add_action('wp_ajax_nopriv_cotizador_online', 'cotizador_online_handle_request');
add_action('wp_ajax_cotizador_online', 'cotizador_online_handle_request');
