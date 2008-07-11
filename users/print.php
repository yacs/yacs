<?php
/**
 * print one user profile
 *
 * Restrictions apply on this page:
 * - associates are allowed to move forward
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - print.php/12
 * - print.php?id=12
 *
 * @author Bernard Paques
 * @author GnapZ
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
load_skin('print');

// the title of the page
if($item['nick_name'])
	$context['page_title'] = $item['nick_name'];
elseif($item['full_name'])
	$context['page_title'] = $item['full_name'];

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Users::get_url($item['id'], 'print')));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// display the user profile
} else {

	// the user icon, if any
	$context['page_image'] = $item['avatar_url'];

	// details
	$details = array();

	// the number of posts
	if($item['posts'] > 1)
		$details[] = sprintf(i18n::s('%d posts'), $item['posts']);

	// the date of registration
	if($item['create_date'])
		$details[] = sprintf(i18n::s('Registered %s'), Skin::build_date($item['create_date']));

	// the capability field is displayed only to logged users
	if(!Surfer::is_logged())
		;
	elseif($item['capability'] == 'A')
		$details[] = i18n::s('As an associate of this community, this user has unlimited rights (and duties) on this server.');

	elseif($item['capability'] == 'M')
		$details[] = i18n::s('Member of this community, with contribution rights to this server.');

	elseif($item['capability'] == 'S')
		$details[] = i18n::s('Subscriber of this community, allowed to browse public pages and to receive e-mail newsletters.');

	// warns associates if not active
	if(($item['active'] != 'Y') && Surfer::is_associate()) {
		if($item['active'] == 'R')
			$details[] = i18n::s('Access is restricted to authenticated members.');
		else
			$details[] = i18n::s('Access is restricted to associates.');
	}

	// provide details
	if(count($details))
		$context['text'] .= '<p class="details">'.implode(BR."\n", $details).'</p>';

	// the full name
	if($item['full_name'])
		$context['text'] .= '<p>'.sprintf(i18n::s('Full name: %s'), $item['full_name'])."</p>\n";

	// web address, if any
	if(isset($item['web_address']) && $item['web_address'])
		$context['text'] .= '<p>'.sprintf(i18n::s('Web address: %s'), Skin::build_link($item['web_address'], $item['web_address'], 'external'))."</p>\n";

	// email address - not showed to anonymous surfers for spam protection
	if(isset($item['email']) && $item['email'] && Surfer::may_mail()) {
		$label = i18n::s('E-mail address: %s %s');

		if(isset($context['with_email']) && ($context['with_email'] == 'Y'))
			$url = Users::get_url($id, 'mail');
		else
			$url = 'mailto:'.$item['email'];

		if(isset($item['with_newsletters']) && ($item['with_newsletters'] == 'Y'))
			$suffix = '';
		else
			$suffix = i18n::s('(do not wish to receive newsletters)');

		$context['text'] .= '<p>'.sprintf(i18n::s($label), Skin::build_link($url, $item['email'], 'email'), $suffix)."</p>\n";
	}

	// the introduction text
	if($item['introduction'])
		$context['text'] .= Skin::build_block($item['introduction'], 'introduction');

	// the beautified description, which is the actual page body
	if($item['description'])
		$context['text'] .= '<div class="description">'.Codes::beautify($item['description'])."</div>\n";

	//
	// the files section
	//

	// title
	$section = Skin::build_block(i18n::s('Files'), 'title');

	// list files by date
	include_once '../files/files.php';
	$items = Files::list_by_date_for_anchor('user:'.$item['id'], 0, 50, 'compact');

	// actually render the html for the section
	if($items)
		$context['text'] .= $section.Skin::build_list($items, 'compact');

	//
	// the links section
	//

	// title
	$section = Skin::build_block(i18n::s('See also'), 'title');

	// list links by date
	include_once '../links/links.php';
	if(preg_match('/\blinks_by_title\b/i', $item['options']))
		$items = Links::list_by_title_for_anchor('user:'.$item['id'], 0, 20, 'no_author');
	else
		$items = Links::list_by_date_for_anchor('user:'.$item['id'], 0, 20, 'no_author');

	// actually render the html
	if($items)
		$context['text'] .= $section.Skin::build_list($items, 'compact');

	//
	// the articles section
	//

	// title
	$section = Skin::build_block(i18n::s('Recent pages'), 'title');

	// list articles by date
	$items = Articles::list_by_date_for_author($item['id'], 0, 50, 'compact');

	// actually render the html
	if($items)
		$context['text'] .= $section.Skin::build_list($items, 'compact');

	//
	// watch list
	//

	// list tracked articles by date
	if($items = Members::list_articles_by_date_for_member('user:'.$item['id'], 0, 20, 'compact'))
		$context['text'] .= Skin::build_box(i18n::s('Dashboard'), Skin::build_list($items, 'compact'));

}

// render the skin
render_skin();

?>