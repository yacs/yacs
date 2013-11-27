/*
 * script for accordion layout
 *
 * @author: alexis Raimbault
 * @author : Bernard Paques
 */

var accordion = {

	/**
	 * toggle a box in an accordion
	 *
	 * @param the box
	 * @param string URL of the extending icon
	 * @param string URL of the collapsing icon
	 * @param string accordion id
	 */
	toggle: function(handle, down_href, up_href, accordion) {

		// the toggled panel
		var toggled = $(handle).next('.accordion_content');
		var processed = false;

                // refold each opened gusset in selected accordion
                $('.'+accordion).each(function(i,gusset) {
                    // gusset == this

		    // remove open state class
		    $(gusset).children('.accordion_link').removeClass('accordion-open');

		    // get the movable panel
		    var panel = $(gusset).children(".accordion_content");
                    // detect unfolded panel
                    if(panel.css("display") != 'none') {
			panel.removeClass('accordion-open');
			// slide up panel
                        panel.slideUp({duration: 'slow', scaleContent:false});
                        // change icon to unfold visual
                        $(gusset).find('.handle').attr('src', down_href);
                        // clicked box has been closed
						if(toggled == panel) {
                            processed = true;
						}
                    }
                });

                // only extend closed elements that have not been processed (closed) during this click
                if((toggled.css("display") == 'none') && !processed) {
                        // slide down panel
			toggled.slideDown({duration: 'slow', scaleContent:false});

			// add open state class
			toggled.addClass('accordion-open');
			$(handle).addClass('accordion-open');
                        // change the image to fold visual
                        $(handle).find(".handle").attr('src', up_href);
                }

	}
}


