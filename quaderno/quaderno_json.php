<?php
/**
* Quaderno JSON
*
* Low level library to encode and decode messages using JSON
* and sending those messages through HTTP with cURL
* 
* @package   Quaderno PHP
* @author    Quaderno <hello@quaderno.io>
* @copyright Copyright (c) 2015, Quaderno
* @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
*/

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

abstract class QuadernoJSON
{
	private $http_method = 'GET';
	private $username = '';
	private $password = 'foo';
	private $content_type = 'application/json';
	private $endpoint = null;
	private $request_body = null;
	private $response = null;
	private $error_message = '';
	
	public function __construct() {
		$this->username = WC_QD_Integration::$api_token;
	}

	public function exec()
	{
    $args = array(
  	  'method' => $this->http_method,
  	  'headers' => array(
  	    'Authorization' => 'Basic ' . base64_encode( $this->username . ':' . $this->password ),
  	    'Content-Type' => $this->content_type
  	  ),
  	  'timeout' => 70,
      'sslverify' => false
  	);

  	// Add the request body if we've got one
  	if ( !is_null( $this->request_body ) && is_array( $this->request_body ) && count( $this->request_body ) > 0 ) {
  		$args['body'] = json_encode( $this->request_body );
  	}

  	// Get results
  	$this->response = wp_remote_request($this->endpoint, $args);

		// Decode data
		if ( is_wp_error($this->response) || '200' != $this->response['response']['code'] ) {
			$this->error_message = __( 'There was a problem connecting to the API.', 'woocommerce-quaderno' );
			$this->response = null;

			return false;
		}

		$this->response['body'] = json_decode($response['body'], true);

		return true;
	}
}