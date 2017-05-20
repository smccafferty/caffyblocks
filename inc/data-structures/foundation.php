<?php
namespace Caff\CaffyBlocks;

class Foundation {
	public $title;
	public $menu_title;
	public $foundation_id;
	public $capability;
	public $description;
	public $parent_page;
	public $buildings;

	/**
	 * Constructor
	 * @param string $title
	 * @param string $menu_title
	 * @param string $foundation_id
	 * @param string $capability
	 * @param string $description
	 * @param string $parent_page
	 */
	public function __construct( $title, $menu_title, $foundation_id, $capability = 'manage_options', $description = '', $parent_page = '' ) {
		$this->title         = $title;
		$this->menu_title    = $menu_title;
		$this->foundation_id = $foundation_id;
		$this->capability    = $capability;
		$this->description   = $description;
		$this->parent_page   = $parent_page;
		$this->buildings     = array();

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		if ( current_user_can( $this->capability ) ) {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
		}
	}

	/**
	 * Add a error to display on the plugin's settings page
	 * @param string $code
	 * @param string $message
	 * @param string $type
	 */
	public function add_error( $code, $message, $type = 'error' ) {
		add_settings_error( $this->foundation_id, $code, $message, $type );
	}

	/**
	 * Add a building to this foundation
	 * @param string $title
	 * @param string $building_id
	 * @param string $capability
	 * @param string $description
	 * @return CaffyBlocks\Building
	 */
	public function add_building( $title, $building_id, $capability = '', $description = '' ) {
		if ( ! isset( $this->buildings[ $building_id ] ) ) {
			$building = new Building( $this, $title, $building_id, $capability, $description );
			$this->buildings[ $building_id ] = $building;
		}
		return $this->buildings[ $building_id ];
	}

	/**
	 * Helper function to add the page to the admin menu
	 */
	public function admin_menu() {
		if ( current_user_can( $this->capability ) ) {
			if ( $this->parent_page ) {
				$page_hook = add_submenu_page( $this->parent_page, $this->title, $this->menu_title, $this->capability, $this->foundation_id, array( $this, 'display' ) );
			} else {
				$page_hook = add_menu_page( $this->title, $this->menu_title, $this->capability, $this->foundation_id, array( $this, 'display' ) );
			}

			$foundation = $this;

			add_action( 'load-' . $page_hook, function() use ( $foundation ){
				do_action( 'caffyblocks_admin_enqueue_scripts', $foundation );
			} );
		}

	}

	public function admin_init() {
		register_setting( $this->foundation_id, $this->foundation_id, array( $this, 'sanitize_callback' ) );

		add_action( 'caffyblocks_admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	public function admin_enqueue_scripts() {
		// used for allowing the reordering of the buildings and widget bars
		wp_enqueue_script( 'jquery-ui-sortable');

		wp_enqueue_style( 'caffyblocks-admin-css', \Caff\CaffyBlocks::plugins_url( 'css/caffyblocks-admin.css' ) );

		// logic required for the theme settings page
		wp_enqueue_script( 'caffyblocks-admin-js', \Caff\CaffyBlocks::plugins_url( 'js/caffyblocks-admin.js' ), array( 'jquery', 'jquery-ui-sortable' ), false, true );

		wp_localize_script( 'caffyblocks-admin-js', 'caffyblocks', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'caffyblocks_nonce' )
		) );
	}

	/**
	 * Sanitize data for foundation
	 * @param array $new_values
	 * @return boolean
	 */
	public function sanitize_callback( $new_values ) {
		if ( current_user_can( $this->capability ) ) {
			$this->add_error( 'all', 'Your changes have been saved.', 'updated' );

			$displayed_building = empty( $_POST['caffyblocks-displayed-building'] ) ? false : $_POST['caffyblocks-displayed-building'];

			if ( empty( $displayed_building ) || empty( $this->buildings[ $displayed_building ] ) ) {
				return false;
			}

			$new_value = isset( $new_values[ $displayed_building ] ) ? $new_values[ $displayed_building ] : array();
			$this->buildings[ $displayed_building ]->sanitize_callback( $new_value );
		}
		// return false so a new option doesn't get added for this.
		return false;
	}

	/**
	 * Used in the caffyblocks admin, displays the current foundation
	 */
	public function display() {
		if ( current_user_can( $this->capability ) ) :
			$selected_building = ( empty( $_GET['building'] ) && ! empty( $this->buildings ) ) ? reset( array_keys( $this->buildings ) ) : $_GET['building'];
			?>
			<div class="wrap caffyblocks">
				<h2><?php echo wp_kses_post( $this->title ); ?></h2>
				<?php settings_errors( $this->foundation_id, false, true ); ?>
				<form action="options.php" method="POST">
					<table class="form-table">
						<?php settings_fields( $this->foundation_id ); ?>
						<tr valign="top">
							<th scope="row">
								<?php if ( ! empty( $this->buildings ) ) : ?>
									<ul class="caffyblocks-menu">
										<?php foreach ( $this->buildings as $building ) : ?>
											<?php
											$classes = array();
											if ( $selected_building === $building->building_id ) {
												$classes[] = 'current-menu-item';
											}
											?>
											<li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"><a href="<?php echo esc_url( add_query_arg( 'building', $building->building_id ) ); ?>"><?php esc_html_e( $building->title ); ?></a></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</th>
							<td>
								<?php
								if ( ! empty( $this->buildings ) ) {
									$building = ( $selected_building && isset( $this->buildings[ $selected_building ] ) ) ? $this->buildings[ $selected_building ] : array_shift( $this->buildings );
									printf( '<input type="hidden" name="caffyblocks-displayed-building" value="%s"/>', esc_attr( $building->building_id ) );
									$building->display();
								}
								?>
							</td>
						</tr>
					</table>
					<?php submit_button(); ?>
				</form>
			</div>
			<?php
		endif;
	}
}