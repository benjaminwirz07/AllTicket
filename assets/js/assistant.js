(function () {
    'use strict';

    // Referencias al panel, formulario y controles que componen el chat flotante.
    var panel = document.getElementById('chatbotPanel');
    var launcher = document.getElementById('chatbotLauncher');
    var closeButton = document.getElementById('chatbotClose');
    var log = document.getElementById('chatbotLog');
    var form = document.getElementById('chatbotForm');
    var input = document.getElementById('chatbotInput');
    var sendButton = document.getElementById('chatbotSend');
    var errorBox = document.getElementById('chatbotError');
    var csrfToken = document.getElementById('chatbotCsrf').value;
    var historyLoaded = false;
    var sending = false;

    // Inserta mensajes como texto y mantiene los accesos rápidos al final del historial.
    function addMessage(text, sender) {
        var message = document.createElement('div');
        message.className = 'chatbot-message chatbot-message--' + sender;
        message.textContent = text;
        log.appendChild(message);
        var quickAccess = log.querySelector('.chatbot-quick-access');
        if (quickAccess) {
            log.appendChild(quickAccess);
        }
        log.scrollTop = log.scrollHeight;
    }

    // Construye una sola vez los botones de preguntas frecuentes del asistente.
    function addQuickAccess() {
        var existingQuickAccess = log.querySelector('.chatbot-quick-access');
        if (existingQuickAccess) {
            log.appendChild(existingQuickAccess);
            log.scrollTop = log.scrollHeight;
            return;
        }

        var quickAccess = document.createElement('section');
        quickAccess.className = 'chatbot-quick-access';
        quickAccess.setAttribute('aria-label', 'Accesos rápidos');

        var heading = document.createElement('h2');
        heading.className = 'chatbot-quick-access__heading';
        heading.textContent = 'Accesos rápidos';
        quickAccess.appendChild(heading);

        var actions = [
            { label: 'Próximos recitales', message: '¿Qué recitales próximos hay?', icon: 'bi-calendar-event' },
            { label: 'Cómo comprar entradas', message: '¿Cómo puedo comprar entradas?', icon: 'bi-ticket-perforated' },
            { label: 'Últimas noticias', message: 'Quiero ver las últimas noticias', icon: 'bi-newspaper' },
            { label: 'Ayuda y contacto', message: 'Necesito ayuda y contacto', icon: 'bi-question-circle' }
        ];
        var buttons = document.createElement('div');
        buttons.className = 'chatbot-quick-access__buttons';

        actions.forEach(function (action) {
            var button = document.createElement('button');
            button.className = 'chatbot-quick-access__button';
            button.type = 'button';
            button.dataset.message = action.message;

            var icon = document.createElement('i');
            icon.className = 'bi ' + action.icon;
            icon.setAttribute('aria-hidden', 'true');

            var label = document.createElement('span');
            label.textContent = action.label;

            var arrow = document.createElement('i');
            arrow.className = 'bi bi-chevron-right';
            arrow.setAttribute('aria-hidden', 'true');

            button.append(icon, label, arrow);
            buttons.appendChild(button);
        });

        quickAccess.appendChild(buttons);
        log.appendChild(quickAccess);
        log.scrollTop = log.scrollHeight;
    }

    // Expone errores de red o validación en el contenedor accesible del chat.
    function showError(text) {
        errorBox.textContent = text;
        errorBox.hidden = !text;
    }

    // Convierte respuestas JSON y centraliza el mensaje alternativo ante fallos del servidor.
    async function readResponse(response, fallback) {
        var data;
        try {
            data = await response.json();
        } catch (error) {
            throw new Error(fallback);
        }
        if (!response.ok) {
            throw new Error(data.error || fallback);
        }
        return data;
    }

    // Carga el historial de sesión y habilita el envío después de una respuesta válida.
    async function loadHistory() {
        if (historyLoaded) {
            return;
        }

        var response = await fetch('/src/controllers/assistant/message.php', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        });
        var data = await readResponse(response, 'No se pudo cargar el chat.');

        if (data.messages.length === 0) {
            addMessage('¡Hola! Soy el asistente de AllTicket. Preguntame por recitales, entradas, compras o noticias.', 'assistant');
        } else {
            data.messages.forEach(function (message) {
                addMessage(message.message, message.sender);
            });
        }

        addQuickAccess();
        historyLoaded = true;
        input.disabled = false;
        sendButton.disabled = false;
        input.focus();
    }

    // Abre el panel y recupera el historial solo la primera vez.
    async function openChat() {
        panel.hidden = false;
        launcher.setAttribute('aria-expanded', 'true');
        if (!historyLoaded) {
            input.disabled = true;
            sendButton.disabled = true;
            showError('');
            try {
                await loadHistory();
            } catch (error) {
                showError(error.message);
            }
        } else {
            input.focus();
        }
    }

    // Cierra el panel y devuelve el foco al botón que lo abrió.
    function closeChat() {
        panel.hidden = true;
        launcher.setAttribute('aria-expanded', 'false');
        launcher.focus();
    }

    // Alterna la apertura del chat y conecta sus controles de cierre y accesos rápidos.
    launcher.addEventListener('click', function () {
        if (panel.hidden) {
            openChat();
        } else {
            closeChat();
        }
    });
    closeButton.addEventListener('click', closeChat);

    log.addEventListener('click', function (event) {
        var button = event.target.closest('.chatbot-quick-access__button');
        if (!button || sending) {
            return;
        }
        input.value = button.dataset.message;
        form.requestSubmit();
    });

    // Envía un único mensaje por vez y restablece los controles incluso si la petición falla.
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        var message = input.value.trim();
        if (sending || message === '') {
            return;
        }

        sending = true;
        input.value = '';
        input.disabled = true;
        sendButton.disabled = true;
        showError('');
        addMessage(message, 'user');

        try {
            // Codifica el mensaje y el token CSRF como datos de formulario.
            var body = new URLSearchParams({
                mensaje: message,
                csrf_token: csrfToken
            });
            var response = await fetch('/src/controllers/assistant/message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    Accept: 'application/json'
                },
                credentials: 'same-origin',
                body: body.toString()
            });
            var data = await readResponse(response, 'No se pudo enviar el mensaje.');

            addMessage(data.reply, 'assistant');
        } catch (error) {
            showError(error.message);
        } finally {
            sending = false;
            input.disabled = false;
            sendButton.disabled = false;
            input.focus();
        }
    });
})();
