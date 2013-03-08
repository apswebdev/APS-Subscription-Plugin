// JavaScript Document

function update_list(){
        
		var parameters = "";

		jQuery("input:checked").each(function(){
           
		   parameters = parameters + "|" + jQuery(this).val();
		  		  
		});
		
		jQuery.ajax({
			type: 'POST',
			url: '../wp-content/plugins/tpc-sub/ajax/update_ajax.php',
			data: { data: parameters },
			dataType: 'html',
			
			beforeSend: function() {
				jQuery('#rem').fadeIn('slow');
				jQuery('#rem').html('<img src="../wp-content/plugins/tpcgit/images/loader.png" style="margin-right:5px">updating categories....');
			},
			
			success: function(data) {
				jQuery('#rem').html(data);
			},
			error: function (responseData) {
				jQuery('#rem').html("Error: Process Failed");
			}
		});

}

function send_campaign(){
        
		jQuery.ajax({
			type: 'POST',
			url: '../wp-content/plugins/tpc-sub/ajax/email_ajax_daily.php',
			data: { data: "" },
			dataType: 'html',
			
			beforeSend: function() {
				jQuery('#rem2').fadeIn('slow');
				jQuery('#rem2').html('<img src="../wp-content/plugins/tpcgit/images/loader.png" style="margin-right:5px">sending campaign to all....');
			},
			
			success: function(data) {
				jQuery('#rem2').html(data);
			},
			error: function (responseData) {
				jQuery('#rem2').html("Error: Process Failed");
			}
		});

}
function send_campaign_weekly(){
        
		jQuery.ajax({
			type: 'POST',
			url: '../wp-content/plugins/tpc-sub/ajax/email_ajax_weekly.php',
			data: { data: "" },
			dataType: 'html',
			
			beforeSend: function() {
				jQuery('#rem3').fadeIn('slow');
				jQuery('#rem3').html('<img src="../wp-content/plugins/tpcgit/images/loader.png" style="margin-right:5px">sending campaign to all....');
			},
			
			success: function(data) {
				jQuery('#rem3').html(data);
			},
			error: function (responseData) {
				jQuery('#rem3').html("Error: Process Failed");
			}
		});

}

function subscribe_campaign(){
        
		jQuery.ajax({
			type: 'POST',
			url: '../wp-content/plugins/tpc-sub/ajax/subscribe_ajax.php',
			data: { challenge: jQuery("#recaptcha_challenge_field").val(), response: jQuery("#recaptcha_response_field").val(),
			        send_type: jQuery('#send_type:checked').val(), fname: jQuery('#f_name').val(),
					lname: jQuery('#l_name').val(), email: jQuery('#email_in').val(),cat: jQuery('#cat_selected').val()},
			dataType: 'html',
			
			beforeSend: function() {
				
				jQuery('#response').html('<img src="../wp-content/plugins/tpcgit/images/loader.png" style="margin-right:5px">processing....');
			},
			
			success: function(data) {
				jQuery('#response').html(data);
			},
			error: function (responseData) {
				jQuery('#response').html("Error: Process Failed");
			}
		});

}


		
		