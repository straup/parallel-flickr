function photo_redraw_init(){

    /*
    $('#' + lid).click(function(){

	var i = $('#' + iid);
	var r = i.attr('data-allow-redraw');

	if (r == 'disabled'){
	    return;
	}
	
	var h_lg = i.attr('data-height-lg')
	var w_lg = i.attr('data-width-lg')
	
	if (r == 'yes'){

	    var cl = i.attr('class');
	    cl = cl.replace("img-responsive", "");
	    cl += " img-super-big";
	    i.attr('class', cl);
	    
	    i.attr('data-allow-redraw', 'no');
	    i.removeAttr('style');
	    i.css('height', h_lg);
	    i.css('width', w_lg);
	    
	    $('#' + sid).hide();
	    $('#' + bid).show();
	}

	else {
	    
	    var cl = i.attr('class');
	    cl = cl.replace("img-super-big", "");
	    cl += " img-responsive";
	    i.attr('class', cl);
	    
	    // really... ? (20131118/straup)
	    i.removeAttr('style');
	    
	    i.attr('data-allow-redraw', 'yes');
	    var force = true;
	    photo_redraw(force);
	}

	return false;
    });
    */

    $(window).resize(function(){
	photo_redraw();
    });

    photo_redraw();
}

function photo_redraw(force){

    var ph = $(".img");
    ph = $(ph[0]);

    var iid = ph.attr("id");
    console.log(iid);

    var i = $('#' + iid);
    var r = i.attr('data-allow-redraw');
    
    if (r == 'no'){
	return;
    }

    var h_lg = i.attr('data-height-lg')
    var w_lg = i.attr('data-width-lg')
    
    var p = i.position();
    var i_top = p['top'];

    var w = $(window);
    var w_top = w.scrollTop();

    if ((w_top < i_top) || (force)){

	var i_h = i.height();
	var w_h = w.height();

	// console.log("I: " + i_h);
	// console.log("W: " + w_h);

	var viewport = (w_h - i_top) * .8;

	// console.log('viewport is ' + viewport);
	// console.log('height is ' + h_lg);

	if (viewport > h_lg){
	    i.removeAttr('style');
	    i.css('max-height', viewport);
	}

	else if ((viewport >= 200) && (viewport <= h_lg)){
	    i.removeAttr('style');
	    i.css('max-height', viewport);
	}
	
	else {
	    // console.log("NO");
	}
    }
    
    if (! i.is(':visible')){
	i.removeAttr('style');
	i.show();
    }
    
    // $('#' + bid).hide();
    // $('#' + sid).show();
};
