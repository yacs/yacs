/**
 * the YACS AJAX shared library.
 *
 * @link http://andykdocs.de/andykdocs/document/Migrating-from-the-Prototype-JavaScript-Framework-to-jQuery/Prototype-JS-to-jQuery-Migration-Cheat-Sheet-V1-April-2010.html
 *
 * This file extends prototype, etc., to enhance interactions with the end-user
 *
 * @author Bernard Paques
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
			Element.removeClassName(rowEl, 'odd');
			if((i % 2) !== 0) {
				Element.addClassName(rowEl, 'odd');
			}

			// set style classes on each column
			for (j = 2; j < tblEl.rows[i].cells.length; j++) {
				cellEl = rowEl.cells[j];
				Element.removeClassName(cellEl, 'sorted');
				// highlight the one that was sorted
				if (j == col) {
					Element.addClassName(cellEl, 'sorted');
				}
			}
		}

		// find the table header and highlight the column that was sorted
		var el = tblEl.parentNode.tHead;
		rowEl = el.rows[el.rows.length - 1];

		// set style classes for each column
		for (i = 0; i < rowEl.cells.length; i++) {
			cellEl = rowEl.cells[i];
			Element.removeClassName(cellEl, 'sorted');

			// highlight the header of the sorted column
			if (i == col) {
				Element.addClassName(cellEl, 'sorted');
			}
		}
	},

	/**
	 * remote procedure call based on JSON-RPC
	 *
	 */
	call: function(parameters, callBack) {

		// hash encoding to JSON
		parameters = Object.toJSON(parameters);

		// start an ajax transaction
		var handle = new Ajax.Request(url_to_root + 'services/json_rpc.php', {
			method: 'post',
			parameters: parameters,
			requestHeaders: {Accept: 'application/json'},
			onSuccess: function(transport) {
				var response = transport.responseText.evalJSON(true);
				if(typeof callBack == 'function') {
					if(response.error) {
						callBack(response.error);
					} else if(response.result) {
						callBack(response.result);
					} else {
						callBack(1);
					}
				}
				response = null; // no memory leak
			},
			onFailure: function(transport) {
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

		var handle = new Effect.Opacity('modal_content', {duration:0.3, from:1.0, to:0.3, queue: 'end',
			afterFinish: function(target) {

				// clear the content
				Element.update('modal_content', '');

				// mask the modal box
				Element.setStyle('modal_panel', { display: 'none' });

			} });
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
			objContent.setAttribute('id','modal_content');
			Element.update(objContent, '<img src="'+Yacs.spinningImage.src+'" />');

			var objCentered = document.createElement("div");
			objCentered.setAttribute('id','modal_centered');
//			objCentered.setStyle({ visibility: 'hidden' });
			objCentered.appendChild(objContent);

			Yacs.modalOverlay = document.createElement("div");
			Yacs.modalOverlay.setAttribute('id','modal_panel');
			Yacs.modalOverlay.onclick = function() {};	// you have to click in the box to close the modal box
			Yacs.modalOverlay.appendChild(objCentered);

			var objBody = document.getElementsByTagName("body").item(0);
			objBody.appendChild(Yacs.modalOverlay);

			// just to fix a bug on first image rendering for Internet Explorer 7...
			var fix = new Effect.MoveBy('modal_centered', 0, 0, {duration: 0.0});

		// ensure containers are visible to compute box size
		} else {
			Element.setStyle('modal_panel', { display: 'block' });
		}

		// paint or repaint box content
		var handle = new Effect.Opacity('modal_content', {duration:0.1, from:1.0, to:0.3, queue: 'end',
			afterFinish: function(target) {

				// update the content
				Yacs.updateModalBox(boxContent);

				// display the updated box
				Element.setStyle('modal_content', { visibility: 'visible' });
				var opacity = new Effect.Opacity('modal_content', {duration:0.3, from:0.5, to:1.0, queue: 'end'});

			} });



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
		Element.setStyle(handle, {height: '10em'});
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
	 */
	addOnDemandTools: function(handle, option) {
		handle = $(handle);

		var prefix = '';
		if(handle.hasClassName('sortable')) {
			prefix += '<span class="onHoverLeft drag_handle"><img src="'+url_to_root+'skins/_reference/ajax/on_demand_handle.png" width="16" height="16" alt="Drag" /></span>';
		}
		var suffix = '<span class="onHoverRight">';
		if(handle.hasClassName('mutable')) {
			suffix += '<a href="#" onclick="Yacs.toggleProperties(\''+handle.identify()+'\'); return false;"><img src="'+url_to_root+'skins/_reference/ajax/on_demand_properties.png" width="16" height="16" alt="Properties" /></a>';
		}
		suffix += '<a href="#" onclick="Element.remove(\''+handle.identify()+'\'); return false;"><img src="'+url_to_root+'skins/_reference/ajax/on_demand_delete.png" width="16" height="16" alt="Delete" /></a></span>';
		var here = new Element.insert(handle, { top: suffix + prefix });

		handle.onmouseout = function () { Yacs.mouseOut(this); return false; };
		handle.onmouseover = function () { Yacs.mouseOver(this); return false; };

		handle = null; // no memory leak

	},

	/**
	 * mouse is moving elsewhere
	 */
	mouseOut: function(handle) {
		var nodes = handle.select('span.onHoverLeft', 'span.onHoverRight');
		nodes.each(function (node) { Element.setStyle(node, { visibility: 'hidden' }); });

		nodes = null; // no memory leak
	},

	/**
	 * mouse is coming on top of some element
	 */
	mouseOver: function(handle) {
		var nodes = handle.select('span.onHoverLeft', 'span.onHoverRight');
		nodes.each(function (node) { Element.setStyle(node, { visibility: 'visible' }); });

		nodes = null; // no memory leak
	},

	toggleProperties: function(handle) {
		var nodes = $(handle).select('.properties');
		nodes.each(function (node) { var handle = new Effect.toggle(node, 'slide'); });

		nodes = null; // no memory leak
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

		// pre-load the spinning image used during ajax updates
		Yacs.spinningImage = new Image();
		Yacs.spinningImage.src = url_to_root + 'skins/_reference/ajax/ajax_spinner.gif';

		// pre-load the image used at the working overlay
		Yacs.workingImage = new Image();
		Yacs.workingImage.src = url_to_root + 'skins/_reference/ajax/ajax_working.gif';

		// change the behavior of buttons used for data submission, except those with style 'no_spin_on_click'
		var buttons = $$('button');
		for(var index = 0; index < buttons.length; index++) {
			var button = buttons[index];
			var buttonType = String(button.getAttribute('type'));
			if(buttonType.toLowerCase().match('submit') && !Element.hasClassName(button, 'no_spin_on_click')) {
				button.onclick = Yacs.startWorking;
			}

			button = null; // no memory leak
		}

		// on-demand headers
		var nodes = $$('.onDemandTools');
		for(index = 0; index < nodes.length; index++) {
			var node = nodes[index];
			Yacs.addOnDemandTools(node, { });
		}

		// prepare for a nice slideshow
		var anchors = $$('a.image_show');
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
		Event.observe(window, 'blur', Yacs.looseFocus);

		// back to normal rate on focus
		Event.observe(window, 'focus', Yacs.getFocus);

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
		Object.extend(this.options, options || {});

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
			if((loader.width > 1) && (loader.width + 30 > document.viewport.getWidth())) {
				scale = (document.viewport.getWidth() - 30) / loader.width;
				loader.height*= scale;
				loader.width *= scale;
			}
			if((loader.height > 1) && (loader.height + 110 > document.viewport.getHeight())) { // take title and buttons into account
				scale = (document.viewport.getHeight() - 110) / loader.height;
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
			if((yDelta !== 0) && (xDelta !== 0) && $('modal_image_panel')) {

				// previous image -- <div id="modal_image_panel"><img ...
				var previousImage = $('modal_image_panel').down();

				// current height and width
				var currentHeight = Element.getHeight(previousImage);
				var currentWidth = Element.getWidth(previousImage);

				// compute scaling factors
				var yScale = ((currentHeight + yDelta) / currentHeight) * 100;
				var xScale = ((currentWidth + xDelta) / currentWidth) * 100;

				// scaling previous image makes ugly things
				var opacity = new Effect.Opacity('modal_content', {duration:0.1, from:1.0, to:0.0});
				Element.setStyle('modal_content', { visibility: 'hidden' });

				// adjust the overall size
				if(yDelta !== 0) {
					var effect = new Effect.Scale(previousImage, yScale, {scaleX: false, duration: 0.4, queue: 'end'});
				}
				if(xDelta !== 0) {
					var effect2 = new Effect.Scale(previousImage, xScale, {scaleY: false, duration: 0.4, queue: 'end'});
				}
			}

			// image title -- <a><span><img title="" ...
			var imageTitle = Element.down(anchor, 'img').getAttribute('title');

			// image href
			var imageReference = '<div id="modal_image_panel"><img src="'+anchor.getAttribute('href')+'" width="'+loader.width+'" height="'+loader.height+'" /></div>';

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
				nextLoader.src = anchor.nextAnchor.getAttribute('href');

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
		loader.src = anchor.getAttribute('href');

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
		var container = Element.up(handle);
		Element.setStyle(container, {position: 'relative'});

		// the panel to slide
		var panel = Element.next(handle, '.panel');

		// align the sliding panel
		if((onLeft !== true) && (onLeft !== false)) {
			onLeft = (Element.getWidth(panel) > (Element.cumulativeOffset(container).left + Element.getWidth(handle)));
		}

		if((down !== true) && (down !== false)) {
			down = true;
		}

		// do the alignment
		if(onLeft && down) {
			Element.setStyle(panel, {position: 'absolute', top: container.getHeight() + 'px', left: 0});

		}
		if(onLeft && !down) {
			Element.setStyle(panel, {position: 'absolute', bottom: container.getHeight() + 'px', left: 0});

		}
		if(!onLeft && down) {
			Element.setStyle(panel, {position: 'absolute', top: container.getHeight() + 'px', right: 0});
		}
		if(!onLeft && !down) {
			Element.setStyle(panel, {position: 'absolute', bottom: container.getHeight() + 'px', right: 0});
		}

		// display the panel if it is not visible
		if(panel.style.display == 'none') {

			new Effect.SlideDown(panel, {duration:.3, scaleContent:false});

			// change the image (if there is an image)
			var icon = Element.next(Element.down(handle, 'span'), 'img');
			if(icon && up_href) {
				icon.src = up_href;
			}

		// collapse the panel if it is visible
		} else {

			new Effect.SlideUp(panel, {duration:.3, scaleContent:false});

			// change the image (if there is an image)
			var icon = Element.next(Element.down(handle, 'span'), 'img');
			if(icon && down_href) {
				icon.src = down_href;
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

		Element.update(panel, '<img alt="*" src="' + Yacs.spinningImage.src + '" style="vertical-align:-3px" />');

	},

	/**
	 * load some opaque overlay during back-end processing
	 */
	startWorking: function() {

		if(Yacs.workingOverlay) {
			Element.setStyle(Yacs.workingOverlay, { display: 'block' });
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
		objWorkingImage.setAttribute('src', url_to_root + 'skins/_reference/ajax/ajax_working.gif');

		var objCentered = document.createElement("div");
		Element.setStyle(objCentered, { position: 'absolute', top: '30%', left: '0%', height: '25%', width: '100%', textAlign: 'center', lineHeight: '0' });
		objCentered.appendChild(objWorkingImage);

		Yacs.workingOverlay = document.createElement("div");
		Yacs.workingOverlay.setAttribute('id','yacsWorkingOverlay');
		Element.setStyle(Yacs.workingOverlay, { position: 'fixed', top: '0', left: '0', zIndex: '1000', width: '100%', height: '100%', minHeight: '100%', backgroundColor: '#000', filter: 'alpha(opacity=20)', opacity: '0.2', display: 'block' });
		Yacs.workingOverlay.onclick = function() { Element.setStyle(Yacs.workingOverlay, { display: 'none' });};
		Yacs.workingOverlay.appendChild(objCentered);

		var objBody = document.getElementsByTagName("body").item(0);
		objBody.appendChild(Yacs.workingOverlay);

		return true;
	},

	/**
	 * hide the working overlay
	 */
	stopWorking: function() {

		var handle = $('yacsWorkingOverlay');
		if(handle) {
			Element.setStyle(handle, { display: 'none' });
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
		Yacs.subscribeAjax = new Ajax.Request(url_to_root + 'users/heartbit.php', {
			method: 'get',
			parameters: { },
			requestHeaders: {Accept: 'application/json'},
			onSuccess: Yacs.subscribeSuccess,
			onFailure: Yacs.subscribeFailure
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

	subscribeSuccess: function(transport) {

		// dispatch received notification
		var response = transport.responseText.evalJSON(true);
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

		// minimum time between two successive notifications
		Yacs.subscribeTimer = setTimeout(Yacs.subscribe, 20000);
		Yacs.subscribeAjax = null;

	},

	// remember polling rate
	subscribeRate: "fast",

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
		var oldDsply = handle.style.display;
		handle.style.display = "none";

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
		handle.style.display = oldDsply;

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
		logLine.appendChild(Yacs.window_.document.createTextNode('=> ' + message));
		Yacs.window_.document.body.appendChild(logLine);

	},

	/**
	 * This handler adds code to tabbing items, to process further clicks.
	 *
	 * It has to be called once, after the construction of proper DOM elements.
	 * You can either build the DOM manually, then place a call to this function,
	 * or, alternatively, just call Skin::build_tabs() from within you PHP code
	 * to have everything done automatically.
	 *
	 * @param tabs A list of tabs related to panels and URLs
	 * @param args This corresponds to the Ajax options supported in prototype.js
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
				Event.observe($(id), 'click', Yacs.tabsEvent);

				// we are on first tab
				if(!Yacs.tabs_current) {
					Yacs.tabs_current = id;
				}
			}
		}

		// move to the right tab
//		new PeriodicalExecuter(function(pe) {

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

//		}, 0.5);


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
			if(id == $(iterator).id) {
				newCurrent = iterator;

			} else {

				// update the tab
				$(iterator).className = 'tab-background';

				// update the panel
				if($(panel).style.display != 'none') {
					new Effect.Fade(panel, {duration:.1, scaleContent:false});
				}
				$(panel).className = 'panel-background';
			}
		}

		// activate the clicked tab -- see skins/_reference/ajax.css
		panel = Yacs.tabs_list[newCurrent][0];

		// remember our state
		window.location.hash = id;
		Yacs.tabs_current = id;

		// update the tab
		$(newCurrent).className = 'tab-foreground';

		// update the panel
		if($(panel).style.display == 'none') {
			new Effect.Appear(panel, {duration:.1, scaleContent:false});
		}
		$(panel).className = 'panel-foreground';

		// load panel content, if necessary
		if(Yacs.tabs_list[newCurrent].length > 1) {
			Yacs.updateOnce(panel, Yacs.tabs_list[newCurrent][1], Yacs.tabs_args);
		}

	},

	/**
	 * click on a tab
	 */
	tabsEvent: function(e) {

		// target the clicked tab
		var clicked = Event.element(e);

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
		Event.stop(e);
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
		$(current.handle).style.fontsize = currentSize;

		// also resize poorly inherited items
		allTags = $(current.handle).getElementsByTagName('div');
		for(var index = 0; index < allTags.length; index++ ) { allTags[index].style.fontSize = currentSize; }
		allTags = $(current.handle).getElementsByTagName('td');
		for(index = 0; index < allTags.length; index++ ) { allTags[index].style.fontSize = currentSize; }
		allTags = $(current.handle).getElementsByTagName('tr');
		for(index = 0; index < allTags.length; index++ ) { allTags[index].style.fontSize = currentSize; }
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
		var toggled = Element.next(handle, '.accordion_content');
		var processed = false;

		// close all boxes in the accordion
 		$$('.'+accordion).each( function(item) {

 			var panel = Element.down(item, '.accordion_content');
			if(panel.style.display != 'none') {
				new Effect.SlideUp(panel, {duration:.3, scaleContent:false});

				// change the image (if there is an image)
				var icon = Element.down(item, 'a').down('img');
				if(icon && down_href) {
					icon.src = down_href;
				}

				// clicked box has been closed
				if(toggled == panel) {
					processed = true;
				}

			}

 		} );

		// only extend closed elements that have not been processed during this click
		if((toggled.style.display == 'none') && !processed) {
			new Effect.SlideDown(toggled, {duration:.3, scaleContent:false});

			// change the image (if there is an image)
			var icon = Element.down(handle, 'img');
			if(icon && up_href) {
				icon.src = up_href;
			}
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
		var panel = Element.next(handle, '.folder_body');

		// display the panel if it is not visible
		if(panel.style.display == 'none') {

			new Effect.SlideDown(panel, {duration:.3, scaleContent:false});

			// change the image (if there is an image)
 			var icon = Element.down(handle, 'img');
 			if(icon && up_href) {
 				icon.src = up_href;
 			}

		// collapse the panel if it is visible
		} else {

			new Effect.SlideUp(panel, {duration:.3, scaleContent:false});

			// change the image (if there is an image)
			var icon = Element.down(handle, 'img');
			if(icon && down_href) {
				icon.src = down_href;
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
	 *
	 * @param string id of the target CSS container
	 * @param string web address to fetch new snippet
	 * @param mixed additional parameters to transmit to Ajax
	 *
	 */
	update: function(panel, address, args) {

		// the spinning image
		Element.update(panel, '<img alt="*" src="' + Yacs.spinningImage.src + '" style="vertical-align:-3px" />');

		// basic options
		var options = {
			asynchronous: true,
			method: 'get',
			evalScripts: true
			};

		// don't use hash from prototype, it would kill our call
		if(args) {
			for(key in args) {
				options[key] = args[key];
			}
		}

		// go go go
		var updater = new Ajax.Updater(panel, address, options);

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
		Element.update('modal_content', content);

		// adjust box size
		Element.setStyle('modal_content', { width: 'auto', height: 'auto' });

		// center the box
		var yShift, xShift;
		yShift = Math.floor(((document.viewport.getHeight() - $('modal_centered').offsetHeight) / 2) - $('modal_centered').offsetTop);
		xShift = Math.floor(((document.viewport.getWidth() - $('modal_centered').offsetWidth) / 2) - $('modal_centered').offsetLeft);

		// update box position
		if((Math.abs(yShift) > 1) || (Math.abs(xShift) > 1)) {
			var effect = new Effect.MoveBy('modal_centered', yShift, xShift, {duration: 0.2, queue: 'end'});
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
		if(!$(panel).innerHTML || ($(panel).innerHTML === '') || ($(panel).innerHTML == '<img alt="*" src="' + Yacs.spinningImage.src + '" style="vertical-align:-3px" />')) {
			Yacs.update(panel, address, args);
		}

	}

};

// initialize yacs
Event.observe(window, "load", Yacs.onWindowLoad);
