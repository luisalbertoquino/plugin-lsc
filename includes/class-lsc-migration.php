<?php
/**
 * Migra la configuración de versiones anteriores del plugin al modelo
 * de datos actual:
 * - 2.0.0: de una sola option con ítems de nivel 0 y nivel 1 mezclados,
 *   al modelo de bloques CRUD (conservando solo los ítems de nivel 0).
 * - 2.1.0: del botón "Campus Virtual etR" como caso especial en su
 *   propia option, al CRUD de botones fijos (que permite añadir más).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LSC_Migration {

	const DATA_VERSION_OPTION = 'lsc_accesibilidad_data_version';
	const CURRENT_DATA_VERSION = '2.1.0';

	const LEGACY_CAMPUS_SELECTOR = '.unnv-campus-btn';

	/** @var LSC_Blocks_Repository */
	private $repository;

	/** @var LSC_Buttons_Repository */
	private $buttons_repository;

	public function __construct( LSC_Blocks_Repository $repository, LSC_Buttons_Repository $buttons_repository ) {
		$this->repository          = $repository;
		$this->buttons_repository  = $buttons_repository;
	}

	public function maybe_migrate() {
		$current_version = get_option( self::DATA_VERSION_OPTION, '' );

		if ( version_compare( $current_version, self::CURRENT_DATA_VERSION, '>=' ) ) {
			return;
		}

		if ( version_compare( $current_version, '2.0.0', '<' ) ) {
			$old_items = get_option( LSC_ACCESIBILIDAD_OPTION_KEY, array() );

			if ( ! empty( $old_items ) && is_array( $old_items ) ) {
				$this->migrate_campus_virtual_legacy( $old_items );
				$this->migrate_menu_block( $old_items );
			}
		}

		if ( version_compare( $current_version, '2.1.0', '<' ) ) {
			$this->migrate_campus_virtual_to_button_cpt();
		}

		update_option( self::DATA_VERSION_OPTION, self::CURRENT_DATA_VERSION );
	}

	/**
	 * 2.0.0: extrae el ítem especial de Campus Virtual de la option
	 * antigua y lo deja en su propia option intermedia (formato usado
	 * entre 2.0.0 y 2.0.x), previo al CRUD de botones fijos.
	 */
	private function migrate_campus_virtual_legacy( $old_items ) {
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

	/**
	 * 2.1.0: migra la option intermedia de Campus Virtual (2.0.0-2.0.x)
	 * al nuevo CRUD de botones fijos, como su primer registro.
	 */
	private function migrate_campus_virtual_to_button_cpt() {
		$campus = get_option( LSC_ACCESIBILIDAD_CAMPUS_OPTION_KEY, array() );

		if ( empty( $campus ) || ! is_array( $campus ) || empty( $campus['url'] ) ) {
			return;
		}

		$this->buttons_repository->save( null, array(
			'name'          => 'Campus Virtual etR',
			'active'        => true,
			'selector'      => self::LEGACY_CAMPUS_SELECTOR,
			'url'           => $campus['url'],
			'type'          => isset( $campus['type'] ) ? $campus['type'] : '',
			'attachment_id' => isset( $campus['attachment_id'] ) ? $campus['attachment_id'] : 0,
			'box_width'     => isset( $campus['box_width'] ) ? $campus['box_width'] : LSC_Buttons_Repository::DEFAULT_BOX_WIDTH,
			'offset_x'      => isset( $campus['offset_x'] ) ? $campus['offset_x'] : LSC_Buttons_Repository::DEFAULT_OFFSET_X,
			'offset_y'      => isset( $campus['offset_y'] ) ? $campus['offset_y'] : LSC_Buttons_Repository::DEFAULT_OFFSET_Y,
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
