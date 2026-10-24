<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Theme supports, menus (same markup as the static header), small security defaults.

defined( 'ABSPATH' ) || exit;

/**
 * Supports and menu locations.
 */
function roama_setup() {
	load_theme_textdomain( 'roama', ROAMA_DIR . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	register_nav_menus(
		array(
			'roama_primary' => __( 'Головне меню (шапка)', 'roama' ),
			'roama_footer'  => __( 'Меню в підвалі', 'roama' ),
		)
	);
}
add_action( 'after_setup_theme', 'roama_setup' );

/** The static site's titles read «Сторінка — Сайт». */
add_filter(
	'document_title_separator',
	static function () {
		return '—';
	}
);

/**
 * Menu markup of a location: the static site's classes, no WordPress ids or classes on <li>.
 *
 * @param string $location roama_primary or roama_footer.
 * @return array{list: string, link: string}|null
 */
function roama_menu_classes( $location ) {
	$map = array(
		'roama_primary' => array(
			'list' => 'site-nav__list',
			'link' => 'site-nav__link',
		),
		'roama_footer'  => array(
			'list' => 'site-footer__list',
			'link' => 'site-footer__link',
		),
	);
	return $map[ $location ] ?? null;
}

/**
 * Prints a menu of the theme. Primary falls back to the published pages; the footer menu
 * prints nothing until it is assigned (the static footer has no menu).
 *
 * @param string $location roama_primary or roama_footer.
 */
function roama_menu( $location ) {
	$classes = roama_menu_classes( $location );
	if ( ! $classes ) {
		return;
	}
	wp_nav_menu(
		array(
			'theme_location' => $location,
			'container'      => false,
			'items_wrap'     => '<ul class="' . esc_attr( $classes['list'] ) . '">%3$s</ul>',
			'depth'          => 1,
			'fallback_cb'    => 'roama_primary' === $location ? 'roama_menu_fallback' : '__return_empty_string',
		)
	);
}

/**
 * Fallback of the primary menu: the published pages in menu order, with the static markup.
 *
 * @param array $args wp_nav_menu() arguments.
 */
function roama_menu_fallback( $args = array() ) {
	$classes = roama_menu_classes( 'roama_primary' );
	$pages   = get_pages(
		array(
			'sort_column' => 'menu_order,post_title',
			'parent'      => 0,
			'number'      => 8,
		)
	);
	$items   = array();
	$front   = (int) get_option( 'page_on_front' );
	if ( ! $front ) {
		$items[] = array(
			'url'     => home_url( '/' ),
			'title'   => __( 'Головна', 'roama' ),
			'current' => is_front_page(),
		);
	}
	foreach ( $pages as $page ) {
		$items[] = array(
			'url'     => get_permalink( $page ),
			'title'   => get_the_title( $page ),
			'current' => is_page( $page->ID ),
		);
	}
	echo '<ul class="' . esc_attr( $classes['list'] ) . '">';
	foreach ( $items as $item ) {
		printf(
			'<li><a class="%s" href="%s"%s>%s</a></li>',
			esc_attr( $classes['link'] ),
			esc_url( $item['url'] ),
			$item['current'] ? ' aria-current="page"' : '',
			esc_html( $item['title'] )
		);
	}
	echo '</ul>';
}

/** No WordPress classes or ids on the <li> of our menus: the static markup has none. */
add_filter(
	'nav_menu_css_class',
	static function ( $classes, $item, $args ) {
		return ( isset( $args->theme_location ) && roama_menu_classes( $args->theme_location ) ) ? array() : $classes;
	},
	10,
	3
);
add_filter(
	'nav_menu_item_id',
	static function ( $id, $item, $args ) {
		return ( isset( $args->theme_location ) && roama_menu_classes( $args->theme_location ) ) ? '' : $id;
	},
	10,
	3
);

/**
 * The link class of the static header. WordPress adds aria-current="page" to the current
 * item, but also to an anchor link of the current page (/#features): only the page itself
 * is current, as in the static header.
 */
add_filter(
	'nav_menu_link_attributes',
	static function ( $atts, $item, $args ) {
		$classes = isset( $args->theme_location ) ? roama_menu_classes( $args->theme_location ) : null;
		if ( $classes ) {
			$atts['class'] = $classes['link'];
			if ( isset( $atts['href'] ) && false !== strpos( (string) $atts['href'], '#' ) ) {
				unset( $atts['aria-current'] );
			}
		}
		return $atts;
	},
	10,
	3
);

// Security defaults: no version number in the page source.
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );
