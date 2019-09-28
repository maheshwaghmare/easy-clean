<?php
/**
 * Plugin Name: Bulk Clean
 * Plugin URI: https://github.com/maheshwaghmare/easy-clean/
 * Description: Bulk clean allow you to delete unwanted posts, pages, custom post etc with a single click.
 * Version: 1.1.0
 * Author: Mahesh M. Waghmare
 * Author URI: https://maheshwaghmare.wordpress.com/
 * Text Domain: easy-clean
 *
 * @package Easy Clean
 */

define( 'EASY_CLEAN_VER', '1.1.0' );
define( 'EASY_CLEAN_FILE', __FILE__ );
define( 'EASY_CLEAN_BASE', plugin_basename( EASY_CLEAN_FILE ) );
define( 'EASY_CLEAN_DIR', plugin_dir_path( EASY_CLEAN_FILE ) );
define( 'EASY_CLEAN_URI', plugins_url( '/', EASY_CLEAN_FILE ) );

require_once( 'classes/class-easy-clean.php' );
