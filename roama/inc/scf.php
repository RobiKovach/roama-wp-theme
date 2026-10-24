<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Secure Custom Fields: Local JSON in acf-json/, the options page «ROAMA: налаштування»,
// a notice when SCF is missing, the «Показувати секцію» toggles of the sections.

defined( 'ABSPATH' ) || exit;

define( 'ROAMA_OPTIONS_SLUG', 'roama-settings' );

/** Field groups are saved to and loaded from the theme's acf-json/ (they travel in git). */
add_filter(
	'acf/settings/save_json',
	static function () {
		return ROAMA_DIR . '/acf-json';
	}
);
add_filter(
	'acf/settings/load_json',
	static function ( $paths ) {
		$paths   = (array) $paths;
		$paths[] = ROAMA_DIR . '/acf-json';
		return array_values( array_unique( $paths ) );
	}
);

/**
 * The options page (contacts, socials, footer, the form, SEO defaults).
 */
function roama_options_page() {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}
	acf_add_options_page(
		array(
			'page_title' => __( 'ROAMA: налаштування', 'roama' ),
			'menu_title' => __( 'ROAMA: налаштування', 'roama' ),
			'menu_slug'  => ROAMA_OPTIONS_SLUG,
			'capability' => 'edit_theme_options',
			'icon_url'   => 'dashicons-admin-generic',
			'position'   => 61,
			'redirect'   => false,
		)
	);
}
add_action( 'acf/init', 'roama_options_page' );

/**
 * Without SCF the site still renders the defaults; the admin learns why fields are missing.
 */
function roama_scf_missing_notice() {
	if ( function_exists( 'acf_get_field_groups' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html__( 'Тема ROAMA бере тексти й картинки з плагіна Secure Custom Fields. Встановіть і активуйте його (Плагіни → Додати новий → «Secure Custom Fields»): поки його немає, сайт показує типовий вміст, а поля в адмінці недоступні.', 'roama' )
	);
}
add_action( 'admin_notices', 'roama_scf_missing_notice' );

/**
 * Section => its «Показувати секцію» field. Front page and about page.
 *
 * @return array<string, string>
 */
function roama_section_toggles() {
	return array(
		'hero'        => 'roama_hero_show',
		'anchor-menu' => 'roama_anchors_show',
		'features'    => 'roama_features_show',
		'edge-split'  => 'roama_edge_show',
		'assets-demo' => 'roama_assets_show',
		'sizes'       => 'roama_sizes_show',
		'motion-demo' => 'roama_motion_show',
		'contact'     => 'roama_contact_show',
		'about'       => 'roama_about_show',
	);
}

/**
 * Networks of the «Соцмережі» repeater (value => label). Keep in sync with acf-json.
 *
 * @return array<string, string>
 */
function roama_social_networks() {
	return array(
		'telegram'  => 'Telegram',
		'instagram' => 'Instagram',
		'facebook'  => 'Facebook',
		'youtube'   => 'YouTube',
		'tiktok'    => 'TikTok',
		'linkedin'  => 'LinkedIn',
		'viber'     => 'Viber',
		'whatsapp'  => 'WhatsApp',
	);
}

/**
 * Contacts of the options page, ready to print: only the filled ones.
 *
 * @return array{phone: array|null, email: string, address: string, socials: array<int, array{url: string, label: string}>}
 */
function roama_contacts() {
	$phone   = trim( (string) roama_field( 'roama_phone', '', 'option' ) );
	$email   = sanitize_email( (string) roama_field( 'roama_email', '', 'option' ) );
	$socials = array();
	foreach ( (array) roama_field( 'roama_socials', array(), 'option' ) as $row ) {
		$url      = esc_url_raw( (string) ( $row['url'] ?? '' ), array( 'http', 'https' ) );
		$network  = (string) ( $row['network'] ?? '' );
		$networks = roama_social_networks();
		if ( '' !== $url && isset( $networks[ $network ] ) ) {
			$socials[] = array(
				'url'   => $url,
				'label' => $networks[ $network ],
			);
		}
	}
	$digits = preg_replace( '/[^\d+]/', '', $phone );
	return array(
		'phone'   => '' !== $phone && '' !== $digits ? array(
			'text' => $phone,
			'href' => 'tel:' . $digits,
		) : null,
		'email'   => is_email( $email ) ? $email : '',
		'address' => trim( (string) roama_field( 'roama_address', '', 'option' ) ),
		'socials' => $socials,
	);
}

/**
 * The ID of the Contact Form 7 form chosen in the settings, 0 when there is none
 * (no CF7, nothing chosen, or the form was deleted).
 */
function roama_contact_form_id() {
	if ( ! function_exists( 'wpcf7_contact_form' ) ) {
		return 0;
	}
	$id = roama_field( 'roama_cf7_form', 0, 'option' );
	$id = is_object( $id ) ? (int) $id->ID : (int) $id;
	return ( $id && 'wpcf7_contact_form' === get_post_type( $id ) ) ? $id : 0;
}

/**
 * Off = the section is not printed at all; the anchor menu then drops its link.
 * The contact section also needs a form.
 *
 * @param bool   $visible Visible so far.
 * @param string $section Section slug.
 * @param int    $post_id Page ID.
 */
function roama_section_visible( $visible, $section, $post_id ) {
	if ( ! $visible ) {
		return false;
	}
	$toggles = roama_section_toggles();
	if ( isset( $toggles[ $section ] ) && ! roama_show( $toggles[ $section ], $post_id ? $post_id : false ) ) {
		return false;
	}
	if ( 'contact' === $section && ! roama_contact_form_id() ) {
		return false;
	}
	return true;
}
add_filter( 'roama_section_visible', 'roama_section_visible', 10, 3 );
