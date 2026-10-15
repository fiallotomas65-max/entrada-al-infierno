var contadorPreguntas = 0;

document.addEventListener("DOMContentLoaded", async function () {
    var usuario = await iniciarPaginaPrivada();
    if (!usuario) return;

    var btnAgregar = document.getElementById("btn-agregar-pregunta");
    if (btnAgregar) {
        btnAgregar.addEventListener("click", function () {
            agregarTarjetaPregunta();
        });
    }

    agregarTarjetaPregunta();

    var form = document.getElementById("form-crear-encuesta");
    if (form) {
        form.addEventListener("submit", procesarGuardadoEncuesta);
    }
});

function agregarTarjetaPregunta() {
    contadorPreguntas++;
    var numP = contadorPreguntas;
    var contenedor = document.getElementById("contenedor-preguntas");

    var card = document.createElement("div");
    card.className = "tarjeta-pregunta";
    card.id = "pregunta-card-" + numP;
    card.style.cssText = "background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #d1d5db; position: relative;";

    card.innerHTML = 
        '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">' +
            '<span style="font-weight: 700; color: #1f2937;">Pregunta ' + numP + '</span>' +
            '<button type="button" onclick="eliminarPregunta(' + numP + ')" style="background: #ef4444; color: #fff; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">Eliminar Pregunta</button>' +
        '</div>' +
        '<div style="margin-bottom: 12px;">' +
            '<label style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 4px;">Texto de la pregunta</label>' +
            '<input type="text" class="input-texto-pregunta" placeholder="Escriba la pregunta aquí..." required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">' +
        '</div>' +
        '<div style="margin-bottom: 12px;">' +
            '<label style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 4px;">Tipo de respuesta</label>' +
            '<select class="select-tipo-pregunta" onchange="cambiarTipoPregunta(' + numP + ', this.value)" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">' +
                '<option value="cerrada">Opción Múltiple (Cerrada)</option>' +
                '<option value="abierta">Texto Libre (Abierta)</option>' +
            '</select>' +
        '</div>' +
        '<div class="contenedor-opciones" id="opciones-contenedor-' + numP + '" style="margin-top: 15px; background: #f9fafb; padding: 12px; border-radius: 6px; border: 1px solid #e5e7eb;">' +
            '<label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px;">Opciones de respuesta</label>' +
            '<div class="lista-opciones" id="lista-opciones-' + numP + '" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 10px;">' +
            '</div>' +
            '<button type="button" onclick="agregarOpcion(' + numP + ')" style="background: #10b981; color: #fff; border: none; padding: 5px 12px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+ Agregar Opción</button>' +
        '</div>';

    contenedor.appendChild(card);

    agregarOpcion(numP, "Excelente");
    agregarOpcion(numP, "Buena");
    agregarOpcion(numP, "Regular");
    agregarOpcion(numP, "Mala");
}

function eliminarPregunta(numP) {
    var card = document.getElementById("pregunta-card-" + numP);
    if (card) {
        card.remove();
    }
}

function cambiarTipoPregunta(numP, tipo) {
    var contenedorOpciones = document.getElementById("opciones-contenedor-" + numP);
    if (tipo === "abierta") {
        contenedorOpciones.style.display = "none";
    } else {
        contenedorOpciones.style.display = "block";
    }
}

function agregarOpcion(numP, textoPredeterminado) {
    var lista = document.getElementById("lista-opciones-" + numP);
    if (!lista) return;

    var valor = textoPredeterminado || "";
    var div = document.createElement("div");
    div.className = "item-opcion";
    div.style.cssText = "display: flex; gap: 8px; align-items: center;";

    div.innerHTML = 
        '<input type="text" class="input-opcion-texto" value="' + escaparTexto(valor) + '" placeholder="Texto de la opción" required style="flex: 1; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">' +
        '<button type="button" onclick="this.parentElement.remove()" style="background: #dc2626; color: #fff; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">&times;</button>';

    lista.appendChild(div);
}

async function procesarGuardadoEncuesta(e) {
    e.preventDefault();

    var titulo = document.getElementById("titulo").value.trim();
    var descripcion = document.getElementById("descripcion").value.trim();

    var tarjetas = document.querySelectorAll(".tarjeta-pregunta");
    if (tarjetas.length === 0) {
        mostrarMensaje("Debe agregar al menos una pregunta.", "error");
        return;
    }

    var preguntas = [];
    var errorValidacion = null;

    tarjetas.forEach(function (card) {
        if (errorValidacion) return;

        var textoPregunta = card.querySelector(".input-texto-pregunta").value.trim();
        var tipoPregunta = card.querySelector(".select-tipo-pregunta").value;

        if (!textoPregunta) {
            errorValidacion = "Todas las preguntas deben tener un texto escrito.";
            return;
        }

        var opcionesArray = [];
        if (tipoPregunta === "cerrada") {
            var inputsOpciones = card.querySelectorAll(".input-opcion-texto");
            inputsOpciones.forEach(function (inp) {
                var txt = inp.value.trim();
                if (txt !== "") {
                    opcionesArray.push(txt);
                }
            });

            if (opcionesArray.length < 2) {
                errorValidacion = "Las preguntas de opción múltiple deben tener al menos 2 opciones.";
                return;
            }
        }

        preguntas.push({
            texto: textoPregunta,
            tipo: tipoPregunta,
            opciones: opcionesArray
        });
    });

    if (errorValidacion) {
        mostrarMensaje(errorValidacion, "error");
        return;
    }

    var payload = {
        titulo: titulo,
        descripcion: descripcion,
        preguntas: preguntas
    };

    try {
        var respuesta = await fetch(RAIZ + "api/encuesta_guardar.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });

        var datos = await respuesta.json();

        if (!datos.ok) {
            throw new Error(datos.mensaje || "Error al guardar la encuesta.");
        }

        guardarMensaje("Encuesta creada correctamente.", "exito");
        window.location.href = "encuestas.html";
    } catch (err) {
        mostrarMensaje(err.message, "error");
    }
}