<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// The theme's code lives in inc/; this file only loads it.

defined( 'ABSPATH' ) || exit;

define( 'ROAMA_VERSION', '0.1.1' );
define( 'ROAMA_DIR', get_template_directory() );

foreach ( array( 'helpers', 'defaults', 'icons', 'setup', 'enqueue', 'scf', 'limits', 'seo', 'cf7', 'leads-core', 'leads', 'importer' ) as $roama_file ) {
	require_once ROAMA_DIR . '/inc/' . $roama_file . '.php';
}
unset( $roama_file );
