<?php

	namespace app\controllers;
	use app\models\mainModel;

	/*==================================================================
	  FERIADOS (feriadosList) — traido de CDJG v1
	  ------------------------------------------------------------------
	  Dias sin entrenamiento por feriado (asistencia_feriados, tabla propia
	  de CDJG). «Ver asistencias» los sombrea igual que los fines de semana
	  (asistenciaController::diasSinEntrenamiento).

	  Diferencias con la v1: consultas con parametros (la v1 interpolaba la
	  fecha y el id en el SQL) y la fecha se valida como fecha real, no solo
	  por su forma.
	==================================================================*/
	class feriadoController extends mainModel{

		public const DIAS = [1 => 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

		/** Un feriado por id, o null. */
		public function buscarFeriado(int $id): ?array{
			$fila = $this->ejecutarConsulta(
				"SELECT * FROM asistencia_feriados WHERE feriado_id = :id", [':id' => $id]
			)->fetch(\PDO::FETCH_ASSOC);
			return $fila ?: null;
		}

		/** Datos del formulario, validados. $id: el feriado que se edita (0 al crear). Devuelve [datos, error]. */
		private function datosFormulario(int $id): array{
			$fecha  = trim((string)($_POST['feriado_fecha'] ?? ''));
			$desc   = $this->limpiarCadena($_POST['feriado_descripcion'] ?? '');
			$activo = ($_POST['feriado_activo'] ?? '1') === '0' ? 0 : 1;

			if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
				return [null, 'La fecha no es válida.'];
			}
			if ($desc === '' || mb_strlen($desc) > 255) {
				return [null, 'La descripción es obligatoria (hasta 255 caracteres).'];
			}
			$repetido = $this->ejecutarConsulta(
				"SELECT COUNT(*) FROM asistencia_feriados WHERE feriado_fecha = :fecha AND feriado_id <> :id",
				[':fecha' => $fecha, ':id' => $id])->fetchColumn();
			if ((int)$repetido > 0) {
				return [null, 'Ya hay un feriado registrado en esa fecha.'];
			}
			return [[
				['campo_nombre' => 'feriado_fecha',       'campo_marcador' => ':Fecha',  'campo_valor' => $fecha],
				['campo_nombre' => 'feriado_descripcion', 'campo_marcador' => ':Desc',   'campo_valor' => $desc],
				['campo_nombre' => 'feriado_activo',      'campo_marcador' => ':Activo', 'campo_valor' => $activo],
			], ''];
		}

		private function error(string $texto): string{
			return json_encode(['tipo' => 'simple', 'titulo' => 'No se pudo guardar', 'texto' => $texto, 'icono' => 'error']);
		}

		public function registrarFeriado(): string{
			[$datos, $error] = $this->datosFormulario(0);
			if ($datos === null) { return $this->error($error); }

			$ok = $this->guardarDatos('asistencia_feriados', $datos);
			if ($ok->rowCount() < 1) { return $this->error('No se pudo registrar el feriado. Intente nuevamente.'); }
			return json_encode(['tipo' => 'recargar', 'titulo' => 'Feriado registrado',
				'texto' => 'El feriado se registró correctamente.', 'icono' => 'success']);
		}

		public function actualizarFeriado(): string{
			$id = (int)($_POST['feriado_id'] ?? 0);
			if ($id <= 0 || $this->buscarFeriado($id) === null) {
				return $this->error('No se encontró el feriado.');
			}
			[$datos, $error] = $this->datosFormulario($id);
			if ($datos === null) { return $this->error($error); }

			$this->actualizarDatos('asistencia_feriados', $datos,
				['condicion_campo' => 'feriado_id', 'condicion_marcador' => ':Id', 'condicion_valor' => $id]);
			return json_encode(['tipo' => 'redireccionar', 'url' => APP_URL . 'feriadosList/',
				'titulo' => 'Feriado actualizado', 'texto' => 'El feriado se actualizó correctamente.', 'icono' => 'success']);
		}

		public function eliminarFeriado(): string{
			$id = (int)($_POST['feriado_id'] ?? 0);
			if ($id <= 0 || $this->buscarFeriado($id) === null) {
				return $this->error('No se encontró el feriado.');
			}
			$ok = $this->ejecutarConsulta("DELETE FROM asistencia_feriados WHERE feriado_id = :id", [':id' => $id]);
			if ($ok->rowCount() < 1) { return $this->error('No se pudo eliminar el feriado.'); }
			return json_encode(['tipo' => 'recargar', 'titulo' => 'Feriado eliminado',
				'texto' => 'El feriado se eliminó del calendario.', 'icono' => 'success']);
		}

		/** Anios con feriados, del mas reciente al mas antiguo; siempre incluye el actual y el siguiente. */
		public function aniosConFeriados(): array{
			$anios = array_map('intval', $this->ejecutarConsulta(
				"SELECT DISTINCT YEAR(feriado_fecha) FROM asistencia_feriados")->fetchAll(\PDO::FETCH_COLUMN));
			$anios = array_unique(array_merge($anios, [(int)date('Y'), (int)date('Y') + 1]));
			rsort($anios);
			return $anios;
		}

		/** Filas del listado de un anio (0 = todos). */
		public function listarFeriados(int $anio): string{
			$h = static fn($v) => htmlspecialchars(html_entity_decode((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES, 'UTF-8');
			$params = [];
			$where = '';
			if ($anio > 0) {
				$where = 'WHERE feriado_fecha BETWEEN :desde AND :hasta';
				$params = [':desde' => $anio . '-01-01', ':hasta' => $anio . '-12-31'];
			}
			$datos = $this->ejecutarConsulta("SELECT * FROM asistencia_feriados $where ORDER BY feriado_fecha DESC", $params)
				->fetchAll(\PDO::FETCH_ASSOC);

			$tabla = '';
			foreach ($datos as $r) {
				$id = (int)$r['feriado_id'];
				$ts = strtotime((string)$r['feriado_fecha']);
				$activo = (int)$r['feriado_activo'] === 1;
				$desc = $h($r['feriado_descripcion']);
				$tabla .= '
					<tr>
						<td data-order="' . $h($r['feriado_fecha']) . '">' . date('d/m/Y', $ts) . '</td>
						<td>' . self::DIAS[(int)date('N', $ts)] . '</td>
						<td>' . $desc . '</td>
						<td><span class="badge ' . ($activo ? 'text-bg-success' : 'text-bg-secondary') . '">' . ($activo ? 'Activo' : 'Inactivo') . '</span></td>
						<td class="text-end"><div class="d-inline-flex gap-1">'
							. $this->siPuede('editar', 'feriadosList', '<a href="' . APP_URL . 'feriadosList/' . $id . '/" class="btn btn-primary btn-sm" aria-label="Editar el feriado ' . $desc . '"><i class="fas fa-pen me-1" aria-hidden="true"></i>Editar</a>')
							. $this->siPuede('eliminar', 'feriadosList', '<form class="FormularioAjax" action="' . APP_URL . 'app/ajax/feriadoAjax.php" method="POST" autocomplete="off">
								<input type="hidden" name="modulo_feriado" value="eliminar">
								<input type="hidden" name="feriado_id" value="' . $id . '">
								' . ds_boton_eliminar('el feriado ' . $r['feriado_descripcion']) . '
							</form>') . '
						</div></td>
					</tr>';
			}
			return $tabla;
		}
	}
