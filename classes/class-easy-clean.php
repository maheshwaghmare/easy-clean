<?php
/**
 * Initialize Plugin
 *
 * @package Easy Clean
 * @since 1.0.0
 */

if ( ! class_exists( 'Easy_Clean' ) ) :

	/**
	 * Initialize plugin.
	 */
	class Easy_Clean {

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
			require_once EASY_CLEAN_DIR . 'classes/class-easy-clean-page.php';
			require_once EASY_CLEAN_DIR . 'classes/class-easy-clean-post.php';
		}

	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Easy_Clean::get_instance();

endif;
