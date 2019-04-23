<?php
/**
 * demonstrate AJAX capabilities of YACS
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load localized strings
i18n::bind('tools');

// load the skin
load_skin('tools');

// the path to this page
$context['path_bar'] = array( 'tools/' => i18n::s('Tools') );

// page title
$context['page_title'] = i18n::s('AJAX demonstration');

// working overlay
$context['text'] .= '<p style="margin-bottom: 1em;">'.Skin::build_submit_button(i18n::s('Working overlay')).' - '.i18n::s('Click on the button, then click on the overlay to hide it').'</p>'."\n";

// a modal box
$context['text'] .= '<p style="margin-bottom: 1em;"><a href="#" onclick="Yacs.displayModalBox({ title: &quot;'.i18n::s('AJAX demonstration').'&quot;,'
	.'body: &quot;Lorem ipsum dolor sit amet, consectetur adipisicing elit,<br />sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.&quot;,'
	.'button_TRUE: &quot;'.i18n::s('OK').'&quot;}, function(choice) { alert(\''.i18n::s('OK').'\') } ); return false;" class="button"><span>'.i18n::s('Modal box').'</span></a> - '.i18n::s('Click on the button, then handle the modal box').'</p>'."\n";

// an alert box
$context['text'] .= '<p style="margin-bottom: 1em;"><a href="#" onclick="Yacs.alert(\''.i18n::s('AJAX demonstration').'\', function() { alert(\''.i18n::s('OK').'\') })" class="button"><span>'.i18n::s('Alert box').'</span></a> - '.i18n::s('Click on the button to show the alert message').'</p>'."\n";

// a confirmation dialog box
$context['text'] .= '<p style="margin-bottom: 1em;"><a href="#" onclick="Yacs.confirm(\''.i18n::s('AJAX demonstration').'\', function(choice) { if(choice) { alert(\''.i18n::s('OK').'\') } })" class="button"><span>'.i18n::s('Confirmation box').'</span></a> - '.i18n::s('Click on the button to make your choice').'</p>'."\n";

// notifications
$context['text'] .= '<p style="margin-bottom: 1em;"><a href="#" onclick="Yacs.handleAlertNotification({ title: &quot;'.i18n::s('Alert notification').'&quot;,'
	.'nick_name: &quot;Foo Bar&quot;,'
	.'address: &quot;http://www.google.com/&quot; })" class="button"><span>'.i18n::s('Alert notification').'</span></a>'
	.' <a href="#" onclick="Yacs.handleBrowseNotification({ message: &quot;'.i18n::s('Browse notification').'&quot;,'
	.'nick_name: &quot;Foo Bar&quot;,'
	.'address: &quot;http://www.google.com/&quot; })" class="button"><span>'.i18n::s('Browse notification').'</span></a>'
	.' <a href="#" onclick="Yacs.handleHelloNotification({ message: &quot;'.i18n::s('Hello notification').'&quot;,'
	.'nick_name: &quot;Foo Bar&quot; })" class="button"><span>'.i18n::s('Hello notification').'</span></a> - '.i18n::s('Click on buttons to process notifications').'</p>'."\n";

// a scaled iframe for preview
$context['text'] .= '<p style="margin-bottom: 1em;"><a href="http://www.cisco.com/" class="button tipsy_preview"><span>'.i18n::s('Cisco').'</span></a> - '.i18n::s('Hover the button to display a preview').'</p>'."\n";

// a popup box
$context['text'] .= '<p style="margin-bottom: 1em;"><a href="http://www.cisco.com/" onclick="Yacs.popup( { url: this.href, width: \'100%\', height: \'100%\' } ); return false;" class="button"><span>'.i18n::s('Cisco').'</span></a> - '.i18n::s('Click on the button to trigger the popup window').'</p>'."\n";

// a JSON-RPC call -- see services/rpc_echo_hook.php
$context['text'] .= '<p style="margin-bottom: 1em;"><a href="#" onclick="Yacs.call( { method: \'echo\', params: { message: \''.i18n::s('AJAX demonstration').'\' }, id: 123 }, function(s) { if(s.message) { alert(s.message); } else { alert(\'failed!\'); } } ); return false;" class="button"><span>JSON-RPC echo</span></a> - '.i18n::s('Click on the button to call a remote function').'</p>'."\n";

// another JSON-RPC call -- see services/rpc_activity_hook.php
$context['text'] .= '<p style="margin-bottom: 1em;"><a href="#" onclick="Yacs.call( { method: \'user.activity\', params: { anchor: \'tools/ajax.php\', action: \'test\' } } ); alert(\'record has been added to table yacs_activities\'); return false;" class="button"><span>JSON-RPC activity</span></a> - '.i18n::s('Click on the button to remember some on-line activity').'</p>'."\n";

// a popup box with content
$context['text'] .= '<p style="margin-bottom: 1em;"><a href="#" onclick="Yacs.popup( { content: \'<html><head><title>Popup</title></head><body><p>'.i18n::s('Hello world').'</p><p>Lorem ipsum dolor sit amet, consectetur adipisicing elit,<br />sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p><p><a href=&quot;javascript:self.close()&quot;>'.i18n::s('Close').'</a></p></body></html>\' } ); return false;" class="button"><span>'.i18n::s('Hello world').'</span></a> - '.i18n::s('Click on the button to trigger the popup window').'</p>'."\n";

// contextual handling of elements in a list
$context['text'] .= '<div class="onDemandTools mutable" style="position:relative; width: 400px; padding: 0.5em; border: 1px dotted #ccc;"><b>'.i18n::s('Hover me to access tools').'</b>'
	.'<p class="properties" style="display: none">Lorem ipsum dolor sit amet, consectetur adipisicing elit,<br />sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>'
	.'<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit,<br />sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>'
	.'</div>'."\n";

// a sortable list
$context['text'] .= '<p style="margin-bottom: 1em;"><b>'.i18n::s('Drag and drop following elements').'</b></p>'
	.'<div id="sortables" style="position:relative; width: 510px; padding: 0.5em; border: 1px dotted #ccc;">'
	.'<div class="sortable">1. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</div>'
	.'<div class="sortable">2. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</div>'
	.'<div class="sortable">3. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</div>'
	.'</div>'."\n";

// filtering floating numbers
$context['text'] .= '<p style="margin-bottom: 1em;"><b>'.i18n::s('Interactive filters').'</b></p>'
	.'<form>'
	.'<p><input type="text" size="6" onkeypress="return Yacs.filterInteger(this, event)" /> - '.i18n::s('Enter an integer').'</p>'
	.'<p><input type="text" size="6" onkeypress="return Yacs.filterFloat(this, event)" /> - '.i18n::s('Enter a floating number').'</p>'
	.'</form>'."\n";

// autocompletion field
$context['text'] .= '<p style="margin-bottom: 1em;"><b>'.i18n::s('autocompletion').'</b></p>'
        .'<form>'
        .'<p>'.Skin::build_autocomplete_tag_input('test_auto', 'test_auto', '', 'keywords').'</p>'
        .'</form>'."\n";

// calling raw content of a page
$context['text'] .= '<p>';
$context['text'] .= '<a class="button" id="view_1"><span>Press to open article 1 viewing page</span></a>';
$context['text'] .= '<a class="button" id="edit_1"><span>Press to open article 1 edition form</span></a>';
$context['text'] .= '</p>';

$context['text'] .= '<p><textarea size="3"></textarea>';

// some AJAX to make it work
Page::insert_script('$("#sortables .sortable").each( function() { '
		    .'$("#sortables").sortable({axis: "y", handle: ".drag_handle"});'."\n"		    
		    //.'$("#edit_1").click(function(){$.get(url_to_root+"articles/edit.php",{id:1, raw:"Y"}).done(function(data){var content={body: data};Yacs.displayModalBox(content);});});'
		    //.'$("#view_1").click(function(){$.get(url_to_root+"articles/view.php",{id:1, raw:"Y"}).done(function(data){var content={body: data};Yacs.displayModalBox(content);});});'
		    .'$("#view_1").click(function(){Yacs.displayOverlaid(url_to_root+"articles/view.php?id=1")});'
		    .'$("#edit_1").click(function(){Yacs.displayOverlaid(url_to_root+"articles/edit.php?id=1",true,true)});'
	);

// render the page according to the loaded skin
render_skin();

?>
