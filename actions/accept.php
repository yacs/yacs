<?php
/**
 * accept an action
 *
 * Accepting an action means that the surfer changes the status of the action. Therefore,
 * his/her name is registered in the database with the modification date.
 *
 * The new status can be either:
 * - 'on-going' - the action has been started
 * - 'completed' - nothing more to do
 * - 'rejected' - the task has been cancelled for some reason
 *
 * Restrictions apply on this page:
 * - associates and editors are allowed to move forward
 * - surfer is the action owner
 * - permission is denied if the anchor is not viewable by this surfer
 * - surfer can accept an action that has been anchored to his/her profile
 * - permission denied is the default
 *
 * Accept following invocations:
 * - accept.php/12/&lt;status&gt;
 * - accept.php?id=12&amp;status=&lt;status&gt;
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'actions.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// look for the status
$status = NULL;
if(isset($_REQUEST['status']))
	$status = $_REQUEST['status'];
elseif(isset($context['arguments'][1]))
	$status = $context['arguments'][1];
$status = strip_tags($status);

// get the item from the database
$item =& Actions::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// maybe this anonymous surfer is allowed to handle the anchor of this item
if(is_object($anchor) && Surfer::may_handle($anchor->get_handle()))
	Surfer::empower();

// associates and editors can do what they want
if(Surfer::is_empowered() || (is_object($anchor) && $anchor->is_editable()))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// the action is anchored to the profile of this user
elseif(Surfer::get_id() && ($item['anchor'] == 'user:'.Surfer::get_id()))
	$permitted = TRUE;

// the default is to deny access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('actions', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'actions/' => 'actions' );

// the title of the page
if($item['title'])
	$context['page_title'] = $item['title'];
else
	$context['page_title'] = i18n::s('Accept one action');

// not found
if(!$item['id']) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('actions/accept.php?id='.$item['id'].'&status='.$status));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// update the database
} elseif($error = Actions::accept($item['id'], $status))
	$context['text'] .= $error;

// post-processing tasks
else {

	// if the surfer came directly to this page, rewards it
	if(!Surfer::is_logged()) {

		// provide some feed-back
		$context['page_title'] = i18n::s('Your request has been processed.');

		// the new status
		switch($status) {
		case 'on-going':
			$label = i18n::s('On-going');
			break;

		case 'completed':
			$label = i18n::s('Completed');
			break;

		case 'rejected':
			$label = i18n::s('Rejected');
			break;

		}

		// show that everything's going fine
		$context['text'] .= sprintf(i18n::s('<p>The following action has been flagged with the status:</p><p><b>%s</b></p>'), $label);

		// display the action itself
		$context['text'] .= Skin::build_box($item['title'], Codes::beautify($item['description']));

	// else display an updated page
	} else
		Safe::redirect($context['url_to_home'].$context['url_to_root'].Actions::get_url($item['id']));

}

// render the skin
render_skin();

?>