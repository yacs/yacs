/**
 * to be loaded after codes/edit.js
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// the path to YACS root
if(!url_to_root)
	url_to_root = '/yacs/';

// tabindex to avoid form cluttering with buttons
document.write('<div style="margin: 0px;">'
	 + '<a href="#" onclick="edit_insert(\' :-) \',\'\');return false;" title="smile" tabindex="2000"><img src="' + url_to_root + 'skins/_reference/smileys/smile.gif" /></a> '
	+ '<a href="#" onclick="edit_insert(\' ;-) \',\'\');return false;" title="wink" tabindex="2001"><img src="' + url_to_root + 'skins/_reference/smileys/winkgrin.gif" /></a> '
	+ '<a href="#" onclick="edit_insert(\' >:-( \',\'\');return false;" title="angry" tabindex="2002"><img src="' + url_to_root + 'skins/_reference/smileys/angry.gif" /></a> '
	+ '<a href="#" onclick="edit_insert(\' :-D \',\'\');return false;" title="cheesy" tabindex="2003"><img src="' + url_to_root + 'skins/_reference/smileys/cheesy.gif" /></a> '
	+ '<a href="#" onclick="edit_insert(\' :-o \',\'\');return false;" title="shocked" tabindex="2004"><img src="' + url_to_root + 'skins/_reference/smileys/shocked.gif" /></a> '
	+ '<a href="#" onclick="edit_insert(\' 8-) \',\'\');return false;" title="cool" tabindex="2005"><img src="' + url_to_root + 'skins/_reference/smileys/cool.gif" /></a> '
	+ '<a href="#" onclick="edit_insert(\' ??? \',\'\');return false;" title="confused" tabindex="2006"><img src="' + url_to_root + 'skins/_reference/smileys/confused.gif" /></a> '
	+ '<a href="#" onclick="edit_insert(\' ::-) \',\'\');return false;" title="roll" tabindex="2007"><img src="' + url_to_root + 'skins/_reference/smileys/rolleyes.gif" /></a> '
	+ '<a href="#" onclick="edit_insert(\' :-P \',\'\');return false;" title="tongue" tabindex="2008"><img src="' + url_to_root + 'skins/_reference/smileys/tongue.gif" /></a> '
	+ '<a href="#" onclick="edit_insert(\' :-[ \',\'\');return false;" title="embarassed" tabindex="2009"><img src="' + url_to_root + 'skins/_reference/smileys/embarassed.gif" /></a> '
	+ '<a href="#" onclick="edit_insert(\' :-X \',\'\');return false;" title="sealed" tabindex="2010"><img src="' + url_to_root + 'skins/_reference/smileys/sealed.gif" /></a> '
	+ '<a href="#" onclick="edit_insert(\' :-/ \',\'\');return false;" title="undecided" tabindex="2011"><img src="' + url_to_root + 'skins/_reference/smileys/undecided.gif" /></a> '
	+ '<a href="#" onclick="edit_insert(\' :-* \',\'\');return false;" title="kiss" tabindex="2012"><img src="' + url_to_root + 'skins/_reference/smileys/kiss.gif" /></a> '
	+ '<a href="#" onclick="edit_insert(\' :\\\'( \',\'\');return false;" title="cry" tabindex="2013"><img src="' + url_to_root + 'skins/_reference/smileys/cry.gif" /></a> '
	+ '<a href="' + url_to_root + 'smileys/" onclick="window.open(this.href);return false;" tabindex="2014">>></a> '
	+ '</div><br />');

