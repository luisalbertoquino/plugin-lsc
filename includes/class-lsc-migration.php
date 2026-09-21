<?php
/**
 * Migra la configuración de la versión anterior del plugin (una sola
 * option con ítems de nivel 0 y nivel 1 mezclados) al nuevo modelo de
 * bloques CRUD, conservando solo los ítems de nivel 0.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LSC_Migration {

	const DATA_VERSION_OPTION = 'lsc_accesibilidad_data_version';
	const CURRENT_DATA_VERSION = '2.0.0';

	/** @var LSC_Blocks_Repository */
	private $repository;

	public function __construct( LSC_Blocks_Repository $repository ) {
		$this->repository = $repository;
	}

	public function maybe_migrate() {
		$current_version = get_option( self::DATA_VERSION_OPTION, '' );

		if ( version_compare( $current_version, self::CURRENT_DATA_VERSION, '>=' ) ) {
			return;
		}

		$old_items = get_option( LSC_ACCESIBILIDAD_OPTION_KEY, array() );

		if ( empty( $old_items ) || ! is_array( $old_items ) ) {
			update_option( self::DATA_VERSION_OPTION, self::CURRENT_DATA_VERSION );
			return;
		}

		$this->migrate_campus_virtual( $old_items );
		$this->migrate_menu_block( $old_items );

		update_option( self::DATA_VERSION_OPTION, self::CURRENT_DATA_VERSION );
	}

	private function migrate_campus_virtual( $old_items ) {
		if ( empty( $old_items['custom-campus-virtual-etr'] ) ) {
			return;
		}

		$campus_item = $old_items['custom-campus-virtual-etr'];

		update_option( LSC_ACCESIBILIDAD_CAMPUS_OPTION_KEY, array(
			'url'           => isset( $campus_item['url'] ) ? $campus_item['url'] : '',
			'type'          => isset( $campus_item['type'] ) ? $campus_item['type'] : '',
			'attachment_id' => isset( $campus_item['attachment_id'] ) ? $campus_item['attachment_id'] : 0,
			'offset_x'      => 30,
			'offset_y'      => -15,
		) );
	}

	private function migrate_menu_block( $old_items ) {
		$menu_id = LSC_Menu_Items::get_primary_menu();

		if ( ! $menu_id ) {
			return;
		}

		$valid_top_level_ids = LSC_Menu_Items::get_valid_ids( $menu_id );

		$migrated_items = array();

		foreach ( $old_items as $item_id => $item ) {
			if ( ! is_numeric( $item_id ) ) {
				continue; // Descarta el ítem especial de Campus Virtual, ya migrado aparte.
			}

			$item_id = (int) $item_id;

			if ( ! in_array( $item_id, $valid_top_level_ids, true ) ) {
				continue; // Era de nivel 1, o ya no existe: se descarta.
			}

			$migrated_items[ $item_id ] = array(
				'url'           => isset( $item['url'] ) ? $item['url'] : '',
				'type'          => isset( $item['type'] ) ? $item['type'] : '',
				'attachment_id' => isset( $item['attachment_id'] ) ? $item['attachment_id'] : 0,
			);
		}

		if ( empty( $migrated_items ) ) {
			return;
		}

		$this->repository->save( null, array(
			'name'             => 'Menú principal (migrado)',
			'active'           => true,
			'menu_id'          => $menu_id,
			'selector_desktop' => LSC_Blocks_Repository::DEFAULT_SELECTOR_DESKTOP,
			'selector_mobile'  => LSC_Blocks_Repository::DEFAULT_SELECTOR_MOBILE,
			'items'            => $migrated_items,
			'box_width'        => LSC_Blocks_Repository::DEFAULT_BOX_WIDTH,
			'offset_x'         => 0,
			'offset_y'         => 0,
			'scope'            => 'all',
			'scope_pages'      => array(),
			'migrated_from'    => LSC_ACCESIBILIDAD_OPTION_KEY,
		) );
	}
}
