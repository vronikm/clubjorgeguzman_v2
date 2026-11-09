<?php

	namespace app\controllers;
	use app\models\mainModel;

	/*==================================================================
	  INSTITUCIONES EDUCATIVAS (institucionList) — traido de CDJG v1
	  ------------------------------------------------------------------
	  La «Unidad Educativa» en la que estudia cada alumno
	  (sujeto_alumno.alumno_unidadid -> general_institucion). Tabla propia
	  de CDJG: DigiSports no la tenia. En la v1 vivia en escuelaController.

	  Diferencias con la v1:
	  - Consultas con parametros (la v1 interpolaba el id en el SQL).
	  - Se puede marcar una institucion como inactiva: deja de ofrecerse en
	    la ficha del alumno, pero los alumnos que ya la tienen la conservan.
	    No se elimina: borrarla dejaria a esos alumnos apuntando a nada.
	==================================================================*/
	class institucionController extends mainModel{

		/** Una institucion por id, o null. */
		public function buscarInstitucion(int $id): ?array{
			$fila = $this->ejecutarConsulta(
				"SELECT * FROM general_institucion WHERE institucion_id = :id", [':id' => $id]
			)->fetch(\PDO::FETCH_ASSOC);
			return $fila ?: null;
		}

		/** Datos del formulario, validados. Devuelve [datos, error]. */
		private function datosFormulario(): array{
			$nombre    = $this->limpiarCadena($_POST['institucion_nombre'] ?? '');
			$direccion = $this->limpiarCadena($_POST['institucion_direccion'] ?? '');
			$email     = trim((string)($_POST['institucion_email'] ?? ''));
			$telefono  = preg_replace('/\D+/', '', (string)($_POST['institucion_telefono'] ?? ''));
			$estado    = ($_POST['institucion_estado'] ?? 'A') === 'I' ? 'I' : 'A';

			if ($nombre === '') {
				return [null, 'El nombre de la institución es obligatorio.'];
			}
			if (mb_strlen($nombre) > 100 || mb_strlen($direccion) > 200) {
				return [null, 'El nombre admite hasta 100 caracteres y la dirección hasta 200.'];
			}
			if ($email !== '' && (strlen($email) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
				return [null, 'El correo no es válido (hasta 50 caracteres).'];
			}
			if (strlen($telefono) > 9) {
				return [null, 'El teléfono admite hasta 9 dígitos.'];
			}
			return [[
				['campo_nombre' => 'institucion_nombre',    'campo_marcador' => ':Nombre',    'campo_valor' => $nombre],
				['campo_nombre' => 'institucion_direccion', 'campo_marcador' => ':Direccion', 'campo_valor' => $direccion],
				['campo_nombre' => 'institucion_email',     'campo_marcador' => ':Email',     'campo_valor' => $this->limpiarCadena($email)],
				['campo_nombre' => 'institucion_telefono',  'campo_marcador' => ':Telefono',  'campo_valor' => $telefono],
				['campo_nombre' => 'institucion_estado',    'campo_marcador' => ':Estado',    'campo_valor' => $estado],
			], ''];
		}

		private function error(string $texto): string{
			return json_encode(['tipo' => 'simple', 'titulo' => 'No se pudo guardar', 'texto' => $texto, 'icono' => 'error']);
		}

		public function registrarInstitucion(): string{
			[$datos, $error] = $this->datosFormulario();
			if ($datos === null) { return $this->error($error); }

			$ok = $this->guardarDatos('general_institucion', $datos);
			if ($ok->rowCount() < 1) { return $this->error('No se pudo registrar la institución. Intente nuevamente.'); }
			return json_encode(['tipo' => 'recargar', 'titulo' => 'Institución registrada',
				'texto' => 'La institución se registró correctamente.', 'icono' => 'success']);
		}

		public function actualizarInstitucion(): string{
			$id = (int)($_POST['institucion_id'] ?? 0);
			if ($id <= 0 || $this->buscarInstitucion($id) === null) {
				return $this->error('No se encontró la institución.');
			}
			[$datos, $error] = $this->datosFormulario();
			if ($datos === null) { return $this->error($error); }

			$this->actualizarDatos('general_institucion', $datos,
				['condicion_campo' => 'institucion_id', 'condicion_marcador' => ':Id', 'condicion_valor' => $id]);
			return json_encode(['tipo' => 'redireccionar', 'url' => APP_URL . 'institucionList/',
				'titulo' => 'Institución actualizada', 'texto' => 'Los datos se actualizaron correctamente.', 'icono' => 'success']);
		}

		/** Filas del listado, con cuantos alumnos tiene cada una. */
		public function listarInstituciones(): string{
			$h = static fn($v) => htmlspecialchars(html_entity_decode((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES, 'UTF-8');
			$datos = $this->ejecutarConsulta(
				"SELECT I.*, (SELECT COUNT(*) FROM sujeto_alumno A
							   WHERE A.alumno_unidadid = I.institucion_id AND A.alumno_estado <> 'E') AS alumnos
				   FROM general_institucion I
				  ORDER BY I.institucion_estado, I.institucion_nombre")->fetchAll(\PDO::FETCH_ASSOC);

			$tabla = '';
			foreach ($datos as $r) {
				$id = (int)$r['institucion_id'];
				$activo = $r['institucion_estado'] === 'A';
				$tabla .= '
					<tr>
						<td>' . $h($r['institucion_nombre']) . '</td>
						<td>' . $h($r['institucion_direccion']) . '</td>
						<td>' . $h($r['institucion_email']) . '</td>
						<td>' . $h($r['institucion_telefono']) . '</td>
						<td class="text-center" data-order="' . (int)$r['alumnos'] . '"><span class="badge rounded-pill ' . ((int)$r['alumnos'] > 0 ? 'text-bg-primary' : 'text-bg-light border') . '">' . (int)$r['alumnos'] . '</span></td>
						<td><span class="badge ' . ($activo ? 'text-bg-success' : 'text-bg-secondary') . '">' . ($activo ? 'Activa' : 'Inactiva') . '</span></td>
						<td class="text-end">'
							. $this->siPuede('editar', 'institucionList', '<a href="' . APP_URL . 'institucionList/' . $id . '/" class="btn btn-primary btn-sm" aria-label="Editar ' . $h($r['institucion_nombre']) . '"><i class="fas fa-pen me-1" aria-hidden="true"></i>Editar</a>') . '
						</td>
					</tr>';
			}
			return $tabla;
		}

		/**
		 * Instituciones activas como <option>, para la ficha del alumno.
		 * La que ya tiene el alumno se incluye aunque este inactiva, para no
		 * perderla al guardar la ficha.
		 */
		public function opcionesInstitucion($seleccionada): string{
			$sel = (int)$seleccionada;
			$h = static fn($v) => htmlspecialchars(html_entity_decode((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES, 'UTF-8');
			$datos = $this->ejecutarConsulta(
				"SELECT institucion_id, institucion_nombre FROM general_institucion
				  WHERE institucion_estado = 'A' OR institucion_id = :sel
				  ORDER BY institucion_nombre", [':sel' => $sel])->fetchAll(\PDO::FETCH_ASSOC);
			$option = '<option value="">Sin especificar</option>';
			foreach ($datos as $r) {
				$id = (int)$r['institucion_id'];
				$option .= '<option value="' . $id . '"' . ($id === $sel ? ' selected' : '') . '>' . $h($r['institucion_nombre']) . '</option>';
			}
			return $option;
		}

		/** Nombre de la institucion de un alumno, para mostrarla en su perfil. */
		public function nombreInstitucion($id): string{
			$id = (int)$id;
			if ($id <= 0) { return ''; }
			$nombre = $this->ejecutarConsulta(
				"SELECT institucion_nombre FROM general_institucion WHERE institucion_id = :id", [':id' => $id]
			)->fetchColumn();
			return $nombre === false ? '' : html_entity_decode((string)$nombre, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}
	}
