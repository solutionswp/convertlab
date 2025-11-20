<?php
/**
 * Admin menu class.
 *
 * Creates the ConvertLab admin menu structure.
 *
 * @package ConvertLab
 * @since 1.0.0
 */

namespace ConvertLab\Admin;

/**
 * AdminMenu class.
 *
 * @since 1.0.0
 */
class AdminMenu {

	/**
	 * Instance.
	 *
	 * @var AdminMenu
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return AdminMenu
	 * @since 1.0.0
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
	}

	/**
	 * Add admin menu pages.
	 *
	 * @since 1.0.0
	 */
	public function add_menu_pages() {
		// Main menu
		add_menu_page(
			__( 'ConvertLab', 'convertlab' ),
			__( 'ConvertLab', 'convertlab' ),
			'manage_options',
			'convertlab',
			array( $this, 'render_popups_page' ),
			'dashicons-megaphone',
			30
		);

		// Popups submenu
		add_submenu_page(
			'convertlab',
			__( 'Popups', 'convertlab' ),
			__( 'Popups', 'convertlab' ),
			'edit_clb_popups',
			'convertlab',
			array( $this, 'render_popups_page' )
		);

		// Leads submenu
		add_submenu_page(
			'convertlab',
			__( 'Leads', 'convertlab' ),
			__( 'Leads', 'convertlab' ),
			'manage_options',
			'convertlab-leads',
			array( $this, 'render_leads_page' )
		);

		// Settings submenu
		add_submenu_page(
			'convertlab',
			__( 'Settings', 'convertlab' ),
			__( 'Settings', 'convertlab' ),
			'manage_options',
			'convertlab-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render popups page.
	 *
	 * @since 1.0.0
	 */
	public function render_popups_page() {
		$popup_builder = PopupBuilderPage::get_instance();
		$popup_builder->render();
	}

	/**
	 * Render leads page.
	 *
	 * @since 1.0.0
	 */
	public function render_leads_page() {
		$leads_page = LeadsPage::get_instance();
		$leads_page->render();
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_settings_page() {
		$settings_page = SettingsPage::get_instance();
		$settings_page->render();
	}
}

