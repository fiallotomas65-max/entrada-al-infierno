document.addEventListener("DOMContentLoaded", async function () {
    var usuario = await iniciarPaginaPrivada();
    if (!usuario) return;

    var urlParams = new URLSearchParams(window.location.search);
    var idEncuesta = urlParams.get("id");

    if (!idEncuesta) {
        mostrarMensaje("No se especificó ninguna encuesta.", "error");
        return;
    }

    cargarDetalleEncuesta(idEncuesta);
});

async function cargarDetalleEncuesta(id) {
    var contenedor = document.getElementById("detalle-encuesta-contenedor");
    if (!contenedor) return;

    try {
        var datos = await llamarApi(RAIZ + "api/encuesta_detalle.php?id=" + id);
        var encuesta = datos.encuesta;
        var preguntas = datos.preguntas || [];
        var totalRespuestas = datos.total_respuestas || 0;

        var estadoColor = encuesta.estado === 'Activa' ? '#10b981' : '#6b7280';

        var html = 
            '<div style="background:#fff; padding:25px; border-radius:8px; border:1px solid #e5e7eb; margin-bottom:25px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">' +
                '<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:10px; margin-bottom:15px;">' +
                    '<div>' +
                        '<h2 style="margin:0 0 8px 0; color:#111827; font-size:1.4rem;">' + escaparTexto(encuesta.titulo) + '</h2>' +
                        '<p style="margin:0; color:#4b5563; font-size:1rem; line-height:1.5;">' + escaparTexto(encuesta.descripcion || 'Sin descripción.') + '</p>' +
                    '</div>' +
                    '<span style="font-size:0.85rem; color:#fff; background:' + estadoColor + '; padding:4px 12px; border-radius:12px; font-weight:600;">' + escaparTexto(encuesta.estado) + '</span>' +
                '</div>' +
                '<div style="display:flex; gap:20px; font-size:0.88rem; color:#6b7280; border-top:1px solid #f3f4f6; padding-top:12px;">' +
                    '<span><strong>Fecha de creación:</strong> ' + escaparTexto(encuesta.fecha_creacion) + '</span>' +
                    '<span><strong>Respuestas recibidas:</strong> ' + totalRespuestas + '</span>' +
                '</div>' +
            '</div>' +

            '<h2 style="font-size:1.2rem; margin-bottom:15px; color:#1f2937;">Preguntas configuradas (' + preguntas.length + ')</h2>' +
            '<div style="display:flex; flex-direction:column; gap:15px;">';

        if (preguntas.length === 0) {
            html += '<p style="color:#6b7280;">Esta encuesta no tiene preguntas registradas.</p>';
        } else {
            preguntas.forEach(function (preg, idx) {
                html += '<div style="background:#fff; padding:20px; border-radius:8px; border:1px solid #e5e7eb;">' +
                    '<div style="font-weight:600; color:#111827; margin-bottom:8px; font-size:1.05rem;">' + (idx + 1) + '. ' + escaparTexto(preg.texto) + ' <span style="font-size:0.8rem; font-weight:normal; color:#6b7280;">(' + (preg.tipo === 'cerrada' ? 'Opción múltiple' : 'Texto libre') + ')</span></div>';

                if (preg.tipo === 'cerrada' && preg.opciones && preg.opciones.length > 0) {
                    html += '<ul style="margin:8px 0 0 20px; padding:0; color:#374151; list-style-type:disc;">';
                    preg.opciones.forEach(function (opc) {
                        html += '<li style="margin-bottom:4px;">' + escaparTexto(opc.texto) + '</li>';
                    });
                    html += '</ul>';
                } else if (preg.tipo === 'abierta') {
                    html += '<div style="margin-top:8px; padding:10px; background:#f9fafb; border:1px dashed #d1d5db; border-radius:4px; color:#9ca3af; font-size:0.88rem;">[Campo de respuesta de texto libre para el paciente]</div>';
                }

                html += '</div>';
            });
        }

        html += '</div>';

        contenedor.innerHTML = html;
    } catch (err) {
        contenedor.innerHTML = '<p style="color:#ef4444; background:#fef2f2; padding:15px; border-radius:6px; border:1px solid #fca5a5;">Error al obtener el detalle de la encuesta: ' + escaparTexto(err.message) + '</p>';
    }
}