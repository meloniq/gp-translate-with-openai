<?php
/**
 * Glossary class file.
 *
 * @package Meloniq\GpOpenaiTranslate
 */

namespace Meloniq\GpOpenaiTranslate;

use GP;
use GP_Glossary;
use GP_Glossary_Entry;
use GP_Locales;

/**
 * Glossary class for handling GlotPress glossary integration.
 */
class Glossary {

	/**
	 * Transient prefix for caching.
	 *
	 * @var string
	 */
	const TRANSIENT_PREFIX = 'gpoai_glossary_';

	/**
	 * Cache expiry in seconds (1 hour).
	 *
	 * @var int
	 */
	const CACHE_EXPIRY = HOUR_IN_SECONDS;

	/**
	 * Get glossary entries for a specific locale.
	 *
	 * Uses GlotPress native glossary tables directly.
	 *
	 * @param string $locale The locale slug.
	 *
	 * @return array Array of glossary entries.
	 */
	public static function get_entries_for_locale( string $locale ): array {
		// Check transient cache first.
		$cache_key = self::TRANSIENT_PREFIX . $locale;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$entries = array();

		// Require GlotPress.
		if ( ! class_exists( 'GP' ) || ! class_exists( 'GP_Glossary_Entry' ) ) {
			return $entries;
		}

		// Get the locale object from GlotPress.
		$locale_obj = GP_Locales::by_slug( $locale );
		if ( ! $locale_obj ) {
			return $entries;
		}

		// Get the locale-level glossary (project_id = 0).
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( 0, 'default', $locale );
		if ( ! $translation_set ) {
			return $entries;
		}

		$glossary = GP::$glossary->by_set_id( $translation_set->id );
		if ( ! $glossary ) {
			return $entries;
		}

		$glossary_entries = GP::$glossary_entry->find_many( array( 'glossary_id' => $glossary->id ) );
		if ( empty( $glossary_entries ) ) {
			return $entries;
		}

		foreach ( $glossary_entries as $entry ) {
			$entries[] = array(
				'term'           => $entry->term,
				'translation'    => $entry->translation,
				'part_of_speech' => $entry->part_of_speech,
				'comment'        => $entry->comment,
			);
		}

		// Cache the results.
		set_transient( $cache_key, $entries, self::CACHE_EXPIRY );

		return $entries;
	}

	/**
	 * Find matching glossary terms in the given text.
	 *
	 * @param string $text   The text to search for terms.
	 * @param string $locale The target locale.
	 *
	 * @return array Array of matching glossary entries.
	 */
	public static function find_matching_terms( string $text, string $locale ): array {
		// If GlotPress is not available, return empty array.
		if ( ! class_exists( 'GP' ) ) {
			return array();
		}

		$entries        = self::get_entries_for_locale( $locale );
		$matching_terms = array();
		$seen           = array();
		$text_lower     = mb_strtolower( $text );

		foreach ( $entries as $entry ) {
			if ( empty( $entry['term'] ) || empty( $entry['translation'] ) ) {
				continue;
			}

			// Dedup by term + translation + part_of_speech.
			$key = mb_strtolower( $entry['term'] ) . '|' . mb_strtolower( $entry['translation'] ) . '|' . ( $entry['part_of_speech'] ?? '' );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}

			$term_lower = mb_strtolower( $entry['term'] );

			// Use word boundary matching (case-insensitive).
			$pattern = '/\b' . preg_quote( $term_lower, '/' ) . '\b/ui';

			if ( preg_match( $pattern, $text_lower ) ) {
				$matching_terms[] = $entry;
				$seen[ $key ]     = true;
			}
		}

		return $matching_terms;
	}

	/**
	 * Format glossary entries for inclusion in the translation prompt.
	 *
	 * @param array $entries Array of glossary entries.
	 *
	 * @return string Formatted glossary context for the prompt.
	 */
	public static function format_for_prompt( array $entries ): string {
		if ( empty( $entries ) ) {
			return '';
		}

		$formatted_entries = array();
		foreach ( $entries as $entry ) {
			$term_string = sprintf( '"%s" = "%s"', $entry['term'], $entry['translation'] );
			if ( ! empty( $entry['part_of_speech'] ) ) {
				$term_string .= sprintf( ' (%s)', $entry['part_of_speech'] );
			}
			if ( ! empty( $entry['comment'] ) ) {
				$term_string .= sprintf( ' [%s]', $entry['comment'] );
			}
			$formatted_entries[] = $term_string;
		}

		return 'Use these glossary terms: ' . implode( ', ', $formatted_entries ) . '.';
	}
}
