/** 
 * javascript interface for layout_as_tree_manager
 * uses jQuery and jQuery-ui for drag&drop operations and many more
 * 
 * @author Alexis Raimbault
 * @reference
 */

// url to use for sending ajax requests
var tm_ajaxUrl = url_to_root+"layouts/layout_as_tree_manager/tree_manager_ajax.php";


var TreeManager = {        
    
    /**
     * called it for to start interactions on page
     */
    init: function() {
	
	// options for dragged elements
	TreeManager.dragOptions = {
		containment:".tm-ddz",
		cursor: 'move',
		revert: true,  // by defaut a dragged object will return to it's possition
		revertDuration: 700,
		start: function(e, ui) {
		    // to prevent zooming after dragging (click event)
		    $(this).addClass('tm-nozoom'); 
		}
	    };
	    
	TreeManager.dropOptions = {
		accept : ".tm-drag",
		greedy : true,	 // prevent drop event propagation on nested drop elements
		hoverClass: "tm-hoverdrop",
		drop : TreeManager.elemDroped	// function to call when dropping is done	
	    }	
	
	$().ready(function() {
	    
	    // draggable elements
	    $(".tm-drag").draggable(TreeManager.dragOptions);
	    
	    // droppable ones
	    $(".tm-drop").droppable(TreeManager.dropOptions);
	    
	    // all cmd buttons
	    $(".tm-cmd").click( function() {TreeManager.cmd($(this));});
	    
	    //zoom & rename folders	    
	    $(".tm-zoom").click_n_dblclick(TreeManager.zoom,TreeManager.inputRename);
	    
	    // rename content
	    $(".tm-page").dblclick( function() {TreeManager.inputRename($(this));});
	    
	    // hide menu bar ( we could override clic action on item creation link instead )
	    $('.menu_bar').hide();
	    
	});	
	
    },  
    
    /**
     * called after pressing on delete button of a anchor
     * routed from cmd() function
     */
    confirmDelete: function (anchor) {
	
	// count nb of sub elements
	var nbsub = anchor.find("li").length;
	
	if(!nbsub) {
	    // is anchor a folder ?
	    if(!anchor.hasClass("tm-drop")) {
		// check if parent is a category
		var parent = anchor.parents(".tm-drop").first();
		if( TreeManager.is_cat(parent.data('ref')) )
		    // free the category assignment
		    TreeManager.postBind(anchor,parent,'free'); 
		else
		    TreeManager.postDelete(anchor);
	    } else
		// no sub elements, delete it right now
		TreeManager.postDelete(anchor);
	} else
	    // ask for a confirmation
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
    
    /**
     * called after pressing any command buttons
     * will route to dealing function depending on object class
     */
    cmd: function (cmd) {			
	
	if(cmd.hasClass("tm-create")) {	   
	    var anchor = cmd.parents(".tm-drop").first(); // consider only folders	    
	    TreeManager.inputCreate(anchor);
	    return;
	}
	
	if(cmd.hasClass("tm-delete")) {
	    var anchor = cmd.parents(".tm-drag").first(); // consider any draggable element 
	    TreeManager.confirmDelete(anchor);
	    return;
	}	
	
    },
    
    /**
     * called to finish the dropping animation, when
     * reply to ajax request was a success
     * 
     * @param obj jquery obj dragged
     * @param tar jquery obj drop target
     */
    dropping: function (obj,tar) {		
	
	// find <ul.tm-sub_elems> the sub-elements list of target
	var list = tar.children(".tm-sub_elems");		
	
	// append the dragged object to it
	obj.appendTo(list);
	obj.animate({left:'0',top:'0'},100);
    },
    
    /**
     * called when a dragged element is dropped on a droppable one
     * 
     * @param e the event
     * @param ui a jquery-ui object
     */
    elemDroped: function (e,ui) {	
			
	if(!TreeManager.is_cat(ui.draggable.data("ref")) && TreeManager.is_cat($(this).data("ref")))
	    // post a assignment
	    TreeManager.postBind(ui.draggable, $(this), 'assign');
	else
	    // post a move
	    TreeManager.postMove(ui.draggable, $(this));
		
    },               
    
    /**
     * called after pressing create button binded to a entry
     * build a input to ask for new element title
     * 
     * @param anchor jquery obj the anchor which will receive the new entry
     */
    inputCreate: function(anchor) {
	
	// input html
	var input = $('<input type="text" name="create"/>');
	
	// add to subelems list
	input.prependTo(anchor.children('.tm-sub_elems'));
	input.wrap('<li class="tm-drop"></li>');
	
	// remove input on focus out
	input.focusout(function() {
	    input.parent().remove();
	});
	
	// post on input change
	input.change(function() {
	    TreeManager.postCreate(anchor,input);	   
	});
		
	// input name right now !	
	input.focus();
	
    },

    /**
     * called after dblclicking on a entry name
     * build a input to ask for renaming
     * 
     * @param title jquery obj the double clicked element
     * could be a <a><span> or a single <span>
     */
    inputRename: function(title) {
	
	// display an input field instead of name
	var input = $('<input type="text" name="rename"/>'); 
	input.val(title.text());    // use former name
	input.insertBefore(title);
	title.detach();	// remove the original title but keep it in DOM
	
	// remove input on focus out
	input.focusout(function() {
	    title.insertBefore(input);
	    input.remove();
	});
	
	// post on input change
	input.change(function() {	    
	    TreeManager.postRename(title,input);
	});
	
	// input new name right now !
	input.focus();	
    },
    
    /**
     * tells if reference is from a category or not
     */
    is_cat:function(ref) {
	
	ref = ref.split(":");
	
	if(ref[0]=='category')
	    return true
	else
	    return false;	
    },

    /**
     * this function perform the ajax request to assign or free a element to a 
     * category. If succeed the element is either clone and added under the target 
     * category or removed from his parent category
     * @see layouts/layout_as_tree_manager/tree_manager_ajax.php
     * 
     * @param anchor jquery object the dragged element or deleted element
     * @param cat jquery object the target cat or the parent cat
     * @param way string what to do : 'assign' or 'free'
     */
    postBind:function(anchor,cat,way) {
	
	Yacs.startWorking();
	$.post(
	    tm_ajaxUrl,
	    {action : 'bind', anchor : anchor.data('ref'), cat : cat.data('ref'), way: way}
	).done(function( data ) { 
	    if( data.success ) {
		if(way == 'free')
		    // free element
		    anchor.remove();
		else if(way == 'assign') {
		    var newanch = anchor.clone();
		    TreeManager.dropping(newanch,cat);
		}
	    }
	    Yacs.stopWorking();
	});
    },
   
    /**
     * this function perform the ajax request to create a new element
     * if succeed, create DOM elements to represent the newly created entry
     * @see layouts/layout_as_tree_manager/tree_manager_ajax.php
     * 
     * @param anchor jquery object the anchor under which element is created ( <li> )
     * @param input jquery object the input containning the new element's name  
     */
    postCreate:function (anchor,input) {
	
	Yacs.startWorking();
	$.post(
	    tm_ajaxUrl,
	    {action : 'create', anchor : anchor.data('ref'), title : input.val()}
	).done(function( data ) {				

		if(data.success) {
		    // create the new <li>
		    var newli = $('<li class="tm-drag tm-drop"></li>');
		    
		    // set the title
		    var title = $('<span class="tm-folder"></span>').text(data.title);
		    // single and double click events
		    var zoom = $('<a class="tm-zoom"></a>').click_n_dblclick(TreeManager.zoom,TreeManager.inputRename);
		    // nest elements
		    zoom.append(title);
		    newli.append(zoom);
		    
		    // clone and append a create cmd to entry
		    var cmd_create = $('.tm-ddz').find('.tm-create').first().clone();
		    cmd_create.click( function() {TreeManager.cmd($(this));});
		    newli.append(cmd_create);
		    
		    // clone add append a delete cmd
		    var cmd_delete = $('.tm-ddz').find('.tm-delete').first().clone();
		    cmd_delete.click( function() {TreeManager.cmd($(this));});
		    newli.append(cmd_delete);
		    
		    // append a empty sub-elements list
		    var sub_list = $('<ul class="tm-sub_elems"></ul>');
		    newli.append(sub_list);
		    
		    // set binded reference (won't appear as a tag attribute)
		    newli.data('ref',data.ref);
		    
		    // make <li> draggable and droppable
		    newli.draggable(TreeManager.dragOptions);
		    newli.droppable(TreeManager.dropOptions);
		    
		    // display <li>
		    input.parent().replaceWith(newli); 
		} else
		    // remove input and leave everything as before
		    input.parent().remove();
				
		Yacs.stopWorking();		
	});	
    },
    
    /**
     * this function perform the ajax request to delete a entry
     * if succeed it delete the binded tags
     * @see layouts/layout_as_tree_manager/tree_manager_ajax.php
     * 
     * @param anchor jquery object the anchor to delete ( <li> )
     */
    postDelete: function (anchor) {
	
	Yacs.startWorking();
	$.post(
	    tm_ajaxUrl,
	    {action : 'delete', anchor : anchor.data('ref')}
	).done(function( data ) {
		if(data.success)
		    anchor.remove();
		
		Yacs.stopWorking();    
	});
    },
   
    /**
     * this function perform the ajax request to move a anchor under another
     * if succeed or not it animate the end of dropping or reverse movment back to former place
     * @see layouts/layout_as_tree_manager/tree_manager_ajax.php
     * 
     * @param obj jquery object the dragged one ( <li> )
     * @param tar jquery object the drop target ( <li> too, or root <ul> )
     */
    postMove: function (obj,tar) {
	
	// freeze object move during request
	obj.draggable( 'option', 'revert', false );
	
	Yacs.startWorking();
	$.post(
	    tm_ajaxUrl,
	    {action : 'move', anchor : obj.data('ref'), tar : tar.data('ref')}
	).done(function( data ) {
				
		if(data.success) {	
		   // finish the dropping
		   TreeManager.dropping(obj, tar); 
		}
		else
		   // back to former place 
		   obj.animate({left:'0',top:'0'},100);
		
		Yacs.stopWorking();    
	});	
    },      

    /**
     * this function perform the ajax request to rename a given entry
     * if succeed the new name is setted
     * @see layouts/layout_as_tree_manager/tree_manager_ajax.php
     * 
     * @param title jquery object the title to change ( <a><span> or <span> )
     * @param input jquery object the input containning the new name
     */
    postRename: function(title,input) {
    
	// get the anchor
	var anchor = input.parents('li').first();
    
	Yacs.startWorking();
	$.post(
	    tm_ajaxUrl,
	    {action : 'rename', anchor : anchor.data('ref'), title : input.val()}
	).done(function( data ) {

		if(data.success) {
		    // look for child span
		    if(title.children('span').lenght)
			title.children('span').text(input.val());
		    else
			// we are already a span :)
			title.text(input.val());


		} 

		// remove input and display title with new or former name
		input.replaceWith(title);

		Yacs.stopWorking();
	});
	
    },
    
    /**
     * this function perform the ajax request to get 
     * a new tree view from a given anchor
     * @see layouts/layout_as_tree_manager/tree_manager_ajax.php
     * 
     * the rendering is all reseted and replace by the new received content
     * Yacs tools and share interface are frozen because they do not interact with the represented anchor
     * 
     * The breadcrumbs is updated to enable zoomOut
     * 
     * @param title jquery object that was clicked to ask for a zoom
     */
    zoom:function (title) {	
	
	// get the anchor to zoom
	var anchor = title.parents(".tm-drop").first();
	
	// maybe event was fired after dropping operation ?
	// this is a fuse
	if(anchor.hasClass('tm-nozoom')) {
	    anchor.removeClass('tm-nozoom');
	    return;
	}
	
	Yacs.startWorking();
	$.get(
	    tm_ajaxUrl,
	    {action : 'zoom', anchor : anchor.data('ref')}
	).done(function( data ) {
		
		if(data.success) {		    	    		  		    
		    // building breadcrumbs complement ...
			var more_crumbs = ''; // string
			// get hierarchy
			var path_anchors = $(anchor.parents(".tm-drop").get().reverse());
			// build it			
			$.each(path_anchors,function() {
			    var link = $('<a class="tm-crumbs"></a>');
			    link.attr('data-ref',$(this).data('ref'))
			    // looking for label			    
			    if(!$(this).hasClass("tm-ddz")) {
				var label = $(this).find(".tm-folder").first();
				link.text(label.text());
			    } else
				// take root title from page
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
		    anchor.parents(".tm-ddz").replaceWith(data.content);
		    TreeManager.init();
		}
		Yacs.stopWorking()
	});
    
    },
    
    /**
     * this function perform a zoomOut operation afer clicking on a built-in
     * breadcrumbs link.
     * If succeed the layout is completly redraw
     * If we are back to root (the anchor where the rendering started) Yacs tools are
     * re-enabled
     * @see layouts/layout_as_tree_manager/tree_manager_ajax.php
     * 
     * @param title jquery object the clicked link in breadcrumbs
     */
    zoomOut:function(title) {
		
	Yacs.startWorking();
	$.get(
	    tm_ajaxUrl,
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
		    $(".tm-ddz").replaceWith(data.content);
		    TreeManager.init();		    
		}
		Yacs.stopWorking();
	});
    }
}