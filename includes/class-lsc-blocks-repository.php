<?php
/**
 * Acceso a datos de los bloques de contenido LSC (CPT lsc_bloque).
 * Centraliza lectura/escritura de post + meta para no dispersar
 * llamadas a la API de posts por toda la clase de administración.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LSC_Blocks_Repository {

	const META_MENU_ID           = '_lsc_menu_id';
	const META_SELECTOR_DESKTOP  = '_lsc_selector_desktop';
	const META_SELECTOR_MOBILE   = '_lsc_selector_mobile';
	const META_ITEMS             = '_lsc_items';
	const META_BOX_WIDTH         = '_lsc_box_width';
	const META_OFFSET_X          = '_lsc_offset_x';
	const META_OFFSET_Y          = '_lsc_offset_y';
	const META_SCOPE             = '_lsc_scope';
	const META_SCOPE_PAGES       = '_lsc_scope_pages';
	const META_MIGRATED_FROM     = '_lsc_migrated_from';

	const DEFAULT_SELECTOR_DESKTOP = '#main-menu';
	const DEFAULT_SELECTOR_MOBILE  = '#main-menu-offcanvas';
	const DEFAULT_BOX_WIDTH        = 100;

	/**
	 * Devuelve todos los bloques (cualquier estado, excepto papelera),
	 * como arrays asociativos ya normalizados.
	 *
	 * @return array
	 */
	public function get_all() {
		$posts = get_posts( array(
			'post_type'      => LSC_Block_CPT::POST_TYPE,
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		return array_map( array( $this, 'to_array' ), $posts );
	}

	/**
	 * Devuelve solo los bloques publicados (activos), usados por el frontend.
	 *
	 * @return array
	 */
	public function get_published() {
		$posts = get_posts( array(
			'post_type'      => LSC_Block_CPT::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		) );

		return array_map( array( $this, 'to_array' ), $posts );
	}

	/**
	 * Busca un bloque por ID.
	 *
	 * @param int $id
	 * @return array|null
	 */
	public function find( $id ) {
		$post = get_post( $id );

		if ( ! $post || LSC_Block_CPT::POST_TYPE !== $post->post_type ) {
			return null;
		}

		return $this->to_array( $post );
	}

	/**
	 * Crea o actualiza un bloque.
	 *
	 * @param int|null $id   ID a actualizar, o null para crear uno nuevo.
	 * @param array    $data Datos ya sanitizados.
	 * @return int ID del bloque guardado.
	 */
	public function save( $id, array $data ) {
		$post_args = array(
			'post_type'   => LSC_Block_CPT::POST_TYPE,
			'post_title'  => $data['name'],
			'post_status' => ! empty( $data['active'] ) ? 'publish' : 'draft',
		);

		if ( $id ) {
			$post_args['ID'] = $id;
			wp_update_post( $post_args );
		} else {
			$id = wp_insert_post( $post_args );
		}

		if ( is_wp_error( $id ) || ! $id ) {
			return 0;
		}

		update_post_meta( $id, self::META_MENU_ID, absint( $data['menu_id'] ) );
		update_post_meta( $id, self::META_SELECTOR_DESKTOP, sanitize_text_field( $data['selector_desktop'] ) );
		update_post_meta( $id, self::META_SELECTOR_MOBILE, sanitize_text_field( $data['selector_mobile'] ) );
		update_post_meta( $id, self::META_ITEMS, $data['items'] );
		update_post_meta( $id, self::META_BOX_WIDTH, absint( $data['box_width'] ) );
		update_post_meta( $id, self::META_OFFSET_X, intval( $data['offset_x'] ) );
		update_post_meta( $id, self::META_OFFSET_Y, intval( $data['offset_y'] ) );
		update_post_meta( $id, self::META_SCOPE, $data['scope'] );
		update_post_meta( $id, self::META_SCOPE_PAGES, $data['scope_pages'] );

		if ( ! empty( $data['migrated_from'] ) ) {
			update_post_meta( $id, self::META_MIGRATED_FROM, sanitize_text_field( $data['migrated_from'] ) );
		}

		return (int) $id;
	}

	/**
	 * Mueve un bloque a la papelera.
	 *
	 * @param int $id
	 * @return bool
	 */
	public function trash( $id ) {
		$post = get_post( $id );

		if ( ! $post || LSC_Block_CPT::POST_TYPE !== $post->post_type ) {
			return false;
		}

		return (bool) wp_trash_post( $id );
	}

	/**
	 * Normaliza un WP_Post + su meta a un array asociativo con los
	 * valores ya tipados y con defaults aplicados.
	 *
	 * @param WP_Post $post
	 * @return array
	 */
	private function to_array( $post ) {
		$items = get_post_meta( $post->ID, self::META_ITEMS, true );

		return array(
			'id'               => $post->ID,
			'name'             => $post->post_title,
			'active'           => 'publish' === $post->post_status,
			'menu_id'          => absint( get_post_meta( $post->ID, self::META_MENU_ID, true ) ),
			'selector_desktop' => $this->meta_or_default( $post->ID, self::META_SELECTOR_DESKTOP, self::DEFAULT_SELECTOR_DESKTOP ),
			'selector_mobile'  => $this->meta_or_default( $post->ID, self::META_SELECTOR_MOBILE, self::DEFAULT_SELECTOR_MOBILE ),
			'items'            => is_array( $items ) ? $items : array(),
			'box_width'        => $this->meta_or_default( $post->ID, self::META_BOX_WIDTH, self::DEFAULT_BOX_WIDTH ),
			'offset_x'         => (int) get_post_meta( $post->ID, self::META_OFFSET_X, true ),
			'offset_y'         => (int) get_post_meta( $post->ID, self::META_OFFSET_Y, true ),
			'scope'            => $this->meta_or_default( $post->ID, self::META_SCOPE, 'all' ),
			'scope_pages'      => (array) get_post_meta( $post->ID, self::META_SCOPE_PAGES, true ),
			'migrated_from'    => get_post_meta( $post->ID, self::META_MIGRATED_FROM, true ),
		);
	}

	private function meta_or_default( $post_id, $key, $default ) {
		$value = get_post_meta( $post_id, $key, true );
		return ( '' === $value || false === $value ) ? $default : $value;
	}
}
