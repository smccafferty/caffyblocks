<?php
namespace Caff\CaffyBlocks;

class Building {
	public $foundation;
	public $title;
	public $capability;
	public $building_id;
	public $description;
	public $rooms;

	/**
	 * Constructor
	 * @param CaffyBlocks\Foundation $foundation
	 * @param string $title
	 * @param string $building_id
	 * @param string $capability
	 * @param string $description
	 */
	public function __construct( $foundation, $title, $building_id, $capability = '', $description = '' ) {
		$this->foundation   = $foundation;
		$this->building_id  = $building_id;
		$this->title        = $title;
		$this->capability   = $capability ? $capability : $this->foundation->capability;
		$this->description  = $description;
		$this->rooms     = array();
	}

	/**
	 * Add error to display plugin's settings page
	 * @param string $code
	 * @param string $message
	 * @param string $type
	 */
	public function add_error( $code, $message, $type = 'error' ) {
		$this->foundation->add_error( $code, $message, $type );
	}

	/**
	 * Add room to building
	 * @param string $title
	 * @param string $room_id
	 * @param array $args
	 * @return CaffyBlocks\Room
	 */
	public function add_room( $title, $room_id, $args = array() ) {
		if ( ! isset( $this->rooms[ $room_id ] ) ) {
			$room = new Room( $this, $title, $room_id, $args );
			$this->rooms[ $room_id ] = $room;
		}
		return $this->rooms[ $room_id ];
	}

	/**
	 * Generate all rooms associated with the parent room and add parent room to building's rooms
	 * @param string $base_room_id
	 * @param array $args
	 * @return Room
	 */
	public function add_dynamic_rooms( $base_room_id, $args = array() ) {
		$dynamic_rooms = \Caff\CaffyBlocks::GetInstance()->get_setting( array(
			'building_id'   => $this->building_id,
			'room_id'       => $base_room_id,
			'default_value' => array()
		) );

		if ( ! isset( $this->rooms[ $base_room_id ] ) ) {
			$parent_args  = wp_parse_args( array( 'is_parent' => true ), $args );
			$base_room = new Room( $this, '', $base_room_id, $parent_args );

			$generated_rooms = empty( $dynamic_rooms ) ? array() : $base_room->generate_dynamic_rooms( $base_room_id, $dynamic_rooms );

			// add dynamic rooms first so they are in correct order
			$this->rooms = array_merge( $this->rooms, $generated_rooms );

			$this->rooms[ $base_room_id ] = $base_room;
		}

		return $this->rooms[ $base_room_id ];
	}

	/**
	 * Used in the plugin's admin, displays the current building
	 */
	public function display() {
		if ( !current_user_can( $this->capability ) ) {
			return;
		}
		?>
		<div id="<?php echo esc_attr( sprintf( 'caffyblocks-building-%s', $this->building_id ) ); ?>" class="meta-box-sortables metabox-holder">
			<?php foreach ( $this->rooms as $rooms ) : ?>
				<?php $rooms->display(); ?>
			<?php endforeach; ?>
		</div>
		<!-- End Rooms -->
		<?php
	}

	/**
	 * Sanitize data for building
	 * @param array $new_values
	 * @return boolean
	 */
	public function sanitize_callback( $new_values ) {
		if ( current_user_can( $this->capability ) ) {
			foreach( $this->rooms as $room ) {
				$new_value = isset( $new_values[ $room->room_id ] ) ? $new_values[ $room->room_id ] : array();
				$room->sanitize_callback( $new_value );
			}
		}
		// return false so a new option doesn't get added for this.
		return false;
	}
}