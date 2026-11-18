(() => {
    // Guarda temporalmente el único bloque de entrada que se enviará a impresión.
    let printTarget = null;

    // Restaura las clases temporales utilizadas para limitar la impresión al ticket elegido.
    const resetPrint = () => {
        document.body.classList.remove('ticket-printing');
        if (printTarget) {
            printTarget.classList.remove('is-print-target');
            printTarget = null;
        }
    };

    // Genera los QR desde los datos de cada entrada o muestra un aviso si falta la librería.
    const qrElements = document.querySelectorAll('[data-ticket-qr]');
    if (qrElements.length > 0) {
        if (typeof window.QRCode !== 'function') {
            qrElements.forEach((element) => {
                element.textContent = 'No se pudo cargar el generador del QR. Revisá tu conexión a internet.';
            });
        } else {
            qrElements.forEach((element) => {
                const value = element.dataset.ticketQr;
                if (!value) return;

                new window.QRCode(element, {
                    text: value,
                    width: 180,
                    height: 180,
                    colorDark: '#121118',
                    colorLight: '#ffffff',
                    correctLevel: window.QRCode.CorrectLevel.M,
                });
            });
        }
    }

    // Imprime el ticket asociado al botón y limpia su estado al terminar o salir de la página.
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-ticket-print-button]');
        if (!button) return;

        const target = button.closest('[data-ticket-print]');
        if (!target || !target.querySelector('[data-ticket-qr] canvas, [data-ticket-qr] img')) {
            return;
        }

        resetPrint();
        printTarget = target;
        printTarget.classList.add('is-print-target');
        document.body.classList.add('ticket-printing');
        window.print();
    });

    window.addEventListener('afterprint', resetPrint);
    window.addEventListener('beforeunload', resetPrint);
})();
