document.addEventListener("DOMContentLoaded", async function () {
    var usuario = await iniciarPaginaPrivada();
    if (!usuario) return;

    cargarTraslados();
});

async function cargarTraslados() {
    var contenedor = document.getElementById("listado-traslados");
    if (!contenedor) return;

    try {
        var datos = await llamarApi(RAIZ + "api/traslados.php");
        var lista = datos.traslados || [];

        if (lista.length === 0) {
            contenedor.innerHTML = '<p style="color:#6b7280; background:#f9fafb; padding:15px; border-radius:6px; border:1px solid #e5e7eb;">No hay traslados o solicitudes registradas.</p>';
            return;
        }

        var html = '<div style="display: grid; gap: 20px;">';
        lista.forEach(function (t) {
            html += crearTarjetaTraslado(t);
        });
        html += '</div>';

        contenedor.innerHTML = html;
    } catch (err) {
        contenedor.innerHTML = '<p style="color:#ef4444; background:#fef2f2; padding:15px; border-radius:6px; border:1px solid #fca5a5;">Error al cargar los traslados: ' + escaparTexto(err.message) + '</p>';
    }
}

function crearTarjetaTraslado(t) {
    var paso1 = 'background:#d1d5db; color:#374151;';
    var paso2 = 'background:#d1d5db; color:#374151;';
    var paso3 = 'background:#d1d5db; color:#374151;';

    if (t.estado === 'Pendiente') {
        paso1 = 'background:#3b82f6; color:#fff; font-weight:bold;';
    } else if (t.estado === 'En curso') {
        paso1 = 'background:#10b981; color:#fff;';
        paso2 = 'background:#f59e0b; color:#fff; font-weight:bold;';
    } else if (t.estado === 'Finalizado') {
        paso1 = 'background:#10b981; color:#fff;';
        paso2 = 'background:#10b981; color:#fff;';
        paso3 = 'background:#10b981; color:#fff; font-weight:bold;';
    } else if (t.estado === 'Cancelado') {
        paso1 = paso2 = paso3 = 'background:#ef4444; color:#fff;';
    }

    var htmlAcciones = '';
    if (t.estado === 'Pendiente') {
        htmlAcciones = '<button onclick="cambiarEstado(' + t.id_traslado + ', \'En curso\')" class="boton" style="background:#f59e0b; font-size:0.8rem; padding:6px 12px;">Iniciar Salida</button> ' +
                       '<button onclick="cambiarEstado(' + t.id_traslado + ', \'Cancelado\')" class="boton" style="background:#ef4444; font-size:0.8rem; padding:6px 12px;">Cancelar</button>';
    } else if (t.estado === 'En curso') {
        htmlAcciones = '<button onclick="cambiarEstado(' + t.id_traslado + ', \'Finalizado\')" class="boton" style="background:#10b981; font-size:0.8rem; padding:6px 12px;">Marcar Llegada / Finalizar</button>';
    }

    return '<div style="background:#fff; padding:20px; border-radius:8px; border:1px solid #e5e7eb; box-shadow:0 1px 3px rgba(0,0,0,0.05);">' +
        '<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">' +
            '<div>' +
                '<span style="background:#374151; color:#fff; padding:3px 8px; border-radius:4px; font-size:0.75rem; font-weight:600;">' + escaparTexto(t.tipo_elemento) + '</span> ' +
                '<h3 style="display:inline; margin-left:8px; color:#111827; font-size:1.1rem;">' + escaparTexto(t.elemento) + '</h3>' +
            '</div>' +
            '<div>' + htmlAcciones + '</div>' +
        '</div>' +

        '<div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px; font-size:0.88rem; background:#f9fafb; padding:10px; border-radius:6px; margin-bottom:15px;">' +
            '<div><strong>Ruta:</strong> ' + escaparTexto(t.origen) + ' &rarr; ' + escaparTexto(t.destino) + '</div>' +
            '<div><strong>Ambulancia:</strong> ' + escaparTexto(t.patente || 'N/A') + ' (' + escaparTexto(t.modelo || '') + ')</div>' +
            '<div><strong>Personal:</strong> C: ' + escaparTexto(t.conductor || 'Sin asignar') + (t.copiloto ? ' | Cop: ' + escaparTexto(t.copiloto) : '') + '</div>' +
        '</div>' +

        (t.observaciones ? '<p style="font-size:0.85rem; color:#4b5563; margin-bottom:15px;"><strong>Detalle/Req:</strong> ' + escaparTexto(t.observaciones) + '</p>' : '') +

        '<!-- Línea de tiempo interactiva -->' +
        '<div style="margin-top:10px;">' +
            '<div style="font-size:0.8rem; font-weight:600; color:#6b7280; margin-bottom:6px;">LÍNEA DE TIEMPO DE TRAZABILIDAD:</div>' +
            '<div style="display:flex; gap:5px; align-items:center;">' +
                '<div style="flex:1; text-align:center; padding:8px; border-radius:4px; font-size:0.8rem; ' + paso1 + '">1. Solicitud / Pendiente</div>' +
                '<div style="font-weight:bold; color:#9ca3af;">&rarr;</div>' +
                '<div style="flex:1; text-align:center; padding:8px; border-radius:4px; font-size:0.8rem; ' + paso2 + '">2. En Traslado</div>' +
                '<div style="font-weight:bold; color:#9ca3af;">&rarr;</div>' +
                '<div style="flex:1; text-align:center; padding:8px; border-radius:4px; font-size:0.8rem; ' + paso3 + '">3. Finalizado / Retorno</div>' +
            '</div>' +
        '</div>' +
    '</div>';
}

async function cambiarEstado(idTraslado, nuevoEstado) {
    if (!confirm("¿Confirma cambiar el estado del traslado a '" + nuevoEstado + "'?")) return;

    try {
        var resp = await fetch(RAIZ + "api/traslado_estado.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id_traslado: idTraslado, nuevo_estado: nuevoEstado })
        });

        var datos = await resp.json();
        if (!datos.ok) throw new Error(datos.mensaje);

        mostrarMensaje(datos.mensaje, "exito");
        cargarTraslados();
    } catch (err) {
        mostrarMensaje(err.message, "error");
    }
}