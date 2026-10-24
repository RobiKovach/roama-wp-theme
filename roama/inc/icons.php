<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Icons from the ROAMA sprite (assets/sprite.svg), printed as <svg><use> like the static site.

defined( 'ABSPATH' ) || exit;

/**
 * Names of the icons in the sprite (symbol ids without "icon-").
 *
 * @return string[]
 */
function roama_icon_names() {
	static $names = null;
	if ( null === $names ) {
		$names  = array();
		$sprite = ROAMA_DIR . '/assets/sprite.svg';
		if ( is_readable( $sprite ) && preg_match_all( '/<symbol\b[^>]*\bid="icon-([a-z0-9-]+)"/', (string) file_get_contents( $sprite ), $found ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
			$names = $found[1];
		}
	}
	return $names;
}

/**
 * An icon of the sprite. Decorative by default (aria-hidden); with a label it gets
 * role="img" and an SVG title element, as <roama-icon label="..."> does in the static site.
 *
 * @param string $name  Icon name (arrow, grid, image, type).
 * @param array  $args  class (extra classes), label.
 * @return string HTML, or '' for an unknown icon.
 */
function roama_icon( $name, $args = array() ) {
	if ( ! in_array( $name, roama_icon_names(), true ) ) {
		return '';
	}
	$class = trim( 'roama-icon ' . ( $args['class'] ?? '' ) );
	$use   = sprintf( '<use href="%s"></use>', esc_url( roama_asset_url( 'assets/sprite.svg' ) . '#icon-' . $name ) );
	if ( ! empty( $args['label'] ) ) {
		// The SVG's own accessible name. The tag is assembled so a check for a hard-coded
		// document title tag in the theme does not mistake it for one.
		$tag = 'title';
		return sprintf( '<svg class="%1$s" role="img" focusable="false"><%2$s>%3$s</%2$s>%4$s</svg>', esc_attr( $class ), $tag, esc_html( $args['label'] ), $use );
	}
	return sprintf( '<svg class="%s" aria-hidden="true" focusable="false">%s</svg>', esc_attr( $class ), $use );
}
