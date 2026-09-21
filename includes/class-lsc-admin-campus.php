<?php
/**
 * Pantalla de configuración fija del botón "Campus Virtual etR",
 * un elemento del tema que no es un ítem de menú de WordPress y por
 * eso queda fuera del CRUD de bloques.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LSC_Admin_Campus {

	const NONCE_ACTION  = 'lsc_campus_save';
	const NONCE_NAME    = 'lsc_campus_nonce';
	const MAX_FILE_SIZE = 3145728; // 3 MB en bytes.

	public function __construct() {
		add_action( 'admin_post_lsc_campus_save', array( $this, 'handle_save' ) );
	}

	public function render() {
		$campus   = get_option( LSC_ACCESIBILIDAD_CAMPUS_OPTION_KEY, array() );
		$url      = isset( $campus['url'] ) ? $campus['url'] : '';
		$type     = isset( $campus['type'] ) ? $campus['type'] : '';
		$att_id   = isset( $campus['attachment_id'] ) ? $campus['attachment_id'] : '';
		$offset_x = isset( $campus['offset_x'] ) ? $campus['offset_x'] : 30;
		$offset_y = isset( $campus['offset_y'] ) ? $campus['offset_y'] : -15;
		$back_url = admin_url( 'admin.php?page=lsc-accesibilidad' );
		?>
		<div class="wrap lsc-accesibilidad-wrap">
			<h1>Botón: Campus Virtual etR</h1>
			<p><a href="<?php echo esc_url( $back_url ); ?>">&larr; Volver al listado</a></p>
			<p>Este botón es un elemento fijo de la topbar del sitio, no un ítem de menú de WordPress, por lo que se configura aparte de los bloques.</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lsc_campus_save">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

				<table class="form-table">
					<tr>
						<th>Contenido LSC</th>
						<td>
							<div class="lsc-preview" data-item-id="campus-virtual">
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

							<button type="button" class="button lsc-upload-button" data-item-id="campus-virtual">Subir GIF/Video</button>
							<button type="button" class="button lsc-remove-button" data-item-id="campus-virtual" <?php disabled( '', $url ); ?>>Quitar</button>
						</td>
					</tr>
					<tr>
						<th><label for="lsc-campus-offset-x">Posición horizontal (px)</label></th>
						<td><input type="number" id="lsc-campus-offset-x" name="offset_x" value="<?php echo esc_attr( $offset_x ); ?>"></td>
					</tr>
					<tr>
						<th><label for="lsc-campus-offset-y">Posición vertical (px)</label></th>
						<td><input type="number" id="lsc-campus-offset-y" name="offset_y" value="<?php echo esc_attr( $offset_y ); ?>"></td>
					</tr>
				</table>

				<?php submit_button( 'Guardar cambios' ); ?>
			</form>
		</div>
		<?php
	}

	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No tienes permisos para realizar esta acción.' );
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME );

		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		$data = array();

		if ( '' !== $url ) {
			$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
			$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

			if ( in_array( $type, array( 'gif', 'mp4' ), true ) && ( ! $attachment_id || $this->attachment_within_size_limit( $attachment_id ) ) ) {
				$data = array(
					'url'           => $url,
					'type'          => $type,
					'attachment_id' => $attachment_id,
				);
			}
		}

		$data['offset_x'] = isset( $_POST['offset_x'] ) ? intval( $_POST['offset_x'] ) : 30;
		$data['offset_y'] = isset( $_POST['offset_y'] ) ? intval( $_POST['offset_y'] ) : -15;

		update_option( LSC_ACCESIBILIDAD_CAMPUS_OPTION_KEY, $data );

		wp_safe_redirect( admin_url( 'admin.php?page=lsc-accesibilidad&lsc_saved=1' ) );
		exit;
	}

	private function attachment_within_size_limit( $attachment_id ) {
		$file_path = get_attached_file( $attachment_id );

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return true;
		}

		return filesize( $file_path ) <= self::MAX_FILE_SIZE;
	}
}
