<?php
/**
 * WP Block functions.
 * 
 * @see https://gist.github.com/jenssogaard/54a1927ecf51c3238bd3eff1dac73114
 */

/**
 * Fetch ACF block data from a page.
 *
 * @param string $block_name WP block name.
 * @param string $field_name ACF field name.
 * @param int    $post_id    The post ID.
 * @return void
 */
function alm_acf_get_blocks_from_page( string $block_name, string $field_name, int $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return false;
	}

	$blocks = parse_blocks( $post->post_content );

	if ( $blocks ) {
		foreach ( $blocks as $block ) {
			if( $block['blockName'] === $block_name ) {
				if( $block['attrs']['data'][$field_name] ) {
					$arr = [];
					foreach( $block['attrs']['data'][$field_name] as $image ) {
						$arr[] = acf_get_attachment($image);
					}
					//return $arr;
					return $block['attrs']['data'][$field_name];
				}
			}
		}
	}

	return false;
}