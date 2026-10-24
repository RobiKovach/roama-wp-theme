<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// The first content in one step: media with alt texts, pages (Головна = the static front
// page, «Про шаблон» with the «Про нас» template, the privacy page), every SCF field with the
// demo texts, the Contact Form 7 form, both menus, permalinks /%postname%/.
// Idempotent: a second run skips what exists; overwrite only on demand.
// Runs as `wp roama import [--overwrite]` and from Appearance → «ROAMA: імпорт вмісту».

defined( 'ABSPATH' ) || exit;

define( 'ROAMA_IMPORT_KEY', '_roama_import_key' );

/**
 * The import plan of import/content.php.
 *
 * @return array{site: array, media: array, pages: array, options: array, menus: array}
 */
function roama_import_content() {
	$content = include ROAMA_DIR . '/import/content.php';
	return wp_parse_args(
		is_array( $content ) ? $content : array(),
		array(
			'site'    => array(),
			'media'   => array(),
			'pages'   => array(),
			'options' => array(),
			'menus'   => array(),
		)
	);
}

/**
 * Runs the import.
 *
 * @param bool $overwrite Rewrite what exists (fields, menu items, form, media alt) with the demo content.
 * @return array{done: string[], skipped: string[], warnings: string[]}
 */
function roama_import( $overwrite = false ) {
	$report  = array(
		'done'     => array(),
		'skipped'  => array(),
		'warnings' => array(),
	);
	$content = roama_import_content();
	$ids     = array(
		'media' => array(),
		'page'  => array(),
		'form'  => 0,
	);

	roama_import_site( $content['site'], $overwrite, $report );

	foreach ( $content['media'] as $key => $item ) {
		$ids['media'][ $key ] = roama_import_media( (string) $key, $item, $overwrite, $report );
	}

	foreach ( $content['pages'] as $key => $item ) {
		$ids['page'][ $key ] = roama_import_page( (string) $key, $item, $report );
	}

	// The front page and the templates first: SCF finds a field by the group's location.
	roama_import_reading( $ids['page'], $content['pages'], $report );

	$ids['form'] = roama_import_form( $overwrite, $report );

	if ( function_exists( 'update_field' ) ) {
		foreach ( $content['pages'] as $key => $item ) {
			if ( ! empty( $item['fields'] ) && $ids['page'][ $key ] ) {
				roama_import_fields( $item['fields'], $ids['page'][ $key ], $ids, $overwrite, $report );
			}
		}
		roama_import_fields( $content['options'], 'option', $ids, $overwrite, $report );
	} else {
		$report['warnings'][] = 'Secure Custom Fields не активний: поля не записано (сайт показує типовий вміст). Активуйте SCF і запустіть імпорт ще раз.';
	}

	foreach ( $content['menus'] as $location => $menu ) {
		roama_import_menu( (string) $location, $menu, $ids['page'], $overwrite, $report );
	}

	roama_import_permalinks( $report );

	/** After the import (a project adds its own content here). */
	do_action( 'roama_imported', $ids, $overwrite );
	return $report;
}

/**
 * The site title, only over WordPress's own default (or with overwrite).
 *
 * @param array $site      blogname, blogdescription.
 * @param bool  $overwrite Overwrite.
 * @param array $report    Report.
 */
function roama_import_site( $site, $overwrite, &$report ) {
	$defaults = array( '', 'My WordPress Website', 'My WordPress Site', 'Мій сайт WordPress', __( 'My WordPress Website' ) ); // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- core's own string
	foreach ( array( 'blogname', 'blogdescription' ) as $option ) {
		if ( ! isset( $site[ $option ] ) ) {
			continue;
		}
		$current = (string) get_option( $option );
		$generic = 'blogname' === $option ? in_array( $current, $defaults, true ) : in_array( $current, array( '', 'Just another WordPress site', __( 'Just another WordPress site' ) ), true ); // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
		if ( $current === $site[ $option ] ) {
			continue;
		}
		if ( $overwrite || $generic ) {
			update_option( $option, sanitize_text_field( $site[ $option ] ) );
			$report['done'][] = "Налаштування {$option}";
		} else {
			$report['skipped'][] = "Налаштування {$option} (уже задано)";
		}
	}
}

/**
 * One file of import/media/ into the Media library.
 *
 * @param string $key       Key in content.php.
 * @param array  $item      file, title, alt.
 * @param bool   $overwrite Rewrite the title and alt of an existing attachment.
 * @param array  $report    Report.
 * @return int Attachment ID, 0 on failure.
 */
function roama_import_media( $key, $item, $overwrite, &$report ) {
	$existing = roama_import_find( 'attachment', $key );
	$alt      = (string) ( $item['alt'] ?? '' );
	if ( $existing ) {
		if ( $overwrite ) {
			update_post_meta( $existing, '_wp_attachment_image_alt', $alt );
			$report['done'][] = "Картинка «{$key}»: alt оновлено";
		} else {
			$report['skipped'][] = "Картинка «{$key}» (уже є)";
		}
		return $existing;
	}
	$file = ROAMA_DIR . '/import/media/' . basename( (string) ( $item['file'] ?? '' ) );
	if ( ! is_readable( $file ) ) {
		$report['warnings'][] = "Картинка «{$key}»: немає файлу import/media/" . basename( $file );
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	// media_handle_sideload() moves the file: give it a copy.
	$tmp = wp_tempnam( basename( $file ) );
	if ( ! $tmp || ! copy( $file, $tmp ) ) {
		$report['warnings'][] = "Картинка «{$key}»: не вдалося скопіювати файл";
		return 0;
	}
	$id = media_handle_sideload(
		array(
			'name'     => basename( $file ),
			'tmp_name' => $tmp,
		),
		0,
		(string) ( $item['title'] ?? '' )
	);
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		$report['warnings'][] = "Картинка «{$key}»: " . $id->get_error_message();
		return 0;
	}
	update_post_meta( $id, ROAMA_IMPORT_KEY, $key );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	$report['done'][] = "Картинка «{$key}»";
	return (int) $id;
}

/**
 * A page: found again by its key, else by its slug; created when missing. Its title and text
 * are never rewritten (the client's own); the template is set when it has none.
 *
 * @param string $key    Key in content.php.
 * @param array  $item   title, slug, template, order, content, privacy.
 * @param array  $report Report.
 * @return int Page ID, 0 on failure.
 */
function roama_import_page( $key, $item, &$report ) {
	if ( ! empty( $item['privacy'] ) ) {
		$policy = (int) get_option( 'wp_page_for_privacy_policy' );
		if ( $policy && 'publish' === get_post_status( $policy ) ) {
			$report['skipped'][] = 'Сторінка політики конфіденційності (уже є: «' . get_the_title( $policy ) . '»)';
			return $policy;
		}
	}
	$id = roama_import_find( 'page', $key );
	if ( ! $id && ! empty( $item['slug'] ) ) {
		$page = get_page_by_path( (string) $item['slug'], OBJECT, 'page' );
		$id   = $page && 'trash' !== $page->post_status ? (int) $page->ID : 0;
		if ( $id ) {
			update_post_meta( $id, ROAMA_IMPORT_KEY, $key );
		}
	}
	if ( $id ) {
		$report['skipped'][] = 'Сторінка «' . get_the_title( $id ) . '» (уже є)';
	} else {
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => (string) $item['title'],
				'post_name'    => (string) ( $item['slug'] ?? '' ),
				'post_content' => (string) ( $item['content'] ?? '' ),
				'menu_order'   => (int) ( $item['order'] ?? 0 ),
				'meta_input'   => array( ROAMA_IMPORT_KEY => $key ),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			$report['warnings'][] = "Сторінка «{$item['title']}»: " . $id->get_error_message();
			return 0;
		}
		$report['done'][] = "Сторінка «{$item['title']}»";
	}
	$template = (string) ( $item['template'] ?? '' );
	if ( '' !== $template && ! get_post_meta( $id, '_wp_page_template', true ) ) {
		update_post_meta( $id, '_wp_page_template', $template );
	}
	return (int) $id;
}

/**
 * Reading settings: the static front page and the privacy page.
 *
 * @param array $ids    Page key => ID.
 * @param array $pages  Pages of content.php.
 * @param array $report Report.
 */
function roama_import_reading( $ids, $pages, &$report ) {
	foreach ( $pages as $key => $item ) {
		$id = (int) ( $ids[ $key ] ?? 0 );
		if ( ! $id ) {
			continue;
		}
		if ( ! empty( $item['front'] ) && ( 'page' !== get_option( 'show_on_front' ) || $id !== (int) get_option( 'page_on_front' ) ) ) {
			$current = (int) get_option( 'page_on_front' );
			if ( 'page' === get_option( 'show_on_front' ) && $current && 'publish' === get_post_status( $current ) && ! get_post_meta( $current, ROAMA_IMPORT_KEY, true ) ) {
				$report['skipped'][] = 'Головна сторінка (сайт уже має свою: «' . get_the_title( $current ) . '»)';
			} else {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', $id );
				$report['done'][] = 'Головна сторінка = «' . get_the_title( $id ) . '»';
			}
		}
		if ( ! empty( $item['privacy'] ) && (int) get_option( 'wp_page_for_privacy_policy' ) !== $id ) {
			update_option( 'wp_page_for_privacy_policy', $id );
			$report['done'][] = 'Налаштування → Конфіденційність = «' . get_the_title( $id ) . '»';
		}
	}
}

/**
 * The theme's Contact Form 7 form (inc/cf7.php).
 *
 * @param bool  $overwrite Rewrite the form definition.
 * @param array $report    Report.
 * @return int Form ID, 0 without CF7.
 */
function roama_import_form( $overwrite, &$report ) {
	if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
		$report['warnings'][] = 'Contact Form 7 не активний: форму не створено (секція контактів не показується). Активуйте CF7 і запустіть імпорт ще раз.';
		return 0;
	}
	$existed = (bool) get_posts(
		array(
			'post_type'      => 'wpcf7_contact_form',
			'post_status'    => 'any',
			'title'          => ROAMA_CF7_TITLE,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	$id = roama_cf7_create_form( $overwrite );
	if ( ! $id ) {
		$report['warnings'][] = 'Форму CF7 не вдалося створити';
	} elseif ( $existed && ! $overwrite ) {
		$report['skipped'][] = 'Форма «' . ROAMA_CF7_TITLE . '» (уже є)';
	} else {
		$report['done'][] = 'Форма «' . ROAMA_CF7_TITLE . '»' . ( $existed ? ' (оновлено)' : '' );
	}
	return $id;
}

/**
 * SCF fields of a page or of the options page. A field the client already saved is kept
 * (empty too: an empty optional text is their choice), unless overwrite.
 *
 * @param array      $fields    Field name => value or a reference (@media:, @page:, @form).
 * @param int|string $post_id   Page ID or 'option'.
 * @param array      $ids       Imported IDs.
 * @param bool       $overwrite Overwrite.
 * @param array      $report    Report.
 */
function roama_import_fields( $fields, $post_id, $ids, $overwrite, &$report ) {
	$written = 0;
	$kept    = 0;
	foreach ( $fields as $name => $value ) {
		$value = roama_import_resolve( $value, $ids );
		if ( null === $value ) {
			continue; // The referenced item could not be imported (reported there).
		}
		if ( ! $overwrite && roama_import_field_saved( (string) $name, $post_id ) ) {
			++$kept;
			continue;
		}
		$field = function_exists( 'acf_get_field' ) ? acf_get_field( $name ) : null;
		$key   = is_array( $field ) && ! empty( $field['key'] ) ? $field['key'] : $name;
		update_field( $key, $value, $post_id );
		++$written;
	}
	$place = 'option' === $post_id ? '«ROAMA: налаштування»' : '«' . get_the_title( (int) $post_id ) . '»';
	if ( $written ) {
		$report['done'][] = "Поля {$place}: {$written}";
	}
	if ( $kept ) {
		$report['skipped'][] = "Поля {$place}: {$kept} (уже заповнені)";
	}
}

/**
 * Was the field saved before (its meta / option row exists)?
 *
 * @param string     $name    Field name.
 * @param int|string $post_id Page ID or 'option'.
 */
function roama_import_field_saved( $name, $post_id ) {
	if ( 'option' === $post_id ) {
		return null !== get_option( 'options_' . $name, null );
	}
	return metadata_exists( 'post', (int) $post_id, $name );
}

/**
 * A value of content.php with its references replaced by IDs; null when a reference is missing.
 *
 * @param mixed $value Value.
 * @param array $ids   Imported IDs.
 */
function roama_import_resolve( $value, $ids ) {
	if ( ! is_string( $value ) || '' === $value || '@' !== $value[0] ) {
		return $value;
	}
	if ( '@form' === $value ) {
		return $ids['form'] ? (int) $ids['form'] : null;
	}
	if ( preg_match( '/^@(media|page):([\w-]+)$/', $value, $match ) ) {
		$id = (int) ( $ids[ $match[1] ][ $match[2] ] ?? 0 );
		return $id ? $id : null;
	}
	return $value;
}

/**
 * A menu for a location. An existing menu keeps its items (the client's) unless overwrite;
 * the location is assigned when it is empty.
 *
 * @param string $location  roama_primary / roama_footer.
 * @param array  $menu      name, items.
 * @param array  $pages     Page key => ID.
 * @param bool   $overwrite Replace the items.
 * @param array  $report    Report.
 */
function roama_import_menu( $location, $menu, $pages, $overwrite, &$report ) {
	$name    = (string) $menu['name'];
	$object  = wp_get_nav_menu_object( $name );
	$menu_id = $object ? (int) $object->term_id : 0;
	$created = false;
	if ( ! $menu_id ) {
		$menu_id = wp_create_nav_menu( $name );
		if ( is_wp_error( $menu_id ) ) {
			$report['warnings'][] = "Меню «{$name}»: " . $menu_id->get_error_message();
			return;
		}
		$created = true;
	}
	if ( $created || $overwrite ) {
		foreach ( (array) wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) ) as $old ) {
			wp_delete_post( $old->ID, true );
		}
		$position = 0;
		foreach ( (array) $menu['items'] as $item ) {
			$args = array(
				'menu-item-status'   => 'publish',
				'menu-item-position' => ++$position,
			);
			if ( isset( $item['page'] ) ) {
				$page_id = (int) ( $pages[ $item['page'] ] ?? 0 );
				if ( ! $page_id ) {
					continue;
				}
				$args += array(
					'menu-item-type'      => 'post_type',
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $page_id,
				);
				if ( isset( $item['title'] ) ) {
					$args['menu-item-title'] = (string) $item['title'];
				}
			} else {
				$url   = (string) $item['url'];
				$args += array(
					'menu-item-type'  => 'custom',
					'menu-item-title' => (string) $item['title'],
					'menu-item-url'   => 0 === strpos( $url, '/' ) ? home_url( $url ) : $url,
				);
			}
			wp_update_nav_menu_item( $menu_id, 0, $args );
		}
		$report['done'][] = "Меню «{$name}»" . ( $created ? '' : ' (пункти замінено)' );
	} else {
		$report['skipped'][] = "Меню «{$name}» (уже є)";
	}
	$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
	if ( empty( $locations[ $location ] ) || ( $overwrite && (int) $locations[ $location ] !== $menu_id ) || ! wp_get_nav_menu_object( (int) $locations[ $location ] ) ) {
		$locations[ $location ] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}
}

/**
 * Permalinks /%postname%/ with fresh rewrite rules (stale rules answer 200 on a 404).
 *
 * @param array $report Report.
 */
function roama_import_permalinks( &$report ) {
	global $wp_rewrite;
	if ( '/%postname%/' !== get_option( 'permalink_structure' ) ) {
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		$report['done'][] = 'Постійні посилання /%postname%/';
	}
	flush_rewrite_rules( false );
}

/**
 * A post imported earlier, by its key.
 *
 * @param string $type Post type.
 * @param string $key  Import key.
 * @return int ID, 0 when there is none.
 */
function roama_import_find( $type, $key ) {
	$found = get_posts(
		array(
			'post_type'      => $type,
			'post_status'    => 'attachment' === $type ? 'inherit' : array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'meta_key'       => ROAMA_IMPORT_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);
	return $found ? (int) $found[0] : 0;
}

// WP-CLI: wp roama import [--overwrite].
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'roama import',
		/**
		 * Imports the ROAMA demo content: media, pages, SCF fields, the CF7 form, menus, permalinks.
		 * A second run skips what exists.
		 *
		 * ## OPTIONS
		 *
		 * [--overwrite]
		 * : Rewrite fields, menu items, the form and image alt texts with the demo content.
		 *
		 * @param array $args       Positional arguments.
		 * @param array $assoc_args --overwrite.
		 */
		static function ( $args, $assoc_args ) {
			$report = roama_import( ! empty( $assoc_args['overwrite'] ) );
			foreach ( $report['done'] as $line ) {
				WP_CLI::log( '+ ' . $line );
			}
			foreach ( $report['skipped'] as $line ) {
				WP_CLI::log( '= ' . $line );
			}
			foreach ( $report['warnings'] as $line ) {
				WP_CLI::warning( $line );
			}
			WP_CLI::success( sprintf( 'ROAMA import: %d done, %d skipped, %d warnings.', count( $report['done'] ), count( $report['skipped'] ), count( $report['warnings'] ) ) );
		}
	);
}

/**
 * Appearance → «ROAMA: імпорт вмісту».
 */
function roama_import_admin_menu() {
	add_theme_page(
		__( 'ROAMA: імпорт вмісту', 'roama' ),
		__( 'ROAMA: імпорт вмісту', 'roama' ),
		'manage_options',
		'roama-import',
		'roama_import_admin_page'
	);
}
add_action( 'admin_menu', 'roama_import_admin_menu' );

/**
 * The import screen: what it does, a button, the last report.
 */
function roama_import_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$report = get_transient( 'roama_import_report_' . get_current_user_id() );
	if ( $report ) {
		delete_transient( 'roama_import_report_' . get_current_user_id() );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'ROAMA: імпорт вмісту', 'roama' ); ?></h1>
		<p><?php esc_html_e( 'Створює демо-вміст теми: картинки в Медіафайлах, сторінки «Головна», «Про шаблон» і «Політика конфіденційності», тексти всіх полів, контактну форму, меню в шапці й підвалі, постійні посилання /%postname%/. Повторний запуск нічого не дублює і не змінює вже заповнені поля.', 'roama' ); ?></p>
		<?php if ( is_array( $report ) ) : ?>
			<div class="notice notice-<?php echo $report['warnings'] ? 'warning' : 'success'; ?>">
				<p><strong><?php esc_html_e( 'Імпорт завершено.', 'roama' ); ?></strong></p>
				<ul>
					<?php foreach ( $report['done'] as $roama_line ) : ?>
						<li><?php echo esc_html( '+ ' . $roama_line ); ?></li>
					<?php endforeach; ?>
					<?php foreach ( $report['skipped'] as $roama_line ) : ?>
						<li><?php echo esc_html( '= ' . $roama_line ); ?></li>
					<?php endforeach; ?>
					<?php foreach ( $report['warnings'] as $roama_line ) : ?>
						<li><?php echo esc_html( '! ' . $roama_line ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="roama_import">
			<?php wp_nonce_field( 'roama_import' ); ?>
			<p>
				<label><input type="checkbox" name="roama_overwrite" value="1">
				<?php esc_html_e( 'Перезаписати: повернути демо-тексти в усі поля, пункти меню й форму (ваші зміни в них буде втрачено)', 'roama' ); ?></label>
			</p>
			<?php submit_button( __( 'Імпортувати вміст', 'roama' ), 'primary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}

/**
 * The button of the import screen (nonce + capability).
 */
function roama_import_admin_post() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Недостатньо прав.', 'roama' ), 403 );
	}
	check_admin_referer( 'roama_import' );
	$overwrite = ! empty( $_POST['roama_overwrite'] );
	set_transient( 'roama_import_report_' . get_current_user_id(), roama_import( $overwrite ), 10 * MINUTE_IN_SECONDS );
	wp_safe_redirect( admin_url( 'themes.php?page=roama-import' ) );
	exit;
}
add_action( 'admin_post_roama_import', 'roama_import_admin_post' );
