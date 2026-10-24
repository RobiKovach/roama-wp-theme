<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Front page: text + an image to the screen edge (section#grid.edge-split), image on the
// right (the demo) or on the left (.edge-split--left).

defined( 'ABSPATH' ) || exit;

$roama_left  = 'left' === roama_field( 'roama_edge_side' );
$roama_image = roama_image(
	roama_field( 'roama_edge_image', 0 ),
	'edge-portrait',
	array(
		'class' => 'edge-split__image',
		'sizes' => '(min-width: 56.25em) 50vw, 100vw',
	)
);
?>
<section class="edge-split roama-page-grid<?php echo $roama_left ? ' edge-split--left' : ''; ?>" id="grid" aria-labelledby="grid-title">
	<div class="edge-split__text">
		<h2 class="edge-split__title" id="grid-title"><?php echo esc_html( roama_field( 'roama_edge_title' ) ); ?></h2>
		<?php roama_paragraphs( roama_field( 'roama_edge_text' ) ); ?>
	</div>
	<div class="edge-split__media <?php echo $roama_left ? 'roama-to-left-edge' : 'roama-to-right-edge'; ?>">
		<?php echo $roama_image; // phpcs:ignore WordPress.Security.EscapeOutput -- built escaped ?>
	</div>
</section>
