<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

class WC_QD_Invoice_Manager {

	public function setup() {
		// Create invoice
		add_action( 'woocommerce_order_status_completed', array( $this, 'create_invoice' ), 10, 1 );
	}

	/**
	 * Create invoice
	 *
	 * @param $order_id
	 */
	public function create_invoice( $order_id ) {
		// Get the order
		$order = wc_get_order( $order_id );

		// Return if an invoice has already been issued for this order
		$invoice_id = get_post_meta( $order->id, '_quaderno_invoice', true );
		if ( !empty( $invoice_id ) ) {
			return;
		}

		QuadernoBase::init( WC_QD_Integration::$api_token, WC_QD_Integration::$api_url );

		$invoice = new QuadernoInvoice(array(
			'issue_date' => date('Y-m-d'),
			'currency' => $order->order_currency,
			'po_number' => $order->id,
			'tag_list' => 'woocommerce',
			'notes' => $order->order_comments
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
		$invoice->addContact($contact);

		// Calculate exchange rate
		$exchange_rate = get_post_meta( $order->id, '_woocs_order_rate', true ) ?: 1;

		// Add items
		$virtual_products = false;
		$items = $order->get_items();
		foreach ( $items as $item ) {
			$transaction_type = WC_QD_Calculate_Tax::get_transaction_type( $item['product_id'] );
			if ( $transaction_type != 'standard' ) {
				$virtual_products = true;
			}
		
			$tax = WC_QD_Calculate_Tax::calculate( $transaction_type, $contact->country, $contact->postal_code, $contact->tax_id );
			$new_item = new QuadernoItem(array(
				'description' => $item['name'],
				'quantity' => $order->get_item_count($item),
				'unit_price' => round($order->get_item_subtotal($item) * $exchange_rate, 2),
				'tax_1_name' => $tax->name,
				'tax_1_rate' => $tax->rate,
				'tax_1_country' => $tax->country
			));
			$invoice->addItem( $new_item );
		}
	
		// Add the payment
		$payment = new QuadernoPayment(array(
			'date' => date('Y-m-d'),
			'amount' => round($order->get_total() * $exchange_rate, 2),
			'payment_method' => 'credit_card'
		));
		$invoice->addPayment( $payment );
	
		if ( $invoice->save() ) {
			add_post_meta( $order->id, '_quaderno_invoice', $invoice->id );
			add_post_meta( $order->id, '_quaderno_invoice_number', $invoice->number );
		
			if ( true === $virtual_products ) {
				$evidence = new QuadernoEvidence(array(
					'document_id' => $invoice->id,
					'billing_country' => $order->billing_country,
					'ip_address' => $order->customer_ip_address
				));
				$evidence->save();
			}
		
			if ( true === WC_QD_Integration::$autosend_invoices ) $invoice->deliver();
		}
	}

}