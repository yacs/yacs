/** 
 * javascript interface for layout_as_mosaic
 * uses /included/browser/js_endpage/masonry.pkgd.min.js
 * by David Desandro
 * @see http://masonry.desandro.com/
 * 
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */


var Mosaic = {
    
    /**
     * this function initialize the tiling with masonry script
     */
    init:function($grid_width){	
	
	$().ready(function(){
	    
	    Mosaic.wrap = $('.mc-wrap');
	    
	    // do the tiling
	    Mosaic.wrap.masonry({
		columnWidth : $grid_width,
		itemSelector : '.mc-block'
	    });
	    
	    // get the masonry object for further control
	    Mosaic.control = Mosaic.wrap.data('masonry');
	    
	    // bind to window resize event to re-tile the items
	    Mosaic.control.bindResize();
	    	    
	})
	
    },
    
    infiniteScroll:function() {
	
	$().ready(function(){
	    
	    // hide the page navigation menu because we use 
	    // the scroll to fetch more content
	    $(".mc-infinite + .menu_bar").hide();
	
	    $(".mc-infinite").infinitescroll({

		navSelector  : ".mc-infinite + .menu_bar",            
			       // selector for the paged navigation (it will be hidden)
		nextSelector : ".mc-infinite + .menu_bar .more",    
			       // selector for the NEXT link (to page 2, 3...)
		itemSelector : ".mc-block, .mc-wrap + .menu_bar",          
			       // selector for all items you'll retrieve
		debug        : true
			       // enable debug messaging ( to console.log )
	    },
	    function( newElements ) {
				   
		    // hide new items while they are loading
		    var $newElems = $( newElements ).css({opacity: 0});		    		    
		    var $blocks = $newElems.filter('.mc-block');
		    var $nav = $newElems.filter('.menu_bar');
		    
		    // put blocks inside layout wrapper, not #main_panel 
		    $blocks.appendTo(".mc-wrap");
		    // update the page menu
		    $(".mc-infinite + .menu_bar").replaceWith($nav);		    
		    
		    // ensure that images load before adding to masonry layout
		    $blocks.imagesLoaded(function(){
			// show elems now they're ready
			$blocks.animate({opacity: 1});
			Mosaic.control.appended($blocks); 
		    });		    		    
	    }
	    );
	
	});
    }
    
 
    
}
