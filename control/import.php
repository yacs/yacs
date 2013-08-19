<?php
/**
 * import some XML content
 *
 * @see articles/export.php
 * @see sections/export.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// do not always show the edition form
$with_form = FALSE;

// load the skin
load_skin('control');

// the title of the page
$context['page_title'] = i18n::s('Import XML content');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('control/import.php'));

// only associates can use this tool
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// no file has been uploaded
	if(!$_FILES['upload']['name'] || ($_FILES['upload']['name'] == 'none')) {
		Logger::error(i18n::s('Nothing has been received.'));

	// process the temporary file
	} else {

		// access the temporary uploaded file
		$file_upload = $_FILES['upload']['tmp_name'];

		// zero bytes transmitted
		$_REQUEST['file_size'] = $_FILES['upload']['size'];
		if(!$_FILES['upload']['size'])
			Logger::error(i18n::s('Nothing has been received.'));

		// check provided upload name
		elseif(!Safe::is_uploaded_file($file_upload))
			Logger::error(i18n::s('Possible file attack.'));

		// no content
		elseif(!$content = ltrim(Safe::file_get_contents($file_upload)))
			Logger::error(sprintf(i18n::s('Impossible to read %s.'), $file_upload));

		// process the file
		else {

			// parsing an overlay, or not
			global $in_overlay;
			$in_overlay = FALSE;

			// class of the overlay to use
			global $overlay_class;
			$overlay_class = NULL;

			// overlay parameters
			global $overlay_parameters;
			$overlay_parameters = '';

			// opening a new tag
			function parse_tag_open($parser, $tag, $attributes) {
				global $in_overlay, $overlay_class, $overlay_parameters;

				// starting overlay data
				if($tag == 'overlay') {
					$in_overlay = TRUE;

					// overlay class
					if(isset($attributes['class']))
						$overlay_class = $attributes['class'];

					// overlay parameters
					if(isset($attributes['parameters']))
						$overlay_parameters = $attributes['parameters'];
				}
			}

			// tag content
			global $parsed_cdata;
			$parsed_cdata = '';

			// additional content
			function parse_cdata($parser, $cdata) {
				global $parsed_cdata;
				$parsed_cdata .= $cdata;
			}

			// gathered attributes
			global $parsed_item;
			$parsed_item = array();

			// gathered overlay attributes
			global $parsed_overlay;
			$parsed_overlay = array();

			// for the end user
			global $parsing_report;
			$parsing_report = '';

			// closing a tag
			function parse_tag_close($parser, $tag) {
				global $context;
				global $in_overlay, $overlay_class, $overlay_parameters;
				global $parsed_cdata, $parsed_item, $parsed_overlay, $parsing_report;

				// save gathered data if necessary
				switch($tag) {

				case 'article': // end of article

					// transcode owner id
					$parsed_item['owner_id'] = Surfer::get_id();
					if(isset($parsed_item['owner_nick_name']) && ($user = Users::get($parsed_item['owner_nick_name'])))
						$parsed_item['owner_id'] = $user['id'];

					// transcode creator id
					$parsed_item['create_id'] = Surfer::get_id();
					if(isset($parsed_item['create_nick_name']) && ($user = Users::get($parsed_item['create_nick_name'])))
						$parsed_item['create_id'] = $user['id'];

					// transcode editor id
					$parsed_item['edit_id'] = Surfer::get_id();
					if(isset($parsed_item['edit_nick_name']) && ($user = Users::get($parsed_item['edit_nick_name'])))
						$parsed_item['edit_id'] = $user['id'];

					// transcode publisher id
					$parsed_item['publish_id'] = Surfer::get_id();
					if(isset($parsed_item['publish_nick_name']) && ($user = Users::get($parsed_item['publish_nick_name'])))
						$parsed_item['publish_id'] = $user['id'];

					// bind to given overlay
					$overlay = NULL;
					if($overlay_class)
						$overlay = Overlay::bind($overlay_class.' '.$overlay_parameters);

					// when the page has been overlaid
					if(is_object($overlay)) {

						// update the overlay from content
						foreach($parsed_overlay as $label => $value)
							$overlay->attributes[ $label ] = $value;

						// save content of the overlay in this item
						$parsed_item['overlay'] = $overlay->save();
						$parsed_item['overlay_id'] = $overlay->get_id();
					}

					// find anchor from handle
					if(isset($parsed_item['anchor_handle']) && ($reference = Sections::lookup($parsed_item['anchor_handle'])))
						$parsed_item['anchor'] = $reference;

					// update an existing page
					if(isset($parsed_item['handle']) && ($item = Articles::get($parsed_item['handle']))) {

						// transcode page id
						$parsed_item['id'] = $item['id'];

						// stop on error
						if(!Articles::put($parsed_item) || (is_object($overlay) && !$overlay->remember('update', $parsed_item, 'article:'.$item['id'])))
							Logger::error(sprintf('Unable to save article %s', $parsed_item['title'].' ('.$parsed_item['id'].')'));

					// create a new page
					} else {
						unset($parsed_item['id']);

						// stop on error
						if(!$parsed_item['id'] = Articles::post($parsed_item))
							Logger::error(sprintf('Unable to save article %s', $parsed_item['title']));

						else {

							// save overlay content
							if(is_object($overlay))
								$overlay->remember('insert', $parsed_item, 'article:'.$parsed_item['id']);

						}
					}

					// report to surfer
					$parsing_report .= '<li>'.Skin::build_link(Articles::get_permalink($parsed_item), $parsed_item['title'])."</li>\n";

					// ready for next item
					$overlay_class = NULL;
					$overlay_parameters = '';
					$parsed_overlay = array();
					$parsed_item = array();
					Safe::set_time_limit(30);
					break;

				case 'overlay': // end of overlay data
					$in_overlay = FALSE;
					break;

				case 'section': // end of section

					// transcode owner id
					$parsed_item['owner_id'] = Surfer::get_id();
					if(isset($parsed_item['owner_nick_name']) && ($user = Users::get($parsed_item['owner_nick_name'])))
						$parsed_item['owner_id'] = $user['id'];

					// transcode creator id
					$parsed_item['create_id'] = Surfer::get_id();
					if(isset($parsed_item['create_nick_name']) && ($user = Users::get($parsed_item['create_nick_name'])))
						$parsed_item['create_id'] = $user['id'];

					// transcode editor id
					$parsed_item['edit_id'] = Surfer::get_id();
					if(isset($parsed_item['edit_nick_name']) && ($user = Users::get($parsed_item['edit_nick_name'])))
						$parsed_item['edit_id'] = $user['id'];

					// bind to given overlay
					$overlay = NULL;
					if($overlay_class)
						$overlay = Overlay::bind($overlay_class.' '.$overlay_parameters);

					// when the page has been overlaid
					if(is_object($overlay)) {

						// update the overlay from content
						foreach($parsed_overlay as $label => $value)
							$overlay->attributes[ $label ] = $value;

						// save content of the overlay in this item
						$parsed_item['overlay'] = $overlay->save();
						$parsed_item['overlay_id'] = $overlay->get_id();
					}

					// find anchor from handle
					if(isset($parsed_item['anchor_handle']) && ($reference = Sections::lookup($parsed_item['anchor_handle'])))
						$parsed_item['anchor'] = $reference;

					// update an existing section
					if(isset($parsed_item['handle']) && ($item = Sections::get($parsed_item['handle']))) {

						// transcode section id
						$parsed_item['id'] = $item['id'];

						// stop on error
						if(!Sections::put($parsed_item) || (is_object($overlay) && !$overlay->remember('update', $parsed_item, 'section:'.$item['id'])))
							Logger::error(sprintf('Unable to save section %s', $parsed_item['title'].' ('.$parsed_item['id'].')'));

					// create a new page
					} else {
						unset($parsed_item['id']);

						// stop on error
						if(!$parsed_item['id'] = Sections::post($parsed_item))
							Logger::error(sprintf('Unable to save section %s', $parsed_item['title']));

						else {

							// save overlay content
							if(is_object($overlay))
								$overlay->remember('insert', $parsed_item, 'section:'.$parsed_item['id']);

						}
					}

					// report to surfer
					$parsing_report .= '<li>'.Skin::build_link(Sections::get_permalink($parsed_item), $parsed_item['title'])."</li>\n";

					// ready for next item
					$overlay_class = NULL;
					$overlay_parameters = '';
					$parsed_overlay = array();
					$parsed_item = array();
					Safe::set_time_limit(30);
					break;

				default: // just another attribute

					// decode cdata
					$parsed_cdata = trim(preg_replace(array('/&lt;/', '/&gt;/'), array('<', '>'), $parsed_cdata));

					// feeding the overlay or the item itself
					if($in_overlay)
						$parsed_overlay[$tag] = $parsed_cdata;
					else
						$parsed_item[$tag] = $parsed_cdata;

					// ready for next attribute
					$parsed_cdata = '';
					break;

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
				Logger::error('Parsing error: '.xml_error_string(xml_get_error_code($parser)).' at line '.xml_get_current_line_number($parser));
			} else {

				$context['text'] = '<p>'.i18n::s('Following items have been processed:')."</p>\n";
				$context['text'] .= '<ul>'.$parsing_report.'</ul>';

			}
			xml_parser_free($parser);

		}

		// delete the temporary file
		Safe::unlink($file_upload);

		// clear the full cache
		Cache::clear();

	}

} else {

	// the form to post a file
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form" enctype="multipart/form-data"><div>';

	// the file
	$label = i18n::s('File');
	$input = '<input type="file" name="upload" id="upload" size="30" />'
		.' (&lt;&nbsp;'.$context['file_maximum_size'].i18n::s('bytes').')';
	$hint = i18n::s('Select the file to upload');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit')).'</p>';

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	Page::insert_script('$("#upload").focus();');

}

// render the skin
render_skin();

?>