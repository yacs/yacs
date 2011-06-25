<?php
/**
 * capture form data
 *
 * @todo display shortcut based on nick name at page bottom
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
	$anchor =& Anchors::get($item['anchor']);

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

// current item
if(isset($item['id']))
	$context['current_item'] = 'form:'.$item['id'];

// the path to this page
$context['path_bar'] = array( 'forms/' => i18n::s('Forms') );

// the title of the page
if($item['title'])
	$context['page_title'] = $item['title'];

// no form yet
$with_form = FALSE;

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	include '../error.php';

// no anchor
}elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Forms::get_url($item['id'], 'view', $item['title'])));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// save this at the right place
	$_REQUEST['anchor'] = $anchor->get_reference();

	// protect from hackers
	if(isset($_REQUEST['edit_name']))
		$_REQUEST['edit_name'] = preg_replace(FORBIDDEN_IN_NAMES, '_', $_REQUEST['edit_name']);
	if(isset($_REQUEST['edit_address']))
		$_REQUEST['edit_address'] =& encode_link($_REQUEST['edit_address']);

	// let define a new id
	unset($_REQUEST['id']);

	// make a title for the new page
	if(!isset($_REQUEST['title']) || !$_REQUEST['title'])
		$_REQUEST['title'] = sprintf('%s - %s', Surfer::get_name(), $item['title']);

	// always publish the page
	$_REQUEST['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');

	// do not re-use form description
	$_REQUEST['description'] = '';

	// basic attributes
	$attributes = array();
	$attributes[] = array(i18n::c('Name') => Surfer::get_name());
	$attributes[] = array(i18n::c('Email') => Surfer::get_email_address());

	// parse the form
	$text = '';
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
					$_FILES[$field['name']]['name'] = utf8::encode($_FILES[$field['name']]['name']);

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
		Logger::error(i18n::s('Please prove you are not a robot.'));
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
						Logger::error(i18n::s('The size of this file is over limit.'));

					// size exceeds form limit -- UPLOAD_ERR_FORM_SIZE
					elseif(isset($_FILES[$field['name']]['error']) && ($_FILES[$field['name']]['error'] == 2))
						Logger::error(i18n::s('The size of this file is over limit.'));

					// partial transfer -- UPLOAD_ERR_PARTIAL
					elseif(isset($_FILES[$field['name']]['error']) && ($_FILES[$field['name']]['error'] == 3))
						Logger::error(i18n::s('No file has been transmitted.'));

					// no file -- UPLOAD_ERR_NO_FILE
					elseif(isset($_FILES[$field['name']]['error']) && ($_FILES[$field['name']]['error'] == 4))
						Logger::error(i18n::s('No file has been transmitted.'));

					// zero bytes transmitted
					elseif(!$_FILES[$field['name']]['size'])
						Logger::error(i18n::s('No file has been transmitted.'));

					// check provided upload name
					elseif(!Safe::is_uploaded_file($_FILES[$field['name']]['tmp_name']))
						Logger::error(i18n::s('Possible file attack.'));

					// move the uploaded file
					elseif(!Safe::move_uploaded_file($_FILES[$field['name']]['tmp_name'], $file_path.$file_name))
						Logger::error(sprintf(i18n::s('Impossible to move the upload file to %s.'), $file_path.$file_name));

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

		// if poster is a registered user
		if(Surfer::get_id()) {

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// add this page to watch list
			Members::assign('article:'.$_REQUEST['id'], 'user:'.Surfer::get_id());

		}

		// get the new item
		$article =& Anchors::get('article:'.$_REQUEST['id']);

		// page title
		$context['page_title'] = i18n::s('Thank you for your contribution');

		// the page has been recorded
		$context['text'] .= '<p>'.i18n::s('A new page has been created with submitted data. This will be reviewed by people in charge.').'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		if(!$anchor->is_viewable())
			$menu = array_merge($menu, array($context['url_to_root'] => i18n::s('Front page')));
		else
			$menu = array_merge($menu, array($article->get_url() => i18n::s('View the page')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// log the creation of a new page
		$label = sprintf(i18n::c('New page: %s'), strip_tags($article->get_title()));

		// poster and target section
		$description = sprintf(i18n::s('Sent by %s in %s'), Surfer::get_name(), $anchor->get_title());

		// title and link
		$link = $context['url_to_home'].$context['url_to_root'].$article->get_url();
                $description .= "\n\n".'<a href="'.$link.'">'.$link.'</a>'."\n\n";

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
			$details[] = RESTRICTED_FLAG.' '.i18n::s('Community - Access is granted to any identified surfer').BR."\n";

		// restricted to associates
		elseif($item['active'] == 'N')
			$details[] = PRIVATE_FLAG.' '.i18n::s('Private - Access is restricted to selected persons').BR."\n";

		// all details
		if(@count($details))
			$context['page_details'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

	}


	// show the description
	$context['text'] .= Skin::build_block($item['description'], 'description');

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form" enctype="multipart/form-data"><div>';

	// form fields
	$fields = array();

	// transmit the id as a hidden field
	$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// additional fields for anonymous surfers
	if(!Surfer::is_logged()) {

		// splash
		if(isset($item['id']))
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('forms/view.php?id='.$item['id']);
		elseif(is_object($anchor))
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('forms/view.php?anchor='.$anchor->get_reference());
		else
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('forms/view.php');
		$context['text'] .= '<p>'.sprintf(i18n::s('If you have previously registered to this site, please %s. Then the server will automatically put your name and address in following fields.'), Skin::build_link($login_url, i18n::s('authenticate')))."</p>\n";

		// the name, if any
		$label = i18n::s('Your name');
		$input = '<input type="text" name="edit_name" size="45" maxlength="128" accesskey="n" value="'.encode_field(Surfer::get_name(' ')).'" />';
		$hint = i18n::s('Let us a chance to know who you are');
		$fields[] = array($label, $input, $hint);

		// the address, if any
		$label = i18n::s('Your e-mail address');
		$input = '<input type="text" name="edit_address" size="45" maxlength="128" accesskey="a" value="'.encode_field(Surfer::get_email_address()).'" />';
		$hint = i18n::s('Put your e-mail address to receive feed-back');
		$fields[] = array($label, $input, $hint);

		// stop robots
		if($field = Surfer::get_robot_stopper())
			$fields[] = $field;

	} else {

		// the name, if any
		$label = i18n::s('Your name');
		$fields[] = array($label, Surfer::get_name());

		// the address, if any
		if($value = Surfer::get_email_address()) {
			$label = i18n::s('Your e-mail address');
			$fields[] = array($label, $value);
		}

	}

	// we are now entering regular fields
	$context['text'] .= Skin::build_form($fields);
	$fields = array();

	// the title
	$title = sprintf('%s - %s', Surfer::get_name(i18n::s('Your name')), $item['title']);
	$context['text'] .= Skin::build_block(i18n::s('Title'), 'subtitle')."\n"
		.'<textarea name="title" id="title" rows="2" cols="50" accesskey="t">'.$title.'</textarea>'
		.BR.'<span class="tiny">'.i18n::s('Please provide a meaningful title.').'</span>'.BR;


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
					$context['text'] .= '<input type="file" name="'.encode_field($field['name']).'" size="45" maxlength="255" />';
				break;

			// insert a field to get some text
			case 'label':
				if(isset($field['type']) && ($field['type'] == 'title'))
					$context['text'] .= Skin::build_block($field['text'], 'title')."\n";
				elseif(isset($field['type']) && ($field['type'] == 'subtitle'))
					$context['text'] .= Skin::build_block($field['text'], 'subtitle')."\n";
				else
					$context['text'] .= '<div>'.$field['text'].'</div>';
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
					$context['text'] .= '<select name="'.encode_field($field['name']).'">'."\n";
					foreach($options as $option) {
						$context['text'] .= '<option value="'.encode_field($option[0]).'"> '.$option[1]."</option>\n";
					}
					$context['text'] .= '</select>'."\n";
				} elseif(isset($field['type']) && ($field['type'] == 'check')) {
					foreach($options as $option) {
						$context['text'] .= '<input type="checkbox" name="'.encode_field($field['name']).'[]" value="'.encode_field($option[0]).'" /> '.$option[1].BR."\n";
					}
				} else {
					foreach($options as $option) {
						$context['text'] .= '<input type="radio" name="'.encode_field($field['name']).'" value="'.encode_field($option[0]).'" /> '.$option[1].BR."\n";
					}
				}
				break;

			// display some text
			case 'text':
				if(isset($field['type']) && ($field['type'] == 'textarea'))
					$context['text'] .= '<textarea name="'.encode_field($field['name']).'" rows="7" cols="50"></textarea>';
				elseif(isset($field['type']) && ($field['type'] == 'password'))
					$context['text'] .= '<input type="password" name="'.encode_field($field['name']).'" size="45" maxlength="255" />';
				else
					$context['text'] .= '<input type="text" name="'.encode_field($field['name']).'" size="45" maxlength="255" />';
				break;
			}
		}
	}

	// bottom commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');
	$menu[] = Skin::build_link('forms/', i18n::s('Cancel'), 'span');
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	$context['page_footer'] .= JS_PREFIX
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// title is mandatory'."\n"
//		.'	if(!container.title.value) {'."\n"
//		.'		alert("'.i18n::s('Please provide a meaningful title.').'");'."\n"
//		.'		Yacs.stopWorking();'."\n"
//		.'		return false;'."\n"
//		.'	}'."\n"
		."\n"
		.'	// successful check'."\n"
		.'	return true;'."\n"
		.'}'."\n"
		."\n"
		.JS_SUFFIX."\n";

	// page tools
	//
	if(Surfer::is_associate()) {
		$context['page_tools'][] = Skin::build_link(Forms::get_url($item['id'], 'edit'), i18n::s('Edit'));
		$context['page_tools'][] = Skin::build_link(Forms::get_url($item['id'], 'delete'), i18n::s('Delete'));
	}

	// referrals, if any
	$context['components']['referrals'] =& Skin::build_referrals(Forms::get_url($item['id']));
}

// render the skin
render_skin();

?>
