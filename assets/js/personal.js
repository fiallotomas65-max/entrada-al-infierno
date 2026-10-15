var temporizadorBusqueda = null;

document.addEventListener("DOMContentLoaded", async function () {
    var usuario = await iniciarPaginaPrivada();
    if (!usuario) return;

    cargarPersonal();

    var inputBusqueda = document.getElementById("input-busqueda");
    var filtroEsp = document.getElementById("filtro-especialidad");
    var filtroTur = document.getElementById("filtro-turno");
    var btnLimpiar = document.getElementById("btn-limpiar");

    if (inputBusqueda) {
        inputBusqueda.addEventListener("input", function () {
            clearTimeout(temporizadorBusqueda);
            temporizadorBusqueda = setTimeout(cargarPersonal, 300);
        });
    }

    if (filtroEsp) filtroEsp.addEventListener("change", cargarPersonal);
    if (filtroTur) filtroTur.addEventListener("change", cargarPersonal);

    if (btnLimpiar) {
        btnLimpiar.addEventListener("click", function () {
            inputBusqueda.value = "";
            filtroEsp.value = "";
            filtroTur.value = "";
            cargarPersonal();
        });
    }
});

async function cargarPersonal() {
    var contenedor = document.getElementById("listado-personal");
    if (!contenedor) return;

    var busqueda = document.getElementById("input-busqueda") ? document.getElementById("input-busqueda").value.trim() : "";
    var especialidad = document.getElementById("filtro-especialidad") ? document.getElementById("filtro-especialidad").value : "";
    var turno = document.getElementById("filtro-turno") ? document.getElementById("filtro-turno").value : "";

    var query = "?busqueda=" + encodeURIComponent(busqueda) +
                "&especialidad=" + encodeURIComponent(especialidad) +
                "&turno=" + encodeURIComponent(turno);

    try {
        var datos = await llamarApi(RAIZ + "api/personal_listado.php" + query);
        var lista = datos.personal || [];
        var especialidades = datos.especialidades || [];

        actualizarOpcionesEspecialidad(especialidades, especialidad);

        if (lista.length === 0) {
            contenedor.innerHTML = '<p style="color:#6b7280; background:#f9fafb; padding:15px; border-radius:6px; border:1px solid #e5e7eb;">No se encontraron funcionarios que coincidan con la búsqueda o filtro.</p>';
            return;
        }

        var html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">';
        lista.forEach(function (p) {
            html += crearTarjetaPersonal(p);
        });
        html += '</div>';

        contenedor.innerHTML = html;
    } catch (err) {
        contenedor.innerHTML = '<p style="color:#ef4444; background:#fef2f2; padding:15px; border-radius:6px; border:1px solid #fca5a5;">Error al cargar el personal: ' + escaparTexto(err.message) + '</p>';
    }
}

function actualizarOpcionesEspecialidad(listaEspecialidades, seleccionada) {
    var select = document.getElementById("filtro-especialidad");
    if (!select || select.options.length > 1) return;

    listaEspecialidades.forEach(function (esp) {
        var opt = document.createElement("option");
        opt.value = esp;
        opt.textContent = esp;
        if (esp === seleccionada) opt.selected = true;
        select.appendChild(opt);
    });
}

function crearTarjetaPersonal(p) {
    var hoy = new Date().toISOString().split("T")[0];
    var alertaLibreta = '';

    if (p.vencimiento_libreta) {
        if (p.vencimiento_libreta < hoy) {
            alertaLibreta = '<span style="background:#ef4444; color:#fff; font-size:0.75rem; padding:2px 6px; border-radius:4px; font-weight:600;">Libreta Vencida</span>';
        } else {
            alertaLibreta = '<span style="color:#4b5563; font-size:0.8rem;">Libreta Vence: ' + escaparTexto(p.vencimiento_libreta) + '</span>';
        }
    }

    var certs = p.certificaciones ? p.certificaciones.split(',') : [];
    var htmlCerts = certs.map(function(c) {
        return '<span style="background:#e0f2fe; color:#0369a1; font-size:0.75rem; padding:2px 8px; border-radius:10px;">' + escaparTexto(c.trim()) + '</span>';
    }).join(' ');

    return '<div style="background:#fff; padding:20px; border-radius:8px; border:1px solid #e5e7eb; box-shadow:0 1px 3px rgba(0,0,0,0.05); display:flex; flex-direction:column; justify-content:space-between;">' +
        '<div>' +
            '<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">' +
                '<div>' +
                    '<h3 style="margin:0; color:#111827; font-size:1.1rem;">' + escaparTexto(p.nombre + ' ' + p.apellido) + '</h3>' +
                    '<span style="font-size:0.85rem; color:#6b7280;">CI: ' + escaparTexto(p.documento) + '</span>' +
                '</div>' +
                '<span style="background:#f3f4f6; color:#374151; font-size:0.75rem; padding:4px 8px; border-radius:4px; font-weight:600;">' + escaparTexto(p.clasificacion_operativa || 'Sin Clasificar') + '</span>' +
            '</div>' +

            '<div style="font-size:0.88rem; color:#374151; margin-bottom:12px; line-height:1.5;">' +
                '<div><strong>Especialidad/Área:</strong> ' + escaparTexto(p.especialidad || 'General') + '</div>' +
                '<div><strong>Turno:</strong> ' + escaparTexto(p.turno || 'No asignado') + '</div>' +
                '<div><strong>Teléfono:</strong> ' + escaparTexto(p.telefono || 'Sin registrar') + '</div>' +
            '</div>' +

            (alertaLibreta ? '<div style="margin-bottom:10px;">' + alertaLibreta + '</div>' : '') +

            (htmlCerts ? '<div style="margin-top:8px;"><strong style="font-size:0.8rem; color:#6b7280; display:block; margin-bottom:4px;">CERTIFICACIONES:</strong><div style="display:flex; flex-wrap:wrap; gap:4px;">' + htmlCerts + '</div></div>' : '') +
        '</div>' +
    '</div>';
}