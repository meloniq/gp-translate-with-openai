<?php
/**
 * Frontend class file.
 *
 * @package Meloniq\GpOpenaiTranslate
 */

namespace Meloniq\GpOpenaiTranslate;

use GP;

/**
 * Frontend class.
 */
class Frontend {

	/**
	 * Is locale supported.
	 *
	 * @var bool
	 */
	protected $is_locale_supported = false;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// Check if the API key is set, if not, don't do anything.
		if ( ! Config::get_api_key() ) {
			return;
		}

		// Add the actions to handle adding the translate menu to the various parts of GlotPress.
		add_action( 'gp_pre_tmpl_load', array( $this, 'gp_pre_tmpl_load' ), 10, 2 );
		add_filter( 'gp_entry_actions', array( $this, 'gp_entry_actions' ), 10, 1 );
		add_action( 'gp_translation_set_bulk_action', array( $this, 'gp_translation_set_bulk_action' ), 10, 1 );
		add_action( 'gp_translation_set_bulk_action_post', array( $this, 'gp_translation_set_bulk_action_post' ), 10, 4 );
	}

	/**
	 * This function loads the javascript when required.
	 *
	 * @param string $template The current template.
	 * @param array  $args The current arguments.
	 *
	 * @return void
	 */
	public function gp_pre_tmpl_load( $template, $args ) {
		// Check if we are on the translation template.
		if ( 'translations' !== $template ) {
			return;
		}

		// Is locale supported?
		$this->is_locale_supported = Locales::is_supported( $args['locale']->slug );
		if ( ! $this->is_locale_supported ) {
			return;
		}

		// Create options for the localization script.
		$options = array(
			'locale'  => $args['locale']->slug,
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'gpoai_nonce' ),
		);

		wp_register_script( 'gp-translate-with-openai-js', plugins_url( 'assets/gpoai_translate.js', __DIR__ ), array( 'jquery', 'editor', 'gp-common' ), '1.0', true );
		gp_enqueue_script( 'gp-translate-with-openai-js' );
		wp_localize_script( 'gp-translate-with-openai-js', 'gpoai_translate', $options );
	}

	/**
	 * Adds option to individual translation entries.
	 *
	 * @param array $actions The current actions array.
	 *
	 * @return array The updated actions array.
	 */
	public function gp_entry_actions( array $actions ): array {
		if ( ! $this->is_locale_supported ) {
			return $actions;
		}

		$actions[] = '<a href="#" class="gpoai_translate" tabindex="-1">' . esc_html__( 'Translate with OpenAI', 'gp-translate-with-openai' ) . '</a><br>';

		return $actions;
	}

	/**
	 * Adds option to the bulk actions dropdown.
	 *
	 * @return void
	 */
	public function gp_translation_set_bulk_action(): void {
		if ( ! $this->is_locale_supported ) {
			return;
		}

		echo '<option value="gpoai_translate">' . esc_html__( 'Translate with OpenAI', 'gp-translate-with-openai' ) . '</option>';
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
	public function gp_translation_set_bulk_action_post( $project, $locale, $translation_set, $bulk ): void {
		// Check if the action is the one we are looking for.
		if ( 'gpoai_translate' !== $bulk['action'] ) {
			return;
		}

		$translate = Translate::instance();
		$translate->gp_translation_set_bulk_action_post( $project, $locale, $translation_set, $bulk );
	}
}
