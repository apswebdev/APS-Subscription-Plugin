<?php 
/*===================================================================
 *    : class tpcsendemail 
 *    : this will process the whole emailing list wubscriptions
 *===================================================================*/


/* get file dependents */
require_once("../../../../wp-load.php");


class tpcsendemail
{

		/*===================================================================
		 *    : initialize call
		 *    : prepare initial structure of email processing
		 *    : this will process all that is covered from email sending
		 *      RSS subscriptions  
		 *===================================================================*/
		public static function email_init()
		{
				
	            /* initialize all emails needed */         
				$emails = array();
				$emails = self::email_get_all();
				
				/* process send email and get failed messages */         
				$failed_message[] = self::email_process($emails);

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
		protected static function email_get_all()
		{
			    global $wpdb;
								
				$init_email = $wpdb->get_results( $wpdb->prepare("SELECT re_fname, 
																	 re_lname,
																	 re_email,
																	 re_cat 
															  FROM ".$wpdb->prefix."rss_email 
															  WHERE re_active = 'active' AND re_type = 'daily'"));
				
				return $init_email;
			
		}
		
		/*===================================================================
		 *    : initialize call
		 *    : prepare initial structure of email
		 *===================================================================*/
		public static function email_process($emails)
		{
				
				/* loop through email details */
				foreach($emails as $em => $val){
				      
					  /* check if mail is not yet sent */
					  if(self::email_check_snt($val->re_email)){
					  
							  /* get message format */
							  $message = self::email_construct($val->re_cat);
							  
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
tpcsendemail::email_init();
?>