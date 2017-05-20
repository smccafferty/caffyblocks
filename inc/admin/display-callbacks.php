<?php
namespace Caff\CaffyBlocks\Admin;

function display_label( $accessory ) {
	?>
	<label name="<?php echo esc_attr( $accessory->get_field_name() ); ?>" id="<?php echo esc_attr( $accessory->get_field_id() ); ?>" value="<?php echo esc_attr( $accessory->value ); ?>"><?php echo esc_html( $accessory->value ); ?></label>
	<?php
}

function display_dropdown( $accessory ) {
	$options = ( empty( $accessory->data['options'] ) || ! is_array( $accessory->data['options'] ) ) ? array() : $accessory->data['options'];
	?>
	<label for="<?php echo esc_attr( $accessory->get_field_id() ); ?>"><?php esc_html_e( $accessory->title ); ?>
		<select name="<?php echo esc_attr( $accessory->get_field_name() ); ?>" id="<?php echo esc_attr( $accessory->get_field_id() ); ?>">
			<?php
			foreach ( $options as $option_value => $option_label ) {
				printf( '<option %s value="%s">%s</option>', selected( $accessory->value, $option_value, false ), esc_attr( $option_value ), esc_html( $option_label ) );
			}
			?>
		</select>
	</label>
	<?php
}

function display_links( $accessory ) {
	$menu_locations = get_nav_menu_locations();

	$links  = isset( $accessory->data['links'] ) ? $accessory->data['links'] : array();
	?>
	<ul id="<?php echo esc_attr( $accessory->get_field_id() ); ?>">
		<?php foreach ( $links as $id => $args ) : ?>
			<?php
			$label = isset( $args['label'] ) ? $args['label'] : false;
			$type  = empty( $args['type'] ) ? false : $args['type'];
			switch ( $type ) {
				case 'nav_menu' :
					$menu_location = isset( $args['nav_menu_location_id'] ) ? $args['nav_menu_location_id'] : false;
					if ( empty( $menu_location ) ) {
						continue;
					}
					$link = admin_url( 'nav-menus.php' );
					if ( !empty( $menu_locations[ $menu_location ] ) ) {
						$link = add_query_arg( 'menu', $menu_locations[ $menu_location ], $link );
					}
					break;
				default :
					$link = empty( $args['link'] ) ? false : $args['link'];
					break;
			}
			if ( empty( $label ) || empty( $link ) ) {
				continue;
			}
			?>
			<li><a id="<?php echo esc_attr( $accessory->get_field_id( $id ) ); ?>" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $label ); ?></a></li>
		<?php endforeach; ?>
	</ul>
	<?php
}

function display_hidden_field( $accessory ) {
	?>
	<input type="hidden" name="<?php echo esc_attr( $accessory->get_field_name() ); ?>" id="<?php echo esc_attr( $accessory->get_field_id() ); ?>" value="<?php echo esc_attr( $accessory->value ); ?>" />
	<?php
}

function display_ad_row( $accessory ) {
	?>
	<label for="<?php echo esc_attr( $accessory->get_field_id() ); ?>">AD</label>
	<input type="hidden" name="<?php echo esc_attr( $accessory->get_field_name() ); ?>" id="<?php echo esc_attr( $accessory->get_field_id() ); ?>" value="ad-row" />
	<?php
}

function display_text_box( $accessory ) {
	$value = empty( $accessory->value ) ? $accessory->default_value : $accessory->value;
	?>
	<label for="<?php echo esc_attr( $accessory->get_field_id() ); ?>"><?php echo esc_html( $accessory->title ); ?>
		<input type="textbox" name="<?php echo esc_attr( $accessory->get_field_name() ); ?>" id="<?php echo esc_attr( $accessory->get_field_id() ); ?>" value="<?php echo esc_attr( $value ); ?>" />
	</label>
	<?php
}