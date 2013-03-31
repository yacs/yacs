/**
 * the YACS AJAX shared library.
 *
 * @link http://andykdocs.de/andykdocs/document/Migrating-from-the-Prototype-JavaScript-Framework-to-jQuery/Prototype-JS-to-jQuery-Migration-Cheat-Sheet-V1-April-2010.html
 *
 * This file extends jquery, etc., to enhance interactions with the end-user
 *
 * @author Bernard Paques
 * @author Christophe Battarel
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

var startWatch = new Date();

var Yacs = {

	/**
	 * alert the surfer
	 *
	 * @param message to display
	 * @param function to be called on click, if any
	 */
	alert: function(message, callBack) {

		Yacs.displayModalBox({ body: '<div style="text-align: left">'+message.replace(/\n/gi, '<br />')+'</div>',
			button_TRUE: "OK" }, callBack);

	},

	/**
	 * reflect last sort in table
	 *
	 * @param target table
	 * @param sorted column
	 */
	beautifyTable: function(tblEl, col) {

		// look for class names in styles
		var rowTest = new RegExp("odd", "gi");
		var colTest = new RegExp("sorted", "gi");

		// alternate row appearance
		var i, j;
		var rowEl, cellEl;

		for (i = 0; i < tblEl.rows.length; i++) {
			rowEl = tblEl.rows[i];
			$(rowEl).removeClass('odd');
			if((i % 2) !== 0) {
				$(rowEl).addClass('odd');
			}

			// set style classes on each column
			for (j = 2; j < tblEl.rows[i].cells.length; j++) {
				cellEl = rowEl.cells[j];
				$(cellEl).removeClass('sorted');
				// highlight the one that was sorted
				if (j == col) {
					$(cellEl).addClass('sorted');
				}
			}
		}

		// find the table header and highlight the column that was sorted
		var el = tblEl.parentNode.tHead;
		rowEl = el.rows[el.rows.length - 1];

		// set style classes for each column
		for (i = 0; i < rowEl.cells.length; i++) {
			cellEl = rowEl.cells[i];
			$(cellEl).removeClass('sorted');

			// highlight the header of the sorted column
			if (i == col) {
				$(cellEl).addClass('sorted');
			}
		}
	},

	/**
	 * implement multiple entries separated by comma
	 * autocompletion mecanism from remote php source
	 *
	 * build on jquery-ui autocomplete plugin
	 * @link http://jqueryui.com/demos/autocomplete/#multiple-remote
	 *
	 * @param string id of the target element (without '#')
	 * @param string web service to call (default: '/users/complete.php')
	 * @param function called back on completion, with new value as parameter
	 *
	 */
	autocomplete_m: function(target, source_url, callback) {
		function split( val ) {
			return val.split( /,\s*/ );
		}
		function extractLast( term ) {
			return split( term ).pop();
		}

		$('#'+target)
		    // don't navigate away from the field on tab when selecting an item
		    .bind( "keydown", function( event ) {
			    if( event.keyCode === $.ui.keyCode.TAB &&
					    $( this ).data( "autocomplete" ).menu.active ) {
				    event.preventDefault();
			    }
		    })
		    .autocomplete({
				source: function( request, response ) {
					$.getJSON( source_url, {
						term: extractLast( request.term )
					}, response );
				},
				search: function() {
					// custom minLength
					var term = extractLast( this.value );
					if ( term.length < 2 ) {
						return false;
					}
				},
				focus: function() {
					// prevent value inserted on focus
					return false;
				},
				select: function( event, ui ) {
					var terms = split( this.value );
					// remove the current input
					terms.pop();
					// add the selected item
					terms.push( ui.item.value );
					// add placeholder to get the comma-and-space at the end
					terms.push( "" );
					this.value = terms.join( ", " );
					if(callback)
						callback(ui.item.value);
					return false;
				}
		    });
	},

	/**
	 * autocomplete a field by querying an AJAX source
	 *
	 * @link http://jqueryui.com/demos/autocomplete/#custom-data
	 *
	 * @param string id of the target element (without '#')
	 * @param boolean true if only one value should be set, false if this is a comma-separated list of values
	 * @param string web service to call (default: '/users/complete.php')
	 * @param function called back on completion, with new value as parameter
	 *
	 * @see users/complete.php
	 **/
	autocomplete_names: function(target, unique, source_url, callback) {

		if(!source_url) {
			source_url = url_to_root + 'users/complete.php';
		}

	    // use the multiple entries autocomplete exept if unique user required
	    if(unique) {
	    	if(callback) {
				$('#'+target).autocomplete({source:source_url, minLength:2, select: function(event, ui) { $('#'+target).val(ui.item.value); callback(ui.item.value); }});
	    	} else {
				$('#'+target).autocomplete({source:source_url, minLength:2});
			}
	    } else
			Yacs.autocomplete_m(target, source_url, callback);

		// override rendering of items in menu list to show full name and email
		$('#'+target).data( "autocomplete" )._renderItem = function( ul, item ) {
			return $( "<li></li>" )
				.data( "item.autocomplete", item )
				.append( "<a>" + item.value + "<span class='informal details'> -&nbsp;" + item.label + "</span></a>" )
				.appendTo( ul );
		};
	},

	/**
	 * remote procedure call based on JSON-RPC
	 *
	 */
	call: function(parameters, callBack) {

		// hash encoding to JSON
		parameters = $.toJSON(parameters);   // use jquery-json plugin

		// start an ajax transaction
		$.ajax( {
			url: url_to_root + 'services/json_rpc.php',
			type: 'post',
			data: parameters,
			dataType: "json",
			success: function(response) {
				if(typeof callBack == 'function') {
					if(response.error) {
						callBack(response);
					} else if(response.result) {
						callBack(response.result);
					} else {
						callBack(1);
					}
				}
				response = null; // no memory leak
			},
			error: function(handle, ajaxOptions, thrownError) {
//				console.log('status: '+handle.statusText);
//				console.log('error: '+thrownError);
				if(typeof callBack == 'function') {
					callBack(0);
				}
			}
		});
	},

	/**
	 * clear one cookie
	 *
	 * @param cookie name
	 */
	clearCookie: function(name) {
		if(Yacs.getCookie(name)) {
			document.cookie = name + "=; expires=Thu, 01-Jan-70 00:00:01 GMT";
		}
	},

	/**
	 * trigger the show
	 */
	clickImage: function() {
		Yacs.showImage(this);
		return false;
	},

	/**
	 * close the modal box
	 */
	closeModalBox: function() {

		$('#modal_content').fadeTo(0.3, 0.3,
			function() {

				// clear the content
				$('#modal_content').html('');

				// mask the modal box
				$('#modal_panel').css('display', 'none');

			} );
	},

	/**
	 * ask for confirmation
	 *
	 * @param message to display
	 * @param callBack the function to call when the OK button is cliqued
	 */
	confirm: function(message, callBack) {

		Yacs.displayModalBox({ body: '<div style="text-align: left">'+message.replace(/\n/gi, '<br />')+'</div>',
			button_TRUE: "OK", button_FALSE: "Cancel" }, callBack);

	},

	/**
	 * compare two values
	 *
	 * @param first value
	 * @param second value
	 * @return comparison value
	 */
	compareValues: function(v1, v2) {

		// if the values are numeric, convert them to floats
		var f1 = parseFloat(v1);
		var f2 = parseFloat(v2);
		if(!isNaN(f1) && !isNaN(f2)) {
			v1 = f1;
			v2 = f2;
		}

		// compare the two values
		if(v1 == v2) {
			return 0;
		}
		if(v1 > v2) {
			return 1;
		}
		return -1;
	},

	/**
	 * detect Flash on client-side
	 *
	 * The script used to detect Flash under Firefox, etc. This is complemented
	 * by server-side detection for IE.
	 *
	 * This function also save findings in a cookie for transmission to server.
	 *
	 * @return 'yes' or 'no'
	 */
	detectFlash: function () {

		var detected = Yacs.getCookie("FlashIsAvailable");

		// cookie has already been set
		if(detected) {
			return detected;
		}

		// detect in plugins
		if((navigator.plugins !== null) && (navigator.plugins.length > 0)) {
			if(navigator.plugins["Shockwave Flash 2.0"] || navigator.plugins["Shockwave Flash"]) {
				detected = 'yes';
			}
		} else if(navigator.userAgent.toLowerCase().indexOf("webtv") != -1) {
			detected = 'yes';

		// assume Flash is embedded in Windows
		} else if(navigator.userAgent.toLowerCase().indexOf("windows") != -1) {
			detected = 'yes';
		}

		// remember this in a cookie
		Yacs.setCookie("FlashIsAvailable", detected);

		// return the result, if useful
		return detected;

	},

	/**
	 * compute and record surfer time zone
	 *
	 * This function computes the surfer time zone, and save it in a cookie to
	 * share this with the back-end
	 *
	 * @return the time zone
	 */
	detectTimeZone: function () {

		var timeZone = Yacs.getCookie("TimeZone");

		// cookie has already been set
		if(timeZone) {
			return timeZone;
		}

		// compute time shift
		var now = new Date();
		timeZone = (-now.getTimezoneOffset() /60);

		// remember this in a cookie
		Yacs.setCookie("TimeZone", timeZone);

		// return the result, if useful
		return timeZone;

	},

	/**
	 * display a modal box
	 *
	 * @param string XHTML content of the box
	 * @param function to be called on OK or Cancel
	 *
	 * @see tools/ajax.php
	 */
	displayModalBox: function(content, callBack) {

		// prepare box actual content
		var boxContent = '';

		if(content.title) {
			boxContent += '<h2 class="boxTitle">'+content.title+'</h2>';
		}
		if(content.body) {
			boxContent += '<div class="boxBody">'+content.body+'</div>';
		}

		// handle key press
		var keyCodes = [];

		// all buttons
		boxContent += '<div class="boxButtons">';
		if(content.button_PREVIOUS) {
			if(typeof Yacs.modalCallPrevious == "function") {
				boxContent += '<span class="button"><button type="button" onclick="(Yacs.modalCallPrevious)();"><div style="padding: 0 1em">'+content.button_PREVIOUS+'</div></button></span> ';
			} else {
				boxContent += '<span class="button"><button type="button" disabled="disabled"><div style="padding: 0 1em">'+content.button_PREVIOUS+'</div></button></span> ';
			}
		}
		if(content.button_TRUE) {
			boxContent += '<span class="button"><button type="button" onclick="if(typeof Yacs.modalCallBack == &quot;function&quot;) { (Yacs.modalCallBack)(true) }; Yacs.closeModalBox();"><div style="padding: 0 1em">'+content.button_TRUE+'</div></button></span> ';
		}
		if(content.button_FALSE) {
			boxContent += ' &nbsp; <span class="button"><button type="button" onclick="if(typeof Yacs.modalCallBack == &quot;function&quot;) { (Yacs.modalCallBack)(false) }; Yacs.closeModalBox();">'+content.button_FALSE+'</button></span> ';
		}
		if(content.button_NEXT) {
			if(typeof Yacs.modalCallNext == "function") {
				boxContent += '<span class="button"><button type="button" onclick="(Yacs.modalCallNext)();"><div style="padding: 0 1em">'+content.button_NEXT+'</div></button></span>';
			} else {
				boxContent += '<span class="button"><button type="button" disabled="disabled"><div style="padding: 0 1em">'+content.button_NEXT+'</div></button></span>';
			}
		}
		boxContent += '</div>';

		// wait for the update
		if(typeof callBack == "function") {
			Yacs.modalCallBack = callBack;
		} else {
			Yacs.modalCallBack = null;
		}

		// insert some html at the bottom of the page that looks similar to this:
		//
		// <div id="modal_panel">
		//	<div id="modal_centered">
		//		<div id="modal_content">
		//			content, passed as parameter, goes here
		//		</div>
		//	</div>
		// </div>

		// extend the DOM tree
		if(!Yacs.modalOverlay) {

			var objContent = document.createElement("div");
			$(objContent).attr('id','modal_content');
			$(objContent).html('<img src="'+Yacs.spinningImage.src+'" />');

			var objCentered = document.createElement("div");
			$(objCentered).attr('id','modal_centered');
//			objCentered.css({ visibility: 'hidden' });
			$(objCentered).append(objContent);

			Yacs.modalOverlay = document.createElement("div");
			$(Yacs.modalOverlay).attr('id','modal_panel');
			$(Yacs.modalOverlay).click(function() {});	// you have to click in the box to close the modal box
			$(Yacs.modalOverlay).append(objCentered);

			var objBody = document.getElementsByTagName("body").item(0);
			$(objBody).append(Yacs.modalOverlay);

		// ensure containers are visible to compute box size
		} else {
			$('#modal_panel').css('display', 'block');
		}

		// paint or repaint box content
		$('#modal_content').fadeTo(0.1, 0.3,
			function() {

				// update the content
				Yacs.updateModalBox(boxContent);

				// display the updated box
				$('#modal_content').css('visibility', 'visible');
				$('#modal_content').fadeTo(0.3, 1.0);

			});



	},

	/**
	 * filter floating numbers
	 *
	 * usage: onkeypress="return Yacs.filterFloat(this, event)"
	 *
	 * @param handle of the filtered element
	 * @param keyboard event
	 */
	filterFloat: function(handle, event) {
		var key;
		if(window.event) {
			key = window.event.keyCode;
		} else if(event)
			key = event.which;
		else
			return true;

		// control keys
		if((key==null) || (key<32))
			return true;

		// numbers
		if((("0123456789,.+-").indexOf(String.fromCharCode(key)) > -1))
			return true;

		// filtered out
		return false;
	},

	/**
	 * filter integer numbers
	 *
	 * usage: onkeypress="return Yacs.filterInteger(this, event)"
	 *
	 * @param handle of the filtered element
	 * @param keyboard event
	 */
	filterInteger: function(handle, event) {
		var key;
		if(window.event) {
			key = window.event.keyCode;
		} else if(event)
			key = event.which;
		else
			return true;

		// control keys
		if((key==null) || (key<32))
			return true;

		// numbers
		if((("0123456789+-").indexOf(String.fromCharCode(key)) > -1))
			return true;

		// filtered out
		return false;
	},

	/**
	 * get the value of one cookie
	 *
	 * @param cookie name
	 * @return cookie value
	 */
	getCookie: function(name) {

		var prefix = name + "=";
		var begin = document.cookie.indexOf(prefix);
		if(begin == -1) { return null; }
		var end = document.cookie.indexOf(";", begin);
		if(end == -1) { end = document.cookie.length; }
		return unescape(document.cookie.substring(begin + prefix.length, end));

	},

	/**
	 * get a sortable string
	 *
	 * This function finds and concatenate the values of all text nodes
	 *
	 * @param the target DOM element
	 * @return a sortable string
	 */
	getTextValue: function(el) {

		// the returned string
		var s = "";

		// look at every child node
		var i;
		for(i = 0; i < el.childNodes.length; i++) {

			// a text node
			if(el.childNodes[i].nodeType == 3) {
				s += el.childNodes[i].nodeValue;

			// an element node which is a break
			} else if((el.childNodes[i].nodeType == 1) && (el.childNodes[i].tagName == "br")) {
				s += " ";

			// use recursion to get text within sub-elements
			} else {
				s += Yacs.getTextValue(el.childNodes[i]);
			}

		}

		return s;
	},

	/**
	 * expand a commenting textarea
	 *
	 * This is used in walls
	 *
	 * @param handle to the item to expand
	 */
	growPanel: function(handle) {
		$(handle).css('height', '10em');
	},

	/**
	 * we have received an 'alert' notification
	 *
	 * @see users/notifications.php
	 */
	handleAlertNotification: function(response) {

		// build a message in English if no localized message has been provided by the server
		if(typeof response.dialog_text != 'string') {
			response.dialog_text = 'New at ' + response.title + "\nby: " + response.nick_name + "\n" + 'Would you like to browse this page?';
		}

		// switch to the offered address, if accepted by surfer
		if(typeof response.address == 'string') {
			Yacs.confirm(response.dialog_text, function(choice) { if(choice) {window.open(response.address);} });
		}
	},

	/**
	 * we have received a 'browse' notification
	 *
	 * @see users/notifications.php
	 */
	handleBrowseNotification: function(response) {

		// build a message in English if no localized message has been provided by the server
		if(typeof response.dialog_text != 'string') {
			response.dialog_text = 'From ' + response.nick_name + ":\n" + response.message + "\n" + 'Would you like to browse the provided link?';
		}

		// switch to the offered address, if already in tracker, or if accepted by surfer
		if(typeof response.address == 'string') {
			if((window.name == 'yacs_tracker')) {
				window.open(response.address, 'yacs_tracker');
			} else {
				Yacs.confirm(response.dialog_text, function(choice) { if(choice) {window.open(response.address, 'yacs_tracker');} });
			}
		}
	},

	/**
	 * we have received a 'hello' notification
	 *
	 * @see users/notifications.php
	 */
	handleHelloNotification: function(response) {

		// build a message in English if no localized message has been provided by the server
		if(typeof response.dialog_text != 'string') {
			response.dialog_text = 'From ' + response.nick_name + ":\n" + response.message;

			if(typeof response.address == 'string') {
				response.dialog_text += "\n" + 'Would you like to chat with this person?';
			}
		}

		// only show the message to the end-user
		if(typeof response.address != 'string') {
			Yacs.alert(response.dialog_text);

		// else switch to the offered address, if accepted by surfer
		} else {
			Yacs.confirm(response.dialog_text, function(choice) { if(choice) {window.open(response.address);} });
		}
	},

	/**
	 * prepare for mouse hovering
	 *
	 * @param mixed id of the object to equip, or a reference to it
	 *
	 * @see forms/forms.js
	 * @see tools/ajax.php
	 */
	addOnDemandTools: function(handle) {

		if(typeof handle != "object")
	        handle = $("#" + handle);

		var prefix = '';
		if(handle.hasClass('sortable')) {
			prefix += '<span class="onHoverLeft drag_handle"><img src="'+url_to_root+'skins/_reference/ajax/on_demand_handle.png" width="16" height="16" alt="Drag" /></span>';
		}

		var suffix = '<span class="onHoverRight">';
		if(handle.hasClass('mutable')) {
			suffix += '<a href="#" onclick="Yacs.toggleProperties(\'#'+ Yacs.identify(handle) +'\'); return false;"><img src="'+url_to_root+'skins/_reference/ajax/on_demand_properties.png" width="16" height="16" alt="Properties" /></a>';
		}
		suffix += '<a href="#" onclick="$(\'#'+ Yacs.identify(handle) +'\').remove(); return false;"><img src="'+url_to_root+'skins/_reference/ajax/on_demand_delete.png" width="16" height="16" alt="Delete" /></a></span>';

		handle.prepend(suffix + prefix);

		handle.mouseout(function () { Yacs.mouseOut('#'+ Yacs.identify($(this))); return false; });
		handle.mouseover(function () { Yacs.mouseOver('#'+ Yacs.identify($(this))); return false; });
	},

	/**
	 * mouse is moving elsewhere
	 */
	mouseOut: function(handle) {
		$(handle + ' .onHoverLeft, ' + handle + ' .onHoverRight')
			.css('visibility', 'hidden');
	},

	/**
	 * mouse is coming on top of some element
	 */
	mouseOver: function(handle) {
		$(handle + ' .onHoverLeft, ' + handle + ' .onHoverRight')
			.css('visibility', 'visible');
	},

	toggleProperties: function(handle) {
        $(handle).children('.properties').toggle('slide');
	},

	/**
	 * general initialization
	 */
	onWindowLoad: function() {

		// resize according to surfer preferences
		Yacs.textSize();

		// this window has the focus
		Yacs.hasFocus = true;

		// compute and record surfer time zone
		Yacs.detectTimeZone();

		// detect Flash on client side
		Yacs.detectFlash();

		// change the behavior of buttons used for data submission, except those with style 'no_spin_on_click'
		$('button').each(function() {
			var buttonType = String($(this).attr('type'));
			if(buttonType.toLowerCase().match('submit') && !$(this).hasClass('no_spin_on_click')) {
				$(this).click(Yacs.startWorking);
			}
		});

		// show tips
		$('a[title].tip').each(function() {
			$(this).tipsy({fallback: $(this).attr('title'), gravity: $.fn.tipsy.autoNS, fade: true}).tipsy("show");
		});

		// close all tooltips on tabbing, etc
		$("body").bind("yacs", function(e) {
			$('a.tip,input.tip,textarea.tip').each(function() { $(this).tipsy("hide"); });
		});

		// load the link in a scaled-down iframe
		$('a.tipsy_preview').each(function() {
			$(this).tipsy({fallback: '<div class="tipsy_thumbnail"><iframe class="tipsy_iframe" src="'+$(this).attr('href')+'" /></div>',
				html: true,
				gravity: $.fn.tipsy.autoWE,
				fade: true,
				offset: 8,
				opacity: 1.0});
		});

		// beautify links titles in menu bars
		$('.menu_bar a[title]').each(function() {
			$(this).tipsy({fallback: $(this).attr('title'), gravity: $.fn.tipsy.autoNS, fade: true});
		});

		// stick some div to the top
		var handle = $('div.stickyHeader');
		if(handle && handle.offset())
			Yacs.stickyHeader = handle.offset().top;

		// adjust stickies on load
		Yacs.positionStickies();

		// adjust position on scrolling and on resize
		$(window).scroll(Yacs.positionStickies).resize(Yacs.positionStickies);

		// on-demand headers
		$('.onDemandTools').each(function() {
			Yacs.addOnDemandTools($(this));
		});

		// identify all elements that should be tiled
		$('.floating_box').addClass('tile');
		$('div.description .thumbnail_image').addClass('tile');

		// create groups of adjacent tiles
		var siblingsLast = null;
		$('.tile').each(function(){

			// head of a group of tiles
			if(!siblingsLast)
				siblingsLast = $(this).nextUntil(':not(.tile)').andSelf().wrapAll('<div class="tiler" />').last();

			// tail of the group
			if( $(this).is(siblingsLast) )
				siblingsLast = null;
		});

		// do the tiling
		var $tiled = $('.tiler');

		$tiled.imagesLoaded( function(){
			$tiled.masonry({
				itemSelector: '.tile'
			});
		});

		// prepare for a nice slideshow
		var anchors = $('.image_show');
		for(index = 0; index < anchors.length; index++) {
			var anchor = anchors[index];
			anchor.onclick = Yacs.clickImage;
			if(index > 0) {
				anchor.previousAnchor = anchors[index - 1];
			}
			if(index+1 < anchors.length) {
				anchor.nextAnchor = anchors[index + 1];
			}

			anchor = null; // no memory leak
		}

		// slow down notifications on window blur
		$(window).blur(Yacs.looseFocus);

		// back to normal rate on focus
		$(window).focus(Yacs.getFocus);

		// check for asynchronous notifications
		setTimeout(Yacs.subscribe, 40000);
	},

	getFocus: function() {
		Yacs.hasFocus = true;
	},

	looseFocus: function() {
		Yacs.hasFocus = false;
	},

	/**
	 * open a popup window
	 */
	popup: function(options) {

		// default options
		this.options = {
			url: '#',
			width: 600,
			height: 500,
			name:"_blank",
			location:"no",
			menubar:"no",
			toolbar:"no",
			status:"yes",
			scrollbars:"yes",
			resizable:"yes",
			left:"",
			top:"",
			normal:false
		};

		// use provided options, if any
		$.extend(this.options, options || {});

		// sanity check
		if(this.options.normal) {
			this.options.menubar = "yes";
			this.options.status = "yes";
			this.options.toolbar = "yes";
			this.options.location = "yes";
		}

		// some computations
		this.options.width = (this.options.width < screen.availWidth?this.options.width:screen.availWidth);
		this.options.height= (this.options.height < screen.availHeight?this.options.height:screen.availHeight);
		var openoptions = 'width='+this.options.width+',height='+this.options.height+',location='+this.options.location+',menubar='+this.options.menubar+',toolbar='+this.options.toolbar+',scrollbars='+this.options.scrollbars+',resizable='+this.options.resizable+',status='+this.options.status;
		if(this.options.top !== '') { openoptions+=",top="+this.options.top; }
		if(this.options.left !== '') { openoptions+=",left="+this.options.left; }

		// open the popup
		var window_handle;
		window_handle = window.open(this.options.url, this.options.name, openoptions);

		// add content, if option allows to do so
		if(window_handle && this.options.content && (this.options.url == '#')) {
			window_handle.document.write(this.options.content);
			window_handle.document.close();
		}

		// focus on the new window
		if(window_handle && window_handle.focus) {
			window_handle.focus();
		}

		// allow for additional handling of this window
		return window_handle;
	},

	/**
	 * adjust sticky header and footer
	 */
	positionStickies: function() {

		// adjust sticking header
		var wt = $(window).scrollTop();
		$('div.stickyHeader').each(function() {
			var mt = Yacs.stickyHeader;
			$(this).css({'position': (wt>mt) ? 'fixed' : 'static', 'top': (wt>mt) ? '0px' : ''});
		});

		// adjust sticking footer
		$('div.stickyFooter').each(function() {
			$(this).css({position: "absolute",
				top: ($(window).scrollTop()+$(window).height()-$(this).outerHeight())+"px"});
		});

	},

	/**
	 * set a new cookie
	 *
	 * @param cookie name
	 * @param cookie value
	 * @param days before expiration (optional)
	 */
	setCookie: function(name, value) {

		var argv = arguments;
		var argc = arguments.length;
		var expires = (argc > 2) ? argv[2] : null;
		if(expires > 0) {
			var today = new Date();
			var future = today.getTime() + 3600000*24*expires;
			expires = new Date();
			expires.setTime(future);
		}
		var path = (argc > 3) ? argv[3] : '/';
		var domain = (argc > 4) ? argv[4] : null;
		var secure = (argc > 5) ? argv[5] : false;
		document.cookie = name + "=" + escape (value) +
			((expires === null) ? "" : ("; expires=" + expires.toGMTString())) +
			((path === null) ? "" : ("; path=" + path)) +
			((domain === null) ? "" : ("; domain=" + domain)) +
			((secure === true) ? "; secure" : "");

	},

	/**
	 * similar to lightbox, integrated into yacs
	 */
	showImage: function(anchor) {

		// load the image in the background
		var loader = new Image();
		loader.onload=function(){

			// adjust image size to viewport dimensions
			var scale = 1.0;
			if((loader.width > 1) && (loader.width + 30 > $(window).width())) {
				scale = ($(window).width() - 30) / loader.width;
				loader.height*= scale;
				loader.width *= scale;
			}

			if((loader.height > 1) && (loader.height + 110 > $(window).height())) { // take title and buttons into account
				scale = ($(window).height() - 110) / loader.height;
				loader.height*= scale;
				loader.width *= scale;
			}

			// difference with previous image
			var yDelta = 0;
			if(typeof Yacs.previousImageHeight == 'number') {
				yDelta = loader.height - Yacs.previousImageHeight;
			}
			Yacs.previousImageHeight = loader.height;
			var xDelta = 0;
			if(typeof Yacs.previousImageWidth == 'number') {
				xDelta = loader.width - Yacs.previousImageWidth;
			}
			Yacs.previousImageWidth = loader.width;

			// rescale on size change
			if((yDelta !== 0) && (xDelta !== 0) && $('#modal_image_panel')) {

				// previous image -- <div id="modal_image_panel"><img ...
				var previousImage = $('#modal_image_panel').children('img');

				// current height and width
				var currentHeight = previousImage.height();
				var currentWidth = previousImage.width();

				// compute scaling factors
				var yScale = ((currentHeight + yDelta) / currentHeight) * 100;
				var xScale = ((currentWidth + xDelta) / currentWidth) * 100;

				// scaling previous image makes ugly things
				$('#modal_content').fadeTo(0.1, 0.0);
				$('#modal_content').css('visibility', 'hidden');

				// adjust the overall size
				if(yDelta !== 0) {
				    previousImage.effect("scale", {direction: 'vertical', percent: yScale, duration: 400, queue: 'end'});
				}
				if(xDelta !== 0) {
				    previousImage.effect("scale", {direction: 'horizontal', percent: xScale, duration: 400, queue: 'end'});
				}
			}

			// image title -- <a><span><img title="" ...
			var imageTitle = $(anchor).find('img').attr('title');

			// image href
			var imageReference = '<div id="modal_image_panel"><img src="'+$(anchor).attr('href')+'" width="'+loader.width+'" height="'+loader.height+'" /></div>';

			// a neighbour on the left
			if(anchor.previousAnchor) {
				Yacs.modalCallPrevious = function() { Yacs.showImage(anchor.previousAnchor); };
			} else {
				Yacs.modalCallPrevious = null;
			}

			// a neighbour on the right
			if(anchor.nextAnchor) {
				Yacs.modalCallNext = function() { Yacs.showImage(anchor.nextAnchor); };

				// do not wait for user click to load the image
				var nextLoader = new Image();
				nextLoader.src = $(anchor.nextAnchor).attr('href');

			} else {
				Yacs.modalCallNext = null;
			}

			// display the image on screen
			Yacs.displayModalBox({ title: imageTitle,
				body: imageReference,
				button_PREVIOUS: '<<',
				button_TRUE: 'X',
				button_NEXT: '>>' });

			//	clear onLoad, IE behaves erratically with animated gifs otherwise
			loader.onload = function(){};
		};

		// actual pre-load
		loader.src = $(anchor).attr('href');

	},

	/**
	 * slide a panel
	 *
	 * @param the clicked item
	 * @param string URL of the extending icon
	 * @param string URL of the collapsing icon
	 * @param boolean TRUE to align to the left of the container, FALSE to align on right edge
	 * @param boolean TRUE to slide down, FALSE to slide up
	 */
	slidePanel: function(handle, down_href, up_href, onLeft, down) {

		// align to the parent container
		var container = $(handle).parent();
		$(container).css({position: 'relative', zIndex: 100});

		// the panel to slide
		var panel = $(handle).next('.panel');

		// align the sliding panel
		if((onLeft !== true) && (onLeft !== false)) {
			onLeft = ($(panel).width() > ($(container).offset().left + $(handle).width()));
		}

		if((down !== true) && (down !== false)) {
			down = true;
		}

		// do the alignment
		if(onLeft && down) {
			$(panel).css({position: 'absolute', top: '100%', left: 0});

		}
		if(onLeft && !down) {
			$(panel).css({position: 'absolute', bottom: '100%', left: 0});

		}
		if(!onLeft && down) {
			$(panel).css({position: 'absolute', top: '100%', right: 0});
		}
		if(!onLeft && !down) {
			$(panel).css({position: 'absolute', bottom: '100%', right: 0});
		}

		// display the panel if it is not visible
		if($(panel).css("display") == 'none') {

			$(panel).css({position: 'relative', zIndex: 200}).slideDown({duration: 'slow', scaleContent:false});

			// change the image (if there is an image)
			var icon = $(handle).children('img');
			if(icon && up_href) {
				icon.attr('src',up_href);
			}

		// collapse the panel if it is visible
		} else {

			$(panel).slideUp({duration: 'slow', scaleContent:false});

			// change the image (if there is an image)
			var icon = $(handle).children('img');
			if(icon && down_href) {
				icon.attr('src',down_href);
			}

		}

	},

	/**
	 * show the spinning wheel
	 *
	 * This function displays a nice spinning image while loading the page.
	 *
	 * @param string id of the target CSS container
	 *
	 */
	spin: function(panel) {

		if(Yacs.spinningImage)
			$(panel).html('<img alt="*" src="' + Yacs.spinningImage.src + '" style="vertical-align:-3px" />');

	},

	spinningImage: null,

	/**
	 * load some opaque overlay during back-end processing
	 */
	startWorking: function() {

		if(Yacs.workingOverlay) {
			$(Yacs.workingOverlay).css({ display: 'block' });
			return true;
		}

		// insert some html at the bottom of the page that looks similar to this:
		//
		// <div id="yacsWorkingOverlay">
		//	<div>
		//		<img src="/yacs/skins/_reference/ajax/ajax_working.gif" />
		//	</div>
		// </div>

		var objWorkingImage = document.createElement("img");
		$(objWorkingImage).attr('src', url_to_root + 'skins/_reference/ajax/ajax_working.gif');

		var objCentered = document.createElement("div");
		$(objCentered).css({ position: 'absolute', top: '30%', left: '0%', height: '25%', width: '100%', textAlign: 'center', lineHeight: '0' });
		$(objCentered).append(objWorkingImage);

		Yacs.workingOverlay = document.createElement("div");
		$(Yacs.workingOverlay).attr('id','yacsWorkingOverlay');
		$(Yacs.workingOverlay).css({ position: 'fixed', top: '0', left: '0', zIndex: '1000', width: '100%', height: '100%', minHeight: '100%', backgroundColor: '#000', filter: 'alpha(opacity=20)', opacity: '0.2', display: 'block' });
		$(Yacs.workingOverlay).click(function() { $(Yacs.workingOverlay).css({ display: 'none' });});
		$(Yacs.workingOverlay).append(objCentered);

		var objBody = document.getElementsByTagName("body").item(0);
		$(objBody).append(Yacs.workingOverlay);

		return true;
	},

	/**
	 * hide the working overlay
	 */
	stopWorking: function() {

		var handle = $('#yacsWorkingOverlay');
		if(handle) {
			$(handle).css({ display: 'none' });
		}

	},

	/**
	 * subscribe to notifications sent by the back-end asynchronously
	 *
	 * @see users/heartbit.php
	 */
	subscribe: function() {

		// not less than 3 seconds between two successive calls
		var now = new Date();
		if((typeof Yacs.subscribeStamp == "object") && (now.getTime() < (Yacs.subscribeStamp.getTime() + 3000))) {
			return;
		}
		Yacs.subscribeStamp = now;

		// a transaction is taking place
		if(Yacs.subscribeAjax) {
			return;
		}

		// clear on-going action, if any
		if((typeof Yacs.subscribeTimer == "number") && (Yacs.subscribeTimer > 0)) {
			clearTimeout(Yacs.subscribeTimer);
			Yacs.subscribeTimer = 0;
		}

		// start an ajax transaction
		Yacs.subscribeAjax = $.ajax(url_to_root + 'users/heartbit.php', {
			type: 'get',
			data: {'reference': Yacs.current_item, 'action': Yacs.current_action},
			dataType: "json",
			success: Yacs.subscribeSuccess,
			error: Yacs.subscribeFailure
		});
	},

	subscribeFailure: function(transport) {

		if(Yacs.hasFocus) { // regular idle cycle
			Yacs.subscribeTimer = setTimeout(Yacs.subscribe, 40000);
		} else { // don't stress the server when we don't have the focus
			Yacs.subscribeTimer = setTimeout(Yacs.subscribe, 120000);
		}
		Yacs.subscribeAjax = null;

	},

	subscribeSuccess: function(response) {

		// dispatch received notification
		if(response) {
			switch(response.type) {
			case 'alert':
				Yacs.handleAlertNotification(response);
				break;
			case 'browse':
				Yacs.handleBrowseNotification(response);
				break;
			case 'hello':
				Yacs.handleHelloNotification(response);
				break;
			}
		}

		// minimum time between two successive notifications
		Yacs.subscribeTimer = setTimeout(Yacs.subscribe, 20000);
		Yacs.subscribeAjax = null;

	},

	// on-going timer, if any
	subscribeTimer: 0,

	/**
	 * sort a table
	 *
	 * @param id of the table, tbody or tfoot element to be sorted
	 * @param index of the column to sort, starting at 0
	 * @param if true, sort in reverse order
	 */
	sortTable: function(id, column, rev) {

		// get the table or table section to sort
		var handle = document.getElementById(id);

		// on first sort set up an array of reverse sort flags
		if(!handle.reverseSort || (handle.reverseSort === null)) {
			handle.reverseSort = [];
		}

		// set the initial sort direction
		if(!handle.reverseSort[column] || (handle.reverseSort[column] === null)) {
			handle.reverseSort[column] = rev;
		}

		// if this column was the last one sorted, reverse its sort direction
		if(column == handle.lastColumn) {
			handle.reverseSort[column] = !handle.reverseSort[column];
		}

		// remember this column as the last one sorted
		handle.lastColumn = column;

		// hide the table during operations
		var oldDsply = $(handle).css("display");
		$(handle).css("display","none");

		// use a selection sort algorithm
		var tmpEl;
		var i, j;
		var minVal, minIdx;
		var testVal;
		var cmp;
		for(i = 0; i < handle.rows.length - 1; i++) {

			// assume the current row has the minimum value
			minIdx = i;
			minVal = Yacs.getTextValue(handle.rows[i].cells[column]);

			// look for a smaller values in following rows
			for(j = i + 1; j < handle.rows.length; j++) {
				testVal = Yacs.getTextValue(handle.rows[j].cells[column]);
				cmp = Yacs.compareValues(minVal, testVal);

				// the case of reverse ordering
				if(handle.reverseSort[column]) {
					cmp = -cmp;
				}

				// sort by the first column if those values are equal
				if(cmp === 0 && column !== 0) {
					cmp = Yacs.compareValues(Yacs.getTextValue(handle.rows[minIdx].cells[0]), Yacs.getTextValue(handle.rows[j].cells[0]));
				}

				// this is the new minimum
				if (cmp > 0) {
					minIdx = j;
					minVal = testVal;
				}
			}

			// move the minimum row just below the current row
			if (minIdx > i) {
				tmpEl = handle.removeChild(handle.rows[minIdx]);
				handle.insertBefore(tmpEl, handle.rows[i]);
			}
		}

		// beautify the table
		Yacs.beautifyTable(handle, column);

		// Set team rankings.
		//	setRanks(handle, column, rev);

		// show the table again
		$(handle).css("display", oldDsply);

		return false;
	},

	/**
	 * @link http://ajaxcookbook.org/
	 */
	syslog: function(message) {

		if (!Yacs.window_ || Yacs.window_.closed) {
			var win = window.open("", null, "width=400,height=200," +
								  "scrollbars=yes,resizable=yes,status=no," +
								  "location=no,menubar=no,toolbar=no");
			if (!win) { return; }
			var doc = win.document;
			doc.write("<html><head><title>Debug Log</title></head>" +
				  "<body></body></html>");
			doc.close();
			Yacs.window_ = win;
		}
		var logLine = Yacs.window_.document.createElement("div");
		$(logLine).append(Yacs.window_.document.createTextNode('=> ' + message));
		$(Yacs.window_.document.body).append(logLine);

	},

	/**
	 * This handler adds code to tabbing items, to process further clicks.
	 *
	 * It has to be called once, after the construction of proper DOM elements.
	 * You can either build the DOM manually, then place a call to this function,
	 * or, alternatively, just call Skin::build_tabs() from within you PHP code
	 * to have everything done automatically.
	 *
	 * @param tabs a list of tabs related to panels and URLs
	 * @param args additional ajax settings given to $.ajax()
	 *
	 * @see users/view.php
	 * @see skins/skin_skeleton.php
	 *
	 * @link http://actsasflinn.com/Ajax_Tabs/index.html AJAX Tabs (Rails redux)
	 * @link http://20bits.com/2007/05/23/dynamic-ajax-tabs-in-20-lines/
	 */
	tabs: function(tabs, args) {

		Yacs.tabs_list = tabs;
		Yacs.tabs_args = args;
		Yacs.tabs_current = null;

		// react to clicks
		var id;
		for(id in tabs) {
			if(tabs.hasOwnProperty(id)) {

				// instrument this tab
				$("#"+id).click(Yacs.tabsEvent);

				// we are on first tab
				if(!Yacs.tabs_current) {
					Yacs.tabs_current = id;
				}
			}
		}

		// where are we?
		if(window.location.hash.length > 1) {
			var hash = document.location.hash.substr(1,document.location.hash.length);

			// are we already there?
			if(Yacs.tabs_current == hash)
				return;

			// change to this tab
			for(id in tabs) {
				if(id == hash) {
					Yacs.tabsDisplay(id);
					break;
				}
			}

			// wait until next change of hash
			Yacs.tabs_current = hash;
		}

	},

	/**
	 * display given tab
	 */
	tabsDisplay: function(id) {

		// fade away all other tabs
		var newCurrent;
		var iterator;
		var panel;
		for(iterator in Yacs.tabs_list) {

			panel = Yacs.tabs_list[iterator][0];
			if(id == iterator) {
				newCurrent = iterator;

			} else {

			    // update the tab
			    $("#"+iterator).removeClass('tab-foreground');
			    $("#"+iterator).addClass('tab-background');

			    // update the panel
			    if($("#"+panel).css("display") != 'none') {
				    $("#"+panel).fadeOut(.1);
			    }
			$("#"+panel).removeClass('panel-foreground');
			$("#"+panel).addClass('panel-background');
			}
		}

		// activate the clicked tab -- see skins/_reference/ajax.css
		panel = Yacs.tabs_list[newCurrent][0];

		// remember our state
		Yacs.tabs_current = id;

		// update the tab
		$("#"+newCurrent).removeClass('tab-background');
		$("#"+newCurrent).addClass('tab-foreground');

		// update the panel
		if($("#"+panel).css("display") == 'none') {
			$("#"+panel).fadeIn(.1);
		}
		$("#"+panel).removeClass('panel-background');
		$("#"+panel).addClass('panel-foreground');

		// load panel content, if necessary
		if(Yacs.tabs_list[newCurrent].length > 1) {
			Yacs.updateOnce(panel, Yacs.tabs_list[newCurrent][1], Yacs.tabs_args);
		}

		// dispatch custom event (e.g., for tooltips, Google Maps, etc)
		$('body').trigger('yacs');
	},

	/**
	 * click on a tab
	 */
	tabsEvent: function(e) {

		// target the clicked tab
		var clicked = this;

		// if we click on a link, move upwards to list item -- 'a' is for XHTML strict, 'A' for other cases
		if((clicked.tagName == 'a') || (clicked.tagName == 'A')) {
			clicked = clicked.parentNode;
		}

		// trigger custom behavior, if any
		if(typeof Yacs.tabs_args.onClick == 'function') {
			Yacs.tabs_args.onClick(clicked);
		}

		// display the target tab
		Yacs.tabsDisplay(clicked.id);

		// do not propagate event
		e.stopPropagation();
		return false;
	},

	/**
	 * change text size
	 *
	 * Use this function in links, to allow surfer to adjust text size.
	 *
	 * @param target container in page
	 * @param size increment or decrement
	 */
	textResize: function(handle, increment) {

		// get current size
		var current = Yacs.getCookie('TextSize');
		var currentSize = 2;
		if(current !== null) {
			eval("current = "+current);
			currentSize = current.size;
		}

		// change it
		currentSize += increment;
		if(currentSize < 0 ) { currentSize = 0; }
		if(currentSize > 6 ) { currentSize = 6; }

		// save it for 6 months = 6 * 30 = 180 days
		Yacs.setCookie('TextSize', '{ handle: "' + handle + '", size: ' + currentSize + ' }', 180);

		// actual style update
		Yacs.textSize();
	},

	/**
	 * enforce current text settings
	 *
	 * This is invoked each time a page is loaded
	 */
	textSize: function() {

		// use data from cookie
		var current = Yacs.getCookie('TextSize');
		if(current === null) {
			return;
		}
		eval('current = ' + current);

		// do nothing on standard size
		if(current == 2) {
			return;
		}

		// get actual style
		var allSizes = [ 'xx-small', 'x-small', 'small', 'medium', 'large', 'x-large', 'xx-large' ];
		var currentSize = allSizes[ current.size ];

		// resize the target container
		$(current.handle).css("fontsize", currentSize);

		// also resize poorly inherited items
		$(current.handle + ' div').each(function () {
		   $(this).css("fontSize", currentSize);
		});
		$(current.handle + ' td').each(function () {
		   $(this).css("fontSize", currentSize);
		});
		$(current.handle + ' tr').each(function () {
		   $(this).css("fontSize", currentSize);
    	});
	},

	/**
	 * toggle a box in an accordion
	 *
	 * @param the box
	 * @param string URL of the extending icon
	 * @param string URL of the collapsing icon
	 * @param string accordion id
	 */
	toggle_accordion: function(handle, down_href, up_href, accordion) {

		// the toggled panel
		var toggled = $(handle).next('.accordion_content');
		var processed = false;

                // refold each opened gusset in selected accordion
                $('.'+accordion).each(function(i,gusset) {
                    // gusset == this
                    var panel = $(gusset).children(".accordion_content");
                    // detect unfolded panel
                    if(panel.css("display") != 'none') {
                        // slide up panel
                        $(panel).slideUp({duration: 'slow', scaleContent:false});
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
						$(toggled).slideDown({duration: 'slow', scaleContent:false});
                        // change the image to fold visual
                        $(handle).find(".handle").attr('src', up_href);
                }

	},

	/**
	 * toggle a folded box
	 *
	 * @param the box
	 * @param string URL of the extending icon
	 * @param string URL of the collapsing icon
	 */
	toggle_folder: function(handle, down_href, up_href) {

		// the panel to slide
		var panel = $(handle).next('.folder_body');

		// display the panel if it is not visible
		if(panel.css("display") == 'none') {

			$(panel).slideDown({duration: 'slow', scaleContent:false});

			// change the image (if there is an image)
 			var icon = $(handle).children('img').first();
 			if(icon && up_href) {
 				icon.attr('src', up_href);
 			}

		// collapse the panel if it is visible
		} else {

			$(panel).slideUp({duration: 'slow', scaleContent:false});

			// change the image (if there is an image)
 			var icon = $(handle).children('img').first();

			if(icon && down_href) {
				icon.attr('src', down_href);
			}

		}

	},

	/**
	 * trim strings
	 *
	 * a fast implementation based on http://blog.stevenlevithan.com/archives/faster-trim-javascript
	 *
	 * @param string some text to trim
	 * @return string without leading and ending spaces
	 */
	trim: function(str) {
		var	str = str.replace(/^\s\s*/, ''),
			ws = /\s/,
			i = str.length;
		while(ws.test(str.charAt(--i)));
		return str.slice(0, i + 1);
	},

	/**
	 * update content asynchronously
	 *
	 * This function displays a nice spinning image while loading the page.
	 * Args can be a hash of parameters as specified for jQuery ajax settings.
	 *
	 * @link http://api.jquery.com/jQuery.ajax/
	 *
	 * @param string id of the target CSS container
	 * @param string web address to fetch new snippet
	 * @param mixed additional parameters to ajax call
	 *
	 */
	update: function(panel, address, args) {

		// the spinning image
		$('#'+panel).html('<img alt="*" src="' + Yacs.spinningImage.src + '" style="vertical-align:-3px" />');

		// go go go
		$.ajax($.extend({
			url: address,
			dataType: 'html',
			timeout: 30000,
			success: function(data) {
			  $('#'+panel).hide().html(data).show(400);
			},
			error: function(xhr, message) {
			  $('#'+panel).text(message);
			}}, args));

	},

	/**
	 * update a modal box
	 *
	 * This is called internally by Yacs.displayModalBox()
	 *
	 * @param string XHTML content of the box
	 */
	updateModalBox: function(content) {

		// update box content
		$('#modal_content').html(content);

		// adjust box size
		$('#modal_content').css({ width: 'auto', height: 'auto' });

		// center the box
		var yShift, xShift;
		yShift = Math.floor((($(window).height() - $('#modal_centered').outerHeight()) / 2) - $('#modal_centered').css('top').replace('px', ''));
		xShift = Math.floor((($(window).width() - $('#modal_centered').outerWidth()) / 2) - $('#modal_centered').css('left').replace('px', ''));

		// update box position
		if((Math.abs(yShift) > 1) || (Math.abs(xShift) > 1)) {
			$('#modal_centered').animate({top: '+=' + yShift, left: '+=' + xShift}, 0.2);
		}

	},

	/**
	 * set content only once
	 *
	 * This function is similar to Yacs.update(), except it does nothing if the target
	 * item already contains something.
	 *
	 * Use this function for example to populate tabbed panels of a complex page.
	 * Look at Yacs.tabs() above for a practical example of use
	 *
	 * @param string id of the target CSS container
	 * @param string web address to fetch new snippet
	 * @param mixed additional parameters to transmit to Ajax
	 *
	 */
	updateOnce: function(panel, address, args) {

		// do nothing if the panel contains something
		if(!$(panel).html() || ($(panel).html() === '') || ($(panel).html('<img alt="*" src="' + Yacs.spinningImage.src + '" style="vertical-align:-3px" />'))) {
			Yacs.update(panel, address, args);
		}

	},

	/**
	 * determine id of an element
	 *
	 * @param object the element to consider
	 * @return string
	 */
	identify: function(handle) {

		// use the 'id' attribute, if known
		if(handle.attr('id'))
			return handle.attr('id');

		// provide an anonymous id
		var i = 0;
		do {
			i++;
			var id = 'anonymous_' + i;
		} while(document.getElementById(id) != null);
		handle.attr('id', id);
		return id;
	}

};

// initialize yacs
$(document).ready(Yacs.onWindowLoad);

// this can be done right now

// pre-load the spinning image used during ajax updates
Yacs.spinningImage = new Image();
Yacs.spinningImage.src = url_to_root + 'skins/_reference/ajax/ajax_spinner.gif';

// pre-load the image used at the working overlay
Yacs.workingImage = new Image();
Yacs.workingImage.src = url_to_root + 'skins/_reference/ajax/ajax_working.gif';


