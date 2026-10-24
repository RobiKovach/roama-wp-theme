<?php
/*! © ROAMA. Licensed under GPL-2.0-or-later. */
// Contact Form 7: the theme's own form markup (BEM block contact-form, ROAMA tokens in
// css/theme.css), CF7 CSS and autop off, a honeypot, the privacy line under the button,
// the phone check, and the form definition the importer creates (roama_cf7_create_form()).

defined( 'ABSPATH' ) || exit;

define( 'ROAMA_CF7_TITLE', 'ROAMA: контактна форма' );
define( 'ROAMA_HONEYPOT', 'roama-website' );

// The theme styles every state itself; CF7's own CSS and its automatic <p>/<br> are off.
add_filter( 'wpcf7_load_css', '__return_false' );
add_filter( 'wpcf7_autop_or_not', '__return_false' );

/**
 * Every CF7 form on the site gets the theme's block class.
 *
 * @param string $class Classes of the <form>.
 */
function roama_cf7_form_class( $class ) {
	return trim( $class . ' contact-form' );
}
add_filter( 'wpcf7_form_class_attr', 'roama_cf7_form_class' );

/**
 * The wrap CF7 puts around each control gets the theme's class (styled display:block,
 * as the control inside: an inline-block textarea would sit on the baseline).
 *
 * @param string $html Form elements.
 */
function roama_cf7_form_elements( $html ) {
	return preg_replace( '/class="wpcf7-form-control-wrap"/', 'class="wpcf7-form-control-wrap contact-form__wrap"', (string) $html );
}
add_filter( 'wpcf7_form_elements', 'roama_cf7_form_elements' );

/**
 * Form tags of the theme: [roama_honeypot] and [roama_privacy].
 */
function roama_cf7_tags() {
	if ( ! function_exists( 'wpcf7_add_form_tag' ) ) {
		return;
	}
	wpcf7_add_form_tag( 'roama_honeypot', 'roama_cf7_honeypot_tag' );
	wpcf7_add_form_tag( 'roama_privacy', 'roama_cf7_privacy_tag' );
}
add_action( 'wpcf7_init', 'roama_cf7_tags' );

/**
 * A field people never see or reach; bots fill it in. Filled = spam (roama_cf7_spam()).
 */
function roama_cf7_honeypot_tag() {
	return sprintf(
		'<span class="contact-form__hp" aria-hidden="true"><label>%1$s <input type="text" name="%2$s" value="" tabindex="-1" autocomplete="off"></label></span>',
		esc_html__( 'Не заповнюйте це поле', 'roama' ),
		esc_attr( ROAMA_HONEYPOT )
	);
}

/**
 * The consent line under the button, with the privacy policy page of Settings → Privacy.
 */
function roama_cf7_privacy_tag() {
	$url = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
	if ( '' === $url ) {
		return esc_html__( 'Надсилаючи форму, ви погоджуєтеся на обробку ваших даних, щоб ми могли відповісти.', 'roama' );
	}
	return sprintf(
		/* translators: %s: link to the privacy policy */
		esc_html__( 'Надсилаючи форму, ви погоджуєтеся з %s.', 'roama' ),
		sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'політикою конфіденційності', 'roama' ) )
	);
}

/**
 * A filled honeypot marks the submission as spam: no mail, no lead.
 *
 * @param bool $spam Spam so far.
 */
function roama_cf7_spam( $spam ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- CF7 checks the request.
	$value = isset( $_POST[ ROAMA_HONEYPOT ] ) ? trim( (string) wp_unslash( $_POST[ ROAMA_HONEYPOT ] ) ) : '';
	if ( '' === $value ) {
		return $spam;
	}
	$submission = class_exists( 'WPCF7_Submission' ) ? WPCF7_Submission::get_instance() : null;
	if ( $submission && method_exists( $submission, 'add_spam_log' ) ) {
		$submission->add_spam_log(
			array(
				'agent'  => 'roama_honeypot',
				'reason' => 'The hidden field was filled in.',
			)
		);
	}
	return true;
}
add_filter( 'wpcf7_spam', 'roama_cf7_spam', 5 );

/**
 * Digits of a Ukrainian phone in the international form (380XXXXXXXXX), '' when it is not one.
 * Accepts what the mask of js/contact-form.js prints and what people paste (0XX…, 380XX…, +38 …).
 *
 * @param string $phone As typed.
 */
function roama_phone_digits( $phone ) {
	$digits = preg_replace( '/\D+/', '', (string) $phone );
	if ( 10 === strlen( $digits ) && '0' === $digits[0] ) {
		$digits = '38' . $digits;
	}
	return ( 12 === strlen( $digits ) && 0 === strpos( $digits, '380' ) ) ? $digits : '';
}

/**
 * The phone field must be a whole number, not only «digits and spaces».
 *
 * @param WPCF7_Validation $result Validation result.
 * @param WPCF7_FormTag    $tag    The tel tag.
 */
function roama_cf7_validate_phone( $result, $tag ) {
	if ( 'roama-phone' !== $tag->name ) {
		return $result;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- CF7 checks the request.
	$value = isset( $_POST[ $tag->name ] ) ? trim( (string) wp_unslash( $_POST[ $tag->name ] ) ) : '';
	if ( '' !== $value && '' === roama_phone_digits( $value ) ) {
		$result->invalidate( $tag, __( 'Вкажіть номер повністю, наприклад +38 (067) 123 45 67.', 'roama' ) );
	}
	return $result;
}
add_filter( 'wpcf7_validate_tel', 'roama_cf7_validate_phone', 20, 2 );
add_filter( 'wpcf7_validate_tel*', 'roama_cf7_validate_phone', 20, 2 );

/**
 * The phone mask (js/contact-form.js) on pages that print a form.
 */
function roama_cf7_enqueue_mask() {
	wp_enqueue_script(
		'roama-contact-form',
		get_theme_file_uri( 'js/contact-form.js' ),
		array(),
		roama_asset_version( 'js/contact-form.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}

/**
 * Labels of the form's fields (used by the leads: Telegram, the sheet).
 *
 * @return array<string, string>
 */
function roama_cf7_labels() {
	return array(
		'roama-name'    => __( 'Ім’я', 'roama' ),
		'roama-phone'   => __( 'Телефон', 'roama' ),
		'roama-email'   => __( 'Email', 'roama' ),
		'roama-message' => __( 'Повідомлення', 'roama' ),
	);
}

/**
 * The definition of the theme's form: markup, mail, messages, Flamingo settings.
 *
 * @return array{title: string, form: string, mail: array, messages: array, additional_settings: string}
 */
function roama_cf7_form_definition() {
	$form = <<<'FORM'
<label class="contact-form__field"><span class="contact-form__label">Ім’я</span>
[text* roama-name autocomplete:name maxlength:80 class:contact-form__control]</label>
<label class="contact-form__field"><span class="contact-form__label">Телефон</span>
[tel* roama-phone autocomplete:tel maxlength:24 class:contact-form__control placeholder "+38 (0XX) XXX XX XX"]</label>
<label class="contact-form__field contact-form__field--wide"><span class="contact-form__label">Email <span class="contact-form__optional">(необов’язково)</span></span>
[email roama-email autocomplete:email maxlength:120 class:contact-form__control]</label>
<label class="contact-form__field contact-form__field--wide"><span class="contact-form__label">Повідомлення <span class="contact-form__optional">(необов’язково)</span></span>
[textarea roama-message maxlength:2000 class:contact-form__control]</label>
[roama_honeypot]
<div class="contact-form__actions">[submit class:button class:contact-form__submit "Надіслати"]</div>
<p class="contact-form__consent">[roama_privacy]</p>
FORM;

	$body = "Ім’я: [roama-name]\nТелефон: [roama-phone]\nEmail: [roama-email]\n\nПовідомлення:\n[roama-message]\n\n-- \nФорма на сайті [_site_title] ([_url])";

	return array(
		'title'               => ROAMA_CF7_TITLE,
		'form'                => $form,
		'mail'                => array(
			'active'             => true,
			'subject'            => 'Заявка з сайту [_site_title]: [roama-name]',
			'sender'             => '[_site_title] <wordpress@[_site_domain]>',
			'recipient'          => '[_site_admin_email]',
			'body'               => $body,
			'additional_headers' => 'Reply-To: [roama-email]',
			'attachments'        => '',
			'use_html'           => false,
			'exclude_blank'      => true,
		),
		'messages'            => array(
			'mail_sent_ok'             => 'Дякуємо! Повідомлення надіслано, ми скоро відповімо.',
			'mail_sent_ng'             => 'Не вдалося надіслати повідомлення. Спробуйте ще раз або зателефонуйте нам.',
			'validation_error'         => 'Перевірте поля, позначені нижче, і надішліть ще раз.',
			'spam'                     => 'Не вдалося надіслати повідомлення. Спробуйте ще раз пізніше.',
			'accept_terms'             => 'Щоб надіслати форму, погодьтеся з умовами.',
			'invalid_required'         => 'Заповніть це поле.',
			'invalid_too_long'         => 'Текст задовгий.',
			'invalid_too_short'        => 'Текст закороткий.',
			'upload_failed'            => 'Не вдалося завантажити файл.',
			'upload_file_type_invalid' => 'Такий тип файлу не підходить.',
			'upload_file_too_large'    => 'Файл завеликий.',
			'upload_failed_php_error'  => 'Не вдалося завантажити файл.',
			'invalid_email'            => 'Вкажіть email у вигляді name@example.com.',
			'invalid_url'              => 'Вкажіть адресу сторінки.',
			'invalid_tel'              => 'Вкажіть номер повністю, наприклад +38 (067) 123 45 67.',
			'invalid_number'           => 'Вкажіть число.',
			'invalid_date'             => 'Вкажіть дату.',
		),
		'additional_settings' => "flamingo_email: \"[roama-email]\"\nflamingo_name: \"[roama-name]\"\nflamingo_subject: \"Заявка з сайту: [roama-name]\"",
	);
}

/**
 * Creates the theme's form once (found again by its title) and returns its ID; 0 without CF7.
 *
 * @param bool $overwrite Rewrite the definition of an existing form.
 */
function roama_cf7_create_form( $overwrite = false ) {
	if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
		return 0;
	}
	$definition = roama_cf7_form_definition();
	$existing   = get_posts(
		array(
			'post_type'      => 'wpcf7_contact_form',
			'post_status'    => 'any',
			'title'          => $definition['title'],
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( $existing && ! $overwrite ) {
		return (int) $existing[0];
	}
	$form = $existing ? WPCF7_ContactForm::get_instance( (int) $existing[0] ) : WPCF7_ContactForm::get_template( array( 'title' => $definition['title'] ) );
	if ( ! $form ) {
		return 0;
	}
	$form->set_title( $definition['title'] );
	$form->set_properties(
		array(
			'form'                => $definition['form'],
			'mail'                => array_merge( (array) $form->prop( 'mail' ), $definition['mail'] ),
			'messages'            => array_merge( (array) $form->prop( 'messages' ), $definition['messages'] ),
			'additional_settings' => $definition['additional_settings'],
		)
	);
	return (int) $form->save();
}
