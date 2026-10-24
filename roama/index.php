<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Fallback for every view without its own template (posts, archives, search).

defined( 'ABSPATH' ) || exit;

get_header();
?>
	<main id="main" tabindex="-1">
		<article class="section">
			<div class="roama-container"><div class="prose">
				<?php if ( is_singular() ) : ?>
					<?php
					while ( have_posts() ) :
						the_post();
						?>
						<h1><?php the_title(); ?></h1>
						<?php the_content(); ?>
					<?php endwhile; ?>
				<?php else : ?>
					<h1><?php echo esc_html( is_home() ? ( get_option( 'page_for_posts' ) ? get_the_title( (int) get_option( 'page_for_posts' ) ) : __( 'Записи', 'roama' ) ) : wp_strip_all_tags( get_the_archive_title() ) ); ?></h1>
					<?php if ( have_posts() ) : ?>
						<?php
						while ( have_posts() ) :
							the_post();
							?>
							<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<?php the_excerpt(); ?>
						<?php endwhile; ?>
						<?php the_posts_pagination(); ?>
					<?php else : ?>
						<p><?php esc_html_e( 'Тут поки нічого немає.', 'roama' ); ?></p>
					<?php endif; ?>
				<?php endif; ?>
			</div></div>
		</article>
	</main>
<?php
get_footer();
