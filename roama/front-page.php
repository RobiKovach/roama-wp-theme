<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// The front page: the sections of roama/site/pages/index.html in their fixed order.

defined( 'ABSPATH' ) || exit;

get_header();

/*
 * Section => anchor id (the anchor menu links only to sections that are printed).
 * Visibility comes from the filter roama_section_visible (the «Показувати секцію» toggles).
 */
$roama_sections = array(
	'hero'        => null,
	'anchor-menu' => null,
	'features'    => 'features',
	'edge-split'  => 'grid',
	'assets-demo' => 'assets',
	'sizes'       => null,
	'motion-demo' => 'motion',
	'contact'     => 'contact',
);
$roama_visible  = array();
foreach ( array_keys( $roama_sections ) as $roama_section ) {
	if ( apply_filters( 'roama_section_visible', true, $roama_section, get_queried_object_id() ) ) {
		$roama_visible[] = $roama_section;
	}
}
$roama_anchors = array();
foreach ( $roama_visible as $roama_section ) {
	if ( $roama_sections[ $roama_section ] ) {
		$roama_anchors[] = $roama_sections[ $roama_section ];
	}
}
?>
	<main id="main" tabindex="-1">
		<?php
		foreach ( $roama_visible as $roama_section ) {
			get_template_part( 'template-parts/sections/' . $roama_section, null, array( 'anchors' => $roama_anchors ) );
		}
		?>
	</main>
<?php
get_footer();
