<?php
/**
 * Formulario de creación/edición de un botón fijo de contenido LSC.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LSC_Admin_Buttons_Form {

	const NONCE_ACTION  = 'lsc_boton_save';
	const NONCE_NAME    = 'lsc_boton_nonce';
	const MAX_FILE_SIZE = 3145728; // 3 MB en bytes.

	/** @var LSC_Buttons_Repository */
	private $repository;

	public function __construct( LSC_Buttons_Repository $repository ) {
		$this->repository = $repository;

		add_action( 'admin_post_lsc_boton_save', array( $this, 'handle_save' ) );
	}

	public function render() {
		$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$button = $id ? $this->repository->find( $id ) : null;

		$name      = $button ? $button['name'] : '';
		$active    = $button ? $button['active'] : true;
		$selector  = $button ? $button['selector'] : '';
		$url       = $button ? $button['url'] : '';
		$type      = $button ? $button['type'] : '';
		$att_id    = $button ? $button['attachment_id'] : '';
		$box_width = $button ? $button['box_width'] : LSC_Buttons_Repository::DEFAULT_BOX_WIDTH;
		$offset_x  = $button ? $button['offset_x'] : LSC_Buttons_Repository::DEFAULT_OFFSET_X;
		$offset_y  = $button ? $button['offset_y'] : LSC_Buttons_Repository::DEFAULT_OFFSET_Y;

		$back_url = admin_url( 'admin.php?page=lsc-accesibilidad-botones' );
		?>
		<div class="wrap lsc-accesibilidad-wrap">
			<h1><?php echo $button ? 'Editar botón fijo' : 'Añadir botón fijo nuevo'; ?></h1>
			<p><a href="<?php echo esc_url( $back_url ); ?>">&larr; Volver al listado</a></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lsc_boton_save">
				<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

				<table class="form-table">
					<tr>
						<th><label for="lsc-boton-name">Nombre del botón</label></th>
						<td>
							<input type="text" id="lsc-boton-name" name="name" class="regular-text" required
								value="<?php echo esc_attr( $name ); ?>"
								placeholder="Ej. Campus Virtual etR">
						</td>
					</tr>
					<tr>
						<th><label for="lsc-boton-active">Estado</label></th>
						<td>
							<label>
								<input type="checkbox" id="lsc-boton-active" name="active" value="1" <?php checked( $active ); ?>>
								Activo (visible en el sitio)
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="lsc-boton-selector">Selector CSS del botón</label></th>
						<td>
							<input type="text" id="lsc-boton-selector" name="selector" class="regular-text" required
								value="<?php echo esc_attr( $selector ); ?>"
								placeholder="Ej. .unnv-campus-btn">
							<p class="description">El selector CSS que identifica este botón en el HTML del sitio (clase o ID del elemento fijo del tema).</p>
						</td>
					</tr>
					<tr>
						<th>Contenido LSC</th>
						<td>
							<div class="lsc-preview" data-item-id="lsc-boton">
								<?php if ( $url && 'mp4' === $type ) : ?>
									<video src="<?php echo esc_url( $url ); ?>" muted loop playsinline width="100"></video>
								<?php elseif ( $url ) : ?>
									<img src="<?php echo esc_url( $url ); ?>" alt="" width="100">
								<?php else : ?>
									<span class="lsc-preview-empty">Sin contenido asignado</span>
								<?php endif; ?>
							</div>

							<input type="hidden" class="lsc-url-field" name="url" value="<?php echo esc_attr( $url ); ?>">
							<input type="hidden" class="lsc-type-field" name="type" value="<?php echo esc_attr( $type ); ?>">
							<input type="hidden" class="lsc-attachment-field" name="attachment_id" value="<?php echo esc_attr( $att_id ); ?>">

							<button type="button" class="button lsc-upload-button" data-item-id="lsc-boton">Subir GIF/Video</button>
							<button type="button" class="button lsc-remove-button" data-item-id="lsc-boton" <?php disabled( '', $url ); ?>>Quitar</button>
						</td>
					</tr>
					<tr>
						<th><label for="lsc-boton-box-width">Tamaño del recuadro (px)</label></th>
						<td><input type="number" id="lsc-boton-box-width" name="box_width" min="40" max="400" value="<?php echo esc_attr( $box_width ); ?>"></td>
					</tr>
					<tr>
						<th><label for="lsc-boton-offset-x">Posición horizontal (px)</label></th>
						<td>
							<input type="number" id="lsc-boton-offset-x" name="offset_x" value="<?php echo esc_attr( $offset_x ); ?>">
							<p class="description">Positivo mueve el recuadro hacia la derecha, negativo hacia la izquierda.</p>
						</td>
					</tr>
					<tr>
						<th><label for="lsc-boton-offset-y">Posición vertical (px)</label></th>
						<td>
							<input type="number" id="lsc-boton-offset-y" name="offset_y" value="<?php echo esc_attr( $offset_y ); ?>">
							<p class="description">Positivo mueve el recuadro hacia abajo, negativo hacia arriba.</p>
						</td>
					</tr>
				</table>

				<?php submit_button( $button ? 'Guardar cambios' : 'Crear botón' ); ?>
			</form>
		</div>
		<?php
	}

	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No tienes permisos para realizar esta acción.' );
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME );

		$id       = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$selector = isset( $_POST['selector'] ) ? sanitize_text_field( wp_unslash( $_POST['selector'] ) ) : '';

		if ( '' === $name || '' === $selector ) {
			wp_die( 'El nombre y el selector CSS son obligatorios.' );
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		$type = '';
		$attachment_id = 0;

		if ( '' !== $url ) {
			$type          = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
			$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

			$valid_type      = in_array( $type, array( 'gif', 'mp4' ), true );
			$within_limit    = ! $attachment_id || $this->attachment_within_size_limit( $attachment_id );

			if ( ! $valid_type || ! $within_limit ) {
				$url           = '';
				$type          = '';
				$attachment_id = 0;
			}
		}

		$data = array(
			'name'          => $name,
			'active'        => ! empty( $_POST['active'] ),
			'selector'      => $selector,
			'url'           => $url,
			'type'          => $type,
			'attachment_id' => $attachment_id,
			'box_width'     => isset( $_POST['box_width'] ) ? absint( $_POST['box_width'] ) : LSC_Buttons_Repository::DEFAULT_BOX_WIDTH,
			'offset_x'      => isset( $_POST['offset_x'] ) ? intval( $_POST['offset_x'] ) : LSC_Buttons_Repository::DEFAULT_OFFSET_X,
			'offset_y'      => isset( $_POST['offset_y'] ) ? intval( $_POST['offset_y'] ) : LSC_Buttons_Repository::DEFAULT_OFFSET_Y,
		);

		$this->repository->save( $id ?: null, $data );

		wp_safe_redirect( admin_url( 'admin.php?page=lsc-accesibilidad-botones&lsc_saved=1' ) );
		exit;
	}

	/**
	 * Verificación de servidor del límite de peso, como respaldo de la
	 * validación que ya hace el JS al momento de seleccionar el archivo.
	 */
	private function attachment_within_size_limit( $attachment_id ) {
		$file_path = get_attached_file( $attachment_id );

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return true;
		}

		return filesize( $file_path ) <= self::MAX_FILE_SIZE;
	}
}
