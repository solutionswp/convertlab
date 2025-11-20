<?php
/**
 * REST API registration class.
 *
 * Registers all REST API endpoints.
 *
 * @package ConvertLab
 * @since 1.0.0
 */

namespace ConvertLab\API;

use ConvertLab\Utils\LeadModel;
use ConvertLab\Utils\Analytics;

/**
 * RegisterAPI class.
 *
 * @since 1.0.0
 */
class RegisterAPI {

	/**
	 * Instance.
	 *
	 * @var RegisterAPI
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return RegisterAPI
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
		// Get popup config
		register_rest_route(
			'convertlab/v1',
			'/popup/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_popup_config' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required' => true,
						'type'     => 'integer',
					),
				),
			)
		);

		// Submit lead
		register_rest_route(
			'convertlab/v1',
			'/lead/submit',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'submit_lead' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'popup_id' => array(
						'required' => true,
						'type'     => 'integer',
					),
					'email'    => array(
						'required' => true,
						'type'     => 'string',
						'validate_callback' => 'is_email',
					),
					'name'     => array(
						'required' => false,
						'type'     => 'string',
					),
					'phone'    => array(
						'required' => false,
						'type'     => 'string',
					),
					'form_data' => array(
						'required' => false,
						'type'     => 'object',
					),
				),
			)
		);

		// Record event
		register_rest_route(
			'convertlab/v1',
			'/event',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'record_event' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'popup_id'   => array(
						'required' => true,
						'type'     => 'integer',
					),
					'event_type' => array(
						'required' => true,
						'type'     => 'string',
						'enum'     => array( 'impression', 'conversion' ),
					),
				),
			)
		);
	}

	/**
	 * Get popup config.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 * @since 1.0.0
	 */
	public function get_popup_config( $request ) {
		$popup_id = absint( $request->get_param( 'id' ) );

		$popup = get_post( $popup_id );

		if ( ! $popup || 'clb_popup' !== $popup->post_type ) {
			return new \WP_Error(
				'convertlab_popup_not_found',
				__( 'Popup not found.', 'convertlab' ),
				array( 'status' => 404 )
			);
		}

		if ( 'publish' !== $popup->post_status ) {
			return new \WP_Error(
				'convertlab_popup_not_published',
				__( 'Popup is not published.', 'convertlab' ),
				array( 'status' => 403 )
			);
		}

		$config = get_post_meta( $popup_id, '_clb_popup_config', true );

		if ( ! $config || ! is_array( $config ) ) {
			return new \WP_Error(
				'convertlab_config_not_found',
				__( 'Popup configuration not found.', 'convertlab' ),
				array( 'status' => 404 )
			);
		}

		// Check visibility rules
		if ( ! $this->check_visibility_rules( $popup_id, $config ) ) {
			return new \WP_Error(
				'convertlab_popup_not_visible',
				__( 'Popup is not visible on this page.', 'convertlab' ),
				array( 'status' => 403 )
			);
		}

		return new \WP_REST_Response(
			array(
				'id'     => $popup_id,
				'title'  => $popup->post_title,
				'config' => $config,
			),
			200
		);
	}

	/**
	 * Check visibility rules.
	 *
	 * @param int   $popup_id Popup ID.
	 * @param array $config Popup config.
	 * @return bool
	 * @since 1.0.0
	 */
	private function check_visibility_rules( $popup_id, $config ) {
		if ( ! isset( $config['triggers']['page_targeting'] ) ) {
			return true;
		}

		$page_targeting = $config['triggers']['page_targeting'];

		if ( 'all' === $page_targeting ) {
			return true;
		}

		if ( 'product' === $page_targeting && function_exists( 'is_product' ) ) {
			return is_product();
		}

		if ( 'homepage' === $page_targeting ) {
			return is_front_page();
		}

		return true;
	}

	/**
	 * Submit lead.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 * @since 1.0.0
	 */
	public function submit_lead( $request ) {
		$popup_id = absint( $request->get_param( 'popup_id' ) );
		$email    = sanitize_email( $request->get_param( 'email' ) );
		$name     = sanitize_text_field( $request->get_param( 'name' ) ?? '' );
		$phone    = sanitize_text_field( $request->get_param( 'phone' ) ?? '' );
		$form_data = $request->get_param( 'form_data' ) ?? array();

		// Verify popup exists
		$popup = get_post( $popup_id );
		if ( ! $popup || 'clb_popup' !== $popup->post_type ) {
			return new \WP_Error(
				'convertlab_invalid_popup',
				__( 'Invalid popup ID.', 'convertlab' ),
				array( 'status' => 400 )
			);
		}

		// Validate email
		if ( ! is_email( $email ) ) {
			return new \WP_Error(
				'convertlab_invalid_email',
				__( 'Invalid email address.', 'convertlab' ),
				array( 'status' => 400 )
			);
		}

		// Save lead
		$lead_model = new LeadModel();
		$lead_id    = $lead_model->insert_lead(
			array(
				'popup_id'  => $popup_id,
				'email'     => $email,
				'name'      => $name,
				'phone'     => $phone,
				'form_data' => $form_data,
			)
		);

		if ( ! $lead_id ) {
			return new \WP_Error(
				'convertlab_save_failed',
				__( 'Failed to save lead.', 'convertlab' ),
				array( 'status' => 500 )
			);
		}

		// Record conversion
		$analytics = Analytics::get_instance();
		$analytics->record_conversion( $popup_id );

		// Send webhook if enabled
		$this->send_webhook( $popup_id, $email, $name, $phone, $form_data );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'lead_id' => $lead_id,
				'message' => __( 'Lead saved successfully.', 'convertlab' ),
			),
			200
		);
	}

	/**
	 * Record event (impression or conversion).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 * @since 1.0.0
	 */
	public function record_event( $request ) {
		$popup_id   = absint( $request->get_param( 'popup_id' ) );
		$event_type = sanitize_text_field( $request->get_param( 'event_type' ) );

		// Verify popup exists
		$popup = get_post( $popup_id );
		if ( ! $popup || 'clb_popup' !== $popup->post_type ) {
			return new \WP_Error(
				'convertlab_invalid_popup',
				__( 'Invalid popup ID.', 'convertlab' ),
				array( 'status' => 400 )
			);
		}

		$analytics = Analytics::get_instance();

		if ( 'impression' === $event_type ) {
			$analytics->record_impression( $popup_id );
		} elseif ( 'conversion' === $event_type ) {
			$analytics->record_conversion( $popup_id );
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Event recorded successfully.', 'convertlab' ),
			),
			200
		);
	}

	/**
	 * Send webhook if enabled.
	 *
	 * @param int    $popup_id Popup ID.
	 * @param string $email Email address.
	 * @param string $name Name.
	 * @param string $phone Phone.
	 * @param array  $form_data Additional form data.
	 * @since 1.0.0
	 */
	private function send_webhook( $popup_id, $email, $name, $phone, $form_data ) {
		$webhook_enabled = get_option( 'convertlab_webhook_enabled', false );
		$webhook_url     = get_option( 'convertlab_webhook_url', '' );

		if ( ! $webhook_enabled || empty( $webhook_url ) ) {
			return;
		}

		$payload = array(
			'popup_id'  => $popup_id,
			'email'     => $email,
			'name'      => $name,
			'phone'     => $phone,
			'form_data' => $form_data,
			'timestamp' => current_time( 'mysql' ),
		);

		wp_remote_post(
			$webhook_url,
			array(
				'body'    => wp_json_encode( $payload ),
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'timeout' => 15,
			)
		);
	}
}

