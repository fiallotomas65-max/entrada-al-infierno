document.addEventListener("DOMContentLoaded", async function () {
    var usuario = await iniciarPaginaPrivada();
    if (!usuario) return;

    cargarListadoDocumentos();
});

async function cargarListadoDocumentos() {
    var contenedor = document.getElementById("listado-documentos");
    if (!contenedor) return;

    try {
        var datos = await llamarApi(RAIZ + "api/documentos.php");
        var lista = datos.documentos || [];

        if (lista.length === 0) {
            contenedor.innerHTML = '<p style="color:#6b7280; background:#f9fafb; padding:15px; border-radius:6px; border:1px solid #e5e7eb;">No hay documentos cargados en el sistema actualmente.</p>';
            return;
        }

        var html = '<div style="display: grid; gap: 15px;">';
        lista.forEach(function (doc) {
            html += '<div style="background:#fff; padding:18px; border-radius:8px; border:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; box-shadow:0 1px 3px rgba(0,0,0,0.05);">' +
                '<div>' +
                    '<h3 style="margin:0 0 6px 0; color:#111827; font-size:1.1rem; font-weight:600;">' + escaparTexto(doc.titulo) + '</h3>' +
                    '<p style="margin:0 0 10px 0; color:#4b5563; font-size:0.92rem; line-height:1.4;">' + escaparTexto(doc.descripcion) + '</p>' +
                    '<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">' +
                        '<span style="font-size:0.8rem; color:#374151; background:#f3f4f6; padding:3px 10px; border-radius:12px; font-weight:500;">Categoría: ' + escaparTexto(doc.categoria) + '</span>' +
                        '<span style="font-size:0.8rem; color:#6b7280; margin-left:5px;">Fecha: ' + escaparTexto(doc.fecha_alta) + '</span>' +
                        (doc.subido_por ? '<span style="font-size:0.8rem; color:#9ca3af;">| Usuario: ' + escaparTexto(doc.subido_por) + '</span>' : '') +
                    '</div>' +
                '</div>' +
                '<div style="margin-left:20px; flex-shrink:0;">' +
                    '<a href="' + RAIZ + 'documentos/' + escaparTexto(doc.archivo) + '" target="_blank" class="boton" style="font-size:0.85rem; text-decoration:none; display:inline-flex; align-items:center; gap:5px;">' +
                        icono("documento", 16) + ' Ver PDF' +
                    '</a>' +
                '</div>' +
            '</div>';
        });
        html += '</div>';

        contenedor.innerHTML = html;
    } catch (err) {
        contenedor.innerHTML = '<p style="color:#ef4444; background:#fef2f2; padding:15px; border-radius:6px; border:1px solid #fca5a5;">Error al obtener la lista de documentos: ' + escaparTexto(err.message) + '</p>';
    }
}