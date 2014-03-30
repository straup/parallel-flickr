function photo_shortcuts_init(prev, next){

    $(document).keydown(function(e){

	if (e.keyCode == 13){
	    // 
	}
	
	// left arrow
	
	else if ((e.keyCode == 37) && (prev)){
	    location.href = prev;
	}
	
	// right arrow
	
	else if ((e.keyCode == 39) && (next)){
	    location.href = next;
	}
	
	// 'g' - disabled
	
	/*
	  else if (e.keyCode == 71){
	  
	  var el = $("#edit_geo");
	  
	  if (el){			
	  el.click();
	  }
	  }
	*/
	
	else {}
    });

}
