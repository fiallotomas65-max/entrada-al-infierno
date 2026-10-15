// ============================================================
// traslados.js (admin)
// ============================================================

var trasladoActual = null;

(async function () {
    var sesion = await iniciarPaginaPrivada();
    if (!sesion) return;

    await cargarOpcionesFormulario();
    await cargarTraslados();
})();

// carga los combos del formulario de alta: ambulancias, personal y rutas
async function cargarOpcionesFormulario() {
    try {
        var datosAmb = await llamarApi(RAIZ + "api/ambulancias.php");
        var opcionesAmb = '<option value="">Seleccione una ambulancia</option>';
        datosAmb.ambulancias.forEach(function (a) {
            if (a.estado != "Mantenimiento") {
                opcionesAmb += '<option value="' + a.id_ambulancia + '">' + escaparTexto(a.patente + " - " + a.modelo) + '</option>';
            }
        });
        document.getElementById("id_ambulancia").innerHTML = opcionesAmb;

        var datosPer = await llamarApi(RAIZ + "api/personal.php");
        var opcionesConductor = '<option value="">Seleccione un conductor</option>';
        var opcionesCopiloto = '<option value="">Sin copiloto</option>';
        datosPer.personal.forEach(function (p) {
            var nombreCompleto = escaparTexto(p.nombre + " " + p.apellido);
            if (p.cargo == "Conductor") {
                opcionesConductor += '<option value="' + p.id_personal + '">' + nombreCompleto + '</option>';
            } else {
                opcionesCopiloto += '<option value="' + p.id_personal + '">' + nombreCompleto + '</option>';
            }
        });
        document.getElementById("id_conductor").innerHTML = opcionesConductor;
        document.getElementById("id_copiloto").innerHTML = opcionesCopiloto;

        var datosRutas = await llamarApi(RAIZ + "api/rutas.php");
        var opcionesRutas = '<option value="">Sin ruta predefinida</option>';
        datosRutas.rutas.forEach(function (r) {
            opcionesRutas += '<option value="' + r.id_ruta + '">' + escaparTexto(r.nombre) + '</option>';
        });
        document.getElementById("id_ruta").innerHTML = opcionesRutas;
    } catch (error) {
        mostrarMensaje(error.message, "error");
    }
}

async function cargarTraslados() {
    var cuerpo = document.getElementById("cuerpo-tabla-traslados");
    var estado = document.getElementById("filtro-estado").value;

    try {
        var datos = await llamarApi(RAIZ + "api/traslados.php?estado=" + estado);
        var lista = datos.traslados;

        if (lista.length == 0) {
            cuerpo.innerHTML = '<tr><td colspan="7">No hay traslados cargados.</td></tr>';
            return;
        }

        var html = "";
        lista.forEach(function (t) {
            var claseEstado = "estado-" + t.estado.replace(" ", "-");
            html += "<tr>" +
                "<td>" + t.fecha_traslado + "</td>" +
                "<td>" + escaparTexto(t.patente) + "</td>" +
                "<td>" + escaparTexto(t.conductor_nombre + " " + t.conductor_apellido) + "</td>" +
                "<td>" + escaparTexto(t.tipo_elemento) + "</td>" +
                "<td>" + escaparTexto(t.destino) + "</td>" +
                '<td><span class="estado ' + claseEstado + '">' + escaparTexto(t.estado) + "</span></td>" +
                '<td><button class="enlace-editar" onclick="verDetalle(' + t.id_traslado + ')">Seguimiento</button></td>' +
                "</tr>";
        });
        cuerpo.innerHTML = html;
    } catch (error) {
        cuerpo.innerHTML = '<tr><td colspan="7">Error al cargar los traslados.</td></tr>';
        mostrarMensaje(error.message, "error");
    }
}

document.getElementById("form-traslado").addEventListener("submit", async function (evento) {
    evento.preventDefault();
    var formulario = evento.target;

    try {
        var datos = await llamarApi(RAIZ + "api/traslados.php", opcionesPost({
            id_ambulancia: document.getElementById("id_ambulancia").value,
            id_conductor: document.getElementById("id_conductor").value,
            id_copiloto: document.getElementById("id_copiloto").value,
            tipo_elemento: document.getElementById("tipo_elemento").value,
            elemento: document.getElementById("elemento").value,
            id_ruta: document.getElementById("id_ruta").value,
            origen: document.getElementById("origen").value,
            destino: document.getElementById("destino").value,
            fecha_traslado: document.getElementById("fecha_traslado").value,
            observaciones: document.getElementById("observaciones").value
        }));
        formulario.reset();
        document.getElementById("origen").value = "Hospital de Clinicas";
        mostrarMensaje(datos.mensaje, "exito");
        await cargarTraslados();
    } catch (error) {
        mostrarMensaje(error.message, "error");
    }
});

// ------------------------------------------------
// ficha y seguimiento de un traslado
// ------------------------------------------------
function formatearFecha(valor) {
    if (!valor) return "&mdash;";
    return new Date(valor.replace(" ", "T")).toLocaleString("es-UY");
}

async function verDetalle(id) {
    trasladoActual = id;
    document.getElementById("vista-listado").classList.add("oculto");
    document.getElementById("vista-detalle").classList.remove("oculto");

    try {
        var datos = await llamarApi(RAIZ + "api/traslados.php?id=" + id);
        var t = datos.traslado;

        var filas = [
            ["Fecha del traslado", t.fecha_traslado],
            ["Estado", '<span class="estado estado-' + t.estado.replace(" ", "-") + '">' + escaparTexto(t.estado) + "</span>"],
            ["Ambulancia", escaparTexto(t.patente + " - " + t.modelo)],
            ["Conductor", escaparTexto(t.conductor_nombre + " " + t.conductor_apellido)],
            ["Copiloto", t.copiloto_nombre ? escaparTexto(t.copiloto_nombre + " " + t.copiloto_apellido) : "Sin copiloto"],
            ["Elemento", escaparTexto(t.tipo_elemento + " - " + t.elemento)],
            ["Origen", escaparTexto(t.origen)],
            ["Destino", escaparTexto(t.destino)],
            ["Ruta", t.ruta_nombre ? escaparTexto(t.ruta_nombre) : "Sin ruta predefinida"],
            ["Hora de salida", formatearFecha(t.hora_salida)],
            ["Hora de llegada", formatearFecha(t.hora_llegada)],
            ["Observaciones", t.observaciones ? escaparTexto(t.observaciones) : "&mdash;"]
        ];

        var htmlFicha = "";
        filas.forEach(function (f) {
            htmlFicha += "<tr><th>" + f[0] + "</th><td>" + f[1] + "</td></tr>";
        });
        document.getElementById("tabla-ficha").innerHTML = htmlFicha;

        var htmlHistorial = "";
        datos.historial.forEach(function (h) {
            htmlHistorial += "<tr>" +
                "<td>" + formatearFecha(h.fecha_hora) + "</td>" +
                '<td><span class="estado estado-' + h.estado.replace(" ", "-") + '">' + escaparTexto(h.estado) + "</span></td>" +
                "<td>" + (h.comentario ? escaparTexto(h.comentario) : "&mdash;") + "</td>" +
                "<td>" + escaparTexto(h.usuario_nombre + " " + h.usuario_apellido) + "</td>" +
                "</tr>";
        });
        document.getElementById("cuerpo-tabla-historial").innerHTML = htmlHistorial;

        var zona = document.getElementById("zona-cambio-estado");
        if (datos.siguientes_estados.length == 0) {
            zona.innerHTML = "<p>El traslado esta cerrado: no se puede cambiar su estado.</p>";
        } else {
            var opciones = "";
            datos.siguientes_estados.forEach(function (e) {
                opciones += "<option>" + e + "</option>";
            });
            zona.innerHTML =
                '<form id="form-cambio-estado" class="seccion">' +
                '<label for="nuevo-estado">Nuevo estado</label>' +
                '<select id="nuevo-estado">' + opciones + '</select>' +
                '<label for="comentario-estado">Comentario (opcional)</label>' +
                '<textarea id="comentario-estado" rows="2"></textarea>' +
                '<button type="submit" class="boton">Cambiar estado</button>' +
                '</form>';

            document.getElementById("form-cambio-estado").addEventListener("submit", async function (evento) {
                evento.preventDefault();
                try {
                    var resultado = await llamarApi(RAIZ + "api/estado_traslado.php", opcionesPost({
                        id_traslado: trasladoActual,
                        estado: document.getElementById("nuevo-estado").value,
                        comentario: document.getElementById("comentario-estado").value
                    }));
                    mostrarMensaje(resultado.mensaje, "exito");
                    await verDetalle(trasladoActual);
                } catch (error) {
                    mostrarMensaje(error.message, "error");
                }
            });
        }
    } catch (error) {
        mostrarMensaje(error.message, "error");
    }
}

function volverAlListado() {
    trasladoActual = null;
    document.getElementById("vista-detalle").classList.add("oculto");
    document.getElementById("vista-listado").classList.remove("oculto");
    cargarTraslados();
}
