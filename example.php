<?php
namespace Caff;

/* 
 * This example demonstrates the logic you would add to your theme's functions file to create a CaffyBlocks implementation
 */
class CaffyBlocks_Admin_Example {
	/*
	 * This function would be the method to setup your CaffyBlocks implementation. I use the `wp_loaded` action to call
	 * the functionality.
	 */
	public static function setup() {
		// only process if the CaffyBlocks plugin is present
		if ( ! class_exists( 'Caff\CaffyBlocks' ) ) {
			return;
		}

		// create the caffyblocks foundation
		$foundation = \Caff\CaffyBlocks::GetInstance()->build( 'CaffyBlocks', 'CaffyBlocks' );

		// create our first building, which will represent the homepage
		$foundation->add_building( 'Homepage', 'homepage' )
			->add_room( 'Header', 'header', array(
				'container' => array(
					'room-footer' => sprintf( '<p class="text-center"><a href="%s">%s</a></p>', esc_url( admin_url( 'nav-menus.php' ) ), esc_html( 'Manage Navigation' ) )
				) ) )
				->add_accessory( 'rss_feed', array(
					'title'            => 'RSS Feed:',
					'display_callback' => 'Caff\CaffyBlocks\Admin\display_text_box',
					'default_value'    => ''
				) )->room
				->add_accessory( 'header-nav-menus', array(
					'display_callback' => 'Caff\CaffyBlocks\Admin\display_links',
					'type'             => 'static',
					'data'             => array(
						'links' => array(
							'header-nav-menu' => array(
								'label'                => 'Manage Header Nav Menu',
								'type'                 => 'nav_menu',
								'nav_menu_location_id' => 'header'
							)
						)
					)
				) )->room->building
			->add_room( 'Carousel', 'carousel' )
				->add_accessory( 'carousel-items', array(
					'display_callback'   => 'Caff\CaffyBlocks\Admin\display_psu',
					'sanitize_callbacks' => array( 'Caff\CaffyBlocks\Admin\sanitize_psu' ),
					'data'               => array(
						'limit'      => 1,
						'multiple'   => true,
						'post_types' => array( 'post', 'page' ),
					),
					'delete_cache_on' => array()
				) )->room->building
			->add_dynamic_rooms( 'temporary-rooms', array(
				'options' => array(
					'ad-row'          => array(
						'label'    => 'Ad',
						'controls' => array(
							'ad-room' => array(
								'display_callback' => 'Caff\CaffyBlocks\Admin\display_ad'
							)
						)
					)
				)
			) )->building
			->add_room( 'Footer', 'footer' )
				->add_accessory( 'footer-nav-menus', array(
					'display_callback' => 'Caff\CaffyBlocks\Admin\display_links',
					'type'             => 'static',
					'data'             => array(
						'links' => array(
							'footer-nav-menu' => array(
								'label'                => 'Manage Footer Nav Menu',
								'type'                 => 'nav_menu',
								'nav_menu_location_id' => 'footer'
							),
							'sidebar'         => array(
								'label' => 'Sidebar',
								'link'  => admin_url( 'widgets.php' )
							)
						)
					)
				) );

		// creating a caffyblocks page for the post types
		foreach( array( 'post', 'page' ) as $post_type_slug ) {
			// skip the following post types
			if ( in_array( $post_type_slug, array( 'side-story' ) ) ) {
				continue;
			}
			$post_type = get_post_type_object( $post_type_slug );
			$foundation->add_building( $post_type->labels->name, $post_type_slug )
				->add_room( 'Header', 'header', array(
					'container' => array(
						'room-footer' => sprintf( '<p class="text-center"><a href="%s">%s</a></p>', esc_url( admin_url( 'nav-menus.php' ) ), esc_html( 'Manage Navigation' ) )
					) ) )
					->add_accessory( 'header-nav-menus', array(
						'display_callback' => 'Caff\CaffyBlocks\Admin\display_links',
						'type'             => 'static',
						'data'             => array(
							'links' => array(
								'header-nav-menu' => array(
									'label'                => 'Header Nav Menu',
									'type'                 => 'nav_menu',
									'nav_menu_location_id' => 'header'
								)
							)
						)
					) )->room->building
				->add_room( 'Loop', 'loop' )
					->add_accessory( 'custom_template', array(
						'title'            => 'Use Template',
						'display_callback' => 'Caff\CaffyBlocks\Admin\display_dropdown',
						'data'             => array(
							'options' => array(
								'1-column' => '1 Column',
								'2-columns' => '2 Columns',
							)
						),
						'default_value' => '2-columns'
					) )->room->building
				->add_room( 'Footer', 'footer' )
					->add_accessory( 'footer-nav-menus', array(
						'display_callback' => 'Caff\CaffyBlocks\Admin\display_links',
						'type'             => 'static',
						'data'             => array(
							'links' => array(
								'footer-nav-menu' => array(
									'label'                => 'Manage Footer Nav Menu',
									'type'                 => 'nav_menu',
									'nav_menu_location_id' => 'footer'
								),
								'sidebar'         => array(
									'label'                => 'Sidebar',
									'link'                 => admin_url( 'widgets.php' )
								)
							)
						)
					)
				);
		}
	}
}

/**
 * Provide some examples of using CaffyBlocks in your templates
 */
class CaffyBlocks_Usage_Example {
	function display() {
		// Example 1
		// example to get cached caffyblocks object
		$homepage_header_rss_feed = \Caff\CaffyBlocks\Caching::get_object( 'homepage', 'header', 'rss_feed' );

		// Example 2
		// example of an output cached caffyblocks object, if the object is already is cached it is output instead of continuing with the logic
		if ( ! empty( \Caff\CaffyBlocks\Caching::start_caching( 'homepage', 'carousel', 'carousel-items' ) ) ) :
			// example of how to retrieve an uncached caffyblocks object
			$carousel_items = \Caff\CaffyBlocks::GetInstance()->get_accessory( 'carousel-items', 'carousel', 'homepage' )->get_value();
			?>
			<div class="carousel">
				<?php if ( ! empty( $carousel_items ) ) : ?>
					<div class="carousel-items">
						<?php foreach( $carousel_items as $carousel_item ) :
							if ( empty( $carousel_item['post_ids'] ) ){
								$carousel_item_post_type = empty( $carousel_item['post_type'] ) ? 'post' : $carousel_item['post_type'];
								$args = array(
									'numberposts'     => 1,
									'post_type'       => $carousel_item_post_type,
									'suppress_filter' => false,
								);
								$posts = get_posts( $args );
								if ( ! empty( $posts ) ) {
									$post                  = (object) array_shift( $posts );
									$carousel_item_post_id = $post->ID;
								} else {
									continue;
								}
							} else {
								$carousel_item_post_id   = array_shift( $carousel_item['post_ids'] );
								$post                    = get_post( $carousel_item_post_id );
								$carousel_item_post_type = get_post_type( $post );
							}
							setup_postdata( $post );
							// output some cool template
						endforeach; wp_reset_postdata(); ?>
					</div><!-- .carousel-items -->
				<?php endif; ?>
			</div><!-- .carousel -->
			<?php
			Curation_Caching::end_caching( 'homepage', 'carousel', 'carousel-items' );
		endif;

		// Example 3
		// Get non-cached object
		$carousel_items = \Caff\CaffyBlocks::GetInstance()->get_accessory( 'custom_template', 'loop', 'post' )->get_value();
	}
}