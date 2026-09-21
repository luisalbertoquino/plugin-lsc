<?php
/**
 * Pantalla de listado de bloques de contenido LSC, más la fila fija
 * del botón "Campus Virtual etR" (caso especial, fuera del CRUD).
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
		$campus       = get_option( LSC_ACCESIBILIDAD_CAMPUS_OPTION_KEY, array() );
		$new_url      = admin_url( 'admin.php?page=lsc-accesibilidad-form' );
		$campus_edit  = admin_url( 'admin.php?page=lsc-accesibilidad-campus' );

		?>
		<div class="wrap lsc-accesibilidad-wrap">
			<h1>
				Accesibilidad LSC
				<a href="<?php echo esc_url( $new_url ); ?>" class="page-title-action">Añadir bloque nuevo</a>
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

					<?php $this->render_campus_row( $campus, $campus_edit ); ?>
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

	private function render_campus_row( $campus, $campus_edit ) {
		$has_content = ! empty( $campus['url'] );
		?>
		<tr class="lsc-campus-row">
			<td><strong>Botón: Campus Virtual etR</strong></td>
			<td colspan="3"><em>Elemento fijo del sitio (no es un ítem de menú de WordPress).</em></td>
			<td><?php echo $has_content ? 'Configurado' : 'Sin contenido'; ?></td>
			<td>
				<a href="<?php echo esc_url( $campus_edit ); ?>" class="button">Editar</a>
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
