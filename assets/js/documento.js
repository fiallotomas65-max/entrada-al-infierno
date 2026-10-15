document.addEventListener("DOMContentLoaded", async function () {
    var usuario = await iniciarPaginaPrivada();
    if (!usuario) return;

    var formCarga = document.getElementById("form-carga");

    if (formCarga) {
        formCarga.addEventListener("submit", async function (e) {
            e.preventDefault();

            var botonSubmit = formCarga.querySelector('button[type="submit"]');
            var textoOriginalBoton = botonSubmit ? botonSubmit.innerHTML : '';

            if (botonSubmit) {
                botonSubmit.disabled = true;
                botonSubmit.innerText = "Guardando...";
            }

            var formData = new FormData(formCarga);

            try {
                var respuesta = await fetch(RAIZ + "api/documento_guardar.php", {
                    method: "POST",
                    body: formData
                });

                var datos = await respuesta.json();

                if (!datos.ok) {
                    throw new Error(datos.mensaje || "No se pudo guardar el documento.");
                }

                guardarMensaje("Documento guardado correctamente.", "exito");
                window.location.href = "documentos.html";
            } catch (err) {
                mostrarMensaje(err.message, "error");
                if (botonSubmit) {
                    botonSubmit.disabled = false;
                    botonSubmit.innerHTML = textoOriginalBoton;
                }
            }
        });
    }
});