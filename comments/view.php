<?php
/**
 * view one comment
 *
 * If several comments have been posted to a single anchor, a navigation bar will be built to jump
 * directly to previous and next neighbours.
 * This is displayed as a sidebar box in the extra panel.
 *
 * Post of new comments may have been explicitly prevented in anchor (option '[code]no_comments[/code]').
 * Otherwise commands to post new comments are added if the surfer has been authenticated,
 * or if anonymous comments are allowed (parameter '[code]users_with_anonymous_comments[/code]' set to 'Y'),
 * of if teasers have been enabled (parameter '[code]users_without_teasers[/code]' not set to 'Y').
 * Both global parameters are set in [script]users/configure.php[/script]).
 *
 * Where applicable, a link is added on page bottom to incitate people to reply to the displayed comment.
 *
 * The extra panel also features top popular referrals in a sidebar box, if applicable.
 *
 * Access is granted only if the surfer is allowed to view the anchor page.
 *
 * Accept following invocations:
 * - view.php/12
 * - view.php?id=12
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
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
$id = strip_tags($id);

// get the item from the database
include_once 'comments.php';
$item =& Comments::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// the anchor has to be viewable by this surfer
if(!is_object($anchor) || $anchor->is_viewable())
	$permitted = TRUE;
else
	$permitted = FALSE;

// load localized strings
i18n::bind('comments');

// load the skin, maybe with a variant
load_skin('comments', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'comments/' => i18n::s('All comments') );

// the title of the page
if(is_object($anchor) && $anchor->is_viewable())
	$context['page_title'] = $anchor->get_label('comments', 'view_title', $anchor->get_title());
else
	$context['page_title'] = i18n::s('View a comment');

// back to the anchor page
if(is_object($anchor) && $anchor->is_viewable())
	$context['page_menu'] = array_merge($context['page_menu'], array( $anchor->get_url('discuss') => i18n::s('Thread page') ));

// the quote command is available to logged users, or to everybody if set so
if($item['id'] && $permitted && Comments::are_allowed($anchor)) {

	$context['page_menu'] = array_merge($context['page_menu'], array( Comments::get_url($item['id'], 'reply') => i18n::s('Reply') ));

	$context['page_menu'] = array_merge($context['page_menu'], array( Comments::get_url($item['id'], 'quote') => i18n::s('Quote') ));
}

// commands for associates, authenticated editors and author
if($item['id'] && (Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable())
	|| Surfer::is_creator($item['create_id']))) {

	$context['page_menu'] = array_merge($context['page_menu'], array( Comments::get_url($item['id'], 'edit') => i18n::s('Edit') ));

	$context['page_menu'] = array_merge($context['page_menu'], array( Comments::get_url($item['id'], 'delete') => i18n::s('Delete') ));
}

// commands for associates and authenticated editors
if($item['id'] && (Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable())))
	$context['page_menu'] = array_merge($context['page_menu'], array( Comments::get_url($item['id'], 'promote') => i18n::s('Promote') ));

// the new comment command is available to logged users, and to everybody if set so
if($permitted && Comments::are_allowed($anchor)) {
	if($context['with_friendly_urls'] == 'Y')
		$link = 'comments/edit.php/'.str_replace(':', '/', $item['anchor']);
	else
		$link = 'comments/edit.php?anchor='.$item['anchor'];
	$context['page_menu'] = array_merge($context['page_menu'], array( $link => i18n::s('New comment') ));
}

// not found -- help web crawlers
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Comments::get_url($item['id'])));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// display the comment
} else {

	// insert anchor  icon
	if(is_object($anchor))
		$context['page_image'] = $anchor->get_icon_url();

	// initialize the rendering engine
	Codes::initialize(Comments::get_url($item['id']));

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// retrieve navigation links if we have an anchor
	if(is_object($anchor)) {

		// retrieve information from cache, if any
		$cache_id = 'comments/view.php?id='.$item['id'].'#navigation';
		if($data =& Cache::get($cache_id))
			$data = unserialize($data);

		// build information from the database
		else {

			$data = $anchor->get_neighbours('comment', $item);

			// serialize data
			$text = serialize($data);

			// save in cache
			Cache::put($cache_id, $text, 'comments');
		}

		// links to display previous and next pages, if any
		$context['text'] .= Skin::neighbours($data, 'slideshow');

		// a meta link to prefetch the next page
		if(isset($data[2]) && $data[2])
			$context['page_header'] .= "\n".'<link rel="next" href="'.$context['url_to_root'].$data[2].'" title="'.encode_field($data[3]).'"'.EOT;

	}

	// link to the previous comment in thread, if any
	if($item['previous_id'] && ($previous =& Comments::get($item['previous_id'])))
		$context['text'] .= ' <p>'.sprintf(i18n::s('Comment inspired from %s'), Skin::build_link(Comments::get_url($previous['id']), $previous['create_name'])).'</p>';

	// some details about this item
	$details = array();

	// the type, except on wikis and manuals
	if(is_object($anchor) && !$anchor->has_layout('manual') && !$anchor->has_layout('wiki'))
		$details[] = Comments::get_img($item['type']);

	// the poster of this comment
	$details[] = sprintf(i18n::s('by %s %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Skin::build_date($item['create_date'], 'with_hour'));

	// the last edition of this comment
	if($item['create_name'] != $item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date'], 'with_hour'));

	// the complete details
	if($details)
		$context['text'] .= '<p class="details">'.ucfirst(implode(' ', $details))."</p>\n";

	// display the full comment
	if($item['description']) {

		// beautify the complete comment
		$text = Codes::beautify($item['description']);

		// show the description
		$context['text'] .= '<p></p>'.$text."<p></p>\n";
	}

	// list follow-ups in thread, if any
	if($next = Comments::list_next($item['id'], 'compact'))
		$context['text'] .= ' <p style="margin-bottom: 0; padding-bottom: 0;">'.i18n::s('This comment has inspired:').'</p>'.Skin::build_list($next, 'compact');

	// follow-up commands
	$menu = array();

	// a bottom menu to react
	if(Comments::are_allowed($anchor)) {

		// allow posters to change their own comments
		if($item['create_id'] == Surfer::get_id())
			$menu[] = Skin::build_link(Comments::get_url($item['id'], 'edit'), i18n::s('Edit'), 'button' );

		// allow surfers to react to contributions from other people
		else {
			Skin::define_img('NEW_COMMENT_IMG', $context['skin'].'/icons/comments/new.gif');
			$menu[] = Skin::build_link(Comments::get_url($item['id'], 'reply'), NEW_COMMENT_IMG.' '.i18n::s('React to this post'), 'basic');
		}
	}

	// go back to the thread
	if(is_object($anchor))
		$menu[] = Skin::build_link($anchor->get_url('discuss'), i18n::s('Cancel'), 'span' );

	// build the menu
	if(count($menu))
		$context['text'] .= Skin::finalize_list($menu, 'menu_bar');

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	//
	// the referrals, if any, in a sidebar
	//
	if(Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y'))) {

		$cache_id = 'comments/view.php?id='.$item['id'].'#referrals';
		if(!$text =& Cache::get($cache_id)) {

			// box content
			include_once '../agents/referrals.php';
			$text = Referrals::list_by_hits_for_url($context['url_to_root_parameter'].Comments::get_url($item['id']));

			// in a sidebar box
			if($text)
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