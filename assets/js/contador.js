// =============================================
// CONTADORES ANIMADOS (los números +50K y 300+)
// Va en /assets/js/contador.js
//
// Cómo se usa en el HTML:
//   <div class="hero-stat-value"
//        data-contador="50" data-prefijo="+" data-sufijo="K">+50K</div>
//
//   data-contador = el número final
//   data-prefijo  = lo que va antes (opcional)
//   data-sufijo   = lo que va después (opcional)
// =============================================


// Hace que UN número cuente desde 0 hasta su meta
function animarContador(elemento) {
    var meta = Number(elemento.dataset.contador);
    var prefijo = elemento.dataset.prefijo || "";
    var sufijo = elemento.dataset.sufijo || "";
    var duracion = 1500;   // milisegundos (1,5 segundos)
    var inicio = null;

    function paso(ahora) {
        // La primera vez guardamos la hora de inicio
        if (inicio === null) {
            inicio = ahora;
        }

        // progreso va de 0 (empezó) a 1 (terminó)
        var progreso = (ahora - inicio) / duracion;
        if (progreso > 1) {
            progreso = 1;
        }

        // Hace que arranque rápido y frene al final
        var suave = 1 - Math.pow(1 - progreso, 3);

        var numero = Math.round(meta * suave);
        elemento.textContent = prefijo + numero + sufijo;

        // Si todavía no terminó, pedimos el siguiente cuadro
        if (progreso < 1) {
            requestAnimationFrame(paso);
        }
    }

    requestAnimationFrame(paso);
}


// Busca todos los números con data-contador
var contadores = document.querySelectorAll("[data-contador]");

// Si la persona pidió "menos movimiento", mostramos el número final y listo
var menosMovimiento = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

if (menosMovimiento) {
    for (var i = 0; i < contadores.length; i++) {
        var c = contadores[i];
        c.textContent = (c.dataset.prefijo || "") + c.dataset.contador + (c.dataset.sufijo || "");
    }
} else {
    // El "vigilante" avisa cuando un número aparece en pantalla
    var vigilante = new IntersectionObserver(function (entradas) {
        for (var j = 0; j < entradas.length; j++) {
            if (entradas[j].isIntersecting) {
                animarContador(entradas[j].target);
                vigilante.unobserve(entradas[j].target);   // solo una vez
            }
        }
    });

    for (var k = 0; k < contadores.length; k++) {
        vigilante.observe(contadores[k]);
    }
}