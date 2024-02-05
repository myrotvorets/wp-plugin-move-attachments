<?php

namespace Myrotvorets\WordPress\MoveAttachments;

use WP_Post;

abstract class Utils {
	public static function url_to_postid( string $url ): int {
		$cache_key = md5( $url );
		/** @var mixed */
		$post_id = wp_cache_get( $cache_key, 'url_to_postid' );

		if ( ! is_int( $post_id ) ) {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.url_to_postid_url_to_postid
			$post_id = url_to_postid( $url );
			wp_cache_set( $cache_key, $post_id, 'url_to_postid', HOUR_IN_SECONDS );
		}

		return $post_id;
	}

	/**
	 * @psalm-return array<array-key, list{int, string}>
	 */
	public static function get_attachments( int $post_id ): array {
		$args = [
			'post_parent'    => $post_id,
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		];

		/** @var WP_Post[] */
		$atts = get_children( $args );
		return array_map( fn ( WP_Post $att ) => [ $att->ID, (string) get_attached_file( $att->ID ) ], $atts );
	}
}
