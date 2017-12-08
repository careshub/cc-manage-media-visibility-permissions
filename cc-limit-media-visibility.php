<?php

/**
 * @link              http://example.com
 * @since             1.0.0
 * @package           CC_Limit_Media_Visibility
 *
 * @wordpress-plugin
 * Plugin Name:       CC Limit Media Visibility
 * Plugin URI:        http://example.com/plugin-name-uri/
 * Description:       Limit media items visible to non-admin users within a site's media admin interface and upload media modals.
 * Version:           1.0.0
 * Author:            David Cavins
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cc-limit-media-visibility
 * Domain Path:       /languages
 */

namespace CC_Limit_Media_Visibility;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

// In the Media library, restrict what is shown to non-admin users.
add_action( 'pre_get_posts', __NAMESPACE__ . '\\media_library_filter_query', 10 );
// In the "Add Media" modal, restrict what is shown to non-admin users.
add_filter( 'ajax_query_attachments_args', __NAMESPACE__ . '\\filter_query_attachment_args', 10 );

// When in the media/post editor, allow a user to edit his own media.
add_filter( 'map_meta_cap', __NAMESPACE__ . '\\filter_map_meta_caps', 11, 4 );

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
function filter_query_attachment_args( $query ) {
	// If the current user isn't a site admin, default to showing only her media attachments.
	// We use "author__in" so that later plugins that need to filter for a group of users can.
	if ( ! current_user_can( 'delete_users' ) ) {
	    $query['author__in'] = array( get_current_user_id() );
	}

    return $query;
}

/**
 * Allow admins to manage any media. Other users can only manage their own.
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
