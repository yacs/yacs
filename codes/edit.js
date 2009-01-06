//
// @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
// @tester Fernand
// @reference
// @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
//

function countInstances(open,closed) {
	 var ta = document.getElementById('edit_area');

	 var opening = ta.value.split(open);
	 var closing = ta.value.split(closed);
	 return opening.length + closing.length - 2;
}

// insert some text
function edit_insert(text1, text2) {
	 var ta = document.getElementById('edit_area');

	// ie
	if (document.selection) {
		var str = document.selection.createRange().text;
		ta.focus();
		var sel = document.selection.createRange();
		if (text2!="") {
		   if (str=="") {
			  var instances = countInstances(text1,text2);
			  if (instances%2 != 0){ sel.text = sel.text + text2;}
			  else{ sel.text = sel.text + text1;}
		   } else {
			  sel.text = text1 + sel.text + text2;
		   }
		} else {
		   sel.text = sel.text + text1;
		}

	 // mozilla
	 } else if (ta.selectionStart | ta.selectionStart == 0) {
		if (ta.selectionEnd > ta.value.length) {
			ta.selectionEnd = ta.value.length;
		}

		var firstPos = ta.selectionStart;
		var secondPos = ta.selectionEnd+text1.length;

		ta.value=ta.value.slice(0,firstPos)+text1+ta.value.slice(firstPos);
		ta.value=ta.value.slice(0,secondPos)+text2+ta.value.slice(secondPos);

		ta.selectionStart = firstPos+text1.length;
		ta.selectionEnd = secondPos;
		ta.focus();

	 } else { // Opera
		var sel = document.hop.contenu;

		var instances = countInstances(text1,text2);
		if (instances%2 != 0 && text2 != ""){
			sel.value = sel.value + text2;
		} else{
			sel.value = sel.value + text1;
		}
	 }
}

// insert current date
function edit_insert_date() {
	var now = new Date();
	edit_insert('\n- '+now.toString().substring(4, 10)+' ', '');
}

// tabindex to avoid form cluttering with buttons
document.write('<div style="margin-top: 2px;">'
	+ '<a href="#" onclick="edit_insert_date();return false;" class="button" title="Date [d]" tabindex="1000" accesskey="d"><span>- Date</span></a>'
	+ '<a href="#" onclick="edit_insert(\'[nl]\',\'\');return false;" class="button" title="New line [n]" tabindex="1001" accesskey="n"><span>[nl]</span></a>'
	+ '<a href="#" onclick="edit_insert(\'\\t\',\'\');return false;" class="button" title="Tab [t]" tabindex="1002" accesskey="t"><span> -> </span></a>'
	+ '<a href="#" onclick="edit_insert(\'[b]\',\'[/b]\');return false;" class="button" title="Bold [b]" tabindex="1003" accesskey="b"><span><b>B</b></span></a>'
	+ '<a href="#" onclick="edit_insert(\'[i]\',\'[/i]\');return false;" class="button" title="Italic [i]" tabindex="1004" accesskey="i"><span><i>I </i></span></a>'
	+ '<a href="#" onclick="edit_insert(\'[u]\',\'[/u]\');return false;" class="button" title="Underlined [u]" tabindex="1005" accesskey="u"><span><u>U</u></span></a>'
	+ '<a href="#" onclick="edit_insert(\'\\n[*] \',\'\');return false;" class="button" title="Bullet [x]" tabindex="1006" accesskey="x"><span>[*]</span></a>'
	+ '<a href="#" onclick="edit_insert(\'[Label|http://link]\',\'\');return false;" class="button" title="Link [l]" tabindex="1007" accesskey="l"><span>[link]</span></a>'
	+ '<a href="#" onclick="edit_insert(\'[email]\',\'[/email]\');return false;" class="button" title="Email address [e]" tabindex="1008" accesskey="e"><span>[email]</span></a>'
	+ '<a href="#" onclick="edit_insert(\'\\n[quote]\',\'[/quote]\\n\');return false;" class="button" title="Quote [q]" tabindex="1009" accesskey="q"><span>&quot;..&quot;</span></a>'
	+ '<a href="#" onclick="edit_insert(\'\\n\\n[title]\',\'[/title]\\n\');return false;" class="button" title="Level 1 title [1]" tabindex="1010" accesskey="1"><span>h1</span></a>'
	+ '<a href="#" onclick="edit_insert(\'\\n\\n[subtitle]\',\'[/subtitle]\\n\');return false;" class="button" title="Level 2 title [2]" tabindex="1011" accesskey="2"><span>h2</span></a>'
	+ '</div><br />');

