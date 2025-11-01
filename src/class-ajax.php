<?php
/**
 * Ajax class file.
 *
 * @package Meloniq\GpOpenaiTranslate
 */

namespace Meloniq\GpOpenaiTranslate;

/**
 * Ajax class.
 */
class Ajax {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_ajax_gpoai_translate', array( $this, 'translate' ), 10 );
	}

	/**
	 * Translate a string.
	 *
	 * @return void
	 */
	public function translate() {
		global $gpoai_translate;

		if ( ! isset( $gpoai_translate ) ) {
			wp_send_json(
				array(
					'success' => false,
					'error'   => array(
						'message' => 'GlotPress not yet loaded.',
						'reason'  => '',
					),
				)
			);
		}

		if ( ! isset( $_POST['original'] ) || ! isset( $_POST['locale'] ) ) {
			wp_send_json(
				array(
					'success' => false,
					'error'   => array(
						'message' => 'Missing parameters.',
						'reason'  => '',
					),
				)
			);
		}

		if ( ! isset( $_POST['nonce'] ) ) {
			wp_send_json(
				array(
					'success' => false,
					'error'   => array(
						'message' => 'Missing nonce.',
						'reason'  => '',
					),
				)
			);
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'gpoai_nonce' ) ) {
			wp_send_json(
				array(
					'success' => false,
					'error'   => array(
						'message' => 'Invalid nonce.',
						'reason'  => '',
					),
				)
			);
		}

		$locale = sanitize_text_field( wp_unslash( $_POST['locale'] ) );
		$string = sanitize_text_field( wp_unslash( $_POST['original'] ) );

		$translate  = Translate::instance();
		$new_string = $translate->translate( $string, $locale );

		if ( is_wp_error( $new_string ) ) {
			$response = array(
				'success' => false,
				'error'   => array(
					'message' => $new_string->get_error_message(),
					'reason'  => $new_string->get_error_data(),
				),
			);
		} else {
			$response = array(
				'success' => true,
				'data'    => array( 'translatedText' => $new_string ),
			);
		}

		wp_send_json( $response );
	}
}
