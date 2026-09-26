<?php
/**
 * Orquestador de las páginas de administración del plugin: registra
 * el menú y las páginas ocultas, y delega el renderizado a las clases
 * dedicadas de listado, formulario de bloque y Campus Virtual.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LSC_Admin {

	/** @var LSC_Admin_List */
	private $list_screen;

	/** @var LSC_Admin_Form */
	private $form_screen;

	/** @var LSC_Admin_Buttons_List */
	private $buttons_list_screen;

	/** @var LSC_Admin_Buttons_Form */
	private $buttons_form_screen;

	public function __construct( LSC_Blocks_Repository $repository, LSC_Buttons_Repository $buttons_repository ) {
		$this->list_screen         = new LSC_Admin_List( $repository );
		$this->form_screen         = new LSC_Admin_Form( $repository );
		$this->buttons_list_screen = new LSC_Admin_Buttons_List( $buttons_repository );
		$this->buttons_form_screen = new LSC_Admin_Buttons_Form( $buttons_repository );

		add_action( 'admin_menu', array( $this, 'register_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_notices', array( $this, 'show_notices' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( LSC_ACCESIBILIDAD_FILE ), array( $this, 'add_settings_link' ) );
	}

	/**
	 * Añade el enlace "Configuración" junto a Activar/Desactivar en la
	 * lista general de plugins.
	 *
	 * @param array $links
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$settings_url = admin_url( 'admin.php?page=lsc-accesibilidad' );
		$settings_link = '<a href="' . esc_url( $settings_url ) . '">Configuración</a>';

		array_unshift( $links, $settings_link );

		return $links;
	}

	public function register_pages() {
		add_menu_page(
			'Accesibilidad LSC',
			'Accesibilidad LSC',
			'manage_options',
			'lsc-accesibilidad',
			array( $this->list_screen, 'render' ),
			'dashicons-video-alt3',
			80
		);

		add_submenu_page(
			null, // No aparece en el menú lateral, se llega por enlace.
			'Bloque LSC',
			'Bloque LSC',
			'manage_options',
			'lsc-accesibilidad-form',
			array( $this->form_screen, 'render' )
		);

		add_submenu_page(
			'lsc-accesibilidad',
			'Botones fijos LSC',
			'Botones fijos',
			'manage_options',
			'lsc-accesibilidad-botones',
			array( $this->buttons_list_screen, 'render' )
		);

		add_submenu_page(
			null,
			'Botón fijo LSC',
			'Botón fijo LSC',
			'manage_options',
			'lsc-accesibilidad-boton-form',
			array( $this->buttons_form_screen, 'render' )
		);
	}

	public function enqueue_admin_assets( $hook ) {
		$pages = array(
			'toplevel_page_lsc-accesibilidad',
			'admin_page_lsc-accesibilidad-form',
			'lsc-accesibilidad_page_lsc-accesibilidad-botones',
			'admin_page_lsc-accesibilidad-boton-form',
		);

		if ( ! in_array( $hook, $pages, true ) ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_script(
			'lsc-accesibilidad-admin',
			LSC_ACCESIBILIDAD_URL . 'assets/js/lsc-accessibility-admin.js',
			array( 'jquery' ),
			LSC_ACCESIBILIDAD_VERSION,
			true
		);

		wp_localize_script( 'lsc-accesibilidad-admin', 'lscAccesibilidadAdmin', array(
			'maxFileSize'      => LSC_Admin_Form::MAX_FILE_SIZE,
			'maxFileSizeLabel' => '3 MB',
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'menuItemsNonce'   => wp_create_nonce( LSC_Admin_Form::NONCE_ACTION ),
		) );

		wp_enqueue_style(
			'lsc-accesibilidad-admin',
			LSC_ACCESIBILIDAD_URL . 'assets/css/lsc-accessibility-admin.css',
			array(),
			LSC_ACCESIBILIDAD_VERSION
		);
	}

	public function show_notices() {
		if ( ! isset( $_GET['page'] ) || 0 !== strpos( (string) $_GET['page'], 'lsc-accesibilidad' ) ) {
			return;
		}

		if ( isset( $_GET['lsc_saved'] ) && '1' === $_GET['lsc_saved'] ) {
			echo '<div class="notice notice-success is-dismissible"><p>Cambios guardados correctamente.</p></div>';
		}
	}
}
