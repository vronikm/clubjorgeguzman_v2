<?php
	/*
	| INSTITUCIONES EDUCATIVAS: la «Unidad Educativa» de cada alumno.
	| Traido de CDJG v1 (institucionList). Ver institucionController.
	|
	| /institucionList/      alta + listado
	| /institucionList/ID/   edicion de esa institucion + listado
	*/
	use app\controllers\institucionController;
	$insInstitucion = new institucionController();

	$h = static fn($v) => htmlspecialchars(html_entity_decode((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES, 'UTF-8');

	$id = (int)($url[1] ?? 0);
	$fila = $id > 0 ? $insInstitucion->buscarInstitucion($id) : null;
	if ($fila === null) { $id = 0; }
	$editando = $id > 0;

	/* Sin permiso para la operacion, el formulario no se muestra: el guardia
	   del endpoint lo rechazaria igual, pero no hay que ofrecerlo. */
	$mostrarForm = $editando ? puede('editar', 'institucionList') : puede('crear', 'institucionList');

	$v = [
		'nombre'    => $fila['institucion_nombre']    ?? '',
		'direccion' => $fila['institucion_direccion'] ?? '',
		'email'     => $fila['institucion_email']     ?? '',
		'telefono'  => $fila['institucion_telefono']  ?? '',
		'estado'    => $fila['institucion_estado']    ?? 'A',
	];

	$tituloVista = 'Instituciones educativas';
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
				<div class="col-sm-6"><h1 class="m-0">Instituciones educativas</h1></div>
				<div class="col-sm-6">
				<ol class="breadcrumb float-sm-end">
					<li class="breadcrumb-item"><a href="#">Configuración</a></li>
					<li class="breadcrumb-item active">Institución</li>
				</ol>
				</div>
			</div>
			</div>
		</div>

		<section class="app-content">
			<div class="container-fluid">
				<?php if ($mostrarForm): ?>
				<div class="card card-default" id="institucion-form">
					<div class="card-header">
						<h3 class="card-title"><?php echo $editando ? 'Editar institución' : 'Nueva institución'; ?></h3>
					</div>
					<form class="FormularioAjax" action="<?php echo APP_URL; ?>app/ajax/institucionAjax.php" method="POST" autocomplete="off">
						<input type="hidden" name="modulo_institucion" value="<?php echo $editando ? 'actualizar' : 'registrar'; ?>">
						<input type="hidden" name="institucion_id" value="<?php echo $id; ?>">
						<div class="card-body">
							<div class="row">
								<div class="col-md-4 mb-3">
									<label for="institucion_nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
									<input type="text" class="form-control" id="institucion_nombre" name="institucion_nombre" maxlength="100" required value="<?php echo $h($v['nombre']); ?>">
								</div>
								<div class="col-md-4 mb-3">
									<label for="institucion_direccion" class="form-label">Dirección</label>
									<input type="text" class="form-control" id="institucion_direccion" name="institucion_direccion" maxlength="200" value="<?php echo $h($v['direccion']); ?>">
								</div>
								<div class="col-md-4 mb-3">
									<label for="institucion_email" class="form-label">Correo</label>
									<input type="email" class="form-control" id="institucion_email" name="institucion_email" maxlength="50" value="<?php echo $h($v['email']); ?>">
								</div>
								<div class="col-md-3 mb-3">
									<label for="institucion_telefono" class="form-label">Teléfono</label>
									<input type="tel" class="form-control" id="institucion_telefono" name="institucion_telefono" maxlength="9" inputmode="numeric" pattern="[0-9]{0,9}" value="<?php echo $h($v['telefono']); ?>">
								</div>
								<div class="col-md-3 mb-3">
									<label for="institucion_estado" class="form-label">Estado</label>
									<select class="form-select" id="institucion_estado" name="institucion_estado">
										<option value="A" <?php echo $v['estado'] === 'A' ? 'selected' : ''; ?>>Activa</option>
										<option value="I" <?php echo $v['estado'] === 'I' ? 'selected' : ''; ?>>Inactiva</option>
									</select>
									<div class="form-text">Una inactiva no se ofrece a alumnos nuevos; quien ya la tiene la conserva.</div>
								</div>
							</div>
						</div>
						<?php echo ds_acciones_form(APP_URL . 'institucionList/'); ?>
					</form>
				</div>
				<?php endif; ?>

				<div class="card card-default">
					<div class="card-header"><h3 class="card-title">Instituciones registradas</h3></div>
					<div class="card-body">
						<div class="table-responsive">
						<table id="tablaInstituciones" class="table table-bordered table-striped table-sm align-middle" style="font-size: 13px;">
							<thead>
								<tr>
									<th>Nombre</th>
									<th>Dirección</th>
									<th>Correo</th>
									<th>Teléfono</th>
									<th class="text-center">Alumnos</th>
									<th>Estado</th>
									<th class="text-end">Opciones</th>
								</tr>
							</thead>
							<tbody><?php echo $insInstitucion->listarInstituciones(); ?></tbody>
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
		new DataTable('#tablaInstituciones', {
			"autoWidth": false, "order": [], "pageLength": 25,
			"columnDefs": [{ "orderable": false, "targets": -1 }],
			"language": {
				"emptyTable": "Todavía no hay instituciones registradas",
				"info": "Mostrando _START_ a _END_ de _TOTAL_ instituciones",
				"infoEmpty": "Sin instituciones", "infoFiltered": "(filtrado de _MAX_)",
				"lengthMenu": "Mostrar _MENU_", "search": "Buscar:",
				"zeroRecords": "Ninguna institución coincide con la búsqueda",
				"paginate": { "first": "Primero", "last": "Último", "next": "Siguiente", "previous": "Anterior" }
			}
		});
	});
	</script>
  </body>
</html>
