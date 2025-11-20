<?php
/**
 * Popup save handler class.
 *
 * Handles saving popup configurations.
 *
 * @package ConvertLab
 * @since 1.0.0
 */

namespace ConvertLab\Admin;

/**
 * PopupSaveHandler class.
 *
 * @since 1.0.0
 */
class PopupSaveHandler {

	/**
	 * Instance.
	 *
	 * @var PopupSaveHandler
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return PopupSaveHandler
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {
		register_rest_route(
			'convertlab/v1',
			'/popup/save',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_popup' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'id'     => array(
						'required' => false,
						'type'     => 'integer',
					),
					'title'  => array(
						'required' => true,
						'type'     => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'config' => array(
						'required' => true,
						'type'     => 'object',
						'validate_callback' => array( $this, 'validate_config' ),
					),
				),
			)
		);
	}

	/**
	 * Check permission.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function check_permission() {
		return current_user_can( 'edit_clb_popups' );
	}

	/**
	 * Validate popup config.
	 *
	 * @param array $config Config data.
	 * @return bool
	 * @since 1.0.0
	 */
	public function validate_config( $config ) {
		if ( ! is_array( $config ) ) {
			return false;
		}

		// Validate required keys
		$required_keys = array( 'design', 'triggers' );
		foreach ( $required_keys as $key ) {
			if ( ! isset( $config[ $key ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Save popup.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 * @since 1.0.0
	 */
	public function save_popup( $request ) {
		$popup_id = $request->get_param( 'id' );
		$title    = $request->get_param( 'title' );
		$config   = $request->get_param( 'config' );

		// Sanitize config
		$config = $this->sanitize_config( $config );

		if ( $popup_id ) {
			// Update existing popup
			$post_id = wp_update_post(
				array(
					'ID'         => absint( $popup_id ),
					'post_title' => sanitize_text_field( $title ),
					'post_type'  => 'clb_popup',
				),
				true
			);
		} else {
			// Create new popup
			$post_id = wp_insert_post(
				array(
					'post_title'  => sanitize_text_field( $title ),
					'post_type'   => 'clb_popup',
					'post_status' => 'publish',
				),
				true
			);
		}

		if ( is_wp_error( $post_id ) ) {
			return new \WP_Error(
				'convertlab_save_error',
				$post_id->get_error_message(),
				array( 'status' => 500 )
			);
		}

		// Save config as JSON in postmeta
		update_post_meta( $post_id, '_clb_popup_config', $config );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'id'      => $post_id,
				'message' => __( 'Popup saved successfully.', 'convertlab' ),
			),
			200
		);
	}

	/**
	 * Sanitize popup config.
	 *
	 * @param array $config Config data.
	 * @return array Sanitized config.
	 * @since 1.0.0
	 */
	private function sanitize_config( $config ) {
		$sanitized = array();

		// Design settings
		if ( isset( $config['design'] ) && is_array( $config['design'] ) ) {
			$sanitized['design'] = array(
				'title'           => sanitize_text_field( $config['design']['title'] ?? '' ),
				'text'            => wp_kses_post( $config['design']['text'] ?? '' ),
				'image'           => absint( $config['design']['image'] ?? 0 ),
				'background_color' => sanitize_hex_color( $config['design']['background_color'] ?? '#ffffff' ),
				'button_text'     => sanitize_text_field( $config['design']['button_text'] ?? __( 'Submit', 'convertlab' ) ),
				'button_color'    => sanitize_hex_color( $config['design']['button_color'] ?? '#0073aa' ),
			);
		}

		// Form fields
		if ( isset( $config['fields'] ) && is_array( $config['fields'] ) ) {
			$sanitized['fields'] = array();
			foreach ( $config['fields'] as $field ) {
				$sanitized['fields'][] = array(
					'type'        => sanitize_key( $field['type'] ?? 'email' ),
					'name'        => sanitize_key( $field['name'] ?? '' ),
					'label'       => sanitize_text_field( $field['label'] ?? '' ),
					'required'    => ! empty( $field['required'] ),
					'placeholder' => sanitize_text_field( $field['placeholder'] ?? '' ),
				);
			}
		}

		// Trigger settings
		if ( isset( $config['triggers'] ) && is_array( $config['triggers'] ) ) {
			$sanitized['triggers'] = array(
				'page_targeting' => sanitize_text_field( $config['triggers']['page_targeting'] ?? 'all' ),
				'time_delay'     => absint( $config['triggers']['time_delay'] ?? 0 ),
				'scroll_percent' => absint( $config['triggers']['scroll_percent'] ?? 0 ),
				'show_once'      => ! empty( $config['triggers']['show_once'] ),
			);
		}

		// Thank you message
		if ( isset( $config['thank_you'] ) ) {
			$sanitized['thank_you'] = array(
				'message' => wp_kses_post( $config['thank_you']['message'] ?? '' ),
				'redirect' => esc_url_raw( $config['thank_you']['redirect'] ?? '' ),
			);
		}

		return $sanitized;
	}
}

