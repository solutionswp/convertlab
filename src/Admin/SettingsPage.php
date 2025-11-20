<?php
/**
 * Settings page class.
 *
 * Handles plugin settings page.
 *
 * @package ConvertLab
 * @since 1.0.0
 */

namespace ConvertLab\Admin;

/**
 * SettingsPage class.
 *
 * @since 1.0.0
 */
class SettingsPage {

	/**
	 * Instance.
	 *
	 * @var SettingsPage
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return SettingsPage
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
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting(
			'convertlab_settings',
			'convertlab_update_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => 'https://raw.githubusercontent.com/solutionswp/convertlab/main/update.json',
			)
		);

		register_setting(
			'convertlab_settings',
			'convertlab_webhook_enabled',
			array(
				'type'    => 'boolean',
				'default' => false,
			)
		);

		register_setting(
			'convertlab_settings',
			'convertlab_webhook_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);

		register_setting(
			'convertlab_settings',
			'convertlab_data_retention_days',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 365,
			)
		);

		register_setting(
			'convertlab_settings',
			'convertlab_delete_tables_on_uninstall',
			array(
				'type'    => 'boolean',
				'default' => false,
			)
		);
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.0.0
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle form submission
		if ( isset( $_POST['convertlab_settings_submit'] ) && check_admin_referer( 'convertlab_settings', 'convertlab_settings_nonce' ) ) {
			update_option( 'convertlab_update_url', esc_url_raw( $_POST['convertlab_update_url'] ?? '' ) );
			update_option( 'convertlab_webhook_enabled', isset( $_POST['convertlab_webhook_enabled'] ) );
			update_option( 'convertlab_webhook_url', esc_url_raw( $_POST['convertlab_webhook_url'] ?? '' ) );
			update_option( 'convertlab_data_retention_days', absint( $_POST['convertlab_data_retention_days'] ?? 365 ) );
			update_option( 'convertlab_delete_tables_on_uninstall', isset( $_POST['convertlab_delete_tables_on_uninstall'] ) );

			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'convertlab' ) . '</p></div>';
		}

		$update_url              = get_option( 'convertlab_update_url', '' );
		$webhook_enabled         = get_option( 'convertlab_webhook_enabled', false );
		$webhook_url             = get_option( 'convertlab_webhook_url', '' );
		$data_retention_days     = get_option( 'convertlab_data_retention_days', 365 );
		$delete_tables_on_uninstall = get_option( 'convertlab_delete_tables_on_uninstall', false );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="post" action="">
				<?php wp_nonce_field( 'convertlab_settings', 'convertlab_settings_nonce' ); ?>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="convertlab_update_url"><?php esc_html_e( 'Update.json URL', 'convertlab' ); ?></label>
							</th>
							<td>
								<input 
									type="url" 
									id="convertlab_update_url" 
									name="convertlab_update_url" 
									value="<?php echo esc_attr( $update_url ); ?>" 
									class="regular-text"
									placeholder="https://raw.githubusercontent.com/solutionswp/convertlab/main/update.json"
								/>
								<p class="description">
									<?php esc_html_e( 'URL to your update.json file for automatic plugin updates.', 'convertlab' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="convertlab_webhook_enabled"><?php esc_html_e( 'Enable Webhook', 'convertlab' ); ?></label>
							</th>
							<td>
								<label>
									<input 
										type="checkbox" 
										id="convertlab_webhook_enabled" 
										name="convertlab_webhook_enabled" 
										value="1" 
										<?php checked( $webhook_enabled, true ); ?>
									/>
									<?php esc_html_e( 'Send leads to webhook URL', 'convertlab' ); ?>
								</label>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="convertlab_webhook_url"><?php esc_html_e( 'Webhook URL', 'convertlab' ); ?></label>
							</th>
							<td>
								<input 
									type="url" 
									id="convertlab_webhook_url" 
									name="convertlab_webhook_url" 
									value="<?php echo esc_attr( $webhook_url ); ?>" 
									class="regular-text"
									<?php echo $webhook_enabled ? '' : 'disabled'; ?>
								/>
								<p class="description">
									<?php esc_html_e( 'URL to send lead data when a new lead is captured.', 'convertlab' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="convertlab_data_retention_days"><?php esc_html_e( 'Data Retention (Days)', 'convertlab' ); ?></label>
							</th>
							<td>
								<input 
									type="number" 
									id="convertlab_data_retention_days" 
									name="convertlab_data_retention_days" 
									value="<?php echo esc_attr( $data_retention_days ); ?>" 
									min="1"
									class="small-text"
								/>
								<p class="description">
									<?php esc_html_e( 'Number of days to retain lead data. (Future feature)', 'convertlab' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="convertlab_delete_tables_on_uninstall"><?php esc_html_e( 'Delete Tables on Uninstall', 'convertlab' ); ?></label>
							</th>
							<td>
								<label>
									<input 
										type="checkbox" 
										id="convertlab_delete_tables_on_uninstall" 
										name="convertlab_delete_tables_on_uninstall" 
										value="1" 
										<?php checked( $delete_tables_on_uninstall, true ); ?>
									/>
									<?php esc_html_e( 'Delete database tables when plugin is uninstalled', 'convertlab' ); ?>
								</label>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button( __( 'Save Settings', 'convertlab' ), 'primary', 'convertlab_settings_submit' ); ?>
			</form>
		</div>
		<?php
	}
}

