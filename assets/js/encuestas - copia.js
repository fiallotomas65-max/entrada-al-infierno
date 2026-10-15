document.addEventListener("DOMContentLoaded", async function () {
    var usuario = await iniciarPaginaPrivada();
    if (!usuario) return;

    cargarListadoEncuestas();
});

async function cargarListadoEncuestas() {
    var contenedor = document.getElementById("listado-encuestas");
    if (!contenedor) return;

    try {
        var datos = await llamarApi(RAIZ + "api/encuestas.php");
        var lista = datos.encuestas || [];

        if (lista.length === 0) {
            contenedor.innerHTML = '<p style="color:#6b7280; background:#f9fafb; padding:15px; border-radius:6px; border:1px solid #e5e7eb;">No hay encuestas registradas actualmente.</p>';
            return;
        }

        var html = '<div style="display: grid; gap: 15px;">';
        lista.forEach(function (encuesta) {
            var estadoColor = encuesta.estado === 'Activa' ? '#10b981' : '#6b7280';
            
            html += '<div style="background:#fff; padding:18px; border-radius:8px; border:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; box-shadow:0 1px 3px rgba(0,0,0,0.05);">' +
                '<div>' +
                    '<h3 style="margin:0 0 6px 0; color:#111827; font-size:1.1rem; font-weight:600;">' + escaparTexto(encuesta.titulo) + '</h3>' +
                    '<p style="margin:0 0 10px 0; color:#4b5563; font-size:0.92rem; line-height:1.4;">' + escaparTexto(encuesta.descripcion || 'Sin descripción.') + '</p>' +
                    '<div style="display:flex; gap:10px; align-items:center;">' +
                        '<span style="font-size:0.8rem; color:#fff; background:' + estadoColor + '; padding:2px 8px; border-radius:10px; font-weight:500;">' + escaparTexto(encuesta.estado) + '</span>' +
                        '<span style="font-size:0.8rem; color:#6b7280;">Creada: ' + escaparTexto(encuesta.fecha_creacion) + '</span>' +
                    '</div>' +
                '</div>' +
                '<div style="margin-left:20px; flex-shrink:0; display:flex; gap:10px;">' +
                    '<a href="ver_encuesta.html?id=' + encuesta.id_encuesta + '" class="boton" style="font-size:0.85rem; text-decoration:none;">Ingresar</a>' +
                '</div>' +
            '</div>';
        });
        html += '</div>';

        contenedor.innerHTML = html;
    } catch (err) {
        contenedor.innerHTML = '<p style="color:#ef4444; background:#fef2f2; padding:15px; border-radius:6px; border:1px solid #fca5a5;">Error al obtener las encuestas: ' + escaparTexto(err.message) + '</p>';
    }
}