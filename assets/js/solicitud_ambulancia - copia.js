document.addEventListener("DOMContentLoaded", async function () {
    var usuario = await iniciarPaginaPrivada();
    if (!usuario) return;

    document.getElementById("fecha_traslado").valueAsDate = new Date();

    cargarRecursosFormulario();

    var form = document.getElementById("form-solicitud-ambulancia");
    if (form) {
        form.addEventListener("submit", procesarGuardadoSolicitud);
    }
});

async function cargarRecursosFormulario() {
    try {
        var datos = await llamarApi(RAIZ + "api/recursos_traslado.php");

        // Cargar Ambulancias
        var selectAmb = document.getElementById("id_ambulancia");
        selectAmb.innerHTML = '<option value="">-- Seleccionar Ambulancia --</option>';
        (datos.ambulancias || []).forEach(function (a) {
            var dis = a.estado !== 'Disponible' ? ' (No disponible: ' + a.estado + ')' : '';
            selectAmb.innerHTML += '<option value="' + a.id_ambulancia + '" ' + (a.estado !== 'Disponible' ? 'disabled' : '') + '>' + escaparTexto(a.patente + ' - ' + a.modelo) + dis + '</option>';
        });

        // Cargar Personal (Conductores y Copilotos)
        var selectCond = document.getElementById("id_conductor");
        var selectCop = document.getElementById("id_copiloto");
        selectCond.innerHTML = '<option value="">-- Seleccionar Conductor --</option>';
        selectCop.innerHTML = '<option value="">-- Ninguno / Sin Copiloto --</option>';

        (datos.personal || []).forEach(function (p) {
            var txt = escaparTexto(p.nombre + ' ' + p.apellido + ' (' + p.cargo + ')');
            selectCond.innerHTML += '<option value="' + p.id_personal + '">' + txt + '</option>';
            selectCop.innerHTML += '<option value="' + p.id_personal + '">' + txt + '</option>';
        });

        // Cargar Rutas Predefinidas
        var selectRuta = document.getElementById("id_ruta");
        selectRuta.innerHTML = '<option value="">-- Seleccionar Ruta (Opcional) --</option>';
        (datos.rutas || []).forEach(function (r) {
            selectRuta.innerHTML += '<option value="' + r.id_ruta + '" data-origen="' + escaparTexto(r.origen) + '" data-destino="' + escaparTexto(r.destino) + '">' + escaparTexto(r.nombre) + '</option>';
        });

        selectRuta.addEventListener("change", function () {
            var opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.origen) {
                document.getElementById("origen").value = opt.dataset.origen;
                document.getElementById("destino").value = opt.dataset.destino;
            }
        });

    } catch (err) {
        mostrarMensaje("Error al cargar lista de recursos: " + err.message, "error");
    }
}

async function procesarGuardadoSolicitud(e) {
    e.preventDefault();

    var form = e.target;

    // Recopilar Requerimientos Especiales
    var reqs = [];
    document.querySelectorAll(".chk-req:checked").forEach(function (chk) {
        reqs.push(chk.value);
    });
    var reqAdicional = document.getElementById("req_adicional").value.trim();
    if (reqAdicional) reqs.push(reqAdicional);

    var formData = new FormData(form);
    formData.append("requerimientos", reqs.join(", "));

    try {
        var resp = await fetch(RAIZ + "api/traslado_guardar.php", {
            method: "POST",
            body: formData
        });

        var datos = await resp.json();
        if (!datos.ok) throw new Error(datos.mensaje);

        guardarMensaje("Solicitud de ambulancia guardada correctamente.", "exito");
        window.location.href = "ambulancias.html";
    } catch (err) {
        mostrarMensaje(err.message, "error");
    }
}