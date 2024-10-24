jQuery( function($) {

	var $subscription_type_field_wrapper = $('#pms-subscription-plan-type').closest('.pms-meta-box-field-wrapper');
	var $seats_field_wrapper   			 = $('#pms-subscription-plan-seats').closest('.pms-meta-box-field-wrapper');
	var $price_field_wrapper   			 = $('#pms-subscription-plan-price').closest('.pms-meta-box-field-wrapper');

	/**
	 * Move Seats under price
	 *
	 */
	$price_field_wrapper.after( $seats_field_wrapper );

	/**
	 * Show appropiate fields on document ready
	 *
	 */
	 if( $subscription_type_field_wrapper.find('select').val() == 'group' ){
		 jQuery( 'input', $seats_field_wrapper ).attr( 'required', true )
		 $seats_field_wrapper.show()
	 } else {
		 jQuery( 'input', $seats_field_wrapper ).attr( 'required', false )
		 $seats_field_wrapper.hide();
	 }


	/**
	 * Show appropiate fields (duration or expiration and renewal) on subscription type select change
	 *
	 */
	$(document).on( 'change', '#pms-subscription-plan-type', function() {

		if( $subscription_type_field_wrapper.find('select').val() == 'group' ){
   		 jQuery( 'input', $seats_field_wrapper ).attr( 'required', true )
   		 $seats_field_wrapper.show()
   	 } else {
   		 jQuery( 'input', $seats_field_wrapper ).attr( 'required', false )
   		 $seats_field_wrapper.hide();
   	 }

	});

	/**
	 * Make sure the Seats field is not empty
	 */
	$(document).on( 'click', '#publish, #save-post', function() {

		if( $subscription_type_field_wrapper.find('select').val() == 'group' && $('#pms-subscription-plan-seats').val().trim() == '' ) {

			alert( 'You need to define the number of seats for this Group Subscription.' );

			return false;

		}

	});

});
