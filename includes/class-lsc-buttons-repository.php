<?php
/**
 * Acceso a datos de los botones fijos de contenido LSC (CPT lsc_boton).
 * Un botón fijo es un elemento del tema que no es un ítem de menú de
 * WordPress (ej. "Campus Virtual etR", un botón de "Agendar cita", etc.),
 * identificado por su propio selector CSS.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LSC_Buttons_Repository {

	const META_SELECTOR      = '_lsc_selector';
	const META_URL           = '_lsc_url';
	const META_TYPE          = '_lsc_type';
	const META_ATTACHMENT_ID = '_lsc_attachment_id';
	const META_BOX_WIDTH     = '_lsc_box_width';
	const META_OFFSET_X      = '_lsc_offset_x';
	const META_OFFSET_Y      = '_lsc_offset_y';

	const DEFAULT_BOX_WIDTH = 100;
	const DEFAULT_OFFSET_X  = 30;
	const DEFAULT_OFFSET_Y  = -15;

	/**
	 * Devuelve todos los botones (cualquier estado, excepto papelera),
	 * como arrays asociativos ya normalizados.
	 *
	 * @return array
	 */
	public function get_all() {
		$posts = get_posts( array(
			'post_type'      => LSC_Button_CPT::POST_TYPE,
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		return array_map( array( $this, 'to_array' ), $posts );
	}

	/**
	 * Devuelve solo los botones publicados (activos) con contenido
	 * asignado, usados por el frontend.
	 *
	 * @return array
	 */
	public function get_published() {
		$posts = get_posts( array(
			'post_type'      => LSC_Button_CPT::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		) );

		$buttons = array_map( array( $this, 'to_array' ), $posts );

		return array_values( array_filter( $buttons, function ( $button ) {
			return '' !== $button['url'] && '' !== $button['selector'];
		} ) );
	}

	/**
	 * Busca un botón por ID.
	 *
	 * @param int $id
	 * @return array|null
	 */
	public function find( $id ) {
		$post = get_post( $id );

		if ( ! $post || LSC_Button_CPT::POST_TYPE !== $post->post_type ) {
			return null;
		}

		return $this->to_array( $post );
	}

	/**
	 * Crea o actualiza un botón fijo.
	 *
	 * @param int|null $id   ID a actualizar, o null para crear uno nuevo.
	 * @param array    $data Datos ya sanitizados.
	 * @return int ID del botón guardado.
	 */
	public function save( $id, array $data ) {
		$post_args = array(
			'post_type'   => LSC_Button_CPT::POST_TYPE,
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

		update_post_meta( $id, self::META_SELECTOR, sanitize_text_field( $data['selector'] ) );
		update_post_meta( $id, self::META_URL, esc_url_raw( $data['url'] ) );
		update_post_meta( $id, self::META_TYPE, sanitize_key( $data['type'] ) );
		update_post_meta( $id, self::META_ATTACHMENT_ID, absint( $data['attachment_id'] ) );
		update_post_meta( $id, self::META_BOX_WIDTH, absint( $data['box_width'] ) );
		update_post_meta( $id, self::META_OFFSET_X, intval( $data['offset_x'] ) );
		update_post_meta( $id, self::META_OFFSET_Y, intval( $data['offset_y'] ) );

		return (int) $id;
	}

	/**
	 * Mueve un botón a la papelera.
	 *
	 * @param int $id
	 * @return bool
	 */
	public function trash( $id ) {
		$post = get_post( $id );

		if ( ! $post || LSC_Button_CPT::POST_TYPE !== $post->post_type ) {
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
		return array(
			'id'            => $post->ID,
			'name'          => $post->post_title,
			'active'        => 'publish' === $post->post_status,
			'selector'      => (string) get_post_meta( $post->ID, self::META_SELECTOR, true ),
			'url'           => (string) get_post_meta( $post->ID, self::META_URL, true ),
			'type'          => (string) get_post_meta( $post->ID, self::META_TYPE, true ),
			'attachment_id' => absint( get_post_meta( $post->ID, self::META_ATTACHMENT_ID, true ) ),
			'box_width'     => $this->meta_or_default( $post->ID, self::META_BOX_WIDTH, self::DEFAULT_BOX_WIDTH ),
			'offset_x'      => $this->meta_or_default( $post->ID, self::META_OFFSET_X, self::DEFAULT_OFFSET_X ),
			'offset_y'      => $this->meta_or_default( $post->ID, self::META_OFFSET_Y, self::DEFAULT_OFFSET_Y ),
		);
	}

	private function meta_or_default( $post_id, $key, $default ) {
		$value = get_post_meta( $post_id, $key, true );
		return ( '' === $value || false === $value ) ? $default : (int) $value;
	}
}
