<?php
/**
 * Obtiene los ítems de nivel 0 de un menú de WordPress específico,
 * para asignarles contenido LSC dentro de un bloque.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LSC_Menu_Items {

	/**
	 * Devuelve los ítems de nivel 0 (top-level) de un menú de WordPress.
	 *
	 * @param int $menu_id Term ID del menú.
	 * @return array Lista de arrays { id, title }.
	 */
	public static function get_top_level_items( $menu_id ) {
		if ( ! $menu_id ) {
			return array();
		}

		$menu_items = wp_get_nav_menu_items( $menu_id );

		if ( ! $menu_items ) {
			return array();
		}

		$top_level = array();

		foreach ( $menu_items as $menu_item ) {
			if ( 0 === (int) $menu_item->menu_item_parent ) {
				$top_level[] = array(
					'id'    => (int) $menu_item->ID,
					'title' => $menu_item->title,
				);
			}
		}

		return $top_level;
	}

	/**
	 * Devuelve la lista de IDs de nivel 0 válidos para un menú, usada
	 * para validar el guardado de un bloque.
	 *
	 * @param int $menu_id
	 * @return array
	 */
	public static function get_valid_ids( $menu_id ) {
		return wp_list_pluck( self::get_top_level_items( $menu_id ), 'id' );
	}

	/**
	 * Localiza el menú que la versión anterior del plugin autodetectaba,
	 * usado únicamente por la rutina de migración de datos antiguos.
	 *
	 * @return int|WP_Term|false
	 */
	public static function get_primary_menu() {
		$locations = get_nav_menu_locations();

		$preferred_locations = array( 'primary', 'main-menu', 'main_menu', 'header-menu', 'top' );

		foreach ( $preferred_locations as $location ) {
			if ( ! empty( $locations[ $location ] ) ) {
				return $locations[ $location ];
			}
		}

		if ( ! empty( $locations ) ) {
			$first_location = reset( $locations );
			if ( $first_location ) {
				return $first_location;
			}
		}

		$menus = wp_get_nav_menus();

		if ( ! empty( $menus ) ) {
			return $menus[0]->term_id;
		}

		return false;
	}
}
