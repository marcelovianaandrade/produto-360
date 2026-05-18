<?php
/**
 * Uninstall do plugin Produto 360.
 *
 * Remove todos os posts do CPT e seus metadados.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Deleta todos os posts do CPT e seus metas
$posts = get_posts( array(
	'post_type'      => 'produto_360',
	'numberposts'    => -1,
	'post_status'    => 'any',
	'fields'         => 'ids',
) );

foreach ( $posts as $post_id ) {
	wp_delete_post( $post_id, true );
}
