var RAIZ = (function () {
    var scripts = document.getElementsByTagName("script");
    for (var i = 0; i < scripts.length; i++) {
        var indice = scripts[i].src.indexOf("assets/js/comun.js");
        if (indice !== -1) {
            return scripts[i].src.substring(0, indice);
        }
    }
    return "/"; // no deberia llegar aca si esta pagina incluyo este archivo
})();

// el svg del pulso rojo del logo, se reusa en todas las pantallas
function svgPulso(alto) {
    alto = alto || 40;
    return '<svg width="' + (alto * 3) + '" height="' + alto + '" viewBox="0 0 150 40" fill="none">' +
        '<path d="M0 20 L45 20 L55 5 L65 35 L75 20 L150 20" stroke="#c0392b" stroke-width="4" ' +
        'stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

// iconos de linea simple, usados en el menu de la barra lateral y en las
// tarjetas de resumen del panel. Todos usan currentColor, asi que toman
// el color de texto del elemento donde se insertan.
var RUTAS_ICONO = {
    home: 'M3 10.5 12 3l9 7.5 M5 9.5V21h14V9.5 M9 21v-6h6v6',
    documento: 'M6 3h8l5 5v13H6z M14 3v5h5',
    encuesta: 'M9 5h6a1 1 0 0 1 1 1v1H8V6a1 1 0 0 1 1-1z M5 5h1v16h12V5h1v0 M8 5H6a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2 M9 13l2 2 4-4',
    ambulancia: 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z M12 8v8 M8 12h8',
    persona: 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M4 20c0-4 3.6-7 8-7s8 3 8 7',
    ruta: 'M12 21s7-7.8 7-12.5A7 7 0 0 0 5 8.5C5 13.2 12 21 12 21z M12 11a2.3 2.3 0 1 0 0-4.6 2.3 2.3 0 0 0 0 4.6z',
    traslado: 'M3 8h14 M13 4l4 4-4 4 M21 16H7 M11 12l-4 4 4 4',
    mensaje: 'M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z',
    reloj: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z M12 7v5l3 3',
    salir: 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4 M16 17l5-5-5-5 M21 12H9'
};

function icono(nombre, alto) {
    alto = alto || 18;
    var trazo = RUTAS_ICONO[nombre];
    if (!trazo) return "";
    var partes = trazo.split(" M").map(function (p, i) { return (i == 0 ? p : "M" + p); });
    var paths = partes.map(function (p) { return '<path d="' + p + '"/>'; }).join("");
    return '<svg width="' + alto + '" height="' + alto + '" viewBox="0 0 24 24" fill="none" ' +
        'stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' + paths + '</svg>';
}

// pinta el logo chico (para el encabezado) dentro del elemento con ese id
function pintarLogoChico(idElemento) {
    var el = document.getElementById(idElemento);
    if (!el) return;
    el.innerHTML = '<div class="logo-cds">' + svgPulso(24) +
        '<span class="logo-texto">C.D.S.</span></div>';
}

// pinta el logo grande (para login y portada) dentro del elemento con ese id
function pintarLogoGrande(idElemento) {
    var el = document.getElementById(idElemento);
    if (!el) return;
    el.innerHTML = '<div class="logo-cds logo-grande">' +
        '<span class="logo-texto">CLINICAL DATA SYSTEM</span>' +
        svgPulso(50) +
        '<span class="logo-sub">HOSPITAL DE CLINICAS</span></div>';
}

// las secciones que ve cada rol en el menu de navegacion: [nombre, ruta, icono]
function seccionesPorRol(rol) {
    if (rol == "Administrador") {
        return [
            ["Panel", "panel.html", "home"],
            ["Documentaciones", "admin/documentos.html", "documento"],
            ["Encuestas", "admin/encuestas.html", "encuesta"],
            ["Ambulancias", "admin/ambulancias.html", "ambulancia"],
            ["Personal", "admin/personal.html", "persona"],
            ["Rutas", "admin/rutas.html", "ruta"],
            ["Traslados", "admin/traslados.html", "traslado"]
        ];
    }
    if (rol == "Recepcionista") {
        return [
            ["Panel", "panel.html", "home"],
            ["Documentaciones", "admin/documentos.html", "documento"],
            ["Encuestas", "admin/encuestas.html", "encuesta"]
        ];
    }
    if (rol == "Coordinador") {
        return [
            ["Panel", "panel.html", "home"],
            ["Ambulancias", "admin/ambulancias.html", "ambulancia"],
            ["Personal", "admin/personal.html", "persona"],
            ["Rutas", "admin/rutas.html", "ruta"],
            ["Traslados", "admin/traslados.html", "traslado"]
        ];
    }
    return [];
}

// las iniciales del nombre y apellido, para el circulo de avatar
function obtenerIniciales(nombreCompleto) {
    var palabras = (nombreCompleto || "").trim().split(/\s+/);
    var primera = palabras[0] ? palabras[0][0] : "";
    var segunda = palabras.length > 1 ? palabras[palabras.length - 1][0] : "";
    return (primera + segunda).toUpperCase();
}

// arma la barra lateral completa (logo + menu + usuario) para una pagina
// privada. Si no hay sesion activa, redirige al login.
async function iniciarPaginaPrivada() {
    var respuesta = await fetch(RAIZ + "api/sesion.php");
    var datos = await respuesta.json();

    if (!datos.logueado) {
        window.location.href = RAIZ + "login.html";
        return null;
    }

    var barraLateral = document.getElementById("barra-lateral");
    if (barraLateral) {
        var secciones = seccionesPorRol(datos.rol);
        var pagina_actual = window.location.pathname.split("/").pop();

        var enlaces = "";
        secciones.forEach(function (s) {
            var esta_activo = s[1].endsWith(pagina_actual) ? " activo" : "";
            enlaces += '<a class="enlace-nav' + esta_activo + '" href="' + RAIZ + s[1] + '">' +
                icono(s[2]) + '<span>' + s[0] + '</span></a>';
        });

        barraLateral.innerHTML =
            '<div id="logo-barra"></div>' +
            '<nav class="navegacion">' + enlaces + '</nav>' +
            '<div class="zona-usuario">' +
            '<div class="avatar-usuario">' + escaparTexto(obtenerIniciales(datos.nombre)) + '</div>' +
            '<div class="datos-usuario">' +
            '<span class="nombre-usuario">' + escaparTexto(datos.nombre) + '</span>' +
            '<span class="rol-usuario">' + escaparTexto(datos.rol) + '</span>' +
            '</div>' +
            '<button class="boton-salir" onclick="cerrarSesion()" title="Cerrar sesion">' + icono("salir") + '</button>' +
            '</div>';

        pintarLogoChico("logo-barra");
    }

    mostrarMensajeGuardado();
    return datos;
}

// cierra la sesion y vuelve al login
async function cerrarSesion() {
    await fetch(RAIZ + "api/logout.php", { method: "POST" });
    window.location.href = RAIZ + "login.html";
}

// ------------------------------------------------------------
// mensajes que se muestran arriba de la pagina. Se guardan en
// sessionStorage para poder mostrarlos despues de una redireccion.
// ------------------------------------------------------------
function guardarMensaje(texto, tipo) {
    sessionStorage.setItem("mensaje_cds", JSON.stringify({ texto: texto, tipo: tipo || "exito" }));
}

function mostrarMensajeGuardado() {
    var guardado = sessionStorage.getItem("mensaje_cds");
    if (!guardado) return;
    sessionStorage.removeItem("mensaje_cds");
    var datos = JSON.parse(guardado);
    mostrarMensaje(datos.texto, datos.tipo);
}

// muestra un mensaje en el contenedor #zona-mensaje de la pagina actual
function mostrarMensaje(texto, tipo) {
    var zona = document.getElementById("zona-mensaje");
    if (!zona) return;
    var clase = (tipo == "error") ? "mensaje-error" : "mensaje-exito";
    zona.innerHTML = '<div class="mensaje ' + clase + '">' + escaparTexto(texto) + '</div>';
    setTimeout(function () {
        zona.innerHTML = "";
    }, 5000);
}

// evita que texto con < o > rompa el html al insertarlo con innerHTML
function escaparTexto(texto) {
    var div = document.createElement("div");
    div.innerText = texto == null ? "" : texto;
    return div.innerHTML;
}

// llama a una api y devuelve el json ya interpretado. Si la api
// respondio con ok:false, lanza un error con el mensaje del servidor.
async function llamarApi(url, opciones) {
    var respuesta = await fetch(url, opciones || {});
    var datos = await respuesta.json();
    if (!datos.ok) {
        throw new Error(datos.mensaje || "Ocurrio un error.");
    }
    return datos;
}

// arma las opciones de fetch para un POST con body JSON
function opcionesPost(objeto) {
    return {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(objeto)
    };
}