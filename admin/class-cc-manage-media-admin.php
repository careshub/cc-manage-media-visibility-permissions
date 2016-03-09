<?php
/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @package    CC_Manage_Media
 * @subpackage CC_Manage_Media/admin
 * @author     David Cavins
 */

class CC_Manage_Media_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name       The name of this plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the Dashboard.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		// wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/cc-mrad-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the dashboard.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		// wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/cc-mrad-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * In the Media library, restrict what is shown to non-admin users.
	 * We show only the user's media.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query WP_Query query args used in filtering the attachments query.
	 */
	function media_library_filter_query( $wp_query_obj ) {
		global $pagenow;

		// The Media library is identified by the $pagenow param.
		if ( 'upload.php' != $pagenow ) {
		    return;
		}

		if ( ! current_user_can( 'delete_users' ) ) {
		    $wp_query_obj->set('author__in', array( get_current_user_id() ) );
		}

		return;
	}

	/**
	 * In the "Add Media" modal, restrict what is shown to non-admin users.
	 * We show only the user's media.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query WP_Query query args used in filtering the attachments query.
	 */
	public function filter_query_attachment_args( $query ) {
		// If the current user isn't a site admin, default to showing only her media attachments.
		// We use "author__in" so that later plugins that need to filter for a group of users can.
		if ( ! current_user_can( 'delete_users' ) ) {
		    $query['author__in'] = array( get_current_user_id() );
		}

	    return $query;
	}

	/**
	 * Allow SA Curators to manage media authored by any SA Curator.
	 *
	 * @since 1.0.0
	 *
	 * @param array $caps Capabilities for meta capability
	 * @param string $cap Capability name
	 * @param int $user_id User id
	 * @param mixed $args Arguments
	 */
	function filter_map_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {
	    global $pagenow;

		switch ( $cap ) {
			case 'edit_post':
			case 'delete_post':
				// Only act on attachments.
				if ( isset( $args[0] ) && 'attachment' == get_post_type( $args[0] ) ) {
					// If the use is the author, allow him to edit the media.
					// Was the media item created by an sa_curator?
					$author_id = get_post_field( 'post_author', $args[0] );
					if ( $author_id == get_current_user_id() ) {
						$caps = array( 'upload_files' );
					}
				}
				break;
			case 'edit_others_posts':
				/* There's a context-less edit_others_posts check in WP:
				 * Addresses core bug in _wp_translate_postdata()
				 *
				 * @see https://core.trac.wordpress.org/ticket/30452
				 */
				// We have to get the right post object, since no reference to a post is passed.
				$post_obj = false;
				// This problem only seems to affect the media library editor view.
				if ( 'post.php' === $pagenow
					&& ! empty( $_POST['post_ID'] ) ) {
					$post_obj = get_post( (int) $_POST['post_ID'] );
				}
				if ( ! $post_obj || 'attachment' != $post_obj->post_type ) {
					break;
				}
				$author_id = get_post_field( 'post_author', $args[0] );
				if ( $author_id == get_current_user_id() ) {
					$caps = array( 'upload_files' );
				}
				break;
		}

		return $caps;
	}
}
