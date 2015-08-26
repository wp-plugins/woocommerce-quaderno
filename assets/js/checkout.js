jQuery(document).ready( function ( $ ) {
  'use strict';
	var eu_countries = ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'GB'];
	
	$('#billing_country').change(function(){
	  $('#vat_number_field').toggle($.inArray($('#billing_country').val(), eu_countries) != -1);
	});
	
	$('#billing_postcode').change(function () {
	  $('body').trigger('update_checkout');
	});
	
	$('#vat_number').change(function() {
		$('body').trigger('update_checkout');
	});

} );