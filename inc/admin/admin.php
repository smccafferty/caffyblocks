<?php
namespace Caff\CaffyBlocks;

require_once __DIR__ . '/display-callbacks.php';
require_once __DIR__ . '/sanitize-callbacks.php';

class Admin {
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
	}

	public static function admin_init() {
		// ajax for creating a new room
		add_action( 'wp_ajax_add_dynamic_room', array( __CLASS__, 'ajax_add_dynamic_room' ) );
		add_action( 'wp_ajax_nopriv_add_dynamic_room', array( __CLASS__, 'ajax_add_dynamic_room' ) );
	}

	public static function ajax_add_dynamic_room() {
		$required_parameters = array(
			'building_id'  => 'building id',
			'base_room_id' => 'base room id',
			'room_type'    => 'room id'
		);
		$room_args = array();
		// iterate through the required parameters to verify they exist, if not return an error.
		foreach ( $required_parameters as $required_parameter => $label ) {
			if ( isset( $_POST[ $required_parameter ] ) ) {
				$room_args[ $required_parameter ] = $_POST[ $required_parameter ];
			} else {
				wp_send_json( array(
					'success' => false,
					'error'   => sprintf( 'No "%s" specified.', $label )
				) );
			}
		}
		ob_start();
		self::output_dynamic_room( $room_args );
		$html = ob_get_contents();
		ob_end_clean();
		wp_send_json( array(
			'success' => true,
			'html'    => $html
		) );
	}

	static function output_dynamic_room( $room_args = array() ) {
		$defaults = array(
			'building_id'  => false,
			'base_room_id' => false,
			'room_type'    => false
		);

		$room_args = wp_parse_args( $room_args, $defaults );

		if ( empty( $room_args['building_id'] ) || empty( $room_args['base_room_id'] ) || empty( $room_args['room_type'] ) ) {
			return;
		}

		$building_id  = $room_args['building_id'];
		$base_room_id = $room_args['base_room_id'];
		$room_type    = $room_args['room_type'];
		$foundation   = \Caff\CaffyBlocks::GetInstance()->get_foundation();

		// get the building
		$building = isset( $foundation->buildings[ $building_id ] ) ? $foundation->buildings[ $building_id ] : false;

		if ( empty( $building ) ) {
			return;
		}

		// get the base room
		$base_room = isset( $building->rooms[ $base_room_id ] ) ? $building->rooms[ $base_room_id ] : false;

		if ( empty( $base_room ) ) {
			return;
		}

		// get the data associated with the room type
		$dynamic_room_type_args = $base_room->get_dynamic_room_args( $room_type );

		$room_title = isset( $dynamic_room_type_args['label'] ) ? $dynamic_room_type_args['label'] : '';

		$dynamic_room_id = \Caff\CaffyBlocks\Room::generate_dynamic_room_id( $room_type );

		$dynamic_args = array(
			'parent_room' => $base_room_id
		);

		// create the new room
		$dynamic_room = new \Caff\CaffyBlocks\Room( $building, $room_title, $dynamic_room_id, $dynamic_args );

		$dynamic_room_accessories = isset( $dynamic_room_type_args['accessories'] ) ? $dynamic_room_type_args['accessories'] : array();

		// add the hidden accessory to specify the room type
		$dynamic_room->add_accessory( sprintf( '%s_room-type', $dynamic_room_id ), array(
			'type'             => 'static',
			'display_callback' => '\Caff\CaffyBlocks\Admin\display_hidden_field',
			'value'            => $room_type
		) );

		// add the accessories to the room
		foreach ( $dynamic_room_accessories as $id => $dynamic_room_accessory ) {
			$dynamic_room->add_accessory( $id, $dynamic_room_accessory );
		}

		// display the room
		$dynamic_room->display();
	}
}
add_action( 'init', array( 'Caff\CaffyBlocks\Admin', 'init' ) );