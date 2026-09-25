<?php
/**
 * Registra el tipo de contenido interno usado para guardar los botones
 * fijos de contenido LSC (ej. "Campus Virtual etR"). No es público ni
 * tiene interfaz nativa: toda la administración se hace desde las
 * pantallas propias del plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LSC_Button_CPT {

	const POST_TYPE = 'lsc_boton';

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function register() {
		register_post_type( self::POST_TYPE, array(
			'label'               => 'Botones fijos LSC',
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'supports'            => array( 'title' ),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		) );
	}
}
