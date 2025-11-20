<?php
/**
 * Popup renderer class.
 *
 * Handles frontend popup rendering.
 *
 * @package ConvertLab
 * @since 1.0.0
 */

namespace ConvertLab\Frontend;

/**
 * PopupRenderer class.
 *
 * @since 1.0.0
 */
class PopupRenderer {

	/**
	 * Instance.
	 *
	 * @var PopupRenderer
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return PopupRenderer
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_footer', array( $this, 'render_popups' ) );
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		// Get active popups
		$popups = $this->get_active_popups();

		if ( empty( $popups ) ) {
			return;
		}

		// Enqueue popup loader script
		wp_enqueue_script(
			'convertlab-popup-loader',
			CONVERTLAB_PLUGIN_URL . 'assets/js/popup-loader.js',
			array(),
			CONVERTLAB_VERSION,
			true
		);

		// Enqueue popup styles
		wp_enqueue_style(
			'convertlab-popup',
			CONVERTLAB_PLUGIN_URL . 'assets/css/popup.css',
			array(),
			CONVERTLAB_VERSION
		);

		// Localize script
		wp_localize_script(
			'convertlab-popup-loader',
			'convertlabPopup',
			array(
				'apiUrl' => rest_url( 'convertlab/v1/' ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
				'popups' => $this->prepare_popups_data( $popups ),
			)
		);
	}

	/**
	 * Get active popups for current page.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	private function get_active_popups() {
		$popups = get_posts(
			array(
				'post_type'      => 'clb_popup',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'meta_query'     => array(
					array(
						'key'     => '_clb_popup_config',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		$active_popups = array();

		foreach ( $popups as $popup ) {
			$config = get_post_meta( $popup->ID, '_clb_popup_config', true );

			if ( ! $config || ! is_array( $config ) ) {
				continue;
			}

			// Check page targeting
			if ( ! $this->matches_page_targeting( $config ) ) {
				continue;
			}

			$active_popups[] = $popup;
		}

		return $active_popups;
	}

	/**
	 * Check if popup matches page targeting rules.
	 *
	 * @param array $config Popup config.
	 * @return bool
	 * @since 1.0.0
	 */
	private function matches_page_targeting( $config ) {
		if ( ! isset( $config['triggers']['page_targeting'] ) ) {
			return true;
		}

		$page_targeting = $config['triggers']['page_targeting'];

		if ( 'all' === $page_targeting ) {
			return true;
		}

		if ( 'homepage' === $page_targeting ) {
			return is_front_page();
		}

		if ( 'product' === $page_targeting && function_exists( 'is_product' ) ) {
			return is_product();
		}

		return true;
	}

	/**
	 * Prepare popups data for frontend.
	 *
	 * @param array $popups Popup posts.
	 * @return array
	 * @since 1.0.0
	 */
	private function prepare_popups_data( $popups ) {
		$data = array();

		foreach ( $popups as $popup ) {
			$config = get_post_meta( $popup->ID, '_clb_popup_config', true );

			$data[] = array(
				'id'     => $popup->ID,
				'title'  => $popup->post_title,
				'config' => $config,
			);
		}

		return $data;
	}

	/**
	 * Render popup container in footer.
	 *
	 * @since 1.0.0
	 */
	public function render_popups() {
		$popups = $this->get_active_popups();

		if ( empty( $popups ) ) {
			return;
		}
		?>
		<div id="convertlab-popup-container"></div>
		<?php
	}
}

