<?php
	/*
	| PENSIÓN POR ALUMNO: alumnos activos con la pensión que pagan (su
	| descuento vigente o, si no tienen, la de su sede). Traído de ADF Pedro
	| Larrea v1 (reportePensionAlumno); CDJG v1 tenia la misma consulta.
	|
	| Las sedes se acotan en el servidor con la regla de la sesión
	| (reporteController::pensionAlumno -> condicionSede).
	| Exportar (copiar / Excel / imprimir) exige permiso_exportar en el menú:
	| la información de menores sale del sistema solo si alguien lo decidió.
	*/
	use app\controllers\reporteController;
	$insReporte = new reporteController();

	$sedeid       = max(0, (int)($_POST['alumno_sedeid'] ?? 0));
	$opcionesSede = $insReporte->opcionesSedeAsistencia($sedeid);
	$ofrecerTodas = $insReporte->ofrecerTodasLasSedes();
	/* Con una sola sede y sin «Todas», el combo la trae elegida: la consulta
	   tiene que usar esa misma, no la 0. */
	if (!$ofrecerTodas && $sedeid === 0 && preg_match('/value="(\d+)"/', $opcionesSede, $primeraSede)) {
		$sedeid = (int)$primeraSede[1];
	}
	$exportar = puede_exportar('reportePensionAlumno');

	/* La cabecera es comun a todas las vistas: ds_futbol/app/views/inc/cabecera.php */
	$tituloVista = 'Pensión por alumno';
	$extras      = array (0 => 'datatables',1 => 'swal',);
	require_once "app/views/inc/cabecera.php";
?>
  <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper ds-core">
      <!-- Navbar -->
      <?php require_once "app/views/inc/navbar.php"; ?>
      <!-- /.navbar -->

      <!-- Main Sidebar Container -->
      <?php require_once "app/views/inc/main-sidebar.php"; ?>
      <!-- /.Main Sidebar Container -->

      <!-- vista -->
      <div class="app-main">

		<!-- Content Header (Page header) -->
		<div class="app-content-header">
			<div class="container-fluid">
			<div class="row mb-2">
				<div class="col-sm-6">
				<h1 class="m-0">Pensión por alumno</h1>
				</div><!-- /.col -->
				<div class="col-sm-6">
				<ol class="breadcrumb float-sm-end">
					<li class="breadcrumb-item"><a href="#">Reportes</a></li>
					<li class="breadcrumb-item active">Pensión por alumno</li>
				</ol>
				</div><!-- /.col -->
			</div><!-- /.row -->
			</div><!-- /.container-fluid -->
		</div>
		<!-- /.content-header -->

		<section class="app-content">
			<form action="<?php echo APP_URL."reportePensionAlumno/" ?>" method="POST" autocomplete="off">
			<div class="container-fluid">
				<div class="card card-default">
					<div class="card-header">
						<h3 class="card-title">Criterios de búsqueda</h3>
						<div class="card-tools">
							<button type="button" class="btn btn-tool" data-lte-toggle="card-collapse" title="Plegar o desplegar" aria-label="Plegar o desplegar"><i data-lte-icon="expand" class="fas fa-plus"></i><i data-lte-icon="collapse" class="fas fa-minus"></i></button>
						</div>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-3">
								<div class="mb-3">
								<label for="alumno_sedeid" class="form-label">Sede</label>
								<select class="form-select" id="alumno_sedeid" name="alumno_sedeid">
									<?php if ($ofrecerTodas): ?>
										<option value="0" <?php echo $sedeid === 0 ? 'selected' : ''; ?>>Todas</option>
									<?php endif; ?>
									<?php echo $opcionesSede; ?>
								</select>
								</div>
							</div>
							<div class="col-md-3 d-flex align-items-end">
								<div class="mb-3 w-100">
									<?php echo ds_boton_buscar(); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			</form>
			<div class="container-fluid">
				<div class="card card-default">
					<div class="card-header">
						<h3 class="card-title">Resultado de la búsqueda</h3>
						<div class="card-tools">
							<button type="button" class="btn btn-tool" data-lte-toggle="card-collapse" title="Plegar o desplegar" aria-label="Plegar o desplegar"><i data-lte-icon="expand" class="fas fa-plus"></i><i data-lte-icon="collapse" class="fas fa-minus"></i></button>
						</div>
					</div>
					<div class="card-body">
						<div class="table-responsive">
						<table id="example1" class="table table-bordered table-striped table-sm" style="font-size: 13px;">
							<thead>
								<tr>
									<th>Sede</th>
									<th>Identificación</th>
									<th>Apellidos</th>
									<th>Nombres</th>
									<th>Pensión</th>
									<th>Detalle</th>
									<th>Fecha inicio dscto</th>
								</tr>
							</thead>
							<tbody>
								<?php echo $insReporte->pensionAlumno($sedeid); ?>
							</tbody>
						</table>
						</div>
					</div>
				</div>
			</div><!-- /.container-fluid -->
		</section>
		<!-- /.section -->
      </div>
      <!-- /.vista -->
      <?php require_once "app/views/inc/footer.php"; ?>
    </div>
    <!-- ./wrapper -->
	<script src="<?php echo APP_URL; ?>app/views/dist/plugins/jquery/jquery.min.js"></script>
	<script src="<?php echo DS_HUB_URL; ?>ds_core/assets/vendor/overlayscrollbars/js/overlayscrollbars.browser.es6.min.js"></script>
	<script src="<?php echo DS_HUB_URL; ?>ds_core/assets/vendor/bootstrap5/js/bootstrap.bundle.min.js"></script>
	<script src="<?php echo DS_HUB_URL; ?>ds_core/assets/vendor/datatables2/js/dataTables.min.js"></script>
	<script src="<?php echo DS_HUB_URL; ?>ds_core/assets/vendor/datatables2/js/dataTables.bootstrap5.min.js"></script>
	<script src="<?php echo DS_HUB_URL; ?>ds_core/assets/vendor/datatables2/js/dataTables.responsive.min.js"></script>
	<script src="<?php echo DS_HUB_URL; ?>ds_core/assets/vendor/datatables2/js/responsive.bootstrap5.min.js"></script>
	<?php if ($exportar): ?>
	<script src="<?php echo DS_HUB_URL; ?>ds_core/assets/vendor/datatables2/js/dataTables.buttons.min.js"></script>
	<script src="<?php echo DS_HUB_URL; ?>ds_core/assets/vendor/datatables2/js/buttons.bootstrap5.min.js"></script>
	<script src="<?php echo DS_HUB_URL; ?>ds_core/assets/vendor/datatables2/js/buttons.html5.min.js"></script>
	<?php /* jszip se trae al pulsar Excel, no en cada carga (exportar.js). */ ?>
	<script src="<?php echo ds_recurso('ds_core/assets/js/exportar.js'); ?>"></script>
	<script src="<?php echo DS_HUB_URL; ?>ds_core/assets/vendor/datatables2/js/buttons.print.min.js"></script>
	<?php endif; ?>
	<script src="<?php echo DS_HUB_URL; ?>ds_core/assets/vendor/adminlte4/js/adminlte.min.js"></script>
	<script src="<?php echo ds_recurso('ds_futbol/app/views/dist/js/ajax.js'); ?>" ></script>
	<script src="<?php echo ds_recurso('ds_futbol/app/views/dist/js/main.js'); ?>" ></script>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		var opciones = {
			"responsive": true, "lengthChange": false, "autoWidth": false,
			"order": [],
			"language": {
				"emptyTable": "No hay alumnos activos con estos criterios",
				"info": "Mostrando _START_ a _END_ de _TOTAL_ alumnos",
				"infoEmpty": "Sin alumnos",
				"infoFiltered": "(filtrado de _MAX_ alumnos)",
				"search": "Buscar:",
				"zeroRecords": "Ningún alumno coincide con la búsqueda",
				"paginate": { "first": "Primero", "last": "Último", "next": "Siguiente", "previous": "Anterior" },
				"buttons": { "copy": "Copiar", "print": "Imprimir" }
			},
			layout: { topStart: <?php echo $exportar ? "'buttons'" : 'null'; ?> }
		};

		<?php if ($exportar): ?>
		var sede = document.getElementById('alumno_sedeid');
		var titulo = 'Pensión por alumno | Sede: ' + sede.options[sede.selectedIndex].text.trim();
		var todas = { modifier: { page: 'all', search: 'applied', order: 'applied' } };
		opciones.buttons = [
			{ extend: 'copy', exportOptions: todas },
			{ extend: 'excel', text: '<i class="fas fa-file-excel me-1"></i>Excel', title: titulo, exportOptions: todas },
			{ extend: 'print', text: '<i class="fas fa-print me-1"></i>Imprimir', title: titulo, exportOptions: todas }
		];
		<?php endif; ?>

		new DataTable('#example1', opciones);
	});
	</script>
  </body>
</html>
