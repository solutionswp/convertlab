<?php
/**
 * Database installer class.
 *
 * Handles database table creation and updates.
 *
 * @package ConvertLab
 * @since 1.0.0
 */

namespace ConvertLab\Utils;

/**
 * DBInstaller class.
 *
 * @since 1.0.0
 */
class DBInstaller {

	/**
	 * Install database tables.
	 *
	 * @since 1.0.0
	 */
	public function install() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'convertlab_leads';

		$sql = "CREATE TABLE $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			popup_id bigint(20) UNSIGNED NOT NULL,
			email varchar(255) NOT NULL,
			name varchar(255) DEFAULT NULL,
			phone varchar(50) DEFAULT NULL,
			form_data longtext DEFAULT NULL,
			synced tinyint(1) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY popup_id (popup_id),
			KEY email (email),
			KEY synced (synced),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Update database version
		update_option( 'convertlab_db_version', CONVERTLAB_DB_VERSION );
	}

	/**
	 * Check if database needs update.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function needs_update() {
		$installed_version = get_option( 'convertlab_db_version', '0.0.0' );
		return version_compare( $installed_version, CONVERTLAB_DB_VERSION, '<' );
	}

	/**
	 * Update database if needed.
	 *
	 * @since 1.0.0
	 */
	public function maybe_update() {
		if ( $this->needs_update() ) {
			$this->install();
		}
	}
}

