<?php
/**
 * OG Image Generator.
 *
 * Generates 1200×630px Open Graph preview images using GD.
 * Layout:
 *   - Left side: title (top), small portrait + author + post URL (bottom)
 *   - Right side: large featured image (or blank space)
 *
 * Colors match assets/src/scss/base/_palette.scss
 *
 * @package Kim_Branzell
 */

// ── Image dimensions ───────────────────────────────────────────
define( 'KB_OG_W', 1200 );
define( 'KB_OG_H', 630 );

// ── Palette ────────────────────────────────────────────────────
define( 'KB_OG_BG', '#F8F9F9' );  // c-2, light bg
define( 'KB_OG_TEXT', '#063935' );  // c-4, dark text
define( 'KB_OG_ACCENT', '#0c6f67' );  // c-1, teal accent
define( 'KB_OG_ACCENT_LIGHT', '#e5eff1' ); // c-3, light tint

// ── Fonts ───────────────────────────────────────────────────────
define( 'KB_OG_FONT_REGULAR', realpath( __DIR__ . '/../../assets/src/fonts/AtkinsonHyperlegible-Regular.ttf' ) );
define( 'KB_OG_FONT_BOLD', realpath( __DIR__ . '/../../assets/src/fonts/AtkinsonHyperlegible-Bold.ttf' ) );

// ── Helpers ────────────────────────────────────────────────────

/**
 * Allocate a GD color from a hex string.
 *
 * @param resource|GdImage $img GD image resource.
 * @param string           $hex Hex color, optionally with #.
 * @return int Color index.
 */
function kb_og_color( $img, $hex ) {
	$hex = ltrim( $hex, '#' );
	if ( strlen( $hex ) === 3 ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );
	return imagecolorallocate( $img, $r, $g, $b );
}

/**
 * Load a portrait image from a URL or local path.
 *
 * @param string $url Image URL.
 * @return GdImage|false GD image resource or false.
 */
function kb_og_load_portrait( $url ) {
	if ( empty( $url ) ) {
		return false;
	}

	$tmpfile = null;

	// Local file
	if ( strpos( $url, home_url() ) === 0 ) {
		$path = str_replace( home_url(), ABSPATH, $url );
		$path = rawurldecode( $path );
		if ( file_exists( $path ) ) {
			return kb_og_load_from_file( $path );
		}
	}

	// Remote URL
	$response = wp_remote_get(
		$url,
		array(
			'timeout'   => 8,
			'sslverify' => false,
		)
	);
	if ( is_wp_error( $response ) ) {
		return false;
	}

	$body    = wp_remote_retrieve_body( $response );
	$tmpfile = get_temp_dir() . 'kb_og_' . uniqid() . '.tmp';
	if ( file_put_contents( $tmpfile, $body ) === false ) {
		return false;
	}

	$img = kb_og_load_from_file( $tmpfile );
	@unlink( $tmpfile );

	return $img;
}

/**
 * Load a GD image from a local file path.
 *
 * @param string $path File path.
 * @return GdImage|false
 */
function kb_og_load_from_file( $path ) {
	$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
	switch ( $ext ) {
		case 'png':
			return @imagecreatefrompng( $path );
		case 'jpg':
		case 'jpeg':
			return @imagecreatefromjpeg( $path );
		case 'gif':
			return @imagecreatefromgif( $path );
		case 'webp':
			return @imagecreatefromwebp( $path );
		default:
			// Fallback: try string
			$body = @file_get_contents( $path );
			if ( $body ) {
				return @imagecreatefromstring( $body );
			}
	}
	return false;
}

/**
 * Composite a circular portrait onto the canvas.
 *
 * @param GdImage       $canvas   Target canvas.
 * @param GdImage|false $portrait Source portrait image.
 * @param int           $cx       Circle center X.
 * @param int           $cy       Circle center Y.
 * @param int           $size     Circle diameter.
 * @return bool Whether a portrait was composited.
 */
function kb_og_draw_portrait( $canvas, $portrait, $cx, $cy, $size ) {
	if ( ! $portrait ) {
		return false;
	}

	$pw = imagesx( $portrait );
	$ph = imagesy( $portrait );
	if ( $pw < 1 || $ph < 1 ) {
		return false;
	}

	// Circle mask
	$mask = imagecreatetruecolor( $size, $size );
	imagesavealpha( $mask, true );
	$transparent = imagecolorallocatealpha( $mask, 0, 0, 0, 127 );
	imagefill( $mask, 0, 0, $transparent );
	$black = imagecolorallocate( $mask, 0, 0, 0 );
	imagefilledellipse( $mask, (int) ( $size / 2 ), (int) ( $size / 2 ), $size, $size, $black );

	// Scale portrait to fill circle
	$scale  = max( $size / $pw, $size / $ph );
	$sw     = (int) ( $pw * $scale );
	$sh     = (int) ( $ph * $scale );
	$scaled = imagecreatetruecolor( $sw, $sh );
	imagecopyresampled( $scaled, $portrait, 0, 0, 0, 0, $sw, $sh, $pw, $ph );

	// Center crop offset
	$ox = (int) max( 0, ( $sw - $size ) / 2 );
	$oy = (int) max( 0, ( $sh - $size ) / 2 );

	// Apply mask
	imagecopy( $mask, $scaled, 0, 0, $ox, $oy, $size, $size );

	// Composite onto canvas
	imagecopy( $canvas, $mask, $cx - (int) ( $size / 2 ), $cy - (int) ( $size / 2 ), 0, 0, $size, $size );

	imagedestroy( $mask );
	imagedestroy( $scaled );

	return true;
}

/**
 * Draw initials on a circular background.
 *
 * @param GdImage $canvas  Target canvas.
 * @param int     $cx      Circle center X.
 * @param int     $cy      Circle center Y.
 * @param int     $size    Circle diameter.
 * @param string  $initials 1–2 character initials.
 * @param string  $bg_hex   Background hex.
 * @param string  $text_hex Text hex.
 */
function kb_og_draw_initials( $canvas, $cx, $cy, $size, $initials, $bg_hex, $text_hex ) {
	$bg   = kb_og_color( $canvas, $bg_hex );
	$text = kb_og_color( $canvas, $text_hex );

	imagefilledellipse( $canvas, $cx, $cy, $size, $size, $bg );

	$font = KB_OG_FONT_REGULAR;
	$fs   = (int) ( $size * 0.38 );
	$bbox = imagettfbbox( $fs, 0, $font, $initials );
	$tw   = abs( $bbox[2] - $bbox[0] );
	$th   = abs( $bbox[5] - $bbox[1] );
	imagettftext( $canvas, $fs, 0, $cx - (int) ( $tw / 2 ), $cy + (int) ( $th / 2 ), $text, $font, $initials );
}

/**
 * Wrap text to fit within a max width, returning an array of lines.
 *
 * @param string $text       Text to wrap.
 * @param int    $max_width  Max pixel width.
 * @param int    $font_size   Font size in pixels.
 * @param string $font_file   Path to font file.
 * @return string[] Lines.
 */
function kb_og_wrap_text( $text, $max_width, $font_size, $font_file ) {
	$words = preg_split( '/\s+/', trim( $text ) );
	$lines = array();
	$cur   = '';

	foreach ( $words as $word ) {
		$test = $cur === '' ? $word : $cur . ' ' . $word;
		$bbox = imagettfbbox( $font_size, 0, $font_file, $test );
		$w    = abs( $bbox[2] - $bbox[0] );

		if ( $w <= $max_width ) {
			$cur = $test;
		} else {
			if ( $cur !== '' ) {
				$lines[] = $cur;
			}
			// Single word too wide — truncate to avoid infinite loop
			$wb = imagettfbbox( $font_size, 0, $font_file, $word );
			$ww = abs( $wb[2] - $wb[0] );
			if ( $ww > $max_width ) {
				$cur = mb_strimwidth( $word, 0, 30, '' );
			} else {
				$cur = $word;
			}
		}
	}

	if ( $cur !== '' ) {
		$lines[] = $cur;
	}

	return $lines;
}

/**
 * Generate initials from a name (up to 2 chars).
 *
 * @param string $name Full name.
 * @return string Initials.
 */
function kb_og_initials( $name ) {
	$parts = array_filter( preg_split( '/\s+/', trim( $name ) ) );
	if ( empty( $parts ) ) {
		return '?';
	}
	if ( count( $parts ) === 1 ) {
		return mb_strtoupper( mb_substr( reset( $parts ), 0, 1 ) );
	}
	$first  = mb_substr( reset( $parts ), 0, 1 );
	$second = mb_substr( end( $parts ), 0, 1 );
	return mb_strtoupper( $first . $second );
}

/**
 * Wrap a URL into multiple lines, breaking at safe characters first.
 *
 * @param string $url        URL to wrap.
 * @param int    $max_width  Max pixel width.
 * @param int    $font_size  Font size.
 * @param string $font_file  Font file path.
 * @return string[] Lines.
 */
function kb_og_wrap_url( $url, $max_width, $font_size, $font_file ) {
	// First try breaking after / - . _ ~ characters
	$segments = preg_split( '/([\/\.\-_~])/', $url, -1, PREG_SPLIT_DELIM_CAPTURE );
	$lines    = array();
	$current  = '';

	foreach ( $segments as $segment ) {
		$test = $current . $segment;
		$bbox = imagettfbbox( $font_size, 0, $font_file, $test );
		$w    = abs( $bbox[2] - $bbox[0] );

		if ( $w <= $max_width ) {
			$current = $test;
		} else {
			if ( $current !== '' ) {
				$lines[] = $current;
			}
			// If a single segment is still too wide, hard-wrap it character by character
			$bbox_seg = imagettfbbox( $font_size, 0, $font_file, $segment );
			if ( abs( $bbox_seg[2] - $bbox_seg[0] ) > $max_width ) {
				$chars   = preg_split( '//u', $segment, -1, PREG_SPLIT_NO_EMPTY );
				$current = '';
				foreach ( $chars as $char ) {
					$test2 = $current . $char;
					$bbox2 = imagettfbbox( $font_size, 0, $font_file, $test2 );
					if ( abs( $bbox2[2] - $bbox2[0] ) <= $max_width ) {
						$current = $test2;
					} else {
						if ( $current !== '' ) {
							$lines[] = $current;
						}
						$current = $char;
					}
				}
			} else {
				$current = $segment;
			}
		}
	}

	if ( $current !== '' ) {
		$lines[] = $current;
	}

	return $lines;
}

// ── Main render function ────────────────────────────────────────

/**
 * Render the OG image for a given post.
 *
 * Layout (matching reference):
 *   - Left side: site label (top), title (upper), small portrait + author + URL (bottom)
 *   - Right side: large featured image (or placeholder)
 *
 * @param int    $post_id         Post ID.
 * @param string $title           Post title.
 * @param string $post_url        Post permalink URL.
 * @param string $portrait_url    Portrait URL or null.
 * @param string $author_name     Author display name.
 * @param string $featured_url    Featured image URL or null.
 * @return GdImage GD image resource.
 */
function kb_og_render_image( $post_id, $title, $post_url, $portrait_url, $author_name, $featured_url = null ) {
	$canvas = imagecreatetruecolor( KB_OG_W, KB_OG_H );
	imagesavealpha( $canvas, true );
	imagefill( $canvas, 0, 0, kb_og_color( $canvas, KB_OG_BG ) );

	$font_reg = KB_OG_FONT_REGULAR;
	$font_bld = KB_OG_FONT_BOLD;

	// ── Top accent line ───────────────────────────────────────
	imagefilledrectangle( $canvas, 0, 0, KB_OG_W, 4, kb_og_color( $canvas, KB_OG_ACCENT ) );

	// ── Featured image (right side, dominant) ──────────────────
	$featured_zone_left = 540;
	$featured_w         = KB_OG_W - $featured_zone_left - 40; // 620px wide
	$featured_h         = KB_OG_H - 80;                       // 550px tall (landscape-ish)
	$featured_x         = $featured_zone_left;
	$featured_y         = 40;

	if ( $featured_url ) {
		$feat = kb_og_load_portrait( $featured_url );
		if ( $feat ) {
			// Scale and crop to fill the featured zone
			$fw     = imagesx( $feat );
			$fh     = imagesy( $feat );
			$scale  = max( $featured_w / $fw, $featured_h / $fh );
			$sw     = (int) ( $fw * $scale );
			$sh     = (int) ( $fh * $scale );
			$scaled = imagecreatetruecolor( $sw, $sh );
			imagecopyresampled( $scaled, $feat, 0, 0, 0, 0, $sw, $sh, $fw, $fh );
			$ox = (int) max( 0, ( $sw - $featured_w ) / 2 );
			$oy = (int) max( 0, ( $sh - $featured_h ) / 2 );
			imagecopy( $canvas, $scaled, $featured_x, $featured_y, $ox, $oy, $featured_w, $featured_h );
			imagedestroy( $scaled );
			imagedestroy( $feat );
		}
	}
	// No featured image → leave blank space (no placeholder)

	// ── Left content area ──────────────────────────────────────
	$content_x = 60;
	$content_w = $featured_zone_left - $content_x - 50; // ~430px

	// ── Title ──────────────────────────────────────────────────
	$title_size = 30;
	for ( $sz = 30; $sz >= 18; $sz -= 2 ) {
		$lines = kb_og_wrap_text( $title, $content_w, $sz, $font_reg );
		if ( count( $lines ) <= 5 ) {
			$title_size = $sz;
			break;
		}
	}

	$lines   = kb_og_wrap_text( $title, $content_w, $title_size, $font_reg );
	$line_h  = (int) ( $title_size * 1.5 );
	$title_y = 80 + $title_size;

	foreach ( array_slice( $lines, 0, 5 ) as $i => $line ) {
		imagettftext( $canvas, $title_size, 0, $content_x, $title_y + ( $i * $line_h ), kb_og_color( $canvas, KB_OG_TEXT ), $font_reg, $line );
	}

	// ── Bottom-left author block ───────────────────────────────
	// Small portrait circle at bottom-left
	$portrait_sz = 90;
	$portrait_cx = $content_x + (int) ( $portrait_sz / 2 ); // 105
	$portrait_cy = KB_OG_H - 80;

	$portrait = kb_og_load_portrait( $portrait_url );
	if ( $portrait && kb_og_draw_portrait( $canvas, $portrait, $portrait_cx, $portrait_cy, $portrait_sz ) ) {
		imagedestroy( $portrait );
	} else {
		kb_og_draw_initials( $canvas, $portrait_cx, $portrait_cy, $portrait_sz, kb_og_initials( $author_name ), KB_OG_ACCENT, KB_OG_BG );
	}

	// Author name to the right of portrait
	$author_size = 22;
	$bbox        = imagettfbbox( $author_size, 0, $font_reg, $author_name );
	$author_th   = abs( $bbox[5] - $bbox[1] );
	$author_x    = $portrait_cx + (int) ( $portrait_sz / 2 ) + 18;
	$author_y    = $portrait_cy - 8;
	imagettftext( $canvas, $author_size, 0, $author_x, $author_y, kb_og_color( $canvas, KB_OG_TEXT ), $font_reg, $author_name );

	// Post URL below author name — wrap to stay within content area
	$url_size    = 15;
	$url_avail_w = $content_w - ( $author_x - $content_x );
	$url_lines   = kb_og_wrap_url( $post_url, $url_avail_w, $url_size, $font_reg );
	$url_line_h  = (int) ( $url_size * 1.35 );
	$url_y       = $author_y + $url_line_h + 4;

	foreach ( array_slice( $url_lines, 0, 3 ) as $i => $url_line ) {
		imagettftext( $canvas, $url_size, 0, $author_x, $url_y + ( $i * $url_line_h ), kb_og_color( $canvas, KB_OG_ACCENT ), $font_reg, $url_line );
	}

	return $canvas;
}

// ── Cache helpers ──────────────────────────────────────────────

/**
 * Get the cache file path for an OG image.
 *
 * @param int $post_id Post ID.
 * @param int $version Version number.
 * @return string Absolute file path.
 */
function kb_og_cache_path( $post_id, $version ) {
	$dir  = wp_upload_dir();
	$base = $dir['basedir'] . '/og-images';
	if ( ! file_exists( $base ) ) {
		wp_mkdir_p( $base );
	}
	return $base . '/og-' . $post_id . '-v' . $version . '.png';
}

/**
 * Get the cache file URL.
 *
 * @param int $post_id Post ID.
 * @param int $version Version number.
 * @return string URL.
 */
function kb_og_cache_url( $post_id, $version ) {
	$dir = wp_upload_dir();
	return $dir['baseurl'] . '/og-images/og-' . $post_id . '-v' . $version . '.png';
}

/**
 * Get the current OG version for a post.
 *
 * @param int $post_id Post ID.
 * @return int Version number.
 */
function kb_og_get_version( $post_id ) {
	return (int) get_post_meta( $post_id, 'kb_og_version', true ) ?: 1;
}

/**
 * Render and cache an OG image. Returns cache path and URL.
 *
 * @param int $post_id Post ID.
 * @return array{ path: string, url: string, version: int }
 */
function kb_og_render( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return array(
			'path'    => '',
			'url'     => '',
			'version' => 0,
		);
	}

	$title     = get_the_title( $post_id );
	$post_url  = get_permalink( $post_id );
	$author_id = (int) $post->post_author;
	$author    = get_the_author_meta( 'display_name', $author_id );
	$portrait  = kb_get_portrait_url( $author_id );
	$version   = kb_og_get_version( $post_id );

	// Featured image
	$featured_id  = get_post_thumbnail_id( $post_id );
	$featured_url = $featured_id ? wp_get_attachment_image_url( $featured_id, 'large' ) : null;

	$cache_path = kb_og_cache_path( $post_id, $version );
	if ( file_exists( $cache_path ) ) {
		return array(
			'path'    => $cache_path,
			'url'     => kb_og_cache_url( $post_id, $version ),
			'version' => $version,
		);
	}

	$canvas = kb_og_render_image( $post_id, $title, $post_url, $portrait, $author, $featured_url );

	if ( ! imagepng( $canvas, $cache_path, 6 ) ) {
		// Fallback: try in-memory output if file write fails
		imagestring( $canvas, 5, 10, 10, 'OG Image Render Error', kb_og_color( $canvas, '#ff0000' ) );
	}
	imagedestroy( $canvas );

	return array(
		'path'    => $cache_path,
		'url'     => kb_og_cache_url( $post_id, $version ),
		'version' => $version,
	);
}

/**
 * Bump OG version for a post (triggers async re-render via cron).
 *
 * @param int $post_id Post ID.
 * @return int New version number.
 */
function kb_og_bump_version( $post_id ) {
	$current = kb_og_get_version( $post_id );
	$new     = $current + 1;
	update_post_meta( $post_id, 'kb_og_version', $new );

	if ( ! wp_next_scheduled( 'kb_og_render', array( $post_id ) ) ) {
		wp_schedule_single_event( time(), 'kb_og_render', array( $post_id ) );
	}

	return $new;
}

/**
 * Flush OG cache for a specific post.
 *
 * @param int $post_id Post ID.
 */
function kb_og_flush_cache( $post_id ) {
	$dir     = wp_upload_dir()['basedir'] . '/og-images';
	$pattern = $dir . '/og-' . $post_id . '-v*.png';

	foreach ( glob( $pattern ) as $file ) {
		@unlink( $file );
	}
}
add_action( 'kb_og_flush_cache', 'kb_og_flush_cache' );

/**
 * Flush all OG caches.
 */
function kb_og_flush_all_cache() {
	$dir     = wp_upload_dir()['basedir'] . '/og-images';
	$pattern = $dir . '/og-*.png';

	foreach ( glob( $pattern ) as $file ) {
		@unlink( $file );
	}
}
add_action( 'kb_og_flush_all_cache', 'kb_og_flush_all_cache' );

/**
 * Cron render action.
 *
 * @param int $post_id Post ID.
 */
function kb_og_cron_render( $post_id ) {
	kb_og_render( $post_id );
}
add_action( 'kb_og_render', 'kb_og_cron_render' );
