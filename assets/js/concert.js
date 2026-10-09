// ============================================================
// AllTicket - pagina del recital
//   1. Cuenta regresiva
//   2. Elegir la entrada (butacas o campo)
//   3. Mapa y lugares cercanos
// ============================================================

// Datos que nos manda el PHP
var datos = document.getElementById('datos-show');
var puedeComprar = (datos.dataset.puedeComprar == 'si');

var MAXIMO = 6;       // maximo de entradas por compra
var COLUMNAS = 20;    // butacas por fila


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

    // Agrega un cero adelante: 5 -> 05
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
        var dias = Math.floor(segundosTotales / 86400);
        var horas = Math.floor((segundosTotales % 86400) / 3600);
        var minutos = Math.floor((segundosTotales % 3600) / 60);
        var segundos = segundosTotales % 60;

        cajaDias.textContent = dias;
        cajaHoras.textContent = conCero(horas);
        cajaMin.textContent = conCero(minutos);
        cajaSeg.textContent = conCero(segundos);
    }

    actualizar();
    setInterval(actualizar, 1000);
}


// ============================================================
// 2. ELEGIR LA ENTRADA
// ============================================================

var formulario = document.getElementById('form-compra');

var panelButacas = document.getElementById('panel-seats');
var panelCampo = document.getElementById('panel-field');
var grilla = document.getElementById('seat-grid');
var listaButacas = document.getElementById('seat-chips');
var textoResumen = document.getElementById('sum-text');
var textoTotal = document.getElementById('sum-total');
var botonComprar = document.getElementById('btn-buy');
var cajaError = document.getElementById('form-error');
var textoCantidad = document.getElementById('qty-out');

var entrada = null;     // la entrada que eligio el usuario
var butacas = [];       // las butacas que eligio: ["3-12", "3-13"]
var cantidad = 1;       // cantidad (solo para entradas sin butaca)


// Muestra un mensaje de error (o lo borra si va vacio)
function mostrarError(mensaje) {
    cajaError.textContent = mensaje;
    if (mensaje == '') {
        cajaError.hidden = true;
    } else {
        cajaError.hidden = false;
    }
}

// Escribe un precio lindo: 30000 -> $ 30.000
function formatoPrecio(numero) {
    return '$ ' + Math.round(numero).toLocaleString('es-AR');
}

// Cuantas entradas va a comprar
function cuantasEntradas() {
    if (entrada.numerada) {
        return butacas.length;
    }
    return cantidad;
}

// Maximo que puede comprar de esta entrada
function maximoPosible() {
    if (entrada.disponibles < MAXIMO) {
        return entrada.disponibles;
    }
    return MAXIMO;
}


// Dibuja todas las butacas en pantalla
function dibujarButacas() {

    var total = entrada.stock;
    var filas = Math.ceil(total / COLUMNAS);
    var contador = 0;

    grilla.innerHTML = '';

    for (var fila = 1; fila <= filas; fila++) {

        var filaDiv = document.createElement('div');
        filaDiv.className = 'ct-seatrow';

        // Numero de fila a la izquierda
        var etiqueta = document.createElement('span');
        etiqueta.className = 'ct-seatrow__lbl';
        etiqueta.textContent = 'F' + fila;
        filaDiv.appendChild(etiqueta);

        for (var columna = 1; columna <= COLUMNAS; columna++) {

            if (contador >= total) {
                break;
            }

            var codigo = fila + '-' + columna;

            var boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'ct-seat';
            boton.setAttribute('data-codigo', codigo);

            // Pasillo en el medio
            if (columna == 10) {
                boton.className = 'ct-seat is-gap';
            }

            // Si ya esta vendida, no se puede tocar
            if (entrada.ocupadas.indexOf(codigo) != -1) {
                boton.disabled = true;
            }

            filaDiv.appendChild(boton);
            contador = contador + 1;
        }

        grilla.appendChild(filaDiv);
    }
}


// Actualiza la lista de butacas, el total y el boton
function actualizarResumen() {

    // Lista de butacas elegidas
    listaButacas.innerHTML = '';
    for (var i = 0; i < butacas.length; i++) {
        var partes = butacas[i].split('-');
        var etiqueta = document.createElement('span');
        etiqueta.className = 'ct-chip';
        etiqueta.textContent = 'Fila ' + partes[0] + ' · Butaca ' + partes[1];
        listaButacas.appendChild(etiqueta);
    }

    textoCantidad.textContent = cantidad;

    // Si todavia no eligio ninguna entrada
    if (entrada == null) {
        textoResumen.textContent = 'Elegí un tipo de entrada para continuar.';
        textoTotal.textContent = formatoPrecio(0);
        botonComprar.disabled = true;
        return;
    }

    var n = cuantasEntradas();

    if (entrada.numerada && n == 0) {
        textoResumen.textContent = entrada.tipo + ' · elegí al menos una butaca';
    } else if (n == 1) {
        textoResumen.textContent = '1 entrada · ' + entrada.tipo;
    } else {
        textoResumen.textContent = n + ' entradas · ' + entrada.tipo;
    }

    textoTotal.textContent = formatoPrecio(entrada.precio * n);

    if (puedeComprar && n >= 1) {
        botonComprar.disabled = false;
    } else {
        botonComprar.disabled = true;
    }
}


// Cuando el usuario elige un tipo de entrada
function alElegirEntrada(evento) {

    var radio = evento.target;

    // Guardamos los datos de la entrada elegida
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

    panelButacas.hidden = true;
    panelCampo.hidden = true;

    if (entrada.numerada) {
        dibujarButacas();
        panelButacas.hidden = false;
    } else {
        panelCampo.hidden = false;
    }

    actualizarResumen();
}


// Cuando el usuario toca una butaca
function alTocarButaca(evento) {

    var boton = evento.target;

    if (boton.classList.contains('ct-seat') == false) {
        return;
    }
    if (boton.disabled) {
        return;
    }

    var codigo = boton.getAttribute('data-codigo');
    var posicion = butacas.indexOf(codigo);

    if (posicion != -1) {
        // Ya estaba elegida: la sacamos
        butacas.splice(posicion, 1);
        boton.classList.remove('is-selected');
        mostrarError('');
    } else {
        // No estaba elegida: la agregamos (si no se paso del maximo)
        if (butacas.length >= maximoPosible()) {
            mostrarError('Podés elegir hasta ' + maximoPosible() + ' butacas por compra.');
            return;
        }
        butacas.push(codigo);
        boton.classList.add('is-selected');
        mostrarError('');
    }

    actualizarResumen();
}


// Botones + y - de la cantidad (entradas de campo)
function bajarCantidad() {
    if (cantidad > 1) {
        cantidad = cantidad - 1;
        mostrarError('');
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
    actualizarResumen();
}


// Cuando toca "Continuar con el pago"
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

    // Pasamos la cantidad y las butacas al formulario
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

    grilla.addEventListener('click', alTocarButaca);
    document.getElementById('qty-minus').addEventListener('click', bajarCantidad);
    document.getElementById('qty-plus').addEventListener('click', subirCantidad);
    formulario.addEventListener('submit', alEnviar);

    actualizarResumen();
}


// ============================================================
// 3. MAPA (OpenStreetMap) Y LUGARES CERCANOS (Overpass)
//    No hace falta ninguna clave: OpenStreetMap es libre.
// ============================================================

var mapa = null;

// Busca una direccion en Nominatim. Cuando termina llama a "alTerminar"
// con {lat, lon} o con null si no encontro nada.
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


// Distancia en metros entre dos puntos
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


// Dice a que grupo pertenece un lugar de OpenStreetMap
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


// Muestra un grupo de lugares en la lista de la derecha
function mostrarGrupo(titulo, icono, color, lugares) {

    if (lugares.length == 0) {
        return;
    }

    var lista = document.getElementById('near-list');

    // Los mas cercanos primero
    lugares.sort(function (a, b) {
        return a.distancia - b.distancia;
    });

    var h3 = document.createElement('h3');
    h3.innerHTML = '<i class="bi ' + icono + ' me-1"></i>' + titulo;
    lista.appendChild(h3);

    var ul = document.createElement('ul');
    ul.className = 'list-unstyled mb-0';

    // Mostramos como maximo 4 de cada grupo
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

        // Puntito en el mapa
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


// Pide a Overpass los lugares que hay cerca
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

                // Si no tiene nombre, le ponemos uno generico
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


// Pone el marcador del lugar y busca lo que hay cerca
function mostrarLugarEnMapa(posicion, esExacta) {

    var estado = document.getElementById('map-status');

    if (posicion == null) {
        estado.textContent = 'No pudimos ubicar el lugar en el mapa.';
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

    // Link para ir en OpenStreetMap
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

    // Creamos el mapa (empieza en Buenos Aires)
    mapa = L.map('venue-map', { scrollWheelZoom: false });
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(mapa);
    mapa.setView([-34.6037, -58.3816], 12);

    var lugar = datos.dataset.lugar;
    var direccion = datos.dataset.direccion;
    var ciudad = datos.dataset.ciudad;

    // Primero probamos con la direccion
    buscarDireccion(direccion + ', ' + ciudad + ', Argentina', function (primero) {

        if (primero != null) {
            mostrarLugarEnMapa(primero, true);
            return;
        }

        // Si no la encuentra, probamos con el nombre del lugar
        // (esperamos 1 segundo porque Nominatim no deja pedir mas rapido)
        setTimeout(function () {
            buscarDireccion(lugar + ', ' + ciudad + ', Argentina', function (segundo) {

                if (segundo != null) {
                    mostrarLugarEnMapa(segundo, true);
                    return;
                }

                // Ultimo intento: solo la ciudad
                setTimeout(function () {
                    buscarDireccion(ciudad + ', Argentina', function (tercero) {
                        mostrarLugarEnMapa(tercero, false);
                    });
                }, 1100);
            });
        }, 1100);
    });
}


// ============================================================
// ARRANCAMOS TODO
// ============================================================

ponerCuentaRegresiva();
prepararEntradas();
prepararMapa();