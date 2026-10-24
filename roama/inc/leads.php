<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Leads: every Contact Form 7 lead goes to the client's Telegram bot and Google Sheet
// (inc/leads-core.php), Settings → «Заявки» (token never printed back, wp-config constants
// win), the test-lead button, the hourly retry, the UTM script.

defined( 'ABSPATH' ) || exit;

define( 'ROAMA_LEADS_PAGE', 'roama-leads' );

/**
 * Both CF7 hooks: mail may be unset at launch, the lead must still reach the client
 * (spam fires neither). The form's answer = did the lead reach anyone.
 *
 * @param WPCF7_ContactForm $contact_form The form.
 */
function roama_leads_on_submit( $contact_form ) {
	if ( ! class_exists( 'WPCF7_Submission' ) ) {
		return;
	}
	$submission = WPCF7_Submission::get_instance();
	if ( ! $submission ) {
		return;
	}
	$lead    = roama_leads_make(
		(array) $submission->get_posted_data(),
		(string) $contact_form->title(),
		(string) $submission->get_meta( 'url' ),
		function_exists( 'roama_cf7_labels' ) ? roama_cf7_labels() : array(),
		roama_leads_source()
	);
	$reached = roama_leads_process( $lead );
	// The same rule CF7 uses to skip the mail (it still reports «mail_sent» then).
	$skipped = (bool) apply_filters( 'wpcf7_skip_mail', $contact_form->is_true( 'skip_mail' ) || $contact_form->is_true( 'demo_mode' ), $contact_form );
	$mailed  = 'wpcf7_mail_sent' === current_action() && ! $skipped;
	if ( 'wpcf7_mail_failed' === current_action() && $reached ) {
		$submission->set_status( 'mail_sent' );
		$submission->set_response( $contact_form->message( 'mail_sent_ok' ) );
	} elseif ( 'wpcf7_mail_sent' === current_action() && ! $mailed && ! $reached ) {
		// Mail is off (skip_mail) and no channel took the lead: the visitor must not read «sent».
		$submission->set_status( 'mail_failed' );
		$submission->set_response( $contact_form->message( 'mail_sent_ng' ) );
	}
}
add_action( 'wpcf7_mail_sent', 'roama_leads_on_submit' );
add_action( 'wpcf7_mail_failed', 'roama_leads_on_submit' );

add_action( ROAMA_LEADS_CRON, 'roama_leads_retry' );

/**
 * The event leaves with the theme.
 */
function roama_leads_unschedule() {
	wp_clear_scheduled_hook( ROAMA_LEADS_CRON );
}
add_action( 'switch_theme', 'roama_leads_unschedule' );

/**
 * The UTM / referrer / landing page cookie (first touch, renewed by new UTM tags).
 */
function roama_leads_enqueue_utm() {
	wp_enqueue_script(
		'roama-leads-utm',
		get_theme_file_uri( 'js/leads-utm.js' ),
		array(),
		roama_asset_version( 'js/leads-utm.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'roama_leads_enqueue_utm' );

/**
 * Settings → «Заявки».
 */
function roama_leads_menu() {
	add_options_page(
		__( 'Заявки: Telegram і Google-таблиця', 'roama' ),
		__( 'Заявки', 'roama' ),
		'manage_options',
		ROAMA_LEADS_PAGE,
		'roama_leads_page'
	);
}
add_action( 'admin_menu', 'roama_leads_menu' );

/**
 * Back to the settings page with a notice code.
 *
 * @param string $notice Notice code.
 */
function roama_leads_back( $notice ) {
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'         => ROAMA_LEADS_PAGE,
				'roama_notice' => $notice,
			),
			admin_url( 'options-general.php' )
		)
	);
	exit;
}

/**
 * Saves the settings: an empty password field keeps the stored value.
 */
function roama_leads_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Недостатньо прав.', 'roama' ), 403 );
	}
	check_admin_referer( 'roama_leads_save' );
	$saved = get_option( ROAMA_LEADS_OPTION, array() );
	$saved = is_array( $saved ) ? $saved : array();
	$input = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each value is sanitized below
	$bad   = array();

	$token = isset( $input['roama_token'] ) ? trim( sanitize_text_field( (string) $input['roama_token'] ) ) : '';
	if ( ! empty( $input['roama_token_clear'] ) ) {
		$saved['token'] = '';
	} elseif ( '' !== $token ) {
		if ( preg_match( '/^\d{5,}:[A-Za-z0-9_-]{20,}$/', $token ) ) {
			$saved['token'] = $token;
		} else {
			$bad[] = 'token';
		}
	}
	if ( isset( $input['roama_chats'] ) ) {
		$chats          = sanitize_textarea_field( (string) $input['roama_chats'] );
		$saved['chats'] = implode( "\n", roama_leads_parse_chats( $chats ) );
		if ( '' !== trim( $chats ) && '' === $saved['chats'] ) {
			$bad[] = 'chats';
		}
	}
	if ( isset( $input['roama_sheet_url'] ) ) {
		$url = esc_url_raw( trim( (string) $input['roama_sheet_url'] ), array( 'https' ) );
		if ( '' === $url || 'script.google.com' === wp_parse_url( $url, PHP_URL_HOST ) ) {
			$saved['sheet_url'] = $url;
		} else {
			$bad[] = 'sheet_url';
		}
	}
	$secret = isset( $input['roama_secret'] ) ? trim( sanitize_text_field( (string) $input['roama_secret'] ) ) : '';
	if ( ! empty( $input['roama_secret_clear'] ) ) {
		$saved['secret'] = '';
	} elseif ( '' !== $secret ) {
		if ( strlen( $secret ) >= 16 && strlen( $secret ) <= 200 ) {
			$saved['secret'] = $secret;
		} else {
			$bad[] = 'secret';
		}
	}
	update_option( ROAMA_LEADS_OPTION, $saved, false );
	roama_leads_back( $bad ? 'bad_' . implode( '_', $bad ) : 'saved' );
}
add_action( 'admin_post_roama_leads_save', 'roama_leads_save' );

/**
 * «Надіслати тестову заявку»: sends only on click, never queued.
 */
function roama_leads_test() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Недостатньо прав.', 'roama' ), 403 );
	}
	check_admin_referer( 'roama_leads_test' );
	$channels = roama_leads_channels();
	if ( ! $channels['telegram'] && ! $channels['sheet'] ) {
		roama_leads_back( 'test_none' );
	}
	$lead   = roama_leads_make(
		array(
			'roama-name'    => 'Тестова заявка',
			'roama-message' => 'Перевірка зв’язку з сайту. Якщо ви це бачите, заявки доходять.',
		),
		__( 'Тест із налаштувань', 'roama' ),
		admin_url( 'options-general.php?page=' . ROAMA_LEADS_PAGE ),
		roama_cf7_labels()
	);
	$result = roama_leads_deliver( $lead );
	foreach ( $result['errors'] as $error ) {
		roama_leads_log_error( 'test', $error );
	}
	roama_leads_back( roama_leads_done( $result['pending'] ) ? 'test_ok' : ( $result['reached'] ? 'test_part' : 'test_fail' ) );
}
add_action( 'admin_post_roama_leads_test', 'roama_leads_test' );

/**
 * «Повторити зараз»: one retry round of the queue.
 */
function roama_leads_retry_now() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Недостатньо прав.', 'roama' ), 403 );
	}
	check_admin_referer( 'roama_leads_retry' );
	roama_leads_retry();
	roama_leads_back( 'retried' );
}
add_action( 'admin_post_roama_leads_retry', 'roama_leads_retry_now' );

/**
 * Text of a notice code.
 *
 * @param string $code From the redirect.
 * @return array{0: string, 1: string}|null [type, text].
 */
function roama_leads_notice( $code ) {
	$fields = array(
		'token'     => __( 'токен має вигляд 123456789:AA… (з BotFather)', 'roama' ),
		'chats'     => __( 'chat ID — це число (для груп з мінусом) або @назва_каналу', 'roama' ),
		'sheet_url' => __( 'адреса веб-застосунку має починатися з https://script.google.com/', 'roama' ),
		'secret'    => __( 'секретний ключ — від 16 до 200 символів', 'roama' ),
	);
	if ( 0 === strpos( $code, 'bad_' ) ) {
		$why = array();
		foreach ( explode( '_', substr( $code, 4 ) ) as $part ) {
			foreach ( $fields as $key => $text ) {
				if ( 0 === strpos( $key, $part ) && ! in_array( $text, $why, true ) ) {
					$why[] = $text;
				}
			}
		}
		return array( 'error', __( 'Збережено, крім полів з помилкою: ', 'roama' ) . implode( '; ', $why ) . '.' );
	}
	$notices = array(
		'saved'     => array( 'success', __( 'Налаштування збережено.', 'roama' ) ),
		'test_ok'   => array( 'success', __( 'Тестову заявку надіслано в усі канали. Перевірте Telegram і таблицю.', 'roama' ) ),
		'test_part' => array( 'warning', __( 'Тестова заявка дійшла лише в частину каналів. Причина — у списку помилок нижче.', 'roama' ) ),
		'test_fail' => array( 'error', __( 'Тестова заявка нікуди не дійшла. Причина — у списку помилок нижче.', 'roama' ) ),
		'test_none' => array( 'warning', __( 'Спершу налаштуйте Telegram або таблицю.', 'roama' ) ),
		'retried'   => array( 'success', __( 'Чергу перевірено ще раз.', 'roama' ) ),
	);
	return $notices[ $code ] ?? null;
}

/**
 * The settings page.
 */
function roama_leads_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$config   = roama_leads_config();
	$saved    = get_option( ROAMA_LEADS_OPTION, array() );
	$saved    = is_array( $saved ) ? $saved : array();
	$channels = roama_leads_channels( $config );
	$queue    = roama_leads_queue();
	$errors   = array_reverse( (array) get_option( ROAMA_LEADS_ERRORS, array() ) );
	$code     = isset( $_GET['roama_notice'] ) ? sanitize_key( wp_unslash( $_GET['roama_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a notice code only
	$notice   = '' !== $code ? roama_leads_notice( $code ) : null;
	$locked   = __( 'Задано у wp-config.php — змінюйте там.', 'roama' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Заявки: Telegram і Google-таблиця', 'roama' ); ?></h1>
		<?php if ( $notice ) : ?>
			<div class="notice notice-<?php echo esc_attr( $notice[0] ); ?> is-dismissible"><p><?php echo esc_html( $notice[1] ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Кожна заявка з форми сайту йде в Telegram-бот і в Google-таблицю (якщо їх налаштовано) і завжди зберігається у Flamingo («Flamingo → Вхідні повідомлення»). Покрокова інструкція — у файлі теми setup/LEADS-SETUP.md.', 'roama' ); ?></p>
		<p>
			<?php
			printf(
				/* translators: 1: Telegram state, 2: sheet state */
				esc_html__( 'Telegram: %1$s. Таблиця: %2$s.', 'roama' ),
				esc_html( $channels['telegram'] ? __( 'налаштовано', 'roama' ) : __( 'не налаштовано', 'roama' ) ),
				esc_html( $channels['sheet'] ? __( 'налаштовано', 'roama' ) : __( 'не налаштовано', 'roama' ) )
			);
			?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="roama_leads_save">
			<?php wp_nonce_field( 'roama_leads_save' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="roama-token"><?php esc_html_e( 'Токен бота', 'roama' ); ?></label></th>
					<td>
						<input type="password" id="roama-token" name="roama_token" class="regular-text" value="" autocomplete="new-password" maxlength="100" <?php disabled( $config['locked']['token'] ); ?> placeholder="<?php echo esc_attr( '' !== ( $saved['token'] ?? '' ) ? __( 'Збережено — порожнє поле не змінює токен', 'roama' ) : '123456789:AA…' ); ?>">
						<p class="description">
							<?php echo esc_html( $config['locked']['token'] ? $locked : __( 'Від @BotFather. Токен ніде не показується після збереження.', 'roama' ) ); ?>
						</p>
						<?php if ( ! $config['locked']['token'] && '' !== ( $saved['token'] ?? '' ) ) : ?>
							<label><input type="checkbox" name="roama_token_clear" value="1"> <?php esc_html_e( 'Видалити збережений токен', 'roama' ); ?></label>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="roama-chats"><?php esc_html_e( 'Chat ID', 'roama' ); ?></label></th>
					<td>
						<textarea id="roama-chats" name="roama_chats" class="regular-text" rows="3" maxlength="500" <?php disabled( $config['locked']['chats'] ); ?>><?php echo esc_textarea( implode( "\n", $config['chats'] ) ); ?></textarea>
						<p class="description"><?php echo esc_html( $config['locked']['chats'] ? $locked : __( 'Один у рядку: кожен отримає заявку. Як дізнатися chat ID — у LEADS-SETUP.md.', 'roama' ) ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="roama-sheet-url"><?php esc_html_e( 'Адреса веб-застосунку таблиці', 'roama' ); ?></label></th>
					<td>
						<input type="url" id="roama-sheet-url" name="roama_sheet_url" class="large-text" maxlength="300" value="<?php echo esc_attr( $config['sheet_url'] ); ?>" <?php disabled( $config['locked']['sheet_url'] ); ?> placeholder="https://script.google.com/macros/s/…/exec">
						<p class="description"><?php echo esc_html( $config['locked']['sheet_url'] ? $locked : __( 'Apps Script → Розгорнути → Веб-застосунок → URL.', 'roama' ) ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="roama-secret"><?php esc_html_e( 'Секретний ключ таблиці', 'roama' ); ?></label></th>
					<td>
						<input type="password" id="roama-secret" name="roama_secret" class="regular-text" value="" autocomplete="new-password" maxlength="200" <?php disabled( $config['locked']['secret'] ); ?> placeholder="<?php echo esc_attr( '' !== ( $saved['secret'] ?? '' ) ? __( 'Збережено — порожнє поле не змінює ключ', 'roama' ) : '' ); ?>">
						<p class="description"><?php echo esc_html( $config['locked']['secret'] ? $locked : __( 'Той самий ключ, що у властивості ROAMA_SECRET скрипта таблиці (від 16 символів).', 'roama' ) ); ?></p>
						<?php if ( ! $config['locked']['secret'] && '' !== ( $saved['secret'] ?? '' ) ) : ?>
							<label><input type="checkbox" name="roama_secret_clear" value="1"> <?php esc_html_e( 'Видалити збережений ключ', 'roama' ); ?></label>
						<?php endif; ?>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Зберегти', 'roama' ) ); ?>
		</form>

		<h2><?php esc_html_e( 'Перевірка', 'roama' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="roama_leads_test">
			<?php wp_nonce_field( 'roama_leads_test' ); ?>
			<?php submit_button( __( 'Надіслати тестову заявку', 'roama' ), 'secondary', 'submit', false ); ?>
		</form>

		<h2><?php esc_html_e( 'Черга', 'roama' ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: 1: number of leads, 2: number of attempts */
				esc_html__( 'Заявок, що чекають повторної відправки: %1$d. Сайт пробує щогодини, до %2$d разів.', 'roama' ),
				count( $queue ),
				(int) ROAMA_LEADS_MAX_ATTEMPTS
			);
			?>
		</p>
		<?php if ( $queue ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="roama_leads_retry">
				<?php wp_nonce_field( 'roama_leads_retry' ); ?>
				<?php submit_button( __( 'Повторити зараз', 'roama' ), 'secondary', 'submit', false ); ?>
			</form>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Останні помилки', 'roama' ); ?></h2>
		<?php if ( ! $errors ) : ?>
			<p><?php esc_html_e( 'Помилок немає.', 'roama' ); ?></p>
		<?php else : ?>
			<ul>
				<?php foreach ( array_slice( $errors, 0, 10 ) as $error ) : ?>
					<li><?php echo esc_html( wp_date( 'Y-m-d H:i', (int) ( $error['time'] ?? 0 ) ) . ' — ' . (string) ( $error['message'] ?? '' ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<?php
}
