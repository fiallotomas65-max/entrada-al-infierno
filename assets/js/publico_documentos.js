// ============================================================
// publico_documentos.js - pagina publica, no requiere sesion
// ============================================================

pintarLogoChico("logo-encabezado");

(async function () {
    var zona = document.getElementById("zona-documentos");
    try {
        var respuesta = await fetch(RAIZ + "api/documentos.php");
        var datos = await respuesta.json();
        var lista = datos.documentos;

        if (lista.length == 0) {
            zona.innerHTML = "<p>No hay documentacion disponible por el momento.</p>";
            return;
        }

        // se agrupan por categoria, manteniendo el orden en el que llegaron
        var grupos = {};
        var orden = [];
        lista.forEach(function (doc) {
            if (!grupos[doc.categoria]) {
                grupos[doc.categoria] = [];
                orden.push(doc.categoria);
            }
            grupos[doc.categoria].push(doc);
        });

        var html = "";
        orden.forEach(function (categoria) {
            html += "<h2>" + escaparTexto(categoria) + "</h2><div class=\"lista-documentos\">";
            grupos[categoria].forEach(function (doc) {
                html += '<div class="tarjeta-documento">' +
                    "<div><h3>" + escaparTexto(doc.titulo) + "</h3><p>" + escaparTexto(doc.descripcion) + "</p></div>" +
                    '<a class="boton-documento" href="' + RAIZ + 'api/ver_pdf.php?id=' + doc.id_documento + '" target="_blank">Ver documento</a>' +
                    "</div>";
            });
            html += "</div>";
        });

        zona.innerHTML = html;
    } catch (error) {
        zona.innerHTML = "<p>No se pudo cargar la documentacion. Intente nuevamente.</p>";
    }
})();
