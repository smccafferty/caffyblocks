<?php
namespace Caff\CaffyBlocks;

class Room {
	public $building;
	public $title;
	public $capability;
	public $room_id;
	public $description;
	public $args;
	public $accessories;

	public $is_parent;
	public $parent_room;

	public static $dynamic_base_identifier = '__dynamic__';

	/**
	 * Constructor
	 * @param string $building
	 * @param string $title
	 * @param string $room_id
	 * @param array $args
	 */
	public function __construct( $building, $title, $room_id, $args = array() ) {
		$this->building    = $building;
		$this->room_id = $room_id;
		$this->title       = $title;

		$args = wp_parse_args( $args, array(
				'capability'     => $this->building->capability,
				'description'    => '',
				'is_parent'      => false,
				'parent_room' => ''
			)
		);

		$this->capability  = $args['capability'];
		$this->description = $args['description'];
		$this->is_parent   = $args['is_parent'];
		$this->parent_room = $args['parent_room'];
		$this->args        = $args;
		$this->accessories = array();
	}

	/**
	 * Add a error to display on the plugin's settings page
	 * @param string $code
	 * @param string $message
	 * @param string $type
	 */
	public function add_error( $code, $message, $type = 'error' ) {
		$this->building->foundation->add_error( $code, $message, $type );
	}

	/**
	 * Add a accessory to this room
	 * @param string $accessory_id
	 * @param array $args
	 * @return Accessory
	 */
	public function add_accessory( $accessory_id, $args = array() ) {
		if( ! isset( $this->accessories[ $accessory_id ] ) ) {
			$accessory = new Accessory( $this, $accessory_id, $args );

			if ( $accessory->is_setting() && empty( $accessory->value ) ) {
				$accessory->value = \Caff\CaffyBlocks::GetInstance()->get_setting( array(
					'accessory_id'  => $accessory_id,
					'room_id'       => $this->room_id,
					'building_id'   => $this->building->building_id,
					'default_value' => $accessory->default_value
				) );
			}

			$this->accessories[ $accessory_id ] = $accessory;
		}
		return $this->accessories[ $accessory_id ];
	}

	/**
	 * Used in the plugin's admin, displays the current room
	 */
	public function display() {
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}
		?>
		<?php if ( $this->is_parent ) : ?>
			<?php $options = ( isset( $this->args['options'] ) && is_array( $this->args['options'] ) ) ? $this->args['options'] : array(); ?>
			<div id="<?php echo esc_attr( sprintf( 'dynamic-rooms-%s', $this->room_id ) ); ?>" class="parent-room"></div>
			<p id="<?php echo esc_attr( sprintf( 'room-%s', $this->room_id ) ); ?>" class="text-center parent-room">
				<?php foreach ( $options as $option_key => $option_data ) : ?>
					<?php
					$option_label = isset( $option_data['label'] ) ? $option_data['label'] : false;
					if ( empty( $option_label ) ) {
						continue;
					}
					?>
					<input type="button" name="<?php echo esc_attr( sprintf( 'add-%s', $option_key ) ); ?>" id="<?php echo esc_attr( sprintf( 'add-%s', $option_key ) ); ?>" data-type="<?php echo esc_attr( $option_key ); ?>" data-base-room-id="<?php echo esc_attr( $this->room_id ); ?>" data-building-id="<?php echo esc_attr( $this->building->building_id ); ?>" data-foundation-id="<?php echo esc_attr( $this->building->foundation->foundation_id ); ?>" value="<?php echo esc_attr( sprintf( 'Add %s', $option_label ) ); ?>" data-container-id="<?php printf( 'dynamic-rooms-%s', esc_attr( $this->room_id ) ); ?>" class="button add-room" />
				<?php endforeach; ?>
			</p>
		<?php else : ?>
			<div id="<?php echo esc_attr( sprintf( 'room-%s', esc_attr( $this->room_id ) ) ); ?>" class="room-container" data-container-id="<?php printf( 'dynamic-rooms-%s', esc_attr( $this->parent_room ) ); ?>">
				<div class="room-header">
					<a class="room-expand" title="Click to toggle"></a>
					<h3><?php esc_html_e( $this->title ); ?></h3>
				</div>
				<div class="room-content">
					<?php foreach ( $this->accessories as $accessory ) : ?>
						<?php $accessory->display(); ?>
					<?php endforeach; ?>
					<div class="accessory-loading hidden">
						<img class="spinner is-active" src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" />
					</div>
				</div>
				<div class="room-footer">
					<?php if ( ! empty( $this->parent_room ) ) : ?>
						<p><a href="#" class="remove-room"><?php esc_html_e( 'Remove Room' ); ?></a></p>
					<?php elseif ( ! empty( $this->args['container']['room-footer'] ) ) : ?>
						<?php echo wp_kses_post( $this->args['container']['room-footer'] ); ?>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Sanitize data in the room
	 * @param array $new_values
	 */
	public function sanitize_callback( $new_values ) {
		// only continue if the user has the capability and if the room does not have a parent
		// as the sanitizing of child rooms is handled in the parent room
		if ( current_user_can( $this->capability ) && empty( $this->parent_room ) ) {
			$room_args = array(
				'building_id'  => $this->building->building_id,
				'room_id'   => $this->room_id,
				'default_value' => array()
			);

			$old_values = \Caff\CaffyBlocks::GetInstance()->get_setting( $room_args );
			$values     = array();

			// if is a dynamic room parent; can create dynamic rooms
			if ( $this->is_parent ) {
				$foundation_id = $this->building->foundation->foundation_id;
				$building_id   = $this->building->building_id;
				$parent_room   = $this->room_id;

				$dynamic_rooms = isset( $_POST[ $foundation_id ][ $building_id ][ $parent_room ] ) ? $_POST[ $foundation_id ][ $building_id ][ $parent_room ] : array();

				// exclude any child rooms that were removed
				$old_values = array_intersect_key( $dynamic_rooms, $old_values );

				$rooms = $this->generate_dynamic_rooms( $this->room_id, $dynamic_rooms );

				foreach ( $rooms as $room ) {
					$room_new_values = isset( $new_values[ $room->room_id ] ) ? $new_values[ $room->room_id ] : array();
					$room_data = $this->_sanitize_callback_worker( $old_values, $room_new_values, $room->accessories );
					// only add if data exists
					if ( ! empty( $room_data ) ) {
						$values[ $room->room_id ] = $room_data;
					}
				}
				if ( Caching::enabled() ) {
					Caching::delete_room( $this );
				}
			} else {
				$room_data = $this->_sanitize_callback_worker( $old_values, $new_values, $this->accessories );
				// only add if data exists
				if ( ! empty( $room_data ) ) {
					$values = $room_data;
				}
			}

			$room_args['value'] = $values;

			\Caff\CaffyBlocks::GetInstance()->set_setting( $room_args );
		}
	}

	/**
	 * Helper to iterate through the accessories of a room to sanitize each accessory's data.
	 * @param array $old_values
	 * @param array $new_values
	 * @param array $accessories
	 * @return array
	 */
	private function _sanitize_callback_worker( $old_values, $new_values, $accessories ) {
		$room_data = array();
		foreach ( $accessories as $accessory ) {
			if ( ! $accessory->is_setting() ) {
				continue;
			}
			$new_value = isset( $new_values[ $accessory->accessory_id ] ) ? $new_values[ $accessory->accessory_id ] : null;
			$old_value = isset( $old_values[ $accessory->accessory_id ] ) ? $old_values[ $accessory->accessory_id ] : null;
			$room_data[ $accessory->accessory_id ] = $accessory->sanitize( $new_value, $old_value );
		}

		return $room_data;
	}

	/**
	 * Uses provided dynamic rooms data to create a Caff\CaffyBlocks\Room for each dynamic room, with their corresponding
	 * accessories.
	 * @param string $base_room_id
	 * @param array $dynamic_rooms
	 * @return array
	 */
	public function generate_dynamic_rooms( $base_room_id, $dynamic_rooms = array() ) {
		$rooms = array();

		$foundation_id     = isset( $this->building->foundation->foundation_id ) ? $this->building->foundation->foundation_id : false;
		$building_id = isset( $this->building->building_id ) ? $this->building->building_id : false;

		if ( empty( $foundation_id ) || empty( $building_id ) ) {
			return $rooms;
		}

		if ( ! empty( $dynamic_rooms ) ) {
			foreach ( $dynamic_rooms as $room_id => $dynamic_room ) {
				$room_type_key = sprintf( '%s_room-type', $room_id );
				$room_type     = isset( $dynamic_room[ $room_type_key ] ) ? $dynamic_room[ $room_type_key ] : '';

				// an room type is required in order to create the room
				if ( empty( $room_type ) ) {
					continue;
				}

				// get room type args
				$room_type_args = $this->get_dynamic_room_args( $room_type );

				$room_label = isset( $room_type_args['label'] ) ? $room_type_args['label'] : '';

				// don't continue creating the room if the room type args are not defined
				if ( empty( $room_type_args ) ) {
					continue;
				}

				// add the args
				$room_args = isset( $room_type_args['args'] ) ? $room_type_args['args'] : array();

				$room_args['parent_room'] = $base_room_id;

				// create room
				$room = new Room( $this->building, $room_label, $room_id, $room_args );

				// add the hidden accessory to specify the room type
				$room->add_accessory( $room_type_key, array(
					'type'             => 'setting',
					'display_callback' => 'display_hidden_field',
					'value'            => $room_type
				) );

				// add accessories
				$dynamic_room_accessories = empty( $room_type_args['accessories'] ) ? array() : $room_type_args['accessories'];
				foreach ( $dynamic_room_accessories as $accessory_id => $dynamic_room_accessory ) {
					$accessory_value = isset( $dynamic_room[ $accessory_id ] ) ? $dynamic_room[ $accessory_id ] : '';
					$dynamic_room_accessory['value'] = $accessory_value;
					$room->add_accessory( $accessory_id, $dynamic_room_accessory );
				}

				$rooms[ $room_id ] = $room;
			}
		}
		return $rooms;
	}

	/**
	 * Gets the arguments for a room associated to the room type specified.
	 * @param string $room_type
	 * @return array
	 */
	public function get_dynamic_room_args( $room_type ) {
		$possible_room_types = ( isset( $this->args['options'] ) && is_array( $this->args['options'] ) ) ? $this->args['options'] : array();

		foreach ( $possible_room_types as $possible_room_type => $possible_room_type_args ) {
			if ( $room_type === $possible_room_type ) {
				return $possible_room_type_args;
			}
		}

		return array();
	}

	/**
	 * Helper function to generate a room key for a dynamic room.
	 * @param string $base_room_id
	 * @return string
	 */
	public static function generate_dynamic_room_id( $base_room_id ) {
		return sprintf( '%s%s%s', $base_room_id, self::$dynamic_base_identifier, substr( md5( wp_generate_password() ), 0, 5 ) );
	}
}