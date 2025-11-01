<?php
/**
 * Profile class file.
 *
 * @package Meloniq\GpOpenaiTranslate
 */

namespace Meloniq\GpOpenaiTranslate;

/**
 * Profile class.
 */
class Profile {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'show_user_profile', array( $this, 'show_user_profile' ), 10, 1 );
		add_action( 'edit_user_profile', array( $this, 'edit_user_profile' ), 10, 1 );

		add_action( 'personal_options_update', array( $this, 'personal_options_update' ), 10, 1 );
		add_action( 'edit_user_profile_update', array( $this, 'edit_user_profile_update' ), 10, 1 );
	}

	/**
	 * Show the user profile.
	 *
	 * @param WP_User $user The user object.
	 *
	 * @return void
	 */
	public function show_user_profile( $user ): void {
		$this->edit_user_profile( $user );
	}

	/**
	 * Edit the user profile.
	 *
	 * @param WP_User $user The user object.
	 *
	 * @return void
	 */
	public function edit_user_profile( $user ): void {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}

		$models = array(
			'gpt-3.5-turbo',
			'gpt-4',
			'gpt-4-turbo',
			'gpt-4o',
			'gpt-4o-mini',
		);

		$api_key       = get_user_meta( $user->ID, 'gpoai_api_key', true );
		$model         = get_user_meta( $user->ID, 'gpoai_model', true );
		$custom_prompt = get_user_meta( $user->ID, 'gpoai_custom_prompt', true );
		$temperature   = get_user_meta( $user->ID, 'gpoai_temperature', true );
		?>
		<h3 id="gp-translate-with-openai"><?php esc_html_e( 'GP Translate with OpenAI', 'gp-translate-with-openai' ); ?></h3>
		<input type="hidden" name="gpoai_nonce" value="<?php echo esc_attr( wp_create_nonce( 'gpoai_nonce' ) ); ?>">
		<table class="form-table">
			<tr>
				<th>
					<label for="gpoai_api_key"><?php esc_html_e( 'OpenAI API Key', 'gp-translate-with-openai' ); ?></label>
				</th>
				<td>
					<input type="text" id="gpoai_api_key" name="gpoai_api_key" value="<?php echo esc_attr( $api_key ); ?>">
					<p class="description"><?php esc_html_e( 'Enter the OpenAI API Key.', 'gp-translate-with-openai' ); ?></p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gpoai_model"><?php esc_html_e( 'OpenAI Model', 'gp-translate-with-openai' ); ?></label>
				</th>
				<td>
					<select name="gpoai_model" id="gpoai_model">
					<?php foreach ( $models as $model_name ) { ?>
						<option value="<?php echo esc_attr( $model_name ); ?>" <?php selected( $model, $model_name ); ?>><?php echo esc_html( $model_name ); ?></option>
					<?php } ?>
					</select>
					<p class="description"><?php esc_html_e( 'Select the OpenAI Model.', 'gp-translate-with-openai' ); ?></p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gpoai_custom_prompt"><?php esc_html_e( 'OpenAI Custom Prompt', 'gp-translate-with-openai' ); ?></label>
				</th>
				<td>
					<textarea name="gpoai_custom_prompt" id="gpoai_custom_prompt" class="large-text"><?php echo esc_attr( $custom_prompt ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Enter your custom prompt for OpenAI translation suggestions.', 'gp-translate-with-openai' ); ?></p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="gpoai_temperature"><?php esc_html_e( 'OpenAI Temperature', 'gp-translate-with-openai' ); ?></label>
				</th>
				<td>
					<input type="text" id="gpoai_temperature" name="gpoai_temperature" value="<?php echo esc_attr( $temperature ); ?>">
					<p class="description"><?php esc_html_e( 'Enter the OpenAI Temperature.', 'gp-translate-with-openai' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Update the user profile.
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return void
	 */
	public function personal_options_update( $user_id ): void {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user || ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}

		// Nonce check.
		if ( ! isset( $_POST['gpoai_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['gpoai_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'gpoai_nonce' ) ) {
			return;
		}

		$api_key       = isset( $_POST['gpoai_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['gpoai_api_key'] ) ) : '';
		$model         = isset( $_POST['gpoai_model'] ) ? sanitize_text_field( wp_unslash( $_POST['gpoai_model'] ) ) : '';
		$custom_prompt = isset( $_POST['gpoai_custom_prompt'] ) ? sanitize_text_field( wp_unslash( $_POST['gpoai_custom_prompt'] ) ) : '';
		$temperature   = isset( $_POST['gpoai_temperature'] ) ? sanitize_text_field( wp_unslash( $_POST['gpoai_temperature'] ) ) : '';

		update_user_meta( $user->ID, 'gpoai_api_key', $api_key );
		update_user_meta( $user->ID, 'gpoai_model', $model );
		update_user_meta( $user->ID, 'gpoai_custom_prompt', $custom_prompt );
		update_user_meta( $user->ID, 'gpoai_temperature', $temperature );
	}

	/**
	 * Update the user profile.
	 *
	 * @param WP_User|int $user The user object or ID.
	 *
	 * @return void
	 */
	public function edit_user_profile_update( $user ): void {
		if ( ! is_object( $user ) ) {
			$user = get_user_by( 'id', $user );
		}

		$this->personal_options_update( $user->ID );
	}
}
