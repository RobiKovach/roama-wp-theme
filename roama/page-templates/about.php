<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
/*
 * Template Name: Про нас
 *
 * The about page of roama/site/pages/about.html: a title, an optional image and a long
 * text (prose), from the SCF group «Про нас». The section has its «Показувати секцію»
 * toggle; hidden, the page keeps only its title for screen readers.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
	<main id="main" tabindex="-1">
		<?php
		while ( have_posts() ) :
			the_post();
			$roama_title = roama_field( 'roama_about_title', get_the_title() );
			if ( ! apply_filters( 'roama_section_visible', true, 'about', get_the_ID() ) ) :
				?>
				<h1 class="screen-reader-text"><?php echo esc_html( $roama_title ); ?></h1>
				<?php
				continue;
			endif;
			$roama_text  = roama_field( 'roama_about_text', '' );
			$roama_image = roama_image(
				roama_field( 'roama_about_image', 0 ),
				'',
				array(
					'class' => 'about__image',
					'sizes' => '(min-width: 48em) 46rem, calc(100vw - 2rem)',
				)
			);
			?>
			<article class="section">
				<div class="roama-container"><div class="prose">
					<h1><?php echo esc_html( $roama_title ); ?></h1>
					<?php
					if ( '' !== $roama_image ) {
						echo '<figure class="about__media">' . $roama_image . '</figure>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built escaped
					}
					if ( '' !== trim( (string) $roama_text ) ) {
						echo wp_kses_post( $roama_text );
					} elseif ( '' !== trim( (string) get_the_content() ) ) {
						the_content();
					} else {
						echo wp_kses_post( roama_default( 'roama_about_text' ) );
					}
					?>
				</div></div>
			</article>
			<?php
		endwhile;
		?>
	</main>
<?php
get_footer();
