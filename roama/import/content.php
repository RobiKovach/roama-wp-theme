<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// The first content of a new site (wp roama import): the ROAMA demo. Field texts come from
// roama_defaults() (inc/defaults.php), so the templates' fallbacks and the import are one
// source. A project changes its first content here and in the defaults.
//
// References between items: '@media:<key>' = the imported attachment's ID, '@page:<key>' = the
// imported page's ID, '@form' = the ID of the theme's Contact Form 7 form.

defined( 'ABSPATH' ) || exit;

$roama_d = roama_defaults();

return array(
	// Site title (header logo, footer ©): written only when the site still has WordPress's
	// own default title, or with --overwrite.
	'site'    => array(
		'blogname'        => 'ROAMA',
		'blogdescription' => '',
	),

	// import/media/<file> -> Media library, found again by its key.
	'media'   => array(
		'hero' => array(
			'file'  => 'hero-landscape.jpg',
			'title' => 'Абстрактний пейзаж',
			'alt'   => $roama_d['roama_hero_image_alt'],
		),
		'edge' => array(
			'file'  => 'edge-portrait.jpg',
			'title' => 'Абстрактна композиція (вертикальна)',
			'alt'   => '', // Decorative in the demo (alt="").
		),
	),

	// Pages, found again by their key (meta _roama_import_key), else by slug.
	'pages'   => array(
		'home'    => array(
			'title'    => 'Головна',
			'slug'     => 'home',
			'template' => '',
			'order'    => 0,
			'front'    => true,
			'fields'   => array(
				'roama_hero_show'        => 1,
				'roama_hero_eyebrow'     => $roama_d['roama_hero_eyebrow'],
				'roama_hero_title'       => $roama_d['roama_hero_title'],
				'roama_hero_text'        => $roama_d['roama_hero_text'],
				'roama_hero_button_text' => $roama_d['roama_hero_button_text'],
				'roama_hero_button_page' => '@page:about',
				'roama_hero_image'       => '@media:hero',
				'roama_anchors_show'     => 1,
				'roama_anchors_label'    => $roama_d['roama_anchors_label'],
				'roama_anchors_features' => $roama_d['roama_anchors_features'],
				'roama_anchors_grid'     => $roama_d['roama_anchors_grid'],
				'roama_anchors_assets'   => $roama_d['roama_anchors_assets'],
				'roama_anchors_motion'   => $roama_d['roama_anchors_motion'],
				'roama_anchors_contact'  => $roama_d['roama_anchors_contact'],
				'roama_features_show'    => 1,
				'roama_features_title'   => $roama_d['roama_features_title'],
				'roama_features_cards'   => $roama_d['roama_features_cards'],
				'roama_edge_show'        => 1,
				'roama_edge_title'       => $roama_d['roama_edge_title'],
				'roama_edge_text'        => $roama_d['roama_edge_text'],
				'roama_edge_image'       => '@media:edge',
				'roama_edge_side'        => $roama_d['roama_edge_side'],
				'roama_assets_show'      => 1,
				'roama_assets_title'     => $roama_d['roama_assets_title'],
				'roama_sizes_show'       => 1,
				'roama_sizes_title'      => $roama_d['roama_sizes_title'],
				'roama_sizes_text'       => $roama_d['roama_sizes_text'],
				'roama_motion_show'      => 1,
				'roama_motion_title'     => $roama_d['roama_motion_title'],
				'roama_contact_show'     => 1,
				'roama_contact_title'    => $roama_d['roama_contact_title'],
				'roama_contact_text'     => $roama_d['roama_contact_text'],
			),
		),
		'about'   => array(
			'title'    => 'Про шаблон',
			'slug'     => 'about',
			'template' => 'page-templates/about.php',
			'order'    => 1,
			'fields'   => array(
				'roama_about_show'  => 1,
				'roama_about_title' => $roama_d['roama_about_title'],
				'roama_about_text'  => $roama_d['roama_about_text'],
			),
		),
		// Settings → Privacy points here when the site has no published privacy page yet
		// (the consent line under the form links to it). The text is the client's own.
		'privacy' => array(
			'title'    => 'Політика конфіденційності',
			'slug'     => 'privacy', // Not «privacy-policy»: WordPress's own draft has that slug.
			'template' => '',
			'order'    => 2,
			'privacy'  => true,
			'content'  => "<!-- wp:paragraph -->\n<p>[FILL IN: текст політики конфіденційності — хто збирає дані, які (ім’я, телефон, email, повідомлення з форми), навіщо, де вони зберігаються (пошта, Telegram, Google-таблиця, Flamingo), як довго і як попросити їх видалити. Текст дає клієнт і перевіряє його юрист.]</p>\n<!-- /wp:paragraph -->",
		),
	),

	// «ROAMA: налаштування». Contacts stay empty: the demo has none (the footer prints only
	// what is filled).
	'options' => array(
		'roama_footer_text' => $roama_d['roama_footer_text'],
		'roama_cf7_form'    => '@form',
	),

	// Menus of the static header and the footer. Items: a page by key, or a custom URL
	// relative to the home page ('/#features') or a bare anchor ('#contacts').
	'menus'   => array(
		'roama_primary' => array(
			'name'  => 'ROAMA: головне меню',
			'items' => array(
				array( 'page' => 'home' ),
				array( 'page' => 'about' ),
				array(
					'title' => 'Можливості',
					'url'   => '/#features',
				),
				array(
					'title' => 'Контакти',
					'url'   => '#contacts',
				),
			),
		),
		'roama_footer'  => array(
			'name'  => 'ROAMA: меню в підвалі',
			'items' => array(
				array( 'page' => 'privacy' ),
			),
		),
	),
);
