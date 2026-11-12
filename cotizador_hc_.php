<?php
/**
 * Plugin Name: WooCommerce Cotizador Online
 * Description: Un plugin para generar cotizaciones online utilizando WooCommerce.
 * Version: 1.0
 * Author: [Ignacio Montero]
 */

// Evita el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Carga de scripts y estilos
function cotizador_online_enqueue_scripts() {
    wp_enqueue_style('cotizador-online-css', plugin_dir_url(__FILE__) . 'css/style.css');
    wp_enqueue_script('cotizador-online-js', plugin_dir_url(__FILE__) . 'js/scripts.js', array('jquery'), null, true);

    // Pasa la URL de admin-ajax.php a JavaScript
    wp_localize_script('cotizador-online-js', 'cotizadorOnline', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'cotizador_online_enqueue_scripts');

// Shortcode para el formulario de cotización
function cotizador_online_form_shortcode() {
    ob_start();
    ?>
    <form id="cotizador-form">
        <label for="name">Nombre:</label>
        <input type="text" id="name" name="name" required>
        
        <label for="email">Correo Electrónico:</label>
        <input type="email" id="email" name="email" required>
        
        <label for="product">Producto:</label>
        <select id="product" name="product">
            <?php
            // Consulta los productos desde WooCommerce
            $products = wc_get_products(array(
                'limit' => -1, // Muestra todos los productos
            ));

            foreach ($products as $product) {
                echo '<option value="' . $product->get_id() . '">' . $product->get_name() . '</option>';
            }
            ?>
        </select>

        <label for="quantity">Cantidad:</label>
        <input type="number" id="quantity" name="quantity" required>

        <button type="submit">Solicitar Cotización</button>
    </form>
    <div id="cotizacion-result"></div>
    <?php
    return ob_get_clean();
}




add_shortcode('cotizador_online_form', 'cotizador_online_form_shortcode');

// Manejo de la solicitud AJAX



function cotizador_online_handle_request() {
    if (isset($_POST['name'], $_POST['email'], $_POST['product'], $_POST['quantity'])) {
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $product_id = intval($_POST['product']);
        $quantity = intval($_POST['quantity']);

        $product = wc_get_product($product_id);
        if ($product) {
            // Precio total calculado
            $total_price = $product->get_price() * $quantity;

            // Envía el correo de solicitud de cotización
            $subject = 'Nueva solicitud de cotización';
            $message = "Nombre: $name\nCorreo: $email\nProducto: " . $product->get_name() . "\nCantidad: $quantity\nTotal Estimado: " . wc_price($total_price);
            
            // Correos a enviar
            $recipients = array(get_option('admin_email')); // Agrega aquí correos adicionales si es necesario

            wp_mail($recipients, $subject, $message);

            // Respuesta AJAX
            wp_send_json_success(array(
                'message' => 'Tu solicitud de cotización ha sido enviada. Pronto nos pondremos en contacto contigo.',
                'total_price' => wc_price($total_price)
            ));
        }
    }
    wp_die();
}
add_action('wp_ajax_nopriv_cotizador_online', 'cotizador_online_handle_request');
add_action('wp_ajax_cotizador_online', 'cotizador_online_handle_request');
