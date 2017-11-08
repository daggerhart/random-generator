<?php
/*
 * Plugin Name: Random Generator
 * Description: A thingy for brandon
 * Author: Jonathan Daggerhart
 * Version: 0.1.0
 */


RandomGenerator::register();


class RandomGenerator {

	private function __construct() { }

	/**
	 * Hook plugin into WP
	 */
	public static function register() {
		$plugin = new self();
		$plugin->loadcmb2();

		add_shortcode('random_generator', array( $plugin, 'shortcode' ));

		add_action( 'init', array( $plugin, 'register_post_type' ) );
		add_action( 'cmb2_admin_init', array( $plugin, 'generator_metaboxes' ) );
	}

	/**
	 * Load CMB2 Fields api
	 */
	function loadcmb2() {
		$file = dirname( __FILE__ ) . '/vendor/cmb2/init.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}

	/**
	 * Shortcode for random generator.
	 *
	 * @param $attributes
	 * @param string $content
	 *
	 * @return mixed
	 */
	function shortcode( $attributes, $content = '' ) {
		$attributes = wp_parse_args( $attributes, array() );

		$token_groups = get_post_meta( get_the_ID(), 'random_generator_token_groups', TRUE );
		$description = get_post_meta( get_the_ID(), 'random_generator_description', TRUE );
		$js = array(
			'description' => $description,
			'token_groups' => array(),
		);

		if (!empty($token_groups)) {

			// Prep everything for JS and replacement
			foreach ($token_groups as $group) {
				$token = '[' . trim($group['token'] ) . ']';
				$replacements = explode("\n", $group['replacements']);
				$show_ones = !empty($group['show_ones']) ? true : false;
				$details = !empty($group['details']) ? explode("\n", $group['details']) : [];

				$replacements = array_map('trim', $replacements);
				$details = array_map('trim', $details);

				foreach ($replacements as $index => $item) {
					$formatted = array(
						'text' => $item,
						'no' => 1,
						'die' => 1,
						'details' => $details,
						'show_ones' => $show_ones,
					);

					if (strpos($item, '::') !== FALSE) {
						$temp = explode('::', $item);
						$formatted['text'] = $temp[1];

						$dice = explode('d', $temp[0]);
						$formatted['no'] = $dice[0];
						$formatted['die'] = $dice[1];
					}

					$replacements[$index] = $formatted;
				}

				$js['token_groups'][] = array(
					'token' => $token,
					'replacements' => $replacements,
				);
			}

			wp_enqueue_script('random_generator_js', plugins_url('/js/random-generator.js', __FILE__ ), array('jquery'));
			wp_localize_script( 'random_generator_js', 'random_generator', $js );
		}

		$description = "<div id='random-generator-target'>{$description}</div>".
		               "<button id='random-generator-button' type='button' class='button'>Generate</button>";

		return $description;
	}

	/**
	 * Custom post type
	 */
	function register_post_type() {
		$labels = array(
			'name'               => _x( 'Random generators', 'post type general name'),
			'singular_name'      => _x( 'Random generator', 'post type singular name'),
			'menu_name'          => _x( 'Random generators', 'admin menu'),
			'name_admin_bar'     => _x( 'Random generator', 'add new on admin bar'),
			'add_new'            => _x( 'Add New', 'random generator'),
			'add_new_item'       => __( 'Add New Random generator'),
			'new_item'           => __( 'New Random generator'),
			'edit_item'          => __( 'Edit Random generator'),
			'view_item'          => __( 'View Random generator'),
			'all_items'          => __( 'All Random generators'),
			'search_items'       => __( 'Search Random generators'),
			'parent_item_colon'  => __( 'Parent Random generators:'),
			'not_found'          => __( 'No random generators found.'),
			'not_found_in_trash' => __( 'No random generators found in Trash.')
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Description.'),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'generator' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
		);

		register_post_type( 'random_generator', $args );
	}

	/**
	 * Fields for the new post type
	 */
	function generator_metaboxes() {

		$prefix = 'random_generator_';

		$cmb = new_cmb2_box( array(
			'id'            => $prefix . 'metabox',
			'title'         => esc_html__( 'Random Generator' ),
			'object_types'  => array( 'random_generator' ), // Post type
		) );

		$cmb->add_field( array(
			'id'         => $prefix . 'description',
			'name'       => esc_html__( 'Description with tokens' ),
			'desc'       => esc_html__( 'Write the story, or description that will have tokens randomly replaced in. Tokens should be wrapped with brackets: [Some Token]'),
			'type'       => 'textarea',
			'before_row' => '<p>Put this shortcode in your content: <code>[random_generator]</code></p>',
		) );

		$group_field_id = $cmb->add_field( array(
			'id'          => $prefix . 'token_groups',
			'type'        => 'group',
			'description' => __( 'Tokens and their possible replacements' ),
			'options'     => array(
				'group_title'   => __( 'Group {#}' ), // since version 1.1.4, {#} gets replaced by row number
				'add_button'    => __( 'Add Another Group' ),
				'remove_button' => __( 'Remove Group' ),
				'sortable'      => false,
			),
		) );

		$cmb->add_group_field( $group_field_id, array(
			'name' => 'Token',
			'id'   => 'token',
			'type' => 'text',
			'desc' => __('Token without the braces.'),
		) );

		$cmb->add_group_field( $group_field_id, array(
			'name' => 'Possible Replacements',
			'description' => 'One per line. Prepend dice to a row like this- 2d6::forest wardens',
			'id'   => 'replacements',
			'type' => 'textarea',
		) );

		$cmb->add_group_field( $group_field_id, array(
			'name' => 'Show number 1',
			'id'   => 'show_ones',
			'type' => 'checkbox',
			'desc' => __('Check this if you want to show the number 1 when a replacement is only one item.'),
		) );


		$cmb->add_group_field( $group_field_id, array(
			'name' => 'Details',
			'id'   => 'details',
			'type' => 'textarea_small',
			'desc' => __('One per line. Additional adjectives prepended to the chosen replacement at random.'),
		) );
	}
}


