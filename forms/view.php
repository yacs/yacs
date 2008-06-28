<?php
/**
 * capture form data
 *
 * This script displays an empty form, captures the input from the end-user,
 * and creates a web page out of it.
 *
 * Content of the new page reflects form content, except help labels.
 * Moreover, an overlay of class form is added to the page to store captured
 * values. This overlay provides support for export to CSV and XML formats.
 *
 * The extra panel has following elements:
 * - The top popular referrals, if any
 *
 * Accepted calls:
 * - view.php/12
 * - view.php?id=12
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../files/files.php';	// for attached files
include_once 'forms.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Forms::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// access is restricted to authenticated member
elseif(($item['active'] == 'R') && Surfer::is_member())
	$permitted = TRUE;

// public access is allowed
elseif($item['active'] == 'Y')
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin
load_skin('forms');

// the path to this page
$context['path_bar'] = array( 'forms/' => i18n::s('Forms') );

// the title of the page
if($item['title'])
	$context['page_title'] = $item['title'];

// no form yet
$with_form = FALSE;

// not found
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// no anchor
}elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No anchor has been found.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Forms::get_url($item['id'], 'view', $item['title'])));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// save this at the right place
	$_REQUEST['anchor'] = $anchor->get_reference();

	// protect from hackers
	if(isset($_REQUEST['edit_name']))
		$_REQUEST['edit_name'] = preg_replace(FORBIDDEN_CHARS_IN_NAMES, '_', $_REQUEST['edit_name']);
	if(isset($_REQUEST['edit_address']))
		$_REQUEST['edit_address'] = preg_replace(FORBIDDEN_CHARS_IN_URLS, '_', $_REQUEST['edit_address']);

	// let define a new id
	unset($_REQUEST['id']);

	// make a title for the new page
	$_REQUEST['title'] = sprintf(i18n::c('Form submitted by %s %s'), Surfer::get_name(), Skin::build_date(time(), 'day'));

	// always publish the page
	$_REQUEST['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');

	// do not re-use form description
	$_REQUEST['description'] = '';

	// parse the form
	$text = '';
	$attributes = array();
	if($item['content']) {

		// list all fields in sequence
		$fields = Safe::json_decode($item['content']);
		foreach($fields as $field) {

			// sanity check
			if(!isset($field['class']))
				continue;

			switch($field['class']) {

			// reflect captured text
			case 'file':
				if(isset($_FILES[$field['name']]['name']) && $_FILES[$field['name']]['name']) {

					// $_FILES transcoding to utf8 is not automatic
					$_FILES[$field['name']]['name'] = utf8::to_unicode($_FILES[$field['name']]['name']);

					// enhance file name
					$file_name = $_FILES[$field['name']]['name'];
					$file_extension = '';
					$position = strrpos($_FILES[$field['name']]['name'], '.');
					if($position !== FALSE) {
						$file_name = substr($_FILES[$field['name']]['name'], 0, $position);
						$file_extension = strtolower(substr($_FILES[$field['name']]['name'], $position+1));
					}
					$_FILES[$field['name']]['name'] = str_replace(array('.', '_', '%20'), ' ', $file_name);
					if($file_extension)
						$_FILES[$field['name']]['name'] .= '.'.$file_extension;

					// to be replaced with actual file reference
					$text .= '<p>[file='.$field['name'].', '.$_FILES[$field['name']]['name'].']'."</p>\n";

					// remember attribute value
					$attributes[] = array($field['name'] => $_FILES[$field['name']]['name']);
				}
				break;

			// reflect titles in the generated page
			case 'label':
				if(isset($field['type']) && ($field['type'] == 'title'))
					$text .= '<h2>'.$field['text'].'</h2>'."\n";
				elseif(isset($field['type']) && ($field['type'] == 'subtitle'))
					$text .= '<h3>'.$field['text'].'</h3>'."\n";
// 				else
// 					$text .= $field['text']."\n";
				break;

			// reflect selected items
			case 'list':

				// retrieve options
				$options = array();
				$lines = explode("\n", $field['text']);
				foreach($lines as $line) {
					if(preg_match('!/(.+?)/\s*(.+)$!', $line, $matches))
						$options[] = array($matches[1], $matches[2]);
				}

				// display selected option
				if(isset($_REQUEST[$field['name']])) {
					$checked = array();
					if($field['type'] == "check")
						$text .= '<ul>'."\n";
					else
						$text .= '<p>';
					foreach($options as $option) {
						if((is_array($_REQUEST[$field['name']]) && in_array($option[0], $_REQUEST[$field['name']])) || ($option[0] == $_REQUEST[$field['name']])) {
							if($field['type'] == "check")
								$text .= '<li>'.$option[1]."</li>\n";
							else
								$text .= $option[1];

							$checked = array_merge($checked, array($option[0] => $option[1]));
						}
					}
					if($field['type'] == "check")
						$text .= '</ul>'."\n";
					else
						$text .= '</p>'."\n";

					// remember attribute value
					$attributes[] = array($field['name'] => $checked);
				}

				break;

			// reflect captured text
			case 'text':
				$text .= '<p>'.$_REQUEST[$field['name']]."</p>\n";

				// remember attribute value
				$attributes[] = array($field['name'] => $_REQUEST[$field['name']]);
				break;
			}
		}
	}
	if(isset($_REQUEST['description']))
		$_REQUEST['description'] .= $text;
	else
		$_REQUEST['description'] = $text;

	// track anonymous surfers
	Surfer::track($_REQUEST);

	// store structured data in an overlay
	include_once '../overlays/overlay.php';
	$overlay = Overlay::bind('form');
	$overlay->parse_once($attributes);

	// save content of the overlay in the article
	$_REQUEST['overlay'] = $overlay->save();
	$_REQUEST['overlay_id'] = $overlay->get_id();

	// stop robots
	if(Surfer::may_be_a_robot()) {
		Skin::error(i18n::s('Please prove you are not a robot.'));
		$item = $_REQUEST;
		$with_form = TRUE;

	// create a new page
	} elseif(!$_REQUEST['id'] = Articles::post($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// successful post
	} else {

		// attach files to this page, if any
		if(is_array($_FILES) && Surfer::may_upload()) {

			// look for files in fields
			foreach($fields as $field) {
				if(isset($field['class']) && ($field['class'] == 'file') && isset($_FILES[$field['name']]['name']) && $_FILES[$field['name']]['name']) {

					// ensure we have a file name
					$file_name = utf8::to_ascii($_FILES[$field['name']]['name']);

					// create folders
					$file_path = 'files/'.$context['virtual_path'].'article/'.$_REQUEST['id'];
					Safe::make_path($file_path);

					// make an absolute path
					$file_path = $context['path_to_root'].$file_path.'/';

					// size exceeds php.ini settings -- UPLOAD_ERR_INI_SIZE
					if(isset($_FILES[$field['name']]['error']) && ($_FILES[$field['name']]['error'] == 1))
						Skin::error(i18n::s('The size of this file is over server limit (php.ini).'));

					// size exceeds form limit -- UPLOAD_ERR_FORM_SIZE
					elseif(isset($_FILES[$field['name']]['error']) && ($_FILES[$field['name']]['error'] == 2))
						Skin::error(i18n::s('The size of this file is over form limit.'));

					// partial transfer -- UPLOAD_ERR_PARTIAL
					elseif(isset($_FILES[$field['name']]['error']) && ($_FILES[$field['name']]['error'] == 3))
						Skin::error(i18n::s('File transfer has been interrupted.'));

					// no file -- UPLOAD_ERR_NO_FILE
					elseif(isset($_FILES[$field['name']]['error']) && ($_FILES[$field['name']]['error'] == 4))
						Skin::error(i18n::s('No file has been transferred.'));

					// zero bytes transmitted
					elseif(!$_FILES[$field['name']]['size'])
						Skin::error(sprintf(i18n::s('It is likely file size goes beyond the limit displayed in upload form. Nothing has been transmitted for %s.'), $_FILES[$field['name']]['name']));

					// check provided upload name
					elseif(!Safe::is_uploaded_file($_FILES[$field['name']]['tmp_name']))
						Skin::error(i18n::s('Possible file attack.'));

					// move the uploaded file
					elseif(!Safe::move_uploaded_file($_FILES[$field['name']]['tmp_name'], $file_path.$file_name))
						Skin::error(sprintf(i18n::s('Impossible to move the upload file to %s.'), $file_path.$file_name));

					// this will be filtered by umask anyway
					else {
						Safe::chmod($file_path.$file_name, $context['file_mask']);

						// record one file entry
						$item = array();
						$item['anchor'] = 'article:'.$_REQUEST['id'];
						$item['file_name'] = $file_name;
						$item['file_size'] = $_FILES[$field['name']]['size'];
						$item['file_href'] = '';

						//
						if($attached = Files::post($item))
							$_REQUEST['description'] = str_replace('[file='.$field['name'], '[file='.$attached, $_REQUEST['description']);

					}

				}
			}

			// update references in new page
			Articles::put($_REQUEST);

		}

		// touch the related anchor
		$anchor->touch('article:create', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

		// clear the cache
		Articles::clear($_REQUEST);

		// if poster is a registered user
		if(Surfer::get_id()) {

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// add this page to watch list
			Members::assign('article:'.$_REQUEST['id'], 'user:'.Surfer::get_id());

		}

		// get the new item
		$article = Anchors::get('article:'.$_REQUEST['id']);

		// page title
		$context['page_title'] = i18n::s('Thank you for your contribution');

		// the page has been recorded
		$context['text'] .= i18n::s('<p>A new page has been created with submitted data. You can use its permanent address at any time to review or complement it.</p>');

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array($article->get_url() => i18n::s('View the page')));
		if(Surfer::may_upload()) {
			$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('article:'.$_REQUEST['id']) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('files/edit.php?anchor='.urlencode('article:'.$_REQUEST['id']) => i18n::s('Upload a file')));
		}
		$menu = array_merge($menu, array('links/edit.php?anchor='.urlencode('article:'.$_REQUEST['id']) => i18n::s('Add a link')));
		if(Surfer::get_email_address() && isset($context['with_email']) && ($context['with_email'] == 'Y'))
			$menu = array_merge($menu, array(Articles::get_url($_REQUEST['id'], 'mail') => i18n::s('Invite people')));
		$follow_up .= Skin::build_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// log the creation of a new page
		$label = sprintf(i18n::c('New page: %s'), strip_tags($article->get_title()));

		// poster and target section
		$description = sprintf(i18n::s('Sent by %s in %s'), Surfer::get_name(), $anchor->get_title());

		// title and link
		$description .= "\n\n".$context['url_to_home'].$context['url_to_root'].$article->get_url()."\n\n";

		// notify sysops
		Logger::notify('forms/view.php', $label, $description);

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// initialize the rendering engine
	Codes::initialize(Forms::get_url($item['id'], 'view', $item['title']));

	// provide details only to associates
	if(Surfer::is_associate()) {
		$details = array();

		// the nick name
		if($item['nick_name'])
			$details[] = '"'.$item['nick_name'].'"';

		// information on last update
		if($item['edit_name'])
			$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

		// restricted to logged members
		if($item['active'] == 'R')
			$details[] = RESTRICTED_FLAG.' '.i18n::s('Access is restricted to authenticated members').BR."\n";

		// restricted to associates
		elseif($item['active'] == 'N')
			$details[] = PRIVATE_FLAG.' '.i18n::s('Access is restricted to associates').BR."\n";

		// all details
		if(@count($details))
			$context['page_details'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

	}

	// use the cache if possible
	$cache_id = 'forms/view.php?id='.$item['id'].'#content';
	if(!$text =& Cache::get($cache_id)) {

		// show the description
		if($item['description'])
			$text .= '<div class="description">'.Codes::beautify($item['description'])."</div>\n";

		// the form
		$text .= '<form method="post" enctype="multipart/form-data" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

		// form fields
		$fields = array();

		// transmit the id as a hidden field
		$text .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

		// additional fields for anonymous surfers
		if(!Surfer::is_logged()) {

			// splash
			if(isset($item['id']))
				$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('forms/view.php?id='.$item['id']);
			elseif(is_object($anchor))
				$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('forms/view.php?anchor='.$anchor->get_reference());
			else
				$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('forms/view.php');
			$text .= '<p>'.sprintf(i18n::s('If you have previously registered to this site, please %s. Then the server will automatically put your name and address in following fields.'), Skin::build_link($login_url, 'authenticate'))."</p>\n";

			// the name, if any
			$label = i18n::s('Your name').' *';
			$input = '<input type="text" name="edit_name" size="45" maxlength="128" accesskey="n" value="'.encode_field(Surfer::get_name()).'" />';
			$hint = i18n::s('Let us a chance to know who you are');
			$fields[] = array($label, $input, $hint);

			// the address, if any
			$label = i18n::s('Your e-mail address');
			$input = '<input type="text" name="edit_address" size="45" maxlength="128" accesskey="a" value="'.encode_field(Surfer::get_email_address()).'" />';
			$hint = i18n::s('Put your e-mail address to be alerted on surfer reactions');
			$fields[] = array($label, $input, $hint);

			// stop robots
			if($field = Surfer::get_robot_stopper())
				$fields[] = $field;

			// we are now entering regular fields
			$text .= Skin::build_form($fields);
			$fields = array();

		}

		// build the user form
		if($item['content']) {

			// list all fields in sequence
			$fields = Safe::json_decode($item['content']);

			foreach($fields as $field) {

				// sanity check
				if(!isset($field['class']))
					continue;

				switch($field['class']) {

				// upload a file
				case 'file':
					if(Surfer::may_upload())
						$text .= '<input type="file" name="'.encode_field($field['name']).'" size="45" maxlength="255" />';
					break;

				// insert a field to get some text
				case 'label':
					if(isset($field['type']) && ($field['type'] == 'title'))
						$text .= Skin::build_block($field['text'], 'title')."\n";
					elseif(isset($field['type']) && ($field['type'] == 'subtitle'))
						$text .= Skin::build_block($field['text'], 'subtitle')."\n";
					else
						$text .= '<div>'.$field['text'].'</div>';
					break;

				// insert a field to select among several options
				case 'list':
					$options = array();
					$lines = explode("\n", $field['text']);
					foreach($lines as $line) {
						if(preg_match('!/(.+?)/\s*(.+)$!', $line, $matches))
							$options[] = array($matches[1], $matches[2]);
					}
					if(isset($field['type']) && ($field['type'] == 'drop')) {
						$text .= '<select name="'.encode_field($field['name']).'">'."\n";
						foreach($options as $option) {
							$text .= '<option value="'.encode_field($option[0]).'"> '.$option[1]."</option>\n";
						}
						$text .= '</select>'."\n";
					} elseif(isset($field['type']) && ($field['type'] == 'check')) {
						foreach($options as $option) {
							$text .= '<input type="checkbox" name="'.encode_field($field['name']).'[]" value="'.encode_field($option[0]).'" /> '.$option[1].BR."\n";
						}
					} else {
						foreach($options as $option) {
							$text .= '<input type="radio" name="'.encode_field($field['name']).'" value="'.encode_field($option[0]).'" /> '.$option[1].BR."\n";
						}
					}
					break;

				// display some text
				case 'text':
					if(isset($field['type']) && ($field['type'] == 'textarea'))
						$text .= '<textarea name="'.encode_field($field['name']).'" rows="7" cols="50"></textarea>';
					elseif(isset($field['type']) && ($field['type'] == 'password'))
						$text .= '<input type="password" name="'.encode_field($field['name']).'" size="45" maxlength="255" />';
					else
						$text .= '<input type="text" name="'.encode_field($field['name']).'" size="45" maxlength="255" />';
					break;
				}
			}
		}

		// bottom commands
		$menu = array();
		$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');
		if(Surfer::is_associate()) {
			$menu[] = Skin::build_link(Forms::get_url($item['id'], 'edit'), i18n::s('Edit'), 'span');
			$menu[] = Skin::build_link(Forms::get_url($item['id'], 'delete'), i18n::s('Delete'), 'span');
		}
		$menu[] = Skin::build_link('forms/', i18n::s('Cancel'), 'span');
		$text .= Skin::finalize_list($menu, 'assistant_bar');

		// end of the form
		$text .= '</div></form>';

		// the script used for form handling at the browser
		$context['page_footer'] .= '<script type="text/javascript">// <![CDATA['."\n"
			.'// check that main fields are not empty'."\n"
			.'func'.'tion validateDocumentPost(container) {'."\n"
			."\n"
			.'	// title is mandatory'."\n"
//					.'	if(!container.title.value) {'."\n"
//					.'		alert("'.i18n::s('Please provide a meaningful title.').'");'."\n"
//					.'		Yacs.stopWorking();'."\n"
//					.'		return false;'."\n"
//					.'	}'."\n"
			."\n"
			.'	// successful check'."\n"
			.'	return true;'."\n"
			.'}'."\n"
			."\n"
			.'// ]]></script>'."\n";

		// save in cache
		Cache::put($cache_id, $text, 'form:'.$item['id']);
	}
	$context['text'] .= $text;

	// referrals, if any
	if(Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y'))) {

		$cache_id = 'forms/view.php?id='.$item['id'].'#referrals#';
		if(!$text =& Cache::get($cache_id)) {

			// box content in a sidebar box
			include_once '../agents/referrals.php';
			if($text = Referrals::list_by_hits_for_url($context['url_to_root_parameter'].Forms::get_url($item['id'])))
				$text =& Skin::build_box(i18n::s('Referrals'), $text, 'navigation', 'referrals');

			// save in cache for one hour 60 * 60 = 3600
			Cache::put($cache_id, $text, 'referrals', 3600);

		}

		// in the extra panel
		$context['extra'] .= $text;
	}
}

// render the skin
render_skin();

?>