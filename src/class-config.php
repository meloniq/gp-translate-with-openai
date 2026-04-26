<?php
/**
 * Config class file.
 *
 * @package Meloniq\GpOpenaiTranslate
 */

namespace Meloniq\GpOpenaiTranslate;

/**
 * Config class.
 */
class Config {

	/**
	 * Get API key.
	 *
	 * @return string
	 */
	public static function get_api_key(): string {
		// User API key has priority.
		if ( self::get_user_api_key() ) {
			return self::get_user_api_key();
		}

		// Get the global key.
		return get_option( 'gpoai_api_key' );
	}

	/**
	 * Get user API key.
	 *
	 * @return string
	 */
	public static function get_user_api_key(): string {
		$user_id = self::get_current_user_id();

		// No user ID.
		if ( ! $user_id ) {
			return '';
		}

		// Get the user meta.
		$user_api_key = get_user_meta( $user_id, 'gpoai_api_key', true );

		return $user_api_key;
	}

	/**
	 * Get available models.
	 *
	 * @return array
	 */
	public static function get_available_models(): array {
		$models = array(
			'gpt-3.5-turbo',
			'gpt-4',
			'gpt-4-turbo',
			'gpt-4o',
			'gpt-4o-mini',
			'gpt-5-mini',
		);

		return apply_filters( 'gpoai_available_models', $models );
	}

	/**
	 * Get the model.
	 *
	 * @return string
	 */
	public static function get_model(): string {
		// User model has priority.
		if ( self::get_user_model() ) {
			return self::get_user_model();
		}

		// Get the global model.
		return get_option( 'gpoai_model' );
	}

	/**
	 * Get user model.
	 *
	 * @return string
	 */
	public static function get_user_model(): string {
		$user_id = self::get_current_user_id();

		// No user ID.
		if ( ! $user_id ) {
			return '';
		}

		// Get the user meta.
		$user_model = get_user_meta( $user_id, 'gpoai_model', true );

		return $user_model;
	}

	/**
	 * Get the temperature.
	 *
	 * @return float
	 */
	public static function get_temperature(): float {
		// User temperature has priority.
		if ( self::get_user_temperature() ) {
			return self::get_user_temperature();
		}

		// Get the global temperature.
		return (float) get_option( 'gpoai_temperature' );
	}

	/**
	 * Get user temperature.
	 *
	 * @return float
	 */
	public static function get_user_temperature(): float {
		$user_id = self::get_current_user_id();

		// No user ID.
		if ( ! $user_id ) {
			return 0.0;
		}

		// Get the user meta.
		$user_temperature = get_user_meta( $user_id, 'gpoai_temperature', true );

		return (float) $user_temperature;
	}

	/**
	 * Get custom prompt.
	 *
	 * @return string
	 */
	public static function get_custom_prompt(): string {
		// User custom prompt has priority.
		if ( self::get_user_custom_prompt() ) {
			return self::get_user_custom_prompt();
		}

		// Get the global custom prompt.
		return get_option( 'gpoai_custom_prompt' );
	}

	/**
	 * Get user custom prompt.
	 *
	 * @return string
	 */
	public static function get_user_custom_prompt(): string {
		$user_id = self::get_current_user_id();

		// No user ID.
		if ( ! $user_id ) {
			return '';
		}

		// Get the user meta.
		$user_custom_prompt = get_user_meta( $user_id, 'gpoai_custom_prompt', true );

		return $user_custom_prompt;
	}

	/**
	 * Get current user ID.
	 *
	 * @return int
	 */
	public static function get_current_user_id(): int {
		$user_id = 0;

		if ( is_user_logged_in() ) {
			$user    = wp_get_current_user();
			$user_id = $user->ID;
		}

		return $user_id;
	}

	/**
	 * Get default prompt.
	 *
	 * @return string
	 */
	public static function get_default_prompt(): string {
		$prompt_arr = array(
			'You are a professional translation engine.',
			'Translate the text from {SOURCE_LANGUAGE} to {TARGET_LANGUAGE}.',
			'Return ONLY the translated text.',
			'Use natural language.',
			'Do not explain.',
			'Do not add quotes.',
			'Preserve punctuation, formatting, HTML, placeholders (%s, %1$s, %2$d), and variables exactly.',
			'{GLOSSARY}',
		);

		return implode( "\n", $prompt_arr );
	}
}
