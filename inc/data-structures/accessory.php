<?php
namespace Caff\CaffyBlocks;

class Accessory {
	public $room;
	public $title;
	public $accessory_id;
	public $capability;
	public $default_value;
	public $value;
	public $type;
	public $args;
	public $data;
	public $dynamic_parent;

	/**
	 * Constructor
	 * @param CaffyBlocks\Room $room
	 * @param string $accessory_id
	 * @param array $args
	 */
	public function __construct( $room, $accessory_id, $args = array() ) {
		$this->room     = $room;
		$this->accessory_id = $accessory_id;

		$args = wp_parse_args( $args, array(
				'title'              => '',
				'capability'         => $this->room->capability,
				'default_value'      => '',
				'display_callback'   => 'Caff\CaffyBlocks\display_label',
				'sanitize_callbacks' => array(),
				'description'        => '',
				'type'               => 'setting',
				'data'               => '',
				'value'              => $this->default_value,
				'value_callback'     => '',
				'delete_cache_on'    => array()
			)
		);

		$this->title         = $args['title'];
		$this->default_value = $args['default_value'];
		$this->capability    = $args['capability'];
		$this->type          = $args['type'];
		$this->data          = $args['data'];
		$this->value         = $args['value'];
		$this->args          = $args;

		$accessory = $this;

		if ( Caching::enabled() ) {
			foreach( $args['delete_cache_on'] as $action ) {
				add_action( $action, function() use ( &$accessory ) {
					Caching::delete_accessory( $accessory );
				} );
			}
		}
	}

	/**
	 * Add error to display plugin's settings page
	 * @param string $message
	 * @param string $type
	 */
	public function add_error( $message, $type = 'error' ) {
		$this->room->building->add_error( $this->accessory_id, $message, $type );
	}

	/**
	 * Helper function to get the input name property of the current accessory.
	 * @param string $additional_abstraction
	 * @return string
	 */
	public function get_field_name( $additional_abstraction = '' ) {
		if ( empty( $this->room->parent_room ) ) {
			$field_name = sprintf( '%s[%s][%s][%s]', $this->room->building->foundation->foundation_id, $this->room->building->building_id, $this->room->room_id, $this->accessory_id );
		} else {
			$field_name = sprintf( '%s[%s][%s][%s][%s]', $this->room->building->foundation->foundation_id, $this->room->building->building_id, $this->room->parent_room, $this->room->room_id, $this->accessory_id );
		}
		if ( !empty( $additional_abstraction ) && is_string( $additional_abstraction ) ) {
			$field_name = sprintf( '%s[%s]', $field_name, $additional_abstraction );
		}
		return $field_name;
	}

	/**
	 * Helper function to get the input id property of the current accessory
	 * @param string $additional_abstraction
	 * @return string
	 */
	public function get_field_id( $additional_abstraction = '' ) {
		if ( empty( $this->room->parent_room ) ) {
			$field_id = sprintf( '%s-%s-%s-%s', $this->room->building->foundation->foundation_id, $this->room->building->building_id, $this->room->room_id, $this->accessory_id );
		} else {
			$field_id = sprintf( '%s-%s-%s-%s-%s', $this->room->building->foundation->foundation_id, $this->room->building->building_id, $this->room->parent_room, $this->room->room_id, $this->accessory_id );
		}
		if ( !empty( $additional_abstraction ) && is_string( $additional_abstraction ) ) {
			$field_id = sprintf( '%s-%s', $field_id, $additional_abstraction );
		}
		return $field_id;
	}

	/**
	 * Used in the plugin's admin, displays the current accessory
	 */
	public function display() {
		if ( 'setting' === $this->type ) {
			$this->args['value'] = $this->value;
		}
		if ( ! empty( $this->args['display_callback'] ) ) {
			call_user_func_array( $this->args['display_callback'], array( $this ) );
		} else {
			// default to label
			Caff\CaffyBlocks\Admin\display_label( $this );
		}
	}

	/**
	 * Sanitize data for accessory
	 * @param array $new_value
	 * @param array $old_value
	 * @return array
	 */
	public function sanitize( $new_value, $old_value ) {
		if ( $this->is_setting() && current_user_can( $this->capability ) ) {
			$old_value = $new_value;
			$sanitize_callbacks = ( isset( $this->args['sanitize_callbacks'] ) && is_array( $this->args['sanitize_callbacks'] ) ) ? $this->args['sanitize_callbacks'] : array();
			foreach ( $sanitize_callbacks as $callback ) {
				if ( function_exists( $callback ) ) {
					$old_value = call_user_func_array( $callback, array( $old_value, $this ) );
				}
			}
			if ( Caching::enabled() ) {
				Caching::delete_accessory( $this );
			}
		}
		return $old_value;
	}

	/**
	 * Check if the current accessory is a setting type.
	 * @return boolean
	 */
	public function is_setting() {
		return ( 'setting' === $this->type );
	}

	/**
	 * Allows the accessory to have a callback specified change the value of the accessory.
	 * @return mixed
	 */
	public function get_value() {
		if ( ! empty( $this->args['value_callback'] ) && function_exists( $this->args['value_callback'] ) ) {
			$value = call_user_func_array( $this->args['value_callback'], array( $this->value, $this ) );
		} else {
			$value = ( empty( $this->value ) && ! empty( $this->default_value ) ) ? $this->default_value : $this->value;
		}
		return $value;
	}
}