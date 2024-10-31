<?php
/**
 * Plugin Name: 	  RSS Only Posts
 * Tags: 			  feed, rss
 * Description:  	  Display posts only in the feed.
 * Version: 		  0.2
 * License: 		  GPLv2
 * Requires at least: 5.8
 * Tested up to: 	  5.9
 * Requires PHP: 	  7.4
 * Author: 		      Lutz Schröer
 */


add_action( 'init', 'rsspostonly_register_meta' );

 if (is_admin()) {
	add_action( 'admin_print_styles-edit.php', function() {
		wp_enqueue_style( 'rssonly', plugins_url( 'rssonlypost.css', __FILE__ ) );
	} );	
    register_deactivation_hook( __FILE__, 'rssonlyposts_deactivate' );
 	register_activation_hook( __FILE__, 'rssonlyposts_activate' );
 	register_uninstall_hook( __FILE__, 'rssonlyposts_uninstall' );
 } else {
	add_action( 'pre_get_posts', 'rssonlypostquery' );
}


// -----------------------------------------------------------------------------------
// Delete all RSS Only Posts entries from post meta table
function rssonlyposts_uninstall() {
	global $wpdb;
	$sql = "DELETE FROM $wpdb->postmeta where meta_key = '_rssonlypost';";
	$wpdb->query($sql);
}
// -----------------------------------------------------------------------------------
// Re-publishapt all RSS Only Posts on plugin activation
function rssonlyposts_activate() {
	global $wpdb;
	$sql = "SELECT post_id FROM $wpdb->postmeta where meta_key = '_rssonlypost';";
	$results = $wpdb->get_results($sql);

	if (sizeof($results) > 0) {

		foreach ($results as $result) {
			$ids = $result->post_id . ',';
		}
		$ids = rtrim($ids, ',');

		$sql = "UPDATE $wpdb->posts SET post_status='publish' WHERE ID in ($ids);";
		$wpdb->query($sql);
	}
}

// -----------------------------------------------------------------------------------

function rsspostonly_register_meta() {
	register_post_meta(
		'post',
		'_rssonlypost',
		[
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'boolean',
			'auth_callback' => function() {
				return current_user_can( 'edit_posts' );
			}
		]
	);
	add_action( 'enqueue_block_editor_assets', function() {
		wp_enqueue_script(
			'my-data',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'rssonlypost.min.js',
			[ 'wp-element', 'wp-blocks', 'wp-components', 'wp-editor' ],
			'0.1.0',
			true
			);
	} );
}

// -----------------------------------------------------------------------------------

function rssonlyposts_deactivate() {
	global $wpdb;

	// remove ROP action so all posts incl. rop posts are shown
	remove_action( 'pre_get_posts', 'rssonlypostquery' );


	$args = array(
		'meta_key' => '_rssonlypost',
		'meta_value' => '1',
	);
	$rssonly_posts = get_posts( $args );
	

	$ids = ''; // IDs of the posts to update
	if( ! empty( $rssonly_posts ) ){
		foreach ( $rssonly_posts as $post ){
			$ids .= $post->ID . ',';
		}
	}
	$ids = rtrim($ids, ',');

	$sql = "UPDATE $wpdb->posts SET post_status='draft' WHERE ID in ($ids);";
	$wpdb->query($sql);
}

// -----------------------------------------------------------------------------------
// https://www.billerickson.net/customize-the-wordpress-query/
function rssonlypostquery($query) {

	if (is_admin()) {
        return;
    }

	$metaQuery = (array)$query->get('meta_query');

	if( $query->is_main_query()) {
		$metaQuery[] = array(
			'relation' => 'OR',
			array(
				'key' => '_rssonlypost',
				'value' => '1',
				'compare' => '!='
			),
			array(
				'key' => '_rssonlypost',
				'compare' => 'NOT EXISTS'
			),
		);

		$query->set('meta_query', $metaQuery);

	}

}

	
// -----------------------------------------------------------------------------------
add_filter( 'manage_posts_columns', function($columns) {
	return array_merge(
		array_slice( $columns, 0, 6, true ),     // The first 3 items from the old array
		array( 'rssonly' => 'RSS&nbsp;Only' ),   // New value to add after the 3rd item
		array_slice( $columns, 6, null, true )   // Other items after the 3rd
	);
});
// -----------------------------------------------------------------------------------
add_action('manage_post_posts_custom_column', function($column_key, $post_id) {
	if ($column_key == 'rssonly') {
		$value = get_post_meta($post_id, '_rssonlypost', true);
		if ($value == '1') {
			echo '<span class="dashicons dashicons-rss"></span>';
		}
	}

}, 10, 2);
// -----------------------------------------------------------------------------------
add_filter('manage_edit-post_sortable_columns', function($columns) {
	$columns['rssonly'] = 'rssonly';
	return $columns;
} );
// -----------------------------------------------------------------------------------
if (is_admin()) {
	add_action( 'pre_get_posts', function($query) {
		global $pagenow;

		if ( $pagenow == 'edit.php' ) {	
			$orderby = $query->get( 'orderby' );
			if ( 'rssonly' == $orderby ) {

				$meta_query = array(
					'relation' => 'OR',
					array(
						'key' => '_rssonlypost',
						'compare' => 'NOT EXISTS', 
					),
					array(
						'key' => '_rssonlypost',
					),
				);

				$query->set( 'meta_query', $meta_query );
				$query->set( 'orderby', '_rssonlypost' ); 
			}
		} 
	} );
}

// -----------------------------------------------------------------------------------

add_action('init',  function () {
        add_feed('rssonlyposts', function() {

		} 
	);
});
