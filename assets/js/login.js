// ============================================================
// login.js - procesa el formulario de ingreso
// ============================================================

pintarLogoGrande("logo-login");

// si ya hay sesion activa, no tiene sentido mostrar el login
fetch(RAIZ + "api/sesion.php")
    .then(function (r) { return r.json(); })
    .then(function (datos) {
        if (datos.logueado) {
            window.location.href = RAIZ + "panel.html";
        }
    });

document.getElementById("form-login").addEventListener("submit", async function (evento) {
    evento.preventDefault();

    var usuario = document.getElementById("usuario").value;
    var contrasena = document.getElementById("contrasena").value;

    try {
        await llamarApi(RAIZ + "api/login.php", opcionesPost({ usuario: usuario, contrasena: contrasena }));
        window.location.href = RAIZ + "panel.html";
    } catch (error) {
        mostrarMensaje(error.message, "error");
    }
});
