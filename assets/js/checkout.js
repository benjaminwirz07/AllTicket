/* Checkout de demostración: alterna opciones y evita envíos duplicados. */
(function () {
    'use strict';

    var form = document.getElementById('form-pago');
    if (!form) { return; }

    var errBox = document.getElementById('pay-error');

    function showError(msg) {
        if (!errBox) { return; }
        errBox.textContent = msg || '';
        errBox.hidden = !msg;
    }

    // ---- mostrar el panel del medio elegido ----
    form.addEventListener('change', function (ev) {
        if (ev.target.name !== 'payment_method') { return; }
        form.querySelectorAll('[data-panel]').forEach(function (p) {
            p.hidden = p.getAttribute('data-panel') !== ev.target.value;
        });
        showError('');
    });

    // ---- enviar (validación) ----
    form.addEventListener('submit', function (ev) {
        var elegido = form.querySelector('input[name="payment_method"]:checked');

        if (!elegido) {
            ev.preventDefault();
            showError('Elegí un medio de pago.');
            return;
        }
        var terminos = document.getElementById('terminos');
        if (terminos && !terminos.checked) {
            ev.preventDefault();
            showError('Tenés que aceptar los términos y condiciones.');
            return;
        }
        showError('');
    });

    // ---- evitar doble envío ----
    // Se registra DESPUÉS de la validación: solo actúa si el envío sigue en pie.
    form.addEventListener('submit', function (ev) {
        if (ev.defaultPrevented) { return; }          // la validación ya lo frenó
        if (form.dataset.enviando === '1') {          // segundo intento
            ev.preventDefault();
            return;
        }
        form.dataset.enviando = '1';
        var btn = form.querySelector('button[type="submit"]');
        if (btn) {
            // guardamos el HTML original para poder restaurarlo al volver con "atrás"
            if (btn.dataset.htmlOriginal == null) { btn.dataset.htmlOriginal = btn.innerHTML; }
            setTimeout(function () {
                btn.disabled = true;
                btn.classList.add('is-loading');
                btn.innerHTML = '<span class="loader me-2" role="status" aria-hidden="true"></span>Procesando...';
            }, 0);
        }
    });

    // si el navegador restaura la página al volver con "atrás", se rehabilita el botón
    window.addEventListener('pageshow', function (ev) {
        if (ev.persisted) {
            form.dataset.enviando = '';
            var btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('is-loading');
                if (btn.dataset.htmlOriginal) { btn.innerHTML = btn.dataset.htmlOriginal; }
            }
        }
    });
})();