(() => {
    let printTarget = null;

    const resetPrint = () => {
        document.body.classList.remove('ticket-printing');
        if (printTarget) {
            printTarget.classList.remove('is-print-target');
            printTarget = null;
        }
    };

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
