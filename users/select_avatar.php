<?php
/**
 * assign an icon to a user from the avatar library
 *
 * The avatar library is the directory '[code]skins/images/avatars[/code]' under the YACS installation directory.
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
	$context['page_title'] = sprintf(i18n::s('Select an avatar for %s'), $item['nick_name']);

// command to go back
if(isset($item['id']))
	$context['page_menu'] = array( Users::get_url($item['id'], 'view', $item['nick_name']) => sprintf(i18n::s('Back to %s'), $item['nick_name']) );

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// anonymous users are invited to log in or to register
} elseif(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('users/select_avatar.php'));

// not found
elseif(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

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
	$context['text'] .= '<p>'.sprintf(i18n::s('Current avatar: %s'), BR.'<img src="'.$item['avatar_url'].'" alt="avatar" style="avatar"'.EOT).'</p>'."\n";

// list available avatars, except on error
if(!count($context['error'])) {

	// save on network queries to gravatar.com
	$cache_id = 'users/select_avatar.php?id='.$item['id'].'#gravatar';
	if(!$text =& Cache::get($cache_id)) {

		// display the current gravatar, if any
		if(isset($item['email']) && $item['email']) {

			// the gravatar url
			$url = 'http://www.gravatar.com/avatar.php?gravatar_id='.md5($item['email']);

			// it is already in use
			if(isset($item['avatar_url']) && ($url == $item['avatar_url']))
				$text .= '<p>'.sprintf(i18n::s('Your are using your %s as current avatar.'), Skin::build_link('http://www.gravatar.com/', i18n::s('gravatar'), 'external')).'</p>'."\n";

			// show it
			else
				$text .= '<p>'.sprintf(i18n::s('I have a %s and %s'), Skin::build_link('http://www.gravatar.com/', i18n::s('gravatar'), 'external'), '<a href="'.$context['url_to_root'].'users/select_avatar.php?id='.$id.'&avatar='.urlencode($url).'">'.i18n::s('would like to use it').'</a>')
					.'</p>'."\n";

		}

		// put in cache
		Cache::put($cache_id, $text, 'user:'.$item['id']);
	}
	$context['text'] .= $text;

	// save on directory browsing
	$cache_id = 'users/select_avatar.php?id='.$item['id'].'#library';
	if(!$text =& Cache::get($cache_id)) {

		// where images are
		$path = 'skins/images/avatars';

		// browse the path to list directories and files
		if(!$dir = Safe::opendir($context['path_to_root'].$path))
			Skin::error(sprintf(i18n::s('The directory %s does not exist.'), $path));

		// list images
		else {

			if(Surfer::may_upload())
				$text .= '<p>'.sprintf(i18n::s('Click on one image below to make it your new avatar. Instead of using the library you may prefer to %s.'), Skin::build_link('images/edit.php?anchor=user:'.$item['id'].'&amp;action=avatar', i18n::s('upload your own avatar'), 'shortcut')).'</p>'."\n";

			// build the lists
			while(($item = Safe::readdir($dir)) !== FALSE) {

				// skip some files
				if($item == '.' || $item == '..')
					continue;

				if(is_dir($context['path_to_root'].$path.'/'.$item))
					continue;

				// consider only images
				if(!preg_match('/(\.gif|\.jpeg|\.jpg|\.png)$/i', $item))
					continue;

				// make clickable images
				$text .= ' <a href="'.$context['url_to_root'].'users/select_avatar.php?id='.$id.'&avatar='.urlencode($context['url_to_root'].$path.'/'.$item).'">'
					.'<img src="'.$context['url_to_root'].$path.'/'.$item.'" alt="'.$item.'" style="padding: 4px 4px 4px 4px;"'.EOT.'</a> ';

			}
			Safe::closedir($dir);
		}

		// put in cache for one hour
		Cache::put($cache_id, $text, 'path:'.$path, 3600);
	}
	$context['text'] .= $text;

}

// render the skin
render_skin();

?>