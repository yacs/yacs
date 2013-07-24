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
		revertDuration: 700,
		start: function(e, ui) {
		    $(this).addClass('tm-nozoom'); // to prevent zooming after dragging
		}
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
	    
	    //zoom
	    $(".zoom").click( function() {TreeManager.zoom($(this));});
	    
	});	
	
    },  
    
    confirmDelete: function (anchor) {
	
	// count nb of sub elements
	var nbsub = anchor.find("li").length;
	
	if(!nbsub)
	    TreeManager.postDelete(anchor)
	else
	    Yacs.displayModalBox({
		title : 'Suppress',
		body : 'This will suppress '+nbsub+' sub-element(s).',
		button_TRUE : 'Confirm',
		button_FALSE : 'Cancel'		
	    }, function(result) {
		if(result == true)
		    TreeManager.postDelete(anchor)
	    });
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
		    var newli = $('<li class="drag drop"></li>');
		    
		    // set the title
		    var title = $('<span class="folder"></span>').text(data.title);
		    var zoom = $('<a class="zoom"></a>').click(function() {TreeManager.zoom($(this));});
		    zoom.append(title);
		    newli.append(zoom);
		    
		    // clone and append a create cmd
		    var cmd_create = $('.ddz').find('.create').first().clone();
		    cmd_create.click( function() {TreeManager.cmd($(this));});
		    newli.append(cmd_create);
		    
		    // clone add append a delete cmd
		    var cmd_delete = $('.ddz').find('.delete').first().clone();
		    cmd_delete.click( function() {TreeManager.cmd($(this));});
		    newli.append(cmd_delete);
		    
		    // sub elements list
		    var sub_list = $('<ul class="sub_elems"></ul>');
		    newli.append(sub_list);
		    
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
    },      

    zoom:function (title) {
	
	var anchor = title.parents(".drop").first();
	
	if(anchor.hasClass('tm-nozoom')) {
	    anchor.removeClass('tm-nozoom');
	    return;
	}
	
	Yacs.startWorking();
	$.get(
	    ajaxUrl,
	    {action : 'zoom', anchor : anchor.data('ref')}
	).done(function( data ) {
		
		if(data.success) {		    	    		  		    
		    // building breadcrumbs complement ...
			var more_crumbs = '';
			// get hierarchy
			var path_anchors = $(anchor.parents(".drop").get().reverse());
			// build it			
			$.each(path_anchors,function() {
			    var link = $('<a class="tm-crumbs"></a>');
			    link.attr('data-ref',$(this).data('ref'))
			    // looking for label			    
			    if(!$(this).hasClass("ddz")) {
				var label = $(this).find(".folder").first();
				link.text(label.text());
			    } else
				// root title
				link.text($('#main_panel h1 span').text());
			    
			    // tricks : get link's outerHTML by appending it to a temporary <div> 
			    // add also a <span> wrapped on separator to be able to remove it easily while zooming out
			    more_crumbs += $('<div>').append(link).html() + '<span>'+data.crumbs_separator+'</span>';
			});
			// append to breadcrumbs
			var crumbs = $("#crumbs"); 
			crumbs.html(crumbs.html()+more_crumbs);
			// bind click
			$(".tm-crumbs").click(function() {TreeManager.zoomOut($(this))});
		    
		    // update title
		    $('#main_panel h1 span').text(data.title);	
		    
		    // disable tools and share from original page
		    var to_disable = $('#page_tools a, #share a');
		    to_disable.click(function(e) {
			e.preventDefault(); // disabled
		    });
		    to_disable.css('color','grey');
		    $('#page_tools, #share').css('opacity','.5');
		    
		    // update content
		    anchor.parents(".ddz").replaceWith(data.content);
		    TreeManager.init();
		}
		Yacs.stopWorking()
	});
    
    },
    
    zoomOut:function(title) {
		
	Yacs.startWorking();
	$.get(
	    ajaxUrl,
	    {action : 'zoom', anchor : title.data('ref')}
	).done(function( data ) {
		
		if(data.success) {
		    // updating breadcrumbs
		    title.nextAll().remove();
		    title.remove();
		    
		    // re-enable tools
		    if(Yacs.current_item == title.data('ref')) {
			$('#page_tools a, #share a').unbind('click').removeAttr("style");
			$('#page_tools, #share').css('opacity','1');
		    }
		    
		    // update page title
		    $('#main_panel h1 span').text(data.title);	
		    
		    // update content
		    $(".ddz").replaceWith(data.content);
		    TreeManager.init();		    
		}
		Yacs.stopWorking();
	});
    }
}


