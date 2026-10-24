<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// The default value of every SCF field: the texts of the ROAMA demo. Templates fall back to
// them when a field is empty (or SCF is missing); the importer writes the same values.

defined( 'ABSPATH' ) || exit;

/**
 * Field name => default value. Show toggles are not here (on by default, see roama_show()).
 *
 * @return array<string, mixed>
 */
function roama_defaults() {
	static $defaults = null;
	if ( null !== $defaults ) {
		return $defaults;
	}
	$defaults = array(
		// Front page: hero.
		'roama_hero_eyebrow'           => 'Стартовий шаблон',
		'roama_hero_title'             => 'Статичний сайт, зібраний із частин',
		'roama_hero_text'              => 'Сторінки лежать у <code>site/pages/</code>, повторювані блоки — у <code>site/parts/</code>. Vite збирає все у звичайну папку <code>dist/</code>: HTML, CSS і JS без серверної частини.',
		'roama_hero_button_text'       => 'Як це влаштовано',
		'roama_hero_image_alt'         => 'Абстрактний пейзаж: синьо-фіолетове небо, тепле сонце і темні пагорби',
		// Anchor menu.
		'roama_anchors_label'          => 'На цій сторінці:',
		'roama_anchors_features'       => 'Можливості',
		'roama_anchors_grid'           => 'Сітка',
		'roama_anchors_assets'         => 'Ресурси',
		'roama_anchors_motion'         => 'Скрол і рух',
		'roama_anchors_contact'        => 'Контакти',
		// Cards.
		'roama_features_title'         => 'Можливості збірки',
		'roama_features_cards'         => array(
			array(
				'icon'  => 'type',
				'title' => 'Частини сторінок',
				'text'  => '<code>&lt;roama-part src="header"&gt;</code> вставляє файл із <code>site/parts/</code>, атрибути стають змінними.',
			),
			array(
				'icon'  => 'grid',
				'title' => 'Умови',
				'text'  => '<code>&lt;roama-if is="active" eq="home"&gt;</code> позначає активний пункт меню.',
			),
			array(
				'icon'  => 'image',
				'title' => 'Один файл налаштувань',
				'text'  => '<code>roama.config.mjs</code>: назва сайту, ширини дизайну, картинки, шрифти, рух.',
			),
			array(
				'icon'  => 'arrow',
				'title' => 'Панель сторінок',
				'text'  => 'У режимі розробки кнопка «ROAMA · N» у куті веде на кожну сторінку сайту.',
			),
			array(
				'icon'  => 'type',
				'title' => 'Нова сторінка однією командою',
				'text'  => '<code>npm run new -- page price-list</code> створює сторінку з шапкою, <code>h1</code> і підвалом, а <code>new component</code> — блок зі стилями та скриптом.',
			),
			array(
				'icon'  => 'grid',
				'title' => 'Архів для клієнта чи хостингу, зібраний нашим власним ZIP-записувачем без жодного додаткового пакета',
				'text'  => '<code>npm run zip</code> збирає сайт, якщо <code>dist/</code> застарів, і пакує його в один файл.',
			),
		),
		// Text + image to the screen edge.
		'roama_edge_title'             => 'Сітка до краю екрана',
		'roama_edge_text'              => "<code>.roama-page-grid</code> має іменовані лінії <code>full</code>, <code>wide</code> і <code>content</code>. Текст стоїть на лінії контенту, як і в <code>.roama-container</code>.\n\nБлок із класом <code>.roama-to-right-edge</code> починається від лінії контенту й доходить до правого краю екрана — так поруч із текстом ставлять картинку.",
		'roama_edge_side'              => 'right',
		// Demo sections with fixed content (only the title is a field).
		'roama_assets_title'           => 'Картинки, шрифти, іконки',
		'roama_sizes_title'            => 'Плавні розміри',
		'roama_sizes_text'             => '<code>fluid(34, 72)</code> дає заголовок, що росте від 34 px на ширині 360 до 72 px на ширині 1440. Ці ширини беруться з <code>roama.config.mjs</code>, а режими <code>keep-growing</code> і <code>keep-shrinking</code> залишають одну межу відкритою.',
		'roama_motion_title'           => 'Плавний скрол і рух',
		// Contact section (printed once a Contact Form 7 form is chosen in the settings).
		'roama_contact_title'          => 'Зв’язатися з нами',
		'roama_contact_text'           => 'Залиште повідомлення через форму, і ми відповімо на пошту чи телефон, які ви вкажете.',
		// About page.
		'roama_about_title'            => 'Про шаблон',
		'roama_about_text'             => roama_template_html( 'template-parts/about/demo-text' ),
		// Options page.
		'roama_footer_text'            => 'Демо-сайт стартового шаблону для статичних сайтів.',
		'roama_phone'                  => '',
		'roama_email'                  => '',
		'roama_address'                => '',
		'roama_socials'                => array(),
		'roama_seo_default_description' => '',
	);
	/** Lets a project change the defaults (and with them the importer's first content). */
	$defaults = (array) apply_filters( 'roama_defaults', $defaults );
	return $defaults;
}

/**
 * The default of one field ('' when it has none).
 *
 * @param string $name Field name.
 */
function roama_default( $name ) {
	$defaults = roama_defaults();
	return array_key_exists( $name, $defaults ) ? $defaults[ $name ] : '';
}

/**
 * The HTML a template part prints, as a string (the about page's demo text).
 *
 * @param string $slug Template part slug.
 */
function roama_template_html( $slug ) {
	ob_start();
	get_template_part( $slug );
	return trim( (string) ob_get_clean() );
}
