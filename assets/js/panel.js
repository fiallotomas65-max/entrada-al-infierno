// ============================================================
// panel.js
// ============================================================

(async function () {
    var sesion = await iniciarPaginaPrivada();
    if (!sesion) return;

    document.getElementById("titulo-bienvenida").textContent = "Bienvenido, " + sesion.nombre;

    try {
        var datos = await llamarApi(RAIZ + "api/panel.php");
        var r = datos.resumen;

        var tarjetas = [
            ["Documentos publicados", r.documentos, "documento"],
            ["Encuestas activas", r.encuestas_activas, "encuesta"],
            ["Respuestas recibidas", r.respuestas, "mensaje"],
            ["Traslados pendientes", r.traslados_pendientes, "reloj"],
            ["Traslados en curso", r.traslados_en_curso, "traslado"]
        ];

        var html = "";
        tarjetas.forEach(function (t) {
            html += '<div class="tarjeta-resumen">' +
                '<div class="icono-tarjeta">' + icono(t[2], 22) + '</div>' +
                '<div><div class="numero">' + t[1] + '</div><div class="etiqueta">' + t[0] + '</div></div>' +
                '</div>';
        });

        document.getElementById("tarjetas-resumen").innerHTML = html;
    } catch (error) {
        mostrarMensaje(error.message, "error");
    }
})();
