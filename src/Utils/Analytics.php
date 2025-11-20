<?php
/**
 * Analytics class.
 *
 * Handles impression and conversion tracking.
 *
 * @package ConvertLab
 * @since 1.0.0
 */

namespace ConvertLab\Utils;

/**
 * Analytics class.
 *
 * @since 1.0.0
 */
class Analytics {

	/**
	 * Instance.
	 *
	 * @var Analytics
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return Analytics
	 * @since 1.0.0
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Record impression.
	 *
	 * @param int $popup_id Popup ID.
	 * @return bool
	 * @since 1.0.0
	 */
	public function record_impression( $popup_id ) {
		if ( ! $popup_id ) {
			return false;
		}

		$current = get_post_meta( $popup_id, '_clb_impressions', true );
		$current = absint( $current );
		$current++;

		return update_post_meta( $popup_id, '_clb_impressions', $current );
	}

	/**
	 * Record conversion.
	 *
	 * @param int $popup_id Popup ID.
	 * @return bool
	 * @since 1.0.0
	 */
	public function record_conversion( $popup_id ) {
		if ( ! $popup_id ) {
			return false;
		}

		$current = get_post_meta( $popup_id, '_clb_conversions', true );
		$current = absint( $current );
		$current++;

		return update_post_meta( $popup_id, '_clb_conversions', $current );
	}

	/**
	 * Get impressions count.
	 *
	 * @param int $popup_id Popup ID.
	 * @return int
	 * @since 1.0.0
	 */
	public function get_impressions( $popup_id ) {
		return absint( get_post_meta( $popup_id, '_clb_impressions', true ) );
	}

	/**
	 * Get conversions count.
	 *
	 * @param int $popup_id Popup ID.
	 * @return int
	 * @since 1.0.0
	 */
	public function get_conversions( $popup_id ) {
		return absint( get_post_meta( $popup_id, '_clb_conversions', true ) );
	}

	/**
	 * Get conversion rate.
	 *
	 * @param int $popup_id Popup ID.
	 * @return float
	 * @since 1.0.0
	 */
	public function get_conversion_rate( $popup_id ) {
		$impressions = $this->get_impressions( $popup_id );
		$conversions = $this->get_conversions( $popup_id );

		if ( 0 === $impressions ) {
			return 0.0;
		}

		return round( ( $conversions / $impressions ) * 100, 2 );
	}
}

