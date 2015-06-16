<?php

class WC_QD_Checkout_Vat {

	/**
	 * Setup the class
	 */
	public function setup() {

		// Update the taxes on the checkout page whenever the order review template part is refreshed
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'update_taxes_on_update_order_review' ), 10, 1 );

		// Update the taxes in the checkout process when the checkout is processed
		add_action( 'woocommerce_checkout_process', array( $this, 'update_taxes_on_check_process' ), 10 );

		// Update the taxes when the line taxes are calculated in the admin
		add_filter( 'woocommerce_ajax_calc_line_taxes', array( $this, 'update_taxes_on_calc_line_taxes' ), 10, 3 );

		// Set default customer location
		add_filter( 'woocommerce_customer_default_location', array( $this, 'set_default_customer_location' ), 10, 1 );
	}

	/**
	 * Update taxes in cart
	 *
	 * @param object $transaction
	 * @param String $country_code
	 */
	private function update_taxes_in_cart( $items ) {
		if ( count( $items ) > 0 ) {

			// Create tax manager object
			$tax_manager = new WC_QD_Tax_Manager();
			
			foreach ( $items as $key => $item ) {
				// Don't modify tax rates for default products
				if ( 'standard' == $item['product_type'] ) {
					continue;
				}

				// Add the new product class for this product
				$tax_manager->add_product_tax_class( $item['id'], $item['product_type'] );

				// Add the new tax rate for this transaction line
				$tax_manager->add_tax_rate( $item['product_type'], $item['tax_rate'], $item['tax_name'] );
			}
		}
	}

	/**
	 * Catch the update order review action and update taxes to selected billing country
	 *
	 * @param $post_data
	 */
	public function update_taxes_on_update_order_review( $post_data ) {
		// Parse the string
		parse_str( $post_data, $post_arr );

		// The billing data
		$billing_country = sanitize_text_field( $post_arr['billing_country'] );
		$billing_postcode = sanitize_text_field( $post_arr['billing_postcode'] );
		$vat_number = sanitize_text_field( $post_arr['vat_number'] );

		// The cart manager
		$cart_manager = new WC_QD_Cart_Manager($billing_country, $billing_postcode, $vat_number);

		// Update the taxes in cart based on cart items
		$this->update_taxes_in_cart( $cart_manager->get_items_from_cart() );
	}

	/**
	 * Update taxes in the checkout processing process
	 */
	public function update_taxes_on_check_process() {
		// The billing data
		$billing_country = isset( $_POST['billing_country'] ) ? sanitize_text_field( $_POST['billing_country'] ) : '';
		$billing_postcode = isset( $_POST['billing_postcode'] ) ? sanitize_text_field( $_POST['billing_postcode'] ) : '';
		$vat_number = isset( $_POST['vat_number'] ) ? sanitize_text_field( $_POST['vat_number'] ) : '';

		// The cart manager
		$cart_manager = new WC_QD_Cart_Manager($billing_country, $billing_postcode, $vat_number);

		// Update the taxes in cart based on cart items
		$this->update_taxes_in_cart( $cart_manager->get_items_from_cart() );
	}

	/**
	 * Update the taxes when the line taxes are calculated in the admin
	 *
	 * @param array $items
	 * @param int $order_id
	 * @param String $country
	 *
	 * @return array
	 */
	public function update_taxes_on_calc_line_taxes( $items, $order_id, $country ) {
		// Check for items
		if ( isset( $items['order_item_id'] ) ) {
			$tax_manager = new WC_QD_Tax_Manager();

			// Get the order
			$order = wc_get_order( $order_id );

			// Loop through items
			foreach ( $items['order_item_id'] as $item_id ) {
				// Get the product ID
				$product_id = $order->get_item_meta( $item_id, '_product_id', true );

				// Get the transaction type
				$transaction_type = WC_QD_Calculate_Tax::get_transaction_type( $product_id );

				// Don't modify tax rates for default products
				if ( 'standard' == $transaction_type ) {
					continue;
				}
				
				// Calculate taxes
				$tax = WC_QD_Calculate_Tax::calculate($transaction_type, $country);
				
				$tax_manager->add_product_tax_class( $item_id, $transaction_type );
				$tax_manager->add_tax_rate( $transaction_type, $tax->rate, $tax->name );
				$items['order_item_tax_class'][ $item_id ] = $tax_manager->clean_tax_class( $transaction_type );
			}

		}

		return $items;

	}

	/**
	 * Retrieve the customer location based on their IP and set as default location.
	 *
	 * @param string
	 */
	public function set_default_customer_location( $default ) {
		$ip = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

		$ip_data = json_decode( file_get_contents( "http://www.geoplugin.net/json.gp?ip=" . $ip ) );
		if ( $ip_data && $ip_data->geoplugin_countryCode != null ) {
			return $ip_data->geoplugin_countryCode;
		}

		return $default;
	}

}