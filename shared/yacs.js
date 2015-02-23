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
    
    urlMoveFile	: 'files/move.php',
    urlSortFile	: 'files/sort.php',
	urlUploadFile	: 'files/upload.php',

	/**
	 * alert the surfer
	 *
	 * @param message to display
	 * @param function to be called on click, if any
	 */
	alert: function(message, callBack) {

		Yacs.displayModalBox({body: '<div style="text-align: left">'+message.replace(/\n/gi, '<br />')+'</div>',
			button_TRUE: "OK"}, callBack);

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

            // sanity check
            if ($('#'+target).length === 0)
                return;

            if(!source_url) {
                    source_url = url_to_root + 'users/complete.php';
            }

	    // use the multiple entries autocomplete exept if unique user required
	    if(unique) {
	    	if(callback) {
				$('#'+target).autocomplete({source:source_url, minLength:2, select: function(event, ui) {$('#'+target).val(ui.item.value);callback(ui.item.value);}});
	    	} else {
				$('#'+target).autocomplete({source:source_url, minLength:2});
			}
	    } else
			Yacs.autocomplete_m(target, source_url, callback);

		// override rendering of items in menu list to show full name and email
		$('#'+target).data( "ui-autocomplete" )._renderItem = function( ul, item ) {
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
         * Perform a ajax request to check nickname or email syntax
         * and availablility. Usefull for dynamic check in profil creation.
         *
         * @param object $input jquery handle to input field for email or nick_name
         * @returns {undefined}
         */
        checkNickEmail:function($input) {

           var options = new Object;
           options[$input.attr('name')] = $input.val();
           var url = url_to_root + 'users/check_unused.php';

           // ajax request
           $.get(url,options)
           .done(function(reply) {

                if(reply.can === false)
                    $input.addClass('input-bad').removeClass('input-good');
                else if(reply.can === true )
                    $input.addClass('input-good').removeClass('input-bad');
                
                
                // show message if any, in hint following the input
                if(reply.message) {
                    var $hint = $input.nextAll('.yc-form-hint').first();
                    // create hint if does not exist
                    if(!$hint.length) {
                        $hint = $('<span class="yc-form-hint"><span>');
                        $hint.insertAfter($input);
                    }
                    $hint.text(reply.message);
                } else {
                    // clean msg
                    $input.nextAll('.tiny').first().text('');
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
	    
		if(typeof Yacs.doNotCloseModal != 'undefined' && Yacs.doNotCloseModal === true) {
		    Yacs.doNotCloseModal = false;
		    return;
		}

		$('#modal_content').fadeTo(0.3, 0.3,
			function() {

				// clear the content
				$('#modal_content').html('');

				// mask the modal box
				$('#modal_panel').css('display', 'none');

				// stop react to window resizing
				// @see displayModalBox
				// @see updateModalBox
				$( window ).off('resize', Yacs.startResizeModal);

			} );
                        
                // fire event for someone
                $(document).trigger('modalbox-close');
	},

	/**
	 * ask for confirmation
	 *
	 * @param message to display
	 * @param callBack the function to call when the OK button is cliqued
	 */
	confirm: function(message, callBack) {

		Yacs.displayModalBox({body: '<div style="text-align: left">'+message.replace(/\n/gi, '<br />')+'</div>',
			button_TRUE: "OK", button_FALSE: "Cancel"}, callBack);

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
		//	<div id="modal_centered"><a class="boxClose">x</a>
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

                        $(objCentered).addClass('ui-front'); // reference for jquery-ui positionning

			$(objCentered).append(objContent);

			// small cross for closing modal box on right corner
			var objBoxClose = document.createElement("a");
			// $(objBoxClose).text('x');
			$(objBoxClose).attr('id',"modal_close");
			$(objBoxClose).click(function(e){
			    e.stopPropagation();
			    if(typeof Yacs.modalCallBack == 'function'){(Yacs.modalCallBack)(false);}
			    Yacs.closeModalBox();
			});
			$(objCentered).prepend(objBoxClose);
                        
                        // handle ESC key
                        $(document).keydown(function(e) {
                            if (e.keyCode == 27 && $("#modal_close").is(':visible')) {
                                  $("#modal_close").trigger('click');
                            }
                        });


			Yacs.modalOverlay = document.createElement("div");
			$(Yacs.modalOverlay).attr('id','modal_panel');

			$(Yacs.modalOverlay).append(objCentered);

			var objBody = document.getElementsByTagName("body").item(0);
			$(objBody).append(Yacs.modalOverlay);

			/*
			 * add functions to react to window resizing,
			 * by using timer to filter multiple events
			 * firing from browser.
			 * @see Yacs.updateModalBox() for event binding
			 * @see Yacs.closeModalBox() for event unbinding
			 */

			Yacs.resizeTimeout = false;

			Yacs.endResizeModal = function() {
			    if(new Date() - Yacs.resizeTime < 200 )
			       setTimeout(Yacs.endResizeModal, 200);
			    else {

				Yacs.resizeTimeout = false;

                                // memorize box size
                                Yacs.modalWidth     = $('#modal_content').width();
                                Yacs.modalHeight    = $('#modal_centered').height();

				// free the box size
				$('#modal_centered').css('bottom','');
                                $('#modal_centered').css('height','');
				$('#modal_content').css({width:'auto', height: 'auto'});
				// size the box
				Yacs.updateModalBox("sizing");
			    }
			}

			Yacs.startResizeModal = function() {

			    Yacs.resizeTime = new Date();
			    if (Yacs.resizeTimeout === false) {
				Yacs.resizeTimeout = true;
				setTimeout(Yacs.endResizeModal, 200);
			    }
			}


		// ensure containers are visible to compute box size
		// reset click event if any
		} else {
			$('#modal_panel').css('display', 'block').unbind( "click" );
		}

		// click at the back of the overlay close it,
		// exept if option confirmClose is setted
		if(!content.confirmClose) {
		    $('#modal_panel').click(function(e) {
                        if($(e.target).attr('id') !== 'modal_panel') return; // don't do anything if a child was click
			if(typeof Yacs.modalCallBack == 'function'){(Yacs.modalCallBack)(false);}
			Yacs.closeModalBox();
		    });
		}

		// show or hide closing cross
		if(content.withBoxClose)
		    $('#modal_close').show();
		else
		    $('#modal_close').hide();

                // flag for first display, used for sizing
                if(typeof Yacs.modalFirstDisplay == 'undefined') {
                    Yacs.modalFirstDisplay = true;
                }
                
                // fire event for someone
                $(document).trigger('modalbox-open');

		// paint or repaint box content
		$('#modal_content').fadeTo(0.1, 0.3,
			function() {

				// update the content
				Yacs.updateModalBox(boxContent);

			});

	},

	/**
	 * This function display a given yacs' page (view.php or edit.php)
	 * in a modalBox. Yacs php code will perform a special rendering
	 * when called by this function
	 *
	 * 1 get the page and put it in modal box
	 * 2 if array scripts_to_load exists, get the scripts defined in
	 * 3 if function execute_after_loading exists, call it
	 *
	 * @param string url, the page of the server to load
	 * @param boolean withButtons, to add a Send/cancel button
	 * @param boolean confirmClose, to avoid closing accidentaly the overlay,
	 * for example in the case of a form.
	 */
	displayOverlaid:function(url, withButtons, confirmClose) {
            
            
            // memorize param
            Yacs.recallOverlaid = {
                url : url,
                withButtons : withButtons,
                confirmClose : confirmClose
            }
            
            // clean previous validation
            validateDocumentPost = undefined;

	    // add overlaid=Y as parameter
	    if(url.indexOf('?') > -1)
		url += '&';
	    else
		url += '?';

	    url	    += 'overlaid=Y';
            
            Yacs.startWorking();

	    // start ajax request
	    $.get(url)
	    .done(function(data){
		        
                var content={
		    body: data
		};

		if(withButtons) {
		    content.button_TRUE	    = 'OK',
		    content.button_FALSE    = 'Cancel'
		}
		if(confirmClose) {
		    content.confirmClose    = true;
		} else
		    content.withBoxClose    = true;

		// preload instruction for tinymce
		// @see https://gist.github.com/badsyntax/379244
		window.tinyMCEPreInit = {
		    base: url_to_root+'included/tiny_mce',
		    suffix : '.min'	// to search for theme.min.js
		    };

		// function will be called by Yacs.updateModalBox
		Yacs.callAfterDisplayModal = function() {
		    if(typeof scripts_to_load != 'undefined') {
			// get all the scripts
			Yacs.getScriptS(scripts_to_load, function() {
			    // execute all snipets (like a $.ready(...)  )
			    if( typeof execute_after_loading == 'function')
				(execute_after_loading)();
			    else
				// continue with modal box sizing
				Yacs.updateModalBox("sizing");
			});
		    } else if( typeof execute_after_loading == 'function')
			(execute_after_loading)();
		    else
			// modal box sizing
			Yacs.updateModalBox("sizing");
		}

                Yacs.stopWorking();
		// display the modalBox
		Yacs.displayModalBox(content,Yacs.modalPost);
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
		if(begin == -1) {return null;}
		var end = document.cookie.indexOf(";", begin);
		if(end == -1) {end = document.cookie.length;}
		return unescape(document.cookie.substring(begin + prefix.length, end));

	},
        
        /**
         * get the extension of a filename
         * @returns string
         */
        getFileExt:function(filename){
            var ext = filename.toLowerCase();
            if(ext !== '') {
                var file_array = ext.split('.');
                ext = file_array[file_array.length-1]; // extension is at last part
            }
            
            return ext;
        },

	// Get function from string, with or without scopes (by Nicolas Gauthier)
	getFunctionFromString: function(string) {

	    var scope = window;
	    var scopeSplit = string.split('.');
	    for (i = 0; i < scopeSplit.length - 1; i++)
	    {
		scope = scope[scopeSplit[i]];

		if (scope == undefined) return;
	    }

	    return scope[scopeSplit[scopeSplit.length - 1]];
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
	 * This function allow to load a bunch of scripts
	 * @see http://www.jquery4u.com/ajax/getscript-mutiple-scripts/
	 *
	 * @param array resources, urls of scripts to load
	 * @param function callback, something to do after all scripts are loaded
	 */
	getScriptS: function( resources, callback ) {

	    var length = resources.length,
	    handler = function() {counter++;},
	    deferreds = [],
	    counter = 0,
	    idx = 0;

	    for ( ; idx < length; idx++ ) {

		// skip if script already present in the page
		if(Yacs.loadedJs.indexOf(resources[ idx ]) > -1 )
		    continue;

		deferreds.push(
		    $.getScript( resources[ idx ], handler )
		);
	    }

	    $.when.apply( null, deferreds ).then(function() {

		// memorize this loading
		Yacs.loadedJs = Yacs.loadedJs.concat(resources);

		callback && callback();
	    });
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
			Yacs.confirm(response.dialog_text, function(choice) {if(choice) {window.open(response.address);}});
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
				Yacs.confirm(response.dialog_text, function(choice) {if(choice) {window.open(response.address, 'yacs_tracker');}});
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
			Yacs.confirm(response.dialog_text, function(choice) {if(choice) {window.open(response.address);}});
		}
	},
        
        /**
         * Initialize submit button of comment forms
         * for a ajax query
         * 
         * @returns {undefined}
         */
        initAjaxComments : function() {
            
            $('.comment_form button[type=submit]').not('.ajax-post').click(function(e){

                e.preventDefault();
                
                //Yacs.closeModalBox();
                Yacs.startWorking();
                
                var $form = $(this).parents('.comment_form');
                
                // use FormData object for XMLHttpRequest level 2
                // (enable ajax file upload)
                var formData = new FormData($form[0]);
                // ajax post of the comment
                $.ajax({
                    url:$form.attr('action'),
                    type:$form.attr('method'),
                    data:formData,
                    cache: false,
                    contentType: false,
                    processData: false
                }).done(function(newComment){
                    
                    // clear input
                    $('.comment_form textarea').val('');
                    
                    // store received formated comment for other js
                    Yacs.newComment = newComment;
                    // fire a event
                    $('body').trigger('yc-newComment');
                    
                    Yacs.stopWorking();
                    // close modalbox if any
                    Yacs.closeModalBox();
                    
                });
                
                return false;
            });
            
            $('.comment_form button[type=submit]').addClass('ajax-post');
            
        },

	/**
	 * prepare for mouse hovering
	 *
	 * @param mixed id of the object to equip, or a reference to it
	 *
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

		handle.mouseout(function () {Yacs.mouseOut('#'+ Yacs.identify($(this)));return false;});
		handle.mouseover(function () {Yacs.mouseOver('#'+ Yacs.identify($(this)));return false;});
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

        /**
	 * called after page loading to perform session openning
	 * on other virtual hosts declared on the server
	 * perform a ajax request to get the list of virtual hosts,
	 * then a ajax request for each host.
	 *
	 * Job done is memorised in session storage to avoid extra
	 * request
	 */
	multiDomainLogin: function() {

	   if(sessionStorage.getItem('multilogin') == 'done')
	       return;

	   // send query to ask if crossDomainLogin is required
	   $.get( url_to_root + 'tools/check_multi_login.php')
	   .done(function(reply){

	       if(reply.login == true) {

		    // start an cross-domain ajax transaction
		    // @see https://developer.mozilla.org/en-US/docs/HTTP/Access_control_CORS
		    // @see tools/session.php
		    $.each(reply.domains, function(i,url){
			$.ajax({"url": url + "tools/session.php",
				"type": "GET",
				"data": {"id": reply.sessid,"origin": reply.origin},
				"xhrFields": {"withCredentials": true}

			});
		    });
	       } else
		   sessionStorage.setItem('multilogin','done');
	   });
	},

	tinymceInit: function() {

	    // without this tinymce won't initialize during overlaid view
	    // @see https://gist.github.com/badsyntax/379244
	    tinymce.dom.Event.domLoaded = true;

	    // regular initialization
	    // to choose components & configuration :
	    // @see http://www.tinymce.com/wiki.php/Configuration
	    // @see http://www.tinymce.com/wiki.php/Controls
	    // @see http://www.tinymce.com/wiki.php/configuration:formats
	    tinymce.init({
		    selector	: "textarea.tinymce",
		    menubar	:false,
		    width	: '90.5%',
		    resize	: false,
		    plugins	: "charmap, textcolor, fullscreen, code, link",
		    toolbar	: "undo redo | styleselect charmap styleselect| bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist outdent indent | forecolor backcolor | link | fullscreen code",
   style_formats: [
        {title: 'h2', block: 'h2'},
        {title: 'h3', block: 'h3'}
    ],
		    language	: surfer_lang
		});
	},

	toggleProperties: function(handle) {
        $(handle).children('.properties').toggle('slide');
	},


	/**
	 * general initialization
	 */
	onModalBoxLoad: function() {

		// show tips
		$("#modal_content").find('a[title].tip, input.tip, label.tip').each(function() {
			$(this).tipsy({title: 'title', gravity: $.fn.tipsy.autoNS, fade: true});
		});

		// close all tooltips on tabbing, etc
                // and resize modalbox
		$("body").bind("yacs", function(e) {
			$("#modal_content").find('a.tip,input.tip,textarea.tip, label.tip').each(function() {$(this).tipsy("hide");});
                        // activate tabbing flag for speficic sizing (no reduction)
                        Yacs.modalTabbing = true;
                        Yacs.startResizeModal();
		});

		// load the link in a scaled-down iframe
		$("#modal_content").find('a.tipsy_preview').each(function() {
			$(this).tipsy({fallback: '<div class="tipsy_thumbnail"><iframe class="tipsy_iframe" src="'+$(this).attr('href')+'" /></div>',
				html: true,
				gravity: $.fn.tipsy.autoWE,
				fade: true,
				offset: 8,
				opacity: 1.0});
		});

		// beautify links titles in menu bars
		$("#modal_content").find('.menu_bar a[title]').each(function() {
			$(this).tipsy({fallback: $(this).attr('title'), gravity: $.fn.tipsy.autoNS, fade: true});
		});

		// identify all elements that should be tiled
		$("#modal_content").find('.floating_box').addClass('tile');
		$("#modal_content").find('div.description .thumbnail_image').addClass('tile');

		// create groups of adjacent tiles
		var siblingsLast = null;
		$("#modal_content").find('.tile').each(function(){

			// head of a group of tiles
			if(!siblingsLast)
				siblingsLast = $(this).nextUntil(':not(.tile)').addBack().wrapAll('<div class="tiler" />').last();

			// tail of the group
			if( $(this).is(siblingsLast) )
				siblingsLast = null;
		});

		// do the tiling
		var $tiled = $("#modal_content").find('.tiler');
		$tiled.masonry({
		    itemSelector: '.tile'
		});

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
                $('body').delegate('button[type=submit]:not(.no_spin_on_click)','click', function(){Yacs.startWorking();});

		// show tips
		$('a[title].tip, input.tip, label.tip').each(function() {
			$(this).tipsy({title: 'title', gravity: $.fn.tipsy.autoNS, fade: true});
		});

		// close all tooltips on tabbing, etc
		$("body").bind("yacs", function(e) {
			$('a.tip,input.tip,textarea.tip, label.tip').each(function() {$(this).tipsy("hide");});
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
				siblingsLast = $(this).nextUntil(':not(.tile)').addBack().wrapAll('<div class="tiler" />').last();

			// tail of the group
			if( $(this).is(siblingsLast) )
				siblingsLast = null;
		});

		// do the tiling
		var $tiled = $('.tiler');

		$().ready(function(){
			$tiled.masonry({
				itemSelector: '.tile'
			});
		});

		// prepare for a nice slideshow
		Yacs.prepareSlideShow();

		// prepare edition link to ajax call of overlaid edition
                //$(".edit-overlaid").click(function(e){
                $('body').on('click',".edit-overlaid", function(e){ 
                    e.preventDefault();
                    Yacs.displayOverlaid($(this).attr("href"),true, true);
                    return false; // stop propagation
                });
                //$(".open-overlaid").click(function(e){
                $('body').on('click',".open-overlaid", function(e){ 
                    e.preventDefault();
                    Yacs.displayOverlaid($(this).attr("href"));
                    return false; // stop propagation
                });
                
                $('body').on('click',".submit-overlaid", function(e){ 
                    e.preventDefault();
                    Yacs.modalPost(true);
                    return false; // stop propagation
                });
		
		// prepare input to ajax-upload a file
                $('body').on('change','.yc-upload',function(){Yacs.prepareUpload($(this))});
                
		// slow down notifications on window blur
		$(window).blur(Yacs.looseFocus);

		// back to normal rate on focus
		$(window).focus(Yacs.getFocus);

		// store loaded scripts names in a list
		Yacs.loadedJs = new Array();
		var loaded_scripts = $('script[src]');
		$.each(loaded_scripts, function() {
		    Yacs.loadedJs.push($(this).attr('src'));
		});

		// check for virtual host automatic login
		Yacs.multiDomainLogin();

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
         * tool load a css
         * @returns {undefined}
         */
        loadCSS:function(url) {
            
            var file = url.split( "/" ).pop();
            
            var cssId = file.replace('.','');  
            if (!document.getElementById(cssId))
            {
                var head  = document.getElementsByTagName('head')[0];
                var link  = document.createElement('link');
                link.id   = cssId;
                link.rel  = 'stylesheet';
                link.type = 'text/css';
                link.href = url_to_root + url;
                link.media = 'all';
                head.appendChild(link);
            }
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
		if(this.options.top !== '') {openoptions+=",top="+this.options.top;}
		if(this.options.left !== '') {openoptions+=",left="+this.options.left;}

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
	 * initialize a input for ajax upload
	 */
	prepareUpload: function(input) {
	    
	    // get associated label
	    var $label	    = $('label[for='+input.attr('id')+']');
	    var activate    = input.val() !== ''  && !input.hasClass('input-bad'); 
	    
	    // make label visible or not
	    $label.toggle(activate);
	    
	    // click on label start upload
	    if(activate) {
		$label.click(function(e){
                    e.preventDefault();
                    e.stopPropagation();
                    Yacs.upload($(this).attr('for'))
                    return false;
                });
                    
	    } else
		$label.unbind('click');
            
            $label.trigger("click"); // autolaunch
	    
	},
        
        prepareSlideShow: function(selector) {
            
            if(typeof(selector) !== 'string' )
                selector = '.image_show';
            
            var anchors = $(selector);
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
				Yacs.modalCallPrevious = function() {Yacs.showImage(anchor.previousAnchor);};
                                button_PREVIOUS = '<<';
			} else {
				Yacs.modalCallPrevious = null;
                                button_PREVIOUS = null;
			}

			// a neighbour on the right
			if(anchor.nextAnchor) {
				Yacs.modalCallNext = function() {Yacs.showImage(anchor.nextAnchor);};

				// do not wait for user click to load the image
				var nextLoader = new Image();
				nextLoader.src = $(anchor.nextAnchor).attr('href');
                                
                                button_NEXT = '>>';

			} else {
				Yacs.modalCallNext = null;
                                button_NEXT = null;
			}

			// display the image on screen
			Yacs.displayModalBox({title: imageTitle,
				body: imageReference,
				button_PREVIOUS: button_PREVIOUS,
				button_TRUE: 'X',
				button_NEXT: button_NEXT}, Yacs.closeImage);

			//	clear onLoad, IE behaves erratically with animated gifs otherwise
			loader.onload = function(){};
		};

		// actual pre-load
		loader.src = $(anchor).attr('href');

	},
        
        closeImage: function() {
            
            if(typeof(Yacs.recallOverlaid) !== 'undefined') {
                
                Yacs.displayOverlaid(Yacs.recallOverlaid['url'],Yacs.recallOverlaid['withButtons'],Yacs.recallOverlaid['confirmClose']);
            }
        },

        /**
         * Post a form with ajax, and update the page with the respons
         * 
         * @param valid boolean, depending OK or CANCEL is pressed on the page
         */
	modalPost: function(valid) {

	    // action canceled
	    if(!valid) {
                // destroy uploaded files if any
		if(typeof Yacs.uploaded !== 'undefined')
                    Yacs.uploadDestroy("all");
                
                return;
            }

	    // get the form
	    var form = $("#main_form");
            // may be a comment form
            if (!form.length)
                form = $("#comment_form");
            
            if(!form.length) {
                console.log('cannot post cause did not find form');
                return;
            }

	    // call page validation if any
            if(typeof validateDocumentPost === 'function')
                valid = validateDocumentPost(form.get(0));

	    // not valid yet, but keep the modal box openned
	    if(!valid) {
		Yacs.doNotCloseModal = true;
		return;
	    }

	    // ask explicitly tinyMCE to save content
	    if( typeof tinyMCE != "undefined" )
		tinyMCE.triggerSave();

	    // trigger submission
	    //form.submit();
	    Yacs.startWorking();

	    /**
	     * For ajax post, we have to consider intermediate screens
	     * (like list of recipients alerted of the update)
	     */
	    // append a input to the form to tell this is a ajax post
	    $('<input type="hidden" name="overlaid" value="Y" />').appendTo(form);
            
            // use FormData object for XMLHttpRequest level 2
            // (enable ajax file upload)
            var formData = new FormData(form[0]);

	    // we are ok, send a ajax request
	    $.ajax({
		url:form.attr('action'),
		type:form.attr('method'),
		data:formData,
                cache: false,
                contentType: false,
                processData: false
	    }).done(function(html){
		var $html = $(html);

		// look if overlaid is required
		var overlaid = $html.find('.require-overlaid').length;
		// or look for elements to replace
		var $update = $html.find('.modal-post-update');

		// display result in overlaid view
		if(overlaid) {

		    var content={
			body: html,
                        withBoxClose : true,
                        confirmClose : false
		    };

		    Yacs.stopWorking();

		    Yacs.displayModalBox(content, null);

		// replace targeted element in page
		} else if($update.length) {

		    // update content, to targets
		    $.each($update, function() {
			target = $(this).data('update-tar');
			$(target).replaceWith($(this));
		    });

		   Yacs.stopWorking();

		// no update directives, reload everything
		} else
		    window.location.reload();
	    });

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
			$(Yacs.workingOverlay).css({display: 'block'});
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
		$(objCentered).css({position: 'absolute', top: '30%', left: '0%', height: '25%', width: '100%', textAlign: 'center', lineHeight: '0'});
		$(objCentered).append(objWorkingImage);

		Yacs.workingOverlay = document.createElement("div");
		$(Yacs.workingOverlay).attr('id','yacsWorkingOverlay');
		$(Yacs.workingOverlay).css({position: 'fixed', top: '0', left: '0', zIndex: '9000', width: '100%', height: '100%', minHeight: '100%', backgroundColor: '#e6e6ff', filter: 'alpha(opacity=50)', opacity: '0.2', display: 'block'});
		$(Yacs.workingOverlay).click(function() {$(Yacs.workingOverlay).css({display: 'none'});});
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
			$(handle).css({display: 'none'});
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
			if (!win) {return;}
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

                
                // tabs
                //$('body').on('click','.tabs_bar li',function(e){Yacs.tabsEvent(this, e);});
                $('.tabs_bar').not('.ready').find('li').click(function(e){Yacs.tabsEvent(this, e);});
                $('.tabs_bar').addClass('ready');
                
		// behavior of buttons for tabs used as step by step form, if any
		$(".tabs_panels .step").click(function() {

                    // call any validation step function
                    if($(this).hasClass('next') && typeof Yacs.tabsValidateStep == 'function') {
                        var valid = (Yacs.tabsValidateStep)(Yacs.tabs_current);
                        if(!valid)
                            return false;
                        else
                            // make upper next button visible
                            $('.panel-foreground').find('.next').css('visibility','visible');    
                    }

		    // display tab associate with button
		    Yacs.tabsDisplay($(this).data("target"));
		    // smooth scroll to title (begin of form)
		    $('.previous').scrollMinimal(true);
		});
                // if we have steps, that mean to hide validation button before surfer reached the last one
                if($(".tabs_panels .step").length)
                    $("#main_form .bottom").hide();
                
                // button for responsive menu
                $('.tabs-mini-toggle').click(function(){
                    $('.tab-background').toggle();
                });

                /**
                 * Get starting tab
                 * 1) from url
                 * 2) from script
                 * 3) from last tab in sessionStorage if we are not using step by step tab 
                 */  
                var startTab = null;
                if(window.location.hash.length > 1)
                    startTab = document.location.hash.substr(1,document.location.hash.length);
                else if(typeof Yacs.startTabs != 'undefined')
                    startTab = Yacs.startTabs;
                else if(!$('.yc-tab-steps').length)
                    startTab = Yacs.tabsLast();
		
                // where are we?
		if( startTab != null) {

			// are we already there?
			if(Yacs.tabs_current == startTab)
				return;

			// change to this tab
			for(id in tabs) {
				if(id == startTab) {
                                        // scroll to page title
                                        $().ready( function() {$('h1').first().scrollMinimal(true);});
                                        // display the tab
					Yacs.tabsDisplay(id);
					break;
				}
			}

			// wait until next change of hash
			Yacs.tabs_current = startTab;
		}

	},

	/**
	 * display given tab
	 */
	tabsDisplay: function(id) {

		// fade away all other tabs
		var newCurrent =id;
		var iterator;
		var panel;

                
                // tabs
                $('#'+id).siblings().removeClass('tab-foreground').addClass('tab-background');
                $('#'+id).removeClass('tab-background').addClass('tab-foreground');
                // get panels
                //var panels = $('#'+id).parents('.tabs_bar').next('.tabs_panels');
                // if no "panels" found get them other way (step by step form case)
                var panels = $('[data-tab="'+id+'"]').parent();

                // panels
                panels.find('.panel-foreground').not('[data-tab="'+id+'"]')
                        .fadeOut(.1)
                        .removeClass('panel-foreground')
                        .addClass('panel-background');
                iterator = panels.find('.panel-foreground').length;
                
                var newpanel = panels.find('.panel-background[data-tab="'+id+'"]');
                panel = newpanel.attr('id');
                newpanel.fadeIn(.1)
                        .removeClass('panel-background')
                        .addClass('panel-foreground');
                //if(newpanel.length)
                //    Yacs.updateOnce(panel,Yacs.tabs_list[newCurrent][1], Yacs.tabs_args);

		// activate the clicked tab -- see skins/_reference/ajax.css
		// panel = Yacs.tabs_list[newCurrent][0];

		// remember our state
		Yacs.tabs_current = id;
                
                // remember it for next visit to this page
                Yacs.tabsLast(id)

		// update the tab


		// load panel content, if necessary
                
                // set focus on first input
                $("#"+panel+" .yc-form-input input").first().not('.date-time-picker').focus();

                // make validation button visible on last tab displaying
                if( Yacs.tabs_current == iterator )
                    $("#main_form .bottom").show();

		// dispatch custom event (e.g., for tooltips, Google Maps, etc)
		$('body').trigger('yacs');
	},

	/**
	 * click on a tab
	 */
	tabsEvent: function(clicked, e) {
            
		// target the clicked tab
		//var clicked = this;

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
                e.preventDefault();
		e.stopPropagation();
		return false;
	},
        
        /**
         * record or retrieve last tab displayed for a given page
         * use sessionStorage.
         * if a parameter tabid is given, it is reccorded for the current page
         * elsewhere the function provide last tab for the current page if any
         * 
         * @param tabid qtring
         * @returns string or null
         */
        tabsLast : function(tabid) {
            // retrieve stored tabs or create var
            var memoTabs = sessionStorage.getItem('memoTabs');
            if( memoTabs != null ) 
                    memoTabs = JSON.parse(memoTabs);
            else
                    memoTabs = new Object;

            // get current page
            var page = '';
            if( typeof Yacs.current_overlaid_item != 'undefined' )
                    page = Yacs.current_overlaid_item + Yacs.current_overlaid_action;
            else
                    page = Yacs.current_item + Yacs.current_action;

            // with parameter : record a tab
            if(typeof tabid != 'undefined') {


                    if(page != '') {	
                            memoTabs[page] = tabid;
                            sessionStorage.setItem('memoTabs', JSON.stringify(memoTabs));
                    }
            }
            // without parameter : get a tab
            else {
                    if(page != '') {
                            var tab = memoTabs[page];
                            return tab
                    }
            }
            return null
        },
        
        /**
         * when using tabs for a step by step form
         * and custom tabsValidateStep() function,
         * this function help you to test all tabs.
         * Usefull if for example your form is later
         * not displayed step by step but with standard tabs.
         * 
         * the function stops on the first error and display
         * the tab with the error;
         * 
         * @returns boolean
         */
        tabsValidateAll: function() {
            // we suppose everything is ok
            var result = true;

            // sanity check
            if(typeof Yacs.tabsValidateStep !== 'function') return result;

            // get all tabs
            var $tabs = $('.panel-foreground, .panel-background');

            // fetch tabs
            $.each($tabs, function(){

                var tab_id = $(this).attr('id');
                tab_id = "_" + tab_id.substr(0, tab_id.indexOf('_panel'));
                // test the tab
                if(Yacs.tabsValidateStep(tab_id))
                    return; 
                else {
                    // failed, display the tab
                    Yacs.stopWorking();
                    Yacs.tabsDisplay(tab_id);
                    result = false;
                    return false; // break the "each" loop
                }
            });

            return result;
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
		if(currentSize < 0 ) {currentSize = 0;}
		if(currentSize > 6 ) {currentSize = 6;}

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
                // sanity check
                if(typeof str === 'undefined' || str === '')
                    return '';
            
                str = str.replace(/^\s\s*/, ''),
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
	 * we have a job in several steps :
	 * 1. update the content ;
	 * 2. wait for the images to be loaded ;
	 * 3. load and excecute js bound with the content, if any ;
	 * 4. size the modal box to fit the content.
	 *
	 * steps are done by callbacks on this function with different parameters
	 * 1: content = XHTML
	 * 3: content = "execute", and callAfterDisplayModal function is defined
	 * 4: content = "sizing" or no js to load in previous step
	 *
	 * @param mixed XHTML content of the box,
	 * or flag false to load js if any, flag true to resize
	 */
	updateModalBox: function(content) {

		// first, update box content
		if(content !== "execute" && content !== "sizing" && content !== true) {
		    // actual content update
		    $('#modal_content').html(content);
		    $('#modal_content').animate({scrollTop: 0});

		    // remove height limitation if it was set
		    $('#modal_centered').css('bottom','');
                    $('#modal_centered').css('height','');
		    // free the box size
		    $('#modal_content').css({width:'auto', height: 'auto'});

		    // suscribe to resize event
		    // @see displayModalBox
		    // @see closeModalBox
		    $( window ).on('resize', Yacs.startResizeModal);

		    // recall itself when images are loaded, with "false" parameter
		    imagesLoaded('#modal_content', function(){Yacs.updateModalBox("execute");});
		    return;
		}

                if(content === "execute") {
                    // general js initialization
                    Yacs.onModalBoxLoad();

                    // callback after displaying, if defined
                    // for example to load javascript files
                    // @see Yacs.displayOverlaid()
                    if(typeof Yacs.callAfterDisplayModal == 'function') {
                        // this function should recall updateModalBox with "sizing" parameter
                        (Yacs.callAfterDisplayModal)();
                        return;
                    }
                }

		/*
		 * images add additionnal loadings (js) done, do the sizing
		 */

		// use variable to avoid multiple scan of the page by jquery
		var $modal_centered = $('#modal_centered');
		// get top and left of box'div
		var pos_modal = $modal_centered.position();

		// adjust box size to needed width, but max is 90% of window width
                // and do not reduce with on tabbing
		var modal_width = $modal_centered.width();
                var modal_height = $modal_centered.height();

		var max_width   = $(window).width()*.9;
                var min_width   = 0;
                var min_height  = 0;
                if(Yacs.modalTabbing === true) {
                    min_width = Yacs.modalWidth;
                    min_height = Yacs.modalHeight;
                    // reset flag
                    Yacs.modalTabbing = false;
                }
		if(modal_width > max_width)
		    modal_width = max_width;
                if(modal_width < min_width)
                    modal_width = min_width;

                if(modal_height < min_height)
                    $modal_centered.height(min_height);

		$('#modal_content').css({width: modal_width + 'px'});

		// center the box, depending of its height compared to window'height
		if($modal_centered.outerHeight() < $(window).height()) {

		    // center the box
		    var yShift, xShift;
		    yShift = Math.floor((($(window).height() - $modal_centered.outerHeight()) / 2) - pos_modal.top);
		    xShift = Math.floor((($(window).width() - $modal_centered.outerWidth()) / 2) - pos_modal.left);

		    // update box position
		    if((Math.abs(yShift) > 1) || (Math.abs(xShift) > 1)) {
			    $modal_centered.animate({top: '+=' + yShift, left: '+=' + xShift}, 0.2);
		    }
		    // lock the bottom
		    var height_interval = Math.floor(($(window).height() - $modal_centered.outerHeight()) /2 );
		    $modal_centered.css({bottom: height_interval+'px'});

		} else {

		    // center horizontaly
		    var xShift;
		    xShift = Math.floor((($(window).width() - $modal_centered.outerWidth()) / 2) - pos_modal.left);
		    if(Math.abs(xShift) < 1) xShift = 0;
		    // update position and fit the box at top and bottom
		    $modal_centered.animate({top:'5%',bottom:'5%',left: '+=' + xShift}, 0.2);
		}

		// lock modal_content height, display the updated box
		$('#modal_content').css({height: '100%', visibility:'visible'}).fadeTo(0.3, 1.0);

                // recall sizing on first load of modal box
                // because sometimes some loading are late.
                // and content does'nt have the proper width
                if(Yacs.modalFirstDisplay) {
                    Yacs.modalFirstDisplay = false;
                    setTimeout(Yacs.startResizeModal,200);
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
         *
         *
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
	 * Start an ajax upload of a file
	 */
	upload: function(id) {
	    
	    
	    // create a progress bar
	    var $progress = $('<div class="yc-upload-progress-outer"></div>');
	    $progress.append('<div class="yc-upload-progress" id="'+id+'_progress"></div>');
            // cancel button
            var $cancel = $('<a class="yc-upload-cancel">x</a>');
            $progress.append($cancel);
	    
	    $('label[for='+id+']').replaceWith($progress);
	    $('#'+id).hide();
	    // get name of file field
	    var name = $('#'+id).attr('name');
	    
	    
	    // send file
	    var _file = document.getElementById(id);
	    
	    // sanity check
	    if(_file.files.length === 0){
		return;
	    }
	    
	    var data = new FormData();
	    data.append('name',name);
	    data.append(name, _file.files[0]);

	    var request = new XMLHttpRequest();
	    // reply from server
	    request.onreadystatechange = function(){
		if(request.readyState == 4){
		    try {
			var resp = JSON.parse(request.response);
		    } catch (e){
			var resp = {
			    status: 'error',
			    data: 'Unknown error occurred: [' + request.responseText + ']'
			};
		    }
		    console.log(resp.status + ': ' + resp.data);
                    // remove file selector but not if upload was aborded
                    if($('#'+id+'_progress').length) {
                        $('#'+id).remove();
                        
                        if(typeof Yacs.uploaded == 'undefined')
                            Yacs.uploaded = {};
                        // remember this upload
                        Yacs.uploaded[id] = true;
                    }
                    
                    
                    
                    // show preview
                    if(typeof resp.preview !== 'undefined') {
                        $('#'+id+'_progress').parent().replaceWith(resp.preview);
                    }
                    
                    if(typeof resp.js !== 'undefined') {
                        $('body').append(resp.js);
                        if(typeof scripts_to_load !== 'undefined') {
                            // get all the scripts
                            Yacs.getScriptS(scripts_to_load, function() {
                                // execute all snipets (like a $.ready(...)  )
                                if( typeof execute_after_loading === 'function')
                                    (execute_after_loading)();
                            });
                        } else if( typeof execute_after_loading === 'function') {
                            (execute_after_loading)();
                        }
                                
                    }
		}
	    };
	    // animate progress bar
	    request.upload.addEventListener('progress', function(e){
		$('#'+id+'_progress').width(Math.ceil((e.loaded/e.total) * 100) + '%');
	    }, false);
            
            // cancel
            $('#'+id+'_progress').next('.yc-upload-cancel').click(function(){
                // reshow file selector
                $(this).parent().remove();
                $('#'+id).show().val('');
                // aborting
                request.abort();
            });
	    
	    // send data
	    request.open('POST', url_to_root+ Yacs.urlUploadFile);
	    request.send(data);
	    
	},
        
        /**
         * destroy a file just uploaded thru ajax
         * 
         * @param {string} name of the file
         * @returns {undefined}
         */
        uploadDestroy: function(name, $destroylink) {
            
            $.post(url_to_root + Yacs.urlUploadFile,{'name':name, 'action':'destroy'})
             .done(function(reply){
                 console.log(reply.data);
                 if(typeof $destroylink !== "undefined" && reply.preview !== "undefined") {
                     $destroylink.parents(".yc-preview").replaceWith(reply.preview);
                 }
                 
                 delete Yacs.uploaded[name];
                 if(name==='all') delete Yacs.uploaded;
             });
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

// specific jquery extensions
jQuery.fn.extend({

    /**
     * This function enable to bind single click and dblclick on same objects.
     * The problem without it is that .click() is taking priority over dblclick()
     * This function works with a small timer to wait for second click
     * @see http://stackoverflow.com/questions/6330431/jquery-bind-double-click-and-single-click-separately
     *
     * @param callback function what to do on single click
     * @param callback function what to do on double click
     *
     * callbacks are receiving as parameters the
     * jquery object clicked
     */
    click_n_dblclick: function(call_on_click, call_on_dblclick) {

	// DELAY is max time between to clicks of a double one
	var DELAY = 700, clicks = 0, timer = null;

	// return for all jquery objects selected by $()
	return this.each(function() {
	    var obj = $(this);
	    obj.on('click',function(e) {
		clicks++;  //count clicks

		// prevent default action on click (links, etc..)
		e.preventDefault();
		// prevent also propagation to nested element
		e.stopPropagation();

		if(clicks === 1) {

		    timer = setTimeout(function() {

			call_on_click(obj);     //perform single-click action
			clicks = 0;		//after action performed, reset counter

		    }, DELAY);

		} else {

		    clearTimeout(timer);	//prevent single-click action
		    call_on_dblclick(obj);	//perform double-click action
		    clicks = 0;			//after action performed, reset counter
		}

	   }).on("dblclick", function(e){
		e.preventDefault();		//cancel system double-click event
	   });
	});
    },

    /**
     * scroll to a object to make sure it is visible
     * @see http://stackoverflow.com/questions/4217962/scroll-to-an-element-using-jquery
     *
     * @param boolean smooth to have a slow scroll;
     */
    scrollMinimal:function(smooth) {

	// behavior changes if overlaid view is active
	if(!$('#modal_centered').length || $('#modal_centered').is('hidden')) {
	    var cTop = this.offset().top;
	    var wind = $('html, body');
	    var windowTop = $(window).scrollTop();
	    var visibleHeight = $(window).height();
	} else {
            // overlaid view
	    var cTop = this.position().top;
	    var wind = $('#modal_content');
	    var windowTop = wind.scrollTop();
	    var visibleHeight = wind.height();
	}

	var cHeight = this.outerHeight(true);

	if (cTop < windowTop) {
	    if (smooth)
		wind.animate({'scrollTop': cTop}, 'slow', 'swing');
	    else
		$(window).scrollTop(cTop);

	} else if (cTop + cHeight > windowTop + visibleHeight) {
	    if (smooth)
		wind.animate({'scrollTop': cTop - visibleHeight + cHeight}, 'slow', 'swing');
	    else
		$(window).scrollTop(cTop - visibleHeight + cHeight);

	}
    }
    
}); //end jQuery extends

/** 
 * delay tools
 * use it to call a function after
 * a certain time of inactivity.
 * Timer is reset each time function is recalled
 * Usefull to cooldown keyup event for example.
 * 
 * Usage :
 * $('input').keyup(function() {
 *  delay(function(){
 *    alert('Time elapsed!');
 *  }, 1000 );
 * });
*/
var delay = (function(){
  var timer = 0;
  return function(callback, ms){
    clearTimeout (timer);
    timer = setTimeout(callback, ms);
  };
})();


// initialize yacs
$(document).ready(Yacs.onWindowLoad);

// this can be done right now

// pre-load the spinning image used during ajax updates
Yacs.spinningImage = new Image();
Yacs.spinningImage.src = url_to_root + 'skins/_reference/ajax/ajax_spinner.gif';

// pre-load the image used at the working overlay
Yacs.workingImage = new Image();
Yacs.workingImage.src = url_to_root + 'skins/_reference/ajax/ajax_working.gif';

///// RESPONSIVE TABS
// subscibe to tabs event for behavior when minified
$('body').on('yacs', function(){
    if($('.tabs-mini-toggle').is(':visible'))
        $('.tab-background').hide();
});

// redisplay tabs in case of window resizing
$( window ).resize(function() {
    delay(function(){
        if($('#tabs_bar').length && !$('.tabs-mini-toggle').is(':visible')) { 
               $('#tabs_bar li').css('display',''); // remove inline display directive
        }
   }, 200 );
});

