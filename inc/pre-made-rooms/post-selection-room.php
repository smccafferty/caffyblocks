<?php
namespace Caff\CaffyBlocks;

class Post_Selection_Room {
	private $index;

	private $accessory;
	private $accessory_args;
	private $allowed_post_types;
	private $accessory_name;

	public static $delete_cache_on = array(
		'save_post',
		'deleted_post',
		'edited_terms',
		'delete_term',
		'create_term'
	);

	public static function init() {
		// ajax for creating a new room and PSU within room
		add_action( 'wp_ajax_caffyblocks_add_psu_room', array( __CLASS__, 'ajax_add_psu_room' ) );

		add_action( 'wp_ajax_caffyblocks_add_psu', array( __CLASS__, 'ajax_add_psu' ) );
	}

	/**
	 * Ajax callback to add a post selection ui room
	 */
	public static function ajax_add_psu_room() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'caffyblocks_nonce' ) ) {
			exit;
		}

		if ( empty( $_POST['post_types'] ) || ! isset( $_POST['index'] ) || empty( $_POST['field_name'] ) ) {
			exit;
		}

		$args = array(
			'post_types' => (array) json_decode( urldecode( $_POST['post_types'] ) ),
			'index'      => (int) $_POST['index'],
			'field_name' => $_POST['field_name'],
		);

		$accessory = new self( $args );
		$accessory->render_accessory();

		exit;
	}

	/**
	 * Ajax callback to add a post selection ui
	 */
	public static function ajax_add_psu() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'caffyblocks_nonce' ) ) {
			exit;
		}

		if ( empty( $_POST['post_types'] ) || ! isset( $_POST['index'] ) || empty( $_POST['limit'] ) || empty( $_POST['field_name'] ) ) {
			exit;
		}

		$args = array(
			'post_types' => (array) $_POST['post_types'],
			'index'      => intval( $_POST['index'] ),
			'limit'      => intval( $_POST['limit'] ),
			'field_name' => $_POST['field_name'],
		);

		$accessory = new self( $args );

		if ( ! empty( $args['post_types'] ) ) {
			$accessory->render_psu( array(), array_pop( $args['post_types'] ) );
		}

		exit;
	}

	/**
	 * Constructor
	 * @param array $accessory_args
	 * @param Caff\CaffyBlocks\Accessory $accessory
	 */
	public function __construct( $accessory_args, $accessory = null ) {
		$this->accessory      = $accessory;
		$this->accessory_args = $accessory_args;

		// set index for accessory
		$this->index = ! empty( $this->accessory_args['index'] ) ? $this->accessory_args['index'] : 0;

		$this->allowed_post_types = self::get_allowed_post_types( $this->accessory_args['post_types'] );
		$this->accessory_name     = ( ! empty( $this->accessory_args['field_name'] ) ? $this->accessory_args['field_name'] : $this->accessory->get_field_name() );
	}

	/**
	 * Helper to further abstract the data for the post selection ui
	 * @param string $additional_abstraction
	 * @return string
	 */
	private function get_accessory_name( $additional_abstraction = '' ) {
		if ( empty( $additional_abstraction ) ) {
			return sprintf( '%s[%d]', $this->accessory_name, $this->index );
		} else {
			return sprintf( '%s[%d][%s]', $this->accessory_name, $this->index, $additional_abstraction );
		}
	}

	/**
	 * Get the allowed post type objects.
	 * @return array Array of post type objects.
	 */
	private static function get_allowed_post_types( $post_types = array() ) {
		$all_post_types = get_post_types( array(), 'objects' );
		if ( in_array( 'any', $post_types ) ) {
			return $all_post_types;
		}

		$post_type_objects = array();
		foreach( $all_post_types as $post_type_obj ) {
			if ( in_array( $post_type_obj->name, $post_types ) ) {
				$post_type_objects[ $post_type_obj->name ] = $post_type_obj;
			}
		}

		return $post_type_objects;
	}

	/**
	 * Render an instance of the accessory
	 */
	public function render_accessory( $value = array() ) {
		$value = wp_parse_args( $value, array(
			'post_type' => null,
			'post_ids'  => null,
		) );

		?>
		<div class="psu-field" data-psu_index="<?php echo esc_attr( $this->index ); ?>">
			<select class="psu-post_type" name="<?php echo esc_attr( $this->get_accessory_name( 'post_type' ) ); ?>">
				<?php
				foreach ( $this->allowed_post_types as $post_type_obj ) {
					printf( '<option %s value="%s">%s</option>', selected( $value['post_type'], $post_type_obj->name, false ), esc_attr( $post_type_obj->name ), esc_html( $post_type_obj->labels->name ) );
				}
				?>
			</select>
			<?php
			$classes = array(
				'select-psu-posts'
			);
			if ( empty( $value['post_ids'] ) ) {
				$classes[] = 'select-psu-posts-closed';
				$label     = 'Select Posts';
			} else {
				$classes[] = 'select-psu-posts-open';
				$label     = 'Remove Posts';
			}
			?>
			<a href="#" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"><?php esc_attr_e( $label ); ?></a>
			<a href="#" class="psu-remove-item">Remove Item</a>
			<div class="psu-field-wrapper">
				<?php
				if ( ! empty( $value['post_ids'] ) ) {
					$this->render_psu( $value['post_ids'], $value['post_type'] );
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle PSU rendering
	 */
	private function render_psu( $values, $post_type ) {
		// verify only valid post types are loaded
		if ( ! array_key_exists( $post_type, $this->allowed_post_types ) ) {
			return false;
		}

		if ( is_string( $values ) ) {
			$values = explode( ',', $values );
		} elseif ( ! is_array( $values ) ) {
			$values = array();
		}

		echo post_selection_ui( $this->get_accessory_name( 'post_ids' ), array(
			'post_type' => $post_type,
			'limit'     => (int) $this->accessory_args['limit'],
			'selected'  => $values
		) );
	}

}

Post_Selection_Room::init();