<?php
/*
	Plugin Name: WooCommerce Quaderno
	Plugin URI: https://wordpress.org/plugins/woocommerce-quaderno/
	Description: Use Quaderno services in your WooCommerce shop.
	Version: 1.2.1
	Author: Quaderno
	Author URI: https://quaderno.io/
	License: GPL v3

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . '/woo-includes/woo-functions.php' );
}

/**
 * Class WooCommerce_Quaderno
 *
 * @since 1.0
 */
class WooCommerce_Quaderno {

	const VERSION = '1.2.1';
	const QUADERNO_URL = 'https://quadernoapp.com';

	/**
	 * Get the plugin file
	 *
	 * @static
	 * @since  1.0
	 * @access public
	 *
	 * @return String
	 */
	public static function get_plugin_file() {
		return __FILE__;
	}

	/**
	 * A static method that will setup the autoloader
	 *
	 * @static
	 * @since  1.0
	 * @access private
	 */
	private static function setup_autoloader() {
		require_once( plugin_dir_path( self::get_plugin_file() ) . '/quaderno-php/quaderno_load.php' );
		require_once( plugin_dir_path( self::get_plugin_file() ) . '/classes/class-wc-qd-autoloader.php' );
		$autoloader = new WC_QD_Autoloader();
		spl_autoload_register( array( $autoloader, 'load' ) );
	}

	/**
	 * Constructor
	 *
	 * @since  1.0
	 */
	public function __construct() {
		// Check if WC is activated
		if ( ! WC_Dependencies::woocommerce_active_check() ) {
			add_action( 'admin_notices', array( $this, 'notice_activate_wc' ) );
		} else if ( version_compare( WC_VERSION, '2.2.9', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_version_wc' ) );
		} else {
			$this->init();
		}
	}

	/**
	 * Display the notice
	 *
	 * @since  1.0
	 * @access public
	 */
	public function notice_activate_wc() {
		?>
		<div class="error">
			<p><?php printf( __( 'Please install and activate %sWooCommerce%s in order for the WooCommerce Quaderno extension to work!', 'woocommerce-quaderno' ), '<a href="' . admin_url( 'plugin-install.php?tab=search&s=WooCommerce&plugin-search-input=Search+Plugins' ) . '">', '</a>' ); ?></p>
		</div>
	<?php
	}

	/**
	 * Display the notice
	 *
	 * @since  1.0
	 * @access public
	 */
	public function notice_version_wc() {
		?>
		<div class="error">
			<p><?php _e( 'Please update WooCommerce to <strong>version 2.2.9 or higher</strong> in order for the WooCommerce Quaderno extension to work!', 'woocommerce-quaderno' ); ?></p>
		</div>
	<?php
	}

	/**
	 * Init the plugin
	 *
	 * @since  1.0
	 * @access private
	 */
	private function init() {

		// Load plugin textdomain
		load_plugin_textdomain( 'woocommerce-quaderno', false, plugin_dir_path( self::get_plugin_file() ) . 'languages/' );

		// Setup the autoloader
		self::setup_autoloader();
		
		// The VAT number Field
		$vat_number_field = new WC_QD_Vat_Number_Field();
		$vat_number_field->setup();
		
		// Setup the Checkout VAT stuff
		$checkout_vat = new WC_QD_Checkout_Vat();
		$checkout_vat->setup();
		
		// Setup Invoice manager
		$invoice_manager = new WC_QD_Invoice_Manager();
		$invoice_manager->setup();
		
		// Admin only classes
		if ( is_admin() ) {

			// The admin E-Book Field
			$admin_ebook = new WC_QD_Admin_Ebook();
			$admin_ebook->setup();
			
			// Filter plugin links
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_links' ) );
		}

		// Add Quaderno integration fields
		add_filter( 'woocommerce_integrations', array( $this, 'load_integration' ) );

		// Enqueue scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Plugin page links
	 *
	 * @since 1.0
	 */
	public function plugin_links( $links ) {
		$plugin_links = array(
			'<a href="' . self::QUADERNO_URL . '" target="_blank">' . __( 'Sign Up', 'woocommerce-quaderno' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Define integration
	 *
	 * @since 1.0
	 * @param  array $integrations
	 * @return array
	 */
	public function load_integration( $integrations ) {
		$integrations[] = 'WC_QD_Integration';

		return $integrations;
	}

	/**
	 * Enqueue the Quaderno scripts
	 *
	 * @since 1.0
	 */
	public function enqueue_scripts() {
		if ( is_checkout() ) {
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			
			wp_enqueue_script(
				'wc_qd_checkout_js',
				plugins_url( '/assets/js/checkout' . $suffix . '.js', WooCommerce_Quaderno::get_plugin_file() ),
				array( 'jquery' )
			);

		}
	}
	
}

// The 'main' function
function __woocommerce_quaderno_main() {
	new WooCommerce_Quaderno();
}

// Create object - Plugin init
add_action( 'plugins_loaded', '__woocommerce_quaderno_main' );

