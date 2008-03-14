// allow for document.write() in XHTML
// @link http://www.intertwingly.net/blog/2006/11/10/Thats-Not-Write#comments
//
if(typeof document.write == 'undefined') {
	document.write = function() {
		// Concatenating all arguments
		var str = '';
		for (var i = 0; i < arguments.length; i++)
		  str += String(arguments[i]);
		var s = document.createElement('span');
		s.innerHTML = str;
		var e = document.all || document.getElementsByTagName('*');
		var last = e[e.length - 1];
		// Put everything in a span in order to execute included scripts
		last.parentNode.appendChild(s);
		// taking it out again to have “pure” innerHTML
		last.parentNode.removeChild(s);
		last.parentNode.innerHTML += str;
	}
}