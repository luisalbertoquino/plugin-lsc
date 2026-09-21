<?php
/**
 * Formulario de creación/edición de un bloque de contenido LSC.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LSC_Admin_Form {

	const NONCE_ACTION = 'lsc_bloque_save';
	const NONCE_NAME   = 'lsc_bloque_nonce';
	const MAX_FILE_SIZE = 3145728; // 3 MB en bytes.

	/** @var LSC_Blocks_Repository */
	private $repository;

	public function __construct( LSC_Blocks_Repository $repository ) {
		$this->repository = $repository;

		add_action( 'admin_post_lsc_bloque_save', array( $this, 'handle_save' ) );
	}

	public function render() {
		$id    = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$block = $id ? $this->repository->find( $id ) : null;

		$name             = $block ? $block['name'] : '';
		$active           = $block ? $block['active'] : true;
		$menu_id          = $block ? $block['menu_id'] : 0;
		$selector_desktop = $block ? $block['selector_desktop'] : LSC_Blocks_Repository::DEFAULT_SELECTOR_DESKTOP;
		$selector_mobile  = $block ? $block['selector_mobile'] : LSC_Blocks_Repository::DEFAULT_SELECTOR_MOBILE;
		$items            = $block ? $block['items'] : array();
		$box_width        = $block ? $block['box_width'] : LSC_Blocks_Repository::DEFAULT_BOX_WIDTH;
		$offset_x         = $block ? $block['offset_x'] : 0;
		$offset_y         = $block ? $block['offset_y'] : 0;
		$scope            = $block ? $block['scope'] : 'all';
		$scope_pages      = $block ? $block['scope_pages'] : array();

		$menus       = wp_get_nav_menus();
		$menu_items  = $menu_id ? LSC_Menu_Items::get_top_level_items( $menu_id ) : array();
		$pages       = get_pages();

		$back_url = admin_url( 'admin.php?page=lsc-accesibilidad' );
		?>
		<div class="wrap lsc-accesibilidad-wrap">
			<h1><?php echo $block ? 'Editar bloque' : 'Añadir bloque nuevo'; ?></h1>
			<p><a href="<?php echo esc_url( $back_url ); ?>">&larr; Volver al listado</a></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lsc_bloque_save">
				<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

				<table class="form-table">
					<tr>
						<th><label for="lsc-name">Nombre del bloque</label></th>
						<td>
							<input type="text" id="lsc-name" name="name" class="regular-text" required
								value="<?php echo esc_attr( $name ); ?>"
								placeholder="Ej. Menú de apoyo visual principal">
						</td>
					</tr>
					<tr>
						<th><label for="lsc-active">Estado</label></th>
						<td>
							<label>
								<input type="checkbox" id="lsc-active" name="active" value="1" <?php checked( $active ); ?>>
								Activo (visible en el sitio)
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="lsc-menu">Menú de WordPress de origen</label></th>
						<td>
							<select id="lsc-menu" name="menu_id" required onchange="this.form.querySelector('[name=lsc_reload]').value='1'; this.form.submit();">
								<option value="">-- Selecciona un menú --</option>
								<?php foreach ( $menus as $menu ) : ?>
									<option value="<?php echo esc_attr( $menu->term_id ); ?>" <?php selected( $menu_id, $menu->term_id ); ?>>
										<?php echo esc_html( $menu->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<input type="hidden" name="lsc_reload" value="0">
							<p class="description">Al cambiar el menú se recarga la página para mostrar sus ítems principales.</p>
						</td>
					</tr>
					<tr>
						<th><label for="lsc-selector-desktop">Selector CSS — contenedor de escritorio</label></th>
						<td>
							<input type="text" id="lsc-selector-desktop" name="selector_desktop" class="regular-text"
								value="<?php echo esc_attr( $selector_desktop ); ?>">
							<p class="description">Dónde vive este menú en el HTML del sitio (escritorio). Por defecto <code><?php echo esc_html( LSC_Blocks_Repository::DEFAULT_SELECTOR_DESKTOP ); ?></code>.</p>
						</td>
					</tr>
					<tr>
						<th><label for="lsc-selector-mobile">Selector CSS — contenedor móvil</label></th>
						<td>
							<input type="text" id="lsc-selector-mobile" name="selector_mobile" class="regular-text"
								value="<?php echo esc_attr( $selector_mobile ); ?>">
							<p class="description">Dónde vive este menú en el HTML del sitio (móvil/offcanvas). Por defecto <code><?php echo esc_html( LSC_Blocks_Repository::DEFAULT_SELECTOR_MOBILE ); ?></code>.</p>
						</td>
					</tr>
					<tr>
						<th><label for="lsc-box-width">Tamaño del recuadro (px)</label></th>
						<td>
							<input type="number" id="lsc-box-width" name="box_width" min="40" max="400"
								value="<?php echo esc_attr( $box_width ); ?>">
						</td>
					</tr>
					<tr>
						<th><label for="lsc-offset-x">Posición horizontal (px)</label></th>
						<td>
							<input type="number" id="lsc-offset-x" name="offset_x" value="<?php echo esc_attr( $offset_x ); ?>">
							<p class="description">Positivo mueve el recuadro hacia la derecha, negativo hacia la izquierda.</p>
						</td>
					</tr>
					<tr>
						<th><label for="lsc-offset-y">Posición vertical (px)</label></th>
						<td>
							<input type="number" id="lsc-offset-y" name="offset_y" value="<?php echo esc_attr( $offset_y ); ?>">
							<p class="description">Positivo mueve el recuadro hacia abajo, negativo hacia arriba.</p>
						</td>
					</tr>
					<tr>
						<th>Alcance</th>
						<td>
							<label>
								<input type="radio" name="scope" value="all" <?php checked( $scope, 'all' ); ?> onchange="document.getElementById('lsc-scope-pages').style.display='none';">
								Todo el sitio
							</label>
							<br>
							<label>
								<input type="radio" name="scope" value="pages" <?php checked( $scope, 'pages' ); ?> onchange="document.getElementById('lsc-scope-pages').style.display='block';">
								Páginas específicas
							</label>
							<div id="lsc-scope-pages" style="<?php echo 'pages' === $scope ? '' : 'display:none;'; ?> margin-top:8px; max-height:200px; overflow:auto; border:1px solid #ccd0d4; padding:8px; max-width:400px;">
								<?php foreach ( $pages as $page ) : ?>
									<label style="display:block;">
										<input type="checkbox" name="scope_pages[]" value="<?php echo esc_attr( $page->ID ); ?>" <?php checked( in_array( $page->ID, $scope_pages, true ) ); ?>>
										<?php echo esc_html( $page->post_title ); ?>
									</label>
								<?php endforeach; ?>
							</div>
						</td>
					</tr>
				</table>

				<?php if ( $menu_id ) : ?>
					<h2>Ítems principales de "<?php echo esc_html( $this->menu_name( $menu_id ) ); ?>"</h2>
					<?php if ( empty( $menu_items ) ) : ?>
						<p>Este menú no tiene ítems de nivel principal.</p>
					<?php else : ?>
						<table class="widefat lsc-accesibilidad-table">
							<thead>
								<tr>
									<th>Ítem del menú</th>
									<th>Contenido LSC</th>
									<th>Acciones</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $menu_items as $menu_item ) : ?>
									<?php $this->render_item_row( $menu_item, $items ); ?>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				<?php endif; ?>

				<?php submit_button( $block ? 'Guardar cambios' : 'Crear bloque' ); ?>
			</form>
		</div>
		<?php
	}

	private function menu_name( $menu_id ) {
		$menu = get_term( $menu_id, 'nav_menu' );
		return ( $menu && ! is_wp_error( $menu ) ) ? $menu->name : '';
	}

	private function render_item_row( $menu_item, $saved_items ) {
		$item_id = $menu_item['id'];
		$current = isset( $saved_items[ $item_id ] ) ? $saved_items[ $item_id ] : array();
		$url     = isset( $current['url'] ) ? $current['url'] : '';
		$type    = isset( $current['type'] ) ? $current['type'] : '';
		$att_id  = isset( $current['attachment_id'] ) ? $current['attachment_id'] : '';
		?>
		<tr>
			<td><strong><?php echo esc_html( $menu_item['title'] ); ?></strong></td>
			<td class="lsc-preview-cell">
				<div class="lsc-preview" data-item-id="<?php echo esc_attr( $item_id ); ?>">
					<?php if ( $url && 'mp4' === $type ) : ?>
						<video src="<?php echo esc_url( $url ); ?>" muted loop playsinline width="100"></video>
					<?php elseif ( $url ) : ?>
						<img src="<?php echo esc_url( $url ); ?>" alt="" width="100">
					<?php else : ?>
						<span class="lsc-preview-empty">Sin contenido asignado</span>
					<?php endif; ?>
				</div>
			</td>
			<td>
				<input type="hidden"
					class="lsc-url-field"
					name="items[<?php echo esc_attr( $item_id ); ?>][url]"
					value="<?php echo esc_attr( $url ); ?>">
				<input type="hidden"
					class="lsc-type-field"
					name="items[<?php echo esc_attr( $item_id ); ?>][type]"
					value="<?php echo esc_attr( $type ); ?>">
				<input type="hidden"
					class="lsc-attachment-field"
					name="items[<?php echo esc_attr( $item_id ); ?>][attachment_id]"
					value="<?php echo esc_attr( $att_id ); ?>">

				<button type="button" class="button lsc-upload-button" data-item-id="<?php echo esc_attr( $item_id ); ?>">
					Subir GIF/Video
				</button>
				<button type="button" class="button lsc-remove-button" data-item-id="<?php echo esc_attr( $item_id ); ?>" <?php disabled( '', $url ); ?>>
					Quitar
				</button>
			</td>
		</tr>
		<?php
	}

	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No tienes permisos para realizar esta acción.' );
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME );

		$id      = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$menu_id = isset( $_POST['menu_id'] ) ? absint( $_POST['menu_id'] ) : 0;

		// Si solo se cambió el menú (para recargar sus ítems), no guardamos todavía.
		if ( ! empty( $_POST['lsc_reload'] ) ) {
			$redirect = add_query_arg(
				array_filter( array(
					'page'    => 'lsc-accesibilidad-form',
					'id'      => $id ?: null,
					'menu_id' => $menu_id,
				) ),
				admin_url( 'admin.php' )
			);
			wp_safe_redirect( $redirect );
			exit;
		}

		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';

		if ( '' === $name || ! $menu_id ) {
			wp_die( 'El nombre y el menú de origen son obligatorios.' );
		}

		$valid_ids = LSC_Menu_Items::get_valid_ids( $menu_id );
		$posted    = isset( $_POST['items'] ) ? (array) $_POST['items'] : array();
		$items     = array();

		foreach ( $posted as $item_id => $data ) {
			$item_id = absint( $item_id );

			if ( ! in_array( $item_id, $valid_ids, true ) ) {
				continue;
			}

			$url = isset( $data['url'] ) ? esc_url_raw( wp_unslash( $data['url'] ) ) : '';

			if ( '' === $url ) {
				continue;
			}

			$type = isset( $data['type'] ) ? sanitize_key( wp_unslash( $data['type'] ) ) : '';
			if ( ! in_array( $type, array( 'gif', 'mp4' ), true ) ) {
				continue;
			}

			$attachment_id = isset( $data['attachment_id'] ) ? absint( $data['attachment_id'] ) : 0;

			if ( $attachment_id && ! $this->attachment_within_size_limit( $attachment_id ) ) {
				continue;
			}

			$items[ $item_id ] = array(
				'url'           => $url,
				'type'          => $type,
				'attachment_id' => $attachment_id,
			);
		}

		$scope       = isset( $_POST['scope'] ) && 'pages' === $_POST['scope'] ? 'pages' : 'all';
		$scope_pages = isset( $_POST['scope_pages'] ) ? array_map( 'absint', (array) $_POST['scope_pages'] ) : array();

		$data = array(
			'name'             => $name,
			'active'           => ! empty( $_POST['active'] ),
			'menu_id'          => $menu_id,
			'selector_desktop' => isset( $_POST['selector_desktop'] ) ? wp_unslash( $_POST['selector_desktop'] ) : LSC_Blocks_Repository::DEFAULT_SELECTOR_DESKTOP,
			'selector_mobile'  => isset( $_POST['selector_mobile'] ) ? wp_unslash( $_POST['selector_mobile'] ) : LSC_Blocks_Repository::DEFAULT_SELECTOR_MOBILE,
			'items'            => $items,
			'box_width'        => isset( $_POST['box_width'] ) ? absint( $_POST['box_width'] ) : LSC_Blocks_Repository::DEFAULT_BOX_WIDTH,
			'offset_x'         => isset( $_POST['offset_x'] ) ? intval( $_POST['offset_x'] ) : 0,
			'offset_y'         => isset( $_POST['offset_y'] ) ? intval( $_POST['offset_y'] ) : 0,
			'scope'            => $scope,
			'scope_pages'      => 'pages' === $scope ? $scope_pages : array(),
		);

		$this->repository->save( $id ?: null, $data );

		wp_safe_redirect( admin_url( 'admin.php?page=lsc-accesibilidad&lsc_saved=1' ) );
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
