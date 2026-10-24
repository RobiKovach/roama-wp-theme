<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// One card of ul.feature-list. $args: icon (sprite name), title, text (inline markup allowed).

defined( 'ABSPATH' ) || exit;

$roama_title = trim( (string) ( $args['title'] ?? '' ) );
$roama_text  = trim( (string) ( $args['text'] ?? '' ) );
if ( '' === $roama_title && '' === $roama_text ) {
	return;
}
?>
<li class="feature-list__item" data-roama-reveal>
	<?php echo roama_icon( (string) ( $args['icon'] ?? '' ), array( 'class' => 'feature-list__icon' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- built escaped ?>
	<?php if ( '' !== $roama_title ) : ?>
		<h3 class="feature-list__title"><?php echo esc_html( $roama_title ); ?></h3>
	<?php endif; ?>
	<?php if ( '' !== $roama_text ) : ?>
		<p><?php roama_inline( $roama_text ); ?></p>
	<?php endif; ?>
</li>
