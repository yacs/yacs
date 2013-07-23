/* 
 * @author Alexis Raimbault
 * @reference
 */

var ajaxUrl = url_to_root+"layouts/layout_as_tree_manager/tree_manager_ajax.php";


var TreeManager = {        
    
    init: function() {
	
	TreeManager.dragOptions = {
		containment:".ddz",
		cursor: 'move',
		revert: true,
		revertDuration: 700		
	    };
	    
	TreeManager.dropOptions = {
		accept : ".drag",
		greedy : true,	 // prevent drop event propagation on nested elements
		hoverClass: "hoverdrop",
		drop : TreeManager._elemDroped		
	    }	
	
	$().ready(function() {
	    
	    // draggable elements
	    $(".drag").draggable(TreeManager.dragOptions);
	    
	    // droppable ones
	    $(".drop").droppable(TreeManager.dropOptions);
	    
	    // cmd buttons
	    $(".cmd").click( function() {TreeManager.cmd($(this));});
	    
	});	   	
	
    },  
    
    confirmDelete: function (anchor) {
	
	// count nb of sub elements
	var nbsub = anchor.find("li").length;
	
	if(!nbsub)
	    TreeManager.postDelete(anchor)
    },
    
    cmd: function (cmd) {			
	
	if(cmd.hasClass("create")) {	   
	    var anchor = cmd.parents(".drop").first();	    
	    TreeManager.inputCreate(anchor);
	    return;
	}
	
	if(cmd.hasClass("delete")) {
	    var anchor = cmd.parents(".drop").first();
	    TreeManager.confirmDelete(anchor);
	    return;
	}
	
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
    
    inputCreate: function(anchor) {
	
	// input html
	var input = $('<input type="text" name="create"/>');
	
	// add to subelems list
	input.prependTo(anchor.children('.sub_elems'));
	input.wrap('<li class="drop"></li>');
	
	// remove on focus out
	input.focusout(function() {
	    input.parent().remove();
	});
	
	input.change(function() {
	    TreeManager.postCreate(anchor,input);
	});
		
	input.focus();
	
    },
   
    postCreate:function (anchor,input) {
	
	Yacs.startWorking();
	$.post(
	    ajaxUrl,
	    {action : 'create', anchor : anchor.data('ref'), title : input.val()}
	).done(function( data ) {
		if(data.success) {
		    // create the new <li>
		    var newli = $('<li class="drag drop"><ul class="sub_elems"></ul></li>');
		    
		    // clone and append a create cmd
		    var create = $('.ddz').find('.create').first().clone();
		    create.click( function() {TreeManager.cmd($(this));});
		    newli.prepend(create);
		    
		    // set the title
		    var title = $('<span class="folder"></span>').text(data.title);		    
		    newli.prepend(title);
		    
		    // set binded reference
		    newli.data('ref',data.ref);
		    
		    // make <li> draggable and droppable
		    newli.draggable(TreeManager.dragOptions);
		    newli.droppable(TreeManager.dropOptions);
		    
		    // display <li>
		    input.parent().replaceWith(newli);
		} else
		    input.parent().remove();
		
		Yacs.stopWorking();  		
	});
	
    },
    
    postDelete: function (anchor) {
	
	Yacs.startWorking();
	$.post(
	    ajaxUrl,
	    {action : 'delete', anchor : anchor.data('ref')}
	).done(function( data ) {
		if(data.success)
		    anchor.remove();
		
		Yacs.stopWorking();    
	});
    },
   
    postMove: function (obj,tar) {
	
	// freeze object move
	TreeManager.dragItem.draggable( 'option', 'revert', false );
	
	Yacs.startWorking();
	$.post(
	    ajaxUrl,
	    {action : 'move', obj : obj, tar : tar}
	).done(function( data ) {
				
		if(data.success) {		   
		   TreeManager.dropping(TreeManager.dragItem, TreeManager.dropItem); 
		}
		else
		   TreeManager.dragItem.animate({left:'0',top:'0'},100);
		
		Yacs.stopWorking();    
	});
	
    }    
}


