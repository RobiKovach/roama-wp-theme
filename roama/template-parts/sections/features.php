<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Front page: the cards (section#features, ul.feature-list). Works for 1, 2 and up to 12 cards.

defined( 'ABSPATH' ) || exit;

$roama_cards = array_slice( (array) roama_field( 'roama_features_cards' ), 0, 12 );
?>
<section class="section" id="features" aria-labelledby="features-title">
	<div class="roama-container">
		<h2 class="section__title" id="features-title"><?php echo esc_html( roama_field( 'roama_features_title' ) ); ?></h2>
		<ul class="feature-list">
			<?php
			foreach ( $roama_cards as $roama_card ) {
				get_template_part( 'template-parts/cards/feature', null, (array) $roama_card );
			}
			?>
		</ul>
	</div>
</section>
