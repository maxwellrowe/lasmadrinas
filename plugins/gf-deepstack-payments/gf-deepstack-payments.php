<?php
	/*
	Plugin Name: Gravity Forms Deepstack Payment Integration
	Plugin URI: https://mackeycreativelab.com
	description: Functions to integrate Deepstack Payments with Gravity Forms
	Version: 1
	Author: Mackey
	Author URI: https://mackeycreativelab.com
	License: GPL2
	*/
	
	// Vars
	$ds_appid = 'sk_live_8a92ac9c-70ad-4239-95a2-41a5';
	$ds_shared_secret = 'bpIWu6mU6Iyq2iHyPObzFzd8dLML4+wKQgylMfqXzks=';
	$ds_sdk_url = 'https://jssdk.deepstack.io/payments/sdk/3.1.0/index.js';
	$ds_pub_api_key = 'pk_live_KxmuHoutRx3K0HlKcoeK3HqCNJkxWDks';
	
	// Forms -- here we include unique functions for each form
	include( plugin_dir_path( __FILE__ ) . '/forms/make-a-payment.php');
	include( plugin_dir_path( __FILE__ ) . '/forms/annual-appeal.php');
	include( plugin_dir_path( __FILE__ ) . '/forms/ball-payment-member.php');
	include( plugin_dir_path( __FILE__ ) . '/forms/ball-payment-debutante.php');
	include( plugin_dir_path( __FILE__ ) . '/forms/debutante-family-donation.php');
	include( plugin_dir_path( __FILE__ ) . '/forms/honor-a-debutante.php');
	include( plugin_dir_path( __FILE__ ) . '/forms/pay-dues.php');
	include( plugin_dir_path( __FILE__ ) . '/forms/public-support-donation.php');
	include( plugin_dir_path( __FILE__ ) . '/forms/stag-ball-payment.php');
	
	// Globaly usable and available
	// Process Transactions
	// Variables that are required are $data_json and field for the response $response_field
	function process_transaction($data_json, $response_field) {
		// Set up HMAC Variables
		/* OLD
		$appID = 'sk_test_8fe27907-c359-4fe4-ad9b-eaaa';
		$secret_key = 'JC6zgUX3oZ9vRshFsM98lXzH4tu6j4ZfB4cSOqOX/xQ=';
		$method = 'POST';
		$time = date("Y-m-d\Th:i:s.v\Z");
		$nonce = wp_create_nonce();
		$pub_key = 'pk_test_7H5GkZJ4ktV38eZxKDItVMZZvluUhORE';
		*/
		
		$appID = 'sk_live_8a92ac9c-70ad-4239-95a2-41a5';
		$secret_key = 'bpIWu6mU6Iyq2iHyPObzFzd8dLML4+wKQgylMfqXzks=';
		$method = 'POST';
		$time = date("Y-m-d\Th:i:s.v\Z");
		$nonce = wp_create_nonce();
		$pub_key = 'pk_live_KxmuHoutRx3K0HlKcoeK3HqCNJkxWDks';
		
		// Binary Key
		$binary_key = base64_decode($secret_key);
		
		// String for HMAC
		$string = $appID . "|" . $method . "|" . $time . "|" . $nonce . "|" . $data_json;
		
		// HMAC encoded base 64
		$hmac = base64_encode(hash_hmac('sha256', $string, $binary_key, true));
		
		$final_hmac = base64_encode($appID . "|" . $method . "|" . $time . "|" . $nonce . "|" . $hmac);
		
		// Endpoint
		$url = 'https://api.deepstack.io/api/v1/payments/charge';
		
		$curl = curl_init();
		
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => $data_json,
			CURLOPT_HTTPHEADER => [
				"hmac: $final_hmac",
				"Content-Type: application/json"
			],
		]);
		
		$response = curl_exec($curl);
		$response_array = json_decode($response);
		
		$err = curl_error($curl);
		
		curl_close($curl);
		
		if ($err) {
			$_POST[$response_field] = "cURL Error #:" . $err;
		} else {
			$_POST[$response_field] = 'TRANSACTION ID: ' . $response_array->id . ' APPROVED (1 = true, 0 = false)?: ' . $response_array->approved . ' // *INTERNAL*: ' .$response . '/// json data:' . $data_json . '// hmac: ' . $hmac . '// nonce: ' . $nonce . '// string: ' . $string;
		}
	}
	 
?>