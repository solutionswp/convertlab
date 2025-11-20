<?php
/**
 * Popup builder page class.
 *
 * Renders the popup builder admin page.
 *
 * @package ConvertLab
 * @since 1.0.0
 */

namespace ConvertLab\Admin;

use ConvertLab\Utils\Analytics;

/**
 * PopupBuilderPage class.
 *
 * @since 1.0.0
 */
class PopupBuilderPage {

	/**
	 * Instance.
	 *
	 * @var PopupBuilderPage
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return PopupBuilderPage
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 * @since 1.0.0
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'toplevel_page_convertlab' !== $hook ) {
			return;
		}

		// Enqueue builder SPA
		wp_enqueue_script(
			'convertlab-builder',
			CONVERTLAB_PLUGIN_URL . 'assets/js/admin-builder/dist/main.js',
			array(),
			CONVERTLAB_VERSION,
			true
		);

		wp_enqueue_style(
			'convertlab-builder',
			CONVERTLAB_PLUGIN_URL . 'assets/js/admin-builder/dist/style.css',
			array(),
			CONVERTLAB_VERSION
		);

		// Localize script
		wp_localize_script(
			'convertlab-builder',
			'convertlabBuilder',
			array(
				'apiUrl'   => rest_url( 'convertlab/v1/' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'popups'   => $this->get_popups_list(),
				'templates' => $this->get_templates(),
			)
		);
	}

	/**
	 * Get popups list.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	private function get_popups_list() {
		$popups = get_posts(
			array(
				'post_type'      => 'clb_popup',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		$list = array();
		foreach ( $popups as $popup ) {
			$analytics = Analytics::get_instance();
			$list[]    = array(
				'id'            => $popup->ID,
				'title'         => $popup->post_title,
				'status'        => $popup->post_status,
				'impressions'   => $analytics->get_impressions( $popup->ID ),
				'conversions'   => $analytics->get_conversions( $popup->ID ),
				'conversion_rate' => $analytics->get_conversion_rate( $popup->ID ),
				'config'        => get_post_meta( $popup->ID, '_clb_popup_config', true ),
			);
		}

		return $list;
	}

	/**
	 * Get available templates.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	private function get_templates() {
		return array(
			'minimal'      => array(
				'name'        => __( 'Minimal', 'convertlab' ),
				'description' => __( 'Simple and clean popup design', 'convertlab' ),
				'preview'     => CONVERTLAB_PLUGIN_URL . 'templates/popups/minimal/preview.jpg',
			),
			'image-text'   => array(
				'name'        => __( 'Image + Text', 'convertlab' ),
				'description' => __( 'Popup with image and text content', 'convertlab' ),
				'preview'     => CONVERTLAB_PLUGIN_URL . 'templates/popups/image-text/preview.jpg',
			),
			'coupon'       => array(
				'name'        => __( 'Coupon Popup', 'convertlab' ),
				'description' => __( 'Coupon code popup for eCommerce', 'convertlab' ),
				'preview'     => CONVERTLAB_PLUGIN_URL . 'templates/popups/coupon/preview.jpg',
			),
		);
	}

	/**
	 * Render popup builder page.
	 *
	 * @since 1.0.0
	 */
	public function render() {
		if ( ! current_user_can( 'edit_clb_popups' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'convertlab' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div id="convertlab-builder-root"></div>
		</div>
		<?php
	}
}

