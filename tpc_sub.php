<?php 
/*
Plugin name: APS-Subscription-Plugin
Version: beta 1.0
Description: This is an email Subscription and RSS for WP
Author: Anwar Saludsong 
Author URI: http://apsaludsonglabs.com
Plugin URI: http://apsaludsonglabs.com
*/

class tpcrssemail
{

		/*===================================================================
		 *    : initialize hooks
		 *    : this will add styles and scripts and the form
		 *===================================================================*/
		public static function tpcre_init()
		{
			wp_register_style("tpcsub-style", plugins_url( '/tpc-sub/style/tpcsub.css' ) );
		 	wp_enqueue_script('tpcsub-json', plugins_url( '/tpc-sub/scripts/json.js' ) );
			wp_enqueue_script('tpcsub-func', plugins_url( '/tpc-sub/scripts/func.js' ) );
			wp_enqueue_script('tpcsub-ajax', plugins_url( '/tpc-sub/scripts/ajax.js' ) );
            wp_enqueue_style( 'tpcsub-style');
			add_action('admin_menu', array( __CLASS__, 'tpc_form_options'));
			add_shortcode('rss_fill', array( __CLASS__, 'display_subscription'));
		    add_filter( 'cron_schedules', array(__CLASS__,'new_cron_sched'));
			
			/* create db upon install */
		    register_activation_hook(__FILE__, array(__CLASS__, 'Install'));
            register_deactivation_hook(__FILE__, array(__CLASS__, 'Uninstall'));
			
			add_action('email_ajax_daily', array(__CLASS__, 'send_daily'));
			add_action('email_ajax_weekly', array(__CLASS__, 'send_weekly'));
			
		}

		/*===================================================================
		 *    : create DB
		 *    : this will initialize DB to be used by the plugin
		 *===================================================================*/
		public static function Install() {

				global $wpdb;
				
				$wpdb->query("  CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."rss_email (
									re_int INT(10) NOT NULL AUTO_INCREMENT,
									re_fname VARCHAR(200) NOT NULL,
									re_lname VARCHAR(200) NOT NULL,
									re_email VARCHAR(200) NOT NULL,
									re_cat VARCHAR(200) NOT NULL,
									re_active VARCHAR(200) NOT NULL,
									re_type VARCHAR(200) NOT NULL,
									re_flag VARCHAR(10),
									PRIMARY KEY (`re_int`)
								) 
								ENGINE=MyISAM
								DEFAULT CHARSET=utf8
								AUTO_INCREMENT=1");
				
				$wpdb->query("  CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."rss_email_meta (
									meta_int INT(10) NOT NULL AUTO_INCREMENT,
									meta_value VARCHAR(200) NOT NULL,
									PRIMARY KEY (`meta_int`)
								) 
								ENGINE=MyISAM
								DEFAULT CHARSET=utf8
								AUTO_INCREMENT=1");

				$wpdb->query("  CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."rss_email_cron (
					cron_int INT(10) NOT NULL AUTO_INCREMENT,
					cron_value VARCHAR(200) NOT NULL,
					PRIMARY KEY (`cron_int`)
						) 
						ENGINE=MyISAM
						DEFAULT CHARSET=utf8
						AUTO_INCREMENT=1");
                
				/* initialize cron jobs */				
				self::cron_init();								
		}		
		
		/*===================================================================
		 *    : drop DB
		 *    : this will delete database upon unsintall of plugin
		 *===================================================================*/
		public static function Uninstall() {

				global $wpdb;
				
				$wpdb->query("  DROP TABLE IF EXISTS ".$wpdb->prefix."rss_email");
				
				$wpdb->query("  DROP TABLE IF EXISTS ".$wpdb->prefix."rss_email_meta");
				
				$wpdb->query("  DROP TABLE IF EXISTS ".$wpdb->prefix."rss_email_cron");
				
				wp_clear_scheduled_hook('email_ajax_daily');
				
				wp_clear_scheduled_hook('email_ajax_weekly');

		}			
		
		/*===================================================================
		 *    : form setup hook
		 *    : this will initialize the plugin hook in dashboard
		 *===================================================================*/
		public static function tpc_form_options()
		{
			
			$page_title = 'TPC Rss Email Options';
			$menu_title = 'TPC Rss-Email';
			$capability = 'administrator';
			$menu_slug = 'tpcrssemail';
		
			add_object_page( $page_title, 
							 $menu_title, 
							 $capability, 
							  $menu_slug,
							 array( __CLASS__, 'opt_form'));
			
		}

		/*===================================================================
		 *    : display form
		 *    : this will render the actual form for processing git
		 *===================================================================*/
		public static function opt_form()
		{

					$prefix = 'dbt_';
					$meta_box = array(
					'id' => 'cat-meta-box',
					'title' => 'TPC - Rss Email Subscription Options',
					'page' => 'post',
					'context' => 'normal',
					'priority' => 'high',
					'fields' => array(
									array(
									'name' => 'Text box',
									'desc' => 'Enter something here',
									'id' => $prefix . 'text',
									'type' => 'button',
									'std' => 'Update Category Selections'
									)
							)
					);
					
                add_meta_box($meta_box['id'], 
				             $meta_box['title'], 
							 array( __CLASS__, 'show_box'), 
							 "tpcrssemail", 
							 $meta_box['context'], 
							 $meta_box['priority']); 

				do_meta_boxes('tpcrssemail',$meta_box['context'],$meta_box['priority']);

		}
		
		/*===================================================================
		 *    : display form
		 *    : this will render the actual form for processing git
		 *===================================================================*/
		public static function show_box()
		{  			
					global $post;
					$prefix = 'dbt_';
					$meta_box = array(
					'id' => 'cat-meta-box',
		            'title' => 'TPC - Rss Email Subscription Options',
					'page' => 'post',
					'context' => 'normal',
					'priority' => 'high',
					'fields' => array(
									array(
									'name' => '',
									'desc' => 'Click to Process Your Request',
									'id' => $prefix . 'text',
									'type' => 'button',
									'std' => 'Update Category Selections'
									)
							)
					);
				
				echo '<input type="hidden" name="mytheme_meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';
				echo '<table class="form-table">';
				
				echo'<div class="list">';
				
				echo'<h3 style="margin-top:-20px">Select Category List to exclude:</h3>';
				echo'<div style="font-size:12px; margin:-12px 0px 15px 0px">This will enable users to select categories that will not be displayed from the selections in the RSS list </div>';
				
				$categories = get_categories('orderby=name');  
				$wp_cats = array();  
                
				foreach ($categories as $category_list ) 
				{  
				   echo "<div class='list_item'><input type='checkbox' value='".$category_list->cat_ID."' ".
				   		self::checked($category_list->cat_ID)."/>&nbsp;&nbsp;";
				   echo  $category_list->cat_name.'</div>';
				   echo '<br/>';
				}
				

				
				foreach ($meta_box['fields'] as $field) {
				
						$meta = get_post_meta($post->ID, $field['id'], true);
						
						switch ($field['type']) {
						
								case 'button':
								echo '<input type="button"  class="button-primary" onClick="update_list()" name="', $field['id'], 
								'" id="', $field['id'], '" value="', 
								$meta ? $meta : $field['std'], '" size="30" style="float:left; width:200px" />';
								echo '<div id= "rem"></div>', '<br />';

								
								break;

						}
		

				}
				
				echo '</div>';
				
				echo'<div class="list" style="height:180px">';
				echo'<h3 style="margin-top:-20px">Manual Campaign RSS</h3>';
				echo'<div style="font-size:12px; margin:-12px 0px 15px 0px">This will enable admin to manually send the RSS email notifications to subscribers if the cron job is not set on cpanel. </div>';
				echo '<input style="clear:both; float:left; margin-top:12px" type="button" class="button-primary" onClick="send_campaign()"'.
					 ' value="Send Daily Rss Campaign"><div id= "rem2"></div>';
				echo '<div style="float:left; clear:both">Send Emails to Daily Subscribers. This will send posts that belongs to the user\'s selected categories that was posted within the same date of this submission.</div>';
				echo '<input style="clear:both; float:left; margin-top:12px" type="button" class="button-primary" onClick="send_campaign_weekly()"'.
					 ' value="Send Weekly Rss Campaign"><div id= "rem3"></div>';
				echo '<div style="float:left; clear:both">Send Emails to Weekly Subscribers. This will send posts that belongs to the user\'s selected categories that was posted within the last 7 days of submission.</div>';
                echo'</div>';  
				echo '</table>';
				
		}

		/*===================================================================
		 *    : display form
		 *    : this will render the actual form for processing git
		 *===================================================================*/
		public static function checked($id)
		{  	
  				global $wpdb;
				
				$result = $wpdb->get_results( $wpdb->prepare("SELECT meta_value 
											  				  FROM ".$wpdb->prefix."rss_email_meta 
											  				  WHERE meta_int = 1"));
		        
	            foreach($result as $res){
				
					$meta_val = $res->meta_value;
				
				}
				
				$lst_arr = explode("|", $meta_val);
				
				if(in_array($id,$lst_arr)){
				      
					  return "checked";
				
				} else {
				
					  return "";
				
				}
		}
		
		/*===================================================================
		 *    : display subscription
		 *    : this will render subscription in a page
		 *===================================================================*/
		public static function display_subscription()
		{?>  	
                <!-- javascript inside php to use wp codes --> 
				<script type="text/javascript" src="../wp-content/plugins/tpc-sub/scripts/jquery.js"></script>
				<script type="text/javascript">
						function generate_rss(){
							
							var check =""; var check2 ="";
							
							jQuery(".classcat:checked").each(function(){
							
								check = check + "," + jQuery(this).val();
								
								check2 = check2 + "-" + jQuery(this).val();
							
							});
							
							if(check!=""){
								    
									jQuery("#gen_rss, #rss_list_inner").fadeOut();
									
									jQuery('.addthis_toolbox').remove();
									
									check = check.substring(1, check.length);
									
									check2 = check2.substring(1, check2.length);
									
									jQuery("#cat_selected").val(check2);
									
									var link = "<?php echo get_bloginfo('wpurl'); ?>/feed/?cat=" + check; 
									
									jQuery("#rss_a").attr('href',link);
									
									var htm = '<div class="addthis_toolbox addthis_default_style " addthis:url="'+link+'"' + 
									' addthis:title="Custom Rss Feed" addthis:description="Custom Rss Feed>'+
									'<a class="addthis_button_preferred_1"></a>' +
									'<a class="addthis_button_preferred_2"></a>' +
									'<a class="addthis_button_preferred_3"></a>' +
									'<a class="addthis_button_preferred_4"></a>' +
									'<a class="addthis_button_compact"></a>' +
									'<a class="addthis_counter addthis_bubble_style"></a>'+
									'</div>' +
									'<script type="text/javascript"> var addthis_config = {"data_track_addressbar":true}' + ';' + '</' + 'script>' +
						            '<' + 'script type="text/javascript"'+
									'src="http:'+'//'+'s7.addthis.com/js/300/addthis_widget.js'+'#'+'pubid=ra-506f074158c05f68"></'+'script>';
									
									var html2 = "<span class='st_sharethis_large' displayText='ShareThis'></span>" +
												"<span class='st_facebook_large' displayText='Facebook'></span>" +
												"<span class='st_twitter_large' displayText='Tweet'></span>" +
												"<span class='st_linkedin_large' displayText='LinkedIn'></span>" +
												"<span class='st_email_large' displayText='Email'></span>";
									html2 += '<script type="text/javascript">var switchTo5x=true' + ';' + '</s' + 'cript>' +
											'<scr' + 'ipt type="text/javascript" src="http://w.sharethis.com/button/buttons.js">' + '</sc' + 'ript>' +
											'<script type="text/javascript">stLight.options({publisher: "3789dd1a-3eee-48e5-87ee-1e41eaf970f8"})' + ';' 
											+ '</sc' + 'ript>';			
									
									jQuery("#rss_result").fadeOut('slow',function(){
											jQuery('#rss_result').append(htm);
											jQuery("#rss_result").fadeIn('slow');
												
										});
				
							} else {
				
								   alert("Select at least one category!");
				
							}
						}
						
						jQuery("#rss_a2").live('click',function(){
							
							jQuery(".email_sub").fadeIn('slow');
						
						});
						
						jQuery(".email_back").live('click',function(){
							
							jQuery(".email_sub").fadeOut('slow');
							jQuery("#f_name").val('');
							jQuery("#l_name").val('');
							jQuery("#email_in").val('');
							jQuery("#response").html('');
							jQuery("#recaptcha_response_field").val('');
							jQuery("#recaptcha_reload").click();
						
						});
				
				</script>
				<?php
				$categories = get_categories('orderby=name');  
				$wp_cats = array();  
                echo '<div class="email_sub email_back">test</div>'; ?>
				
  			    <!-- setup email subscription with captcha -->
				<div class="email_sub email_submain">
				        <h2>Subscribe Rss Through Email</h2>
						<form id="sub_form" method="post" action="../wp-content/plugins/tpc-sub/ajax/subscribe_ajax.php">
                              
							 <input type="hidden" id="cat_selected">   
							 <!-- captcha validation form --> 
							 <div id="form_inputs">
						        <div class="f_title">First Name</div> 
							 	<input type="text" id="f_name" name="f_name" style="width:200px !important"><br/>
								
								<div class="f_title">Last Name</div> 
							 	<input type="text" id="l_name" name="l_name" style="width:200px !important"><br/>
							    
								<div class="f_title">Email</div> 
							 	<input type="text" id="email_in" name="email_in" style="width:200px !important"><br/>
								
								<h3>Receive this email by:</h3>
								<input type="radio" name="send_type" value="daily" id="send_type">&nbsp; Daily
								<input type="radio" name="send_type" value="weekly" id="send_type"  checked="checked">&nbsp; Weekly
							    
							 	<input type="hidden" id="category_selected">
							 
							 </div>
							 
								<?php

								  require_once('includes/recaptchalib.php');

								  $publickey = "6Le7n9YSAAAAAOVi1zjwEeCSVkNEB49LKJMn_3IE"; 

								  echo recaptcha_get_html($publickey);

								?>
								
								<input type="button" id="submit_form" onClick="subscribe_campaign()" value="Subscribe" />

						</form>
						
						<div id="response"></div>
				
				</div>
  			    
				<?php
				echo '<div id="rss_list_page">';
				
				echo '<div id="rss_title"><img src="../wp-content/plugins/tpc-sub/images/rss.png" style="width:30px;height:30px">'.
				'&nbsp;<span id="textle">Custom RSS feed</span></div>';

				echo '<div id="rss_list_inner">';
				
				echo '<div id="rss_list_sel">Select from our category list</div>';
				 
				foreach ($categories as $category_list ) 
				{  
				   if(self::checked($category_list->cat_ID) != "checked"){
						   echo "<div class='list_item'><input class='classcat' type='checkbox' value='".$category_list->cat_ID."'/>&nbsp;<span style='color:#6495ED'>";
						   echo  $category_list->cat_name.'</span></div>';

				   }
				}
				
			    echo '</div>';?>
				
				<a href="javascript:;" id="gen_rss" onClick="generate_rss()">Generate RSS Feed</a>
				
				<div id="rss_result">
				        
						<img src="../wp-content/plugins/tpc-sub/images/rss2.png">
						
						<a id="rss_a" href="">Custom RSS Feed</a> 
						
						<div id="rss_email_sub">
							
							<img src="../wp-content/plugins/tpc-sub/images/email.png">
							<a id="rss_a2" href="javascript:;">Email Subscription</a> 
						
						</div>
						
				
				</div>
				
				<?php
				echo '</div>';

		}	
	/*===================================================================
	 *    : check initial run
	 *    : this will check if cron has been invoked already
	 *    : invoke if needed
	 *===================================================================*/
	public static function send_daily(){
           
		   self::email_init('daily');		
	
	}

	/*===================================================================
	 *    : check initial run
	 *    : this will check if cron has been invoked already
	 *    : invoke if needed
	 *===================================================================*/
	public static function send_weekly(){
           
		   self::email_init('weekly');		
	
	}		
		
	/*===================================================================
	 *    : check initial run
	 *    : this will check if cron has been invoked already
	 *    : invoke if needed
	 *===================================================================*/
	public static function cron_init(){
		
		 if(self::cron_check()){
		 	
			self::cron_insert();
			
			wp_schedule_event(current_time( 'timestamp' ) + 300, 'daily', 'email_ajax_daily');
			wp_schedule_event(current_time( 'timestamp' ) + 600, 'weekly', 'email_ajax_weekly');
		 
		 }
	
	}

	/*===================================================================
	 *    :  interval settings
	 *    :  will set intervals for new cron setup( for testing purposes
	 *===================================================================*/
	public static function new_cron_sched(){
         
			return array(
				'in_per_min' => array(
					'interval' => 30,
					'display' => 'In every Mintue2'
				),			
				'in_per_minute' => array(
					'interval' => 60,
					'display' => 'In every Mintue'
				),
				'three_min' => array(
					'interval' => 60 * 3,
					'display' => 'In every two Mintues'
				),
				'three_hourly' => array(
					'interval' => 60 * 60 * 3,
					'display' => 'Once in Three minute'
				)
			);
	}

	/*===================================================================
	 *    : check cron flag
	 *    : return true if not yet invoked
	 *===================================================================*/
	protected static function cron_check(){
		
			  global $wpdb;
			  
			  $check = $wpdb->get_results($wpdb->prepare("SELECT cron_int
												 		  FROM ".$wpdb->prefix."rss_email_cron	 			
									                      WHERE cron_value = 'activated'"));  
	
			  if(!empty($check)) return false; else return true;
	
	
	}
	/*===================================================================
	 *    : insert flag 
	 *    : insert initial flag for cron run
	 *===================================================================*/
	protected static function cron_insert(){
		
			  global $wpdb;
			  
			  $check = $wpdb->get_results($wpdb->prepare("INSERT INTO ".$wpdb->prefix."rss_email_cron	 			
									                      VALUES(1, 'activated')"));  
	
	}

		/*===================================================================
		 *    : initialize call
		 *    : prepare initial structure of email processing
		 *    : this will process all that is covered from email sending
		 *      RSS subscriptions  
		 *===================================================================*/
		public static function email_init($method)
		{
				
	            /* initialize all emails needed */         
				$emails = array();
				$emails = self::email_get_all($method);
				
				/* process send email and get failed messages */         
				$failed_message[] = self::email_process($emails,$method);

				$chk = 0;
				
				foreach($failed_message as $fail){
						
					if(!empty($fail)){
					    $chk++;	
					}
					
				}
				
				/* return failed messages */         
				if($chk != 0){
					var_dump($failed_message);
				} else {
					echo "Successfully Sent All Notifications";
				}	 		
		}
		


		/*===================================================================
		 *    : get emails
		 *    : prepare all emails for sending
		 *===================================================================*/
		protected static function email_get_all($method)
		{
			    global $wpdb;
				
				if ($method == "daily"){				
	
						$init_email = $wpdb->get_results( $wpdb->prepare("SELECT re_fname, 
																			 re_lname,
																			 re_email,
																			 re_cat 
																	  FROM ".$wpdb->prefix."rss_email 
																	  WHERE re_active = 'active' AND re_type = 'daily'"));
				} elseif ($method == "weekly"){
	
						$init_email = $wpdb->get_results( $wpdb->prepare("SELECT re_fname, 
																			 re_lname,
																			 re_email,
																			 re_cat 
																	  FROM ".$wpdb->prefix."rss_email 
																	  WHERE re_active = 'active' AND re_type = 'weekly'"));
				
				}
				return $init_email;
			
		}
		
		/*===================================================================
		 *    : initialize call
		 *    : prepare initial structure of email
		 *===================================================================*/
		protected static function email_process($emails,$method)
		{
				
				/* loop through email details */
				foreach($emails as $em => $val){
				      
					  /* check if mail is not yet sent */
					  if(self::email_check_snt($val->re_email)){
					  
							  /* get message format */
							  if($method == 'daily'){
							  	$message = self::email_construct($val->re_cat);
							  }elseif($method == 'weekly'){
							  	$message = self::email_construct_weekly($val->re_cat);
							  }
							  
							  /* send message to recipients */
							  if(!empty($message)){
							  	$failed[] = self::email_send($val->re_fname, $val->re_lname, $val->re_email, $message);
							  }
					  }
				
				}
				
		}
		
		/*===================================================================
		 *    : construct email
		 *    : prepare structure of email to be sent as html
		 *===================================================================*/
		protected static function email_construct($cat)
		{
			    global $wpdb;
				
				$pos = strpos($cat, "-");
				
				$details = array();
						
				if($pos != false){
					
						$cat = explode("-",$cat);
						
						foreach($cat as $c){
								
								$cat_name = get_category($c);
								
								$margin_date = date('Y-m-d');
								
								$init_email = $wpdb->get_results($wpdb->prepare("SELECT ".$wpdb->prefix."posts.post_title,
																						  ".$wpdb->prefix."posts.post_date,  
																						  ".$wpdb->prefix."posts.guid
																				  FROM ".$wpdb->prefix."posts
																				  JOIN ".$wpdb->prefix."term_relationships
																		ON ".$wpdb->prefix."posts.ID =".$wpdb->prefix."term_relationships.object_id				
																		WHERE ".$wpdb->prefix."term_relationships.term_taxonomy_id =" . $c));
															   
								foreach ($init_email as $e){

										  $post_date = date( 'Y-m-d', strtotime($e->post_date) );
										  
										  if( $post_date = $margin_date ){
										  
										  	$details[] = array($cat_name->name, $e->post_title, $e->post_date, $e->guid,);
								          
										  }
								
								}
						
						}
			   } else {
				   
				   				$cat_name = get_category($cat);
								
								$margin_date = date('Y-m-d');
								
								$init_email = $wpdb->get_results($wpdb->prepare("SELECT ".$wpdb->prefix."posts.post_title,
																						  ".$wpdb->prefix."posts.post_date,  
																						  ".$wpdb->prefix."posts.guid
																				  FROM ".$wpdb->prefix."posts
																				  JOIN ".$wpdb->prefix."term_relationships
																		ON ".$wpdb->prefix."posts.ID =".$wpdb->prefix."term_relationships.object_id				
																		WHERE ".$wpdb->prefix."term_relationships.term_taxonomy_id =" . $cat));
															   
								foreach ($init_email as $e){
									      
										  $post_date = date( 'Y-m-d', strtotime($e->post_date) );
										  
										  if( $post_date >= $margin_date ){
										  
										  	$details[] = array($cat_name->name, $e->post_title, $e->post_date, $e->guid,);
								          
										  }
								}
			   
			   
			   }
				
			   return self::email_to_html($details);
		}

		/*===================================================================
		 *    : construct email
		 *    : prepare structure of email to be sent as html
		 *===================================================================*/
		protected static function email_construct_weekly($cat)
		{
			    global $wpdb;
				
				$pos = strpos($cat, "-");
				
				$details = array();
						
				if($pos != false){
					
						$cat = explode("-",$cat);
						
						foreach($cat as $c){
								
								$cat_name = get_category($c);
								
								$margin_date = date( 'Y-m-d', strtotime('-7 days') );
								
								$init_email = $wpdb->get_results($wpdb->prepare("SELECT ".$wpdb->prefix."posts.post_title,
																						  ".$wpdb->prefix."posts.post_date,  
																						  ".$wpdb->prefix."posts.guid
																				  FROM ".$wpdb->prefix."posts
																				  JOIN ".$wpdb->prefix."term_relationships
																		ON ".$wpdb->prefix."posts.ID =".$wpdb->prefix."term_relationships.object_id				
																		WHERE ".$wpdb->prefix."term_relationships.term_taxonomy_id =" . $c));
															   
								foreach ($init_email as $e){

										  $post_date = date( 'Y-m-d', strtotime($e->post_date) );
										  
										  if( $post_date > $margin_date ){
										  
										  	$details[] = array($cat_name->name, $e->post_title, $e->post_date, $e->guid,);
								          
										  }
								
								}
						
						}
			   } else {
				   
				   				$cat_name = get_category($cat);
								
								$margin_date = date( 'Y-m-d', strtotime('-7 days') );
								
								$init_email = $wpdb->get_results($wpdb->prepare("SELECT ".$wpdb->prefix."posts.post_title,
																						  ".$wpdb->prefix."posts.post_date,  
																						  ".$wpdb->prefix."posts.guid
																				  FROM ".$wpdb->prefix."posts
																				  JOIN ".$wpdb->prefix."term_relationships
																		ON ".$wpdb->prefix."posts.ID =".$wpdb->prefix."term_relationships.object_id				
																		WHERE ".$wpdb->prefix."term_relationships.term_taxonomy_id =" . $cat));
															   
								foreach ($init_email as $e){
									      
										  $post_date = date( 'Y-m-d', strtotime($e->post_date) );
										  
										  if( $post_date > $margin_date ){
										  
										  	$details[] = array($cat_name->name, $e->post_title, $e->post_date, $e->guid,);
								          
										  }
								}
			   
			   
			   }
				
			   return self::email_to_html($details);
		}

		/*===================================================================
		 *    : email to html
		 *    : this will convert message to html
		 *===================================================================*/
		protected static function email_to_html($details)
		{           
		    $message="";
			
		    if (!empty($details)){            
					$message = '<div style="padding:50px; border:1px solid #999; float:left ">
								<h3 style="font-family:Arial, Helvetica, sans-serif; color:#999">' .
								'Your Daily Subscription Notice as of ' . date('l jS \of F Y') . '</h3>
								<hr/>
								<h5 style="font-family:Arial, Helvetica, sans-serif; color:#999">Check Our Articles that you might want to read:</h5>';
					
					$cat = "";			
					
					foreach($details as $d => $val){
							  
							  if($cat != $val[0]){
								   
								   $cat = $val[0];
								   
								   $message .= '<h4 style="float:left; clear:both; margin-bottom:5px;"><u>'.$cat.'</u></h4>';
							  
							  }
							  
							  $message .=  '<a href="'.$val[3].'" style="text-decoration:none; clear:both; float:left;">'.$val[1].' - '
							  . date("F j, Y", strtotime($val[2])) . '</a>';
						
					
					}
		
								
					$message .='</div>'; 
			}
			
			return $message;
						
		}		


		/*===================================================================
		 *    : send emails
		 *    : prepare initial structure of email
		 *===================================================================*/
		protected static function email_send($fname, $lname, $email, $message)
		{

					$subject = "To: $fname, $lname Our Rss Update";
					
					add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
					
					wp_mail($email, $subject, $message, $headers);
					
					self::update_status_mail($fname,$lname,$email);					         
			
		}
		
		/*===================================================================
		 *    : email_check_sent
		 *    : check if email is sent already today
		 *===================================================================*/
		protected static function email_check_snt($recipient)
		{
			  
			  global $wpdb;
			  
			  $recipient = trim($recipient);
			  
			  $check = "";
			  
			  $now = date('Y-m-d');
			  
			  $check = $wpdb->get_results($wpdb->prepare("SELECT re_int
												 		  FROM ".$wpdb->prefix."rss_email	 			
									                      WHERE re_email = '$recipient' AND re_flag = '$now'"));  
	
			  if(!empty($check)) return false; else return true;
			
		}

		/*===================================================================
		 *    : update status emails
		 *    : to update the date today of the record
		 *===================================================================*/
		protected static function update_status_mail($fname, $lname, $email)
		{       
		 		global $wpdb;
				
				$now = date('Y-m-d');
				
				$wpdb->get_results($wpdb->prepare("UPDATE ".$wpdb->prefix."rss_email
				                                   SET re_flag = '$now'
										           WHERE re_fname = '$fname' AND re_lname = '$lname' AND re_email = '$email'"));
			
		}	

}

/* initialize call */
tpcrssemail::tpcre_init();
?>