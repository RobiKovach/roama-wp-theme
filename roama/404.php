<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Page not found: the about page layout with a way back home.

defined( 'ABSPATH' ) || exit;

get_header();
?>
	<main id="main" tabindex="-1">
		<article class="section">
			<div class="roama-container"><div class="prose">
				<h1><?php esc_html_e( 'Сторінку не знайдено', 'roama' ); ?></h1>
				<p><?php esc_html_e( 'Можливо, адресу змінили або сторінку прибрали. Почніть із головної або скористайтеся меню вгорі.', 'roama' ); ?></p>
				<p><a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'На головну', 'roama' ); ?> <?php echo roama_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput -- built escaped ?></a></p>
			</div></div>
		</article>
	</main>
<?php
get_footer();
