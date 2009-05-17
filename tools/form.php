<?php
/**
 * basic form
 *
 * A minimalist script based on the YACS framework.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load localized strings
i18n::bind('tools');

// load the skin -- parameter enables to load template_form.php if it exists
load_skin('form');

// the path to this page
$context['path_bar'] = array( 'tools/' => i18n::s('Tools') );

// page title
$context['page_title'] = i18n::s('Form');

// process uploaded data
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// remerciement
	$context['text'] .= '<p>'.i18n::s('Thank you for your contribution').'</p>';

	// surfer name is in $_REQUEST['surfer_name']
	$context['text'] .= '<p>'.sprintf(i18n::s('%s: %s'), i18n::s('Your name'), $_REQUEST['surfer_name']).'</p>';

	// surfer address is in $_REQUEST['surfer_address']
	$context['text'] .= '<p>'.sprintf(i18n::s('%s: %s'), i18n::s('Your e-mail address'), $_REQUEST['surfer_address']).'</p>';

	// description is in $_REQUEST['description']
	$context['text'] .= '<p>'.sprintf(i18n::s('%s: %s'), i18n::s('Your contribution'), BR.$_REQUEST['description']).'</p>';

// display the form
} else {

	// where to upload data
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';

	// surfer name
	$label = i18n::s('Your name');
	$input = '<input type="text" name="surfer_name" id="edit_name" size="45" value="'.encode_field(Surfer::get_name()).'" maxlength="255" />';
	$hint = i18n::s('Give us a chance to know who you are');
	$fields[] = array($label, $input, $hint);

	// surfer address
	$label = i18n::s('Your e-mail address');
	$input = '<input type="text" name="surfer_address" size="45" value="'.encode_field(Surfer::get_email_address()).'" maxlength="255" />';
	$hint = i18n::s('We will use this to contact you back');
	$fields[] = array($label, $input, $hint);

	// some text
	$label = i18n::s('Your message');
	$input = '<textarea name="description" rows="20" cols="50"></textarea>';
	$hint = i18n::s('Please include all elements we could need');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit')).'</p>'."\n";

	// end of the form
	$context['text'] .= '</div></form>';

}

// render the page
render_skin();

?>