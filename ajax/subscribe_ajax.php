<?php
//===================================================================
//     : parameters
//     : 
//===================================================================
$fname = trim($_POST["fname"]);
$lname = trim($_POST["lname"]);
$email = trim($_POST["email"]);
$type =  trim($_POST["send_type"]);
$cat = trim($_POST["cat"]); 
$challenge = $_POST["challenge"];
$response = $_POST["response"];

//===================================================================
//     : register file dependents 
//     : 
//===================================================================
  require_once('../includes/recaptchalib.php');
  require_once("../../../../wp-load.php");


//===================================================================
//     : register file dependents 
//     : 
//===================================================================
class subscribe
{
    
	//===================================================================
	//     : run check  
	//     : check validity of captcha codes
	//===================================================================
	public static function init_subscribe($challenge, $response, $fname, $lname, $email, $type, $cat){
		
		  $privatekey = "6Le7n9YSAAAAALfMjKx_sGxeGL4M6Th3q8pAe8ky";
  		  $resp = recaptcha_check_answer ($privatekey,
                                                  $_SERVER["REMOTE_ADDR"],
                                                  $challenge,
                                                  $response); 
								
		  if (!$resp->is_valid) {

			echo "The reCAPTCHA wasn't entered correctly.";

                  } else { 	
		  
                        self::validate_data($fname, $lname, $email, $type, $cat);				
	
                  }

	}

    
	//===================================================================
	//     : run check  
	//     : check validity of captcha codes
	//===================================================================
	protected static function validate_data($fname, $lname, $email, $type, $cat){
		       
		global $wpdb; 
			   
                if( self::check_email($email)){
				    
                        $result = $wpdb->get_results( $wpdb->prepare("SELECT re_email FROM ".$wpdb->prefix."rss_email 
                                                                      WHERE re_email = '$email'"));

                        if (!empty($result)){

                                echo "Error: Your email is already in our database!";

                        } else {

                                self::insert_data($fname, $lname, $email, $type, $cat);

                        }
			   
		} else {
			   		
			echo "Your Email is invalid";	
					
		}

	}

	//===================================================================
	//     : run check  
	//     : check validity of captcha codes
	//===================================================================
	protected static function check_email($email){
		
		   /* check email first before anything else */
		   if(preg_match("/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i", $email))  {
			   
			  list($userid, $d) = explode( "@", $email);
				  
			  if (!checkdnsrr($d, 'MX')) { 
			 	return false;
		          } else {
			        return true;
			  }
		   } else {
		   	return false;
		   }

	}
	
	//===================================================================
	//     : run check  
	//     : check validity of captcha codes
	//===================================================================
	protected static function insert_data($fname, $lname, $email, $type, $cat){
		      
			  global $wpdb;
			  
			  $wpdb->get_results( $wpdb->prepare("INSERT INTO ".$wpdb->prefix."rss_email VALUES(NULL, '$fname', '$lname', '$email', '$cat', 'active', '$type', '')"));
			
			  echo "You have successfully subscribed to our feed!";

	}	
		
}

/* run subscription */
subscribe::init_subscribe($challenge, $response, $fname, $lname, $email, $type, $cat)

?>