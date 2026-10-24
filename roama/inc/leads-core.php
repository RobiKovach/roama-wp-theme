<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Leads core: a lead from a form, the Telegram message, the Google Sheet (Apps Script web
// app) request, delivery with short timeouts, the retry queue, redaction of secrets.
// No hooks here (inc/leads.php wires them), so tests/php/leads-test.php can call it directly.

defined( 'ABSPATH' ) || exit;

define( 'ROAMA_LEADS_OPTION', 'roama_leads' );
define( 'ROAMA_LEADS_QUEUE', 'roama_leads_queue' );
define( 'ROAMA_LEADS_ERRORS', 'roama_leads_errors' );
define( 'ROAMA_LEADS_CRON', 'roama_leads_retry' );
define( 'ROAMA_LEADS_COOKIE', 'roama_lead_source' );
define( 'ROAMA_LEADS_MAX_ATTEMPTS', 24 );
define( 'ROAMA_LEADS_QUEUE_MAX', 200 );
define( 'ROAMA_LEADS_TG_LIMIT', 4096 );

/**
 * Settings: Settings → «Заявки», each value overridable by a constant in wp-config.php.
 *
 * @return array{token: string, chats: string[], sheet_url: string, secret: string, locked: array<string, bool>}
 */
function roama_leads_config() {
	$saved     = get_option( ROAMA_LEADS_OPTION, array() );
	$saved     = is_array( $saved ) ? $saved : array();
	$constants = array(
		'token'     => 'ROAMA_LEADS_TELEGRAM_TOKEN',
		'chats'     => 'ROAMA_LEADS_TELEGRAM_CHATS',
		'sheet_url' => 'ROAMA_LEADS_SHEET_URL',
		'secret'    => 'ROAMA_LEADS_SHEET_SECRET',
	);
	$config    = array( 'locked' => array() );
	foreach ( $constants as $key => $constant ) {
		$locked                  = defined( $constant );
		$config['locked'][ $key ] = $locked;
		$value                   = $locked ? constant( $constant ) : ( $saved[ $key ] ?? '' );
		$config[ $key ]          = is_array( $value ) ? implode( "\n", $value ) : trim( (string) $value );
	}
	$config['chats'] = roama_leads_parse_chats( $config['chats'] );
	return $config;
}

/**
 * Chat IDs from a text: one per line or comma separated; numeric IDs or @channel names.
 *
 * @param string $text Raw list.
 * @return string[]
 */
function roama_leads_parse_chats( $text ) {
	$chats = array();
	foreach ( preg_split( '/[\s,;]+/', (string) $text ) as $chat ) {
		if ( preg_match( '/^(-?\d{1,20}|@[A-Za-z][A-Za-z0-9_]{3,31})$/', $chat ) ) {
			$chats[] = $chat;
		}
	}
	return array_values( array_unique( $chats ) );
}

/**
 * Which channels are set up.
 *
 * @param array|null $config roama_leads_config().
 * @return array{telegram: bool, sheet: bool}
 */
function roama_leads_channels( $config = null ) {
	$config = $config ? $config : roama_leads_config();
	return array(
		'telegram' => '' !== $config['token'] && array() !== $config['chats'],
		'sheet'    => '' !== $config['sheet_url'] && '' !== $config['secret'],
	);
}

/**
 * Removes secrets from a text that will be stored or shown (the bot token is part of the
 * Telegram API URL, so transport errors may carry it).
 *
 * @param string $text Any message.
 */
function roama_leads_redact( $text ) {
	$config = roama_leads_config();
	$text   = (string) $text;
	foreach ( array( $config['token'], $config['secret'] ) as $secret ) {
		if ( strlen( $secret ) >= 6 ) {
			$text = str_replace( $secret, '[hidden]', $text );
		}
	}
	return preg_replace( '/\d{5,}:[A-Za-z0-9_-]{20,}/', '[hidden]', $text );
}

/**
 * Stores a delivery error (redacted, the last 20) for Settings → «Заявки».
 *
 * @param string $channel telegram|sheet|queue.
 * @param string $message What went wrong.
 */
function roama_leads_log_error( $channel, $message ) {
	$errors   = get_option( ROAMA_LEADS_ERRORS, array() );
	$errors   = is_array( $errors ) ? $errors : array();
	$errors[] = array(
		'time'    => time(),
		'channel' => (string) $channel,
		'message' => roama_leads_cut( roama_leads_redact( $message ), 500 ),
	);
	update_option( ROAMA_LEADS_ERRORS, array_slice( $errors, -20 ), false );
}

/**
 * Cuts a text to a number of characters, with … at the end when it was longer.
 *
 * @param string $text  Text.
 * @param int    $limit Characters.
 */
function roama_leads_cut( $text, $limit ) {
	$text = (string) $text;
	if ( roama_leads_length( $text ) <= $limit ) {
		return $text;
	}
	preg_match_all( '/./us', $text, $chars );
	$out  = '';
	$used = 0;
	foreach ( $chars[0] as $char ) {
		$used += strlen( $char ) > 3 ? 2 : 1;
		if ( $used > $limit - 1 ) {
			break;
		}
		$out .= $char;
	}
	return $out . '…';
}

/**
 * Length as Telegram counts it (UTF-16 code units: an emoji counts 2).
 *
 * @param string $text UTF-8 text.
 */
function roama_leads_length( $text ) {
	$chars = preg_match_all( '/./us', (string) $text );
	$wide  = preg_match_all( '/[\x{10000}-\x{10FFFF}]/u', (string) $text );
	return (int) $chars + (int) $wide;
}

/**
 * Where the visitor came from: the cookie js/leads-utm.js writes, parsed strictly
 * (known keys only, short plain values, http(s) referrer, a local landing path).
 *
 * @param string|null $raw Cookie value (null = $_COOKIE).
 * @return array<string, string>
 */
function roama_leads_source( $raw = null ) {
	if ( null === $raw ) {
		$raw = isset( $_COOKIE[ ROAMA_LEADS_COOKIE ] ) ? wp_unslash( $_COOKIE[ ROAMA_LEADS_COOKIE ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- parsed below
	}
	if ( ! is_string( $raw ) || '' === $raw || strlen( $raw ) > 2000 ) {
		return array();
	}
	$keys   = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'referrer', 'landing' );
	$source = array();
	foreach ( explode( '&', $raw ) as $pair ) {
		$parts = explode( '=', $pair, 2 );
		if ( 2 !== count( $parts ) || ! in_array( $parts[0], $keys, true ) || isset( $source[ $parts[0] ] ) ) {
			continue;
		}
		$value = roama_leads_cut( sanitize_text_field( urldecode( $parts[1] ) ), 200 );
		if ( '' === $value ) {
			continue;
		}
		if ( 'referrer' === $parts[0] ) {
			$value = (string) wp_http_validate_url( $value );
		} elseif ( 'landing' === $parts[0] && ( '/' !== $value[0] || 0 === strpos( $value, '//' ) ) ) {
			$value = '';
		}
		if ( '' !== $value ) {
			$source[ $parts[0] ] = $value;
		}
	}
	return $source;
}

/**
 * A lead from posted form data: service fields (_*) and the honeypot dropped, the rest
 * labelled; arrays (checkboxes) joined.
 *
 * @param array  $posted Posted data (CF7 get_posted_data()).
 * @param string $form   Form title.
 * @param string $page   URL of the page the form was sent from.
 * @param array  $labels Field name => label.
 * @param array  $source roama_leads_source().
 * @return array{id: string, time: string, form: string, page: string, fields: array<int, array{label: string, value: string}>, source: array}
 */
function roama_leads_make( array $posted, $form, $page, array $labels = array(), array $source = array() ) {
	$fields = array();
	foreach ( $posted as $name => $value ) {
		$name = (string) $name;
		if ( '' === $name || '_' === $name[0] || ( defined( 'ROAMA_HONEYPOT' ) && ROAMA_HONEYPOT === $name ) ) {
			continue;
		}
		$value = is_array( $value ) ? implode( ', ', array_map( 'strval', $value ) ) : (string) $value;
		$value = trim( $value );
		if ( '' === $value ) {
			continue;
		}
		$label    = $labels[ $name ] ?? ucfirst( str_replace( array( 'roama-', '-', '_' ), array( '', ' ', ' ' ), $name ) );
		$fields[] = array(
			'label' => (string) $label,
			'value' => $value,
		);
	}
	return array(
		'id'     => wp_generate_uuid4(),
		'time'   => wp_date( 'Y-m-d H:i:s' ),
		'form'   => (string) $form,
		'page'   => esc_url_raw( (string) $page ),
		'fields' => $fields,
		'source' => $source,
	);
}

/**
 * Escapes a text for Telegram's parse_mode=HTML (only & < > are special there).
 *
 * @param string $text Text.
 */
function roama_leads_tg_escape( $text ) {
	return str_replace( array( '&', '<', '>' ), array( '&amp;', '&lt;', '&gt;' ), (string) $text );
}

/**
 * The Telegram message (HTML, at most 4096 characters: the longest values are shortened,
 * never a tag or an entity cut in half).
 *
 * @param array $lead roama_leads_make().
 */
function roama_leads_tg_message( array $lead ) {
	$fields = $lead['fields'];
	$build  = static function ( $fields ) use ( $lead ) {
		$lines   = array( '<b>' . roama_leads_tg_escape( sprintf( 'Нова заявка: %s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ) ) . '</b>' );
		$lines[] = '<b>Форма:</b> ' . roama_leads_tg_escape( $lead['form'] );
		foreach ( $fields as $field ) {
			$lines[] = '<b>' . roama_leads_tg_escape( $field['label'] ) . ':</b> ' . roama_leads_tg_escape( $field['value'] );
		}
		if ( '' !== $lead['page'] ) {
			$lines[] = '<b>Сторінка:</b> ' . roama_leads_tg_escape( $lead['page'] );
		}
		foreach ( $lead['source'] as $key => $value ) {
			$lines[] = '<b>' . roama_leads_tg_escape( $key ) . ':</b> ' . roama_leads_tg_escape( $value );
		}
		$lines[] = '<i>' . roama_leads_tg_escape( $lead['time'] . ' · ' . $lead['id'] ) . '</i>';
		return implode( "\n", $lines );
	};
	$text = $build( $fields );
	for ( $guard = 0; roama_leads_length( $text ) > ROAMA_LEADS_TG_LIMIT && $guard < 50; $guard++ ) {
		$longest = 0;
		foreach ( $fields as $i => $field ) {
			if ( roama_leads_length( $field['value'] ) > roama_leads_length( $fields[ $longest ]['value'] ) ) {
				$longest = $i;
			}
		}
		if ( ! $fields ) {
			break;
		}
		$over                         = roama_leads_length( $text ) - ROAMA_LEADS_TG_LIMIT;
		$keep                         = max( 1, roama_leads_length( $fields[ $longest ]['value'] ) - $over - 10 );
		$fields[ $longest ]['value'] = roama_leads_cut( $fields[ $longest ]['value'], $keep );
		$text                         = $build( $fields );
	}
	return $text;
}

/**
 * Sends the message to one chat. Short timeout; the answer must be Telegram's ok.
 *
 * @param string $token   Bot token.
 * @param string $chat_id Chat ID.
 * @param string $text    HTML message.
 * @return true|string True, or the (unredacted) error.
 */
function roama_leads_send_telegram( $token, $chat_id, $text ) {
	$response = wp_remote_post(
		'https://api.telegram.org/bot' . $token . '/sendMessage',
		array(
			'timeout'     => 4,
			'redirection' => 0,
			'body'        => array(
				'chat_id'                  => $chat_id,
				'text'                     => $text,
				'parse_mode'               => 'HTML',
				'disable_web_page_preview' => 'true',
			),
		)
	);
	if ( is_wp_error( $response ) ) {
		return 'Telegram: ' . $response->get_error_message();
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	if ( 200 === $code && is_array( $data ) && ! empty( $data['ok'] ) ) {
		return true;
	}
	$why = is_array( $data ) && isset( $data['description'] ) ? (string) $data['description'] : 'no answer';
	return sprintf( 'Telegram, chat %s: HTTP %d, %s', $chat_id, $code, $why );
}

/**
 * The JSON the Apps Script web app receives (setup/leads-apps-script.gs).
 *
 * @param array  $lead   roama_leads_make().
 * @param string $secret Shared secret.
 */
function roama_leads_sheet_payload( array $lead, $secret ) {
	return array(
		'secret' => (string) $secret,
		'id'     => $lead['id'],
		'time'   => $lead['time'],
		'site'   => home_url( '/' ),
		'form'   => $lead['form'],
		'page'   => $lead['page'],
		'fields' => $lead['fields'],
		'source' => (object) $lead['source'],
	);
}

/**
 * Sends the lead to the sheet. Apps Script answers a POST with a 302 to the result; WordPress
 * would follow it as a GET with the JSON in the query string (400, although the row WAS
 * written), so the POST does not follow redirects and the Location is fetched with a GET.
 *
 * @param string $url    Web app URL (…/exec).
 * @param string $secret Shared secret.
 * @param array  $lead   roama_leads_make().
 * @return true|string True, or the error.
 */
function roama_leads_send_sheet( $url, $secret, array $lead ) {
	$response = wp_remote_post(
		$url,
		array(
			'timeout'     => 8,
			'redirection' => 0,
			'headers'     => array( 'Content-Type' => 'application/json' ),
			'body'        => wp_json_encode( roama_leads_sheet_payload( $lead, $secret ) ),
			'data_format' => 'body',
		)
	);
	if ( is_wp_error( $response ) ) {
		return 'Sheet: ' . $response->get_error_message();
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( in_array( $code, array( 301, 302, 303, 307, 308 ), true ) ) {
		$location = wp_remote_retrieve_header( $response, 'location' );
		$location = is_array( $location ) ? (string) reset( $location ) : (string) $location;
		if ( 0 !== strpos( $location, 'https://' ) ) {
			return sprintf( 'Sheet: HTTP %d without an https Location', $code );
		}
		$response = wp_remote_get(
			$location,
			array(
				'timeout'     => 8,
				'redirection' => 2,
			)
		);
		if ( is_wp_error( $response ) ) {
			return 'Sheet (result): ' . $response->get_error_message();
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
	}
	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	if ( 200 === $code && is_array( $data ) && ! empty( $data['ok'] ) ) {
		return true;
	}
	$why = is_array( $data ) && isset( $data['error'] ) ? (string) $data['error'] : 'no JSON answer';
	return sprintf( 'Sheet: HTTP %d, %s', $code, $why );
}

/**
 * Delivers a lead to the channels still pending (null = every configured channel).
 *
 * @param array      $lead    roama_leads_make().
 * @param array|null $pending array{telegram?: string[], sheet?: bool} left from an earlier try.
 * @return array{reached: bool, pending: array, errors: string[]} reached = at least one channel took it now.
 */
function roama_leads_deliver( array $lead, $pending = null ) {
	$config   = roama_leads_config();
	$channels = roama_leads_channels( $config );
	if ( null === $pending ) {
		$pending = array(
			'telegram' => $channels['telegram'] ? $config['chats'] : array(),
			'sheet'    => $channels['sheet'],
		);
	}
	$left    = array(
		'telegram' => array(),
		'sheet'    => false,
	);
	$errors  = array();
	$reached = false;
	if ( ! empty( $pending['telegram'] ) ) {
		$text = roama_leads_tg_message( $lead );
		foreach ( (array) $pending['telegram'] as $chat ) {
			$result = $channels['telegram'] ? roama_leads_send_telegram( $config['token'], (string) $chat, $text ) : 'Telegram is not set up';
			if ( true === $result ) {
				$reached = true;
			} else {
				$left['telegram'][] = (string) $chat;
				$errors[]           = roama_leads_redact( $result );
			}
		}
	}
	if ( ! empty( $pending['sheet'] ) ) {
		$result = $channels['sheet'] ? roama_leads_send_sheet( $config['sheet_url'], $config['secret'], $lead ) : 'The sheet is not set up';
		if ( true === $result ) {
			$reached = true;
		} else {
			$left['sheet'] = true;
			$errors[]      = roama_leads_redact( $result );
		}
	}
	return array(
		'reached' => $reached,
		'pending' => $left,
		'errors'  => $errors,
	);
}

/**
 * True when nothing is left to send.
 *
 * @param array $pending array{telegram: string[], sheet: bool}.
 */
function roama_leads_done( array $pending ) {
	return empty( $pending['telegram'] ) && empty( $pending['sheet'] );
}

/**
 * The retry queue (an option, not autoloaded).
 *
 * @return array<int, array{lead: array, pending: array, attempts: int}>
 */
function roama_leads_queue() {
	$queue = get_option( ROAMA_LEADS_QUEUE, array() );
	return is_array( $queue ) ? array_values( $queue ) : array();
}

/**
 * Saves the queue and keeps the hourly WP-Cron event only while it has items.
 *
 * @param array $queue Items.
 */
function roama_leads_save_queue( array $queue ) {
	$queue = array_slice( array_values( $queue ), -ROAMA_LEADS_QUEUE_MAX );
	update_option( ROAMA_LEADS_QUEUE, $queue, false );
	if ( $queue && ! wp_next_scheduled( ROAMA_LEADS_CRON ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', ROAMA_LEADS_CRON );
	} elseif ( ! $queue ) {
		wp_clear_scheduled_hook( ROAMA_LEADS_CRON );
	}
}

/**
 * Puts what did not reach its channel into the queue.
 *
 * @param array $lead    roama_leads_make().
 * @param array $pending What is left.
 */
function roama_leads_enqueue( array $lead, array $pending ) {
	$queue   = roama_leads_queue();
	$queue[] = array(
		'lead'     => $lead,
		'pending'  => $pending,
		'attempts' => 1,
	);
	roama_leads_save_queue( $queue );
}

/**
 * Sends a lead now; failures are logged and queued. Returns whether anyone got it now.
 *
 * @param array $lead roama_leads_make().
 */
function roama_leads_process( array $lead ) {
	$result = roama_leads_deliver( $lead );
	foreach ( $result['errors'] as $error ) {
		roama_leads_log_error( 'send', $error );
	}
	if ( ! roama_leads_done( $result['pending'] ) ) {
		roama_leads_enqueue( $lead, $result['pending'] );
	}
	return $result['reached'];
}

/**
 * One retry round (WP-Cron, hourly): each item gets one more attempt, after
 * ROAMA_LEADS_MAX_ATTEMPTS it is dropped (Flamingo still has the lead).
 *
 * @return array{sent: int, left: int, dropped: int}
 */
function roama_leads_retry() {
	$left  = array();
	$stats = array(
		'sent'    => 0,
		'left'    => 0,
		'dropped' => 0,
	);
	foreach ( roama_leads_queue() as $item ) {
		if ( empty( $item['lead']['id'] ) || ! isset( $item['pending'] ) ) {
			continue;
		}
		$result = roama_leads_deliver( $item['lead'], (array) $item['pending'] );
		if ( roama_leads_done( $result['pending'] ) ) {
			++$stats['sent'];
			continue;
		}
		$item['pending']  = $result['pending'];
		$item['attempts'] = (int) ( $item['attempts'] ?? 1 ) + 1;
		if ( $item['attempts'] >= ROAMA_LEADS_MAX_ATTEMPTS ) {
			++$stats['dropped'];
			roama_leads_log_error( 'queue', sprintf( 'Lead %s was not delivered after %d attempts (it is in Flamingo): %s', $item['lead']['id'], ROAMA_LEADS_MAX_ATTEMPTS, implode( '; ', $result['errors'] ) ) );
			continue;
		}
		$left[] = $item;
	}
	$stats['left'] = count( $left );
	roama_leads_save_queue( $left );
	return $stats;
}
