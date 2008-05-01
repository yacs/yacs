<?php
/**
 * publish an article
 *
 * @todo allow authors to publish their drafts in sections with option for auto-publication (Lasares)
 *
 * Publishing an article means that the surfer takes the ownership of the publication. Therefore,
 * his/her name is registered in the database with the publishing date.
 *
 * Various publishing options can be set as well.
 * Then the actual publication takes place on confirmation.
 *
 * One option is to trackback links embedded into the article. Links are also extracted and inserted in the database separately.
 *
 * This script explicitly pings server profiles that have been configured for this purpose,
 * but only on following conditions:
 * - the section is public (see [script]sections/sections.php[/script])
 * - the page has not been flagged to not appear at the front page
 * - the page is active (not restricted nor hidden)
 *
 * This page is to be used by associates and editors only, while they are reviewing queued articles.
 *
 * Accept following invocations:
 * - publish.php/12
 * - publish.php?id=12
 *
 * If this article, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @tester Olivier
 * @tester Pat
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
$id = strip_tags($id);

// get the item from the database
$item =& Articles::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']))
	$anchor = Anchors::get($item['anchor']);

// do not always show the edition form
$with_form = FALSE;

// load the skin, maybe with a variant
load_skin('articles', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'articles/' => 'All pages' );
if(isset($item['id']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']) => $item['title']));

// page title
$context['page_title'] = i18n::s('Publish a page');

// not found
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// publication is restricted to associates and editors
} elseif(!Surfer::is_associate() && (!Surfer::is_member() || !is_object($anchor) || !$anchor->is_editable())) {

	// anonymous users are invited to log in
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_url($item['id'], 'publish')));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// page has already been published
} elseif(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE)) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// publication is confirmed
} elseif(isset($_REQUEST['publish_date']) && ($_REQUEST['publish_date'] > NULL_DATE)) {

	// convert dates from surfer time zone to UTC time zone
 	$_REQUEST['publish_date'] = Surfer::to_GMT($_REQUEST['publish_date']);
	if(isset($_REQUEST['expiry_date']) && ($_REQUEST['expiry_date'] > NULL_DATE))
	 	$_REQUEST['expiry_date'] = Surfer::to_GMT($_REQUEST['expiry_date']);

	// update the database
	if($error = Articles::stamp($item['id'], $_REQUEST['publish_date'], isset($_REQUEST['expiry_date']) ? $_REQUEST['expiry_date'] : ''))
		Skin::error($error);

	// post-processing tasks
	else {

		// touch the related anchor
		if(is_object($anchor))
			$anchor->touch('article:update', $item['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y') );

		$context['text'] .= '<p>'.i18n::s('The page has been successfully published.')."</p>\n";

		// trackback option
		if(isset($_REQUEST['trackback_option']) && ($_REQUEST['trackback_option'] == 'Y')) {

			// expose links within the page
			$raw = $item['introduction'].' '.$item['source'].' '.$item['description'];

			// pingback & trackback
			include_once '../links/links.php';
			list($links, $created, $advertised, $skipped) = Links::ping($raw, 'article:'.$item['id']);

			// report on processed links
			if(@count($links)) {
				$context['text'] .= '<p>'.i18n::s('Following links have been parsed:')."</p>\n";

				$context['text'] .= '<ul>';
				foreach($links as $link) {

					// the link itself
					$context['text'] .= '<li>'.Skin::build_link($link);

					// link is new in the database
					if(is_array($created) && in_array($link, $created))
						$context['text'] .= ' ('.i18n::s('created').') ';

					// link has been pinged
					if(is_array($advertised) && in_array($link, $advertised))
						$context['text'] .= ' ('.i18n::s('advertised').') ';

					$context['text'] .= "</li>\n";
				}
				$context['text'] .= "</ul>\n";
			}

			// report on skipped links
			if(@count($skipped)) {
				$context['text'] .= '<p>'.i18n::s('Following links have been skipped:')."</p>\n";

				$context['text'] .= '<ul>';
				foreach($skipped as $link)
					$context['text'] .= '<li>'.Skin::build_link($link)."</li>\n";
				$context['text'] .= "</ul>\n";
			}
		}

		// ping option
		if(isset($_REQUEST['ping_option']) && ($_REQUEST['ping_option'] == 'Y')) {

			// list servers to be advertised
			include_once '../servers/servers.php';
			if($servers = Servers::list_for_ping(0, COMPACT_LIST_SIZE, 'ping')) {

				$context['text'] .= '<p>'.i18n::s('Following web sites have been advertised:').'</p><ul>';

				// ping each server
				foreach($servers as $server_url => $attributes) {
					list($server_ping, $server_label) = $attributes;

					include_once '../services/call.php';
					$milestone = get_micro_time();
					$result = @Call::invoke($server_ping, 'weblogUpdates.ping', array(strip_tags($context['site_name']), $context['url_to_home'].$context['url_to_root']), 'XML-RPC');
					if($result[0])
						$label = round(get_micro_time() - $milestone, 2).' sec.';
					else
						$label = '???';
					$context['text'] .= '<li>'.$server_label.' ('.$label.')</li>';

				}

				$context['text'] .= '</ul>';

			}

		// not advertised
		} else
			$context['text'] .= '<p>'.i18n::s('Please note that this page has not been advertised to aggregation servers.').'</p>';

		// 'publish' hook
		if(is_callable(array('Hooks', 'include_scripts')))
			$context['text'] .= Hooks::include_scripts('publish', $item['id']);

		// follow-up commands
		$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';
		$menu = array();
		$menu = array_merge($menu, array(Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']) => i18n::s('View the page')));
		if(Surfer::is_associate())
			$menu = array_merge($menu, array('articles/review.php' => i18n::s('Go to the review queue')));

		$context['text'] .= Skin::build_list($menu, 'menu_bar');

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// the form
	$context['text'] .= '<form id="edit_form" method="post" action="'.$context['script_url'].'"><div>';

	// reference the anchor page
	if(is_object($anchor) && $anchor->is_viewable())
		$context['text'] .= '<p>'.sprintf(i18n::s('You are publishing: %s'), Skin::build_link(Articles::get_url($item['id'], 'view', $item['title']), $item['title'], $item['nick_name']))."</p>\n";

	// encode fields
	$fields = array();

	// the publication date, if any
	$label = i18n::s('Publication date');

	// adjust date from server time zone to surfer time zone
	$value = strftime('%Y-%m-%d %H:%M:%S', time() + ((Surfer::get_gmt_offset() - intval($context['gmt_offset'])) * 3600));
	$input = Skin::build_input('publish_date', $value, 'date_time');
	$hint = i18n::s('Indicate a date (YYYY-MM-DD) in the future and let YACS make the page appear automatically.');
	$fields[] = array($label, $input, $hint);

	// advertise public pages
	$ping_option = FALSE;
	$trackback_option = FALSE;
	if(is_object($anchor) && $anchor->is_public()
			&& (isset($item['active']) && ($item['active'] == 'Y'))
			&& (!isset($item['home_panel']) || ($item['home_panel'] != 'none')) ) {
		$ping_option = TRUE;
		$trackback_option = TRUE;
	}

	// trackback option
	$label = i18n::s('Trackback');
	$input = '<input type="radio" name="trackback_option" value="N"';
	if(!$trackback_option)
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Do not contact referenced servers')
		.BR.'<input type="radio" name="trackback_option" value="Y"';
	if($trackback_option)
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Parse links and trackback referenced pages');
	$fields[] = array($label, $input);

	// ping option
	$label = i18n::s('Ping');

	// list servers to be advertised
	include_once '../servers/servers.php';
	if($servers = Servers::list_for_ping(0, COMPACT_LIST_SIZE, 'ping')) {

		// list targeted servers
		$list = '<ul>';
		foreach($servers as $server_url => $attributes) {
			list($server_ping, $server_label) = $attributes;
			$list .= '<li>'.Skin::build_link($server_url, $server_label, 'external').'</li>';
		}
		$list .= '</ul>';

		$input = '<input type="radio" name="ping_option" value="N"';
		if(!$ping_option)
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('Do not contact aggregation servers')
			.BR.'<input type="radio" name="ping_option" value="Y"';
		if($ping_option)
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('This publication should be advertised at:').$list;

	} else
		$input = sprintf(i18n::s('No aggregation server has been defined so far. Use the %s if your host is visible from the Internet.'), Skin::build_link('control/populate.php?action=servers', i18n::s('Content Assistant'), 'shortcut'));

	$fields[] = array($label, $input);

	// the expiry date, if any
	$label = i18n::s('Expiry date');

	// adjust date from UTC time zone to surfer time zone
	$value = '';
	if(isset($item['expiry_date']))
		$value = Surfer::from_GMT($item['expiry_date']);

	$input = Skin::build_input('expiry_date', $value, 'date_time');
	$hint = i18n::s('Use this field to limit the life time of published pages.');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);
	$fields = array();

	// submit or cancel
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');
	$menu[] = Skin::build_link(Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']), i18n::s('Cancel'), 'span');
	$context['text'] .= Skin::finalize_list($menu, 'menu_bar');

	// article id and confirmation
	$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';


}

// render the skin
render_skin();

?>