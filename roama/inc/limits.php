<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Hard limits for the strings SCF cannot limit: page titles, menu labels, the site name
// (cut on save), and the long editor texts of SCF (a "maxlength" in the field's JSON,
// checked on save). The SCF text fields limit themselves with their Character Limit.

defined( 'ABSPATH' ) || exit;

/**
 * Native limits in characters. Kept in WP-SETUP.md for the client.
 *
 * @return array<string, int>
 */
function roama_native_limits() {
	return (array) apply_filters(
		'roama_native_limits',
		array(
			'page_title'      => 80,
			'menu_label'      => 40,
			'blogname'        => 60,
			'blogdescription' => 160,
		)
	);
}

/**
 * Cuts a plain string to a number of characters (whole words when possible).
 *
 * @param string $text Text.
 * @param int    $max  Limit in characters.
 */
function roama_truncate( $text, $max ) {
	$text = trim( (string) $text );
	if ( $max <= 0 || mb_strlen( $text ) <= $max ) {
		return $text;
	}
	$cut   = mb_substr( $text, 0, $max );
	$space = mb_strrpos( $cut, ' ' );
	if ( false !== $space && $space > $max * 0.6 ) {
		$cut = mb_substr( $cut, 0, $space );
	}
	return rtrim( $cut, " \t\n\r\0\x0B,.;:—-" );
}

/**
 * Page titles and menu labels are cut to their limit on save.
 *
 * @param array $data Slashed post data.
 */
function roama_limit_post_title( $data ) {
	$limits = roama_native_limits();
	$map    = array(
		'page'          => $limits['page_title'] ?? 0,
		'nav_menu_item' => $limits['menu_label'] ?? 0,
	);
	$max    = (int) ( $map[ $data['post_type'] ?? '' ] ?? 0 );
	if ( $max > 0 && isset( $data['post_title'] ) ) {
		$title = wp_unslash( $data['post_title'] );
		if ( mb_strlen( $title ) > $max ) {
			$data['post_title'] = wp_slash( roama_truncate( $title, $max ) );
		}
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'roama_limit_post_title', 10, 1 );

foreach ( array( 'blogname', 'blogdescription' ) as $roama_option ) {
	add_filter(
		'pre_update_option_' . $roama_option,
		static function ( $value ) use ( $roama_option ) {
			$max = (int) ( roama_native_limits()[ $roama_option ] ?? 0 );
			return is_string( $value ) ? roama_truncate( $value, $max ) : $value;
		}
	);
}
unset( $roama_option );

/**
 * SCF editor (wysiwyg) fields with a "maxlength" in their JSON: the visible text (without
 * tags) must fit, else the page is not saved and SCF shows the message at the field.
 *
 * @param bool|string $valid Valid so far, or an error message.
 * @param mixed       $value Submitted value.
 * @param array       $field The field.
 */
function roama_validate_long_text( $valid, $value, $field ) {
	$max = (int) ( $field['maxlength'] ?? 0 );
	if ( true !== $valid || $max <= 0 || ! is_string( $value ) ) {
		return $valid;
	}
	$length = mb_strlen( trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( wp_unslash( $value ) ), ENT_QUOTES, 'UTF-8' ) ) ) );
	if ( $length > $max ) {
		/* translators: 1: length of the text, 2: the limit. */
		return sprintf( __( 'Текст задовгий: %1$d символів, можна до %2$d.', 'roama' ), $length, $max );
	}
	return $valid;
}
add_filter( 'acf/validate_value/type=wysiwyg', 'roama_validate_long_text', 10, 3 );
