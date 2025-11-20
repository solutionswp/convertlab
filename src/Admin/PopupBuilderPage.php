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
		add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 * @since 1.0.0
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'toplevel_page_convertlab' !== $hook && 'convertlab_page_convertlab-edit' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'convertlab-admin',
			CONVERTLAB_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			CONVERTLAB_VERSION
		);

		wp_enqueue_script(
			'convertlab-admin',
			CONVERTLAB_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			CONVERTLAB_VERSION,
			true
		);

		wp_localize_script(
			'convertlab-admin',
			'convertlabAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'convertlab_admin' ),
			)
		);
	}

	/**
	 * Handle form submission.
	 *
	 * @since 1.0.0
	 */
	public function handle_form_submission() {
		if ( ! isset( $_POST['convertlab_save_popup'] ) || ! check_admin_referer( 'convertlab_save_popup', 'convertlab_popup_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_clb_popups' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'convertlab' ) );
		}

		$popup_id = isset( $_POST['popup_id'] ) ? absint( $_POST['popup_id'] ) : 0;
		$title    = isset( $_POST['popup_title'] ) ? sanitize_text_field( $_POST['popup_title'] ) : '';

		if ( empty( $title ) ) {
			wp_die( esc_html__( 'Popup title is required.', 'convertlab' ) );
		}

		// Build config array
		$config = array(
			'design'   => array(
				'title'           => isset( $_POST['design_title'] ) ? sanitize_text_field( $_POST['design_title'] ) : '',
				'text'            => isset( $_POST['design_text'] ) ? wp_kses_post( $_POST['design_text'] ) : '',
				'image'           => isset( $_POST['design_image'] ) ? absint( $_POST['design_image'] ) : 0,
				'background_color' => isset( $_POST['design_background_color'] ) ? sanitize_hex_color( $_POST['design_background_color'] ) : '#ffffff',
				'button_text'     => isset( $_POST['design_button_text'] ) ? sanitize_text_field( $_POST['design_button_text'] ) : __( 'Submit', 'convertlab' ),
				'button_color'    => isset( $_POST['design_button_color'] ) ? sanitize_hex_color( $_POST['design_button_color'] ) : '#0073aa',
			),
			'fields'   => $this->sanitize_fields( $_POST['fields'] ?? array() ),
			'triggers' => array(
				'page_targeting' => isset( $_POST['trigger_page_targeting'] ) ? sanitize_text_field( $_POST['trigger_page_targeting'] ) : 'all',
				'time_delay'     => isset( $_POST['trigger_time_delay'] ) ? absint( $_POST['trigger_time_delay'] ) : 0,
				'scroll_percent' => isset( $_POST['trigger_scroll_percent'] ) ? absint( $_POST['trigger_scroll_percent'] ) : 0,
				'show_once'      => isset( $_POST['trigger_show_once'] ),
			),
			'thank_you' => array(
				'message'  => isset( $_POST['thank_you_message'] ) ? wp_kses_post( $_POST['thank_you_message'] ) : '',
				'redirect' => isset( $_POST['thank_you_redirect'] ) ? esc_url_raw( $_POST['thank_you_redirect'] ) : '',
			),
		);

		if ( $popup_id ) {
			$post_id = wp_update_post(
				array(
					'ID'         => $popup_id,
					'post_title' => $title,
					'post_type'  => 'clb_popup',
				),
				true
			);
		} else {
			$post_id = wp_insert_post(
				array(
					'post_title'  => $title,
					'post_type'   => 'clb_popup',
					'post_status' => 'publish',
				),
				true
			);
		}

		if ( is_wp_error( $post_id ) ) {
			wp_die( esc_html( $post_id->get_error_message() ) );
		}

		update_post_meta( $post_id, '_clb_popup_config', $config );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => 'convertlab',
					'action'   => 'edit',
					'popup_id' => $post_id,
					'message'  => 'saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Sanitize form fields.
	 *
	 * @param array $fields Fields data.
	 * @return array
	 * @since 1.0.0
	 */
	private function sanitize_fields( $fields ) {
		if ( ! is_array( $fields ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$sanitized[] = array(
				'type'        => sanitize_key( $field['type'] ?? 'email' ),
				'name'        => sanitize_key( $field['name'] ?? '' ),
				'label'       => sanitize_text_field( $field['label'] ?? '' ),
				'required'    => ! empty( $field['required'] ),
				'placeholder' => sanitize_text_field( $field['placeholder'] ?? '' ),
			);
		}

		return $sanitized;
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

		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
		$popup_id = isset( $_GET['popup_id'] ) ? absint( $_GET['popup_id'] ) : 0;

		if ( 'edit' === $action && $popup_id ) {
			$this->render_editor( $popup_id );
		} else {
			$this->render_list();
		}
	}

	/**
	 * Render popups list.
	 *
	 * @since 1.0.0
	 */
	private function render_list() {
		$popups = get_posts(
			array(
				'post_type'      => 'clb_popup',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		$analytics = Analytics::get_instance();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'convertlab', 'action' => 'edit' ), admin_url( 'admin.php' ) ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'convertlab' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php if ( isset( $_GET['message'] ) && 'saved' === $_GET['message'] ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Popup saved successfully.', 'convertlab' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( empty( $popups ) ) : ?>
				<div class="convertlab-empty-state">
					<p><?php esc_html_e( 'No popups yet. Create your first popup to get started!', 'convertlab' ); ?></p>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'convertlab', 'action' => 'edit' ), admin_url( 'admin.php' ) ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Create Your First Popup', 'convertlab' ); ?>
					</a>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Title', 'convertlab' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'convertlab' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Impressions', 'convertlab' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Conversions', 'convertlab' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Conversion Rate', 'convertlab' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Actions', 'convertlab' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $popups as $popup ) : ?>
							<tr>
								<td>
									<strong>
										<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'convertlab', 'action' => 'edit', 'popup_id' => $popup->ID ), admin_url( 'admin.php' ) ) ); ?>">
											<?php echo esc_html( $popup->post_title ); ?>
										</a>
									</strong>
								</td>
								<td>
									<span class="status-<?php echo esc_attr( $popup->post_status ); ?>">
										<?php echo 'publish' === $popup->post_status ? esc_html__( 'Published', 'convertlab' ) : esc_html__( 'Draft', 'convertlab' ); ?>
									</span>
								</td>
								<td><?php echo esc_html( $analytics->get_impressions( $popup->ID ) ); ?></td>
								<td><?php echo esc_html( $analytics->get_conversions( $popup->ID ) ); ?></td>
								<td><?php echo esc_html( $analytics->get_conversion_rate( $popup->ID ) ); ?>%</td>
								<td>
									<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'convertlab', 'action' => 'edit', 'popup_id' => $popup->ID ), admin_url( 'admin.php' ) ) ); ?>" class="button button-small">
										<?php esc_html_e( 'Edit', 'convertlab' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render popup editor.
	 *
	 * @param int $popup_id Popup ID.
	 * @since 1.0.0
	 */
	private function render_editor( $popup_id = 0 ) {
		$popup = null;
		$config = array(
			'design'   => array(
				'title'           => '',
				'text'            => '',
				'image'           => 0,
				'background_color' => '#ffffff',
				'button_text'     => __( 'Submit', 'convertlab' ),
				'button_color'    => '#0073aa',
			),
			'fields'   => array(
				array(
					'type'        => 'email',
					'name'        => 'email',
					'label'       => __( 'Email', 'convertlab' ),
					'required'    => true,
					'placeholder' => __( 'Enter your email', 'convertlab' ),
				),
			),
			'triggers' => array(
				'page_targeting' => 'all',
				'time_delay'     => 0,
				'scroll_percent' => 0,
				'show_once'      => true,
			),
			'thank_you' => array(
				'message'  => __( 'Thank you for subscribing!', 'convertlab' ),
				'redirect' => '',
			),
		);

		if ( $popup_id ) {
			$popup = get_post( $popup_id );
			if ( $popup && 'clb_popup' === $popup->post_type ) {
				$saved_config = get_post_meta( $popup_id, '_clb_popup_config', true );
				if ( $saved_config && is_array( $saved_config ) ) {
					$config = wp_parse_args( $saved_config, $config );
				}
			}
		}
		?>
		<div class="wrap">
			<h1><?php echo $popup_id ? esc_html__( 'Edit Popup', 'convertlab' ) : esc_html__( 'Add New Popup', 'convertlab' ); ?></h1>

			<form method="post" action="" id="convertlab-popup-form">
				<?php wp_nonce_field( 'convertlab_save_popup', 'convertlab_popup_nonce' ); ?>
				<input type="hidden" name="popup_id" value="<?php echo esc_attr( $popup_id ); ?>" />

				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="post-body-content">
							<div class="convertlab-section">
								<label for="popup_title">
									<strong><?php esc_html_e( 'Popup Title', 'convertlab' ); ?></strong>
								</label>
								<input type="text" id="popup_title" name="popup_title" value="<?php echo $popup ? esc_attr( $popup->post_title ) : ''; ?>" class="large-text" required />
								<p class="description"><?php esc_html_e( 'Internal title for managing this popup.', 'convertlab' ); ?></p>
							</div>

							<!-- Design Settings -->
							<div class="convertlab-section">
								<h2><?php esc_html_e( 'Design', 'convertlab' ); ?></h2>
								<table class="form-table">
									<tbody>
										<tr>
											<th><label for="design_title"><?php esc_html_e( 'Title', 'convertlab' ); ?></label></th>
											<td>
												<input type="text" id="design_title" name="design_title" value="<?php echo esc_attr( $config['design']['title'] ); ?>" class="regular-text" />
											</td>
										</tr>
										<tr>
											<th><label for="design_text"><?php esc_html_e( 'Text', 'convertlab' ); ?></label></th>
											<td>
												<?php
												wp_editor(
													$config['design']['text'],
													'design_text',
													array(
														'textarea_name' => 'design_text',
														'textarea_rows' => 5,
														'media_buttons' => false,
													)
												);
												?>
											</td>
										</tr>
										<tr>
											<th><label for="design_background_color"><?php esc_html_e( 'Background Color', 'convertlab' ); ?></label></th>
											<td>
												<input type="color" id="design_background_color" name="design_background_color" value="<?php echo esc_attr( $config['design']['background_color'] ); ?>" />
											</td>
										</tr>
										<tr>
											<th><label for="design_button_text"><?php esc_html_e( 'Button Text', 'convertlab' ); ?></label></th>
											<td>
												<input type="text" id="design_button_text" name="design_button_text" value="<?php echo esc_attr( $config['design']['button_text'] ); ?>" class="regular-text" />
											</td>
										</tr>
										<tr>
											<th><label for="design_button_color"><?php esc_html_e( 'Button Color', 'convertlab' ); ?></label></th>
											<td>
												<input type="color" id="design_button_color" name="design_button_color" value="<?php echo esc_attr( $config['design']['button_color'] ); ?>" />
											</td>
										</tr>
									</tbody>
								</table>
							</div>

							<!-- Form Fields -->
							<div class="convertlab-section">
								<h2><?php esc_html_e( 'Form Fields', 'convertlab' ); ?></h2>
								<div id="convertlab-fields-container">
									<?php foreach ( $config['fields'] as $index => $field ) : ?>
										<div class="convertlab-field-row" data-index="<?php echo esc_attr( $index ); ?>">
											<select name="fields[<?php echo esc_attr( $index ); ?>][type]">
												<option value="email" <?php selected( $field['type'], 'email' ); ?>><?php esc_html_e( 'Email', 'convertlab' ); ?></option>
												<option value="text" <?php selected( $field['type'], 'text' ); ?>><?php esc_html_e( 'Text', 'convertlab' ); ?></option>
												<option value="name" <?php selected( $field['type'], 'name' ); ?>><?php esc_html_e( 'Name', 'convertlab' ); ?></option>
												<option value="phone" <?php selected( $field['type'], 'phone' ); ?>><?php esc_html_e( 'Phone', 'convertlab' ); ?></option>
											</select>
											<input type="text" name="fields[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php esc_attr_e( 'Field name', 'convertlab' ); ?>" />
											<input type="text" name="fields[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $field['label'] ); ?>" placeholder="<?php esc_attr_e( 'Label', 'convertlab' ); ?>" />
											<input type="text" name="fields[<?php echo esc_attr( $index ); ?>][placeholder]" value="<?php echo esc_attr( $field['placeholder'] ); ?>" placeholder="<?php esc_attr_e( 'Placeholder', 'convertlab' ); ?>" />
											<label>
												<input type="checkbox" name="fields[<?php echo esc_attr( $index ); ?>][required]" value="1" <?php checked( $field['required'] ); ?> />
												<?php esc_html_e( 'Required', 'convertlab' ); ?>
											</label>
											<button type="button" class="button button-small convertlab-remove-field"><?php esc_html_e( 'Remove', 'convertlab' ); ?></button>
										</div>
									<?php endforeach; ?>
								</div>
								<button type="button" id="convertlab-add-field" class="button"><?php esc_html_e( 'Add Field', 'convertlab' ); ?></button>
							</div>

							<!-- Triggers -->
							<div class="convertlab-section">
								<h2><?php esc_html_e( 'Display Triggers', 'convertlab' ); ?></h2>
								<table class="form-table">
									<tbody>
										<tr>
											<th><label for="trigger_page_targeting"><?php esc_html_e( 'Page Targeting', 'convertlab' ); ?></label></th>
											<td>
												<select id="trigger_page_targeting" name="trigger_page_targeting">
													<option value="all" <?php selected( $config['triggers']['page_targeting'], 'all' ); ?>><?php esc_html_e( 'All Pages', 'convertlab' ); ?></option>
													<option value="homepage" <?php selected( $config['triggers']['page_targeting'], 'homepage' ); ?>><?php esc_html_e( 'Homepage Only', 'convertlab' ); ?></option>
													<option value="product" <?php selected( $config['triggers']['page_targeting'], 'product' ); ?>><?php esc_html_e( 'Product Pages Only', 'convertlab' ); ?></option>
												</select>
											</td>
										</tr>
										<tr>
											<th><label for="trigger_time_delay"><?php esc_html_e( 'Time Delay (seconds)', 'convertlab' ); ?></label></th>
											<td>
												<input type="number" id="trigger_time_delay" name="trigger_time_delay" value="<?php echo esc_attr( $config['triggers']['time_delay'] ); ?>" min="0" class="small-text" />
											</td>
										</tr>
										<tr>
											<th><label for="trigger_scroll_percent"><?php esc_html_e( 'Scroll Percent', 'convertlab' ); ?></label></th>
											<td>
												<input type="number" id="trigger_scroll_percent" name="trigger_scroll_percent" value="<?php echo esc_attr( $config['triggers']['scroll_percent'] ); ?>" min="0" max="100" class="small-text" />
												<p class="description"><?php esc_html_e( 'Show popup when user scrolls this percentage of the page.', 'convertlab' ); ?></p>
											</td>
										</tr>
										<tr>
											<th><label for="trigger_show_once"><?php esc_html_e( 'Show Once', 'convertlab' ); ?></label></th>
											<td>
												<label>
													<input type="checkbox" id="trigger_show_once" name="trigger_show_once" value="1" <?php checked( $config['triggers']['show_once'] ); ?> />
													<?php esc_html_e( 'Show popup only once per session', 'convertlab' ); ?>
												</label>
											</td>
										</tr>
									</tbody>
								</table>
							</div>

							<!-- Thank You -->
							<div class="convertlab-section">
								<h2><?php esc_html_e( 'Thank You Message', 'convertlab' ); ?></h2>
								<table class="form-table">
									<tbody>
										<tr>
											<th><label for="thank_you_message"><?php esc_html_e( 'Message', 'convertlab' ); ?></label></th>
											<td>
												<?php
												wp_editor(
													$config['thank_you']['message'],
													'thank_you_message',
													array(
														'textarea_name' => 'thank_you_message',
														'textarea_rows' => 3,
														'media_buttons' => false,
													)
												);
												?>
											</td>
										</tr>
										<tr>
											<th><label for="thank_you_redirect"><?php esc_html_e( 'Redirect URL (optional)', 'convertlab' ); ?></label></th>
											<td>
												<input type="url" id="thank_you_redirect" name="thank_you_redirect" value="<?php echo esc_attr( $config['thank_you']['redirect'] ); ?>" class="regular-text" placeholder="https://example.com/thank-you" />
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>

				<p class="submit">
					<?php submit_button( __( 'Save Popup', 'convertlab' ), 'primary', 'convertlab_save_popup', false ); ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'convertlab' ), admin_url( 'admin.php' ) ) ); ?>" class="button">
						<?php esc_html_e( 'Cancel', 'convertlab' ); ?>
					</a>
				</p>
			</form>
		</div>
		<?php
	}
}
