<?php
/**
 * Lead model class.
 *
 * Handles lead data operations.
 *
 * @package ConvertLab
 * @since 1.0.0
 */

namespace ConvertLab\Utils;

/**
 * LeadModel class.
 *
 * @since 1.0.0
 */
class LeadModel {

	/**
	 * Insert a new lead.
	 *
	 * @param array $data Lead data.
	 * @return int|false Lead ID on success, false on failure.
	 * @since 1.0.0
	 */
	public function insert_lead( $data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'convertlab_leads';

		// Sanitize data
		$lead_data = array(
			'popup_id'  => absint( $data['popup_id'] ?? 0 ),
			'email'     => $this->sanitize_email( $data['email'] ?? '' ),
			'name'      => $this->sanitize_text( $data['name'] ?? '' ),
			'phone'     => $this->sanitize_text( $data['phone'] ?? '' ),
			'form_data' => wp_json_encode( $data['form_data'] ?? array() ),
			'synced'    => 0,
		);

		// Validate required fields
		if ( empty( $lead_data['email'] ) || ! is_email( $lead_data['email'] ) ) {
			return false;
		}

		if ( empty( $lead_data['popup_id'] ) ) {
			return false;
		}

		$result = $wpdb->insert( $table_name, $lead_data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( $result ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Get leads with pagination.
	 *
	 * @param array $args Query arguments.
	 * @return array Leads array.
	 * @since 1.0.0
	 */
	public function get_leads( $args = array() ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'convertlab_leads';

		$defaults = array(
			'per_page' => 20,
			'page'     => 1,
			'popup_id' => 0,
			'synced'   => null,
			'search'   => '',
			'orderby'  => 'created_at',
			'order'    => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$join  = '';

		if ( ! empty( $args['popup_id'] ) ) {
			$where[] = $wpdb->prepare( 'l.popup_id = %d', $args['popup_id'] );
		}

		if ( null !== $args['synced'] ) {
			$where[] = $wpdb->prepare( 'l.synced = %d', $args['synced'] ? 1 : 0 );
		}

		if ( ! empty( $args['search'] ) ) {
			$search  = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = $wpdb->prepare( '(l.email LIKE %s OR l.name LIKE %s)', $search, $search );
		}

		$where_clause = implode( ' AND ', $where );

		$orderby = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );
		if ( ! $orderby ) {
			$orderby = 'created_at DESC';
		}

		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		$query = "SELECT l.*, p.post_title as popup_title 
			FROM {$table_name} l 
			LEFT JOIN {$wpdb->posts} p ON l.popup_id = p.ID 
			WHERE {$where_clause} 
			ORDER BY l.{$orderby} 
			LIMIT %d OFFSET %d";

		$leads = $wpdb->get_results(
			$wpdb->prepare(
				$query,
				$args['per_page'],
				$offset
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $leads ? $leads : array();
	}

	/**
	 * Get total leads count.
	 *
	 * @param array $args Query arguments.
	 * @return int Total count.
	 * @since 1.0.0
	 */
	public function get_leads_count( $args = array() ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'convertlab_leads';

		$where = array( '1=1' );

		if ( ! empty( $args['popup_id'] ) ) {
			$where[] = $wpdb->prepare( 'popup_id = %d', $args['popup_id'] );
		}

		if ( null !== $args['synced'] ) {
			$where[] = $wpdb->prepare( 'synced = %d', $args['synced'] ? 1 : 0 );
		}

		if ( ! empty( $args['search'] ) ) {
			$search  = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = $wpdb->prepare( '(email LIKE %s OR name LIKE %s)', $search, $search );
		}

		$where_clause = implode( ' AND ', $where );

		$count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}"
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return absint( $count );
	}

	/**
	 * Export leads to CSV.
	 *
	 * @param array $args Query arguments.
	 * @return string CSV content.
	 * @since 1.0.0
	 */
	public function export_leads( $args = array() ) {
		$leads = $this->get_leads(
			array_merge(
				$args,
				array(
					'per_page' => -1,
				)
			)
		);

		$csv_lines = array();

		// Header row
		$csv_lines[] = array(
			__( 'ID', 'convertlab' ),
			__( 'Popup', 'convertlab' ),
			__( 'Email', 'convertlab' ),
			__( 'Name', 'convertlab' ),
			__( 'Phone', 'convertlab' ),
			__( 'Form Data', 'convertlab' ),
			__( 'Synced', 'convertlab' ),
			__( 'Created At', 'convertlab' ),
		);

		// Data rows
		foreach ( $leads as $lead ) {
			$form_data = json_decode( $lead['form_data'], true );
			$form_data = is_array( $form_data ) ? wp_json_encode( $form_data ) : '';

			$csv_lines[] = array(
				$lead['id'],
				$lead['popup_title'] ?? '',
				$lead['email'],
				$lead['name'] ?? '',
				$lead['phone'] ?? '',
				$form_data,
				$lead['synced'] ? __( 'Yes', 'convertlab' ) : __( 'No', 'convertlab' ),
				$lead['created_at'],
			);
		}

		// Generate CSV
		$output = fopen( 'php://temp', 'r+' );
		foreach ( $csv_lines as $line ) {
			fputcsv( $output, $line );
		}
		rewind( $output );
		$csv = stream_get_contents( $output );
		fclose( $output );

		return $csv;
	}

	/**
	 * Mark lead as synced.
	 *
	 * @param int $lead_id Lead ID.
	 * @return bool
	 * @since 1.0.0
	 */
	public function mark_synced( $lead_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'convertlab_leads';

		return (bool) $wpdb->update(
			$table_name,
			array( 'synced' => 1 ),
			array( 'id' => absint( $lead_id ) ),
			array( '%d' ),
			array( '%d' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * Sanitize email.
	 *
	 * @param string $email Email address.
	 * @return string Sanitized email.
	 * @since 1.0.0
	 */
	private function sanitize_email( $email ) {
		return sanitize_email( $email );
	}

	/**
	 * Sanitize text.
	 *
	 * @param string $text Text to sanitize.
	 * @return string Sanitized text.
	 * @since 1.0.0
	 */
	private function sanitize_text( $text ) {
		return sanitize_text_field( $text );
	}
}

