<?php

//===================================================
// ajax parameters
//===================================================
$data = str_replace('\\','',$_POST['data']); 

require_once("../../../../wp-load.php");

class update_meta

{

		/*===================================================================
		 *    : initialize call
		 *    : prepare initial call methods
		 *===================================================================*/
		public static function meta_init($data)
		{
			 
			  $data = trim($data);	
			  
			  self::meta_update($data);
	         
		}
		
		/*===================================================================
		 *    : initialize call
		 *    : prepare initial call methods
		 *===================================================================*/
		public static function meta_update($data)
		{
			
				global $wpdb;

				$static_id = 1;
				
				$data;
				
				if(self::meta_exist()){
				
						$wpdb->get_results($wpdb->prepare("UPDATE ".$wpdb->prefix."rss_email_meta
                                                                                   SET meta_value = '$data'
                                                                                   WHERE meta_int = $static_id"));
						$message = "You have successfully Updated List!";

				} else {
				
						$wpdb->get_results($wpdb->prepare("INSERT INTO ".$wpdb->prefix."rss_email_meta
										  VALUES($static_id, '$data')"));
						$message = "You have successfully Inserted List!";
				
				}
                
                                 echo $message ."-" .self::meta_exist(); 
		}

		/*===================================================================
		 *    : initialize call
		 *    : prepare initial call methods
		 *===================================================================*/
		public static function meta_exist()
		{
			
				global $wpdb;

				$static_id = 1;
				
				$result = $wpdb->get_results( $wpdb->prepare("SELECT meta_value 
                                                                              FROM ".$wpdb->prefix."rss_email_meta 
                                                                              WHERE meta_int = $static_id"));
				
				foreach($result as $res){
					$meta_val = $res->meta_value;
				}
				
				if(!empty($meta_val)){
					
					return true;
					
				} else {
				
					return false;
				
				}
	
		}
		


}

/* initialize update class */
update_meta::meta_init($data);

?>