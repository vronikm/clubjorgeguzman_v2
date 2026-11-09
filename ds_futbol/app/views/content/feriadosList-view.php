<?php
	/*
	| FERIADOS: dias sin entrenamiento. Traido de CDJG v1 (feriadosList).
	| «Ver asistencias» los sombrea como los fines de semana. Ver feriadoController.
	|
	| /feriadosList/      alta + listado (filtro de anio por POST)
	| /feriadosList/ID/   edicion de ese feriado + listado
	*/
	use app\controllers\feriadoController;
	$insFeriado = new feriadoController();

	$h = static fn($v) => htmlspecialchars(html_entity_decode((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES, 'UTF-8');

	$id = (int)($url[1] ?? 0);
	$fila = $id > 0 ? $insFeriado->buscarFeriado($id) : null;
	if ($fila === null) { $id = 0; }
	$editando = $id > 0;
	$mostrarForm = $editando ? puede('editar', 'feriadosList') : puede('crear', 'feriadosList');

	$anios = $insFeriado->aniosConFeriados();
	$anio  = (int)($_POST['anio'] ?? date('Y'));
	if ($anio !== 0 && !in_array($anio, $anios, true)) { $anio = (int)date('Y'); }

	$v = [
		'fecha'  => $fila['feriado_fecha']       ?? '',
		'desc'   => $fila['feriado_descripcion'] ?? '',
		'activo' => (string)($fila['feriado_activo'] ?? '1'),
	];

	$tituloVista = 'Feriados';
	$extras      = array (0 => 'datatables',1 => 'swal',);
	require_once "app/views/inc/cabecera.php";
?>
  <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper ds-core">
      <?php require_once "app/views/inc/navbar.php"; ?>
      <?php require_once "app/views/inc/main-sidebar.php"; ?>

      <div class="app-main">
		<div class="app-content-header">
			<div class="container-fluid">
			<div class="row mb-2">
				<div class="col-sm-6"><h1 class="m-0">Feriados</h1></div>
				<div class="col-sm-6">
				<ol class="breadcrumb float-sm-end">
					<li class="breadcrumb-item"><a href="#">Configuración</a></li>
					<li class="breadcrumb-item active">Feriados</li>
				</ol>
				</div>
			</div>
			</div>
		</div>

		<section class="app-content">
			<div class="container-fluid">
				<?php if ($mostrarForm): ?>
				<div class="card card-default" id="feriado-form">
					<div class="card-header">
						<h3 class="card-title"><?php echo $editando ? 'Editar feriado' : 'Nuevo feriado'; ?></h3>
					</div>
					<form class="FormularioAjax" action="<?php echo APP_URL; ?>app/ajax/feriadoAjax.php" method="POST" autocomplete="off">
						<input type="hidden" name="modulo_feriado" value="<?php echo $editando ? 'actualizar' : 'registrar'; ?>">
						<input type="hidden" name="feriado_id" value="<?php echo $id; ?>">
						<div class="card-body">
							<div class="row">
								<div class="col-md-3 mb-3">
									<label for="feriado_fecha" class="form-label">Fecha <span class="text-danger">*</span></label>
									<input type="date" class="form-control" id="feriado_fecha" name="feriado_fecha" required value="<?php echo $h($v['fecha']); ?>">
								</div>
								<div class="col-md-6 mb-3">
									<label for="feriado_descripcion" class="form-label">Descripción <span class="text-danger">*</span></label>
									<input type="text" class="form-control" id="feriado_descripcion" name="feriado_descripcion" maxlength="255" required value="<?php echo $h($v['desc']); ?>" placeholder="Ej.: Día del Trabajo">
								</div>
								<div class="col-md-3 mb-3">
									<label for="feriado_activo" class="form-label">Estado</label>
									<select class="form-select" id="feriado_activo" name="feriado_activo">
										<option value="1" <?php echo $v['activo'] !== '0' ? 'selected' : ''; ?>>Activo</option>
										<option value="0" <?php echo $v['activo'] === '0' ? 'selected' : ''; ?>>Inactivo</option>
									</select>
									<div class="form-text">Inactivo: se conserva pero no se sombrea en la asistencia.</div>
								</div>
							</div>
						</div>
						<?php echo ds_acciones_form(APP_URL . 'feriadosList/'); ?>
					</form>
				</div>
				<?php endif; ?>

				<div class="card card-default">
					<div class="card-header">
						<h3 class="card-title">Calendario de feriados</h3>
						<div class="card-tools">
							<form method="POST" action="<?php echo APP_URL; ?>feriadosList/" class="d-flex align-items-center gap-2">
								<label for="anio" class="form-label mb-0 small">Año</label>
								<select class="form-select form-select-sm" id="anio" name="anio" onchange="this.form.submit()">
									<option value="0" <?php echo $anio === 0 ? 'selected' : ''; ?>>Todos</option>
									<?php foreach ($anios as $a): ?>
										<option value="<?php echo $a; ?>" <?php echo $a === $anio ? 'selected' : ''; ?>><?php echo $a; ?></option>
									<?php endforeach; ?>
								</select>
							</form>
						</div>
					</div>
					<div class="card-body">
						<div class="table-responsive">
						<table id="tablaFeriados" class="table table-bordered table-striped table-sm align-middle" style="font-size: 13px;">
							<thead>
								<tr>
									<th>Fecha</th>
									<th>Día</th>
									<th>Descripción</th>
									<th>Estado</th>
									<th class="text-end">Opciones</th>
								</tr>
							</thead>
							<tbody><?php echo $insFeriado->listarFeriados($anio); ?></tbody>
						</table>
						</div>
					</div>
				</div>
			</div>
		</section>
      </div>
      <?php require_once "app/views/inc/footer.php"; ?>
    </div>
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/jquery/jquery.min.js"></script>
	<script src="<?php echo DS_HUB_URL; ?>ds_core/assets/vendor/overlayscrollbars/js/overlayscrollbars.browser.es6.min.js"></script>
	<script src="<?php echo DS_HUB_URL; ?>ds_core/assets/vendor/bootstrap5/js/bootstrap.bundle.min.js"></script>
	<script src="<?php echo DS_HUB_URL; ?>ds_core/assets/vendor/datatables2/js/dataTables.min.js"></script>
	<script src="<?php echo DS_HUB_URL; ?>ds_core/assets/vendor/datatables2/js/dataTables.bootstrap5.min.js"></script>
	<script src="<?php echo DS_HUB_URL; ?>ds_core/assets/vendor/adminlte4/js/adminlte.min.js"></script>
	<script src="<?php echo APP_URL; ?>app/views/dist/js/sweetalert2.all.min.js"></script>
	<script src="<?php echo ds_recurso('ds_futbol/app/views/dist/js/ajax.js'); ?>"></script>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		new DataTable('#tablaFeriados', {
			"autoWidth": false, "order": [[0, "desc"]], "pageLength": 25,
			"columnDefs": [{ "orderable": false, "targets": -1 }],
			"language": {
				"emptyTable": "No hay feriados en este año",
				"info": "Mostrando _START_ a _END_ de _TOTAL_ feriados",
				"infoEmpty": "Sin feriados", "infoFiltered": "(filtrado de _MAX_)",
				"lengthMenu": "Mostrar _MENU_", "search": "Buscar:",
				"zeroRecords": "Ningún feriado coincide con la búsqueda",
				"paginate": { "first": "Primero", "last": "Último", "next": "Siguiente", "previous": "Anterior" }
			}
		});
	});
	</script>
  </body>
</html>
