// =============================================
// BUSCADOR DEL HEADER (versión simple)
// Va en /assets/js/nav-search.js
// Reemplaza al archivo anterior. Ya no necesita suggest.php
// =============================================


// ---------------------------------------------
// 1. LISTA DE ARTISTAS Y EVENTOS
// Esta lista la arma el layout.php con los recitales próximos.
// Si por algún motivo no existe, usamos una lista vacía.
// ---------------------------------------------
var sugerenciasDisponibles = [];

if (typeof sugerenciasAllticket !== "undefined") {
    sugerenciasDisponibles = sugerenciasAllticket;
}


// ---------------------------------------------
// 2. BUSCAMOS LOS ELEMENTOS DE LA PÁGINA
// ---------------------------------------------
var formulario = document.getElementById("navSearch");
var campo = document.getElementById("navSearchInput");
var botonLupa = document.getElementById("navSearchToggle");
var cajaSugerencias = document.getElementById("navSearchSuggest");
var indiceSugerenciaActiva = -1;
var maxSugerencias = 8;


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
    campo.setAttribute("aria-expanded", "false");
    campo.removeAttribute("aria-activedescendant");
    indiceSugerenciaActiva = -1;
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
// una lista con los artistas y eventos que coinciden
function buscarCoincidencias(texto) {
    var resultados = [];
    texto = texto.toLowerCase();

    for (var i = 0; i < sugerenciasDisponibles.length; i++) {
        var sugerencia = sugerenciasDisponibles[i];
        var nombre = sugerencia.nombre.toLowerCase();

        if (nombre.includes(texto)) {
            resultados.push(sugerencia);
        }
    }

    resultados.sort(function (a, b) {
        var nombreA = a.nombre.toLowerCase();
        var nombreB = b.nombre.toLowerCase();
        var posicionA = nombreA.indexOf(texto);
        var posicionB = nombreB.indexOf(texto);

        if (posicionA !== posicionB) {
            return posicionA - posicionB;
        }

        return nombreA.localeCompare(nombreB, "es");
    });

    return resultados;
}

function activarSugerencia(indice) {
    var sugerencias = cajaSugerencias.querySelectorAll(".suggest-item");

    if (sugerencias.length === 0) {
        return;
    }

    indiceSugerenciaActiva = (indice + sugerencias.length) % sugerencias.length;

    for (var i = 0; i < sugerencias.length; i++) {
        var activa = i === indiceSugerenciaActiva;
        sugerencias[i].classList.toggle("is-active", activa);
        sugerencias[i].setAttribute("aria-selected", activa ? "true" : "false");
    }

    campo.setAttribute("aria-activedescendant", sugerencias[indiceSugerenciaActiva].id);
    sugerencias[indiceSugerenciaActiva].scrollIntoView({ block: "nearest" });
}

// Dibuja las sugerencias debajo del campo
function mostrarSugerencias(lista, texto) {
    cajaSugerencias.innerHTML = "";
    indiceSugerenciaActiva = -1;
    campo.removeAttribute("aria-activedescendant");
    lista = lista.slice(0, maxSugerencias);

    // Si no hay nada que mostrar
    if (lista.length === 0) {
        var vacio = document.createElement("div");
        vacio.className = "suggest-empty";
        vacio.textContent = "No encontramos \"" + texto + "\"";
        cajaSugerencias.appendChild(vacio);
        cajaSugerencias.hidden = false;
        campo.setAttribute("aria-expanded", "true");
        return;
    }

    // Si hay resultados, creamos un link por cada uno
    for (var i = 0; i < lista.length; i++) {
        var sugerencia = lista[i];
        var link = document.createElement("a");
        link.className = "suggest-item";
        link.id = "navSearchOption" + i;
        link.tabIndex = -1;
        link.setAttribute("role", "option");
        link.setAttribute("aria-selected", "false");
        link.href = "/src/views/index.php?q=" + encodeURIComponent(sugerencia.nombre) + "#eventos";

        var nombre = document.createElement("span");
        nombre.textContent = sugerencia.nombre;
        link.appendChild(nombre);

        var tipo = document.createElement("span");
        tipo.className = "suggest-type";
        tipo.textContent = sugerencia.tipo;
        link.appendChild(tipo);

        cajaSugerencias.appendChild(link);
    }

    cajaSugerencias.hidden = false;
    campo.setAttribute("aria-expanded", "true");
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
    } else if (evento.key === "ArrowDown" && !cajaSugerencias.hidden) {
        evento.preventDefault();
        activarSugerencia(indiceSugerenciaActiva + 1);
    } else if (evento.key === "ArrowUp" && !cajaSugerencias.hidden) {
        evento.preventDefault();
        activarSugerencia(indiceSugerenciaActiva < 0
            ? cajaSugerencias.querySelectorAll(".suggest-item").length - 1
            : indiceSugerenciaActiva - 1);
    } else if (evento.key === "Enter" && indiceSugerenciaActiva >= 0) {
        var sugerenciaActiva = cajaSugerencias.querySelectorAll(".suggest-item")[indiceSugerenciaActiva];
        if (sugerenciaActiva) {
            evento.preventDefault();
            sugerenciaActiva.click();
        }
    }
});

// Cuando hace click en cualquier parte de la página
document.addEventListener("click", function (evento) {
    var clickAfuera = !formulario.contains(evento.target);

    if (clickAfuera) {
        cerrarBuscador();
    }
});