<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Front page: hero (roama/site/pages/index.html, section.hero).

defined( 'ABSPATH' ) || exit;

$roama_button  = roama_link( roama_field( 'roama_hero_button_page', 0 ), roama_optional( 'roama_hero_button_text' ), roama_about_url() );
$roama_text    = roama_optional( 'roama_hero_text' );
$roama_eyebrow = roama_optional( 'roama_hero_eyebrow' );
?>
<section class="hero" aria-labelledby="hero-title">
	<div class="roama-container hero__inner">
		<div class="hero__body">
			<?php if ( '' !== $roama_eyebrow ) : ?>
				<p class="hero__eyebrow"><?php echo esc_html( $roama_eyebrow ); ?></p>
			<?php endif; ?>
			<h1 class="hero__title" id="hero-title"><?php echo esc_html( roama_field( 'roama_hero_title', get_bloginfo( 'name' ) ) ); ?></h1>
			<?php if ( '' !== $roama_text ) : ?>
				<p class="hero__text"><?php roama_inline( $roama_text ); ?></p>
			<?php endif; ?>
			<?php if ( $roama_button ) : ?>
				<a class="button" href="<?php echo esc_url( $roama_button['url'] ); ?>"><?php echo esc_html( $roama_button['text'] ); ?> <?php echo roama_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput -- built escaped ?></a>
			<?php endif; ?>
		</div>
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput -- built escaped
		echo roama_image(
			roama_field( 'roama_hero_image', 0 ),
			'hero-landscape',
			array(
				'alt'      => roama_default( 'roama_hero_image_alt' ),
				'class'    => 'hero__image',
				'sizes'    => '(min-width: 64em) 36rem, calc(100vw - 2rem)',
				'priority' => true,
			)
		);
		?>
	</div>
</section>
