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
	    });
	    
	    // droppable ones
	    $(".drop").droppable({
		accept : ".drag",
		hoverClass: "hoverdrop",
		drop : TreeManager.elemDroped
	    });
	});	   	
	
    },
    
    elemDroped: function (e,ui) {
	
	// get reference to target and moved objects
	var tarRef = $(this).data("ref");
	var movRef = ui.draggable.data("ref");
	
	TreeManager.dragItem = ui.draggable;
	TreeManager.dropItem = $(this);
		
	TreeManager.postMove(movRef,tarRef);
	//TreeManager.dropping(ui.draggable, $(this));
    },
    
    dropping: function (obj,tar) {
	
	// find <ul.sub_elems> the sub-elements list of target
	var list = tar.find(".sub_elems").first();		
	
	// append the dragged object to it
	obj.appendTo(list);
	obj.animate({left:'0',top:'0'},100);
    },
    
    postMove: function (obj,tar) {
	
	// freeze object move
	TreeManager.dragItem.draggable( 'option', 'revert', false );
	
	$.post(
	    ajaxUrl,
	    {action : 'move', obj : obj, tar : tar}
	).done(function( data ) {
		console.log(data.success);
		
		if(data.success) {		   
		   TreeManager.dropping(TreeManager.dragItem, TreeManager.dropItem); 
		}
		else
		   TreeManager.dragItem.animate({left:'0',top:'0'},100);
		    
	});
	
    }
    
    
}


