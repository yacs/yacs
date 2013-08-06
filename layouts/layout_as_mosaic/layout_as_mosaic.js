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
    init:function(){
	//console.log('hey joe');
	
	$().ready(function(){
	    
	    Mosaic.wrap = $('.mc-wrap');
	    
	    // do the tiling
	    Mosaic.wrap.masonry({
		columnWidth : 100,
		itemSelector : '.mc-block'
	    });
	    
	    // get the masonry object for further control
	    Mosaic.control = Mosaic.wrap.data('masonry');
	    
	    // bind to window resize event to re-tile the items
	    Mosaic.control.bindResize();
	    	    
	})
	
    }
    
}
