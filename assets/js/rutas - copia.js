document.addEventListener("DOMContentLoaded", async function () {
    var usuario = await iniciarPaginaPrivada();
    if (!usuario) return;

    cargarRutas();
});

async function cargarRutas() {
    var contenedor = document.getElementById("listado-rutas");
    if (!contenedor) return;

    try {
        var datos = await llamarApi(RAIZ + "api/rutas_listado.php");
        var lista = datos.rutas || [];

        if (lista.length === 0) {
            contenedor.innerHTML = '<p style="color:#6b7280; background:#f9fafb; padding:15px; border-radius:6px; border:1px solid #e5e7eb;">No hay rutas registradas.</p>';
            return;
        }

        var html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">';
        lista.forEach(function (r) {
            html += crearTarjetaRuta(r);
        });
        html += '</div>';

        contenedor.innerHTML = html;
    } catch (err) {
        contenedor.innerHTML = '<p style="color:#ef4444; background:#fef2f2; padding:15px; border-radius:6px; border:1px solid #fca5a5;">Error al cargar las rutas: ' + escaparTexto(err.message) + '</p>';
    }
}

function crearTarjetaRuta(r) {
    var nombre = r.nombre_ruta || "Sin nombre";
    var origen = r.origen || "-";
    var destino = r.destino || "-";
    var distancia = r.distancia_km ? r.distancia_km + " km" : "N/A";
    var tiempo = r.tiempo_estimado_min ? r.tiempo_estimado_min + " min" : "N/A";
    var obs = r.observaciones ? '<p style="font-size:0.85rem; color:#4b5563; margin-top:10px; background:#f9fafb; padding:8px; border-radius:6px;">' + escaparTexto(r.observaciones) + '</p>' : '';

    return '<div style="background:#fff; padding:20px; border-radius:8px; border:1px solid #e5e7eb; box-shadow:0 1px 3px rgba(0,0,0,0.05);">' +
        '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">' +
            '<h3 style="margin:0; color:#111827; font-size:1.05rem;">' + escaparTexto(nombre) + '</h3>' +
            '<span style="background:#eff6ff; color:#2563eb; font-size:0.78rem; padding:3px 10px; border-radius:12px; font-weight:600;">' + escaparTexto(distancia) + '</span>' +
        '</div>' +

        '<div style="display:flex; align-items:center; justify-content:space-between; background:#f9fafb; padding:10px; border-radius:6px; margin-bottom:10px;">' +
            '<div style="text-align:center; flex:1;">' +
                '<div style="font-size:0.72rem; color:#6b7280; font-weight:600;">ORIGEN</div>' +
                '<div style="font-weight:600; color:#111827; font-size:0.9rem;">' + escaparTexto(origen) + '</div>' +
            '</div>' +
            '<div style="color:#9ca3af; font-weight:bold; padding:0 8px;">&rarr;</div>' +
            '<div style="text-align:center; flex:1;">' +
                '<div style="font-size:0.72rem; color:#6b7280; font-weight:600;">DESTINO</div>' +
                '<div style="font-weight:600; color:#111827; font-size:0.9rem;">' + escaparTexto(destino) + '</div>' +
            '</div>' +
        '</div>' +

        '<div style="font-size:0.85rem; color:#4b5563;"><strong>Tiempo estimado:</strong> ' + escaparTexto(tiempo) + '</div>' +

        obs +
    '</div>';
}