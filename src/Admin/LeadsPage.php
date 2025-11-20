<?php
/**
 * Leads page class.
 *
 * Displays leads list and export functionality.
 *
 * @package ConvertLab
 * @since 1.0.0
 */

namespace ConvertLab\Admin;

use ConvertLab\Utils\LeadModel;

/**
 * LeadsPage class.
 *
 * @since 1.0.0
 */
class LeadsPage {

	/**
	 * Instance.
	 *
	 * @var LeadsPage
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Lead model instance.
	 *
	 * @var LeadModel
	 * @since 1.0.0
	 */
	private $lead_model;

	/**
	 * Get instance.
	 *
	 * @return LeadsPage
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
		$this->lead_model = new LeadModel();

		// Handle CSV export
		add_action( 'admin_init', array( $this, 'handle_export' ) );
	}

	/**
	 * Handle CSV export.
	 *
	 * @since 1.0.0
	 */
	public function handle_export() {
		if ( ! isset( $_GET['convertlab_export'] ) || ! check_admin_referer( 'convertlab_export' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$args = array(
			'popup_id' => isset( $_GET['popup_id'] ) ? absint( $_GET['popup_id'] ) : 0,
			'synced'   => isset( $_GET['synced'] ) ? (bool) $_GET['synced'] : null,
			'search'   => isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '',
		);

		$csv = $this->lead_model->export_leads( $args );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=convertlab-leads-' . gmdate( 'Y-m-d' ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		echo "\xEF\xBB\xBF"; // UTF-8 BOM
		echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Render leads page.
	 *
	 * @since 1.0.0
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$per_page     = 20;
		$popup_id     = isset( $_GET['popup_id'] ) ? absint( $_GET['popup_id'] ) : 0;
		$synced       = isset( $_GET['synced'] ) ? sanitize_text_field( $_GET['synced'] ) : null;
		$search       = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

		$args = array(
			'page'     => $current_page,
			'per_page' => $per_page,
			'popup_id' => $popup_id,
			'synced'   => null !== $synced ? ( 'yes' === $synced ) : null,
			'search'   => $search,
		);

		$leads      = $this->lead_model->get_leads( $args );
		$total_leads = $this->lead_model->get_leads_count( $args );
		$total_pages = ceil( $total_leads / $per_page );

		// Get all popups for filter
		$popups = get_posts(
			array(
				'post_type'      => 'clb_popup',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		$export_url = wp_nonce_url(
			add_query_arg(
				array(
					'convertlab_export' => '1',
					'popup_id'          => $popup_id,
					'synced'            => $synced,
					's'                 => $search,
				),
				admin_url( 'admin.php?page=convertlab-leads' )
			),
			'convertlab_export'
		);
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="convertlab-leads-filters" style="margin: 20px 0;">
				<form method="get" action="">
					<input type="hidden" name="page" value="convertlab-leads" />

					<select name="popup_id">
						<option value="0"><?php esc_html_e( 'All Popups', 'convertlab' ); ?></option>
						<?php foreach ( $popups as $popup ) : ?>
							<option value="<?php echo esc_attr( $popup->ID ); ?>" <?php selected( $popup_id, $popup->ID ); ?>>
								<?php echo esc_html( $popup->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<select name="synced">
						<option value=""><?php esc_html_e( 'All Leads', 'convertlab' ); ?></option>
						<option value="yes" <?php selected( $synced, 'yes' ); ?>><?php esc_html_e( 'Synced', 'convertlab' ); ?></option>
						<option value="no" <?php selected( $synced, 'no' ); ?>><?php esc_html_e( 'Not Synced', 'convertlab' ); ?></option>
					</select>

					<input type="text" name="s" placeholder="<?php esc_attr_e( 'Search by email or name...', 'convertlab' ); ?>" value="<?php echo esc_attr( $search ); ?>" />

					<?php submit_button( __( 'Filter', 'convertlab' ), 'secondary', '', false ); ?>
				</form>

				<a href="<?php echo esc_url( $export_url ); ?>" class="button" style="margin-left: 10px;">
					<?php esc_html_e( 'Export CSV', 'convertlab' ); ?>
				</a>
			</div>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'ID', 'convertlab' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Popup', 'convertlab' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Email', 'convertlab' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Name', 'convertlab' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Phone', 'convertlab' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Synced', 'convertlab' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Created', 'convertlab' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $leads ) ) : ?>
						<tr>
							<td colspan="7"><?php esc_html_e( 'No leads found.', 'convertlab' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $leads as $lead ) : ?>
							<tr>
								<td><?php echo esc_html( $lead['id'] ); ?></td>
								<td><?php echo esc_html( $lead['popup_title'] ?? __( 'N/A', 'convertlab' ) ); ?></td>
								<td><?php echo esc_html( $lead['email'] ); ?></td>
								<td><?php echo esc_html( $lead['name'] ?? '-' ); ?></td>
								<td><?php echo esc_html( $lead['phone'] ?? '-' ); ?></td>
								<td>
									<?php if ( $lead['synced'] ) : ?>
										<span class="dashicons dashicons-yes-alt" style="color: green;"></span>
									<?php else : ?>
										<span class="dashicons dashicons-dismiss" style="color: red;"></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $lead['created_at'] ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav">
					<div class="tablenav-pages">
						<?php
						echo paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'prev_text' => __( '&laquo;', 'convertlab' ),
								'next_text' => __( '&raquo;', 'convertlab' ),
								'total'     => $total_pages,
								'current'   => $current_page,
							)
						);
						?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}

