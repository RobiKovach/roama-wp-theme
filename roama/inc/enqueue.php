<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Styles, the ROAMA script (Lenis inside, type="module" as in the static build),
// font preloads and the theme's favicon. Versions = content hashes from the manifest.

defined( 'ABSPATH' ) || exit;

/**
 * Fonts the static build preloads (the first two WOFF2 files, SPEC fonts.preload).
 *
 * @return string[] paths relative to the theme
 */
function roama_preload_fonts() {
	$fonts = array();
	foreach ( array_keys( roama_manifest()['files'] ) as $path ) {
		if ( 0 === strpos( $path, 'assets/fonts/' ) && '.woff2' === substr( $path, -6 ) ) {
			$fonts[] = $path;
		}
	}
	sort( $fonts );
	return array_slice( $fonts, 0, 2 );
}

/**
 * Enqueues in the static order: the ROAMA script, its CSS, then the theme's WordPress-only CSS.
 */
function roama_enqueue() {
	wp_enqueue_script(
		'roama',
		get_theme_file_uri( 'js/roama.js' ),
		array(),
		roama_asset_version( 'js/roama.js' ),
		array(
			'in_footer' => false,
			'strategy'  => 'defer',
		)
	);
	wp_enqueue_style( 'roama', get_theme_file_uri( 'css/roama.css' ), array(), roama_asset_version( 'css/roama.css' ) );
	wp_enqueue_style( 'roama-theme', get_theme_file_uri( 'css/theme.css' ), array( 'roama' ), roama_asset_version( 'css/theme.css' ) );
}
add_action( 'wp_enqueue_scripts', 'roama_enqueue' );

/**
 * The ROAMA bundle is an ES module (as the static <script type="module">): deferred by
 * nature, and it must not be parsed as a classic script.
 *
 * @param string $tag    The <script> tag.
 * @param string $handle Script handle.
 */
function roama_module_tag( $tag, $handle ) {
	if ( 'roama' !== $handle ) {
		return $tag;
	}
	$tag = preg_replace( '/\stype=([\'"])[^\'"]*\1/', '', $tag );
	return preg_replace( '/<script\b/', '<script type="module"', $tag, 1 );
}
add_filter( 'script_loader_tag', 'roama_module_tag', 10, 2 );

/**
 * Font preloads, as in the static <head>.
 */
function roama_preload_tags() {
	foreach ( roama_preload_fonts() as $path ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( roama_asset_url( $path ) )
		);
	}
}
add_action( 'wp_head', 'roama_preload_tags', 2 );

/**
 * The theme's favicon, only while no Site Icon is set (Settings → General → Site Icon wins).
 */
function roama_favicon() {
	if ( has_site_icon() ) {
		return;
	}
	printf( '<link rel="icon" href="%s" sizes="32x32">' . "\n", esc_url( roama_asset_url( 'assets/favicon.ico' ) ) );
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( roama_asset_url( 'assets/favicon.svg' ) ) );
}
add_action( 'wp_head', 'roama_favicon', 3 );
