<?php
/**
 * display one version
 *
 * If several versions have been posted to a single anchor, a navigation bar will be built to jump
 * directly to previous and next neighbours.
 * This is displayed as a sidebar box in the extra panel.
 *
 * The extra panel also features top popular referrals in a sidebar box, if applicable.
 *
 * Only anchor owners can proceed
 *
 * Accept following invocations:
 * - view.php/12
 * - view.php?id=12
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'versions.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Versions::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// you have to own the object to handle versions
if(is_object($anchor) && $anchor->is_owned())
	$permitted = TRUE;

// editors of parent anchor can do it also
elseif(is_object($anchor) && ($anchor->get_type() == 'section') && Sections::is_owned(NULL, $anchor))
	$permitted = TRUE;

// the default is to deny access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('versions', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// current item
if(isset($item['id']))
	$context['current_item'] = 'version:'.$item['id'];

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();

// page title
if(is_object($anchor)) {
	$context['page_title'] = $anchor->get_title();

	// add revision date and author
	if(isset($item['edit_date']))
		$context['page_title'] .= ' ['.Skin::build_date($item['edit_date']).']';
}

// not found -- help web crawlers
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Versions::get_url($item['id'])));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// re-enforce the canonical link
} elseif($context['self_url'] && ($canonical = $context['url_to_home'].$context['url_to_root'].Versions::get_url($item['id'])) && strncmp($context['self_url'], $canonical, strlen($canonical))) {
	Safe::header('Status: 301 Moved Permanently', TRUE, 301);
	Safe::header('Location: '.$canonical);
	Logger::error(Skin::build_link($canonical));

// display the version
} else {

	// display details for this version
	$context['text'] .= '<dl class="version">'."\n";
	if(($attributes = Safe::unserialize($item['content'])) && @count($attributes)) {

		$fields = array( 'title' => array(i18n::s('Title'), '', FALSE),
			'introduction' => array('', '', TRUE),
			'overlay' => array('', '', FALSE, i18n::s('Content of the overlay has changed')),
			'description' => array('', '', TRUE),
			'tags' => array(i18n::s('Tags'), '', TRUE),
			'trailer' => array(i18n::s('Trailer'), BR, FALSE),
			'extra' => array(i18n::s('Extra'), BR, FALSE) );

		foreach($fields as $name => $params) {

			if(isset($attributes[ $name ])) {
				$compared = $anchor->diff($name, $attributes[ $name ]);
				if($params[2] || strcmp($compared, $attributes[ $name ])) {

					// use a constant string instead of showing differences
					if(isset($params[3]))
						$compared = '<ins>'.$params[3].'</ins>';

					if($params[0])
						$context['text'] .= '<div style="margin-bottom: 1em;">'.sprintf(i18n::s('%s: %s'), $params[0], $params[1].$compared).'</div>';
					else
						$context['text'] .= '<div style="margin-bottom: 1em;">'.$params[1].$compared.'</div>';
				}
			}
		}

// 		$rows = array();
// 		foreach($attributes as $name => $value) {
// 			if(is_string($value) && $value && preg_match('/(active|anchor|locked|rank|title)$/', $name))
// 				$rows[] = array($name, $value);
// 		}
// 		if($rows)
// 			$context['text'] .= Skin::table(NULL, $rows);
	}

	// back to the anchor page
	$links = array();
	if((is_object($anchor) && $anchor->is_assigned()))
		$links[] = Skin::build_link(Versions::get_url($anchor->get_reference(), 'list'), i18n::s('Versions'), 'button');
	if($item['id'] && (Surfer::is_associate()
		|| (Surfer::is_member() && is_object($anchor) && $anchor->is_assigned())))
		$links[] = Skin::build_link(Versions::get_url($item['id'], 'restore'), i18n::s('Restore this version'), 'span', i18n::s('Caution: restoration can not be reversed!'));
	$context['text'] .= Skin::finalize_list($links, 'assistant_bar');

	// page help
	$help = '';

	// information to members
	if(Surfer::is_member())
		$help .= '<p>'.sprintf(i18n::s('This has been posted by %s %s.'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']))."</p>\n";

	$help .= '<p><ins>'.i18n::s('Text inserted since that version.').'</ins></p>'
		.'<p><del>'.i18n::s('Text suppressed from this version.').'</del></p>'
		.'<p>'.i18n::s('Caution: restoration can not be reversed!').'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

	//
	// the navigation sidebar
	//
	$text = '';

	// buttons to display previous and next pages, if any
	if(is_object($anchor)) {
		$neighbours = $anchor->get_neighbours('version', $item);
		$text .= Skin::neighbours($neighbours, 'sidebar');
	}

	// build a nice sidebar box
	if($text)
		$text =& Skin::build_box(i18n::s('Navigation'), $text, 'neighbours', 'neighbours');

	$context['components']['neighbours'] = $text;

	//
	// referrals, if any, in a sidebar
	//
	$context['components']['referrals'] =& Skin::build_referrals(Versions::get_url($item['id']));
}

// render the skin
render_skin();

?>
