// ============================================================
// AllTicket - pagina del recital
//   1. Cuenta regresiva
//   2. Elegir la entrada (butacas o campo)
//   3. Mapa y lugares cercanos (se carga al acercarse)
//   4. Menu de secciones y barra de compra
// ============================================================

var datos = document.getElementById('datos-show');
var puedeComprar = (datos.dataset.puedeComprar == 'si');
var logueado = (datos.dataset.logueado == 'si');
var urlLogin = datos.dataset.loginUrl;
var claveGuardado = 'seleccion-' + datos.dataset.concierto;

var MAXIMO = 6;       // maximo de entradas por compra
var COLUMNAS = 20;    // butacas por fila
var PASILLO = 10;     // el pasillo esta despues de esta columna


// ============================================================
// 1. CUENTA REGRESIVA
// ============================================================

function ponerCuentaRegresiva() {

    var caja = document.getElementById('countdown');
    if (caja == null) {
        return;
    }

    var fechaShow = new Date(datos.dataset.fecha).getTime();

    var cajaDias = caja.querySelector('[data-cd="d"]');
    var cajaHoras = caja.querySelector('[data-cd="h"]');
    var cajaMin = caja.querySelector('[data-cd="m"]');
    var cajaSeg = caja.querySelector('[data-cd="s"]');

    function conCero(numero) {
        if (numero < 10) {
            return '0' + numero;
        }
        return '' + numero;
    }

    function actualizar() {
        var falta = fechaShow - new Date().getTime();
        if (falta < 0) {
            falta = 0;
        }

        var segundosTotales = Math.floor(falta / 1000);
        cajaDias.textContent = Math.floor(segundosTotales / 86400);
        cajaHoras.textContent = conCero(Math.floor((segundosTotales % 86400) / 3600));
        cajaMin.textContent = conCero(Math.floor((segundosTotales % 3600) / 60));
        cajaSeg.textContent = conCero(segundosTotales % 60);
    }

    actualizar();
    setInterval(actualizar, 1000);
}


// ============================================================
// 2. ELEGIR LA ENTRADA
// ============================================================

var formulario = document.getElementById('form-compra');

var panelVacio = document.getElementById('panel-empty');
var panelButacas = document.getElementById('panel-seats');
var panelCampo = document.getElementById('panel-field');
var grilla = document.getElementById('seat-grid');
var listaButacas = document.getElementById('seat-chips');
var textoResumen = document.getElementById('sum-text');
var cajaError = document.getElementById('form-error');
var textoCantidad = document.getElementById('qty-out');
var tituloPaso2 = document.getElementById('step2-title');
var ayudaPaso2 = document.getElementById('step2-hint');

// Hay dos totales y dos botones (resumen de escritorio y barra del celular)
var totales = document.querySelectorAll('.js-total');
var botonesComprar = document.querySelectorAll('.js-buy');

var entrada = null;     // la entrada que eligio el usuario
var butacas = [];       // las butacas que eligio: ["3-12", "3-13"]
var cantidad = 1;       // cantidad (solo para entradas sin butaca)


function mostrarError(mensaje) {
    cajaError.textContent = mensaje;
    cajaError.hidden = (mensaje == '');
}

function formatoPrecio(numero) {
    return '$ ' + Math.round(numero).toLocaleString('es-AR');
}

function cuantasEntradas() {
    if (entrada.numerada) {
        return butacas.length;
    }
    return cantidad;
}

function maximoPosible() {
    if (entrada.disponibles < MAXIMO) {
        return entrada.disponibles;
    }
    return MAXIMO;
}


// ---------- Guardar la seleccion (para no perderla al iniciar sesion) ----------

function guardarSeleccion() {
    try {
        if (entrada == null) {
            sessionStorage.removeItem(claveGuardado);
            return;
        }
        sessionStorage.setItem(claveGuardado, JSON.stringify({
            ticket: entrada.id,
            butacas: butacas,
            cantidad: cantidad
        }));
    } catch (error) {
        // si el navegador no deja guardar, seguimos igual
    }
}

function recuperarSeleccion() {

    var guardado = null;
    try {
        guardado = JSON.parse(sessionStorage.getItem(claveGuardado));
    } catch (error) {
        guardado = null;
    }
    if (guardado == null) {
        return;
    }

    var radio = document.querySelector('input[name="ticket_id"][value="' + guardado.ticket + '"]');
    if (radio == null || radio.disabled) {
        return;
    }

    radio.checked = true;
    alElegirEntrada({ target: radio }, true);

    if (entrada.numerada) {
        // Volvemos a marcar solo las butacas que siguen libres
        for (var i = 0; i < guardado.butacas.length; i++) {
            var casilla = grilla.querySelector('[data-codigo="' + guardado.butacas[i] + '"]');
            if (casilla != null && casilla.disabled == false && butacas.length < maximoPosible()) {
                casilla.checked = true;
                butacas.push(guardado.butacas[i]);
            }
        }
    } else if (guardado.cantidad >= 1) {
        cantidad = Math.min(guardado.cantidad, maximoPosible());
    }

    actualizarResumen();
}


// ---------- Mapa de butacas ----------

function dibujarButacas() {

    var total = entrada.stock;
    var filas = Math.ceil(total / COLUMNAS);
    var contador = 0;

    grilla.innerHTML = '';

    for (var fila = 1; fila <= filas; fila++) {

        var filaDiv = document.createElement('div');
        filaDiv.className = 'ct-seatrow';

        var etiqueta = document.createElement('span');
        etiqueta.className = 'ct-seatrow__lbl';
        etiqueta.textContent = 'F' + fila;
        filaDiv.appendChild(etiqueta);

        for (var columna = 1; columna <= COLUMNAS; columna++) {

            if (contador >= total) {
                break;
            }

            var codigo = fila + '-' + columna;

            var boton = document.createElement('input');
            boton.type = 'checkbox';
            boton.className = 'ct-seat';
            boton.setAttribute('data-codigo', codigo);

            // Cada butaca dice quien es (lectores de pantalla y mouse)
            var nombreButaca = 'Fila ' + fila + ', butaca ' + columna;
            boton.setAttribute('aria-label', nombreButaca);
            boton.title = nombreButaca;

            if (columna == PASILLO) {
                boton.className = 'ct-seat is-gap';
            }

            if (entrada.ocupadas.indexOf(codigo) != -1) {
                boton.disabled = true;
                boton.title = nombreButaca + ' (ocupada)';
            }

            filaDiv.appendChild(boton);
            contador = contador + 1;
        }

        grilla.appendChild(filaDiv);
    }
}


// Busca las mejores butacas libres: las filas de adelante, todas juntas
function elegirMejores() {

    var n = parseInt(document.getElementById('auto-qty').value);
    if (n > maximoPosible()) {
        n = maximoPosible();
    }

    // Limpiamos lo que habia elegido
    var marcadas = grilla.querySelectorAll('.ct-seat:checked');
    for (var i = 0; i < marcadas.length; i++) {
        marcadas[i].checked = false;
    }
    butacas = [];

    var filas = grilla.children;

    for (var f = 0; f < filas.length; f++) {

        var asientos = filas[f].querySelectorAll('.ct-seat');
        var seguidas = 0;

        for (var c = 0; c < asientos.length; c++) {

            // Una racha se corta si esta ocupada o si cruza el pasillo
            if (asientos[c].disabled || c == PASILLO) {
                seguidas = 0;
            }
            if (asientos[c].disabled == false) {
                seguidas = seguidas + 1;
            }

            if (seguidas == n) {
                for (var k = c - n + 1; k <= c; k++) {
                    asientos[k].checked = true;
                    butacas.push(asientos[k].getAttribute('data-codigo'));
                }
                mostrarError('');
                guardarSeleccion();
                actualizarResumen();
                return;
            }
        }
    }

    mostrarError('No hay ' + n + ' butacas juntas en esta ubicación. Probá con menos o elegilas en el mapa.');
    actualizarResumen();
}


// ---------- Resumen ----------

function actualizarResumen() {

    listaButacas.innerHTML = '';
    for (var i = 0; i < butacas.length; i++) {
        var partes = butacas[i].split('-');
        var chip = document.createElement('span');
        chip.className = 'ct-chip';
        chip.textContent = 'Fila ' + partes[0] + ' · Butaca ' + partes[1];
        listaButacas.appendChild(chip);
    }

    textoCantidad.textContent = cantidad;

    var total = 0;
    var textoBoton = 'Continuar con el pago';
    var habilitado = false;

    if (entrada == null) {
        textoResumen.textContent = 'Elegí un tipo de entrada para continuar.';
    } else {
        var n = cuantasEntradas();

        if (entrada.numerada && n == 0) {
            textoResumen.textContent = entrada.tipo + ': elegí al menos una butaca.';
        } else if (n == 1) {
            textoResumen.textContent = '1 entrada · ' + entrada.tipo;
        } else {
            textoResumen.textContent = n + ' entradas · ' + entrada.tipo;
        }

        total = entrada.precio * n;
        habilitado = (puedeComprar && n >= 1);

        if (logueado == false) {
            textoBoton = 'Iniciar sesión para pagar';
        }
    }

    for (var t = 0; t < totales.length; t++) {
        totales[t].textContent = formatoPrecio(total);
    }
    for (var b = 0; b < botonesComprar.length; b++) {
        botonesComprar[b].disabled = !habilitado;
        botonesComprar[b].querySelector('.js-buy-label').textContent = textoBoton;
    }
}


// ---------- Eventos ----------

function alElegirEntrada(evento, sinScroll) {

    var radio = evento.target;

    entrada = {
        id: radio.value,
        tipo: radio.dataset.tipo,
        precio: parseFloat(radio.dataset.precio),
        disponibles: parseInt(radio.dataset.disponibles),
        stock: parseInt(radio.dataset.stock),
        numerada: (radio.dataset.numerada == 'si'),
        ocupadas: radio.dataset.ocupadas.split(',')
    };

    butacas = [];
    cantidad = 1;
    mostrarError('');

    panelVacio.hidden = true;
    panelButacas.hidden = true;
    panelCampo.hidden = true;

    var panelActivo = null;

    if (entrada.numerada) {
        tituloPaso2.textContent = 'Elegí tus butacas';
        ayudaPaso2.textContent = 'Tocá las butacas libres. Podés elegir hasta ' + maximoPosible() + '.';
        dibujarButacas();
        panelButacas.hidden = false;
        panelActivo = panelButacas;
    } else {
        tituloPaso2.textContent = 'Elegí la cantidad';
        ayudaPaso2.textContent = 'Hasta ' + maximoPosible() + ' entradas por compra.';
        panelCampo.hidden = false;
        panelActivo = panelCampo;
    }

    // En el celular llevamos a la persona al paso 2
    if (sinScroll != true && window.innerWidth < 992) {
        panelActivo.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    guardarSeleccion();
    actualizarResumen();
}


function alTocarButaca(evento) {

    var casilla = evento.target;

    if (casilla.classList.contains('ct-seat') == false) {
        return;
    }

    var codigo = casilla.getAttribute('data-codigo');

    if (casilla.checked) {
        if (butacas.length >= maximoPosible()) {
            casilla.checked = false;
            mostrarError('Podés elegir hasta ' + maximoPosible() + ' butacas por compra.');
            return;
        }
        butacas.push(codigo);
    } else {
        var posicion = butacas.indexOf(codigo);
        if (posicion != -1) {
            butacas.splice(posicion, 1);
        }
    }

    mostrarError('');
    guardarSeleccion();
    actualizarResumen();
}


function bajarCantidad() {
    if (cantidad > 1) {
        cantidad = cantidad - 1;
        mostrarError('');
        guardarSeleccion();
        actualizarResumen();
    }
}

function subirCantidad() {
    if (cantidad < maximoPosible()) {
        cantidad = cantidad + 1;
        mostrarError('');
    } else {
        mostrarError('Máximo ' + maximoPosible() + ' entradas por compra.');
    }
    guardarSeleccion();
    actualizarResumen();
}


function alEnviar(evento) {

    if (entrada == null) {
        evento.preventDefault();
        mostrarError('Elegí un tipo de entrada.');
        return;
    }

    if (entrada.numerada && butacas.length == 0) {
        evento.preventDefault();
        mostrarError('Elegí al menos una butaca.');
        return;
    }

    // Sin sesion: guardamos la seleccion y mandamos al login
    // (el PHP ya guardo esta pagina para volver despues)
    if (logueado == false) {
        evento.preventDefault();
        guardarSeleccion();
        window.location.href = urlLogin;
        return;
    }

    document.getElementById('f-qty').value = cuantasEntradas();

    if (entrada.numerada) {
        document.getElementById('f-seats').value = butacas.join(',');
    } else {
        document.getElementById('f-seats').value = '';
    }
}


function prepararEntradas() {

    if (formulario == null) {
        return;
    }

    var radios = document.querySelectorAll('input[name="ticket_id"]');
    for (var i = 0; i < radios.length; i++) {
        radios[i].addEventListener('change', alElegirEntrada);
    }

    grilla.addEventListener('change', alTocarButaca);
    document.getElementById('auto-pick').addEventListener('click', elegirMejores);
    document.getElementById('qty-minus').addEventListener('click', bajarCantidad);
    document.getElementById('qty-plus').addEventListener('click', subirCantidad);
    formulario.addEventListener('submit', alEnviar);

    actualizarResumen();
    recuperarSeleccion();
}


// ============================================================
// 3. MAPA (OpenStreetMap) Y LUGARES CERCANOS (Overpass)
// ============================================================

var mapa = null;

function buscarDireccion(texto, alTerminar) {

    var direccion = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(texto);

    fetch(direccion)
        .then(function (respuesta) {
            return respuesta.json();
        })
        .then(function (lista) {
            if (lista.length > 0) {
                alTerminar({ lat: parseFloat(lista[0].lat), lon: parseFloat(lista[0].lon) });
            } else {
                alTerminar(null);
            }
        })
        .catch(function () {
            alTerminar(null);
        });
}

function calcularDistancia(lat1, lon1, lat2, lon2) {
    var radio = 6371000;
    var grados = Math.PI / 180;
    var dLat = (lat2 - lat1) * grados;
    var dLon = (lon2 - lon1) * grados;
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * grados) * Math.cos(lat2 * grados) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return Math.round(radio * c);
}

function buscarGrupo(etiquetas) {
    if (etiquetas.railway == 'subway_entrance' || etiquetas.railway == 'station') {
        return 'transporte';
    }
    if (etiquetas.highway == 'bus_stop') {
        return 'colectivo';
    }
    if (etiquetas.amenity == 'parking') {
        return 'estacionamiento';
    }
    if (etiquetas.amenity == 'restaurant' || etiquetas.amenity == 'cafe' || etiquetas.amenity == 'bar') {
        return 'comida';
    }
    return '';
}

function mostrarGrupo(titulo, icono, color, lugares) {

    if (lugares.length == 0) {
        return;
    }

    var lista = document.getElementById('near-list');

    lugares.sort(function (a, b) {
        return a.distancia - b.distancia;
    });

    var h3 = document.createElement('h3');
    h3.innerHTML = '<i class="bi ' + icono + ' me-1"></i>' + titulo;
    lista.appendChild(h3);

    var ul = document.createElement('ul');
    ul.className = 'list-unstyled mb-0';

    for (var i = 0; i < lugares.length && i < 4; i++) {

        var lugar = lugares[i];

        var li = document.createElement('li');
        var nombre = document.createElement('span');
        nombre.textContent = lugar.nombre;
        var metros = document.createElement('span');
        metros.textContent = lugar.distancia + ' m';
        li.appendChild(nombre);
        li.appendChild(metros);
        ul.appendChild(li);

        var punto = L.circleMarker([lugar.lat, lugar.lon], {
            radius: 6,
            color: '#0b0b14',
            weight: 1.5,
            fillColor: color,
            fillOpacity: 1
        });
        punto.addTo(mapa);
        punto.bindPopup(lugar.nombre + ' (' + lugar.distancia + ' m)');
    }

    lista.appendChild(ul);
}

function buscarLugaresCercanos(lat, lon) {

    var consulta = '[out:json][timeout:25];(' +
        'node(around:900,' + lat + ',' + lon + ')[railway=subway_entrance];' +
        'node(around:900,' + lat + ',' + lon + ')[railway=station];' +
        'node(around:500,' + lat + ',' + lon + ')[highway=bus_stop];' +
        'node(around:600,' + lat + ',' + lon + ')[amenity=parking];' +
        'node(around:400,' + lat + ',' + lon + ')[amenity=restaurant];' +
        'node(around:400,' + lat + ',' + lon + ')[amenity=cafe];' +
        'node(around:400,' + lat + ',' + lon + ')[amenity=bar];' +
        ');out 150;';

    fetch('https://overpass-api.de/api/interpreter', {
        method: 'POST',
        body: 'data=' + encodeURIComponent(consulta),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
        .then(function (respuesta) {
            return respuesta.json();
        })
        .then(function (resultado) {

            var transporte = [];
            var colectivo = [];
            var estacionamiento = [];
            var comida = [];

            for (var i = 0; i < resultado.elements.length; i++) {

                var cosa = resultado.elements[i];
                var etiquetas = cosa.tags;
                if (etiquetas == undefined) {
                    continue;
                }

                var grupo = buscarGrupo(etiquetas);
                if (grupo == '') {
                    continue;
                }

                var nombre = etiquetas.name;
                if (nombre == undefined) {
                    if (grupo == 'colectivo') {
                        nombre = 'Parada de colectivo';
                    } else if (grupo == 'estacionamiento') {
                        nombre = 'Estacionamiento';
                    } else {
                        continue;
                    }
                }

                var lugar = {
                    nombre: nombre,
                    lat: cosa.lat,
                    lon: cosa.lon,
                    distancia: calcularDistancia(lat, lon, cosa.lat, cosa.lon)
                };

                if (grupo == 'transporte') { transporte.push(lugar); }
                if (grupo == 'colectivo') { colectivo.push(lugar); }
                if (grupo == 'estacionamiento') { estacionamiento.push(lugar); }
                if (grupo == 'comida') { comida.push(lugar); }
            }

            var lista = document.getElementById('near-list');
            lista.innerHTML = '';

            mostrarGrupo('Subte y tren', 'bi-train-front', '#22d3ee', transporte);
            mostrarGrupo('Colectivos', 'bi-bus-front', '#60a5fa', colectivo);
            mostrarGrupo('Estacionamiento', 'bi-p-square', '#fbbf24', estacionamiento);
            mostrarGrupo('Para comer y tomar algo', 'bi-cup-hot', '#f472b6', comida);

            if (lista.innerHTML == '') {
                lista.innerHTML = '<p class="small text-secondary-custom mt-2 mb-0">No encontramos lugares cargados cerca.</p>';
            }
        })
        .catch(function () {
            document.getElementById('near-list').innerHTML =
                '<p class="small text-secondary-custom mt-2 mb-0">No pudimos cargar los lugares cercanos ahora.</p>';
        });
}

function mostrarLugarEnMapa(posicion, esExacta) {

    var estado = document.getElementById('map-status');

    if (posicion == null) {
        estado.textContent = 'No pudimos ubicar el lugar en el mapa. Probá con la dirección: ' +
            datos.dataset.direccion + ', ' + datos.dataset.ciudad + '.';
        document.getElementById('near-list').innerHTML = '';
        return;
    }

    if (esExacta) {
        mapa.setView([posicion.lat, posicion.lon], 16);
        estado.textContent = 'Ubicación según OpenStreetMap.';
    } else {
        mapa.setView([posicion.lat, posicion.lon], 12);
        estado.textContent = 'Ubicación aproximada (mostramos la ciudad).';
    }

    var marcador = L.marker([posicion.lat, posicion.lon]);
    marcador.addTo(mapa);
    marcador.bindPopup(datos.dataset.lugar + ' - ' + datos.dataset.direccion);

    var link = document.getElementById('map-link');
    link.href = 'https://www.openstreetmap.org/directions?to=' + posicion.lat + '%2C' + posicion.lon;
    link.hidden = false;

    if (esExacta) {
        buscarLugaresCercanos(posicion.lat, posicion.lon);
    } else {
        document.getElementById('near-list').innerHTML = '';
    }
}

function prepararMapa() {

    if (document.getElementById('venue-map') == null || typeof L == 'undefined') {
        return;
    }

    mapa = L.map('venue-map', { scrollWheelZoom: false });
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(mapa);
    mapa.setView([-34.6037, -58.3816], 12);

    var lugar = datos.dataset.lugar;
    var direccion = datos.dataset.direccion;
    var ciudad = datos.dataset.ciudad;

    buscarDireccion(direccion + ', ' + ciudad + ', Argentina', function (primero) {

        if (primero != null) {
            mostrarLugarEnMapa(primero, true);
            return;
        }

        setTimeout(function () {
            buscarDireccion(lugar + ', ' + ciudad + ', Argentina', function (segundo) {

                if (segundo != null) {
                    mostrarLugarEnMapa(segundo, true);
                    return;
                }

                setTimeout(function () {
                    buscarDireccion(ciudad + ', Argentina', function (tercero) {
                        mostrarLugarEnMapa(tercero, false);
                    });
                }, 1100);
            });
        }, 1100);
    });
}

// El mapa hace pedidos a servicios externos: lo armamos recien
// cuando la persona se acerca a la seccion "Como llegar".
function prepararMapaDiferido() {

    var seccion = document.getElementById('como-llegar');
    if (seccion == null) {
        return;
    }

    if (typeof IntersectionObserver == 'undefined') {
        prepararMapa();
        return;
    }

    var observador = new IntersectionObserver(function (entradas) {
        if (entradas[0].isIntersecting) {
            observador.disconnect();
            prepararMapa();
        }
    }, { rootMargin: '400px 0px' });

    observador.observe(seccion);
}


// ============================================================
// 4. MENU DE SECCIONES Y BARRA DE COMPRA
// ============================================================

function prepararMenu() {

    var enlaces = document.querySelectorAll('.ct-subnav a');
    if (enlaces.length == 0 || typeof IntersectionObserver == 'undefined') {
        return;
    }

    function marcar(id) {
        for (var i = 0; i < enlaces.length; i++) {
            if (enlaces[i].getAttribute('href') == '#' + id) {
                enlaces[i].classList.add('is-active');
            } else {
                enlaces[i].classList.remove('is-active');
            }
        }
    }

    var observador = new IntersectionObserver(function (entradas) {
        for (var i = 0; i < entradas.length; i++) {
            if (entradas[i].isIntersecting) {
                marcar(entradas[i].target.id);
            }
        }
    }, { rootMargin: '-40% 0px -55% 0px' });

    for (var i = 0; i < enlaces.length; i++) {
        var id = enlaces[i].getAttribute('href').substring(1);
        var seccion = document.getElementById(id);
        if (seccion != null) {
            observador.observe(seccion);
        }
    }
}

// La barra de abajo (celular) solo se ve mientras estas en la seccion de entradas
function prepararBarraCompra() {

    var barra = document.getElementById('buybar');
    var seccion = document.getElementById('entradas');
    if (barra == null || seccion == null || typeof IntersectionObserver == 'undefined') {
        return;
    }

    var observador = new IntersectionObserver(function (entradas) {
        if (entradas[0].isIntersecting) {
            barra.classList.add('is-visible');
        } else {
            barra.classList.remove('is-visible');
        }
    });

    observador.observe(seccion);
}


// ============================================================
// ARRANCAMOS TODO
// ============================================================

ponerCuentaRegresiva();
prepararEntradas();
prepararMapaDiferido();
prepararMenu();
prepararBarraCompra();