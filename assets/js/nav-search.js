// =============================================
// BUSCADOR DEL HEADER (versión simple)
// Va en /assets/js/nav-search.js
// Reemplaza al archivo anterior. Ya no necesita suggest.php
// =============================================


// ---------------------------------------------
// 1. LISTA DE EVENTOS
// Esta lista la arma el layout.php con los eventos
// de la base de datos (variable eventosAllticket).
// Si por algún motivo no existe, usamos una lista vacía.
// ---------------------------------------------
var eventos = [];

if (typeof eventosAllticket !== "undefined") {
    eventos = eventosAllticket;
}


// ---------------------------------------------
// 2. BUSCAMOS LOS ELEMENTOS DE LA PÁGINA
// ---------------------------------------------
var formulario = document.getElementById("navSearch");
var campo = document.getElementById("navSearchInput");
var botonLupa = document.getElementById("navSearchToggle");
var cajaSugerencias = document.getElementById("navSearchSuggest");


// ---------------------------------------------
// 3. FUNCIONES
// ---------------------------------------------

// Abre el buscador (muestra el campo y pone el cursor)
function abrirBuscador() {
    formulario.classList.add("is-open");
    campo.focus();
}

// Cierra el buscador y borra las sugerencias
function cerrarBuscador() {
    formulario.classList.remove("is-open");
    ocultarSugerencias();
}

// Borra y esconde la cajita de sugerencias
function ocultarSugerencias() {
    cajaSugerencias.innerHTML = "";
    cajaSugerencias.hidden = true;
}

// Dice si el buscador está abierto (devuelve true o false)
function estaAbierto() {
    return formulario.classList.contains("is-open");
}

// Va a la página de resultados y baja directo a la sección de eventos
function buscar(texto) {
    window.location.href = "/src/views/index.php?q=" + encodeURIComponent(texto) + "#eventos";
}

// Recibe lo que escribió la persona y devuelve
// una lista con los eventos que coinciden
function buscarCoincidencias(texto) {
    var resultados = [];
    texto = texto.toLowerCase();

    for (var i = 0; i < eventos.length; i++) {
        var nombre = eventos[i].toLowerCase();

        if (nombre.includes(texto)) {
            resultados.push(eventos[i]);
        }
    }

    return resultados;
}

// Dibuja las sugerencias debajo del campo
function mostrarSugerencias(lista, texto) {
    cajaSugerencias.innerHTML = "";

    // Si no hay nada que mostrar
    if (lista.length === 0) {
        var vacio = document.createElement("div");
        vacio.className = "suggest-empty";
        vacio.textContent = "No encontramos \"" + texto + "\"";
        cajaSugerencias.appendChild(vacio);
        cajaSugerencias.hidden = false;
        return;
    }

    // Si hay resultados, creamos un link por cada uno
    for (var i = 0; i < lista.length; i++) {
        var link = document.createElement("a");
        link.className = "suggest-item";
        link.href = "/src/views/index.php?q=" + encodeURIComponent(lista[i]) + "#eventos";
        link.textContent = lista[i];

        cajaSugerencias.appendChild(link);
    }

    cajaSugerencias.hidden = false;
}


// ---------------------------------------------
// 4. EVENTOS (qué pasa cuando el usuario hace algo)
// ---------------------------------------------

// Cuando toca la lupa
botonLupa.addEventListener("click", function () {

    if (estaAbierto() === false) {
        // Estaba cerrado: lo abrimos
        abrirBuscador();
    } else if (campo.value.trim() !== "") {
        // Estaba abierto y escribió algo: buscamos
        buscar(campo.value.trim());
    } else {
        // Estaba abierto y vacío: lo cerramos
        cerrarBuscador();
    }

});

// Cuando aprieta Enter (se envía el formulario)
formulario.addEventListener("submit", function (evento) {
    evento.preventDefault();

    var texto = campo.value.trim();
    if (texto !== "") {
        buscar(texto);
    }
});

// Cuando escribe una letra
campo.addEventListener("input", function () {
    var texto = campo.value.trim();

    // Con menos de 2 letras no mostramos nada
    if (texto.length < 2) {
        ocultarSugerencias();
        return;
    }

    var coincidencias = buscarCoincidencias(texto);
    mostrarSugerencias(coincidencias, texto);
});

// Cuando aprieta una tecla (Escape cierra)
campo.addEventListener("keydown", function (evento) {
    if (evento.key === "Escape") {
        cerrarBuscador();
    }
});

// Cuando hace click en cualquier parte de la página
document.addEventListener("click", function (evento) {
    var clickAfuera = !formulario.contains(evento.target);

    if (clickAfuera) {
        cerrarBuscador();
    }
});