<?php
/**
 * Admin Page
 *
 * @package Easy Clean
 * @since 1.0.0
 */

if ( ! class_exists( 'Easy_Clean_Page' ) ) :

	/**
	 * Register page.
	 */
	class Easy_Clean_Page {

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

			// Hooks.
			add_action( 'admin_menu', array( $this, 'register_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'plugin_action_links_' . EASY_CLEAN_BASE, array( $this, 'action_links' ) );

			// AJAX.
			add_action( 'wp_ajax_easy_clean_delete_posts', array( $this, 'delete_posts' ) );
			add_action( 'wp_ajax_easy_clean_delete_logs', array( $this, 'delete_logs' ) );
		}

		/**
		 * Show action links on the plugin screen.
		 *
		 * @param   mixed $links Plugin Action links.
		 * @return  array
		 */
		function action_links( $links ) {
			$action_links = apply_filters( 'easy_clean_action_links', array(
				'settings' => '<a href="' . admin_url( 'tools.php?page=easy_clean' ) . '" aria-label="' . esc_attr__( 'Get Started', 'easy-clean' ) . '">' . esc_html__( 'Get Started', 'easy-clean' ) . '</a>',
			));

			return array_merge( $action_links, $links );
		}

		/**
		 * Delete Log
		 *
		 * @return void
		 */
		function delete_logs() {

			if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( $_POST['_nonce'], 'easy_clean_delete_logs' ) ) {
				wp_send_json_error( 'Invalid request!' );
			}

			$args = array(
				'post_type'      => 'easy_clean_log',

				// Query performance optimization.
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'post_status'    => 'any',
				'posts_per_page' => -1,
			);

			$query = new WP_Query( $args );
			if ( $query->posts ) {
				foreach ( $query->posts as $key => $post_id ) {
					wp_delete_post( $post_id, true );
				}
			}

			wp_send_json_success();
		}

		/**
		 * Delete Posts
		 *
		 * @return void
		 */
		function delete_posts() {

			$post_id   = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			$post_type = isset( $_POST['post_type'] ) ? sanitize_key( $_POST['post_type'] ) : 'post';

			if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( $_POST['_nonce'], 'easy_clean_delete_posts' ) ) {
				wp_send_json_error( 'Invalid request!' );
			}

			if ( ! $post_id ) {
				wp_send_json_error( __( 'Invalid post ID.', 'easy-clean' ) );
			}

			$post_title      = get_the_title( $post_id );
			$deleted_post_id = wp_delete_post( $post_id, true );

			if ( $deleted_post_id ) {
				$message = '<p>Deleted <span class="title">' . $post_title . '</span><span class="post-type">' . $post_type . '</span> by user <span class="user">' . get_the_author_meta( 'login' ) . '</span> at <span class="time">' . current_time( 'd M Y h:m:s' ) . '</span>.</p>';
				$postarr = array(
					'post_type'  => 'easy_clean_log',
					'post_title' => '',
					'meta_input' => array(
						'message' => $message,
					),
				);

				wp_insert_post( $postarr );
			} else {
				$message = '<p>Not deleted <span class="title">' . $post_title . '</span><span class="post-type">' . $post_type . '</span> by user <span class="user">' . get_the_author_meta( 'login' ) . '</span> at <span class="time">' . current_time( 'd M Y h:m:s' ) . '</span>.</p>';
			}

			$data = array(
				'markup'  => '<div class="notice-info clean-post-notice">' . $message . '</div>',
				'post_id' => $post_id,
			);

			wp_send_json_success( $data );
		}

		/**
		 * Enqueue Scripts
		 *
		 * @param  string $hook Current Hook.
		 * @return empty        If current hook is not the easy hook then return null.
		 */
		function enqueue_scripts( $hook ) {

			if ( 'tools_page_easy_clean' !== $hook ) {
				return;
			}

			add_thickbox();

			wp_enqueue_script( 'easy-clean', EASY_CLEAN_URI . '/assets/js/easy-clean.js', array( 'jquery' ), EASY_CLEAN_VER, true );
			wp_enqueue_style( 'easy-clean', EASY_CLEAN_URI . '/assets/css/easy-clean.css', null, EASY_CLEAN_VER, 'all' );
		}

		/**
		 * Register menu
		 *
		 * @return void
		 */
		function register_menu() {
			add_submenu_page( 'tools.php', __( 'Bulk Clean', 'easy-clean' ), __( 'Bulk Clean', 'easy-clean' ), 'manage_options', 'easy_clean', array( $this, 'markup' ) );
		}

		/**
		 * Markup.
		 *
		 * @version 1.0.0
		 *
		 * @return void
		 */
		function markup() {
			?>
			<div class="wrap easy-clean-page">

				<div class="header">
					<div class="inner">
						<h1><?php _e( 'Bulk Clean', 'easy-clean' ); ?> <small class="version"><?php echo EASY_CLEAN_VER; ?></small></h1>
						<p class="description"><?php _e( 'One click delete posts, pages and custom post types.', 'easy-clean' ); ?></p>
					</div>
				</div>

				<div id="poststuff">
					<div id="post-body" class="columns-2">
						<div id="post-body-content">
							<?php
							if ( isset( $_GET['show_log'] ) ) {
								$this->display_log();
							} else {
								$this->display_posts();
							}
							?>
						</div>
						<div class="postbox-container" id="postbox-container-1">
							<div id="side-sortables" style="">
								<div class="postbox">
									<h2 class="hndle"><span><?php _e( 'Getting Started', 'easy-clean' ); ?></span></h2>
									<div class="inside">
										<p><?php _e( 'Plugin <b>PERMANENTLY</b> delete selected posts, pages, custom post types.', 'easy-clean' ); ?></p>
										<p><?php _e( 'NOTE: Plugin does not move deleted post into the trash. Deleted posts will not recover!', 'easy-clean' ); ?></p>
									</div>
								</div>
								<div class="postbox">
									<h3 class="hndle">Log </h3>
									<div class="inside">
										<p><?php _e( 'Check the log to know that who and when the posts are deleted.', 'easy-clean' ); ?></p>

										<?php if ( isset( $_GET['show_log'] ) ) { ?>
											<a href="<?php echo admin_url( 'tools.php?page=easy_clean' ); ?>" class="button">Hide Logs</a>
										<?php } else { ?>
											<a href="<?php echo admin_url( 'tools.php?page=easy_clean&show_log=yes' ); ?>" class="button">Show Logs</a>
										<?php } ?>
										<?php if ( isset( $_GET['show_log'] ) ) { ?>
											<button data-nonce="<?php echo wp_create_nonce( 'easy_clean_delete_logs' ); ?>" class="button easy-clean-delete-log">Clear Logs</button>
										<?php } ?>
									</div>
								</div>

								<div class="postbox">
									<h2 class="hndle"><span><?php _e( 'Support', 'easy-clean' ); ?></span></h2>
									<div class="inside">
										<p><?php _e( 'Do you have any issue with this plugin? Or Do you have any suggessions?', 'easy-clean' ); ?></p>
										<p><?php _e( 'Please don\'t hesitate to <a href="http://maheshwaghmare.wordpress.com/?p=999" target="_blank">send request »</a>.', 'easy-clean' ); ?></p>
									</div>
								</div>
								<div class="postbox">
									<h2 class="hndle"><span><?php _e( 'Donate', 'easy-clean' ); ?></span></h2>
									<div class="inside">
										<p><?php _e( 'Would you like to support the advancement of this plugin?', 'easy-clean' ); ?></p>
										<a href="https://www.paypal.me/mwaghmare7/" target="_blank" class="button button-primary"><?php _e( 'Donate Now!', 'easy-clean' ); ?></a>
									</div>
								</div>
							</div>
						</div>
					</div>
					<!-- /post-body -->
					<br class="clear">
				</div>
			</div>

			<?php
		}

		/**
		 * Display Log
		 *
		 * @return void
		 */
		function display_log() {
			?>
			<a href="<?php echo admin_url( 'tools.php?page=easy_clean' ); ?>" class="button button-back">Back</a>
			<?php
			$args = apply_filters( 'easy_clean_delete_log_query_args', array(
				'post_type'      => 'easy_clean_log',

				// Query performance optimization.
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'post_status'    => 'any',
				'posts_per_page' => -1,
			));

			$query = new WP_Query( $args );
			if ( $query->posts ) {
				foreach ( $query->posts as $key => $post_id ) {
					echo '<div class="notice-info clean-post-notice">' . get_post_meta( $post_id, 'message', true ) . '</div>';
				}
			} else {
				?>
				<div class="no-logs">
					<h3><?php _e( 'No Logs!', 'easy-clean' ); ?></h3>
					<p class="description"><?php _e( 'Not have any log entries!', 'easy-clean' ); ?></p>
				</div>
				<?php
			}
		}

		/**
		 * Delete Posts
		 *
		 * @return void
		 */
		function display_posts() {

			$excludes = array( 'easy_clean_log', 'revision', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request' );

			$post_types = get_post_types();
			foreach ( $post_types as $key => $post_type ) {

				if ( in_array( $post_type, $excludes ) ) {
					continue;
				}

				$current_post_type = get_post_type_object( $post_type );

				$args = array(
					'post_type'      => $post_type,

					// Query performance optimization.
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'post_status'    => 'any',
					'posts_per_page' => -1,
				);

				$query = new WP_Query( $args );
				if ( $query->posts ) {
					$no_of_posts = count( $query->posts );
					?>
					<div class="post-type-wrap post-type-<?php echo esc_attr( $post_type ); ?>-wrap">
						<h3><?php echo esc_html( $current_post_type->label ); ?></h3>
						<p class="description">
							<?php
							/* Translators: %s is the no of found posts count. */
							printf( __( 'Found %s items.', 'easy-clean' ), $no_of_posts );
							?>
						</p>

						<div class="post-type post-type-<?php echo $post_type; ?>">
							<p>
								<label style="max-width: 400px;">
									<input type="checkbox" name="post_ids[]" class="post-type-all" data-post-type="<?php echo $post_type; ?>"> All
								</label>
							</p>
							<?php
							$number = 1;
							foreach ( $query->posts as $key => $post_id ) {
								?>
								<div class="no no-<?php echo $number; ?>">
									<label style="min-width: 400px;">
										<input type="checkbox" class="post-type-<?php echo $post_type; ?>-checkbox" name="post_ids[]" value="<?php echo $post_id; ?>" data-post-type="<?php echo $post_type; ?>">
										<?php echo get_the_title( $post_id ); ?>
									</label>
									<span class="actions">
										<a target="_balnk" href="<?php echo get_permalink( $post_id ); ?>"><?php _e( 'view', 'easy-clean' ); ?></a>
									</span>
								</div>
								<?php
								$number++;
							}
							?>
							<div class="toggle">
								<a href="#"><i class="dashicons dashicons-arrow-down-alt2"></i> <?php _e( 'Show All', 'easy-clean' ); ?></a>
							</div>
						</div>
						<hr/>
					</div>
					<?php
				}
			}
			?>
			<div>
				<button data-nonce="<?php echo wp_create_nonce( 'easy_clean_delete_posts' ); ?>" class="button button-primary easy-clean-delete"><?php _e( 'Delete', 'easy-clean' ); ?></button>
			</div>
			<?php
		}
	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Easy_Clean_Page::get_instance();

endif;
