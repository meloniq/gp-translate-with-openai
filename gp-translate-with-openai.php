<?php
/**
 * Plugin Name:       GP Translate with OpenAI
 * Plugin URI:        https://blog.meloniq.net/gp-translate-with-openai
 *
 * Description:       GlotPress Translate with OpenAI.
 * Tags:              glotpress, translate, machine translate, openai, chatgpt
 *
 * Requires at least: 4.9
 * Requires PHP:      7.4
 * Version:           1.1
 *
 * Author:            MELONIQ.NET
 * Author URI:        https://meloniq.net/
 *
 * License:           GPLv2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Text Domain:       gp-translate-with-openai
 *
 * @package Meloniq\GpOpenaiTranslate
 */

namespace Meloniq\GpOpenaiTranslate;

// If this file is accessed directly, then abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'GPOAI_TD', 'gp-translate-with-openai' );
define( 'GPOAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GPOAI_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Setup plugin data.
 *
 * @return void
 */
function setup() {
	global $gpoai_translate;

	// load openai lib
	// https://github.com/orhanerday/open-ai .
	require_once trailingslashit( __DIR__ ) . 'vendor/autoload.php';

	require_once trailingslashit( __DIR__ ) . 'src/class-config.php';
	require_once trailingslashit( __DIR__ ) . 'src/class-locales.php';
	require_once trailingslashit( __DIR__ ) . 'src/class-translate.php';

	require_once trailingslashit( __DIR__ ) . 'src/class-admin-page.php';
	require_once trailingslashit( __DIR__ ) . 'src/class-settings.php';
	require_once trailingslashit( __DIR__ ) . 'src/class-profile.php';
	require_once trailingslashit( __DIR__ ) . 'src/class-frontend.php';
	require_once trailingslashit( __DIR__ ) . 'src/class-ajax.php';

	$gpoai_translate['admin-page'] = new Admin_Page();
	$gpoai_translate['settings']   = new Settings();
	$gpoai_translate['profile']    = new Profile();
	$gpoai_translate['frontend']   = new Frontend();
	$gpoai_translate['ajax']       = new Ajax();

	// Do not work with subprojects.
	// Not sure if translating a whole project is a good idea.
	require_once trailingslashit( __DIR__ ) . 'src/class-project-bulk.php';
	$gp_openai_translate['project-bulk'] = new Project_Bulk();
}
add_action( 'after_setup_theme', 'Meloniq\GpOpenaiTranslate\setup' );
