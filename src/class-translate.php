<?php
/**
 * Translate class file.
 *
 * @package Meloniq\GpOpenaiTranslate
 */

namespace Meloniq\GpOpenaiTranslate;

use Orhanerday\OpenAi\OpenAi;
use GP;
use GP_Locales;
use WP_Error;

/**
 * Translate class.
 */
class Translate {

	/**
	 * Singleton instance.
	 *
	 * @var Translate
	 */
	private static $instance;

	/**
	 * Get the singleton instance.
	 *
	 * @return Translate
	 */
	public static function instance(): Translate {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * This function is used to bulk translate a set of strings.
	 *
	 * @param string|object $locale The locale to translate to.
	 * @param array         $strings The strings to translate.
	 *
	 * @return array|WP_Error The translated strings or an error.
	 */
	public function translate_batch( $locale, $strings ) {
		if ( is_object( $locale ) ) {
			$locale = $locale->slug;
		}

		return $this->openai_translate_batch( $locale, $strings );
	}

	/**
	 * Translate the text (Source language is always English).
	 *
	 * @param string $text   The text to translate.
	 * @param string $locale The locale to translate to.
	 *
	 * @return string
	 */
	public function translate( string $text, string $locale ): string {
		// Check if the locale is supported.
		if ( ! Locales::is_supported( $locale ) ) {
			return $text;
		}

		$api_key = Config::get_api_key();
		$openai  = new OpenAi( $api_key );

		// get locale object.
		$locale_obj = GP_Locales::by_slug( $locale );
		if ( ! $locale_obj ) {
			return new WP_Error( 'gp_set_no_locale', 'Locale not found!' );
		}

		// get prompt.
		$base_prompt   = sprintf( 'Translate the following text to %s language: ', $locale_obj->english_name );
		$custom_prompt = Config::get_custom_prompt();
		$prompt        = $custom_prompt . ' ' . $base_prompt . ' ' . $text;

		// build request.
		$request = array(
			'model'             => Config::get_model(),
			'messages'          => array(
				array(
					'role'    => 'user',
					'content' => $prompt,
				),
			),
			'temperature'       => Config::get_temperature(),
			'max_tokens'        => 1000,
			'frequency_penalty' => 0,
			'presence_penalty'  => 0,
		);

		// get response.
		$chat     = $openai->chat( $request );
		$response = json_decode( $chat );

		// check for api error.
		if ( isset( $response->error->code ) ) {
			// insufficient_quota, context_length_exceeded.
			return $text;
		}

		// check if response is valid.
		if ( ! $response || ! isset( $response->choices ) || ! is_array( $response->choices ) ) {
			return $text;
		}

		// get translation.
		$translation = $response->choices[0]->message->content;

		// check if something has left.
		if ( empty( $translation ) ) {
			return $text;
		}

		return $translation;
	}

	/**
	 * This function is used to bulk translate a set of strings using OpenAI.
	 *
	 * @param string $locale The locale to translate to.
	 * @param array  $strings The strings to translate.
	 *
	 * @return array|WP_Error The translated strings or an error.
	 */
	protected function openai_translate_batch( $locale, $strings ) {
		if ( ! Locales::is_supported( $locale ) ) {
			return new WP_Error( 'gpoai_translate', sprintf( "The locale %s isn't supported by OpenAI.", $locale ) );
		}

		// If we don't have any strings, throw an error.
		if ( count( $strings ) === 0 ) {
			return new WP_Error( 'gpoai_translate', 'No strings found to translate.' );
		}

		// If we have too many strings, throw an error.
		if ( count( $strings ) > 50 ) {
			return new WP_Error( 'gpoai_translate', 'Only 50 strings allowed.' );
		}

		$translated_strings = array();
		foreach ( $strings as $string ) {
			$translated_strings[] = $this->translate( $string, $locale );
		}

		// Merge the originals and translations arrays.
		$items = gp_array_zip( $strings, $translated_strings );
		if ( ! $items ) {
			return new WP_Error( 'gpoai_translate', 'Error merging arrays' );
		}

		// Loop through the items and clean up the responses.
		$translations = array();
		foreach ( $items as $item ) {
			list( $string, $translation ) = $item;
			$translation                  = $this->clean_translation( $translation );
			$translations[]               = $translation;
		}

		return $translations;
	}

	/**
	 * Cleans up the translation string.
	 *
	 * @param string $text The string to clean.
	 *
	 * @return string
	 */
	protected function clean_translation( $text ) {
		$text = preg_replace_callback(
			'/% (s|d)/i',
			function ( $m ) { // phpcs:ignore
				return '"%".strtolower($m[1])';
			},
			$text
		);
		$text = preg_replace_callback(
			'/% (\d+) \$ (s|d)/i',
			function ( $m ) { // phpcs:ignore
				return '"%".$m[1]."\\$".strtolower($m[2])';
			},
			$text
		);

		return $text;
	}

	/**
	 * Handles bulk translation action.
	 *
	 * @param object $project The current project object.
	 * @param object $locale The current locale object.
	 * @param object $translation_set The current translation set object.
	 * @param array  $bulk The current bulk action array.
	 *
	 * @return void
	 */
	public function gp_translation_set_bulk_action_post( $project, $locale, $translation_set, $bulk ) {
		// Status counters.
		$count            = array();
		$count['err_api'] = 0;
		$count['err_add'] = 0;
		$count['added']   = 0;
		$count['skipped'] = 0;

		$singulars    = array();
		$original_ids = array();

		// Loop through each of the passed in strings and translate them.
		foreach ( $bulk['row-ids'] as $row_id ) {
			// Split the $row_id by '-' and get the first one (which will be the id of the original).
			$original_id = gp_array_get( explode( '-', $row_id ), 0 );
			// Get the original based on the above id.
			$original = GP::$original->get( $original_id );

			// If there is no original or it's a plural, skip it.
			if ( ! $original || $original->plural ) {
				++$count['skipped'];
				continue;
			}

			// Add the original to the queue to translate.
			$singulars[]    = $original->singular;
			$original_ids[] = $original_id;
		}

		// Translate all the originals that we found.
		// $translate = Translate::instance();.
		$results = $this->translate_batch( $locale, $singulars );

		// Did we get an error?
		if ( is_wp_error( $results ) ) {
			gp_notice_set( $results->get_error_message(), 'error' );
			return;
		}

		// Merge the results back in to the original id's and singulars
		// This will create an array like ($items = array( array( id, single, result), array( id, single, result), ... ).
		$items = gp_array_zip( $original_ids, $singulars, $results );

		// If we have no items, something went wrong and stop processing.
		if ( ! $items ) {
			return;
		}

		// Loop through the items and store them in the database.
		foreach ( $items as $item ) {
			// Break up the item back in to individual components.
			list( $original_id, $singular, $translation ) = $item;

			// Did we get an error?
			if ( is_wp_error( $translation ) ) {
				++$count['err_api'];
				continue;
			}

			$warnings = GP::$translation_warnings->check( $singular, null, array( $translation ), $locale );

			// Build a data array to store.
			$data                       = array();
			$data['original_id']        = $original_id;
			$data['user_id']            = get_current_user_id();
			$data['translation_set_id'] = $translation_set->id;
			$data['translation_0']      = $translation;
			$data['status']             = 'fuzzy';
			$data['warnings']           = $warnings;

			// Insert the item in to the database.
			$inserted = GP::$translation->create( $data );
			if ( $inserted ) {
				++$count['added'];
			} else {
				++$count['err_add'];
			}
		}

		$this->set_bulk_action_notice( $count );
	}

	/**
	 * Set notice for bulk action.
	 *
	 * @param array $count The count array.
	 *
	 * @return void
	 */
	protected function set_bulk_action_notice( $count ) {
		// If there are no errors, display how many translations were added.
		if ( 0 === $count['err_api'] && 0 === $count['err_add'] ) {
			// translators: %d is the number of translations added.
			gp_notice_set( sprintf( __( '%d fuzzy translation from OpenAI were added.', 'gp-translate-with-openai' ), $count['added'] ) );
			return;
		}

		$messages = array();

		if ( $count['added'] ) {
			// translators: %d is the number of translations added.
			$messages[] = sprintf( __( 'Added: %d.', 'gp-translate-with-openai' ), $count['added'] );
		}

		if ( $count['err_api'] ) {
			// translators: %d is the number of errors from OpenAI.
			$messages[] = sprintf( __( 'Error from OpenAI: %d.', 'gp-translate-with-openai' ), $count['err_api'] );
		}

		if ( $count['err_add'] ) {
			// translators: %d is the number of errors adding translations.
			$messages[] = sprintf( __( 'Error adding: %d.', 'gp-translate-with-openai' ), $count['err_add'] );
		}

		if ( $count['skipped'] ) {
			// translators: %d is the number of skipped translations.
			$messages[] = sprintf( __( 'Skipped: %d.', 'gp-translate-with-openai' ), $count['skipped'] );
		}

		// Create a message string and add it to the GlotPress notices.
		gp_notice_set( implode( ' ', $messages ), 'error' );
	}
}
