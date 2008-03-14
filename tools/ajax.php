<?php
/**
 * demonstrate AJAX capabilities of YACS
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load localized strings
i18n::bind('tools');

// load the skin
load_skin('tools');

// page title
$context['page_title'] = i18n::s('AJAX demonstration');

// working overlay
$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Working overlay')).' - '.i18n::s('Click on the button, then click on the overlay to hide it').'</p>'."\n";

// a modal box
$context['text'] .= '<p><a href="#" onclick="Yacs.displayModalBox({ title: &quot;'.i18n::s('AJAX demonstration').'&quot;,'
	.'body: &quot;Lorem ipsum dolor sit amet, consectetur adipisicing elit,<br />sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.&quot;,'
	.'button_TRUE: &quot;'.i18n::s('OK').'&quot;}, function(choice) { alert(\''.i18n::s('OK').'\') } ); return false;" class="button"><span>'.i18n::s('Modal box').'</span></a> - '.i18n::s('Click on the button, then handle the modal box').'</p>'."\n";

// an alert box
$context['text'] .= '<p><a href="#" onclick="Yacs.alert(\''.i18n::s('AJAX demonstration').'\', function() { alert(\''.i18n::s('OK').'\') })" class="button"><span>'.i18n::s('Alert box').'</span></a> - '.i18n::s('Click on the button to show the alert message').'</p>'."\n";

// a confirmation dialog box
$context['text'] .= '<p><a href="#" onclick="Yacs.confirm(\''.i18n::s('AJAX demonstration').'\', function(choice) { if(choice) { alert(\''.i18n::s('OK').'\') } })" class="button"><span>'.i18n::s('Confirmation box').'</span></a> - '.i18n::s('Click on the button to make your choice').'</p>'."\n";

// notifications
$context['text'] .= '<p><a href="#" onclick="Yacs.handleAlertNotification({ title: &quot;'.i18n::s('Alert notification').'&quot;,'
	.'nick_name: &quot;Foo Bar&quot;,'
	.'address: &quot;http://www.google.com/&quot; })" class="button"><span>'.i18n::s('Alert notification').'</span></a>'
	.' <a href="#" onclick="Yacs.handleBrowseNotification({ message: &quot;'.i18n::s('Browse notification').'&quot;,'
	.'nick_name: &quot;Foo Bar&quot;,'
	.'address: &quot;http://www.google.com/&quot; })" class="button"><span>'.i18n::s('Browse notification').'</span></a>'
	.' <a href="#" onclick="Yacs.handleHelloNotification({ message: &quot;'.i18n::s('Hello notification').'&quot;,'
	.'nick_name: &quot;Foo Bar&quot; })" class="button"><span>'.i18n::s('Hello notification').'</span></a> - '.i18n::s('Click on buttons to process notifications').'</p>'."\n";

// a popup box
$context['text'] .= '<p><a href="http://www.google.com/" onclick="Yacs.popup( { url: this.href, width: \'100%\', height: \'100%\' } ); return false;" class="button"><span>'.i18n::s('Google').'</span></a> - '.i18n::s('Click on the button to trigger the popup window').'</p>'."\n";

// a popup box with content
$context['text'] .= '<p><a href="#" onclick="Yacs.popup( { content: \'<html><head><title>Popup</title></head><body><p>'.i18n::s('Hello world').'</p><p>Lorem ipsum dolor sit amet, consectetur adipisicing elit,<br />sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p><p><a href=&quot;javascript:self.close()&quot;>'.i18n::s('Close').'</a></p></body></html>\' } ); return false;" class="button"><span>'.i18n::s('Hello world').'</span></a> - '.i18n::s('Click on the button to trigger the popup window').'</p>'."\n";

// contextual handling of elements in a list
$context['text'] .= '<div class="onDemandTools mutable" style="position:relative; width: 400px; padding: 0.5em; border: 1px dotted #ccc;"><b>'.i18n::s('Hover me to access tools').'</b>'
	.'<p class="properties" style="display: none">Lorem ipsum dolor sit amet, consectetur adipisicing elit,<br />sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>'
	.'<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit,<br />sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>'
	.'</div>'."\n";

// a sortable list
$context['text'] .= '<p><b>'.i18n::s('Drag and drop following elements').'</b></p>'
	.'<div id="sortables" style="position:relative; width: 510px; padding: 0.5em; border: 1px dotted #ccc;">'
	.'<div class="sortable">1. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</div>'
	.'<div class="sortable">2. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</div>'
	.'<div class="sortable">3. Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</div>'
	.'</div>'."\n";

// some AJAX to make it work
$context['page_footer'] .= '<script type="text/javascript">// <![CDATA['."\n"
	.'$("sortables").select(".sortable").each(function(node) { Yacs.addOnDemandTools(node); });'."\n"
	.'Sortable.create("sortables", {tag:"div", only:"sortable", overclass:"sortable_hover", ghosting:true, constraint:"vertical", handle:"drag_handle" });'."\n"
	.'// ]]></script>'."\n";

// render the page according to the loaded skin
render_skin();

?>