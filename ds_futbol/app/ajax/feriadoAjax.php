<?php
	/* Propio de CDJG (traido de su v1): ver app/controllers/feriadoController.php */
	require_once __DIR__ . "/../../../ds_core/inc/errores.php"; // antes que config: si falla, sin rutas a la vista
	require_once "../../config/app.php";
	require_once "../views/inc/session_start.php";
	require_once "../../autoload.php";

	/* Guardia: sesion + origen + permiso de rol. La operacion (modulo_feriado)
	   dice la accion: registrar = crear, actualizar = editar, eliminar. */
	$GUARD_VISTA = "feriadosList";
	require_once "../views/inc/ajax_guard.php";

	use app\controllers\feriadoController;

	$operaciones = [
		'registrar' => 'registrarFeriado',
		'actualizar' => 'actualizarFeriado',
		'eliminar' => 'eliminarFeriado',
	];

	$operacion = (string)($_POST['modulo_feriado'] ?? '');
	if (isset($operaciones[$operacion])) {
		$ins = new feriadoController();
		echo $ins->{$operaciones[$operacion]}();
	} else {
		session_destroy();
		header("Location: " . APP_URL . "login/");
	}
