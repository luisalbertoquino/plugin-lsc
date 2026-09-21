<?php
/**
 * Carga los assets del frontend y expone los bloques de contenido LSC
 * aplicables a la página actual, más la configuración de Campus Virtual.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LSC_Frontend {

	/** @var LSC_Blocks_Repository */
	private $repository;

	public function __construct( LSC_Blocks_Repository $repository ) {
		$this->repository = $repository;

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets() {
		$blocks_data = $this->get_applicable_blocks_data();
		$campus_data = $this->get_campus_data();

		if ( empty( $blocks_data ) && empty( $campus_data ) ) {
			return;
		}

		wp_enqueue_style(
			'lsc-accesibilidad',
			LSC_ACCESIBILIDAD_URL . 'assets/css/lsc-accessibility.css',
			array(),
			LSC_ACCESIBILIDAD_VERSION
		);

		wp_enqueue_script(
			'lsc-accesibilidad',
			LSC_ACCESIBILIDAD_URL . 'assets/js/lsc-accessibility.js',
			array(),
			LSC_ACCESIBILIDAD_VERSION,
			true
		);

		wp_localize_script( 'lsc-accesibilidad', 'lscAccesibilidadData', array(
			'blocks'       => $blocks_data,
			'campusVirtual' => $campus_data,
		) );
	}

	/**
	 * Devuelve, ya formateados para el JS, los bloques publicados cuyo
	 * alcance incluye la página actual.
	 *
	 * @return array
	 */
	private function get_applicable_blocks_data() {
		$blocks = $this->repository->get_published();
		$data   = array();

		foreach ( $blocks as $block ) {
			if ( ! $this->block_matches_scope( $block ) ) {
				continue;
			}

			$items = array();
			foreach ( $block['items'] as $item_id => $item ) {
				if ( empty( $item['url'] ) ) {
					continue;
				}
				$items[ (string) $item_id ] = array(
					'url'  => $item['url'],
					'type' => $item['type'],
				);
			}

			if ( empty( $items ) ) {
				continue;
			}

			$data[] = array(
				'id'              => $block['id'],
				'selectorDesktop' => $block['selector_desktop'],
				'selectorMobile'  => $block['selector_mobile'],
				'boxWidth'        => $block['box_width'],
				'offsetX'         => $block['offset_x'],
				'offsetY'         => $block['offset_y'],
				'items'           => $items,
			);
		}

		return $data;
	}

	/**
	 * Evalúa si un bloque debe mostrarse en la página que se está
	 * sirviendo actualmente, según su alcance guardado.
	 *
	 * @param array $block
	 * @return bool
	 */
	private function block_matches_scope( $block ) {
		if ( 'all' === $block['scope'] ) {
			return true;
		}

		if ( empty( $block['scope_pages'] ) ) {
			return false;
		}

		return is_page( $block['scope_pages'] );
	}

	private function get_campus_data() {
		$campus = get_option( LSC_ACCESIBILIDAD_CAMPUS_OPTION_KEY, array() );

		if ( empty( $campus['url'] ) ) {
			return array();
		}

		return array(
			'url'     => $campus['url'],
			'type'    => $campus['type'],
			'offsetX' => isset( $campus['offset_x'] ) ? (int) $campus['offset_x'] : 30,
			'offsetY' => isset( $campus['offset_y'] ) ? (int) $campus['offset_y'] : -15,
		);
	}
}
