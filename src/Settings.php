<?php
/**
 * Settings class file.
 *
 * @package Meloniq\GpOpenaiTranslate
 */

namespace Meloniq\GpOpenaiTranslate;

/**
 * Settings class.
 */
class Settings {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'init_settings' ), 10 );
	}

	/**
	 * Initialize settings.
	 *
	 * @return void
	 */
	public function init_settings(): void {
		// Section: OpenAI API.
		add_settings_section(
			'gpoai_section',
			__( 'OpenAI API', 'gp-translate-with-openai' ),
			array( $this, 'render_section' ),
			'gpoai_settings'
		);

		// Option: API Key.
		$this->register_field_api_key();

		// Option: Model.
		$this->register_field_model();

		// Option: Custom Prompt.
		$this->register_field_custom_prompt();

		// Option: Temperature.
		$this->register_field_temperature();
	}

	/**
	 * Render section.
	 *
	 * @return void
	 */
	public function render_section(): void {
		esc_html_e( 'Settings for OpenAI API access.', 'gp-translate-with-openai' );
	}

	/**
	 * Register settings field API Key.
	 *
	 * @return void
	 */
	public function register_field_api_key(): void {
		$field_name    = 'gpoai_api_key';
		$section_name  = 'gpoai_section';
		$settings_name = 'gpoai_settings';

		register_setting(
			$settings_name,
			$field_name,
			array(
				'label'             => __( 'OpenAI API Key', 'gp-translate-with-openai' ),
				'description'       => __( 'Enter the OpenAI API Key.', 'gp-translate-with-openai' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
				'show_in_rest'      => false,
			),
		);

		add_settings_field(
			$field_name,
			__( 'OpenAI API Key', 'gp-translate-with-openai' ),
			array( $this, 'render_field_api_key' ),
			$settings_name,
			$section_name,
			array(
				'label_for' => $field_name,
			),
		);
	}

	/**
	 * Register settings for Model.
	 *
	 * @return void
	 */
	public function register_field_model(): void {
		$field_name    = 'gpoai_model';
		$section_name  = 'gpoai_section';
		$settings_name = 'gpoai_settings';

		register_setting(
			$settings_name,
			$field_name,
			array(
				'label'             => __( 'OpenAI Model', 'gp-translate-with-openai' ),
				'description'       => __( 'Select the OpenAI Model.', 'gp-translate-with-openai' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
				'show_in_rest'      => false,
			),
		);

		add_settings_field(
			$field_name,
			__( 'OpenAI Model', 'gp-translate-with-openai' ),
			array( $this, 'render_field_model' ),
			$settings_name,
			$section_name,
			array(
				'label_for' => $field_name,
			),
		);
	}

	/**
	 * Register settings for OpenAI Custom Prompt.
	 *
	 * @return void
	 */
	public function register_field_custom_prompt(): void {
		$field_name    = 'gpoai_custom_prompt';
		$section_name  = 'gpoai_section';
		$settings_name = 'gpoai_settings';

		register_setting(
			$settings_name,
			$field_name,
			array(
				'label'             => __( 'OpenAI Custom Prompt', 'gp-translate-with-openai' ),
				'description'       => __( 'Enter your custom prompt for OpenAI translation suggestions.', 'gp-translate-with-openai' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
				'show_in_rest'      => false,
			),
		);

		add_settings_field(
			$field_name,
			__( 'OpenAI Custom Prompt', 'gp-translate-with-openai' ),
			array( $this, 'render_field_custom_prompt' ),
			$settings_name,
			$section_name,
			array(
				'label_for' => $field_name,
			),
		);
	}

	/**
	 * Register settings for OpenAI Temperature.
	 *
	 * @return void
	 */
	public function register_field_temperature(): void {
		$field_name    = 'gpoai_temperature';
		$section_name  = 'gpoai_section';
		$settings_name = 'gpoai_settings';

		register_setting(
			$settings_name,
			$field_name,
			array(
				'label'             => __( 'OpenAI Temperature', 'gp-translate-with-openai' ),
				'description'       => __( 'Enter the OpenAI Temperature.', 'gp-translate-with-openai' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
				'show_in_rest'      => false,
			),
		);

		add_settings_field(
			$field_name,
			__( 'OpenAI Temperature', 'gp-translate-with-openai' ),
			array( $this, 'render_field_temperature' ),
			$settings_name,
			$section_name,
			array(
				'label_for' => $field_name,
			),
		);
	}

	/**
	 * Render settings field API Key.
	 *
	 * @return void
	 */
	public function render_field_api_key(): void {
		$field_name = 'gpoai_api_key';

		$api_key = get_option( $field_name, '' );
		?>
		<input type="text" name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text">
		<p class="description"><?php esc_html_e( 'Enter the OpenAI API Key.', 'gp-translate-with-openai' ); ?></p>
		<?php
	}

	/**
	 * Render settings for OpenAI Model.
	 *
	 * @return void
	 */
	public function render_field_model(): void {
		$field_name = 'gpoai_model';

		$models = array(
			'gpt-3.5-turbo',
			'gpt-4',
			'gpt-4-turbo',
			'gpt-4o',
			'gpt-4o-mini',
		);

		$model = get_option( $field_name, 'gpt-3.5-turbo' );
		?>
		<select name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $field_name ); ?>">
		<?php foreach ( $models as $model_name ) { ?>
			<option value="<?php echo esc_attr( $model_name ); ?>" <?php selected( $model, $model_name ); ?>><?php echo esc_html( $model_name ); ?></option>
		<?php } ?>
		</select>
		<p class="description"><?php esc_html_e( 'Select the OpenAI Model.', 'gp-translate-with-openai' ); ?> <?php esc_html_e( 'Default:', 'gp-translate-with-openai' ); ?><code>gpt-3.5-turbo</code></p>
		<?php
	}

	/**
	 * Render settings for OpenAI Custom Prompt.
	 *
	 * @return void
	 */
	public function render_field_custom_prompt(): void {
		$field_name = 'gpoai_custom_prompt';

		$custom_prompt = get_option( $field_name, '' );
		?>
		<textarea name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $field_name ); ?>" class="large-text"><?php echo esc_textarea( $custom_prompt ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Enter your custom prompt for OpenAI translation suggestions.', 'gp-translate-with-openai' ); ?></p>
		<?php
	}

	/**
	 * Render settings for OpenAI Temperature.
	 *
	 * @return void
	 */
	public function render_field_temperature(): void {
		$field_name = 'gpoai_temperature';

		$temp = get_option( $field_name, 0 );
		?>
		<input type="text" name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $temp ); ?>" class="regular-text">
		<p class="description"><?php esc_html_e( 'Enter the OpenAI Temperature.', 'gp-translate-with-openai' ); ?> <?php esc_html_e( 'Default:', 'gp-translate-with-openai' ); ?><code>0</code></p>
		<?php
	}
}
