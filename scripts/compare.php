<?php
/**
 * compare two scripts
 *
 * [code]scripts/compare.php?original=files/delete.php&updated=files/delete_v2.php[/code]
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// original script
$original = NULL;
if(isset($_REQUEST['original']))
	$original = $_REQUEST['original'];
$original = preg_replace(FORBIDDEN_STRINGS_IN_PATHS, '', strip_tags($original));

// updated script
$updated = NULL;
if(isset($_REQUEST['updated']))
	$updated = $_REQUEST['updated'];
$updated = preg_replace(FORBIDDEN_STRINGS_IN_PATHS, '', strip_tags($updated));

// what to do
$format = 'merge';
if(isset($_REQUEST['format']))
	$format = $_REQUEST['format'];

// load localized strings
i18n::bind('scripts');

// load the skin
load_skin('scripts');

// the path to this page
$context['path_bar'] = array( 'scripts/' => i18n::s('Server software') );

// the title of the page
if($original && $updated)
	$context['page_title'] = 'Compare: '.$original.' versus '.$updated;
else
	$context['page_title'] = i18n::s('Script comparison');

// only associates can go further
if(!Surfer::is_associate())
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// no script has been provided
elseif(!$original || !$updated) {

	// the form to get script names
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';

	// the original script
	$label = i18n::s('Original script');
	$input = '<input type="text" name="original" id="original" size="45" value="'.encode_field($original).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// the updated script
	$label = i18n::s('Updated script');
	$input = '<input type="text" name="updated" size="45" value="'.encode_field($updated).'" maxlength="255" />';
	$fields[] = array($label, $input);

	// select the output format
	$label = i18n::s('Output');
	$input = '<input type="radio" name="format" value="tabular" checked="checked"';
	$input .= EOT.' '.i18n::s('tabular').' ';
	$input .= '<input type="radio" name="format" value="gdiff"';
	$input .= EOT.' '.i18n::s('gdiff')."\n";
	$input .= '<input type="radio" name="format" value="merge"';
	$input .= EOT.' '.i18n::s('merge')."\n";
	$fields[] = array($label, $input);

	// the submit button
	$input = Skin::build_submit_button(i18n::s('Compare'));
	$fields[] = array(NULL, $input);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// end of the form
	$context['text'] .= '</div></form>';

	// set the focus
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// set the focus on first form field'."\n"
		.'$("original").focus();'."\n"
		.'// ]]></script>'."\n";

// the original script has to be there
} elseif(!file_exists($context['path_to_root'].$original)) {
	Skin::error(sprintf(i18n::s('Script %s does not exist'), $original));

// the updatred script has to be there
} elseif(!file_exists($context['path_to_root'].$updated)) {
	Skin::error(sprintf(i18n::s('Script %s does not exist'), $updated));

// analyze two scripts
} else {

	// print the result of the analysis
	include_once 'scripts.php';
	if($format == 'tabular') {
		$text = Scripts::diff($original, $updated);

		// the button to see the gdiff format
		$context['text'] .= '<div style="float:left;"><form method="post" action="'.$context['script_url'].'">';
		$context['text'] .= '<input type="hidden" name="original" value="'.encode_field($original).'" />';
		$context['text'] .= '<input type="hidden" name="updated" value="'.encode_field($updated).'" />';
		$context['text'] .= '<input type="hidden" name="format" value="gdiff" />';
		$context['text'] .= Skin::build_submit_button(i18n::s('gdiff'));
		$context['text'] .= '</form></div>';

		// the button to see the merge format
		$context['text'] .= '<div style="float:left;"><form method="post" action="'.$context['script_url'].'">';
		$context['text'] .= '<input type="hidden" name="original" value="'.encode_field($original).'" />';
		$context['text'] .= '<input type="hidden" name="updated" value="'.encode_field($updated).'" />';
		$context['text'] .= '<input type="hidden" name="format" value="merge" />';
		$context['text'] .= Skin::build_submit_button(i18n::s('Merge'));
		$context['text'] .= '</form></div>';

		$context['text'] .= '<br style="clear:left;"'.EOT."\n";

	} elseif($format == 'merge') {
		$text = Scripts::merge($original, $updated);

		// the button to see the tabular format
		$context['text'] .= '<div style="float:left;"><form method="post" action="'.$context['script_url'].'">';
		$context['text'] .= '<input type="hidden" name="original" value="'.encode_field($original).'" />';
		$context['text'] .= '<input type="hidden" name="updated" value="'.encode_field($updated).'" />';
		$context['text'] .= '<input type="hidden" name="format" value="tabular" />';
		$context['text'] .= Skin::build_submit_button(i18n::s('Tabular'));
		$context['text'] .= '</form></div>';

		// the button to see the gdiff format
		$context['text'] .= '<div style="float:left;"><form method="post" action="'.$context['script_url'].'">';
		$context['text'] .= '<input type="hidden" name="original" value="'.encode_field($original).'" />';
		$context['text'] .= '<input type="hidden" name="updated" value="'.encode_field($updated).'" />';
		$context['text'] .= '<input type="hidden" name="format" value="gdiff" />';
		$context['text'] .= Skin::build_submit_button(i18n::s('gdiff'));
		$context['text'] .= '</form></div>';

		$context['text'] .= '<br style="clear:left;"'.EOT."\n";

	} else {
		$text = Scripts::gdiff($original, $updated);

		// the button to see the tabular format
		$context['text'] .= '<div style="float:left;"><form method="post" action="'.$context['script_url'].'">';
		$context['text'] .= '<input type="hidden" name="original" value="'.encode_field($original).'" />';
		$context['text'] .= '<input type="hidden" name="updated" value="'.encode_field($updated).'" />';
		$context['text'] .= '<input type="hidden" name="format" value="tabular" />';
		$context['text'] .= Skin::build_submit_button(i18n::s('Tabular'));
		$context['text'] .= '</form></div>';

		// the button to see the merge format
		$context['text'] .= '<div style="float: left;"><form method="post" action="'.$context['script_url'].'">';
		$context['text'] .= '<input type="hidden" name="original" value="'.encode_field($original).'" />';
		$context['text'] .= '<input type="hidden" name="updated" value="'.encode_field($updated).'" />';
		$context['text'] .= '<input type="hidden" name="format" value="merge" />';
		$context['text'] .= Skin::build_submit_button(i18n::s('Merge'));
		$context['text'] .= '</form></div>';

		$context['text'] .= '<br style="clear:left;"'.EOT."\n";

	}
	$context['text'] .= "\n<p><pre>\n".htmlspecialchars(str_replace("\t", '  ', $text))."\n</pre></p>\n";
}

// render the skin
render_skin();

?>