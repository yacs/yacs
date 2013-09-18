/** 
 * javascript interface for layout_as_tree_manager
 * uses jQuery and jQuery-ui for drag&drop operations and many more
 * 
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// url to use for sending ajax requests
var tm_ajaxUrl = url_to_root+"layouts/layout_as_tree_manager/tree_manager_ajax.php";


var TreeManager = {        
    
    /**
     * called it for to start interactions on page
     * 
     * @param userlevel string, 'powered' to start interaction, empty for zoom only
     */
    init: function(userlevel) {
			
	
	// options for dragged elements
	TreeManager.dragOptions = {
		containment:".tm-ddz",
		cursor: 'move',		
		distance: 20,
		revert: true,  // by defaut a dragged object will return to it's possition
		revertDuration: 700,
		zIndex : 100,
		start: function(e, ui) {
		    // to prevent zooming after dragging (click event)
		    $(this).addClass('tm-nozoom'); 
		}
	    };
	    
	TreeManager.dropOptions = {
		accept : ".tm-drag",
		greedy : true,	 // prevent drop event propagation on nested drop elements
		hoverClass: "tm-hoverdrop",
		tolerance: 'pointer',
		drop : TreeManager.elemDropped	// function to call when dropping is done	
	    }	
	
	$().ready(function() {	    
	    
	    // delay initialization if layout is hidden (tabs case)
	    if($('.tm-ddz').is(':hidden') ) {
		
		if(!TreeManager.yacs) {
		    // suscribe to yacs event (tabs change)
		    $(document).on('yacs',function(){
			if($('.tm-ddz').is(':visible')) {			    
			    TreeManager.init(userlevel);
			    $(document).off('yacs')     // unsuscribe after init is done
			}
		    });
		    TreeManager.yacs = true; // flag suscribing done
		}
		return;  // do nothing if interface is hidden
	    }	    	    
	    
	    // masonry layout
	    $('.tm-root > .tm-drop').addClass('tm-masonry');
	    $('.tm-root').masonry({
		isInitLayout: false,
		columnWidth : 140,
		itemSelector : '.tm-masonry'
	    });
	    TreeManager.masonry = $('.tm-root').data('masonry');

	    // if we have a pinned folder, narrow the root
	    if($('.tm-pinz').is(':visible')) {
		$('.tm-root').css('width','80%');
		// remove pinned folder from standard list if present
		// this may happen with zoom out case
		var pinref = $('.tm-pinz > .tm-drop').data('ref');
		var to_remove = $('.tm-root .tm-drop[data-ref="'+pinref+'"]');	  
		if(to_remove.length)
		    TreeManager.masonry.remove(to_remove.get());
	    }
	    // use "stamp" to leave space to selected elements on root (fake column)
	    TreeManager.stamp = $('<div class="tm-stamp"></div>').css({
		// display:'none',
		// background : 'red',
		position: 'absolute',
		zIndex: -1,	
		top : 0,
		bottom: 0,
		width: '140px' // same as masonry column
	    });
	    TreeManager.stamp.appendTo($('.tm-ddz'));
	    TreeManager.hasRootColumn = false;	
	    // initialize creation of the column
	    TreeManager.checkRootColumn();		   	    	   	    
	    
	    // layout only if necessary
	    if($('.tm-masonry').length) {
		// close all folders
		//$('.tm-masonry > .tm-sub_elems').hide();
		//$('.tm-masonry').append('<span class="tm-foldmark">...</span>');
		$('.tm-masonry').each(function(){TreeManager.checkHeight($(this),'fold');});
		
		TreeManager.masonry.layout();
	    }
	    
	    if(userlevel == 'powered') {
		// draggable elements
		$(".tm-drag").draggable(TreeManager.dragOptions);

		// droppable ones
		$(".tm-drop").droppable(TreeManager.dropOptions);

		// all cmd buttons
		$(".tm-cmd").click( function(e) {e.stopPropagation();TreeManager.cmd($(this));});		
	    
	    } else {				
		
		// hide commands tags
		$(".tm-cmd, .tm-hovermenu").hide();
	    }	    

	    // fold/unfold list
	    TreeManager.animFold($("li.tm-drop"));

	    // mouse over animation
	    TreeManager.animOver($(".tm-drag"));
		
	    // propagate simple click , zoom on double
	    $(".tm-zoom").click_n_dblclick(TreeManager.propagateClick,TreeManager.zoom);		    	    	    	    
	    	    
	    // hide menu bar ( we could override clic action on item creation link instead )
	    $('.menu_bar').hide();
	    
	    // overlaid viewing of pages and users
	    $('.tm-page, .tm-user').click(function(e){						
		e.preventDefault();
		e.stopPropagation();
		TreeManager.zoom($(this));		
	    });
	    
	    // make pinnable zone ready
	    if(!$('.tm-pinz').length) {
		$('<ul class="tm-pinz"></ul>').insertAfter($('.tm-root'));
	    }	    	    
	    	    	   	    
	});	
	
    },  
    
    /**
     * click on a block toggle fold sub elements if any
     */
    animFold:function(elems) {
	elems.click(function(e){
		e.stopPropagation();
		var li = $(this);
		var subul = li.children('.tm-sub_elems');
		if(!subul.children().length)
		    return;
		subul.toggle(100,function() {		    
		    if(subul.is(":visible")) 	  
			li.children('.tm-foldmark').remove();
		    else			    			
			li.append('<span class="tm-foldmark">...</span>');
		    
		    TreeManager.checkHeight(li,'lay')		    
		});		
	    });
    },    
    
    
    /**
     * mouse overing a block hilight it, but do not hiligth parent
     * if focus is on a child
     */
    animOver:function(elems) {
	elems.mouseenter(function(){
		if(!$(this).find('.tm-hover').length) {
		    $(this).addClass('tm-hover');
		    $(this).parents('.tm-drag').removeClass('tm-hover');
		}				
	    }).mouseleave(function(){
		if($(this).hasClass('tm-hover')) {
		    $(this).removeClass('tm-hover');
		    $(this).parents('.tm-drag').first().addClass('tm-hover');
		}		
	    });	
    },
    
    checkHeight: function(item,opt) {
		
	// find parent folder on root
	var top = item.closest('.tm-masonry');
	
	if(!top.length)
	    return;
	
	// reset min-height
	top.css('min-height','80px')
	// calc its normalize height,
	var top_h = Math.ceil(top.height()/80)*96 - 16;
	if(top_h > 80 && opt == 'fold') {
	    // hide sub list
	    top.children('.tm-sub_elems').hide();
	    top.append('<span class="tm-foldmark">...</span>');
	} else
	    // set min height
	    top.css('min-height',top_h+'px')		
	
	if(opt == 'lay')
	    TreeManager.masonry.layout();
    },
    
    /**
     * selection (page, user) at root folder are listed without masonry
     * as standard <li>. Elements controled by masonry are pushed
     * away using "stamp" feature, this simulate a column for elements
     * on root.
     */
    checkRootColumn:function() {	
	
	// fuse not to call layout() for nothing
	var change = false;	
	
	// is their one child on root that is not a folder ?
	var childOnRoot = $('.tm-root > .tm-drag > .tm-page, .tm-root > .tm-drag > .tm-user').length;
	
	if( childOnRoot && !TreeManager.hasRootColumn) {
	    TreeManager.masonry.stamp(TreeManager.stamp.get());
	    TreeManager.hasRootColumn = true;
	    change=true;	    
	} else if(!childOnRoot && TreeManager.hasRootColumn) {
	    TreeManager.masonry.unstamp(TreeManager.stamp.get());
	    TreeManager.hasRootColumn = false;
	    change=true;
	}
	
	// visual effect
	if(change)
	    TreeManager.masonry.layout();
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

	if(cmd.hasClass("tm-rename")) {
	    var anchor = cmd.parent().prevAll(".tm-zoom").first(); // consider title associated with same entry	    
	    TreeManager.inputRename(anchor);
	    return;
	}
	
	if(cmd.hasClass("tm-pin")) {	   
	    TreeManager.pinup(cmd);	  	    	
	    return;
	}
	
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
		if( TreeManager.is_cat(parent.data('ref')) ) {
		    // free the category assignment
		    TreeManager.postBind(anchor,parent,'free'); 		    
		} else
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
     * called to finish the dropping animation, when
     * reply to ajax request was a success
     * 
     * @param obj jquery obj dragged
     * @param tar jquery obj drop target
     */
    dropping: function (obj,tar) {		
	
	// find <ul.tm-sub_elems> the sub-elements list of target
	var list = tar.children(".tm-sub_elems");
	
	var needReload = false;
	var objIsFolder= obj.find('.tm-folder').length;
		
	if(list.hasClass('tm-root') && !obj.hasClass('tm-masonry') && objIsFolder) {
	    // mark root drop of folder for masonry
	    obj.addClass('tm-masonry');	
	    needReload = true;	
	} else if(obj.hasClass('tm-masonry')) {
	    // remove special attributes of folder on root
	    obj.removeClass('tm-masonry');	   
	    needReload = true;
	}					
	
	// append the dragged object to it
	obj.appendTo(list);
	obj.animate({left:'0',top:'0'},100,
	    function(){	
		// remove absolute position
		obj.css("position","");
		// reload masonry if nb of folder on root change
		if(needReload)
		    TreeManager.masonry.reloadItems();
		// height
		TreeManager.checkHeight(obj);
		// drop of a page on root ? check the column
		if(list.hasClass('tm-root') && !objIsFolder)		
		    TreeManager.checkRootColumn();
		// unfold list if necessary
		// else if(list.is(':hidden'))
		//    tar.trigger('click');
		else
		    // simple re-layout
		    TreeManager.masonry.layout()
	    }
	);
    },
    
    /**
     * called when a dragged element is dropped on a droppable one
     * 
     * @param e the event
     * @param ui a jquery-ui object
     */
    elemDropped: function (e,ui) {	
	
	//// avoid calling something if drop on the same parent
	// get first parent
	var par = ui.draggable.parents('.tm-drop').first();
	// check
	if (par.data('ref') == $(this).data('ref'))
	    return;
	
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
	
	// check if sub list is visible otherwise unfold it
	var sublist = anchor.children('.tm-sub_elems');
	if(sublist.is(':hidden'))
	    anchor.trigger('click');
	
	// input html
	var input = $('<input type="text" size="10" name="create"/>');	
	
	// add to subelems list
	input.prependTo(sublist);
	input.wrap('<li class="tm-drop"></li>');	
	
	// stop propagation of clicking on input
	input.click(function(e){e.stopPropagation();})
	
	// remove input on focus out
	input.focusout(function() {
	    input.parent().remove();
	    TreeManager.checkHeight(anchor);
	    TreeManager.masonry.layout();
	});
	
	// post on input change
	input.change(function() {
	    TreeManager.postCreate(anchor,input);	   
	});
	
	// special behavior on root
	if(sublist.hasClass('tm-root')) {
	    input.parent().addClass('tm-masonry');
	    TreeManager.masonry.prepended(input.parent().get());
	    
	} else
	    // resize only
	    TreeManager.checkHeight(anchor,'lay');	
	    // TreeManager.masonry.layout();
	
		
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
	var input = $('<input type="text" size="10" name="rename"/>'); 
	input.val(title.text());    // use former name
	input.insertBefore(title);
	title.detach();	// remove the original title but keep it in DOM
	
	// stop propagation of clicking on input
	input.click(function(e){e.stopPropagation();})
	
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
    
    pinup: function(cmd) {
	var anchor = cmd.parents(".tm-drop").first(); // consider only folders
	var mother = $('.tm-ddz').data('ref');
	// tools 1
	function pin(li) {	
	    li.data('mother',mother);		// keep track of parent folder	    
	    li.appendTo($('.tm-pinz'));
	    li.css({top:'0',left:'0',bottom:'0'});	// cancel absolute position from masonry
	    cmd.addClass('tm-pin-active');	    
	    $('.tm-root').css({width:'80%'});	    
	    $('.tm-pinz').show();
	} 
	
	// tools 2
	function unpin(li) {	    
	    // append to root only if it is the right mother
	    if(li.data('mother') == mother) {
		li.css('bottom','');		// remove it cause of bad visual effet
		li.appendTo($('.tm-root'));
		li.removeClass('tm-hover');
	    } else
		// remove completly
		li.remove();
	    $('.tm-root').css({width:'100%'});	       
	}
	
	
	// if something already pinned, unpin it
	var pinned = $('.tm-pinz > .tm-drop');
	if(pinned.length)
	    unpin(pinned);
	
	if(!cmd.hasClass("tm-pin-active")) {
	    // pin
	    pin(anchor);
	} else {
	    // unpin
	    unpin(anchor);
	    $('.tm-pinz').hide();
	}
	// redo masonry
	TreeManager.masonry.reloadItems();
	TreeManager.masonry.layout();
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
		if(way == 'free') {
		    // free element
		    anchor.remove();		    
		    // if remove from root, check the need of special column
		    if(cat.hasClass('tm-ddz'))
			TreeManager.checkRootColumn();
		} else if(way == 'assign') {		    
		    
		    var newanch = anchor.clone();
		    newanch.removeClass('ui-draggable-dragging');
		    newanch.find('.tm-cmd').click(function(e){e.stopPropagation();TreeManager.cmd($(this));});
		    TreeManager.animOver(newanch);		   
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
		    var zoom = $('<a class="tm-zoom"></a>').click_n_dblclick(TreeManager.propagateClick,TreeManager.zoom);
		    // nest elements
		    zoom.append(title);
		    newli.append(zoom);		    		   
		    
		    // get cmds menu 
		    var cmds = $(data.menu);
		    // bind click event
		    cmds.find('.tm-cmd').click(function(e){e.stopPropagation();TreeManager.cmd($(this));});
		    newli.append(cmds);
		    
		    // append a empty sub-elements list
		    var sub_list = $('<ul class="tm-sub_elems"></ul>');
		    newli.append(sub_list);		    
		    
		    // set binded reference (won't appear as a tag attribute)
		    newli.data('ref',data.ref);
		    
		    // make <li> draggable and droppable
		    newli.draggable(TreeManager.dragOptions);
		    newli.droppable(TreeManager.dropOptions);
		    
		    // animate it
		    TreeManager.animFold(newli);
		    TreeManager.animOver(newli);
		    
		    // masonry
		    if(input.parent().hasClass('tm-masonry')) {			
			newli.addClass('tm-masonry');
			newli.insertAfter(input.parent()); 
			TreeManager.masonry.remove(input.parent().get());
			TreeManager.masonry.prepended(newli.get());
		
		    } else		    
			// display <li>
			input.parent().replaceWith(newli); 
		} else
		    // remove input and leave everything as before
		    if(input.parent().hasClass('tm-masonry')) {
			TreeManager.masonry.remove(input.parent.get());
			TreeManager.masonry.layout();
		    } else {
			input.parent().remove();
			TreeManager.checkHeight(anchor,'lay')
		    }
				
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
		if(data.success) {
		    if(anchor.hasClass('tm-masonry')) {
			TreeManager.masonry.remove(anchor.get());
			TreeManager.masonry.layout();
		    } else {		
			// find parent folder on root
			var top = anchor.closest('.tm-masonry');
			
			anchor.remove();			
			TreeManager.checkHeight(top, 'lay');
		    }
		}
		
		// TreeManager.masonry.layout();
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
     * Simple click on folder's name must be propagate to the first parent list
     * witch have childs, exept after a drop event
     */
    propagateClick: function (elem) {	
	
	// get this li level, check we are not just after a drop event
	var li = elem.parents('.tm-drag').first();
	if(li.hasClass('tm-nozoom')) {
	    li.removeClass('tm-nozoom');
	    return;
	}
	
	// find parent folder on root
	var top = elem.closest('.tm-masonry');
	top.trigger('click');
	
	// get all parent folders
	/*var parents = elem.parents("li.tm-drop");
	
	// look for the first one with childs
	parents.each(function(i,parent){
	    if($(parent).find('.tm-drag').length) {
		
		$(parent).trigger('click');
		return false // job done, break loop
	    }
	});*/
	
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
	var anchor = title.parents(".tm-drag").first();
	
	// maybe event was fired after dropping operation ?
	// this is a fuse
	if(anchor.hasClass('tm-nozoom')) {
	    anchor.removeClass('tm-nozoom');
	    return;
	}
	
	// is it a simple page ?
	if(!TreeManager.is_cat(anchor.data('ref'))) {
	    // zoom to page
	    Yacs.displayOverlaid(title.attr('href'));
	    return;
	}
	
	// do not zoom in a pinned folder
	if(anchor.parents(".tm-pinz").length)
	    return;
	
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
			    var link = $('<a href="#" class="tm-crumbs"></a>');
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
		    var newroot = $(data.content).find('.tm-root');		    
		    $('.tm-ddz').data('ref',data.root_ref);
		    $(".tm-root").replaceWith(newroot);
		    TreeManager.init(data.userlevel);
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
		    var newroot = $(data.content).find('.tm-root');		    
		    $('.tm-ddz').data('ref',data.root_ref);
		    $(".tm-root").replaceWith(newroot);
		    TreeManager.init(data.userlevel);		    
		}
		Yacs.stopWorking();
	});
    }
}