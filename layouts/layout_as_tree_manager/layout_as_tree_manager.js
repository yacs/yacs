/* 
 * @author Alexis Raimbault
 * @reference
 */

var ajaxUrl = url_to_root+"layouts/layout_as_tree_manager/tree_manager_ajax.php";

var TreeManager = {        
    
    init: function() {
	
	$().ready(function() {
	    
	    // draggable elements
	    $(".drag").draggable({
		containment:".ddz",
		cursor: 'move',
		revert: true,
		revertDuration: 700		
	    })
	    
	    // droppable ones
	    $(".drop").droppable({
		accept : ".drag",
		greedy : true,	 // prevent drop event propagation on nested elements
		hoverClass: "hoverdrop",
		drop : TreeManager._elemDroped		
	    });
	    
	});	   	
	
    },                
    
    dropping: function (obj,tar) {		
	
	// find <ul.sub_elems> the sub-elements list of target
	var list = tar.children(".sub_elems");		
	
	// append the dragged object to it
	obj.appendTo(list);
	obj.animate({left:'0',top:'0'},100);
    },
    
    _elemDroped: function (e,ui) {
	
	// get reference to target and moved objects
	TreeManager.tarRef = $(this).data("ref");
	TreeManager.movRef = ui.draggable.data("ref");				
	
	TreeManager.dragItem = ui.draggable;
	TreeManager.dropItem = $(this);
		
	TreeManager.postMove(TreeManager.movRef,TreeManager.tarRef);			
    },       
    
    postMove: function (obj,tar) {
	
	// freeze object move
	TreeManager.dragItem.draggable( 'option', 'revert', false );
	
	$.post(
	    ajaxUrl,
	    {action : 'move', obj : obj, tar : tar}
	).done(function( data ) {
				
		if(data.success) {		   
		   TreeManager.dropping(TreeManager.dragItem, TreeManager.dropItem); 
		}
		else
		   TreeManager.dragItem.animate({left:'0',top:'0'},100);
		    
	});
	
    }            
}


