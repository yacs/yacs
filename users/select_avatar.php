<?php
/**
 * assign an icon to a user from the avatar library
 *
 * The avatar library is the directory '[code]skins/_reference/avatars[/code]' under the YACS installation directory.
 * Upload some files through FTP there, and they will become useful resources for all members of your community.
 *
 * If there is a gravatar it is displayed as well.
 * On click the gravatar becomes the avatar for the current user profile.
 *
 * @link http://www.gravatar.com/ A gravatar is a globally recognized avatar
 *
 * Restrictions apply on this page:
 * - associates are allowed to move forward
 * - this is the record of the authenticated surfer
 * - permission denied is the default
 *
 * Accept following invocations:
 * - users/select_avatar.php/12
 * - users/select_avatar.php?id=12
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Kedare
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../images/images.php';

// the maximum size for uploads
$image_maximum_size = str_replace('M', '000000', Safe::get_cfg_var('upload_max_filesize'));
if((!$image_maximum_size) || $image_maximum_size > 20000000)
	$image_maximum_size = 2000000;

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
elseif(Surfer::is_logged())
	$id = Surfer::get_id();
$id = strip_tags($id);

// get the item from the database
$item =& Users::get($id);

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// the record of the authenticated surfer
elseif(isset($item['id']) && Surfer::is($item['id']))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );

// the title of the page
if(isset($item['nick_name']))
	$context['page_title'] = sprintf(i18n::s('Select a picture for %s'), $item['nick_name']);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// anonymous users are invited to log in or to register
} elseif(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('users/select_avatar.php'));

// not found
elseif(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// the avatar has been changed
} elseif(isset($_REQUEST['avatar'])) {

	// change the avatar in the profile
	$item['avatar_url'] = $_REQUEST['avatar'];

	// no password change
	unset($item['password']);

	if(Users::put($item))
		Users::clear($item);
}

// the current avatar, if any
if(isset($item['avatar_url']) && $item['avatar_url'])
	$context['text'] .= '<p>'.sprintf(i18n::s('Current picture: %s'), BR.'<img src="'.$item['avatar_url'].'" alt="avatar" style="avatar" />').'</p>'."\n";
else
	$context['text'] .= '<p>'.i18n::s('No picture has been set for this profile.').'</p>';

// list available avatars, except on error
if(!count($context['error']) && isset($item['id'])) {

	// upload an image
	//
	if(Images::are_allowed(NULL, $item, 'user')) {

		// the form to post an image
		$text = '<form method="post" enctype="multipart/form-data" action="'.$context['url_to_root'].'images/edit.php" id="main_form"><div>'
			.'<input type="hidden" name="anchor" value="user:'.$item['id'].'" />'
			.'<input type="hidden" name="action" value="set_as_avatar" />';

		$fields = array();

		// the image
		$text .= '<input type="file" name="upload" id="upload" size="30" accesskey="i" title="'.encode_field(i18n::s('Press to select a local file')).'" />';
		$text .= ' '.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');
		$text .= BR.'<span class="details">'.i18n::s('Select a .png, .gif or .jpeg image.').' (&lt;&nbsp;'.Skin::build_number($image_maximum_size, i18n::s('bytes')).')</span>';

		// end of the form
		$text .= '</div></form>';

		// the script used for form handling at the browser
		$text .= JS_PREFIX
			.'// set the focus on first form field'."\n"
			.'$("upload").focus();'."\n"
			.JS_SUFFIX."\n";


		$context['text'] .= Skin::build_content(NULL, i18n::s('Upload an image'), $text);
	}

	// use the library
	//

	// where images are
	$path = 'skins/_reference/avatars';

	// browse the path to list directories and files
	if($dir = Safe::opendir($context['path_to_root'].$path)) {
		$text = '';

		if(Surfer::may_upload())
			$text .= '<p>'.i18n::s('Click on one image below to make it your new picture.').'</p>'."\n";

		// build the lists
		while(($image = Safe::readdir($dir)) !== FALSE) {

			// skip some files
			if($image[0] == '.')
				continue;

			if(is_dir($context['path_to_root'].$path.'/'.$image))
				continue;

			// consider only images
			if(!preg_match('/(\.gif|\.jpeg|\.jpg|\.png)$/i', $image))
				continue;

			// make clickable images
			$text .= ' <a href="'.$context['url_to_root'].'users/select_avatar.php?id='.$id.'&avatar='.urlencode($context['url_to_root'].$path.'/'.$image).'">'
				.'<img src="'.$context['url_to_root'].$path.'/'.$image.'" alt="'.$image.'" style="padding: 4px 4px 4px 4px;" /></a> ';

		}
		Safe::closedir($dir);
	}

	if($text)
		$context['text'] .= Skin::build_content(NULL, i18n::s('Use the library'), $text);

	// display the current gravatar, if any
	if(isset($item['email']) && $item['email']) {
		$text = '';

		// the gravatar url
		$url = 'http://www.gravatar.com/avatar.php?gravatar_id='.md5($item['email']);

		// it is already in use
		if(isset($item['avatar_url']) && ($url == $item['avatar_url']))
			$text .= '<p>'.sprintf(i18n::s('Your are using your %s as current picture.'), Skin::build_link('http://www.gravatar.com/', i18n::s('gravatar'), 'external')).'</p>'."\n";

		// show it
		else
			$text .= '<p>'.sprintf(i18n::s('I have a %s and %s'), Skin::build_link('http://www.gravatar.com/', i18n::s('gravatar'), 'external'), '<a href="'.$context['url_to_root'].'users/select_avatar.php?id='.$id.'&avatar='.urlencode($url).'">'.i18n::s('would like to use it').'</a>')
				.'</p>'."\n";

		$context['text'] .= Skin::build_content(NULL, i18n::s('Use a gravatar'), $text);
	}

	//
	// bottom commands
	//
	$menu = array();
	$menu[] = Skin::build_link(Users::get_permalink($item), i18n::s('Done'), 'button');
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

}

// render the skin
render_skin();

?>