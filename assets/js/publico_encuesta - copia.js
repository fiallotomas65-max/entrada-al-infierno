// ============================================================
// publico_encuesta.js - pagina publica, no requiere sesion
// ============================================================

pintarLogoChico("logo-encabezado");

var encuestaCargada = null;

(async function () {
    var zona = document.getElementById("contenido-encuesta");
    try {
        var respuesta = await fetch(RAIZ + "api/encuesta_publica.php");
        var datos = await respuesta.json();
        encuestaCargada = datos.encuesta;

        if (!encuestaCargada) {
            zona.innerHTML = "<h1>Encuestas</h1><p>En este momento no hay encuestas disponibles.</p>";
            return;
        }

        var html = "<h1>" + escaparTexto(encuestaCargada.titulo) + "</h1>" +
            '<p class="intro-publica">' + escaparTexto(encuestaCargada.descripcion) + "</p>" +
            '<form id="form-encuesta-publica" class="seccion">';

        encuestaCargada.preguntas.forEach(function (p) {
            html += '<div class="pregunta-formulario"><label>' + escaparTexto(p.texto) + "</label>";
            if (p.tipo == "cerrada") {
                html += '<select data-id-pregunta="' + p.id_pregunta + '" data-tipo="cerrada">' +
                    '<option value="">Sin responder</option>';
                p.opciones.forEach(function (o) {
                    html += '<option value="' + o.id_opcion + '">' + escaparTexto(o.texto) + "</option>";
                });
                html += "</select>";
            } else {
                html += '<textarea data-id-pregunta="' + p.id_pregunta + '" data-tipo="abierta" rows="2"></textarea>';
            }
            html += "</div>";
        });

        html += '<button type="submit" class="boton">Enviar respuestas</button>' +
            '<p class="aclaracion">La encuesta es anonima y no se guardan datos personales.</p>' +
            "</form>";

        zona.innerHTML = html;

        document.getElementById("form-encuesta-publica").addEventListener("submit", enviarEncuesta);
    } catch (error) {
        zona.innerHTML = "<p>No se pudo cargar la encuesta. Intente nuevamente.</p>";
    }
})();

async function enviarEncuesta(evento) {
    evento.preventDefault();

    var respuestas = [];
    document.querySelectorAll("[data-id-pregunta]").forEach(function (campo) {
        if (campo.dataset.tipo == "cerrada") {
            respuestas.push({ id_pregunta: campo.dataset.idPregunta, id_opcion: campo.value, texto_libre: "" });
        } else {
            respuestas.push({ id_pregunta: campo.dataset.idPregunta, id_opcion: "", texto_libre: campo.value });
        }
    });

    try {
        await llamarApi(RAIZ + "api/encuesta_publica.php", opcionesPost({
            id_encuesta: encuestaCargada.id_encuesta,
            respuestas: respuestas
        }));

        document.getElementById("contenido-encuesta").innerHTML =
            "<h1>Gracias por su respuesta</h1>" +
            '<p class="intro-publica">Sus comentarios nos ayudan a mejorar la atencion del hospital.</p>' +
            '<p><a class="boton-documento" href="' + RAIZ + 'publico/documentos.html">Volver a documentos</a></p>';
    } catch (error) {
        alert(error.message);
    }
}
