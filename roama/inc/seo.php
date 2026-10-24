<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Title, description and Open Graph from the SCF «SEO» fields (page fields first, then
// the defaults of the options page). Steps back completely when an SEO plugin is active:
// never two sources of meta.

defined( 'ABSPATH' ) || exit;

/**
 * True when an SEO plugin prints the meta (Yoast, Rank Math, The SEO Framework).
 */
function roama_seo_plugin_active() {
	$active = defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) || function_exists( 'the_seo_framework' );
	/** Lets a project name another SEO plugin. */
	return (bool) apply_filters( 'roama_seo_plugin_active', $active );
}

/**
 * The SEO values of the current view.
 *
 * @return array{title: string, description: string, image: string, url: string, type: string}
 */
function roama_seo_data() {
	$post_id = is_singular() ? get_queried_object_id() : false;
	if ( is_home() && ! is_front_page() ) {
		$post_id = (int) get_option( 'page_for_posts' );
	}
	$page = static function ( $name ) use ( $post_id ) {
		return $post_id ? (string) roama_field( $name, '', $post_id ) : '';
	};

	$description = $page( 'roama_seo_description' );
	if ( '' === $description && $post_id && has_excerpt( $post_id ) ) {
		$description = get_the_excerpt( $post_id );
	}
	if ( '' === $description ) {
		$description = (string) roama_field( 'roama_seo_default_description', get_bloginfo( 'description' ), 'option' );
	}

	$image_id = (int) ( $page( 'roama_seo_image' ) ?: roama_field( 'roama_seo_default_image', 0, 'option' ) );
	$image    = $image_id ? (string) wp_get_attachment_image_url( $image_id, 'large' ) : '';
	if ( '' === $image ) {
		$image = get_theme_file_uri( 'assets/images/hero-landscape-1440.jpg' );
	}

	return array(
		'title'       => $page( 'roama_seo_title' ),
		'description' => wp_strip_all_tags( $description ),
		'image'       => $image,
		'url'         => $post_id ? (string) get_permalink( $post_id ) : ( is_front_page() ? home_url( '/' ) : '' ),
		'type'        => is_front_page() ? 'website' : 'article',
	);
}

/**
 * A page's own SEO title replaces the document title (WordPress still prints the tag).
 *
 * @param string $title Pre-filtered title ('' = WordPress builds it).
 */
function roama_seo_title( $title ) {
	if ( roama_seo_plugin_active() || is_404() || is_search() ) {
		return $title;
	}
	$own = roama_seo_data()['title'];
	return '' !== $own ? $own : $title;
}
add_filter( 'pre_get_document_title', 'roama_seo_title', 20 );

/**
 * Description and Open Graph tags.
 */
function roama_seo_meta() {
	if ( roama_seo_plugin_active() || is_404() ) {
		return;
	}
	$seo = roama_seo_data();
	if ( '' !== $seo['description'] ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $seo['description'] ) );
	}
	$og = array(
		'og:type'      => $seo['type'],
		'og:title'     => '' !== $seo['title'] ? $seo['title'] : wp_get_document_title(),
		'og:url'       => $seo['url'],
		'og:site_name' => get_bloginfo( 'name' ),
		'og:locale'    => get_locale(),
	);
	if ( '' !== $seo['description'] ) {
		$og['og:description'] = $seo['description'];
	}
	if ( '' !== $seo['image'] ) {
		$og['og:image'] = $seo['image'];
	}
	$og = array_filter( $og, 'strlen' );
	foreach ( $og as $property => $content ) {
		$value = in_array( $property, array( 'og:url', 'og:image' ), true ) ? esc_url( $content ) : esc_attr( $content );
		printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $property ), $value ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above
	}
}
add_action( 'wp_head', 'roama_seo_meta', 1 );
