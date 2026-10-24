<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Front page: the anchor menu (nav.anchor-menu). Links only to the sections that are printed.

defined( 'ABSPATH' ) || exit;

$roama_labels = array();
foreach ( array( 'features', 'grid', 'assets', 'motion', 'contact' ) as $roama_anchor ) {
	$roama_label = roama_optional( 'roama_anchors_' . $roama_anchor );
	if ( '' !== $roama_label ) {
		$roama_labels[ $roama_anchor ] = $roama_label;
	}
}
$roama_anchors = array_values( array_intersect( array_keys( $roama_labels ), $args['anchors'] ?? array() ) );
if ( ! $roama_anchors ) {
	return;
}
$roama_title = roama_optional( 'roama_anchors_label' );
?>
<nav class="anchor-menu" aria-label="<?php esc_attr_e( 'На цій сторінці', 'roama' ); ?>">
	<div class="roama-container anchor-menu__inner">
		<?php if ( '' !== $roama_title ) : ?>
			<p class="anchor-menu__label"><?php echo esc_html( $roama_title ); ?></p>
		<?php endif; ?>
		<ul class="anchor-menu__list">
			<?php foreach ( $roama_anchors as $roama_anchor ) : ?>
				<li><a class="anchor-menu__link" href="#<?php echo esc_attr( $roama_anchor ); ?>"><?php echo esc_html( $roama_labels[ $roama_anchor ] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
</nav>
