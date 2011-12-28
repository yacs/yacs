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
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'comments.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Comments::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// the anchor has to be viewable by this surfer
if(!is_object($anchor) || $anchor->is_viewable())
	$permitted = TRUE;
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('comments', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// current item
if(isset($item['id']))
	$context['current_item'] = 'comment:'.$item['id'];

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'comments/' => i18n::s('Threads') );

// page title
if(is_object($anchor) && $anchor->is_viewable())
	$context['page_title'] = $anchor->get_title();
if(!$context['page_title'])
	$context['page_title'] = i18n::s('View a comment');

// not found -- help web crawlers
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Comments::get_url($item['id'])));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// re-enforce the canonical link
} elseif($context['self_url'] && ($canonical = $context['url_to_home'].$context['url_to_root'].Comments::get_url($item['id'])) && strncmp($context['self_url'], $canonical, strlen($canonical))) {
	Safe::header('Status: 301 Moved Permanently', TRUE, 301);
	Safe::header('Location: '.$canonical);
	Logger::error(Skin::build_link($canonical));

// display the comment
} else {

	// insert anchor  icon
	if(is_object($anchor))
		$context['page_image'] = $anchor->get_icon_url();

	// initialize the rendering engine
	Codes::initialize(Comments::get_url($item['id']));

	// neighbours information
	$neighbours = NULL;
	if(is_object($anchor))
		$neighbours = $anchor->get_neighbours('comment', $item);

	//
	// page header
	//

	// a meta link to prefetch the next page
	if(isset($neighbours[2]) && $neighbours[2])
		$context['page_header'] .= "\n".'<link rel="next" href="'.$context['url_to_root'].$neighbours[2].'" title="'.encode_field($neighbours[3]).'" />';

	//
	// main panel -- $context['text']
	//

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// links to display previous and next pages, if any
	$context['text'] .= Skin::neighbours($neighbours, 'slideshow');

	// link to the previous comment in thread, if any
	if($item['previous_id'] && ($previous =& Comments::get($item['previous_id'])))
		$context['text'] .= ' <p>'.sprintf(i18n::s('Comment inspired from %s'), Skin::build_link(Comments::get_url($previous['id']), $previous['create_name'])).'</p>';

	// display the full comment
	$context['text'] .= Skin::build_block($item['description'], 'description');

	// list follow-ups in thread, if any
	if($next = Comments::list_next($item['id'], 'compact'))
		$context['text'] .= ' <p style="margin-bottom: 0; padding-bottom: 0;">'.i18n::s('This comment has inspired:').'</p>'.Skin::build_list($next, 'compact');

	// some details about this item
	$details = array();

	// the type
	if(is_object($anchor))
		$details[] = Comments::get_img($item['type']);

	// the poster of this comment
	if($poster = Users::get_link($item['create_name'], $item['create_address'], $item['create_id']))
		$details[] = sprintf(i18n::s('by %s %s'), $poster, Skin::build_date($item['create_date'], 'with_hour'));
	else
		$details[] = Skin::build_date($item['create_date'], 'with_hour');

	// the last edition of this comment
	if($item['create_name'] != $item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date'], 'with_hour'));

	// all details
	if($details)
		$context['text'] .= '<p class="details">'.ucfirst(implode(' ', $details))."</p>\n";

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	// back to the anchor page
	if(is_object($anchor) && $anchor->is_viewable()) {
		$menu = array(Skin::build_link($anchor->get_url(), i18n::s('Back to main page'), 'button'));

		// a bottom menu to react
		if(Comments::allow_creation($anchor)) {

			// allow posters to change their own comments
			if(Surfer::get_id() && ($item['create_id'] == Surfer::get_id())) {
				Skin::define_img('COMMENTS_EDIT_IMG', 'comments/edit.gif');
				$menu[] = Skin::build_link(Comments::get_url($item['id'], 'edit'), COMMENTS_EDIT_IMG.i18n::s('Edit'));
			}

			// allow surfers to react to contributions from other people
			else {
				Skin::define_img('COMMENTS_REPLY_IMG', 'comments/reply.gif');
				$menu[] = Skin::build_link(Comments::get_url($item['id'], 'reply'), COMMENTS_REPLY_IMG.i18n::s('Reply'));

				Skin::define_img('COMMENTS_QUOTE_IMG', 'comments/quote.gif');
				$menu[] = Skin::build_link(Comments::get_url($item['id'], 'quote'), COMMENTS_QUOTE_IMG.i18n::s('Quote'));

			}
		}

		$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');
	}

	//
	// extra panel -- $context['extra']
	//

	// page tools
	//

	// a bottom menu to react
	if(Comments::allow_creation($anchor)) {

		// allow posters to change their own comments
		if(Surfer::get_id() && ($item['create_id'] == Surfer::get_id())) {
			Skin::define_img('COMMENTS_EDIT_IMG', 'comments/edit.gif');
			$context['page_tools'][] = Skin::build_link(Comments::get_url($item['id'], 'edit'), COMMENTS_EDIT_IMG.i18n::s('Edit'), 'basic', i18n::s('Press [e] to edit'), FALSE, 'e');
		}

		// allow surfers to react to contributions from other people
		else {
			Skin::define_img('COMMENTS_REPLY_IMG', 'comments/reply.gif');
			$context['page_tools'][] = Skin::build_link(Comments::get_url($item['id'], 'reply'), COMMENTS_REPLY_IMG.i18n::s('Reply'));

			Skin::define_img('COMMENTS_QUOTE_IMG', 'comments/quote.gif');
			$context['page_tools'][] = Skin::build_link(Comments::get_url($item['id'], 'quote'), COMMENTS_QUOTE_IMG.i18n::s('Quote'));

			if(Surfer::is_associate()) {
				Skin::define_img('COMMENTS_EDIT_IMG', 'comments/edit.gif');
				$context['page_tools'][] = Skin::build_link(Comments::get_url($item['id'], 'edit'), COMMENTS_EDIT_IMG.i18n::s('Edit'), 'basic', i18n::s('Press [e] to edit'), FALSE, 'e');
			}

		}
	}

	// commands for associates, authenticated editors and author
	if(Comments::allow_modification($anchor, $item)) {
		Skin::define_img('COMMENTS_DELETE_IMG', 'comments/delete.gif');
		$context['page_tools'][] = Skin::build_link(Comments::get_url($item['id'], 'delete'), COMMENTS_DELETE_IMG.i18n::s('Delete'));
	}

	// turn this to an article
	if((Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_owned()))) {
		Skin::define_img('COMMENTS_PROMOTE_IMG', 'comments/promote.gif');
		$context['page_tools'][] = Skin::build_link(Comments::get_url($item['id'], 'promote'), COMMENTS_PROMOTE_IMG.i18n::s('Promote'));
	}

	//
	// the referrals, if any, in a sidebar
	//
	$context['components']['referrals'] =& Skin::build_referrals(Comments::get_url($item['id']));

}

// render the skin
render_skin();

?>
