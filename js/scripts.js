jQuery(function ($) {
    $('.wfq-form').on('submit', function (event) {
        event.preventDefault();
        var form = $(this);
        if (form.data('sending')) return;
        var button = form.find('button[type="submit"]');
        var result = form.find('.wfq-result');
        form.data('sending', true).attr('aria-busy', 'true');
        button.prop('disabled', true);
        result.text('Enviando solicitud…');
        $.ajax({
            url: form.attr('action'), type: 'POST', dataType: 'json',
            data: form.serialize(), timeout: 30000
        }).done(function (response) {
            if (response && response.success && response.data) {
                result.text(response.data.message + ' Importe estimado: ' + response.data.total_price);
            } else {
                result.text(response && response.data && response.data.message || 'No pudimos procesar la solicitud.');
            }
        }).fail(function (xhr, status) {
            var response = xhr.responseJSON;
            result.text(response && response.data && response.data.message ||
                (status === 'timeout' ? 'La respuesta tardó demasiado. El envío podría haberse procesado; consulta con la tienda antes de repetirlo.' : 'No pudimos confirmar el envío. Comprueba tu conexión antes de volver a intentarlo.'));
        }).always(function () {
            form.data('sending', false).removeAttr('aria-busy');
            button.prop('disabled', false);
        });
    });
});
