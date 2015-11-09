<?php

class WC_QD_Credit_Manager {

	public function setup() {
		// Create credit
		add_action( 'woocommerce_refund_created', array( $this, 'create_credit' ), 10, 2 );
	}

	/**
	 * Create credit
	 *
	 * @param $refund_id
	 */
	public function create_credit( $refund_id, $args ) {
		// Get the refund
		$refund = wc_get_order( $refund_id );
		$order = wc_get_order( $args['order_id'] );

		// Return if an credit has already been issued for this refund
		$credit_id = get_post_meta( $refund->id, '_quaderno_credit', true );
		if ( !empty( $credit_id ) ) {
			return;
		}

		QuadernoBase::init( WC_QD_Integration::$api_token, WC_QD_Integration::$api_url );

		$credit = new QuadernoCredit(array(
			'issue_date' => date('Y-m-d'),
			'currency' => $refund->order_currency,
			'po_number' => $order->id,
			'tag_list' => 'woocommerce'
		));

		// Add the contact
		$contact_id = get_user_meta( $order->get_user_id(), '_quaderno_contact', true );
		if ( !empty( $contact_id ) ) {
			$contact = QuadernoContact::find( $contact_id );
		}
		else {
			if ( !empty( $order->billing_company ) ) {
				$kind = 'company';
				$first_name = $order->billing_company;
				$last_name = '';
				$contact_name = $order->billing_first_name . ' ' . $order->billing_last_name;
			} else {
				$kind = 'person';
				$first_name = $order->billing_first_name;
				$last_name = $order->billing_last_name;
				$contact_name = '';
			}

			$contact = new QuadernoContact(array(
				'kind' => $kind,
				'first_name' => $first_name,
				'last_name' => $last_name,
				'contact_name' => $contact_name,
				'street_line_1' => $order->billing_address_1,
				'street_line_2' => $order->billing_address_2,
				'city' => $order->billing_city,
				'postal_code' => $order->billing_postcode,
				'region' => $order->billing_region,
				'country' => $order->billing_country,
				'email' => $order->billing_email,
				'tax_id' => get_post_meta( $order->id, WC_QD_Vat_Number_Field::META_KEY, true )
			));

			if ( $contact->save() ){
				add_user_meta( $order->get_user_id(), '_quaderno_contact', $contact->id, true );
			}
		}
		$credit->addContact($contact);

		// Calculate exchange rate
		$exchange_rate = get_post_meta( $order->id, '_woocs_order_rate', true ) ?: 1;

		// Calculate taxes
		$items = $order->get_items();
		$first_item = array_shift($items);
		$transaction_type = WC_QD_Calculate_Tax::get_transaction_type( $first_item['product_id'] );
		$tax = WC_QD_Calculate_Tax::calculate( $transaction_type, $contact->country, $contact->postal_code, $contact->tax_id );

		// Add item
		$refunded_amount = -round($refund->get_total() * $exchange_rate, 2);
		$new_item = new QuadernoItem(array(
			'description' => 'Refund invoice #' . get_post_meta( $order->id, '_quaderno_invoice_number', true ),
			'quantity' => 1,
			'total_amount' => $refunded_amount,
			'tax_1_name' => $tax->name,
			'tax_1_rate' => $tax->rate,
			'tax_1_country' => $tax->country
		));
		$credit->addItem( $new_item );

		// Add the payment
		$payment = new QuadernoPayment(array(
			'date' => date('Y-m-d'),
			'amount' => $refunded_amount,
			'payment_method' => 'credit_card'
		));
		$credit->addPayment( $payment );

		if ( $credit->save() ) {
			add_post_meta( $refund->id, '_quaderno_credit', $refund->id );
			if ( true === WC_QD_Integration::$autosend_invoices ) $credit->deliver();
		}
	}

}