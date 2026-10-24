<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// A plain page: the layout of roama/site/pages/about.html (article.section > .prose).

defined( 'ABSPATH' ) || exit;

get_header();
?>
	<main id="main" tabindex="-1">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article class="section">
				<div class="roama-container"><div class="prose">
					<h1><?php the_title(); ?></h1>
					<?php the_content(); ?>
				</div></div>
			</article>
			<?php
		endwhile;
		?>
	</main>
<?php
get_footer();
