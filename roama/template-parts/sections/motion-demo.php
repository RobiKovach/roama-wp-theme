<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Front page: smooth scroll and motion (section#motion.motion-demo). The lock button and
// the scrolling box are read by the ROAMA script (data-scroll-demo-*, data-lenis-prevent).

defined( 'ABSPATH' ) || exit;

$roama_steps = array(
	'Створює один об’єкт <code>window.ROAMA</code>.',
	'Перевіряє налаштування «менше руху».',
	'Запускає один екземпляр Lenis і віддає його як <code>window.lenis</code>.',
	'Висота шапки стає змінною <code>--roama-header-height</code>.',
	'Якорі отримують відступ під шапку й переносять фокус.',
	'<code>ROAMA.ready()</code> запускає компоненти сайту.',
	'На <code>&lt;html&gt;</code> з’являється клас <code>is-ready</code>.',
	'Подія <code>roama:ready</code> повідомляє решту коду.',
	'Цей блок має <code>data-lenis-prevent</code> і гортається сам.',
	'Колесо всередині нього не рухає сторінку.',
);
?>
<section class="section motion-demo" id="motion" aria-labelledby="motion-title">
	<div class="roama-container motion-demo__inner">
		<div class="motion-demo__text" data-roama-reveal>
			<h2 class="section__title" id="motion-title"><?php echo esc_html( roama_field( 'roama_motion_title' ) ); ?></h2>
			<p>Lenis робить прокрутку плавною. Якщо в системі ввімкнено «менше руху», сторінка гортається звичайно, а анімації одразу показують кінцевий стан. Посилання-якорі зупиняють ціль під липкою шапкою й переносять на неї фокус клавіатури.</p>
			<p><code>ROAMA.scroll.lock()</code> і <code>unlock()</code> зупиняють сторінку, поки відкрите вікно чи меню. Блокування рахуються: два виклики <code>lock()</code> знімають двома <code>unlock()</code>.</p>
			<div class="motion-demo__actions">
				<button class="button" type="button" aria-pressed="false" data-scroll-demo-lock data-roama-js-only>
					Зупинити прокрутку
				</button>
				<a class="button button--ghost" href="#top">Нагору</a>
			</div>
			<p class="motion-demo__status" role="status" data-scroll-demo-status></p>
		</div>
		<div class="motion-demo__box" role="region" aria-label="Список із власною прокруткою" tabindex="0" data-lenis-prevent data-roama-reveal>
			<p class="motion-demo__box-title">Як стартує <code>roama.js</code></p>
			<ol class="motion-demo__list">
				<?php foreach ( $roama_steps as $roama_step ) : ?>
					<li><?php roama_inline( $roama_step ); ?></li>
				<?php endforeach; ?>
			</ol>
		</div>
	</div>
</section>
