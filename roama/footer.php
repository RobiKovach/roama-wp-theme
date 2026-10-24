<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// The site footer: the markup of roama/site/parts/footer.html, plus the footer menu once it is
// assigned and the contacts of «ROAMA: налаштування» once they are filled.

defined( 'ABSPATH' ) || exit;

$roama_footer_note = roama_optional( 'roama_footer_text', 'option' );
$roama_contacts    = roama_contacts();
?>
<footer class="site-footer" id="contacts">
	<div class="roama-container site-footer__inner">
		<p class="site-footer__copy">© <?php echo esc_html( wp_date( 'Y' ) . ' ' . get_bloginfo( 'name' ) ); ?></p>
		<?php if ( has_nav_menu( 'roama_footer' ) ) : ?>
			<nav class="site-footer__nav" aria-label="<?php esc_attr_e( 'Меню в підвалі', 'roama' ); ?>">
				<?php roama_menu( 'roama_footer' ); ?>
			</nav>
		<?php endif; ?>
		<?php if ( $roama_contacts['phone'] || $roama_contacts['email'] || '' !== $roama_contacts['address'] ) : ?>
			<address class="site-footer__contacts">
				<?php if ( $roama_contacts['phone'] ) : ?>
					<a class="site-footer__link" href="<?php echo esc_url( $roama_contacts['phone']['href'], array( 'tel' ) ); ?>"><?php echo esc_html( $roama_contacts['phone']['text'] ); ?></a>
				<?php endif; ?>
				<?php if ( $roama_contacts['email'] ) : ?>
					<a class="site-footer__link" href="<?php echo esc_url( 'mailto:' . $roama_contacts['email'], array( 'mailto' ) ); ?>"><?php echo esc_html( $roama_contacts['email'] ); ?></a>
				<?php endif; ?>
				<?php if ( '' !== $roama_contacts['address'] ) : ?>
					<span class="site-footer__address"><?php echo nl2br( esc_html( $roama_contacts['address'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside ?></span>
				<?php endif; ?>
			</address>
		<?php endif; ?>
		<?php if ( $roama_contacts['socials'] ) : ?>
			<ul class="site-footer__list site-footer__socials" aria-label="<?php esc_attr_e( 'Соцмережі', 'roama' ); ?>">
				<?php foreach ( $roama_contacts['socials'] as $roama_social ) : ?>
					<li><a class="site-footer__link" href="<?php echo esc_url( $roama_social['url'] ); ?>" rel="noopener" target="_blank"><?php echo esc_html( $roama_social['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( '' !== trim( (string) $roama_footer_note ) ) : ?>
			<p class="site-footer__note"><?php echo esc_html( $roama_footer_note ); ?></p>
		<?php endif; ?>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
