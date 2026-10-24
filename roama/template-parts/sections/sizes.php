<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Front page: fluid sizes note (section.roama-page-grid > div.wide-note).

defined( 'ABSPATH' ) || exit;
?>
<section class="roama-page-grid" aria-labelledby="sizes-title">
	<div class="wide-note roama-wide">
		<h2 class="section__title" id="sizes-title"><?php echo esc_html( roama_field( 'roama_sizes_title' ) ); ?></h2>
		<?php roama_paragraphs( roama_field( 'roama_sizes_text' ) ); ?>
	</div>
</section>
