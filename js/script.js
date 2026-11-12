jQuery(document).ready(function($) {
    $('#cotizador-form').on('submit', function(e) {
        e.preventDefault(); // Evita que el formulario se envíe de forma tradicional y recargue la página

        var formData = {
            action: 'cotizador_online',
            name: $('#name').val(),
            email: $('#email').val(),
            product: $('#product').val(),
            quantity: $('#quantity').val(),
        };

        $.ajax({
            url: cotizadorOnline.ajax_url, // Esta variable debe configurarse en PHP para apuntar al admin-ajax.php
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#cotizacion-result').html('<p>' + response.data.message + '</p><p>Total estimado: ' + response.data.total_price + '</p>');
                } else {
                    $('#cotizacion-result').html('<p>Hubo un problema al procesar la solicitud. Inténtalo nuevamente.</p>');
                }
            },
            error: function() {
                $('#cotizacion-result').html('<p>Error en la solicitud. Inténtalo nuevamente.</p>');
            }
        });
    });
});
