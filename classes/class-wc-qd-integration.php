<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_QD_Integration extends WC_Integration {

	public static $api_token = null;
	public static $api_url = null;
	public static $autosend_invoices = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id                 = 'quaderno';
		$this->method_title       = __( 'Quaderno', 'woocommerce-quaderno' );
		$this->method_description = __( sprintf( 'Quaderno allows WooCommerce users to comply with the EU VAT for digital goods and service, and send beautiful receipts to their customers. %sYou need a Quaderno account for this extension to work. %sClick here to sign up%s and get your access data.', '<br/>', '<a href="' . WooCommerce_Quaderno::QUADERNO_URL . '/signup" target="_blank">', '</a>' ), 'woocommerce-quaderno' );

		// Load admin form
		$this->init_form_fields();

		// Load settings
		$this->init_settings();

		self::$api_token = $this->get_option( 'api_token' );
		self::$api_url  = $this->get_option( 'api_url' );
		self::$autosend_invoices  = $this->get_option( 'autosend_invoices' );

		// Hooks
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'woocommerce_update_options_integration_quaderno', array( $this, 'process_admin_options' ) );

		if ( empty( self::$api_token ) || empty( self::$api_url ) ) {
			add_action( 'admin_notices', array( $this, 'settings_notice' ) );
		}
	}
	
	/**
	 * Init integration form fields
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'api_token' => array(
				'title'       => __( 'Private key', 'woocommerce-quaderno' ),
				'description' => __( 'Get this token from your Quaderno account.', 'woocommerce-quaderno' ),
				'type'        => 'text'
			),
			'api_url'  => array(
				'title'       => __( 'API URL', 'woocommerce-quaderno' ),
				'description' => __( 'Get this URL from your Quaderno account.', 'woocommerce-quaderno' ),
				'type'        => 'text'
			),
			'autosend_invoices' => array(
				'title'       => __( 'Autosend Invoices', 'woocommerce-quaderno' ),
				'description' => __( 'Send invoices to the customers automatically when the order status is changed to complete.', 'woocommerce-quaderno' ),
				'type'        => 'checkbox'
			)
		);
	}

	/**
	 * Settings prompt
	 */
	public function settings_notice() {
		if ( ! empty( $_GET['tab'] ) && 'integration' === $_GET['tab'] ) {
			return;
		}
		?>
		<div id="message" class="updated woocommerce-message">
			<p><?php _e( '<strong>Quaderno</strong> is almost ready &#8211; Please configure your API keys to begin fetching tax rates.', 'woocommerce-quaderno' ); ?></p>

			<p class="submit"><a
					href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=integration&section=quaderno' ); ?>"
					class="button-primary"><?php _e( 'Settings', 'woocommerce-quaderno' ); ?></a></p>
		</div>
	<?php
	}
}