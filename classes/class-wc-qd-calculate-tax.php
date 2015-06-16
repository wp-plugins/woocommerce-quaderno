<?php

class WC_QD_Calculate_Tax {

	/**
	 * Get the transaction type
	 *
	 * @param int $product_id
	 *
	 * @return String
	 */
	public static function get_transaction_type( $product_id ) {
		$type = 'standard';

		if ( 'product' === get_post_type( $product_id ) ) {
			$product = wc_get_product( $product_id );

			// Check if this is a virtual product
			if ( $product->is_virtual() ) {
				$type = 'eservice';
			}

			// Check if this is an e-book
			$is_ebook = get_post_meta( $product_id, '_ebook', true );
			if ( 'yes' === $is_ebook ) {
				$type = 'ebook';
			}

		}

		return $type;
	}
	
	/**
	 * Get the product type
	 *
	 * @param String $transaction_type
	 * @param String $country
	 * @param String $postal_code
	 * @param String $vat_number
	 *
	 * @return Tax
	 */
	public static function calculate( $transaction_type, $country, $postal_code = '', $vat_number = '' ) {
		$params = array( 
			'country' => $country,
			'postal_code' => $postal_code,
			'vat_number' => $vat_number,
			'transaction_type' => $transaction_type
		);

		$slug = 'tax_' . md5( implode( $params ) );
		
		if ( false === ( $tax = get_transient( $slug ) ) ) {
			QuadernoBase::init( WC_QD_Integration::$api_token, WC_QD_Integration::$api_url );
			$tax = QuadernoTax::calculate( $params );
			set_transient( $slug, $tax, WEEK_IN_SECONDS );
		}

		return $tax;
	}

}
