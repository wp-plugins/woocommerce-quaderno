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
	public static function exec($url, $method, $username, $password, $data = null)
	{
    $args = array(
  	  'method' => $method,
  	  'headers' => array(
  	    'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
  	    'Content-Type' => 'application/json'
  	  ),
  	  'timeout' => 70,
      'sslverify' => false
  	);

  	// Add the request body if we've got one
  	if ( !is_null( $data ) && is_array( $data ) && count( $data ) > 0 ) {
  		$args['body'] = json_encode( $data );
  	}

  	// Get results
  	$response = wp_remote_request($url, $args);

		// Decode data
		if ($response['body'])
			$response['body'] = json_decode($response['body'], true);

    return $response;
	}
}