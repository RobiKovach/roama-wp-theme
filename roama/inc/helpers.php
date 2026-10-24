<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Small helpers: the asset manifest, field values with defaults, the theme's own pictures.

defined( 'ABSPATH' ) || exit;

/**
 * assets/manifest.json written by tools/build-theme.mjs (file -> content hash, pictures).
 *
 * @return array{files: array<string,string>, pictures: array<string,array>}
 */
function roama_manifest() {
	static $manifest = null;
	if ( null === $manifest ) {
		$manifest = array(
			'files'    => array(),
			'pictures' => array(),
		);
		$file     = ROAMA_DIR . '/assets/manifest.json';
		if ( is_readable( $file ) ) {
			$data = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( is_array( $data ) ) {
				$manifest = array_merge( $manifest, $data );
			}
		}
	}
	return $manifest;
}

/**
 * Version of a theme file: its content hash from the manifest, else filemtime.
 *
 * @param string $path Path relative to the theme, e.g. css/roama.css.
 */
function roama_asset_version( $path ) {
	$manifest = roama_manifest();
	if ( isset( $manifest['files'][ $path ] ) ) {
		return (string) $manifest['files'][ $path ];
	}
	$file = ROAMA_DIR . '/' . $path;
	return is_readable( $file ) ? (string) filemtime( $file ) : ROAMA_VERSION;
}

/**
 * URL of a theme file with its version, e.g. for the sprite.
 *
 * @param string $path Path relative to the theme.
 */
function roama_asset_url( $path ) {
	return add_query_arg( 'ver', roama_asset_version( $path ), get_theme_file_uri( $path ) );
}

/**
 * A field value (SCF) with a default: an empty field, a missing field or no SCF at all
 * gives the default, so a template never prints a broken tag. Raw value: escape at the echo.
 * Not for true_false fields (false is a real value there): use roama_show().
 *
 * @param string     $name    Field name (roama_*).
 * @param mixed      $default Value when the field is empty; null = the field's entry in roama_defaults().
 * @param int|string $post_id Post ID, 'option' for the options page, false = current post.
 */
function roama_field( $name, $default = null, $post_id = false ) {
	if ( null === $default ) {
		$default = roama_default( $name );
	}
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}
	$value = get_field( $name, $post_id );
	if ( is_string( $value ) && '' === trim( $value ) ) {
		$value = '';
	}
	if ( null === $value || false === $value || '' === $value || array() === $value ) {
		return $default;
	}
	return $value;
}

/**
 * An optional text (a caption, a button, a menu label): once the field was saved, its value
 * is used even when empty (empty = the element is not printed); a field that was never
 * saved, or no SCF, gives the default. Raw value: escape at the echo.
 *
 * @param string     $name    Field name (roama_*).
 * @param int|string $post_id Post ID, 'option', false = current post.
 */
function roama_optional( $name, $post_id = false ) {
	if ( ! function_exists( 'get_field' ) || ! function_exists( 'acf_get_valid_post_id' ) ) {
		return (string) roama_default( $name );
	}
	$id = acf_get_valid_post_id( $post_id );
	if ( is_numeric( $id ) ) {
		$saved = metadata_exists( 'post', (int) $id, $name );
	} else {
		$saved = null !== get_option( $id . '_' . $name, null );
	}
	return $saved ? trim( (string) get_field( $name, $post_id ) ) : (string) roama_default( $name );
}

/**
 * A «Показувати секцію» toggle (true_false). A toggle that was never saved, or no SCF,
 * means on: a new page shows every section.
 *
 * @param string     $name    Field name (roama_*_show).
 * @param int|string $post_id Post ID, 'option', false = current post.
 */
function roama_show( $name, $post_id = false ) {
	if ( ! function_exists( 'get_field' ) ) {
		return true;
	}
	$value = get_field( $name, $post_id );
	return null === $value || '' === $value ? true : (bool) $value;
}

/**
 * An image chosen in SCF (attachment ID) with the theme's own picture as the default.
 * Any ratio fits: the CSS box of every image has object-fit: cover.
 *
 * @param int|string $attachment_id Value of an image field (return format ID).
 * @param string     $fallback      Theme picture name (roama_theme_picture()), '' = none.
 * @param array      $args          alt (for the fallback), class, sizes, priority, size.
 * @return string HTML, or '' when there is nothing to show.
 */
function roama_image( $attachment_id, $fallback = '', $args = array() ) {
	$args          = wp_parse_args(
		$args,
		array(
			'alt'      => '',
			'class'    => '',
			'sizes'    => '100vw',
			'priority' => false,
			'size'     => 'full',
		)
	);
	$attachment_id = is_array( $attachment_id ) ? (int) ( $attachment_id['ID'] ?? 0 ) : (int) $attachment_id;
	if ( $attachment_id && wp_attachment_is_image( $attachment_id ) ) {
		$attr = array(
			'class'    => $args['class'],
			'sizes'    => $args['sizes'],
			'decoding' => 'async',
			'loading'  => $args['priority'] ? 'eager' : 'lazy',
		);
		if ( $args['priority'] ) {
			$attr['fetchpriority'] = 'high';
		}
		$html = wp_get_attachment_image( $attachment_id, $args['size'], false, $attr );
		if ( $html ) {
			return $html;
		}
	}
	return '' !== $fallback ? roama_theme_picture( $fallback, $args ) : '';
}

/**
 * A link made of a text field and a page field (never a raw URL the client could break).
 *
 * @param int|string $page_id  Value of a page field (post object, return format ID).
 * @param string     $text     Link text.
 * @param string     $fallback URL when no published page is chosen.
 * @return array{url: string, text: string}|null Null when the text or the URL is empty.
 */
function roama_link( $page_id, $text, $fallback = '' ) {
	$page_id = is_object( $page_id ) ? (int) $page_id->ID : (int) $page_id;
	$url     = ( $page_id && 'publish' === get_post_status( $page_id ) ) ? (string) get_permalink( $page_id ) : (string) $fallback;
	$text    = trim( (string) $text );
	if ( '' === $url || '' === $text ) {
		return null;
	}
	return array(
		'url'  => $url,
		'text' => $text,
	);
}

/**
 * URL of the first page with the «Про нас» template (the hero button's default target).
 */
function roama_about_url() {
	$pages = get_pages(
		array(
			'meta_key'   => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value' => 'page-templates/about.php', // phpcs:ignore WordPress.DB.SlowDBQuery
			'number'     => 1,
		)
	);
	return $pages ? (string) get_permalink( $pages[0] ) : home_url( '/' );
}

/**
 * A plain text with paragraphs: blank lines become <p>, inline markup stays.
 *
 * @param string $text Raw text of a textarea.
 */
function roama_paragraphs( $text ) {
	echo wp_kses( wpautop( (string) $text ), array_merge( roama_inline_tags(), array( 'p' => array() ) ) );
}

/**
 * Tags allowed in short texts that may hold inline markup (the demo uses <code>).
 */
function roama_inline_tags() {
	return array(
		'code'   => array(),
		'strong' => array(),
		'em'     => array(),
		'br'     => array(),
		'a'      => array(
			'href'   => true,
			'target' => true,
			'rel'    => true,
		),
	);
}

/**
 * Echoes a short text with inline markup only.
 *
 * @param string $text Raw text.
 */
function roama_inline( $text ) {
	echo wp_kses( (string) $text, roama_inline_tags() );
}

/**
 * The theme's own picture (copied from the ROAMA build), with the same markup as the
 * static site: <picture> with AVIF/WebP sources and a JPG/PNG <img>. Used as the default
 * when no media is chosen.
 *
 * @param string $name Picture name in the manifest (hero-landscape, edge-portrait, tile-square).
 * @param array  $args alt, class, sizes, priority (true = the hero: eager + fetchpriority high).
 * @return string HTML, or '' when the picture is unknown.
 */
function roama_theme_picture( $name, $args = array() ) {
	$args     = wp_parse_args(
		$args,
		array(
			'alt'      => '',
			'class'    => '',
			'sizes'    => '100vw',
			'priority' => false,
		)
	);
	$pictures = roama_manifest()['pictures'];
	if ( empty( $pictures[ $name ]['formats'] ) ) {
		return '';
	}
	$picture  = $pictures[ $name ];
	$srcset   = static function ( $format, $widths ) use ( $name ) {
		$items = array();
		foreach ( $widths as $width ) {
			$path    = 'assets/images/' . $name . '-' . (int) $width . '.' . $format;
			$items[] = roama_asset_url( $path ) . ' ' . (int) $width . 'w';
		}
		return implode( ', ', $items );
	};
	$types    = array(
		'avif' => 'image/avif',
		'webp' => 'image/webp',
	);
	$html     = '<picture>';
	$fallback = null;
	foreach ( $picture['formats'] as $format => $widths ) {
		if ( isset( $types[ $format ] ) ) {
			$html .= sprintf(
				'<source type="%s" srcset="%s" sizes="%s">',
				esc_attr( $types[ $format ] ),
				esc_attr( $srcset( $format, $widths ) ),
				esc_attr( $args['sizes'] )
			);
		} else {
			$fallback = $format;
		}
	}
	if ( null === $fallback ) {
		return '';
	}
	$widths = $picture['formats'][ $fallback ];
	$src    = in_array( 1024, $widths, true ) ? 1024 : (int) end( $widths );
	$html  .= sprintf(
		'<img src="%s" srcset="%s" sizes="%s" width="%d" height="%d" alt="%s" class="%s"%s decoding="async">',
		esc_url( roama_asset_url( 'assets/images/' . $name . '-' . $src . '.' . $fallback ) ),
		esc_attr( $srcset( $fallback, $widths ) ),
		esc_attr( $args['sizes'] ),
		(int) $picture['width'],
		(int) $picture['height'],
		esc_attr( $args['alt'] ),
		esc_attr( $args['class'] ),
		$args['priority'] ? ' fetchpriority="high" loading="eager"' : ' loading="lazy"'
	);
	return $html . '</picture>';
}
