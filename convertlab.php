<?php
/**
 * Plugin Name: ConvertLab
 * Plugin URI: https://github.com/solutionswp/convertlab
 * Description: A lightweight, eCommerce-focused WordPress plugin designed to help store owners increase conversions through popups, opt-ins, lead capture, behavioral targeting, and actionable insights.
 * Version: 1.0.2
 * Author: SolutionsWP
 * Author URI: https://github.com/solutionswp
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: convertlab
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package ConvertLab
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 */
define( 'CONVERTLAB_VERSION', '1.0.2' );

/**
 * Plugin directory path.
 */
define( 'CONVERTLAB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'CONVERTLAB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'CONVERTLAB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Database version for migrations.
 */
define( 'CONVERTLAB_DB_VERSION', '1.0.0' );

/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
class ConvertLab {

	/**
	 * Plugin instance.
	 *
	 * @var ConvertLab
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @return ConvertLab
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
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load plugin dependencies.
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {
		// Utils
		require_once CONVERTLAB_PLUGIN_DIR . 'src/Utils/DBInstaller.php';
		require_once CONVERTLAB_PLUGIN_DIR . 'src/Utils/LeadModel.php';
		require_once CONVERTLAB_PLUGIN_DIR . 'src/Utils/Analytics.php';
		require_once CONVERTLAB_PLUGIN_DIR . 'src/Utils/Updater.php';

		// Admin
		require_once CONVERTLAB_PLUGIN_DIR . 'src/Admin/CPTPopup.php';
		require_once CONVERTLAB_PLUGIN_DIR . 'src/Admin/AdminMenu.php';
		require_once CONVERTLAB_PLUGIN_DIR . 'src/Admin/SettingsPage.php';
		require_once CONVERTLAB_PLUGIN_DIR . 'src/Admin/PopupBuilderPage.php';
		require_once CONVERTLAB_PLUGIN_DIR . 'src/Admin/PopupSaveHandler.php';
		require_once CONVERTLAB_PLUGIN_DIR . 'src/Admin/LeadsPage.php';

		// API
		require_once CONVERTLAB_PLUGIN_DIR . 'src/API/RegisterAPI.php';

		// Frontend
		require_once CONVERTLAB_PLUGIN_DIR . 'src/Frontend/PopupRenderer.php';
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Activation and deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Initialize admin menu early (before admin_menu hook fires)
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'init_admin_menu' ), 5 );
			add_action( 'admin_init', array( $this, 'init_admin' ) );
		}

		// Initialize frontend
		add_action( 'init', array( $this, 'init_frontend' ) );

		// Load text domain
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Plugin activation.
	 *
	 * @since 1.0.0
	 */
	public function activate() {
		// Install database tables
		$installer = new ConvertLab\Utils\DBInstaller();
		$installer->install();

		// Set default options
		add_option( 'convertlab_db_version', CONVERTLAB_DB_VERSION );
		add_option( 'convertlab_update_url', 'https://raw.githubusercontent.com/solutionswp/convertlab/main/update.json' );
		add_option( 'convertlab_webhook_enabled', false );
		add_option( 'convertlab_data_retention_days', 365 );

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Initialize admin menu (called early, before admin_menu hook).
	 *
	 * @since 1.0.0
	 */
	public function init_admin_menu() {
		// Initialize admin menu
		ConvertLab\Admin\AdminMenu::get_instance();
	}

	/**
	 * Initialize admin functionality.
	 *
	 * @since 1.0.0
	 */
	public function init_admin() {
		// Register Custom Post Type
		ConvertLab\Admin\CPTPopup::get_instance();

		// Initialize popup save handler
		ConvertLab\Admin\PopupSaveHandler::get_instance();

		// Initialize updater
		ConvertLab\Utils\Updater::get_instance();
	}

	/**
	 * Initialize frontend functionality.
	 *
	 * @since 1.0.0
	 */
	public function init_frontend() {
		// Register REST API
		ConvertLab\API\RegisterAPI::get_instance();

		// Initialize popup renderer
		ConvertLab\Frontend\PopupRenderer::get_instance();
	}

	/**
	 * Load plugin text domain.
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'convertlab',
			false,
			dirname( CONVERTLAB_PLUGIN_BASENAME ) . '/languages'
		);
	}
}

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 */
function convertlab_init() {
	return ConvertLab::get_instance();
}

// Start the plugin
convertlab_init();

