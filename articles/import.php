<?php
/**
 * import some XML file to create pages
 *
 * This is initial work that does not support overlays
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// the maximum size for uploads
$file_maximum_size = str_replace('M', ' M', Safe::get_cfg_var('upload_max_filesize'));
if(!$file_maximum_size)
	$file_maximum_size = '2 M';

// do not always show the edition form
$with_form = FALSE;

// load the skin
load_skin('articles');

// the path to this page
$context['path_bar'] = array( 'articles/' => i18n::s('All pages') );

// the title of the page
$context['page_title'] = i18n::s('Import');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('articles/import.php'));

// only associates can use this tool
elseif(!Surfer::is_associate())
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// process uploaded data
elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// no file has been uploaded
	if(!$_FILES['upload']['name'] || ($_FILES['upload']['name'] == 'none')) {
		Skin::error(i18n::s('Nothing has been received.'));

	// process the temporary file
	} else {

		// access the temporary uploaded file
		$file_upload = $_FILES['upload']['tmp_name'];

		// zero bytes transmitted
		$_REQUEST['file_size'] = $_FILES['upload']['size'];
		if(!$_FILES['upload']['size'])
			Skin::error(i18n::s('Nothing has been received.'));

		// check provided upload name
		elseif(!Safe::is_uploaded_file($file_upload))
			Skin::error(i18n::s('Possible file attack.'));

		// no content
		elseif(!$content = Safe::file_get_contents($file_upload))
			Skin::error(sprintf(i18n::s('Impossible to read %s.'), $file_upload));

		// process the file
		else {

			global $state;
			$state = '*nowhere*';

			function parse_tag_open($parser, $tag, $attributes) {
				global $state;
				if($tag == 'article')
					$state = 'article';
			}

			global $parsed_cdata;

			function parse_cdata($parser, $cdata) {
				global $parsed_cdata;
				$parsed_cdata .= $cdata;
			}

			global $parsed_item;

			global $parsed_items;

			function parse_tag_close($parser, $tag) {
				global $state, $parsed_cdata, $parsed_item, $parsed_items;

				if($state == 'article') {
					if(preg_match('/^(create_date|create_name|description|edit_date|edit_name|introduction|nick_name|publish_date|publish_name|source|title)$/', $tag))
						$parsed_item[$tag] = trim(preg_replace(array('/&lt;/', '/&gt;/'), array('<', '>'), $parsed_cdata));
				}
				$parsed_cdata = '';

				if($tag == 'article') {
					$parsed_items[] = $parsed_item;
					$parsed_item = array();
					Safe::set_time_limit(30);
				}
			}

			// create a parser
			$parser = xml_parser_create();
			xml_set_element_handler($parser, 'parse_tag_open', 'parse_tag_close');
			xml_set_character_data_handler($parser, 'parse_cdata');

			// case is meaningful
			xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, FALSE);

			// parse data
			if(!xml_parse($parser, $content)) {
				Skin::error('Parsing error: '.xml_error_string(xml_get_error_code($parser)).' at line '.xml_get_current_line_number($parser));
			} else {

				$context['text'] = '<p>'.i18n::s('Following articles have been processed:')."</p>\n";

				$context['text'] .= '<ul>';
				foreach($parsed_items as $item) {

					// default anchor
					if(!$item['anchor'])
						$item['anchor'] = $_REQUEST['anchor'];

					// force publishing
					if($_REQUEST['force_publication'] == 'Y') {
						if(!$item['publish_name']) {
							$item['publish_name'] = Surfer::get_name();
							$item['publish_id'] = Surfer::get_id();
							$item['publish_address'] = Surfer::get_email_address();
						}
						if($item['publish_date'] < '0000-00-00')
							$item['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
					}

					if(Articles::post($item))
						$context['text'] .= '<li>'.$item['title'].' ('.i18n::s('imported').")</li>\n";

					Safe::set_time_limit(30);
				}
				$context['text'] .= "</ul>\n";

			}
			xml_parser_free($parser);

		}

		// delete the temporary file
		Safe::unlink($file_upload);

		// clear the full cache
		Cache::clear();

	}

} else {

	// the splash message
	$context['text'] .= '<p>'.i18n::s('Post an XML file to import articles.')."</p>\n";

	// the form to post a file
	$context['text'] .= '<form method="post" enctype="multipart/form-data" action="'.$context['script_url'].'" id="main_form"><div>';

	// the file
	$label = i18n::s('File');
	$input = '<input type="file" name="upload" id="upload" size="30" />'
		.' (&lt;&nbsp;'.$file_maximum_size.i18n::s('bytes').')';
	$hint = i18n::s('Select the file to upload');
	$fields[] = array($label, $input, $hint);

	// the default section for new pages
	$label = i18n::s('Default Section');
	$input = '<select name="anchor">'.Sections::get_options(isset($_REQUEST['anchor']) ? $_REQUEST['anchor'] : '').'</select>';
	$hint = i18n::s('Pages will be posted in this section by default');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// publish all pages
	$context['text'] .= '<p><input type="checkbox" name="force_publication" value="Y" /> '.i18n::s('Force publication of all new pages').'</p>';

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit')).'</p>';

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// set the focus on first form field'."\n"
		.'$("upload").focus();'."\n"
		.'// ]]></script>'."\n";

}

// render the skin
render_skin();

?>