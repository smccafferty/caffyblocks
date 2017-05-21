<?php
namespace Caff\CaffyBlocks\Admin;

function sanitize_psu( $new_value, $control ) {
	if ( ! empty( $new_value ) ) {
		$new_value = array_values( $new_value );
		foreach ( $new_value as $index => $psu ) {
			if ( isset( $psu['post_ids'] ) && is_string( $psu['post_ids'] ) ) {
				$new_value[ $index ]['post_ids'] = explode( ',', $psu['post_ids'] );
			}
		}
	}
	return $new_value;
}