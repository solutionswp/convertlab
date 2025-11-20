<?php
/**
 * Plugin updater class.
 *
 * Handles GitHub-based plugin updates.
 *
 * @package ConvertLab
 * @since 1.0.0
 */

namespace ConvertLab\Utils;

/**
 * Updater class.
 *
 * @since 1.0.0
 */
class Updater {

	/**
	 * Instance.
	 *
	 * @var Updater
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return Updater
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
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_updates' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );
	}

	/**
	 * Check for plugin updates.
	 *
	 * @param object $transient Update transient.
	 * @return object
	 * @since 1.0.0
	 */
	public function check_for_updates( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$update_url = get_option( 'convertlab_update_url', '' );

		if ( empty( $update_url ) ) {
			return $transient;
		}

		// Check cache
		$cache_key = 'convertlab_update_check';
		$update_data = get_transient( $cache_key );

		if ( false === $update_data ) {
			$update_data = $this->fetch_update_data( $update_url );

			if ( $update_data ) {
				set_transient( $cache_key, $update_data, 12 * HOUR_IN_SECONDS );
			}
		}

		if ( $update_data && version_compare( CONVERTLAB_VERSION, $update_data['version'], '<' ) ) {
			$plugin_file = CONVERTLAB_PLUGIN_BASENAME;

			$transient->response[ $plugin_file ] = (object) array(
				'slug'        => 'convertlab',
				'plugin'      => $plugin_file,
				'new_version' => $update_data['version'],
				'url'         => $update_data['homepage'] ?? '',
				'package'     => $update_data['download_url'],
			);
		}

		return $transient;
	}

	/**
	 * Fetch update data from update.json.
	 *
	 * @param string $update_url Update.json URL.
	 * @return array|false
	 * @since 1.0.0
	 */
	private function fetch_update_data( $update_url ) {
		$response = wp_remote_get(
			$update_url,
			array(
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! $data || ! isset( $data['version'] ) || ! isset( $data['download_url'] ) ) {
			return false;
		}

		return $data;
	}

	/**
	 * Plugin information for update screen.
	 *
	 * @param false|object|array $result Result object or array.
	 * @param string            $action The type of information being requested from the Plugin Installation API.
	 * @param object            $args Plugin API arguments.
	 * @return false|object
	 * @since 1.0.0
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( 'convertlab' !== $args->slug ) {
			return $result;
		}

		$update_url = get_option( 'convertlab_update_url', '' );

		if ( empty( $update_url ) ) {
			return $result;
		}

		$update_data = $this->fetch_update_data( $update_url );

		if ( ! $update_data ) {
			return $result;
		}

		$result = (object) array(
			'name'          => 'ConvertLab',
			'slug'          => 'convertlab',
			'version'       => $update_data['version'],
			'author'        => $update_data['author'] ?? '',
			'author_profile' => $update_data['author_url'] ?? '',
			'homepage'      => $update_data['homepage'] ?? '',
			'download_link' => $update_data['download_url'],
			'sections'      => array(
				'description' => $update_data['description'] ?? '',
				'changelog'   => $update_data['changelog'] ?? '',
			),
		);

		return $result;
	}

	/**
	 * Post install hook.
	 *
	 * @param bool  $response Installation response.
	 * @param array $hook_extra Extra arguments.
	 * @param array $result Installation result data.
	 * @return bool
	 * @since 1.0.0
	 */
	public function post_install( $response, $hook_extra, $result ) {
		if ( ! isset( $hook_extra['plugin'] ) || CONVERTLAB_PLUGIN_BASENAME !== $hook_extra['plugin'] ) {
			return $response;
		}

		// Clear update cache
		delete_transient( 'convertlab_update_check' );

		return $response;
	}
}

