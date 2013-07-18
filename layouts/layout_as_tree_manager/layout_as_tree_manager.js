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
		revert: true		
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
		
	TreeManager.postMove(movRef,tarRef);	
	TreeManager.dropping(ui.draggable, $(this));
    },
    
    dropping: function (obj,tar) {
	
	// find <ul.sub_elems> the container of target
	var list = tar.find(".sub_elems").first();		
	
	// append the dragged object to it
	obj.appendTo(list);
    },
    
    postMove: function (obj,tar) {
	
	$.post(
	    ajaxUrl,
	    {action : 'move', obj : obj, tar : tar}
	);
    }
    
    
}


