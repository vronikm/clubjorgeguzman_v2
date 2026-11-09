<?php
	/*
	| BECAS Y DESCUENTOS: alumnos con beca o descuento vigente, lo que se les
	| condona, su asistencia del periodo y si estan al dia. Traido de CDJG v1
	| (reporteBecas).
	|
	| Las sedes se acotan en el servidor con la regla de la sesion
	| (reporteController::becasDescuentos -> condicionSede).
	| Exportar (copiar / Excel / imprimir) exige permiso_exportar en el menu:
	| la informacion de menores sale del sistema solo si alguien lo decidio.
	*/
	use app\controllers\reporteController;
	$insReporte = new reporteController();

	$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
	$dinero = static fn($v) => number_format((float)$v, 2, '.', ',');

	$sedeid       = max(0, (int)($_POST['alumno_sedeid'] ?? 0));
	$opcionesSede = $insReporte->opcionesSedeAsistencia($sedeid);
	$ofrecerTodas = $insReporte->ofrecerTodasLasSedes();
	/* Con una sola sede y sin «Todas», el combo la trae elegida: la consulta
	   tiene que usar esa misma, no la 0. */
	if (!$ofrecerTodas && $sedeid === 0 && preg_match('/value="(\d+)"/', $opcionesSede, $primeraSede)) {
		$sedeid = (int)$primeraSede[1];
	}

	$tipo = (string)($_POST['descuento_tipo'] ?? '');
	if (!preg_match('/^[A-Za-z0-9]{1,3}$/', $tipo)) { $tipo = ''; }
	$estado = (($_POST['alumno_estado'] ?? 'A') === 'T') ? 'T' : 'A';

	/* Periodo de asistencia (input type=month: AAAA-MM). Por defecto, los ultimos 6 meses. */
	$mesValido = static fn($v) => is_string($v) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $v);
	$mesIni = $mesValido($_POST['periodo_ini'] ?? null) ? $_POST['periodo_ini'] : date('Y-m', strtotime('first day of -5 months'));
	$mesFin = $mesValido($_POST['periodo_fin'] ?? null) ? $_POST['periodo_fin'] : date('Y-m');
	if ($mesIni > $mesFin) { [$mesIni, $mesFin] = [$mesFin, $mesIni]; }

	$filas   = $insReporte->becasDescuentos($sedeid, $tipo, $estado, str_replace('-', '', $mesIni), str_replace('-', '', $mesFin));
	$resumen = $insReporte->resumenBecasDescuentos($filas);
	$exportar = puede_exportar('reporteBecas');

	/* La cabecera es comun a todas las vistas: ds_futbol/app/views/inc/cabecera.php */
	$tituloVista = 'Becas y descuentos';
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

		<div class="app-content-header">
			<div class="container-fluid">
			<div class="row mb-2">
				<div class="col-sm-6">
				<h1 class="m-0">Becas y descuentos</h1>
				</div>
				<div class="col-sm-6">
				<ol class="breadcrumb float-sm-end">
					<li class="breadcrumb-item"><a href="#">Reportes</a></li>
					<li class="breadcrumb-item active">Becas y descuentos</li>
				</ol>
				</div>
			</div>
			</div>
		</div>

		<section class="app-content">
			<form action="<?php echo APP_URL."reporteBecas/" ?>" method="POST" autocomplete="off">
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
							<div class="col-md-2">
								<div class="mb-3">
								<label for="descuento_tipo" class="form-label">Tipo</label>
								<select class="form-select" id="descuento_tipo" name="descuento_tipo">
									<?php echo $insReporte->opcionesTipoDescuento($tipo); ?>
								</select>
								</div>
							</div>
							<div class="col-md-2">
								<div class="mb-3">
								<label for="alumno_estado" class="form-label">Alumnos</label>
								<select class="form-select" id="alumno_estado" name="alumno_estado">
									<option value="A" <?php echo $estado === 'A' ? 'selected' : ''; ?>>Solo activos</option>
									<option value="T" <?php echo $estado === 'T' ? 'selected' : ''; ?>>Activos e inactivos</option>
								</select>
								</div>
							</div>
							<div class="col-md-2">
								<div class="mb-3">
								<label for="periodo_ini" class="form-label">Asistencia desde</label>
								<input type="month" class="form-control" id="periodo_ini" name="periodo_ini" value="<?php echo $h($mesIni); ?>">
								</div>
							</div>
							<div class="col-md-2">
								<div class="mb-3">
								<label for="periodo_fin" class="form-label">Asistencia hasta</label>
								<input type="month" class="form-control" id="periodo_fin" name="periodo_fin" value="<?php echo $h($mesFin); ?>">
								</div>
							</div>
							<div class="col-md-1 d-flex align-items-end">
								<div class="mb-3 w-100">
									<?php echo ds_boton_buscar(); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			</form>

			<!-- Resumen -->
			<div class="container-fluid">
				<div class="row g-2 g-md-3 mb-3" aria-label="Resumen">
					<?php foreach ([
						['Alumnos con beneficio', (string)$resumen['alumnos'],
						 $resumen['becas'] . ' beca(s) · ' . $resumen['descuentos'] . ' descuento(s)', 'text-bg-primary', 'fa-hand-holding-heart'],
						['Beca otorgada al mes', '$ ' . $dinero($resumen['beca_mensual']),
						 $resumen['ahorro_pct'] . '% de la pensión plena', 'text-bg-warning', 'fa-percentage'],
						['Pagan a la escuela al mes', '$ ' . $dinero($resumen['ingreso_mensual']),
						 'sobre $ ' . $dinero($resumen['pension_plena']) . ' de pensión plena', 'text-bg-success', 'fa-dollar-sign'],
						['Becados en mora', (string)$resumen['en_mora'],
						 '$ ' . $dinero($resumen['deuda']) . ' por cobrar · asistencia media ' . ($resumen['asistencia'] === null ? 's/d' : $resumen['asistencia'] . '%'),
						 $resumen['en_mora'] > 0 ? 'text-bg-danger' : 'text-bg-secondary', 'fa-exclamation-triangle'],
					] as [$texto, $valor, $detalle, $clase, $icono]): ?>
						<div class="col-6 col-lg-3">
							<div class="info-box mb-0 h-100">
								<span class="info-box-icon <?php echo $clase; ?> shadow-sm"><i class="fas <?php echo $icono; ?>" aria-hidden="true"></i></span>
								<div class="info-box-content">
									<span class="info-box-text"><?php echo $h($texto); ?></span>
									<span class="info-box-number"><?php echo $h($valor); ?></span>
									<small class="text-body-secondary"><?php echo $h($detalle); ?></small>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="container-fluid">
				<div class="card card-default">
					<div class="card-header">
						<h3 class="card-title">Detalle · asistencia de <?php echo $h($mesIni); ?> a <?php echo $h($mesFin); ?></h3>
						<div class="card-tools">
							<button type="button" class="btn btn-tool" data-lte-toggle="card-collapse" title="Plegar o desplegar" aria-label="Plegar o desplegar"><i data-lte-icon="expand" class="fas fa-plus"></i><i data-lte-icon="collapse" class="fas fa-minus"></i></button>
						</div>
					</div>
					<div class="card-body">
						<div class="table-responsive">
						<table id="example1" class="table table-bordered table-striped table-sm align-middle" style="font-size: 13px;">
							<thead>
								<tr>
									<th>Sede</th>
									<th>Identificación</th>
									<th>Alumno</th>
									<th>Tipo</th>
									<th>Motivo</th>
									<th>Desde</th>
									<th class="text-end">Pensión base</th>
									<th class="text-end">Beca / dscto.</th>
									<th class="text-end">% beca</th>
									<th class="text-end">Paga</th>
									<th class="text-center">Asistencia</th>
									<th class="text-center">Pagos</th>
									<th class="text-end">Deuda</th>
									<th class="text-center">Ver</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($filas as $f):
									if ($f['asistencia'] === null) {
										[$claseAsis, $textoAsis] = ['text-bg-secondary', 's/d'];
									} else {
										$claseAsis = $f['asistencia'] >= 85 ? 'text-bg-success' : ($f['asistencia'] >= 70 ? 'text-bg-warning' : 'text-bg-danger');
										$textoAsis = $f['asistencia'] . '%';
									}
									/* Paga pension pero nunca registro un pago de pension:
									   no se puede afirmar que este al dia, se marca aparte. */
									$sinPagos = $f['ultimo_pago'] === '' && $f['pension_paga'] > 0;
								?>
								<tr>
									<td><?php echo $h($f['sede']); ?></td>
									<td><?php echo $h($f['identificacion']); ?></td>
									<td>
										<?php echo $h($f['alumno']); ?>
										<?php if (!$f['activo']): ?><span class="badge text-bg-secondary">Inactivo</span><?php endif; ?>
									</td>
									<td><span class="badge <?php echo $f['es_beca'] ? 'text-bg-primary' : 'text-bg-info'; ?>"><?php echo $h($f['tipo']); ?></span></td>
									<td><?php echo $h($f['detalle']); ?></td>
									<td><?php echo $h($f['desde']); ?></td>
									<td class="text-end"><?php echo $dinero($f['pension_base']); ?></td>
									<td class="text-end"><?php echo $dinero($f['beca_valor']); ?></td>
									<td class="text-end"><?php echo number_format($f['beca_pct'], 1); ?>%</td>
									<td class="text-end"><?php echo $dinero($f['pension_paga']); ?></td>
									<td class="text-center" data-order="<?php echo $f['asistencia'] ?? -1; ?>">
										<span class="badge <?php echo $claseAsis; ?>"><?php echo $textoAsis; ?></span>
										<br><small class="text-body-secondary"><?php echo $f['asistidos']; ?> asist. / <?php echo $f['faltas']; ?> F</small>
									</td>
									<td class="text-center">
										<?php if (!$f['al_dia']): ?>
											<span class="badge text-bg-danger">En mora</span>
											<?php if ($f['meses_vencidos'] > 0): ?><br><small class="text-body-secondary"><?php echo $f['meses_vencidos']; ?> mes(es)</small><?php endif; ?>
										<?php elseif ($sinPagos): ?>
											<span class="badge text-bg-warning">Sin pagos</span>
										<?php else: ?>
											<span class="badge text-bg-success">Al día</span>
											<?php if ($f['ultimo_pago'] !== ''): ?><br><small class="text-body-secondary"><?php echo $h($f['ultimo_pago']); ?></small><?php endif; ?>
										<?php endif; ?>
									</td>
									<td class="text-end"><?php echo $dinero($f['deuda']); ?></td>
									<td class="text-center">
										<a href="<?php echo APP_URL . 'alumnoProfile/' . $f['alumno_id'] . '/'; ?>" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm" title="Ver ficha" aria-label="Ver la ficha de <?php echo $h($f['alumno']); ?>"><i class="fas fa-id-card" aria-hidden="true"></i></a>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
							<tfoot>
								<tr class="fw-bold">
									<td colspan="6" class="text-end">TOTALES</td>
									<td class="text-end"><?php echo $dinero($resumen['pension_plena']); ?></td>
									<td class="text-end"><?php echo $dinero($resumen['beca_mensual']); ?></td>
									<td class="text-end"><?php echo $resumen['ahorro_pct']; ?>%</td>
									<td class="text-end"><?php echo $dinero($resumen['ingreso_mensual']); ?></td>
									<td class="text-center"><?php echo $resumen['asistencia'] === null ? 's/d' : $resumen['asistencia'] . '%'; ?></td>
									<td class="text-center"><?php echo (int)$resumen['en_mora']; ?> en mora</td>
									<td class="text-end"><?php echo $dinero($resumen['deuda']); ?></td>
									<td></td>
								</tr>
							</tfoot>
						</table>
						</div>
						<small class="text-body-secondary d-block mt-2">
							El descuento se registra como la pensión que el alumno paga; la beca es la diferencia con la pensión de la sede.
							La asistencia es la de «Ver registro»: presentes y atrasos sobre los días registrados del período (el justificado cuenta como día).
							El estado de pagos suma los saldos pendientes y las pensiones vencidas, con el mismo criterio de mora de la v1;
							para alumnos inactivos solo cuentan los saldos.
						</small>
					</div>
				</div>
			</div>
		</section>
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
			"responsive": true, "autoWidth": false,
			"lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
			"pageLength": 25,
			"order": [[7, "desc"]],
			"language": {
				"emptyTable": "No hay alumnos con beca o descuento vigente para estos criterios",
				"info": "Mostrando _START_ a _END_ de _TOTAL_ alumnos",
				"infoEmpty": "Sin alumnos",
				"infoFiltered": "(filtrado de _MAX_ alumnos)",
				"lengthMenu": "Mostrar _MENU_",
				"search": "Buscar:",
				"zeroRecords": "Ningún alumno coincide con la búsqueda",
				"paginate": { "first": "Primero", "last": "Último", "next": "Siguiente", "previous": "Anterior" },
				"buttons": { "copy": "Copiar", "print": "Imprimir" }
			},
			layout: { topStart: <?php echo $exportar ? "['pageLength', 'buttons']" : "'pageLength'"; ?> }
		};

		<?php if ($exportar): ?>
		var sede = document.getElementById('alumno_sedeid');
		var titulo = 'Becas y descuentos | Sede: ' + sede.options[sede.selectedIndex].text.trim();
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
