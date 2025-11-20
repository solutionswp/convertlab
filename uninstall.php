<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package ConvertLab
 * @since 1.0.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

/**
 * Delete plugin options.
 *
 * @since 1.0.0
 */
$options = array(
	'convertlab_db_version',
	'convertlab_update_url',
	'convertlab_webhook_enabled',
	'convertlab_webhook_url',
	'convertlab_data_retention_days',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

/**
 * Delete plugin transients.
 *
 * @since 1.0.0
 */
delete_transient( 'convertlab_update_check' );
delete_transient( 'convertlab_update_data' );

/**
 * Delete custom post type posts.
 *
 * @since 1.0.0
 */
$popup_ids = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
		'clb_popup'
	)
);

foreach ( $popup_ids as $popup_id ) {
	wp_delete_post( $popup_id, true );
}

/**
 * Delete database tables (optional - can be toggled via setting).
 *
 * @since 1.0.0
 */
$delete_tables = get_option( 'convertlab_delete_tables_on_uninstall', false );

if ( $delete_tables ) {
	$table_name = $wpdb->prefix . 'convertlab_leads';
	$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

/**
 * Clean up postmeta.
 *
 * @since 1.0.0
 */
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
		$wpdb->esc_like( '_clb_' ) . '%'
	)
); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

