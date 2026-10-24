<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// <head> and the site header: the markup of roama/site/parts/header.html.

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
	<a class="skip-link" href="#main"><?php esc_html_e( 'Перейти до змісту', 'roama' ); ?></a>
<header class="site-header" data-roama-header>
	<div class="roama-container site-header__inner">
		<a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: site name */ __( '%s — на головну', 'roama' ), get_bloginfo( 'name' ) ) ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a>
		<button class="roama-burger site-header__burger" type="button" data-roama-burger aria-controls="site-menu" aria-expanded="false">
			<span class="roama-burger__lines" aria-hidden="true"></span>
			<span class="roama-visually-hidden"><?php esc_html_e( 'Меню', 'roama' ); ?></span>
		</button>
		<nav class="site-nav" id="site-menu" aria-label="<?php esc_attr_e( 'Головне меню', 'roama' ); ?>">
			<?php roama_menu( 'roama_primary' ); ?>
		</nav>
	</div>
</header>
