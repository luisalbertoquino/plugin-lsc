<?php
/**
 * Pantalla de listado de bloques de contenido LSC (por menú de
 * WordPress). Los botones fijos del tema (ej. "Campus Virtual etR")
 * tienen su propio CRUD, ver LSC_Admin_Buttons_List.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LSC_Admin_List {

	const NONCE_ACTION_TRASH = 'lsc_bloque_trash';
	const NONCE_NAME_TRASH   = 'lsc_bloque_trash_nonce';

	/** @var LSC_Blocks_Repository */
	private $repository;

	public function __construct( LSC_Blocks_Repository $repository ) {
		$this->repository = $repository;

		add_action( 'admin_post_lsc_bloque_trash', array( $this, 'handle_trash' ) );
	}

	public function render() {
		$blocks       = $this->repository->get_all();
		$new_url      = admin_url( 'admin.php?page=lsc-accesibilidad-form' );
		$buttons_url  = admin_url( 'admin.php?page=lsc-accesibilidad-botones' );

		?>
		<div class="wrap lsc-accesibilidad-wrap">
			<h1>
				Accesibilidad LSC
				<a href="<?php echo esc_url( $new_url ); ?>" class="page-title-action">Añadir bloque nuevo</a>
				<a href="<?php echo esc_url( $buttons_url ); ?>" class="page-title-action">Botones fijos (Campus Virtual y otros)</a>
			</h1>
			<p>Cada bloque asigna contenido LSC (GIF o video) a los ítems principales de un menú de WordPress, con su propio tamaño, posición y páginas donde debe mostrarse.</p>

			<table class="widefat striped lsc-accesibilidad-table">
				<thead>
					<tr>
						<th>Nombre</th>
						<th>Menú de origen</th>
						<th>Alcance</th>
						<th>Ítems configurados</th>
						<th>Estado</th>
						<th>Acciones</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $blocks ) ) : ?>
						<tr>
							<td colspan="6">Todavía no has creado ningún bloque. <a href="<?php echo esc_url( $new_url ); ?>">Añade el primero</a>.</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $blocks as $block ) : ?>
							<?php $this->render_block_row( $block ); ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private function render_block_row( $block ) {
		$menu   = get_term( $block['menu_id'], 'nav_menu' );
		$menu_name = ( $menu && ! is_wp_error( $menu ) ) ? $menu->name : '(menú no encontrado)';

		$configured = 0;
		foreach ( $block['items'] as $item ) {
			if ( ! empty( $item['url'] ) ) {
				$configured++;
			}
		}
		$total = count( LSC_Menu_Items::get_top_level_items( $block['menu_id'] ) );

		if ( 'all' === $block['scope'] ) {
			$scope_label = 'Todo el sitio';
		} else {
			$scope_label = count( $block['scope_pages'] ) . ' página(s) específica(s)';
		}

		$edit_url  = admin_url( 'admin.php?page=lsc-accesibilidad-form&id=' . $block['id'] );
		$trash_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=lsc_bloque_trash&id=' . $block['id'] ),
			self::NONCE_ACTION_TRASH,
			self::NONCE_NAME_TRASH
		);
		?>
		<tr>
			<td><strong><?php echo esc_html( $block['name'] ); ?></strong></td>
			<td><?php echo esc_html( $menu_name ); ?></td>
			<td><?php echo esc_html( $scope_label ); ?></td>
			<td><?php echo esc_html( $configured . ' / ' . $total ); ?></td>
			<td><?php echo $block['active'] ? 'Activo' : 'Inactivo'; ?></td>
			<td>
				<a href="<?php echo esc_url( $edit_url ); ?>" class="button">Editar</a>
				<a href="<?php echo esc_url( $trash_url ); ?>" class="button" onclick="return confirm('¿Enviar este bloque a la papelera?');">Eliminar</a>
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

		wp_safe_redirect( admin_url( 'admin.php?page=lsc-accesibilidad' ) );
		exit;
	}
}
