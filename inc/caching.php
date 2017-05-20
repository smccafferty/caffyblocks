<?php
namespace Caff\CaffyBlocks;

class Caching {
	// used so output caching is only ran the first time it is called, if other templates try to cache at the same time
	private static $output_caching_in_progress = false;

	// types of caching
	static $key_types = array(
		'output',
		'object'
	);

	/**
	 * Generates a cache key.
	 * @param string $building_id
	 * @param string $room_id
	 * @param string $accessory_id
	 * @param string $type
	 * @return array
	 */
	public static function get_key( $building_id, $room_id = '', $accessory_id = '', $type = 'output' ) {
		$type = in_array( $type, self::$key_types ) ? $type : 'output';

		$cache_key_array = array(
			'caffyblocks-cache',
			$type,
			$building_id,
			$room_id,
			$accessory_id
		);

		$cache_key_array = array_filter( $cache_key_array );

		return implode( '-', $cache_key_array );
	}

	/**
	 * Deletes the cache for the specified building.
	 * @param CaffyBlocks\Building $building
	 * @return boolean
	 */
	public static function delete_building( &$building ) {
		foreach( self::$key_types as $key_type ) {
			$cache_key = self::get_key( $building->building_id, '', '', $key_type );
			wp_cache_delete( $cache_key );
		}
		return true;
	}

	/**
	 * Deletes the cache for the specified room. If the room is a child room, the parent room is deleted as well.
	 * @param CaffyBlocks\Room $room
	 * @return boolean
	 */
	public static function delete_room( &$room ) {
		self::delete_building( $room->building );
		// if is a dynamic room, flush the parent room
		if ( ! empty( $room->parent_room ) && ! empty( $room->building->rooms[ $room->parent_room ] ) ) {
			self::delete_room( $room->building->rooms[ $room->parent_room ] );
		}
		foreach( self::$key_types as $key_type ) {
			$cache_key = self::get_key( $room->building->building_id, $room->room_id, '', $key_type );
			wp_cache_delete( $cache_key );
		}
		return true;
	}

	/**
	 * Deletes the cache for the specified accessory.
	 * @param CaffyBlocks\Accessory $accessory
	 * @return boolean
	 */
	public static function delete_accessory( &$accessory ) {
		self::delete_room( $accessory->room );
		foreach( self::$key_types as $key_type ) {
			$cache_key = self::get_key( $accessory->room->building->building_id, $accessory->room->room_id, $accessory->accessory_id, $key_type );
			wp_cache_delete( $cache_key );
		}
		return true;
	}

	/**
	 * Helper function to enable/disable all caching
	 * @return boolean
	 */
	public static function enabled() {
		return apply_filters( 'caffyblocks_plugin_cache_enabled', true );
	}

	/**
	 * Begin output caching for the specified parameters. Returns a boolean depending on the cache state. If the cache exists
	 * the cache is echoed before returning. MUST USE `Caff\CaffyBlocks\Caching::end_caching()` in order to complete the usage of this
	 * function.
	 * Ex.
	 * if ( ! empty( Caff\CaffyBlocks\Caching::start_caching( {building_id}, {room_id}, {accessory_id} ) ) ) :
	 *      {logic here}
	 *      Caff\CaffyBlocks\Caching::end_caching( {building_id}, {room_id}, {accessory_id} );
	 * endif;
	 *
	 * Cache is cleared on save of the specified building.
	 * @param string $building_id
	 * @param string $room_id
	 * @param string $accessory_id
	 * @return boolean - true if the caching has started or caching is disabled, to allow normal operation and false of the cache exists.
	 */
	public static function start_caching( $building_id, $room_id = '', $accessory_id = '' ) {
		if ( ! self::enabled() ) {
			return true;
		}

		$caffyblocks_cache_key = Caching::get_key( $building_id, $room_id, $accessory_id );

		if ( ! empty( self::$output_caching_in_progress ) && self::$output_caching_in_progress !== $caffyblocks_cache_key ) {
			return true;
		}

		$caffyblocks_cache = wp_cache_get( $caffyblocks_cache_key );
		if ( empty( $caffyblocks_cache ) ) {
			self::$output_caching_in_progress = $caffyblocks_cache_key;
			ob_start();
			return true;
		} else {
			echo $caffyblocks_cache;
			return false;
		}
	}

	/**
	 * End output caching. For use only in conjunction of `Caff\CaffyBlocks\Caching::start_caching()`.
	 * @param string $building_id
	 * @param string $room_id
	 * @param string $accessory_id
	 */
	public static function end_caching( $building_id, $room_id = '', $accessory_id = '' ) {
		if ( ! self::enabled() ) {
			return;
		}

		$caffyblocks_cache_key = Caching::get_key( $building_id, $room_id, $accessory_id );

		if ( ! empty( self::$output_caching_in_progress ) && self::$output_caching_in_progress !== $caffyblocks_cache_key ) {
			return;
		}

		$primary_listing_room_cache = ob_get_contents();
		ob_end_flush();
		wp_cache_add( $caffyblocks_cache_key, $primary_listing_room_cache );

		self::$output_caching_in_progress = false;
	}

	/**
	 * Object cache the data/value for the specified parameters.
	 *
	 * Cache is cleared on save of the specified building.
	 * @param string $building_id
	 * @param string $room_id
	 * @param string $accessory_id
	 * @param mixed $default_value
	 * @return mixed
	 */
	public static function get_object( $building_id, $room_id = '', $accessory_id = '', $default_value = false ) {
		$cache_key = '';
		if ( self::enabled() ) {
			$cache_key = self::get_key( $building_id, $room_id, $accessory_id, 'object' );
			$cache     = wp_cache_get( $cache_key );
			If ( ! empty( $cache ) ) {
				return $cache;
			}
		}

		$object = $default_value;
		if ( ! empty( $accessory_id ) ) {
			$accessory = CaffyBlocks::GetInstance()->get_accessory( $accessory_id, $room_id, $building_id );
			if ( ! empty( $accessory ) ) {
				$object = $accessory->get_value();
			}
		} else {
			$room_settings = CaffyBlocks::GetInstance()->get_setting( array(
				'building_id' => $building_id,
				'room_id'  => $room_id
			) );
			$object = $room_settings;
		}

		if ( self::enabled() && ! empty( $object ) ) {
			wp_cache_add( $cache_key, $object );
		}

		return $object;
	}
}