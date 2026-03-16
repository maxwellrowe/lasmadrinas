<?php
// Make a Payment Form
// ID 21

// Add filter for each form
add_filter('gform_pre_render_21', 'populate_html_21');

function populate_html_21( $form ) {
	if ( empty( $form['id'] ) ) {
			return $form;
	}
	
	foreach( $form['fields'] as &$field ){
		// This is the HTML field that will hold the credit card form
		if( $field->id == 44) {
			ob_start();
			// Output for the HTML field
			?>
				
				<p><strong>Enter your credit card billing details below. You’ll complete your transaction in the next step.</strong></p>
				
				<div id="gpg-form"></div>
				
				<script src="https://jssdk.deepstack.io/payments/sdk/3.1.0/index.js"></script>
				<script>
					
					jQuery(window).load(function() {
						jQuery('#gform_next_button_21_43').hide();
					});
					
					const deepStack = new DeepStackSDK('pk_live_KxmuHoutRx3K0HlKcoeK3HqCNJkxWDks');
					
					const cardForm = deepStack.createForm('card-extended', {
							style: {
									base: {
											width: '560px'
									}
							},
							common: {
									preventImmediateSubmit: true
							}
					});
					
					cardForm.mount('gpg-form');
					
					cardForm.on('FORM_INITIALIZED', () => {
					
							cardForm.setBillingContact({
									phone: '',
									address: {
											country: 'USA',
											postalCode: '',
											state: '',
											city: '',
											lineOne: '',
											lineTwo: ''
									},
									email: '',
									firstName: '',
									lastName: ''
							});
					});
					
					cardForm.on('SUBMIT_CLICKED', () => {
					
						// Your logic here
						cardForm.submit();
					});
					
					cardForm.on('FORM_RESPONSE', (response) => {
						
						console.log(response);
						
						var token = response.token;
						
						if(token != null) {
							jQuery('#input_21_45').val(token);
							jQuery('#gform_next_button_21_43').show();
						}
						// show success message
						cardForm.showSuccess();
						
					}, (error) => {
						// Your logic here
						jQuery('.gpg-form-error-message').show();
						cardForm.showError(error.message);
					});
					
					cardForm.on('ONE_CLICK_PAYMENT_SUBMIT', (oneClickData) => {
						// Your logic here
					}, (error) => {
						// Your logic here
							cardExtended.showError(error);
							console.log(error);
					});
				</script>
				
			<?php
			
			$field->content = ob_get_contents();
			ob_end_clean();
		}
	}
	
	return $form;
}

// Prior to submission -- Required for each form as each is unique
add_action( 'gform_pre_submission_21', 'pre_submission_21' );

// TO DO: Can probably split this out to two functions, make this one more universal with the vars to pass to the second function
// Will need $subtotal, $token, $token_receiver, $total_value_receiver
function pre_submission_21( $form ) {
		
	$subtotal = rgpost( 'input_32' );
	$token = rgpost( 'input_45' );
	
	$total_value = floor($subtotal * 100);
	
	$_POST['input_46'] = $token; // This needs to correlate to the field that gets the token
	$_POST['input_47'] = $total_value; // This needs to correlate to the field that gets the total amount/ value
	
	// Field holding the response
	$response_field = 'input_48';
	
	// Endpoint
	$url = 'https://api.deepstack.io/api/v1/payments/charge';
	
	// POST Data
	$data = array(
			"source" => array(
					"type" => "card_on_file",
					"card_on_file" => array(
							"id" => $token
					)
			),
			"transaction" => array(
					"amount" => $total_value,
					"capture" => true,
					"currency_code" => "USD",
					"country_code" => "USA"
			)
	);
	
	// JSON Encode Data
	$data_json = json_encode($data);
	
	// Process the transaction
	process_transaction($data_json, $response_field);
}

?>