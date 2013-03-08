// JavaScript Document

//===========================================
// this function is for trim
//===========================================
if(typeof String.prototype.trim !== 'function') {
		  String.prototype.trim = function() {
			return this.replace(/^\s+|\s+$/g, ''); 
		  }
}


//===========================================
// this function is for validating inputs
//===========================================
function validate(string){
	
	if(string.trim() === ""){
	      return false;
	} else {
	
	      var x =/[^A-Za-z0-9\-\d ]/;
	      
		  if (!x.test(string)) {
		   
		   		return true;
		       
		  } else {
			  
			   return false;
		  
		  }
	
	}

}


//===========================================
// this function is for validating inputs
//==========================================
function validateEmail(elementsInputs){
var emailFilter=/^.+@.+\..{2,3}$/; 
		if (!emailFilter.test(elementsInputs)) { 
				return true; 
		} 
}

