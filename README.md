# Clinical Data System (C.D.S.)

Sistema para el Hospital de Clinicas, desarrollado como proyecto de egreso de
3.er Bachillerato en Tecnologia de la Informacion. Esta compuesto por dos
modulos: documentacion digital para pacientes y trazabilidad de ambulancias.

## Que hace

**Documentacion para pacientes.** El personal administrativo carga documentos
informativos en el sistema. El paciente los consulta desde el celular
escaneando un codigo QR fijo pegado en la pared del hospital, sin necesidad de
iniciar sesion. Desde el mismo lugar puede completar la encuesta anonima de
satisfaccion, cuyos resultados se ven agregados en el panel administrativo.

**Trazabilidad de ambulancias.** Permite registrar ambulancias, personal de
transporte y rutas del circuito nacional, y armar traslados asociando esos
datos. Cada traslado avanza por un flujo de estados controlado (Pendiente, En
curso, Finalizado, Cancelado) y queda un historial de cada cambio, con su
usuario, fecha y comentario.

## Tecnologias y arquitectura

- **Frontend:** HTML y CSS escritos a mano, y JavaScript sin frameworks. Cada
  pantalla es un archivo `.html` real; no hay PHP mezclado en el marcado.
- **Backend:** PHP sin frameworks, expuesto como una pequena API en `/api`
  que recibe peticiones y devuelve JSON. Las paginas HTML llaman a esa API
  con `fetch()` desde `assets/js/`.
- **Base de datos:** MySQL/MariaDB.

Esta separacion (HTML+JS del lado del navegador, PHP del lado del servidor
respondiendo solo datos) es mas simple de seguir que mezclar PHP dentro del
HTML, y es la razon por la que el punto de entrada es `index.html` y no
`index.php`.

**Como el sitio encuentra su propia carpeta.** Como todas las paginas son
HTML puro, no hay un `BASE_URL` de PHP calculando las rutas. En su lugar,
`assets/js/comun.js` calcula solo, al cargarse, la URL completa desde la que
el navegador lo pidio (mirando su propio `<script src="...">`), y guarda esa
direccion en una variable `RAIZ`. Todas las llamadas a la API y todos los
enlaces entre pantallas se arman con `RAIZ + "algo"`. Gracias a esto, el
sitio funciona igual si se instala en la raiz de un dominio
(`http://localhost/`) o en una subcarpeta de `htdocs`
(`http://localhost/Clinical_Data_System/`), sin tener que tocar ningun
archivo.

## Instalacion

1. Copiar la carpeta `Clinical_Data_System` dentro de `htdocs` de XAMPP.
2. Levantar Apache y MySQL desde el panel de XAMPP.
3. Importar `database/schema.sql` desde phpMyAdmin (crea la base
   `cds_hospital` con sus tablas y datos de prueba).
4. Entrar a `http://localhost/Clinical_Data_System/`.

## Usuarios de prueba

Las contrasenas ya estan hasheadas con bcrypt en `schema.sql` (son hashes
reales y distintos para cada usuario, listos para usar tal cual).

| Usuario     | Rol            | Contrasena       |
|-------------|----------------|------------------|
| mrodriguez  | Administrador  | direccion2024    |
| vfernandez  | Recepcionista  | recepcion2024    |
| jalvarez    | Coordinador    | coordina2024     |

Para dar de alta un usuario nuevo hay que generar su hash con
`password_hash($clave, PASSWORD_BCRYPT)` en PHP e insertarlo directamente en
la tabla `usuarios` (no hay pantalla de alta de usuarios en este proyecto).

## Estructura del proyecto