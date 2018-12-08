<?php
/**
 * Post Type
 *
 * @package Easy Clean
 * @since 1.0.0
 */

if ( ! class_exists( 'Easy_Clean_Post' ) ) :

	/**
	 * Register post type and do post related code.
	 */
	class Easy_Clean_Post {

		/**
		 * Instance
		 *
		 * @access private
		 * @var object Class Instance.
		 * @since 1.0.0
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 1.0.0
		 * @return object initialized object of class.
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'register_post_type' ) );
		}

		/**
		 * Registers a new post type
		 *
		 * @uses $wp_post_types Inserts new post type object into the list
		 */
		function register_post_type() {

			/* Logs post type */
			$log_args = array(
				'labels'              => array( 'name' => __( 'Logs', 'easy-clean' ) ),
				'public'              => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'show_ui'             => false,
				'query_var'           => false,
				'rewrite'             => false,
				'capability_type'     => 'post',
				'supports'            => array( 'title', 'custom-fields' ),
				'can_export'          => true,
			);

			register_post_type( 'easy_clean_log', $log_args );
		}

	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Easy_Clean_Post::get_instance();

endif;
