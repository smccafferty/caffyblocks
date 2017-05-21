<?php

namespace Caff\CaffyBlocks\Admin;

class Settings {
	private $options;

	public function admin_menu() {
		// add caffyblocks settings page
		add_options_page(
			'CaffyBlocks Settings',
			'CaffyBlocks',
			'manage_options',
			'caffyblocks-settings',
			array( $this, 'settings_page' )
		);
	}

	public function admin_init() {
		// register caffyblocks setting
		register_setting(
			'caffyblocks_settings_group',
			'caffyblocks_settings',
			array( $this, 'sanitize' )
		);

		// create section to use for registering setting fields
		add_settings_section(
			'caffyblocks_settings_general',
			'General Settings',
			'__return_empty_string',
			'caffyblocks-settings'
		);

		add_settings_field(
			'enable_test_implementation',
			'Enable Test Implementation',
			array( $this, 'enable_test_implementation_cb' ),
			'caffyblocks-settings',
			'caffyblocks_settings_general'
		);
	}

	/**
	 * Settings page callback
	 */
	public function settings_page() {
		$this->options = get_option( 'caffyblocks_settings' );
		?>
		<div class="wrap">
			<h1>CaffyBlocks Settings</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'caffyblocks_settings_group' );
				do_settings_sections( 'caffyblocks-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function sanitize( $input ) {
		$updates = array();
		if( isset( $input['enable_test_implementation'] ) ) {
			$updates['enable_test_implementation'] = intval( $input['enable_test_implementation'] );
		}
		return $updates;
	}

	public function enable_test_implementation_cb() {
		$this->display_checkbox( 'enable_test_implementation', 'Enable Test Implementation', $this->options['enable_test_implementation'] );
	}

	public function display_checkbox( $slug, $title, $value, $description = '' ) {
		?>
		<input type="checkbox" id="<?php echo esc_attr( sprintf( '%s-%s', \Caff\CaffyBlocks::GROUP, $slug ) ); ?>" name="<?php echo esc_attr( sprintf( '%s[%s]', 'caffyblocks_settings', $slug ) ); ?>" value="1" <?php checked( 1, intval( $value ), true ); ?> /><br/>
		<?php
		if ( ! empty( $description ) ) {
			printf( '<span class="description">%s</span>', wp_kses_post( $description ) );
		}
	}
}