<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Front page: the contact section (section#contact) with the Contact Form 7 form chosen in
// «ROAMA: налаштування». Printed only when that form exists (see roama_section_visible()).

defined( 'ABSPATH' ) || exit;

$roama_form_id = roama_contact_form_id();
if ( ! $roama_form_id ) {
	return;
}
$roama_text = roama_optional( 'roama_contact_text' );
roama_cf7_enqueue_mask();
?>
<section class="section contact" id="contact" aria-labelledby="contact-title">
	<div class="roama-container contact__inner">
		<div class="contact__text">
			<h2 class="section__title" id="contact-title"><?php echo esc_html( roama_field( 'roama_contact_title' ) ); ?></h2>
			<?php if ( '' !== $roama_text ) : ?>
				<p class="contact__lead"><?php roama_inline( $roama_text ); ?></p>
			<?php endif; ?>
		</div>
		<div class="contact__form">
			<?php echo do_shortcode( sprintf( '[contact-form-7 id="%d"]', $roama_form_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- CF7 output ?>
		</div>
	</div>
</section>
