<?php
/**
 * User Portrait Resolution.
 *
 * Resolves the user portrait for OG images:
 * 1. ACF user field: kb_profile_image
 * 2. Fallback: Gravatar
 *
 * @package Kim_Branzell
 */

/**
 * Get the portrait URL for a given user.
 *
 * @param int $user_id Optional. Defaults to author of current post if set.
 * @return string|null Portrait URL, or null if none found.
 */
function kb_get_portrait_url( $user_id = null ) {
	// If no user_id, try to get from global post
	if ( null === $user_id && is_singular() ) {
		global $post;
		$user_id = (int) $post->post_author;
	}

	if ( null === $user_id || 0 === $user_id ) {
		return null;
	}

	// 1. Check ACF user field first
	if ( function_exists( 'get_field' ) ) {
		$acf_image = get_field( 'kb_profile_image', 'user_' . $user_id );
		if ( ! empty( $acf_image ) && is_array( $acf_image ) && ! empty( $acf_image['url'] ) ) {
			return $acf_image['url'];
		}
	}

	// 2. Fallback to Gravatar
	$gravatar = get_avatar_url( $user_id, array( 'size' => 300 ) );
	if ( $gravatar && ! is_wp_error( $gravatar ) ) {
		return $gravatar;
	}

	return null;
}

/**
 * Get portrait URL and cache-bust version.
 *
 * @param int $user_id User ID.
 * @return array{ url: string|null, version: int, source: string }
 */
function kb_get_portrait_data( $user_id = null ) {
	$url     = kb_get_portrait_url( $user_id );
	$source  = 'none';
	$version = 1;

	if ( $user_id ) {
		$version = (int) get_user_meta( $user_id, 'kb_portrait_version', true ) ?: 1;
	}

	if ( $url ) {
		// Check if it came from ACF
		if ( function_exists( 'get_field' ) ) {
			$acf_image = get_field( 'kb_profile_image', 'user_' . $user_id );
			if ( ! empty( $acf_image ) && is_array( $acf_image ) && ! empty( $acf_image['url'] ) ) {
				$source = 'acf';
			} else {
				$source = 'gravatar';
			}
		} else {
			$source = 'gravatar';
		}
	}

	return array(
		'url'     => $url,
		'version' => $version,
		'source'  => $source,
	);
}

/**
 * Invalidate portrait cache when ACF field is updated.
 *
 * Hooked to 'acf/save_post' for user forms.
 *
 * @param int $post_id Post ID (user ID in format 'user_123').
 */
function kb_invalidate_portrait_cache( $post_id ) {
	if ( ! is_string( $post_id ) || strpos( $post_id, 'user_' ) !== 0 ) {
		return;
	}

	$user_id = (int) substr( $post_id, 5 );
	if ( $user_id <= 0 ) {
		return;
	}

	// Bump the portrait version — this will cause all cached OG images to regenerate
	$current = (int) get_user_meta( $user_id, 'kb_portrait_version', true ) ?: 1;
	update_user_meta( $user_id, 'kb_portrait_version', $current + 1 );
}
add_action( 'acf/save_post', 'kb_invalidate_portrait_cache' );
