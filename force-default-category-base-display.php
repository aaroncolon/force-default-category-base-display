<?php
/**
 * Plugin Name: Force Default Category Base Display
 * Description: Forces URL to display default category base when using /%category%/%postname%/ custom permalink structure.
 * Version:     1.0.0
 * Author:      Aaron Colón
 * Author URI:  https://aaron-colon.com/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH') ) {
	exit;
}

/**
 * Prepend default Category base name to Category URLs.
 * 
 * When using custom Permalink structure `/%category%/%pagename%/`
 * in combination with the default Category Base, WordPress
 * will load the Category archive page when the Category slug
 * is entered in the nav bar (http://foo.com/bar/ loads the `bar`
 * Category Archive page). Pagination is broken on these bare
 * Category Archive pages (http://foo.com/bar/page/2/ returns a 404).
 * 
 * @since 1.0.0
 * 
 * @global wp         $wp         Wordpress environment object.
 * @global wp_rewrite $wp_rewrite WordPress rewrite object.
 * 
 * @return void
 */
function fdcbd_prepend_default_category_base() {
	if ( is_admin() || is_feed() ) return;

	global $wp, $wp_rewrite;

	/**
	 * Fail early. 
	 * 
	 * Only target default category base && permalink structure `/%category%/%postname%/`.
	 * 
	 * Using custom fdcbd_is_category() because WP determines paginated 
	 * bare category queries are 404s before the `template_redirect` hook.
	 */
	if ( 
		! fdcbd_is_category( $wp ) || 
		! $wp_rewrite->using_permalinks() || 
		( $wp_rewrite->get_category_permastruct() !== '/category/%category%' && get_option( 'permalink_structure' ) !== '/%category%/%postname%/' ) 
	   ) { return; }

	// Return if path already contains default category base `category` at index 0.
	if ( strpos( $wp->request, 'category' ) === 0 ) {
		return;
	}
	
	// Get the current path.
	$current_path = add_query_arg( null, null ); // Falls back to $_SERVER['REQUEST_URI']

	// Absolute URI with multisite support.
	$parts = parse_url( home_url() );

	// Prepend `/category` to $current_path.
	$new_url = "{$parts['scheme']}://{$parts['host']}" . "/category{$current_path}";

	wp_redirect( $new_url, 301 );

	exit; // always exit after wp_redirect()
};
add_action( 'template_redirect', 'fdcbd_prepend_default_category_base' );

/**
 * Determines if request is a category using the global $wp object.
 * 
 * $wp_query->is_category() returns false on paginated bare category queries.
 * 
 * @since 1.0.0
 * 
 * @param WP $wp Global WordPress environment object instance.
 * 
 * @return bool True if matched rule is a category match.
 */
function fdcbd_is_category( $wp ) {
	// All URIs starting with category base
	if ( strpos( $wp->matched_rule, 'category' ) === 0 ) {
		return true;
	}

	// Bare top-level category URIs
	if ( $wp->matched_rule === '(.+?)/?$' && ! empty( $wp->query_vars['category_name'] ) ) {
		return true;
	}

	// Bare top-level category URIs with pagination
	if ( $wp->matched_rule === '(.+?)/([^/]+)(?:/([0-9]+))?/?$' && ! empty( $wp->query_vars['category_name'] ) && ! empty( $wp->query_vars['page'] ) ) {
		return true;
	}

	// // Bare nested category URIs
	// if ( $wp->matched_rule === '(.+?)/([^/]+)(?:/([0-9]+))?/?$' && ! empty( $wp->query_vars['category_name'] ) && empty( $wp->query_vars['page'] ) ) {
	// 	return true;
	// }

	// // Bare nested category URIs with pagination
	// if ( $wp->matched_rule === '(.+?)/([^/]+)/page/?([0-9]{1,})/?$' && ! empty( $wp->query_vars['category_name'] ) && ! empty( $wp->query_vars['paged'] ) ) {
	// 	return true;
	// }

	return false;
}
