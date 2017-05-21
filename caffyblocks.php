<?php
/*
Plugin Name: CaffyBlocks
Version: .5
Plugin URI: http://github.com/smccafferty/caffyblocks
Description: Provide a foundation to curate the content of the templates for post/pages/custom post types
Author: smccafferty
Author URI: http://github.com/smccafferty
 */

namespace Caff;

// load external dependencies if they exist and are not already loaded
$class_dependencies = array(
	'Post_Selection_UI'
);
$run_auto_load = false;
foreach( $class_dependencies as $class_dependency ) {
	if ( ! class_exists( $class_dependency ) ) {
		$run_auto_load = true;
		break;
	}
}
if ( $run_auto_load && file_exists( __DIR__ . '/lib/autoload.php' ) ) {
	require_once __DIR__ . '/lib/autoload.php';
} elseif ( $run_auto_load ) {
	return;
}

require_once __DIR__ . '/inc/caching.php';
require_once __DIR__ . '/inc/base.php';

class CaffyBlocks {
	private static $instance;
	private $foundation;

	const GROUP = 'caffyblocks';

	public static function GetInstance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new CaffyBlocks();
		}
		return self::$instance;
	}

	public static function init() {
		// Load example if the setting is enabled
		$caffyblocks_settings = get_option( 'caffyblocks_settings' );
		$example_enabled      = isset( $caffyblocks_settings['enable_test_implementation'] ) ? intval( $caffyblocks_settings['enable_test_implementation'] ) : false;
		if ( ! empty( $example_enabled ) ) {
			require_once __DIR__ . '/example.php';
			// Create the WordPress admin example
			add_action( 'wp_loaded', array( '\Caff\CaffyBlocks_Admin_Example', 'setup' ) );
		}
	}

	/**
	 * Helper function to get a plugins url, relative to the $relative_path.
	 * @param string $relative_path The relative building to the plugin path.
	 * @param string $plugin_path The building of the plugin.
	 * @return string Plugin url.
	 */
	public static function plugins_url( $relative_path, $plugin_path = __FILE__ ) {
		$template_dir = get_template_directory();

		foreach ( array( 'template_dir', 'plugin_path' ) as $var ) {
			$$var = str_replace( '\\', '/', $$var ); // sanitize for Win32 installs
			$$var = preg_replace( '|/+|', '/', $$var );
		}
		if ( 0 === strpos( $plugin_path, $template_dir ) ) {
			$url = get_template_directory_uri();
			$folder = str_replace( $template_dir, '', dirname( $plugin_path ) );
			if ( '.' != $folder ) {
				$url .= '/' . ltrim( $folder, '/' );
			}
			if ( !empty( $relative_path ) && is_string( $relative_path ) && strpos( $relative_path, '..' ) === false ) {
				$url .= '/' . ltrim( $relative_path, '/' );
			}
			return $url;
		} else {
			return plugins_url( $relative_path, $plugin_path );
		}
	}

	/**
	 * Provides a way to return the raw settings from buildings/rooms
	 * @param array $args
	 * @return mixed
	 */
	public function get_setting( $args ) {
		$defaults = array(
			'building_id'   => '',
			'room_id'       => '',
			'accessory_id'  => '',
			'default_value' => null
		);

		$args = wp_parse_args( $args, $defaults );

		$building_id   = $args['building_id'];
		$room_id       = $args['room_id'];
		$accessory_id  = $args['accessory_id'];
		$default_value = $args['default_value'];

		if ( empty( $building_id ) ) {
			return $default_value;
		}

		$building_id = sprintf( '%s_%s', $this->foundation->foundation_id, $building_id );

		$building_group = get_option( $building_id );

		if ( ! empty( $room_id ) && ! empty( $accessory_id ) ) { // get accessory settings
			if ( ! empty( $building_group ) && ! empty( $building_group[ $room_id ] ) && ! empty( $building_group[ $room_id ][ $accessory_id ] ) ) {
				return $building_group[ $room_id ][ $accessory_id ];
			} elseif ( ( $building = $this->get_building( $building_id ) ) && ! empty( $building->room[ $room_id ][ $accessory_id ] ) && ! empty( $building->room[ $room_id ][ $accessory_id ]->default_value ) ) {
				return $building->room[ $room_id ][ $accessory_id ]->default_value;
			}
		} elseif ( ! empty( $room_id ) ) { // get room settings
			if ( ! empty( $building_group ) && ! empty( $building_group[ $room_id ] ) ) {
				return $building_group[ $room_id ];
			}
		} else { // building settings
			return $building_group;
		}
		return $default_value;
	}

	/**
	 * Updates the building/room/accessory values
	 * @param array $args
	 * @return boolean
	 */
	public function set_setting( $args ) {
		$defaults = array(
			'building_id'  => '',
			'room_id'      => '',
			'accessory_id' => '',
			'value'        => null
		);

		$args = wp_parse_args( $args, $defaults );

		$building_id  = $args['building_id'];
		$room_id      = $args['room_id'];
		$accessory_id = $args['accessory_id'];
		$value        = $args['value'];

		if ( is_null( $value ) || empty( $building_id ) ) {
			return null;
		}

		$building_id = sprintf( '%s_%s', $this->foundation->foundation_id, $building_id );

		$building_group = get_option( $building_id, array() );

		if ( ! empty( $room_id ) && ! empty( $accessory_id ) ) {
			$building_group[ $room_id ][ $accessory_id ] = $value;
		} elseif ( ! empty( $room_id ) ) {
			$building_group[ $room_id ] = $value;
		} else {
			$building_group = $value;
		}
		
		return update_option( $building_id, $building_group );
	}

	/**
	 * Main function to create a caffyblocks object
	 * @param type $foundation_title
	 * @param type $menu_title
	 * @param type $foundation_id
	 * @param string $capability
	 * @param type $description
	 * @return CaffyBlocks\Foundation
	 */
	public function build( $foundation_title, $menu_title, $foundation_id = 'caffyblocks', $capability = false, $description = '' ) {
		if ( ! $capability ) {
			$capability = 'manage_options';
		} else {
			add_filter( 'option_foundation_capability_caffyblocks', function() use ( $capability ) {
				return $capability;
			} );
		}

		if ( ! isset( $this->foundation ) ) {
			$foundation = new CaffyBlocks\Foundation( $foundation_title, $menu_title, $foundation_id, $capability, $description );
			$this->foundation = $foundation;
		}
		return $this->foundation;
	}

	/**
	 * Get a caffyblocks foundation
	 * @return CaffyBlocks\Foundation
	 */
	public function get_foundation() {
		return $this->foundation;
	}

	/**
	 * Get a building by it's key
	 * @param string $building_id
	 * @return mixed
	 */
	public function get_building( $building_id ) {
		if ( isset( $this->foundation->buildings[ $building_id ] ) ) {
			return $this->foundation->buildings[ $building_id ];
		}
		return null;
	}

	/**
	 * Get a room by it's key and a building
	 * @param string $room_id
	 * @param string $building_id
	 * @return mixed
	 */
	public function get_room( $room_id, $building_id ) {
		$building = $this->get_building( $building_id );
		if ( ! empty( $building ) && isset( $building->rooms[ $room_id ] ) ) {
			return $building->rooms[ $room_id ];
		}
		return null;
	}

	/**
	 * Get a accessory by it's key, a room and building.
	 * @param string $accessory_id
	 * @param string $room_id
	 * @param string $building_id
	 * @return mixed
	 */
	public function get_accessory( $accessory_id, $room_id, $building_id ) {
		$room = $this->get_room( $room_id, $building_id );
		if ( ! empty( $room ) && isset( $room->accessorys[ $accessory_id ] ) ) {
			return $room->accessorys[ $accessory_id ];
		}
		return null;
	}

	/**
	 * Provides a way to get a accessory by using the name associated with a specific accessory. Mainly used in ajax callbacks to get a accessory.
	 * @param string $field_name - Field name that is output via $accessory->get_field_name
	 * @return mixed
	 */
	public function get_accessory_by_field_name( $field_name ) {
		$accessory = false;

		// abstract out the keys
		$structure = preg_match_all( '/[^\[^\]]+/', $field_name, $matches );

		if ( empty( $matches ) || 4 > $structure ) {
			return false;
		}

		// move to first sec of matches
		$matches = array_shift( $matches );

		// check to see if accessory could be in a dynamic room
		$keys = array(
			'foundation_id',
			'building_id',
			'room_id'
		);

		if ( empty( $keys ) ) {
			return false;
		}

		foreach( $keys as $index => $key ) {
			switch( $key ) {
				case 'foundation_id' :
					$foundation = $this->get_foundation();
					break;
				case 'building_id' :
					if ( ! isset( $foundation ) || ! is_a( $foundation, 'Caff\CaffyBlocks\Foundation' ) ) {
						break;
					}
					$building = isset( $foundation->buildings[ $matches[ $index ] ] ) ? $foundation->buildings[ $matches[ $index ] ] : false;
					break;
				case 'room_id' :
					if ( ! isset( $building ) || ! is_a( $building, 'Caff\CaffyBlocks\Building' ) ) {
						break;
					}

					$room = isset( $building->rooms[ $matches[ $index ] ] ) ? $building->rooms[ $matches[ $index ] ] : false;

					// if its a parent then we know we are looking for accessory in a dynamic room
					if ( $room->is_parent ) {

						$dynamic_room_id           = isset( $matches[ $index + 1 ] ) ? $matches[ $index + 1 ] : false;
						$dynamic_room_accessory_id = isset( $matches[ $index + 2 ] ) ? $matches[ $index + 2 ] : false;

						// check if required key exist
						if ( empty( $dynamic_room_id ) || empty( $dynamic_room_accessory_id ) ) {
							break;
						}

						// check if dynamic room already exists, means room has been save
						$dynamic_room = isset( $building->rooms[ $dynamic_room_id ] ) ? $building->rooms[ $dynamic_room_id ] : false;

						// need to create dynamic room
						if ( empty( $dynamic_room ) ) {

							$labels = array(
								'room-type',
								'dynamic-id'
							);

							$dynamic_room_type_and_id = explode( CaffyBlocks\Room::$dynamic_base_identifier, $dynamic_room_id );

							// invalid args
							if ( count( $labels ) !== count( $dynamic_room_type_and_id ) ) {
								break;
							}
							
							$dynamic_room_type_and_id = array_combine( $labels, $dynamic_room_type_and_id );

							$room_type_args = $room->get_dynamic_room_args( $dynamic_room_type_and_id['room-type'] );

							// don't continue creating the room if the room type args are not defined
							if ( empty( $room_type_args ) ) {
								break;
							}

							$room_label = isset( $room_type_args['label'] ) ? $room_type_args['label'] : '';

							// add the args
							$room_args = isset( $room_type_args['args'] ) ? $room_type_args['args'] : array();

							$room_args['parent_room'] = $room->room_id;

							// create dynamic room
							$dynamic_room = $room->building->add_room( $room_label, $dynamic_room_id, $room_args );

							// accessory key does not exist or accessory type doesnt exist for the dynamic room
							if ( empty( $dynamic_room_accessory_id ) || empty( $room_type_args['accessorys'][ $dynamic_room_accessory_id ] ) ) {
								break;
							}

							$accessory = $dynamic_room->add_accessory( $dynamic_room_accessory_id, $room_type_args['accessorys'][ $dynamic_room_accessory_id ] );
						} else {
							$accessory = isset( $dynamic_room->accessorys[ $dynamic_room_accessory_id ] ) ? $dynamic_room->accessorys[ $dynamic_room_accessory_id ] : false;
						}
					} else {
						$accessory_id = isset( $matches[ $index + 1 ] ) ? $matches[ $index + 1 ] : false;

						// check if required key exist
						if ( empty( $accessory_id ) ) {
							break;
						}

						$accessory = isset( $room->accessorys[ $accessory_id ] ) ? $room->accessorys[ $accessory_id ] : false;
					}
			}
		}

		return ( is_a( $accessory, 'Caff\CaffyBlocks\Accessory' ) ) ? $accessory : false;
	}
}
CaffyBlocks::init();