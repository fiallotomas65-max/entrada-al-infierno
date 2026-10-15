<?php
// Reemplaza 'sidebar.php' por el nombre del archivo de tu menú/sidebar si se llama distinto:
if (file_exists(__DIR__ . '/sidebar.php')) include_once __DIR__ . '/sidebar.php';
if (file_exists(__DIR__ . '/header.php')) include_once __DIR__ . '/header.php';
if (file_exists(__DIR__ . '/menu.php')) include_once __DIR__ . '/menu.php';
?>

<!-- Íconos y Estilos -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<style>
    .card-ruta {
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card-ruta:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1) !important;
    }
</style>

<div class="container-fluid p-4">
    <!-- Encabezado superior -->
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-3 shadow-sm border">
        <div>
            <h3 class="fw-bold m-0 text-dark"><i class="bi bi-map text-primary me-2"></i>Gestión de Rutas</h3>
            <p class="text-muted small m-0">Administra orígenes, destinos y tiempos estimados</p>
        </div>
        <button type="button" class="btn btn-primary fw-semibold px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalRuta">
            <i class="bi bi-plus-circle me-2"></i>Nueva Ruta
        </button>
    </div>

    <!-- Contenedor donde JS cargará las tarjetas estilizadas -->
    <div id="listaRutas">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2">Cargando rutas...</p>
        </div>
    </div>
</div>

<!-- Modal para Nueva Ruta -->
<div class="modal fade" id="modalRuta" tabindex="-1" aria-labelledby="modalRutaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalRutaLabel"><i class="bi bi-geo-alt-fill me-2"></i>Registrar Nueva Ruta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formRuta">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="nombre_ruta" class="form-label fw-semibold">Nombre de la Ruta *</label>
                        <input type="text" class="form-control" id="nombre_ruta" name="nombre_ruta" placeholder="Ej: Ruta Central 01" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="origen" class="form-label fw-semibold">Origen *</label>
                            <input type="text" class="form-control" id="origen" name="origen" placeholder="Punto A" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="destino" class="form-label fw-semibold">Destino *</label>
                            <input type="text" class="form-control" id="destino" name="destino" placeholder="Punto B" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="distancia_km" class="form-label fw-semibold">Distancia (km)</label>
                            <input type="number" step="0.01" class="form-control" id="distancia_km" name="distancia_km" placeholder="Ej: 45.5">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tiempo_estimado_min" class="form-label fw-semibold">Tiempo Estimado (min)</label>
                            <input type="number" class="form-control" id="tiempo_estimado_min" name="tiempo_estimado_min" placeholder="Ej: 60">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="2" placeholder="Notas adicionales..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">Guardar Ruta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Lógica JavaScript -->
<script src="js/rutas.js"></script>

<?php
if (file_exists(__DIR__ . '/footer.php')) include_once __DIR__ . '/footer.php';
?>