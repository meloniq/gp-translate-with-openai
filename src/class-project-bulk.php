<?php
/**
 * Project Bulk class file.
 *
 * @package Meloniq\GpOpenaiTranslate
 */

namespace Meloniq\GpOpenaiTranslate;

use GP;
use GP_Project;
use GP_Locales;
use GP_Translation;
use GP_Translation_Set;

/**
 * Project Bulk class.
 */
class Project_Bulk {

	/**
	 * API.
	 *
	 * @var mixed
	 */
	public $api;

	/**
	 * Last method called.
	 *
	 * @var string
	 */
	public $last_method_called;

	/**
	 * Class name.
	 *
	 * @var string
	 */
	public $class_name;

	/**
	 * Request running.
	 *
	 * @var bool
	 */
	public $request_running;

	/**
	 * Singleton instance.
	 *
	 * @var Project_Bulk
	 */
	private static $instance;

	/**
	 * Get the singleton instance.
	 *
	 * @return Project_Bulk
	 */
	public static function instance(): Project_Bulk {
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
		// Check if the API key is set, if not, don't do anything.
		if ( ! Config::get_api_key() ) {
			return;
		}

		add_action( 'gp_project_actions', array( $this, 'gp_project_actions' ), 10, 2 );
		add_action( 'gp_init', array( $this, 'gp_init' ), 11 );
	}

	/**
	 * Initialize on GlotPress init.
	 *
	 * @return void
	 */
	public function gp_init() {
		// Add the routes directly to the global GP_Router object.
		GP::$router->add( '/openai-bulk-translate/(.+?)', array( $this, 'bulk_translate_project' ), 'get' );
		GP::$router->add( '/openai-bulk-translate/(.+?)', array( $this, 'bulk_translate_project' ), 'post' );
	}

	/**
	 * Adds option to projects menu.
	 *
	 * @param array  $actions The current actions array.
	 * @param object $project The current project object.
	 *
	 * @return array The updated actions array.
	 */
	public function gp_project_actions( array $actions, object $project ): array {
		// Check if the user has the permissions to write to the project.
		if ( ! GP::$permission->user_can( wp_get_current_user(), 'write', 'project' ) ) {
			return $actions;
		}

		$actions['openai'] = gp_link_get( gp_url( 'openai-bulk-translate/' . $project->slug ), __( 'OpenAI Translate', GP_OAI_TD ) );

		return $actions;
	}

	/**
	 * Handles bulk translation of entire project.
	 *
	 * @param string $project_path The project path to translate.
	 *
	 * @return void
	 */
	public function bulk_translate_project( $project_path ): void {
		// First let's ensure we have decoded the project path for use later.
		$project_path = urldecode( $project_path );

		// Get the URL to the project for use later.
		// TODO: Do not work with subprojects.
		$url = gp_url_project( $project_path );

		// If we don't have rights, just redirect back to the project.
		if ( ! GP::$permission->user_can( wp_get_current_user(), 'write', 'project' ) ) {
			wp_safe_redirect( $url );
		}

		// Create a project class to use to get the project object.
		$project_class = new GP_Project();

		// Get the project object from the project path that was passed in.
		// TODO: Do not work with subprojects.
		$project_obj = $project_class->by_path( $project_path );
		if ( ! is_object( $project_obj ) ) {
			wp_safe_redirect( $url );
		}

		// Get the translations sets from the project ID.
		$translation_sets = GP::$translation_set->by_project_id( $project_obj->id );

		// Since there might be a lot of translations to process in a batch, let's setup some time limits
		// to make sure we don't give a white screen of death to the user.
		$time_start    = microtime( true );
		$max_exec_time = ini_get( 'max_execution_time' ) * 0.7;

		// Loop through all the sets.
		foreach ( $translation_sets as $set ) {
			// Check to see how our time is doing, if we're over out time limit, stop processing.
			if ( microtime( true ) - $time_start > $max_exec_time ) {
				gp_notice_set( __( 'Not all strings translated as we ran out of execution time!', GP_OAI_TD ) );
				break;
			}

			// Get the locale we're working with.
			$locale = GP_Locales::by_slug( $set->locale );

			// If OpenAI doesn't support this locale, skip it.
			if ( ! Locales::is_supported( $locale->slug ) ) {
				continue;
			}
			// Create a template array to pass in to the worker function at the end of the loop.
			$bulk = array(
				'action'   => 'gp_openai_translate',
				'priority' => 0,
				'row-ids'  => array(),
			);

			// Create a new GP_Translation object to use.
			$translation = new GP_Translation();

			// Get the strings for the current translation.
			$strings = $translation->for_translation( $project_obj, $set, 'no-limit', array( 'status' => 'untranslated' ) );

			// Add the strings to the $bulk template we setup earlier.
			foreach ( $strings as $string ) {
				$bulk['row-ids'][] .= $string->row_id;
			}

			// If we don't have any strings to translate, don't bother calling the translation function.
			if ( count( $bulk['row-ids'] ) > 0 ) {
				$translate = Translate::instance();
				$translate->gp_translation_set_bulk_action_post( $project_obj, $locale, $set, $bulk );
			}
		}

		// Redirect back to the project home.
		wp_safe_redirect( $url );
	}

	/**
	 * Compatibility function required by GP_Router.
	 *
	 * @return void
	 */
	public function before_request() {
		// Do nothing.
	}

	/**
	 * Compatibility function required by GP_Router.
	 *
	 * @return void
	 */
	public function after_request() {
		// Do nothing.
	}
}
