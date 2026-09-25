<?php
/**
 * Pantalla de listado de botones fijos de contenido LSC (elementos del
 * tema que no son ítems de menú de WordPress, ej. "Campus Virtual etR").
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LSC_Admin_Buttons_List {

	const NONCE_ACTION_TRASH = 'lsc_boton_trash';
	const NONCE_NAME_TRASH   = 'lsc_boton_trash_nonce';

	/** @var LSC_Buttons_Repository */
	private $repository;

	public function __construct( LSC_Buttons_Repository $repository ) {
		$this->repository = $repository;

		add_action( 'admin_post_lsc_boton_trash', array( $this, 'handle_trash' ) );
	}

	public function render() {
		$buttons  = $this->repository->get_all();
		$new_url  = admin_url( 'admin.php?page=lsc-accesibilidad-boton-form' );
		$back_url = admin_url( 'admin.php?page=lsc-accesibilidad' );
		?>
		<div class="wrap lsc-accesibilidad-wrap">
			<h1>
				Botones fijos LSC
				<a href="<?php echo esc_url( $new_url ); ?>" class="page-title-action">Añadir botón nuevo</a>
			</h1>
			<p><a href="<?php echo esc_url( $back_url ); ?>">&larr; Volver a Bloques LSC</a></p>
			<p>Un botón fijo es un elemento del tema que no es un ítem de menú de WordPress (ej. "Campus Virtual etR"), identificado por su propio selector CSS. Cada uno tiene su contenido LSC, tamaño y posición independientes.</p>

			<table class="widefat striped lsc-accesibilidad-table">
				<thead>
					<tr>
						<th>Nombre</th>
						<th>Selector CSS</th>
						<th>Contenido</th>
						<th>Estado</th>
						<th>Acciones</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $buttons ) ) : ?>
						<tr>
							<td colspan="5">Todavía no has creado ningún botón fijo. <a href="<?php echo esc_url( $new_url ); ?>">Añade el primero</a>.</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $buttons as $button ) : ?>
							<?php $this->render_row( $button ); ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private function render_row( $button ) {
		$edit_url  = admin_url( 'admin.php?page=lsc-accesibilidad-boton-form&id=' . $button['id'] );
		$trash_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=lsc_boton_trash&id=' . $button['id'] ),
			self::NONCE_ACTION_TRASH,
			self::NONCE_NAME_TRASH
		);
		?>
		<tr>
			<td><strong><?php echo esc_html( $button['name'] ); ?></strong></td>
			<td><code><?php echo esc_html( $button['selector'] ); ?></code></td>
			<td><?php echo $button['url'] ? 'Configurado' : 'Sin contenido'; ?></td>
			<td><?php echo $button['active'] ? 'Activo' : 'Inactivo'; ?></td>
			<td>
				<a href="<?php echo esc_url( $edit_url ); ?>" class="button">Editar</a>
				<a href="<?php echo esc_url( $trash_url ); ?>" class="button" onclick="return confirm('¿Enviar este botón a la papelera?');">Eliminar</a>
			</td>
		</tr>
		<?php
	}

	public function handle_trash() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No tienes permisos para realizar esta acción.' );
		}

		check_admin_referer( self::NONCE_ACTION_TRASH, self::NONCE_NAME_TRASH );

		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( $id ) {
			$this->repository->trash( $id );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=lsc-accesibilidad-botones' ) );
		exit;
	}
}
