<?php
/**
 * Custom Post Type: Popup.
 *
 * Registers the clb_popup custom post type.
 *
 * @package ConvertLab
 * @since 1.0.0
 */

namespace ConvertLab\Admin;

/**
 * CPTPopup class.
 *
 * @since 1.0.0
 */
class CPTPopup {

	/**
	 * Instance.
	 *
	 * @var CPTPopup
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return CPTPopup
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
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	/**
	 * Register custom post type.
	 *
	 * @since 1.0.0
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => _x( 'Popups', 'post type general name', 'convertlab' ),
			'singular_name'      => _x( 'Popup', 'post type singular name', 'convertlab' ),
			'menu_name'          => _x( 'Popups', 'admin menu', 'convertlab' ),
			'name_admin_bar'     => _x( 'Popup', 'add new on admin bar', 'convertlab' ),
			'add_new'            => _x( 'Add New', 'popup', 'convertlab' ),
			'add_new_item'       => __( 'Add New Popup', 'convertlab' ),
			'new_item'           => __( 'New Popup', 'convertlab' ),
			'edit_item'          => __( 'Edit Popup', 'convertlab' ),
			'view_item'          => __( 'View Popup', 'convertlab' ),
			'all_items'          => __( 'All Popups', 'convertlab' ),
			'search_items'       => __( 'Search Popups', 'convertlab' ),
			'parent_item_colon'  => __( 'Parent Popups:', 'convertlab' ),
			'not_found'          => __( 'No popups found.', 'convertlab' ),
			'not_found_in_trash' => __( 'No popups found in Trash.', 'convertlab' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false, // We'll add it to our custom menu
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'clb_popup',
			'capabilities'       => array(
				'edit_post'          => 'edit_clb_popup',
				'read_post'          => 'read_clb_popup',
				'delete_post'        => 'delete_clb_popup',
				'edit_posts'         => 'edit_clb_popups',
				'edit_others_posts'  => 'edit_others_clb_popups',
				'publish_posts'      => 'publish_clb_popups',
				'read_private_posts' => 'read_private_clb_popups',
			),
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' ),
			'show_in_rest'       => false,
		);

		register_post_type( 'clb_popup', $args );
	}
}

