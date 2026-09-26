<?php
/**
 * Plugin Name: Accesibilidad LSC - Bloques por Menú
 * Description: Muestra videos o GIF en Lengua de Señas Colombiana (LSC) asociados a los ítems principales de cualquier menú de WordPress, mediante bloques configurables (menú, tamaño, posición y páginas donde se muestran), al hacer hover en escritorio o tocar un ícono en móvil.
 * Version: 2.1.1
 * Requires PHP: 7.4
 * Author: UNINAVARRA
 * Text Domain: lsc-accesibilidad
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LSC_ACCESIBILIDAD_VERSION', '2.1.1' );
define( 'LSC_ACCESIBILIDAD_FILE', __FILE__ );
define( 'LSC_ACCESIBILIDAD_DIR', plugin_dir_path( __FILE__ ) );
define( 'LSC_ACCESIBILIDAD_URL', plugin_dir_url( __FILE__ ) );
define( 'LSC_ACCESIBILIDAD_OPTION_KEY', 'lsc_accesibilidad_items' );
define( 'LSC_ACCESIBILIDAD_CAMPUS_OPTION_KEY', 'lsc_accesibilidad_campus' );

require_once LSC_ACCESIBILIDAD_DIR . 'includes/class-lsc-menu-items.php';
require_once LSC_ACCESIBILIDAD_DIR . 'includes/class-lsc-block-cpt.php';
require_once LSC_ACCESIBILIDAD_DIR . 'includes/class-lsc-blocks-repository.php';
require_once LSC_ACCESIBILIDAD_DIR . 'includes/class-lsc-button-cpt.php';
require_once LSC_ACCESIBILIDAD_DIR . 'includes/class-lsc-buttons-repository.php';
require_once LSC_ACCESIBILIDAD_DIR . 'includes/class-lsc-migration.php';
require_once LSC_ACCESIBILIDAD_DIR . 'includes/class-lsc-admin-list.php';
require_once LSC_ACCESIBILIDAD_DIR . 'includes/class-lsc-admin-form.php';
require_once LSC_ACCESIBILIDAD_DIR . 'includes/class-lsc-admin-buttons-list.php';
require_once LSC_ACCESIBILIDAD_DIR . 'includes/class-lsc-admin-buttons-form.php';
require_once LSC_ACCESIBILIDAD_DIR . 'includes/class-lsc-admin.php';
require_once LSC_ACCESIBILIDAD_DIR . 'includes/class-lsc-frontend.php';

register_activation_hook( LSC_ACCESIBILIDAD_FILE, 'lsc_accesibilidad_activate' );

function lsc_accesibilidad_activate() {
	if ( false === get_option( LSC_ACCESIBILIDAD_OPTION_KEY ) ) {
		add_option( LSC_ACCESIBILIDAD_OPTION_KEY, array() );
	}
}

function lsc_accesibilidad_init() {
	new LSC_Block_CPT();
	new LSC_Button_CPT();

	$repository         = new LSC_Blocks_Repository();
	$buttons_repository = new LSC_Buttons_Repository();

	// Prioridad 20: corre después de que los CPT se registren en 'init'.
	add_action( 'init', function () use ( $repository, $buttons_repository ) {
		$migration = new LSC_Migration( $repository, $buttons_repository );
		$migration->maybe_migrate();
	}, 20 );

	new LSC_Admin( $repository, $buttons_repository );
	new LSC_Frontend( $repository, $buttons_repository );
}
add_action( 'plugins_loaded', 'lsc_accesibilidad_init' );
