/* =========================================================
   AllTicket · checkout
   - muestra el panel del medio de pago elegido
   - valida la tarjeta en el navegador (Luhn, vencimiento, CVV)
   - los datos de la tarjeta NO se envían (esos inputs no tienen "name")
========================================================= */
(function () {
    'use strict';

    var form = document.getElementById('form-pago');
    if (!form) { return; }

    var $ = function (id) { return document.getElementById(id); };
    var errBox = $('pay-error');

    function showError(msg) {
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

    // ---- tarjeta ----
    var num = $('cc-num'), name = $('cc-name'), exp = $('cc-exp'), cvv = $('cc-cvv'), dni = $('cc-dni');

    function soloDigitos(s) { return s.replace(/\D/g, ''); }

    function marca(d) {
        if (/^4/.test(d)) { return 'Visa'; }
        if (/^(5[1-5]|2[2-7])/.test(d)) { return 'Mastercard'; }
        if (/^3[47]/.test(d)) { return 'American Express'; }
        return 'Tarjeta';
    }

    function luhn(d) {
        var sum = 0, alt = false;
        for (var i = d.length - 1; i >= 0; i--) {
            var n = parseInt(d.charAt(i), 10);
            if (alt) { n *= 2; if (n > 9) { n -= 9; } }
            sum += n;
            alt = !alt;
        }
        return d.length > 0 && sum % 10 === 0;
    }

    num.addEventListener('input', function () {
        var d = soloDigitos(num.value).slice(0, 16);
        var amex = /^3[47]/.test(d);
        num.value = amex
            ? d.replace(/^(\d{0,4})(\d{0,6})(\d{0,5}).*/, function (m, a, b, c) { return [a, b, c].filter(Boolean).join(' '); })
            : d.replace(/(\d{4})(?=\d)/g, '$1 ');
        $('cc-brand').textContent = marca(d);
        $('cc-prev-num').textContent = (num.value || '•••• •••• •••• ••••');
    });

    name.addEventListener('input', function () {
        $('cc-prev-name').textContent = name.value.toUpperCase() || 'NOMBRE APELLIDO';
    });

    exp.addEventListener('input', function () {
        var d = soloDigitos(exp.value).slice(0, 4);
        exp.value = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d;
        $('cc-prev-exp').textContent = exp.value || 'MM/AA';
    });

    cvv.addEventListener('input', function () { cvv.value = soloDigitos(cvv.value).slice(0, 4); });
    dni.addEventListener('input', function () { dni.value = soloDigitos(dni.value).slice(0, 9); });

    function errorTarjeta() {
        var d = soloDigitos(num.value);
        var largo = /^3[47]/.test(d) ? 15 : 16;

        if (d.length !== largo || !luhn(d)) { return 'El número de tarjeta no es válido.'; }
        if (name.value.trim().length < 3) { return 'Ingresá el nombre del titular.'; }

        var m = /^(\d{2})\/(\d{2})$/.exec(exp.value);
        if (!m || +m[1] < 1 || +m[1] > 12) { return 'El vencimiento no es válido.'; }
        var hoy = new Date();
        var finMes = new Date(2000 + (+m[2]), +m[1], 0, 23, 59, 59);
        if (finMes < hoy) { return 'La tarjeta está vencida.'; }

        var cvvLargo = /^3[47]/.test(d) ? 4 : 3;
        if (cvv.value.length !== cvvLargo) { return 'El código de seguridad debe tener ' + cvvLargo + ' dígitos.'; }
        if (dni.value.length < 7) { return 'Ingresá el DNI del titular.'; }
        return '';
    }

    // ---- enviar ----
    form.addEventListener('submit', function (ev) {
        var elegido = form.querySelector('input[name="payment_method"]:checked');

        if (!elegido) {
            ev.preventDefault();
            showError('Elegí un medio de pago.');
            return;
        }
        if (elegido.value === 'tarjeta') {
            var e = errorTarjeta();
            if (e) { ev.preventDefault(); showError(e); return; }
        }
        if (!$('terminos').checked) {
            ev.preventDefault();
            showError('Tenés que aceptar los términos y condiciones.');
            return;
        }
        showError('');
    });
})();