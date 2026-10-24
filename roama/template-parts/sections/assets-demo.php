<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Front page: pictures, fonts, icons (section#assets, div.assets-demo).

defined( 'ABSPATH' ) || exit;

$roama_items = array(
	array(
		'icon'  => 'image',
		'label' => '',
		'title' => 'Картинки в <code>&lt;picture&gt;</code>',
		'text'  => 'Звичайний <code>&lt;img&gt;</code> з <code>assets/images/</code> стає набором AVIF і WebP на кілька ширин із запасним JPG чи PNG. Ширина й висота оригіналу не дають сторінці стрибати.',
	),
	array(
		'icon'  => 'type',
		'label' => '',
		'title' => 'Шрифти лише WOFF2',
		'text'  => 'TTF чи OTF із <code>assets/fonts/</code> перетворюються на WOFF2, <code>@font-face</code> пишеться сам, а перші два шрифти завантажуються наперед. Тут — Onest і Unbounded (OFL).',
	),
	array(
		'icon'  => 'grid',
		'label' => 'Різнокольорова іконка',
		'title' => 'Один спрайт іконок',
		'text'  => '<code>&lt;roama-icon name="arrow"&gt;</code> бере символ зі спрайту. Одноколірні іконки фарбуються кольором тексту, <code>data-roama-multicolor</code> зберігає власні кольори.',
	),
);
?>
<section class="section" id="assets" aria-labelledby="assets-title">
	<div class="roama-container">
		<h2 class="section__title" id="assets-title"><?php echo esc_html( roama_field( 'roama_assets_title' ) ); ?></h2>
		<div class="assets-demo">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput -- built escaped
			echo roama_theme_picture(
				'tile-square',
				array(
					'alt'   => 'Квадратна мозаїка з синіх, фіолетових і пісочних квадратів та кіл',
					'class' => 'assets-demo__image',
					'sizes' => '(min-width: 56.25em) 22rem, calc(100vw - 2rem)',
				)
			);
			?>
			<ul class="assets-demo__list">
				<?php foreach ( $roama_items as $roama_item ) : ?>
					<li class="assets-demo__item" data-roama-reveal>
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput -- built escaped
						echo roama_icon(
							$roama_item['icon'],
							array(
								'class' => 'assets-demo__icon',
								'label' => $roama_item['label'],
							)
						);
						?>
						<div>
							<h3 class="assets-demo__title"><?php roama_inline( $roama_item['title'] ); ?></h3>
							<p><?php roama_inline( $roama_item['text'] ); ?></p>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>
