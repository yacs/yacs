<?php
/**
 * the database abstraction layer for articles
 *
 * @todo place in bin on deletion
 *
 * The several versions of article content are now saved for history, and may be restored at any time.
 *
 * @see versions/versions.php
 *
 * [title]How to lock an article?[/title]
 *
 * An article can be locked to prevent modification.
 * This feature only concerns regular members of the community, as associates and editors are always allowed to add, change of remove any page.
 *
 * [title]How to manage options for articles?[/title]
 *
 * The options field is a convenient place to save attributes for any article without extending the database schema.
 * As articles are commonly used to anchor some pages, their options can be also checked through the [code]has_option()[/code]
 * member function of the [code]Anchor[/code] interface. Check [script]shared/anchor.php[/script] for more information.
 *
 * This means that some options are used within the context of one article (eg, [code]no_links[/code]),
 * while others can be used with related items as well.
 *
 * Specific options to be processed by advanced overlays are not described hereafter.
 * Please check documentation pages for any overlay you use, like [script]overlays/poll.php[/script].
 *
 * You can combine any of following keywords in the field for options, with the separator (spaces, tabs, commas) of your choice:
 *
 * [*] [code]files_by_title[/code] - When viewing articles, order attached files by alphabetical order instead of using edition time information.
 * This option may prove useful to structure a list of files.
 * For example, on a page describing a complex project, you would like to offer an introduction to the project ('[code]1.introduction.doc[/code]'),
 * then a report on initial issue ('[code]2.the issue.ppt[/code]'), and a business case for the solution ('[code]3.profit_and_loss.xls[/code]').
 * By adjusting file names and titles as shown, and by setting the option [code]files_by_title[/code], you would achieve a nice and logical thing.
 *
 * [*] [code]formatted[/code] - The YACS page factory is disabled, since the description contains formatting tags.
 * Use this option if you copy the source of a HTML or of a XHTML page, and paste it into an article at your server.
 * Note that this keyword is also accepted if it is formatted as a YACS code ('[code]&#91;formatted]'[/code])
 * at the very beginning of the description field.
 *
 * [*] [code]hardcoded[/code] - The YACS page factory is disabled, except that new lines are changed to (X)HTML breaks.
 * Use this option if you copy some raw text file (including a mail message) and make a page out of it.
 * Note that this keyword is also accepted if it is formatted as a YACS code ('[code]&#91;hardcoded]'[/code])
 * at the very beginning of the description field.
 *
 * [*] [code]links_by_title[/code] - When wiewing articles, order attached links by alphabetical order instead of using edition time information.
 * This options works like [code]files_by_title[/code], except that it applies to link.
 * Use it to create nice and ordered bookmarks.
 *
 * [*] [code]no_comments[/code] - New comments cannot be posted on this page.
 *
 * [*] [code]no_files[/code] - New files cannot be attached to this page.
 *
 * [*] [code]no_links[/code] - New links cannot be posted to this article.
 *
 * [*] [code]skin_&lt;xxxx&gt;[/code] - Select one skin explicitly.
 * Use this option to apply a specific skin to a page.
 * This setting is the most straightforward way of introducing some skin to web surfers.
 *
 * [*] [code]variant_&lt;xxxx&gt;[/code] - Select one skin variant explicitly.
 * Usually only the variant '[code]articles[/code]' is used throughout articles.
 * This can be changed to '[code]xxxx[/code]' by using the option [code]variant_&lt;xxxx&gt;[/code].
 * Then the underlying skin may adapt to this code by looking at [code]$context['skin_variant'][/code].
 * Basically, use variants to change the rendering of individual articles of your site, if the skin allows it.
 *
 *
 * Also, a specific option is available to handle the article at the front page:
 *
 * [*] [code]none[/code] - Don't mention this published article at the site front page.
 * Use this option to avoid that special pages add noise to the front page.
 * For example, while building the on-line manual of YACS this option has been set to intermediate pages,
 * that are only featuring lists of following pages.
 *
 *
 * [title]How to order articles and to manage sticky pages?[/title]
 *
 * Usually articles are ranked by edition date, with the most recent page coming first.
 * You can change this 'natural' order by modifying the value of the rank field.
 *
 * What is the result obtained, depending on the value set?
 *
 * [*] 10000 - This is the default value. All articles created by YACS are ranked equally.
 *
 * [*] Less than 10000 - Useful to create sticky and ordered pages.
 * Sticky, since these pages will always come first.
 * Ordered, since the lower rank values come before higher rank values.
 * Pages that have the same rank value are ordered by dates, with the newest item coming first.
 * This lets you arrange precisely the order of sticky pages.
 *
 * [*] More than 10000 - To reject pages at the end of lists.
 *
 *
 * @author Bernard Paques
 * @author Florent
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Mark
 * @tester Fernand Le Chien
 * @tester NickR
 * @tester Denis Flouriot
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Articles {

	/**
	 * set SQL order
	 *
	 * @param string wanted
	 * @param boolean TRUE is these are coming from several sections, FALSE, otherwise
	 * @return string to be put in SQL statements
	 */
	public static function _get_order($order, $multiple_anchor=TRUE) {

		switch($order) {
		case 'draft':
			$order = 'edit_date DESC, title';
			break;

		case 'edition': // order by rank, then by reverse date of modification
		default:

			// avoid side effects of ranking across several sections
			if($multiple_anchor)
				$order = 'edit_date DESC, title';
			else
				$order = 'rank, edit_date DESC, title';
			break;

		case 'expiry': // order by expiry date
			$order = 'expiry_date DESC, edit_date DESC, title';
			break;

		case 'hits':	// order by reverse number of hits, then by reverse date of publication

			$order = 'hits DESC, publish_date DESC';
			break;

		case 'overlay': // order by overlay_id, then by number of points

			// avoid side effects of ranking across several sections
			if($multiple_anchor)
				$order = 'overlay_id, rating_sum DESC, publish_date DESC';
			else
				$order = 'overlay_id, rank, rating_sum DESC, publish_date DESC';
			break;

		case 'reverse_overlay' : // Same but DESC for overlay_id, then by number of points

		        // avoid side effects of ranking across several sections
			if($multiple_anchor)
				$order = 'overlay_id DESC, rating_sum DESC, publish_date DESC';
			else
				$order = 'overlay_id DESC, rank, rating_sum DESC, publish_date DESC';
			break;

		case 'publication': // order by rank, then by reverse date of publication
		case 'future': // obsoleted?

			// avoid side effects of ranking across several sections
			if($multiple_anchor)
				$order = 'publish_date DESC, title';
			else
				$order = 'rank, publish_date DESC, title';
			break;

		case 'random':
			$order = 'RAND()';
			break;

		case 'rating':	// order by rank, then by number of points

			// avoid side effects of ranking across several sections
			if($multiple_anchor)
				$order = 'rating_sum DESC, edit_date DESC';
			else
				$order = 'rank, rating_sum DESC, edit_date DESC';
			break;

		case 'reverse_rank':	// order by rank, then by date of publication
			$order = 'rank DESC, edit_date DESC';
			break;

		case 'review':	// order by date of last review
			$order = 'stamp, title';
			break;

		case 'reverse_title':	// order by rank, then by reverse title

			// avoid side effects of ranking across several sections
			if($multiple_anchor)
				$order = 'title DESC';
			else
				$order = 'rank, title DESC';
			break;

		case 'title':	// order by rank, then by title

			// avoid side effects of ranking across several sections
			if($multiple_anchor)
				$order = 'title';
			else
				$order = 'rank, title';
			break;

		case 'unread':	// locate unused pages
			$order = 'hits, edit_date';
			break;

		}

		return $order;
	}

	/**
	 * check if an article can be accessed
	 *
	 * This function returns TRUE if the item can be transferred to surfer,
	 * and FALSE otherwise.
	 *
	 * @param array a set of item attributes, aka, the target page
	 * @param object an instance of the Anchor interface, if any
	 * @return boolean TRUE or FALSE
	 */
	public static function allow_access($item, $anchor) {
		global $context;

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// surfer owns this item, or the anchor
		if(Articles::is_owned($item, $anchor))
			return TRUE;

		// anonymous surfer has provided the secret handle
		if(isset($item['handle']) && Surfer::may_handle($item['handle']))
			return TRUE;

		// surfer is an editor
		if(isset($item['id']) && Articles::is_assigned($item['id']))
			return TRUE;
		if(is_object($anchor) && $anchor->is_assigned())
			return TRUE;

		// surfer is a trusted host
		if(Surfer::is_trusted())
			return TRUE;

		// container is hidden
		if(isset($item['active']) && ($item['active'] == 'N'))
			return FALSE;
		if(is_object($anchor) && $anchor->is_hidden())
			return FALSE;

		// surfer is logged
		if(Surfer::is_logged())
			return TRUE;

		// container is restricted
		if(isset($item['active']) && ($item['active'] == 'R'))
			return FALSE;
		if(is_object($anchor) && !$anchor->is_public())
			return FALSE;

		// public page
		return TRUE;
	}

	/**
	 * check if new articles can be added
	 *
	 * This function returns TRUE if articles can be added to some place,
	 * and FALSE otherwise.
	 *
	 * @param array a set of item attributes, if any --always a section
	 * @param object an instance of the Anchor interface, if any
	 * @return boolean TRUE or FALSE
	 */
	public static function allow_creation($item, $anchor=NULL) {
		global $context;

		// articles are prevented in item, through layout
		if(isset($item['articles_layout']) && ($item['articles_layout'] == 'none'))
			return FALSE;

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// submissions have been disallowed
		if(isset($context['users_without_submission']) && ($context['users_without_submission'] == 'Y'))
			return FALSE;

		// surfer owns this item, or the anchor
		if(Sections::is_owned($item, $anchor, TRUE))
			return TRUE;

		// not for subscribers
		if(Surfer::is_member()) {

			// surfer is an editor, and the section is not private
			if(isset($item['active']) && ($item['active'] != 'N') && Sections::is_assigned($item['id']))
				return TRUE;
			if(isset($item['active']) && ($item['active'] != 'N') && is_object($anchor) && $anchor->is_assigned())
				return TRUE;
			if(!isset($item['id']) && is_object($anchor) && !$anchor->is_hidden() && $anchor->is_assigned())
				return TRUE;

		}

		// container has been locked
		if(isset($item['locked']) && ($item['locked'] == 'Y'))
			return FALSE;

		// anchor has been locked
		if(!isset($item['id']) && is_object($anchor) && $anchor->has_option('locked'))
			return FALSE;

		// anonymous contributions are allowed for articles
		if(isset($item['options']) && preg_match('/\banonymous_edit\b/i', $item['options']))
			return TRUE;
		if(is_object($anchor) && $anchor->has_option('anonymous_edit'))
			return TRUE;

		// subscribers can contribute too
		if(Surfer::is_logged() && isset($item['options']) && preg_match('/\bmembers_edit\b/i', $item['options']))
			return TRUE;
		if(Surfer::is_logged() && is_object($anchor) && $anchor->has_option('members_edit'))
			return TRUE;

		// not for subscribers
		if(Surfer::is_member()) {

			// surfer is an editor (and item has not been locked)
			if(isset($item['id']) && Sections::is_assigned($item['id']))
				return TRUE;
			if(is_object($anchor) && $anchor->is_assigned())
				return TRUE;

		}

		// container is hidden
		if(isset($item['active']) && ($item['active'] == 'N'))
			return FALSE;
		if(is_object($anchor) && $anchor->is_hidden())
			return FALSE;

		// surfer is a member
		if(Surfer::is_member())
			return TRUE;

		// the default is to not allow for new articles
		return FALSE;
	}

	/**
	 * check if an article can be deleted
	 *
	 * This function returns TRUE if the page can be deleted,
	 * and FALSE otherwise.
	 *
	 * @param array a set of item attributes, aka, the target article
	 * @param object an instance of the Anchor interface
	 * @return TRUE or FALSE
	 */
	public static function allow_deletion($item, $anchor) {
		global $context;

		// sanity check
		if(!isset($item['id']))
			return FALSE;

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// surfer owns the page
		if(isset($item['owner_id']) && Surfer::is($item['owner_id']))
			return TRUE;

		// surfer owns the container
		if(is_object($anchor) && $anchor->is_owned())
			return TRUE;

		// allow editors --not subscribers-- to manage content, except on private sections
		if(Surfer::is_member() && is_object($anchor) && !$anchor->is_hidden() && $anchor->is_assigned())
			return TRUE;

		// default case
		return FALSE;
	}

	/**
	 * check if a surfer can send a message to group participants
	 *
	 * @param array a set of item attributes, aka, the target page
	 * @param object an instance of the Anchor interface
	 * @return TRUE or FALSE
	 */
	public static function allow_message($item, $anchor=NULL) {
		global $context;

		// subscribers can never send a message
		if(!Surfer::is_member())
			return FALSE;

		// sanity check
		if(!isset($item['id']))
			return FALSE;

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// surfer owns the container or the page
		if(Articles::is_owned($item, $anchor, TRUE))
			return TRUE;

		// page editors can proceed
		if(isset($item['id']) && Articles::is_assigned($item['id']))
			return TRUE;

		// container editors can proceed
		if(is_object($anchor) && $anchor->is_assigned())
			return TRUE;

		// default case
		return FALSE;
	}

	/**
	 * check if an article can be modified
	 *
	 * This function returns TRUE if the page can be modified,
	 * and FALSE otherwise.
	 *
	 * @param array a set of item attributes, aka, the target article
	 * @param object an instance of the Anchor interface
	 * @return TRUE or FALSE
	 */
	public static function allow_modification($item, $anchor) {
		global $context;

		// sanity check
		if(!isset($item['id']) && !$anchor)
			return FALSE;

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// ensure access rights
		if(!Articles::allow_access($item, $anchor))
			return FALSE;

		// submissions have been disallowed
		if(isset($context['users_without_submission']) && ($context['users_without_submission'] == 'Y'))
			return FALSE;

		// surfer owns the container or the article
		if(Articles::is_owned($item, $anchor))
			return TRUE;

		// allow section editors to manage content, except on private sections
		if(Surfer::is_member() && is_object($anchor) && !$anchor->is_hidden() && $anchor->is_assigned())
			return TRUE;

		// allow page editors to manage content, except on private page
		if(Surfer::is_member() && ($item['active'] != 'N') && Articles::is_assigned($item['id']))
			return TRUE;

		// article has been locked
		if(isset($item['locked']) && ($item['locked'] == 'Y'))
			return FALSE;

		// maybe this anonymous surfer is allowed to handle this item
		if(isset($item['handle']) && Surfer::may_handle($item['handle']))
			return TRUE;

		// community wiki
		if(Surfer::is_logged() && Articles::has_option('members_edit', $anchor, $item))
			return TRUE;

		// public wiki
		if(Articles::has_option('anonymous_edit', $anchor, $item))
			return TRUE;

		// default case
		return FALSE;
	}

	/**
	 * check if an article can be published
	 *
	 * This function returns TRUE if the page can be published,
	 * and FALSE otherwise.
	 *	 
	 * @param array a set of item attributes, aka, the target article
	 * @param object an instance of the Anchor interface
	 * @return TRUE or FALSE
	 */
	public static function allow_publication($item,$anchor) {
		global $context;

		// sanity check
		if(!isset($item['id']))
			return FALSE;

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// submissions have been disallowed
		if(isset($context['users_without_submission']) && ($context['users_without_submission'] == 'Y'))
			return FALSE;

		// owners can publish their content through all of the server
		if(isset($item['owner_id']) && Surfer::is($item['owner_id']) && isset($context['users_with_auto_publish']) && ($context['users_with_auto_publish'] == 'Y'))
			return TRUE;

		// owners can publish their content in this section
		if(isset($item['owner_id']) && Surfer::is($item['owner_id']) && is_object($anchor) && $anchor->has_option('members_edit'))
			return TRUE;

		// surfer owns the container
		if(is_object($anchor) && $anchor->is_owned())
			return TRUE;

		// allow editors to manage content, but not on private sections
		if(Surfer::is_member() && is_object($anchor) && !$anchor->is_hidden() && $anchor->is_assigned())
			return TRUE;

		// default case
		return FALSE;
	}

	/**
	 * document modification dates for this item
	 *
	 * @param object anchor of the article
	 * @param array the article to be documented
	 * @return array strings detailed labels
	 */
	public static function &build_dates($anchor, $item) {
		global $context;

		// we return an array of strings
		$details = array();

		// we do want details for this page
		if(strpos($item['options'], 'with_details') !== FALSE)
			;

		// no details please
		elseif(isset($context['content_without_details']) && ($context['content_without_details'] == 'Y') && !Articles::is_owned($item, $anchor))
			return $details;

		// last modification
		if($item['edit_action'])
			$action = Anchors::get_action_label($item['edit_action']).' ';
		else
			$action = i18n::s('edited');

		if($item['edit_name'])
			$details[] = sprintf(i18n::s('%s by %s %s'), $action, Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));
		else
			$details[] = $action.' '.Skin::build_date($item['edit_date']);

		// last revision, if any
		if(isset($item['review_date']) && ($item['review_date'] > NULL_DATE) && Surfer::is_associate())
			$details[] = sprintf(i18n::s('reviewed %s'), Skin::build_date($item['review_date'], 'no_hour'));

		// publication date and contributor
		if(($item['publish_date'] > NULL_DATE) && ($item['publish_id'] != $item['create_id']) && !strpos($item['edit_action'], ':publish')) {

			if($item['publish_name'])
				$details[] = sprintf(i18n::s('published by %s %s'), Users::get_link($item['publish_name'], $item['publish_address'], $item['publish_id']), Skin::build_date($item['publish_date']));
			else
				$details[] = Skin::build_date($item['publish_date']);

		}

		// post date and author
		if($item['create_date']) {

			// creation and last modification happen on same day by the same person
			if(!strcmp(substr($item['create_date'], 0, 10), substr($item['edit_date'], 0, 10)) && ($item['create_id'] == $item['edit_id']))
				;

			// mention creation date
			elseif($item['create_name'])
				$details[] = sprintf(i18n::s('posted by %s %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Skin::build_date($item['create_date']));
			else
				$details[] = Skin::build_date($item['create_date']);

		}

		// job done
		return $details;
	}

	/**
	 * build a notification related to an article
	 *
	 * The action can be one of the following:
	 * - 'apply' - surfer would like to get access to the page
	 * - 'publish' - either a published page has been posted, or a draft page has been published
	 * - 'submit' - a draft page has been posted
	 * - 'update' - a page (draft or published) has been modified
	 *
	 * This function builds a mail message that displays:
	 * - an image of the contributor (if possible)
	 * - a headline mentioning the contribution
	 * - the full content of the new comment
	 * - a button linked to the reply page
	 * - a link to the containing page
	 *
	 * Note: this function returns legacy HTML, not modern XHTML, because this is what most
	 * e-mail client software can afford.
	 *
	 * @param string either 'apply', 'publish', 'submit' or 'update'
	 * @param array attributes of the item
	 * @param object overlay of the item, if any
	 * @return string text to be send by e-mail
	 */
	public static function build_notification($action='publish', $item, $overlay=NULL) {
		global $context;

		// sanity check
		if(!isset($item['anchor']) || (!$anchor = Anchors::get($item['anchor'])))
			throw new Exception('no anchor for this article');

		// compute page title
		if(is_object($overlay))
			$title = Codes::beautify_title($overlay->get_text('title', $item));
		else
			$title = Codes::beautify_title($item['title']);

		// headline link to section
		$headline_link = '<a href="'.$context['url_to_home'].$context['url_to_root'].$anchor->get_url().'">'.$anchor->get_title().'</a>';

		// headline template
		switch($action) {
		case 'apply':
			$template = i18n::c('%s is requesting access to %s');
			$headline_link = '<a href="'.Articles::get_permalink($item).'">'.$title.'</a>';
			break;
		case 'publish':
			$template = i18n::c('%s has posted a page in %s');
			break;
		case 'submit':
			$template = i18n::c('%s has submitted a page in %s');
			break;
		case 'update':
			$template = i18n::c('%s has updated a page in %s');
			break;
		}

		// headline
		$headline = sprintf($template, Surfer::get_link(), $headline_link);

		// panel content
		$content = '';

		// more insight on this page
		$prefix = $suffix = '';

		// signal articles to be published
		if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
			$prefix .= DRAFT_FLAG;

		// signal restricted and private articles
		if($item['active'] == 'N')
			$prefix .= PRIVATE_FLAG;
		elseif($item['active'] == 'R')
			$prefix .= RESTRICTED_FLAG;

		// flag expired articles
		if(isset($item['expiry_date']) && ($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
			$prefix .= EXPIRED_FLAG.' ';

		// signal locked articles
		if(isset($item['locked']) && ($item['locked'] == 'Y') && Articles::is_owned($item, $anchor))
			$suffix .= ' '.LOCKED_FLAG;

		// insert page title
		$content .= '<h3><span>'.$prefix.$title.$suffix.'</span></h3>';

		// insert anchor prefix
		if(is_object($anchor))
			$content .= $anchor->get_prefix();

		// the introduction text, if any
		if(is_object($overlay))
			$content .= Skin::build_block($overlay->get_text('introduction', $item), 'introduction');
		elseif(isset($item['introduction']) && trim($item['introduction']))
			$content .= Skin::build_block($item['introduction'], 'introduction');

		// get text related to the overlay, if any
		if(is_object($overlay))
			$content .= $overlay->get_text('diff', $item);

		// filter description, if necessary
		if(is_object($overlay))
			$description = $overlay->get_text('description', $item);
		else
			$description = $item['description'];

		// the beautified description, which is the actual page body
		if($description) {

			// use adequate label
			if(is_object($overlay) && ($label = $overlay->get_label('description')))
				$content .= Skin::build_block($label, 'title');

			// beautify the target page
			$content .= Skin::build_block($description, 'description', '', $item['options']);

		}

		// attachment details
		$details = array();

		// avoid first file in list if mentioned in last comment
		$file_offset = 0;

		// comments
		include_once $context['path_to_root'].'comments/comments.php';
		if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE)) {

			// get last contribution for this page
			if($comment = Comments::get_newest_for_anchor('article:'.$item['id'])) {

				if(preg_match('/\[(download|file)=/', $comment['description']))
					$file_offset++;

				// bars around the last contribution
				$bottom_menu = array();

				// last contributor
				$contributor = Users::get_link($comment['create_name'], $comment['create_address'], $comment['create_id']);
				$flag = '';
				if($comment['create_date'] >= $context['fresh'])
					$flag = NEW_FLAG;
				elseif($comment['edit_date'] >= $context['fresh'])
					$flag = UPDATED_FLAG;
				$bottom_menu[] = sprintf(i18n::s('By %s'), $contributor).' '.Skin::build_date($comment['create_date']).$flag;

				// gather pieces
				$pieces = array();

				// last contribution, and user signature
				$pieces[] = ucfirst(trim($comment['description'])).Users::get_signature($comment['create_id']);

				// bottom
				if($bottom_menu)
					$pieces[] = '<div>'.ucfirst(trim(Skin::finalize_list($bottom_menu, 'menu'))).'</div>';

				// put all pieces together
				$content .= '<div>'."\n"
					.join("\n", $pieces)
					.'</div>'."\n";

			}

			// count comments
			$details[] = sprintf(i18n::nc('%d comment', '%d comments', $count), $count);
		}

		// info on related files
		if($count = Files::count_for_anchor('article:'.$item['id'])) {

			// most recent files attached to this page
			if($items = Files::list_by_date_for_anchor('article:'.$item['id'], $file_offset, 3, 'dates')) {

				// more files than listed
				$more = '';
				if($count > 3)
					$more = '<span class="details">'.sprintf(i18n::s('%d files, including:'), $count).'</span>';

				if(is_array($items))
					$items = Skin::build_list($items, 'compact');

				$items = '<div>'.$more.$items.'</div>';
			}

			// wrap it with some header
			if($items)
				$content .= '<h3><span>'.i18n::c('Files').'</span></h3>'.$items;

			// count files
			$details[] = sprintf(i18n::nc('%d file', '%d files', $count), $count);
		}

		// info on related links
		include_once $context['path_to_root'].'links/links.php';
		if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
			$details[] = sprintf(i18n::nc('%d link', '%d links', $count), $count);

		// describe attachments
		if(count($details))
			$content .= '<hr align="left" size=1" width="150">'
				.'<p>'.sprintf(i18n::c('This page has %s'), join(', ', $details)).'</p>';

		// assemble main content of this message
		$text = Skin::build_mail_content($headline, $content);

		// a set of links
		$menu = array();

		// request access to the item
		if($action == 'apply') {

			// call for action
			$link = $context['url_to_home'].$context['url_to_root'].Articles::get_url($item['id'], 'invite', Surfer::get_id());
			$label = sprintf(i18n::c('Invite %s to participate'), Surfer::get_name());
			$menu[] = Skin::build_mail_button($link, $label, TRUE);

			// link to user profile
			$link = Surfer::get_permalink();
			$label = sprintf(i18n::c('View the profile of %s'), Surfer::get_name());
			$menu[] = Skin::build_mail_button($link, $label, FALSE);

		// invite to visit the item
		} else {

			// call for action
			$link = Articles::get_permalink($item);
			if(!is_object($overlay) || (!$label = $overlay->get_label('permalink_command', 'articles', FALSE)))
				$label = i18n::c('View the page');
			$menu[] = Skin::build_mail_button($link, $label, TRUE);

			// link to the container
			$link = $context['url_to_home'].$context['url_to_root'].$anchor->get_url();
			$menu[] = Skin::build_mail_button($link, $anchor->get_title(), FALSE);

		}

		// finalize links
		$text .= Skin::build_mail_menu($menu);

		// the full message
		return $text;

	}

	/**
	 * build the field to capture article option
	 *
	 * @see articles/edit.php
	 *
	 * @param array the edited item
	 * @return string text to be put in the form
	 */
	public static function build_options_input($item) {
		global $context;

		$text = '<input type="text" name="options" id="options" size="55" value="'.encode_field(isset($item['options']) ? $item['options'] : '').'" maxlength="255" accesskey="o" />';
		return $text;
	}

	/**
	 * build the hint to help on article options
	 *
	 * @see articles/edit.php
	 *
	 * @return string text to be put in the form
	 */
	public static function build_options_hint() {
		global $context;

		$keywords = array();
		$keywords[] = '<a>anonymous_edit</a> - '.i18n::s('Allow anonymous surfers to edit content');
		$keywords[] = '<a>members_edit</a> - '.i18n::s('Allow members to edit content');
		$keywords[] = '<a>no_comments</a> - '.i18n::s('Prevent the addition of comments');
		$keywords[] = '<a>files_by_date</a> - '.i18n::s('Sort files by date (default)');
		$keywords[] = '<a>files_by_title</a> - '.i18n::s('Sort files by title (and not by date)');
		$keywords[] = '<a>no_files</a> - '.i18n::s('Prevent the upload of new files');
		$keywords[] = '<a>links_by_title</a> - '.i18n::s('Sort links by title (and not by date)');
		$keywords[] = '<a>no_links</a> - '.i18n::s('Prevent the addition of related links');
		$keywords[] = '<a>view_as_chat</a> - '.i18n::s('Real-time collaboration');
		$keywords[] = '<a>view_as_tabs</a> - '.i18n::s('Tabbed panels');
		$keywords[] = '<a>view_as_wiki</a> - '.i18n::s('Discussion is separate from content');
		$keywords[] = 'view_as_foo_bar - '.sprintf(i18n::s('Branch out to %s'), 'articles/view_as_foo_bar.php');
		$keywords[] = 'edit_as_simple - '.sprintf(i18n::s('Branch out to %s'), 'articles/edit_as_simple.php');
		$keywords[] = 'skin_foo_bar - '.i18n::s('Apply a specific theme (in skins/foo_bar)');
		$keywords[] = 'variant_foo_bar - '.i18n::s('To load template_foo_bar.php instead of the regular template');
		$text = i18n::s('You may combine several keywords:').'<div id="options_list">'.Skin::finalize_list($keywords, 'compact').'</div>';

		Page::insert_script(
			'function append_to_options(keyword) {'."\n"
			.'	var target = $("#options");'."\n"
			.'	target.val(target.val() + " " + keyword);'."\n"
			.'}'."\n"
			.'$(function() {'."\n"
			.'	$("#options_list a").bind("click",function(){'."\n"
			.'		append_to_options($(this).text());'."\n"
			.'	}).css("cursor","pointer");'."\n"
			.'});'
			);

		return $text;
	}

	/**
	 * clear cache entries for one item
	 *
	 * @param array item attributes
	 */
	public static function clear(&$item) {

		// where this item can be displayed
		$topics = array('articles', 'sections', 'categories', 'users');

		// clear this page
		if(isset($item['id']))
			$topics[] = 'article:'.$item['id'];

		// clear the cache
		Cache::clear($topics);

	}

	/**
	 * count records for one anchor
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - article is protected (active='N'), but surfer is an associate, and we are not feeding someone
	 * - surfer is anonymous or the variant is 'boxes', and article has been officially published
	 * - logged surfers are restricted to their own articles, plus published articles
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param string the selected anchor (e.g., 'section:12')
	 * @param boolean FALSE to include sticky pages, TRUE otherwise
	 * @return int the resulting count, or NULL on error
	 */
	public static function count_for_anchor($anchor, $without_sticky=FALSE) {
		global $context;

		// sanity check
		if(!$anchor)
			return NULL;

		// restrict the query to addressable content
		$where = Articles::get_sql_where();

		// avoid sticky articles
		if($without_sticky)
			$where .= " AND (articles.rank >= 10000)";

		// anonymous surfers and subscribers will see only published articles
		if(!Surfer::is_member()) {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// logged surfers that are non-associates are restricted to their own articles, plus published articles
		} elseif(!Surfer::is_empowered()) {
			$where .= " AND ((articles.create_id=".Surfer::get_id().") OR (NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')))";
		}

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$context['now']."'))";

		// several anchors
		if(is_array($anchor)) {
			$items = array();
			foreach($anchor as $token)
				$items[] = "articles.anchor LIKE '".SQL::escape($token)."'";
			$where_anchor = join(' OR ', $items);

		// or only one
		} else
			$where_anchor = "articles.anchor LIKE '".SQL::escape($anchor)."'";

		// select among available items
		$query = "SELECT COUNT(*) as count"
			." FROM ".SQL::table_name('articles')." AS articles"
			." WHERE (".$where_anchor.") AND (".$where.")";

		return SQL::query_scalar($query);
	}

	/**
	 * get some statistics for one user
	 *
	 * @param int the selected user (e.g., '12')
	 * @return int pages for this user
	 *
	 * @see users/view.php
	 */
	public static function count_for_user($user_id) {
		global $context;

		// sanity check
		if(!$user_id)
			return NULL;
		$user_id = SQL::escape($user_id);

		// limit the scope of the request
		$where = Articles::get_sql_where();

		// list only published articles
		if((Surfer::get_id() != $user_id) && !Surfer::is_associate())
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// strip dead pages
		if((Surfer::get_id() != $user_id) && !Surfer::is_associate())
			$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$context['now']."'))";

		// the list of watched sections
		$watched_sections = "(SELECT CONCAT('section:', sections.id) AS target"
			."	FROM (".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('sections')." AS sections)"
			." WHERE (members.member = 'user:".SQL::escape($user_id)."')"
			."	AND (members.anchor LIKE 'section:%')"
			."	AND (sections.id = SUBSTRING(members.anchor, 9))"
			." ORDER BY sections.edit_date DESC, sections.title LIMIT 0, 1000)";

		// the list of forwarding sections
		$forwarding_sections = "(SELECT CONCAT('section:', sections.id) AS target"
			." FROM ".$watched_sections." AS anchors"
			.", ".SQL::table_name('sections')." AS sections"
			." WHERE (sections.anchor = anchors.target) AND (sections.options LIKE '%forward_notifications%')"
			." ORDER BY sections.edit_date DESC, sections.title LIMIT 0, 1000)";

		// look for pages in watched sections
		$query = "(SELECT articles.id FROM (".$watched_sections." UNION ".$forwarding_sections.") AS anchors"
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE (articles.anchor = anchors.target)"
			."	AND ".$where.") UNION ";

		// look for watched pages
		$query .= "(SELECT articles.id FROM (SELECT DISTINCT CAST(SUBSTRING(members.anchor, 9) AS UNSIGNED) AS target FROM ".SQL::table_name('members')." AS members WHERE (members.member LIKE 'user:".SQL::escape($user_id)."') AND (members.anchor LIKE 'article:%')) AS ids"
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE (articles.id = ids.target)"
			."	AND ".$where.")";

		// include articles assigned to this surfer
		if($these_items = Surfer::assigned_articles($user_id))
			$query = "(SELECT articles.id FROM ".SQL::table_name('articles')." AS articles"
				." WHERE articles.id IN (".join(', ', $these_items).")"
				."	AND ".$where.")"
				." UNION ".$query;

		// include articles owned by this surfer
		$query = "(SELECT articles.id FROM ".SQL::table_name('articles')." AS articles"
			." WHERE articles.owner_id = ".$user_id
			."	AND ".$where.")"
			." UNION ".$query;

		// count records
		return SQL::query_count($query);
	}

	/**
	 * delete one article
	 *
	 * @param int the id of the article to delete
	 * @return boolean TRUE on success, FALSE otherwise
	 *
	 * @see articles/delete.php
	 * @see services/blog.php
	 */
	public static function delete($id) {
		global $context;

		// load the record
		$item = Articles::get($id);
		if(!isset($item['id']) || !$item['id']) {
			Logger::error(i18n::s('No item has the provided id.'));
			return FALSE;
		}

		// delete related items
		Anchors::delete_related_to('article:'.$item['id']);

		// delete the record in the database
		$query = "DELETE FROM ".SQL::table_name('articles')." WHERE id = ".SQL::escape($item['id']);
		if(SQL::query($query) === FALSE)
			return FALSE;

		// remember overlay deletion
		if(isset($item['overlay']) && ($overlay = Overlay::load($item, 'article:'.$item['id'])))
			$overlay->remember('delete', $item, 'article:'.$item['id']);

		// job done
		return TRUE;
	}

	/**
	 * delete all articles for a given anchor
	 *
	 * @param string the anchor to check (e.g., 'section:123')
	 * @return void
	 *
	 * @see shared/anchors.php
	 */
	public static function delete_for_anchor($anchor) {
		global $context;

		// seek all records attached to this anchor
		$query = "SELECT id FROM ".SQL::table_name('articles')." AS articles "
			." WHERE articles.anchor LIKE '".SQL::escape($anchor)."'";
		if(!$result = SQL::query($query))
			return;

		// empty list
		if(!SQL::count($result))
			return;

		// delete silently all matching items
		while($row = SQL::fetch($result))
			Articles::delete($row['id']);
	}

	/**
	 * duplicate all articles for a given anchor
	 *
	 * This function duplicates records in the database, and changes anchors
	 * to attach new records as per second parameter.
	 *
	 * @param string the source anchor
	 * @param string the target anchor
	 * @return int the number of duplicated records
	 *
	 * @see shared/anchors.php
	 */
	public static function duplicate_for_anchor($anchor_from, $anchor_to) {
		global $context;

		// look for records attached to this anchor
		$count = 0;
		$query = "SELECT * FROM ".SQL::table_name('articles')." WHERE anchor LIKE '".SQL::escape($anchor_from)."'";
		if(($result = SQL::query($query)) && SQL::count($result)) {

			// the list of transcoded strings
			$transcoded = array();

			// process all matching records one at a time
			while($item = SQL::fetch($result)) {

				// a new id will be allocated
				$old_id = $item['id'];
				unset($item['id']);

				// creator has to be the person who duplicates
				unset($item['create_address']);
				unset($item['create_date']);
				unset($item['create_id']);
				unset($item['create_name']);

				unset($item['edit_address']);
				unset($item['edit_date']);
				unset($item['edit_id']);
				unset($item['edit_name']);

				// target anchor
				$item['anchor'] = $anchor_to;

				// actual duplication
				if($new_id = Articles::post($item)) {

					// more pairs of strings to transcode
					$transcoded[] = array('/\[article='.preg_quote($old_id, '/').'/i', '[article='.$new_id);
					$transcoded[] = array('/\[next='.preg_quote($old_id, '/').'/i', '[next='.$new_id);
					$transcoded[] = array('/\[previous='.preg_quote($old_id, '/').'/i', '[previous='.$new_id);

					// duplicate elements related to this item
					Anchors::duplicate_related_to('article:'.$old_id, 'article:'.$new_id);

					// stats
					$count++;
				}
			}

			// transcode in anchor
			if($anchor = Anchors::get($anchor_to))
				$anchor->transcode($transcoded);

		}

		// number of duplicated records
		return $count;
	}

	/**
	 * do whatever is necessary when a page has been published
	 *
	 * This function:
	 * - logs the publication
	 * - sends notification to watchers and to followers
	 * - "touches" the container of the page,
	 * - ping referred pages remotely (via the pingback protocol)
	 * - ping selected servers, if any
	 * - and triggers the hook 'publish'.
	 *
	 * The first parameter provides the watching context to consider. If call is related
	 * to the creation of a published page, the context is the section that hosts the new
	 * page. If call is related to a draft page that has been published, then the context
	 * is the page itself.
	 *
	 * This function is also able to notify followers of the surfer who has initiated the
	 * action.
	 *
	 * @param object the watching context
	 * @param array attributes of the published page
	 * @param object page overlay, if any
	 * @param boolean TRUE if dates should be left unchanged, FALSE otherwise
	 * @param boolean TRUE if followers should be notified, FALSE otherwise
	 */
	public static function finalize_publication($anchor, $item, $overlay=NULL, $silently=FALSE, $with_followers=FALSE) {
		global $context;

		// sanity check
		if(!isset($item['options']))
			$item['options'] = '';

		// notification to send by e-mail
		$mail = array();
		$mail['subject'] = sprintf(i18n::c('%s: %s'), strip_tags($anchor->get_title()), strip_tags($item['title']));
		$mail['notification'] = Articles::build_notification('publish', $item);
		$mail['headers'] = Mailer::set_thread('article:'.$item['id']);

		// allow the overlay to prevent notifications of watchers
		if(!is_object($overlay) || $overlay->should_notify_watchers($mail))
			$anchor->alert_watchers($mail, 'article:publish', ($item['active'] == 'N'));

		// never notify followers on private pages
		if(isset($item['active']) && ($item['active'] == 'N'))
			$with_followers = FALSE;

		// allow the overlay to prevent notifications of followers
		if(is_object($overlay) && !$overlay->should_notify_followers())
			$with_followers = FALSE;

		// send to followers of this user
		if($with_followers && Surfer::get_id()) {
			$mail['message'] = Mailer::build_notification($mail['notification'], 2);
			Users::alert_watchers('user:'.Surfer::get_id(), $mail);
		}

		// update anchors
		$anchor->touch('article:publish', $item['id'], $silently);

		// advertise public pages
		if(isset($item['active']) && ($item['active'] == 'Y')) {

			// expose links within the page
			$raw = '';
			if(isset($item['introduction']))
				$raw .= $item['introduction'];
			if(isset($item['source']))
				$raw .= ' '.$item['source'];
			if(isset($item['description']))
				$raw .= ' '.$item['description'];

			// pingback to referred links, if any
			include_once $context['path_to_root'].'links/links.php';
			Links::ping($raw, 'article:'.$item['id']);

			// ping servers, if any
			Servers::notify($anchor->get_url());

		}

		// 'publish' hook
		if(is_callable(array('Hooks', 'include_scripts')))
			Hooks::include_scripts('publish', $item['id']);

		// log page publication
		$label = sprintf(i18n::c('Publication: %s'), strip_tags($item['title']));
		$poster = Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']);
		if(is_object($anchor))
			$description = sprintf(i18n::c('Sent by %s in %s'), $poster, $anchor->get_title());
		else
			$description = sprintf(i18n::c('Sent by %s'), $poster);
		$description .= "\n\n".'<a href="'.Articles::get_permalink($item).'">'.$item['title'].'</a>';
		Logger::notify('articles/articles.php: '.$label, $description);

	}

	/**
	 * do whatever is necessary when a page has been submitted
	 *
	 * This function:
	 * - logs the submission
	 * - sends notification to owners that are also watchers
	 * - "touches" the container of the page,
	 * - and triggers the hook 'submit'.
	 *
	 * The first parameter provides the watching context to consider. If call is related
	 * to the creation of a published page, the context is the section that hosts the new
	 * page. If call is related to a draft page that has been published, then the context
	 * is the page itself.
	 *
	 * This function is also able to notify followers of the surfer who has initiated the
	 * action.
	 *
	 * @param object the watching context
	 * @param array attributes of the published page
	 * @param object page overlay, if any
	 */
	public static function finalize_submission($anchor, $item, $overlay=NULL) {
		global $context;

		// notification to send by e-mail
		$mail = array();
		$mail['subject'] = sprintf(i18n::c('%s: %s'), strip_tags($anchor->get_title()), strip_tags($item['title']));
		$mail['notification'] = Articles::build_notification('submit', $item);
		$mail['message'] = Mailer::build_notification($mail['notification'], 1);
		$mail['headers'] = Mailer::set_thread('article:'.$item['id']);

		// allow the overlay to prevent notifications of watcherss
		if(!is_object($overlay) || $overlay->should_notify_watchers($mail)) {

			// look for anchor owner, and climb upwards
			$owners = array();
			$handle = $anchor->get_reference();
			while($handle && ($container = Anchors::get($handle))) {

				// consider owner of this level
				if(($owner_id = $container->get_value('owner_id')) && ($owner = Users::get($owner_id))) {

					// notify this owner, but not if he is the surfer
					if(Surfer::get_id() != $owner_id)
						Users::alert($owner, $mail);

				}

				// move to upper level
				$handle = $container->get_parent();
			}

		}

		// update anchors
		$anchor->touch('article:submit', $item['id']);

		// 'submit' hook
		if(is_callable(array('Hooks', 'include_scripts')))
			Hooks::include_scripts('submit', $item['id']);

		// log page submission
		$label = sprintf(i18n::c('Submission: %s'), strip_tags($item['title']));
		$poster = Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']);
		if(is_object($anchor))
			$description = sprintf(i18n::c('Sent by %s in %s'), $poster, $anchor->get_title());
		else
			$description = sprintf(i18n::c('Sent by %s'), $poster);
		$description .= "\n\n".'<a href="'.Articles::get_permalink($item).'">'.$item['title'].'</a>';
		Logger::notify('articles/articles.php: '.$label, $description);

	}

	/**
	 * do whatever is necessary when a page has been updated
	 *
	 * This function:
	 * - logs the update
	 * - sends notification to watchers and to followers
	 * - "touches" the container of the page,
	 * - ping referred pages remotely (via the pingback protocol)
	 * - ping selected servers, if any
	 * - and triggers the hook 'update'.
	 *
	 * The first parameter provides the watching context to consider. If call is related
	 * to the creation of a published page, the context is the section that hosts the new
	 * page. If call is related to a draft page that has been published, then the context
	 * is the page itself.
	 *
	 * This function is also able to notify followers of the surfer who has initiated the
	 * action.
	 *
	 * @param object the watching context
	 * @param array attributes of the published page
	 * @param object page overlay, if any
	 * @param boolean TRUE if dates should be left unchanged, FALSE otherwise
	 * @param boolean TRUE if watchers should be notified, FALSE otherwise
	 * @param boolean TRUE if followers should be notified, FALSE otherwise
	 */
	public static function finalize_update($anchor, $item, $overlay=NULL, $silently=FALSE, $with_watchers=TRUE, $with_followers=FALSE) {
		global $context;

		// proceed only if the page has been published
		if(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE)) {

			// notification to send by e-mail
			$mail = array();
			$mail['subject'] = sprintf(i18n::c('%s: %s'), i18n::c('Update'), strip_tags($item['title']));
			$mail['notification'] = Articles::build_notification('update', $item);
			$mail['headers'] = Mailer::set_thread('article:'.$item['id']);

			// allow the overlay to prevent notifications of watcherss
			if(is_object($overlay) && !$overlay->should_notify_watchers($mail))
				$with_watchers = FALSE;

			// send to watchers of this page, and to watchers upwards
			if($with_watchers && ($handle = new Article())) {
				$handle->load_by_content($item, $anchor);
				$handle->alert_watchers($mail, 'article:update', ($item['active'] == 'N'));
			}

			// never notify followers on private pages
			if(isset($item['active']) && ($item['active'] == 'N'))
				$with_followers = FALSE;

			// allow the overlay to prevent notifications of followers
			if(is_object($overlay) && !$overlay->should_notify_followers())
				$with_followers = FALSE;

			// send to followers of this user
			if($with_followers && Surfer::get_id()) {
				$mail['message'] = Mailer::build_notification($mail['notification'], 2);
				Users::alert_watchers('user:'.Surfer::get_id(), $mail);
			}

			// update anchors
			$anchor->touch('article:update', $item['id'], $silently);

			// advertise public pages
			if(isset($item['active']) && ($item['active'] == 'Y')) {

				// expose links within the page
				$raw = '';
				if(isset($item['introduction']))
					$raw .= $item['introduction'];
				if(isset($item['source']))
					$raw .= ' '.$item['source'];
				if(isset($item['description']))
					$raw .= ' '.$item['description'];

				// pingback to referred links, if any
				include_once $context['path_to_root'].'links/links.php';
				Links::ping($raw, 'article:'.$item['id']);

				// ping servers, if any
				Servers::notify($anchor->get_url());

			}

		}

		// 'update' hook
		if(is_callable(array('Hooks', 'include_scripts')))
			Hooks::include_scripts('update', $item['id']);

		// log page update
		$label = sprintf(i18n::c('Update: %s'), strip_tags($item['title']));
		$poster = Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']);
		$description = sprintf(i18n::c('Updated by %s in %s'), $poster, $anchor->get_title());
		$description .= "\n\n".'<a href="'.Articles::get_permalink($item).'">'.$item['title'].'</a>';
		Logger::notify('articles/articles.php: '.$label, $description);

	}

	/**
	 * get one article by id, nick name or by handle
	 *
	 * @param int the id of the article
	 * @param boolean TRUE to always fetch a fresh instance, FALSE to enable cache
	 * @return the resulting $item array, with at least keys: 'id', 'title', 'description', etc.
	 */
	public static function get($id, $mutable=FALSE) {
		$output = Articles::get_attributes($id, '*', $mutable);
		return $output;
	}

	/**
	 * get only some attributes
	 *
	 * @param int the id of the article
	 * @param mixed names of the attributes to return
	 * @param boolean TRUE to always fetch a fresh instance, FALSE to enable cache
	 * @return the resulting $item array, with at least keys: 'id', 'title', 'description', etc.
	 */
	public static function &get_attributes($id, $attributes, $mutable=FALSE) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// ensure proper unicode encoding
		$id = (string)$id;
		$id = utf8::encode($id);
                
                // filter id from reference if parameter given that way
                if(substr($id, 0, 8) === 'article:')
                      $id = strstr ($id, ':');

		// cache previous answers
		static $cache;
		if(!is_array($cache))
			$cache = array();

		// cache hit, but only for immutable objects
		if(!$mutable && isset($cache[$id]))
			return $cache[$id];

		// search by id
		if(is_numeric($id)) {
			$query = "SELECT ".SQL::escape($attributes)." FROM ".SQL::table_name('articles')
				." WHERE (id = ".SQL::escape((integer)$id).")";
			// do the job
			$output = SQL::query_first($query);
		}
		// or look for given name of handle
		else {
			$query = "SELECT ".SQL::escape($attributes)." FROM ".SQL::table_name('articles')
				." WHERE (nick_name LIKE '".SQL::escape($id)."') OR (handle LIKE '".SQL::escape($id)."')";
			$count=SQL::query_count($query);
			if($count==1)
				// do the job
				$output = SQL::query_first($query);
			elseif ($count>1) {// result depending language give by $context['page_language']
				if (($_SESSION['surfer_language']=='none'))	
					$language=$context['language'];
				else 
					$language=$_SESSION['surfer_language'];
				$result = SQL::query($query);
				while($item = SQL::fetch($result)) {
				 	$output=$item; // return last by default
					if ($item['language'] == $language) {
					 	$output=$item;
					 	break;
					}
				}
			}

		}

		// save in cache, but only on generic request
		if(isset($output['id']) && ($attributes == '*') && (count($cache) < 1000))
			$cache[$id] = $output;

		// return by reference
		return $output;
	}

	/**
	 * list articles with a given overlay identifier
	 *
	 * @param string the target overlay identifier
	 * @return array of page ids that match the provided identifier, else NULL
	 */
	public static function get_ids_for_overlay($overlay_id) {
		global $context;

		// limit the overall list of results
		$query = "SELECT articles.id FROM ".SQL::table_name('articles')." AS articles"
			." WHERE (overlay_id LIKE '".SQl::escape($overlay_id)."') AND ".Articles::get_sql_where()
			." LIMIT 5000";
		if(!$result = SQL::query($query)) {
			$output = NULL;
			return $output;
		}

		// process all matching records
		$ids = array();
		while($item = SQL::fetch($result))
			$ids[] = $item['id'];

		// return a list of ids
		return $ids;
	}

	/**
	 * get the newest article for one anchor
	 *
	 * This function is to be used while listing articles for one anchor.
	 * It provides the last edited article for this anchor.
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but surfer is a logged user
	 * - article is protected (active='N'), but surfer is an associate
	 * - article has been officially published
	 * - an expiry date has not been defined, or is not yet passed
	 * - (if 2nd parameter is TRUE) article is not sticky (rank >= 10000)
	 *
	 * @param int the id of the anchor
	 * @param boolean FALSE to include sticky pages, TRUE otherwise
	 * @return the resulting $item array, with at least keys: 'id', 'title', 'description', etc.
	 *
	 * @see index.php
	 */
	public static function &get_newest_for_anchor($anchor, $without_sticky=FALSE) {
		global $context;

		// restrict the query to addressable content
		$where = Articles::get_sql_where();

		// just get the newest page
		if($anchor) {

			// several anchors
			if(is_array($anchor)) {
				$items = array();
				foreach($anchor as $token)
					$items[] = "articles.anchor LIKE '".SQL::escape($token)."'";
				$where_anchor = join(' OR ', $items);

			// or only one
			} else
				$where_anchor = "articles.anchor LIKE '".SQL::escape($anchor)."'";

			$where = '('.$where_anchor.') AND ('.$where.')';

		}

		// always only consider published articles
		$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
			." AND (articles.publish_date < '".$context['now']."')";

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$context['now']."'))";

		// avoid sticky articles
		if($without_sticky)
			$where .= " AND (articles.rank >= 10000)";

		// the list of articles
		$query = "SELECT * FROM ".SQL::table_name('articles')." AS articles"
			." WHERE ".$where
			." ORDER BY articles.rank, articles.edit_date DESC, articles.title LIMIT 0,1";

		$output = SQL::query_first($query);
		return $output;
	}

	/**
	 * get url of next article
	 *
	 * This function is used to build navigation bars.
	 *
	 * @param array the current item
	 * @param string reference to the current anchor (e.g., 'section:123')
	 * @param string the order, either 'date' or 'title' or 'rank'
	 * @return an array ($url, $title)
	 *
	 * @see sections/section.php
	 */
	public static function get_next_url($item, $anchor, $order='edition') {
		global $context;

		// sanity check
		if(!is_array($item))
			return $item;

		// limit the scope of the request
		$where = "articles.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR articles.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR articles.active='N'";

		// include articles from managed sections
		if($my_sections = Surfer::assigned_sections())
			$where .= " OR articles.anchor IN ('section:".join("', 'section:", $my_sections)."')";

		// include managed pages for editors
		if($my_articles = Surfer::assigned_articles())
			$where .= " OR articles.id IN (".join(', ', $my_articles).")";

		$where = '('.$where.')';

		// always only consider published articles, except for associates and editors
		if(!Surfer::is_empowered())
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$context['now']."'))";

		// depending on selected sequence
		if($order == 'edition') {
            $match = "articles.rank > ".SQL::escape($item['rank'])." OR (articles.edit_date < '".SQL::escape($item['edit_date'])."' AND articles.rank = ".SQL::escape($item['rank']).")";
            $order = 'articles.rank, articles.edit_date DESC, articles.title';
        } elseif($order == 'publication') {
            $match = "articles.rank > ".SQL::escape($item['rank'])." OR (articles.publish_date < '".SQL::escape($item['publish_date'])."' AND articles.rank = ".SQL::escape($item['rank']).")";
            $order = 'articles.rank, articles.publish_date DESC, articles.title';
        } elseif($order == 'rating') {
            $match = "articles.rank > ".SQL::escape($item['rank'])." OR (articles.rating_sum < ".SQL::escape($item['rating_sum'])." AND articles.rank = ".SQL::escape($item['rank']).")";
            $order = 'articles.rank, articles.rating_sum DESC, articles.edit_date DESC';
        } elseif($order == 'title') {
            $match = "articles.rank > ".SQL::escape($item['rank'])." OR (articles.title > '".SQL::escape($item['title'])."' AND articles.rank = ".SQL::escape($item['rank']).")";
            $order = 'articles.rank, articles.title';
        } else
            return "unknown order '".$order."'";

		// query the database
		$query = "SELECT id, title, nick_name, anchor FROM ".SQL::table_name('articles')." AS articles "
			." WHERE (articles.anchor LIKE '".SQL::escape($anchor)."') AND (".$match.") AND (".$where.")"
			." ORDER BY ".$order." LIMIT 0, 1";
		if(!$result = SQL::query($query))
			return NULL;

		// no result
		if(!SQL::count($result))
			return NULL;

		// return url of the first item of the list
		$item = SQL::fetch($result);
		return array(Articles::get_permalink($item), $item['title']);
	}

	/**
	 * get permanent address
	 *
	 * @param array page attributes
	 * @return string the permanent web address to this item, relative to the installation path
	 */
	public static function get_permalink($item) {
		global $context;

		// sanity check
		if(!isset($item['id']))
			throw new Exception('bad input parameter');				

		// get host to this page
		$vhost = Sections::get_vhost($item['anchor']);		

		// absolute link
		return $vhost.Articles::get_url($item['id'], 'view', $item['title'], isset($item['nick_name']) ? $item['nick_name'] : '');
	}

	/**
	 * get url of previous article
	 *
	 * This function is used to build navigation bars.
	 *
	 * @param array the current item
	 * @param string reference to the anchor (e.g., 'section:123')
	 * @param string the order
	 * @return an array($url, $title)
	 *
	 * @see sections/section.php
	 */
	public static function get_previous_url($item, $anchor, $order='edition') {
		global $context;

		// sanity check
		if(!is_array($item))
			return $item;

		// limit the scope of the request
		$where = "articles.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR articles.active='R'";
		if(Surfer::is_empowered('S'))
			$where .= " OR articles.active='N'";

		// include articles from managed sections
		if($my_sections = Surfer::assigned_sections())
			$where .= " OR articles.anchor IN ('section:".join("', 'section:", $my_sections)."')";

		// include managed pages for editors
		if($my_articles = Surfer::assigned_articles())
			$where .= " OR articles.id IN (".join(', ', $my_articles).")";

		$where = '('.$where.')';

		// always only consider published articles, except for associates and editors
		if(!Surfer::is_empowered())
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$context['now']."'))";

		// depending on selected sequence
		if($order == 'edition') {
            $match = "articles.rank < ".SQL::escape($item['rank'])." OR (articles.edit_date > '".SQL::escape($item['edit_date'])."' AND articles.rank = ".SQL::escape($item['rank']).")";
            $order = 'articles.rank DESC, articles.edit_date, articles.title';
        } elseif($order == 'publication') {
            $match = "articles.rank < ".SQL::escape($item['rank'])." OR (articles.publish_date > '".SQL::escape($item['publish_date'])."' AND articles.rank = ".SQL::escape($item['rank']).")";
            $order = 'articles.rank DESC, articles.publish_date, articles.title';
        } elseif($order == 'rating') {
            $match = "articles.rank < ".SQL::escape($item['rank'])." OR (articles.rating_sum > ".SQL::escape($item['rating_sum'])." AND articles.rank = ".SQL::escape($item['rank']).")";
            $order = 'articles.rank DESC, articles.rating_sum, articles.edit_date';
        } elseif($order == 'title') {
            $match = "articles.rank < ".SQL::escape($item['rank'])." OR (articles.title < '".SQL::escape($item['title'])."' AND articles.rank = ".SQL::escape($item['rank']).")";
            $order = 'articles.rank DESC, articles.title DESC';
        } else
            return "unknown order '".$order."'";

		// query the database
		$query = "SELECT id, title, nick_name, anchor FROM ".SQL::table_name('articles')." AS articles "
			." WHERE (articles.anchor LIKE '".SQL::escape($anchor)."') AND (".$match.") AND (".$where.")"
			." ORDER BY ".$order." LIMIT 0, 1";
		if(!$result = SQL::query($query))
			return NULL;

		// no result
		if(!SQL::count($result))
			return NULL;

		// return url of the first item of the list
		$item = SQL::fetch($result);
		return array(Articles::get_permalink($item), $item['title']);
	}

	/**
	 * get short url for an article
	 *
	 * @param array page attributes
	 * @return string the short link
	 */
	public static function &get_short_url($item) {
		$output = 'a~'.reduce_number($item['id']);
		return $output;
	}

	/**
	 * restrict the scope of SQL query
	 *
	 * @return string to be inserted into a SQL statement
	 */
	public static function get_sql_where() {

		// display active items
		$where = "articles.active='Y'";

		// add restricted items to members and for trusted hosts, or if teasers are allowed
		if(Surfer::is_logged() || Surfer::is_trusted() || Surfer::is_teased())
			$where .= " OR articles.active='R'";

		// include hidden items for associates and for trusted hosts, or if teasers are allowed
		if(Surfer::is_associate() || Surfer::is_trusted() || Surfer::is_teased())
			$where .= " OR articles.active='N'";

		// include private items that the surfer can access
		else {

			// include articles from managed sections
			if($my_sections = Surfer::assigned_sections())
				$where .= " OR articles.anchor IN ('section:".join("', 'section:", $my_sections)."')";

			// include managed pages for editors
			if($my_articles = Surfer::assigned_articles())
				$where .= " OR articles.id IN (".join(', ', $my_articles).")";

		}

		// end of active filter
		$where = '('.$where.')';

		// job done
		return $where;
	}

	/**
	 * build a reference to an article
	 *
	 * Depending on parameter '[code]with_friendly_urls[/code]' and on action,
	 * following results can be observed:
	 *
	 * - view - articles/view.php?id=123 or articles/view.php/123 or article-123
	 *
	 * - other - articles/edit.php?id=123 or articles/edit.php/123 or article-edit/123
	 *
	 * If a third parameter is provided, it may be used to achieve a nice link,
	 * such as the following:
	 * [php]
	 * Articles::get_url(123, 'view', 'A very nice page');
	 * [/php]
	 * will result to
	 * [snippet]
	 * http://server/article-123-a-very-nice-page
	 * [/snippet]
	 *
	 * If a fourth parameter is provided, it will take over the third one. This
	 * is used to leverage nick names in YACS, as per the following invocation:
	 * [php]
	 * Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']);
	 * [/php]
	 *
	 * @param int the id of the article to handle
	 * @param string the expected action ('view', 'print', 'edit', 'delete', ...)
	 * @param string additional data, such as page name, if any
	 * @param string alternate name, if any, to take over on previous parameter
	 * @return string a normalized reference
	 *
	 * @see control/configure.php
	 */
	public static function get_url($id, $action='view', $name=NULL, $alternate_name=NULL) {
		global $context;

		// use alternate name instead of regular name, if one is provided
		if($alternate_name && ($context['with_alternate_urls'] == 'Y'))
			$name = str_replace('_', ' ', $alternate_name);

		// the service to check for updates
		if($action == 'check') {
			if($context['with_friendly_urls'] == 'Y')
				return 'services/check.php/article/'.rawurlencode($id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'services/check.php?id='.urlencode('article:'.$id);
			else
				return 'services/check.php?id='.urlencode('article:'.$id);
		}

		// invite someone to participate
		if($action == 'invite') {
			if($name)
				return 'articles/invite.php?id='.urlencode($id).'&amp;invited='.urlencode($name);
			else
				return 'articles/invite.php?id='.urlencode($id);
		}

		// i like this page
		if($action == 'like') {
			if($context['with_friendly_urls'] == 'Y')
				return 'articles/rate.php/'.rawurlencode($id).'?rating=5&amp;referer='.urlencode($context['self_url']);
			elseif($context['with_friendly_urls'] == 'R')
				return 'article-rate/'.rawurlencode($id).'?rating=5&amp;referer='.urlencode($context['self_url']);
			else
				return 'articles/rate.php?id='.urlencode($id).'&amp;rating=5&amp;referer='.urlencode($context['self_url']);
		}

		// i dislike this page
		if($action == 'dislike') {
			if($context['with_friendly_urls'] == 'Y')
				return 'articles/rate.php/'.rawurlencode($id).'?rating=1&amp;referer='.urlencode($context['self_url']);
			elseif($context['with_friendly_urls'] == 'R')
				return 'article-rate/'.rawurlencode($id).'?rating=1&amp;referer='.urlencode($context['self_url']);
			else
				return 'articles/rate.php?id='.urlencode($id).'&amp;rating=1&amp;referer='.urlencode($context['self_url']);
		}

		// check the target action
		if(!preg_match('/^(delete|describe|duplicate|edit|export|fetch_as_msword|fetch_as_pdf|invite|lock|mail|move|navigate|own|print|publish|rate|stamp|unpublish|view)$/', $action))
			return 'articles/'.$action.'.php?id='.urlencode($id).'&action='.urlencode($name);

		// normalize the link
		return normalize_url(array('articles', 'article'), $action, $id, $name);
	}

	/**
	 * check if an option has been set for a page
	 *
	 * The option can be set either in the page itself, or cascaded from parent sections.
	 *
	 * @param string the option
	 * @param object parent anchor, if any
	 * @param array page attributes
	 * @return TRUE or FALSE
	 */
	 public static function has_option($option, $anchor=NULL, $item=NULL) {
		global $context;

		// sanity check
		if(!$option)
			return FALSE;

		// 'variant' matches with 'variant_red_background', return 'red_background'
		if(isset($item['options']) && preg_match('/\b'.$option.'_(.+?)\b/i', $item['options'], $matches))
			return $matches[1];

		// exact match, return TRUE
		if(isset($item['options']) && (strpos($item['options'], $option) !== FALSE))
			return TRUE;

		// check in anchor
		if(is_object($anchor) && ($result = $anchor->has_option($option)))
			return $result;

		// sorry
		return FALSE;
	}

	/**
	 * set the hits counter - errors are not reported, if any
	 *
	 * @param the id of the article to update
	 */
	public static function increment_hits($id) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return;

		// do the job
		$query = "UPDATE ".SQL::table_name('articles')." SET hits=hits+1 WHERE id = ".SQL::escape($id);
		SQL::query($query);
	}

	/**
	 * has the surfer been assigned to this article?
	 *
	 * This would be the case either:
	 * - if he is a member and has been granted the editor privilege
	 * - if he is a subscriber and has been granted the reader privilege
	 *
	 * @param int the id of the target article
	 * @param int optional id to impersonate
	 * @return TRUE or FALSE
	 */
	public static function is_assigned($id, $surfer_id=NULL) {
		global $context;

		// no impersonation
		if(!$surfer_id) {

			// a managed article requires an authenticated user
			if(!Surfer::is_logged())
				return FALSE;

			// use surfer profile
			$surfer_id = Surfer::get_id();

		}

		// ensure this article has been linked to this user
		return Members::check('user:'.$surfer_id, 'article:'.$id);
	}

	/**
	 * check if a surfer can edit a page
	 *
	 * @param object parent anchor, if any
	 * @param array page attributes
	 * @param int optional reference to some user profile
	 * @return TRUE or FALSE
	 */
	 public static function is_editable($anchor=NULL, $item=NULL, $user_id=NULL) {
		global $context;

		// id of requesting user
		if(!$user_id) {
			if(!Surfer::get_id())
				return FALSE;
			$user_id = Surfer::get_id();
		}

		// surfer is an editor of this page
		if(Members::check('user:'.$user_id, 'article:'.$item['id']))
			return TRUE;

		// surfer is assigned to parent container
		if(is_object($anchor) && $anchor->is_assigned($user_id))
			return TRUE;

		// sorry
		return FALSE;
	}

	/**
	 * check if a surfer owns a page
	 *
	 * @param array page attributes
	 * @param object cascade to parent if set
	 * @param boolean FALSE if the surfer can be an editor of parent section
	 * @param int optional reference to some user profile
	 * @return TRUE or FALSE
	 */
	 public static function is_owned($item=NULL, $anchor=NULL, $strict=FALSE, $user_id=NULL) {
		global $context;

		// ownership requires to be authenticated
		if(!$user_id) {
			if(!Surfer::get_id())
				return FALSE;
			$user_id = Surfer::get_id();
		}

		// surfer owns this page
		if(isset($item['owner_id']) && ($item['owner_id'] == $user_id))
			return TRUE;

		// do not look upwards
		if(!$anchor || !is_object($anchor))
			return FALSE;

		// associates can do what they want
		if(Surfer::is($user_id) && Surfer::is_associate())
			return TRUE;

		// surfer owns parent container
		if($anchor->is_owned($user_id))
			return TRUE;

		// page has not been created yet, section is not private, and surfer is member --not subscriber
		// Alexis => desactivated cause it's rejected later
		//if(!$strict && !isset($item['id']) && Surfer::is_member() && is_object($anchor) && !$anchor->is_hidden())
		//	return TRUE;

		// page is not private, and surfer is editor --not subscriber-- of parent container
		if(!$strict && isset($item['active']) && ($item['active'] != 'N') && Surfer::is_member() && is_object($anchor) && $anchor->is_assigned($user_id))
			return TRUE;

		// sorry
		return FALSE;
	}

	/**
	 * is the surfer watching this page?
	 *
	 * @param int the id of the target article
	 * @param int optional id to impersonate
	 * @return TRUE or FALSE
	 */
	public static function is_watched($id, $surfer_id=NULL) {
		global $context;

		// no impersonation
		if(!$surfer_id) {

			// a managed article requires an authenticated user
			if(!Surfer::is_logged())
				return FALSE;

			// use surfer profile
			$surfer_id = Surfer::get_id();

		}

		// ensure this article has been linked to this user
		return Members::check('article:'.$id, 'user:'.$surfer_id);
	}

	/**
	 * list most recent articles
	 *
	 * Items order is provided by the layout.
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param mixed the list variant, if any
	 * @param string stamp of the minimum publication date to be considered
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	public static function &list_($offset=0, $count=10, $layout='decorated', $since=NULL) {
		global $context;

		// define items order
		if(is_callable(array($layout, 'items_order')))
			$order = $layout->items_order();
		if(!isset($order) || !$order)
			$order = 'publication';

		// ask for ordered articles
		$output =& Articles::list_by($order, $offset, $count, $layout, $since);
		return $output;
	}

	/**
	 * list articles
	 *
	 * The ordering method is provided as first parameter:
	 * - 'draft' - order by reverse date of modification, but only draft pages
	 * - 'edition' - order by reverse date of modification
	 * - 'expiry' - order by reverse expiry date, and consider only expired articles
	 * - 'future' - order by reverse date of publication, and consider only future publication dates
	 * - 'hits' - order by reverse count of hits
	 * - 'publication' - order by reverse date of publication
	 * - 'random' - use random order
	 * - 'rating' - order by reverse number of points
	 * - 'review' - order by MAX(date of last modification, date of last review)
	 * - 'unread' - order by count of hits
	 *
	 * @param string order of resulting set
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param mixed the list variant, if any
	 * @param string stamp of the minimum publication date to be considered
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	public static function &list_by($order=NULL, $offset=0, $count=10, $layout='decorated', $since=NULL) {
		global $context;

		// restrict the query to addressable content
		$where = Articles::get_sql_where();

		// list only draft articles
		if($order == 'draft')
			$where .= " AND ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))";

		// list only articles published in the future
		elseif($order == 'future')
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date > '".$context['now']."')";

		// list only published articles, if not associate or if looking for less popular
		elseif(!Surfer::is_associate() || ($order != 'unread'))
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// list articles published after some date
		if($since && ($since > NULL_DATE))
			$where .= " AND (articles.publish_date > '".$since."')";

		// consider only dead articles
		if($order == 'expiry')
			$where .= " AND ((articles.expiry_date > '".NULL_DATE."') AND (articles.expiry_date <= '".$context['now']."'))";

		// else consider live articles
		else
			$where .= " AND ((articles.expiry_date is NULL) "
					."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$context['now']."'))";

		// avoid articles pushed away from the front page
		$sections_where = '';
		if(isset($context['skin_variant']) && ($context['skin_variant'] == 'home')) {
			$sections_where .= " AND (sections.index_map != 'N')";
		}

		// composite fields
		$more_fields = '';
		if($order == 'review')
			$more_fields = ', GREATEST(articles.edit_date, articles.review_date) AS stamp';

		// order of the resulting set
		$order = Articles::_get_order($order);

		// reference sections
		if($sections_where)
			$query = "SELECT articles.*".$more_fields
				." FROM (".SQL::table_name('articles')." AS articles"
				.", ".SQL::table_name('sections')." AS sections)"
				." WHERE ((articles.anchor_type LIKE 'section') AND (articles.anchor_id = sections.id)) AND ".$where.$sections_where
				." ORDER BY ".$order." LIMIT ".$offset.','.$count;

		// only select articles
		else
			$query = "SELECT articles.*".$more_fields
				." FROM ".SQL::table_name('articles')." AS articles"
				." WHERE ".$where
				." ORDER BY ".$order." LIMIT ".$offset.','.$count;

		// actual request to the database
		$output =& Articles::list_selected(SQL::query($query), $layout);
		return $output;
	}

	/**
	 * list all editors of a page
	 *
	 * This function lists editors of this page, or of any parent section.
	 *
	 * @param array attributes of the page
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface adapted to list of users
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 */
	public static function list_editors_by_name($item, $offset=0, $count=7, $variant='comma5') {
		global $context;

		// this page itself
		$anchors = array('article:'.$item['id']);

		// look at parents
		if($anchor = Anchors::get($item['anchor'])) {

			// look for editors of parent section
			$anchors[] = $anchor->get_reference();

			// look for editors of any ancestor
			$handle = $anchor->get_parent();
			while($handle && ($parent = Anchors::get($handle))) {
				$anchors[] = $handle;
				$handle = $parent->get_parent();
			}

		}

		// list users assigned to any of these anchors
		return Members::list_editors_for_member($anchors, $offset, $count, $variant);
	}

	/**
	 * list articles attached to one anchor
	 *
	 * The ordering method is provided by layout.
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - article is protected (active='N'), but surfer is an associate, and we are not feeding someone
	 * - surfer is anonymous, and article has been officially published
	 * - logged surfers are restricted to their own articles, plus published articles
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param mixed, either a string the target anchor, or an array of anchors
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @param boolean FALSE to include sticky pages, TRUE otherwise
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	public static function &list_for_anchor($anchor, $offset=0, $count=10, $layout='no_anchor', $without_sticky=FALSE) {
		global $context;

		// define items order
		if(is_callable(array($layout, 'items_order')))
			$order = $layout->items_order();
		if(!isset($order) || !$order)
			$order = 'edition';

		// ask for ordered items
		$output =& Articles::list_for_anchor_by($order, $anchor, $offset, $count, $layout, $without_sticky);
		return $output;
	}

	/**
	 * list articles attached to one anchor
	 *
	 * The ordering method is provided as first parameter:
	 * - 'draft' - order by reverse date of modification, but only draft pages
	 * - 'edition' - order by rank, then by reverse date of modification
	 * - 'hits' - order by reverse number of hits, then by reverse date of publication
	 * - 'overlay' - order by overlay_id
	 * - 'publication' - order by rank, then by reverse date of publication
	 * - 'random' - use random order
	 * - 'rating' - order by rank, then by reverse number of points
	 * - 'reverse_rank'
	 * - 'reverse_title'
	 * - 'title' - order by rank, then by titles
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - article is protected (active='N'), but surfer is an associate, and we are not feeding someone
	 * - surfer is anonymous, and article has been officially published
	 * - logged surfers are restricted to their own articles, plus published articles
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param string order of resulting set
	 * @param mixed, either a string the target anchor, or an array of anchors
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param mixed the layout, if any
	 * @param boolean FALSE to include sticky pages, TRUE otherwise
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	public static function &list_for_anchor_by($order, $anchor, $offset=0, $count=10, $layout='no_anchor', $without_sticky=FALSE) {
		global $context;

		// restrict the query to addressable content
		$where = Articles::get_sql_where();

		// avoid sticky articles
		if($without_sticky)
			$where .= " AND (articles.rank >= 10000)";

		// list only draft articles
		if($order == 'draft')
			$where .= " AND ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))";

		// list only published articles
		elseif($order == 'publication')
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))";

		// provide published pages to anonymous surfers
		elseif(!Surfer::is_logged()) {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// logged surfers that are not empowered are restricted to their own articles, plus published articles
		} elseif(!Surfer::is_member() || !is_callable(array('Surfer', 'is_empowered')) || !Surfer::is_empowered()) {
			$where .= " AND ((articles.owner_id=".Surfer::get_id().") OR (NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')))";
		}

		// only consider live articles, except for associates and editors
		if(is_callable(array('Surfer', 'is_empowered')) && !Surfer::is_empowered()) {
			$where .= " AND ((articles.expiry_date is NULL) "
					."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$context['now']."'))";
		}

		// several anchors
		if(is_array($anchor)) {
			$items = array();
			foreach($anchor as $token)
				$items[] = "articles.anchor LIKE '".SQL::escape($token)."'";
			$where_anchor = join(' OR ', $items);

		// or only one
		} else
			$where_anchor = "articles.anchor LIKE '".SQL::escape($anchor)."'";

		// order items
		$order = Articles::_get_order($order, (is_array($anchor) && (count($anchor) > 1)));

		// the list of articles
		$query = "SELECT articles.*"
			." FROM ".SQL::table_name('articles')." AS articles"
			." WHERE (".$where_anchor.") AND (".$where.")"
			." ORDER BY ".$order." LIMIT ".$offset.','.$count;

		$output =& Articles::list_selected(SQL::query($query), $layout);
		return $output;
	}

	/**
	 * list articles for one author
	 *
	 * @param string order of resulting set
	 * @param int the id of the author of the article
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param mixed the layout, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see users/view.php
	 */
	public static function &list_for_author_by($order, $author_id, $offset=0, $count=10, $layout='no_author') {
		global $context;

		// sanity check
		if(!$author_id) {
			$output = NULL;
			return $output;
		}
		$author_id = SQL::escape($author_id);

		// list all of my articles
		if(Surfer::get_id() == $author_id)
			$where = "(articles.active IN ('Y', 'R', 'N'))";

		// else restrict the scope of this request
		else
			$where = Articles::get_sql_where();

		// list only articles contributed by this author
		$where .= " AND ((articles.create_id = ".$author_id.") OR (articles.owner_id = ".$author_id."))";

		// only original author and associates will see draft articles
		if(!Surfer::is_member() || (!Surfer::is_associate() && (Surfer::get_id() != $author_id)))
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$context['now']."'))";

		// order items
		$order = Articles::_get_order($order);

		// the list of articles
		$query = "SELECT articles.*"
			." FROM ".SQL::table_name('articles')." AS articles"
			." WHERE (".$where.")"
			." ORDER BY ".$order." LIMIT ".$offset.','.$count;

		$output =& Articles::list_selected(SQL::query($query), $layout);
		return $output;
	}

	/**
	 * list these articles
	 *
	 * The first parameter can be either a string containing several ids or nick
	 * names separated by commas, or it can be an array of ids or nick names.
	 *
	 * The second parameter can be either a string accepted by Articles::list_selected(),
	 * or an instance of the Layout interface.
	 *
	 * @param mixed a list of ids or nick names
	 * @param mixed the layout to apply
	 * @return string to be inserted into the resulting page
	 */
	public static function &list_for_ids($ids, $layout='select') {
		global $context;

		// turn a string to an array
		if(!is_array($ids))
			$ids = preg_split('/[\s,]+/', (string)$ids);

		// check every id
		$queries = array();
		foreach($ids as $id) {

			// we need some id
			if(!$id)
				continue;

			// look by id or by nick name
			if(is_numeric($id))
				$queries[] = "SELECT * FROM ".SQL::table_name('articles')." WHERE (id = ".SQL::escape($id).")";
			else
				$queries[] = "SELECT * FROM ".SQL::table_name('articles')." WHERE (nick_name LIKE '".SQL::escape($id)."')";

		}

		// no valid id has been found
		if(!count($queries)) {
			$output = NULL;
			return $output;
		}

		// return pages in the order of argument received
		$query = "(".join(') UNION (', $queries).")";

		// query and layout
		$output =& Articles::list_selected(SQL::query($query), $layout);
		return $output;
	}

	/**
	 * list named articles
	 *
	 * This function lists all articles with the same nick name.
	 *
	 * This is used by the page locator to offer alternatives when several pages have the same nick names.
	 * It is also used to link a page to twins, these being, most of the time, translations.
	 *
	 * @param string the nick name
	 * @param int the id of the current page, which will not be listed
	 * @param mixed the layout, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	public static function &list_for_name($name, $exception=NULL, $layout='compact') {
		global $context;

		// limit the scope of this request
		$where = Articles::get_sql_where();

		// avoid exception, if any
		if($exception)
			$where .= " AND (articles.id != ".SQL::escape($exception).")";

		// articles by title -- no more than 100 pages with the same name
		$query = "SELECT articles.*"
			." FROM ".SQL::table_name('articles')." AS articles"
			." WHERE (articles.nick_name LIKE '".SQL::escape($name)."') AND ".$where
			." ORDER BY articles.title LIMIT 100";

		$output =& Articles::list_selected(SQL::query($query), $layout);
		return $output;
	}

	/**
	 * list articles of one user
	 *
	 * This function that are either:
	 * - owned by the user
	 * - or assigned to the user
	 *
	 * @param string passed to _get_order()
	 * @param int the id of the target surfer
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else the outcome of the layout
	 *
	 * @see users/print.php
	 * @see users/view.php
	 */
	public static function &list_for_user_by($order, $user_id, $offset=0, $count=10, $variant='full') {
		global $context;

		// sanity check
		if(!$user_id)
			return NULL;

		// limit the scope of the request
		$where = Articles::get_sql_where();

		// show only published articles if not looking at self record
		if((Surfer::get_id() != $user_id) && !Surfer::is_associate())
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// strip dead pages
		if((Surfer::get_id() != $user_id) && !Surfer::is_associate())
			$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$context['now']."'))";

		// order these pages
		$order = Articles::_get_order($order);

		// the list of watched sections
		$watched_sections = "(SELECT CONCAT('section:', sections.id) AS target"
			."	FROM (".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('sections')." AS sections)"
			." WHERE (members.member = 'user:".SQL::escape($user_id)."')"
			."	AND (members.anchor LIKE 'section:%')"
			."	AND (sections.id = SUBSTRING(members.anchor, 9))"
			." ORDER BY sections.edit_date DESC, sections.title LIMIT 0, 1000)";

		// the list of forwarding sections
		$forwarding_sections = "(SELECT CONCAT('section:', sections.id) AS target"
			." FROM ".$watched_sections." AS anchors"
			.", ".SQL::table_name('sections')." AS sections"
			." WHERE (sections.anchor = anchors.target) AND (sections.options LIKE '%forward_notifications%')"
			." ORDER BY sections.edit_date DESC, sections.title LIMIT 0, 1000)";

		// look for pages in watched sections
		$query = "(SELECT articles.* FROM (".$watched_sections." UNION ".$forwarding_sections.") AS anchors"
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE (articles.anchor = anchors.target)"
			."	AND ".$where.") UNION ";

		// look for watched pages
		$query .= "(SELECT articles.* FROM (SELECT DISTINCT CAST(SUBSTRING(members.anchor, 9) AS UNSIGNED) AS target FROM ".SQL::table_name('members')." AS members WHERE (members.member LIKE 'user:".SQL::escape($user_id)."') AND (members.anchor LIKE 'article:%')) AS ids"
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE (articles.id = ids.target)"
			."	AND ".$where.")";

		// include articles assigned to this surfer
		if($these_items = Surfer::assigned_articles($user_id))
			$query = "(SELECT articles.* FROM ".SQL::table_name('articles')." AS articles"
				." WHERE articles.id IN (".join(', ', $these_items).")"
				."	AND ".$where.")"
				." UNION ".$query;

		// include articles owned by this surfer
		$query = "(SELECT articles.* FROM ".SQL::table_name('articles')." AS articles"
			." WHERE articles.owner_id = ".$user_id
			."	AND ".$where.")"
			." UNION ".$query;

		// finalize the query
		$query .= " ORDER BY ".$order." LIMIT ".$offset.','.$count;

		// use existing listing facility
		$output =& Articles::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list selected articles
	 *
	 * If variant is provided as a string, the functions looks for a script featuring this name.
	 * E.g., for variant 'accordion', the file 'articles/layout_articles_as_accordion.php' is loaded.
	 * If no file matches then the default 'articles/layout_articles.php' script is loaded.
	 *
	 * Options can be provided to the selected layout by adding them after a space
	 * character. For example: 'simple no_anchor' when listing private conversations.
	 *
	 * @param resource result of database query
	 * @param mixed string e.g., 'decorated', or an instance of Layout_Interface
	 * @return NULL on error, else the outcome of the selected layout
	 *
	 * @see services/rss_codec.php
	 * @see skins/skin_skeleton.php
	 * @see index.php
	 */
	public static function &list_selected($result, $variant='compact') {
		global $context;

		// no result
		if(!$result) {
			$output = NULL;
			return $output;
		}

		// use the provided layout interface
		if(is_object($variant)) {
			$output = $variant->layout($result);
			return $output;
		}

		// instanciate the provided name
		$layout = Layouts::new_($variant, 'article',false, true);						

		// do the job
		$output = $layout->layout($result);
		return $output;
	}

	/**
	 * list all watchers of a page
	 *
	 * If the page is public or restricted to any member, the full list of persons watching this
	 * page, and its parent section. If the parent section has the option 'forward_notifications'
	 * the persons assigned to grand parent section are added.
	 *
	 * For example, if the root section A contains a section B, which contains page P, and if
	 * P is public, the function looks for persons assigned either to B or to P.
	 *
	 * If the parent section has option 'forward_notifications', then this fonction adds watchers
	 * of grand-parent section to the list.
	 *
	 * If the page is private, then the function looks for wtahcers of it, and for editors of the
	 * parent section that may also be watchers.
	 *
	 * For example, if the section A is public, and if it contains private page P, the function
	 * looks for watchers of P and for editors of A that are also watchers of A.
	 * This is because watchers of section A who are not editors are not entitled to watch P.
	 *
	 * @param array attributes of the watched page
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface adapted to list of users
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 */
	public static function list_watchers_by_name($item, $offset=0, $count=7, $variant='comma5') {
		global $context;

		// this page itself
		$anchors = array('article:'.$item['id']);

		// to list persons entitled to access this page
		$ancestors = array('article:'.$item['id']);

		// look at parents
		if($anchor = Anchors::get($item['anchor'])) {

			// notify watchers of parent section
			$anchors[] = $anchor->get_reference();

			// notify watchers of grand-parent section too
			if($anchor->has_option('forward_notifications', FALSE) && $anchor->get_parent())
				$anchors[] = $anchor->get_parent();

			// editors of parent and grand parent section are entitled to access the page too
			$ancestors[] = $anchor->get_reference();
			$handle = $anchor->get_parent();
			while($handle && ($parent = Anchors::get($handle))) {

				// notify watchers of grand-parent section too
				if($parent->has_option('forward_notifications', FALSE) && $parent->get_parent())
					$anchors[] = $parent->get_parent();

				$ancestors[] = $handle;
				$handle = $parent->get_parent();
			}

		}

		// authorized users only
		$restricted = NULL;
		if(($item['active'] == 'N') && ($editors =& Members::list_anchors_for_member($ancestors))) {
			foreach($editors as $editor)
				if(strpos($editor, 'user:') === 0)
					$restricted[] = substr($editor, strlen('user:'));
		}

		// list users watching one of these anchors
		return Members::list_watchers_by_name_for_anchor($anchors, $offset, $count, $variant, $restricted);
	}

	/**
	 * list all watchers of a page
	 *
	 * If the page is public or restricted to any member, the full list of persons watching this
	 * page, and its parent section. If the parent section has the option 'forward_notifications'
	 * the persons assigned to grand parent section are added.
	 *
	 * For example, if the root section A contains a section B, which contains page P, and if
	 * P is public, the function looks for persons assigned either to B or to P.
	 *
	 * If the parent section has option 'forward_notifications', then this fonction adds watchers
	 * of grand-parent section to the list.
	 *
	 * If the page is private, then the function looks for wtahcers of it, and for editors of the
	 * parent section that may also be watchers.
	 *
	 * For example, if the section A is public, and if it contains private page P, the function
	 * looks for watchers of P and for editors of A that are also watchers of A.
	 * This is because watchers of section A who are not editors are not entitled to watch P.
	 *
	 * @param array attributes of the watched page
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface adapted to list of users
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 */
	public static function list_watchers_by_posts($item, $offset=0, $count=7, $variant='comma5') {
		global $context;

		// this page itself
		$anchors = array('article:'.$item['id']);

		// to list persons entitled to access this page
		$ancestors = array('article:'.$item['id']);

		// look at parents
		if($anchor = Anchors::get($item['anchor'])) {

			// notify watchers of parent section
			$anchors[] = $anchor->get_reference();

			// notify watchers of grand-parent section too
			if($anchor->has_option('forward_notifications', FALSE) && $anchor->get_parent())
				$anchors[] = $anchor->get_parent();

			// editors of parent and grand parent section are entitled to access the page too
			$ancestors[] = $anchor->get_reference();
			$handle = $anchor->get_parent();
			while($handle && ($parent = Anchors::get($handle))) {

				// notify watchers of grand-parent section too
				if($parent->has_option('forward_notifications', FALSE) && $parent->get_parent())
					$anchors[] = $parent->get_parent();

				$ancestors[] = $handle;
				$handle = $parent->get_parent();
			}

		}

		// authorized users only
		$restricted = NULL;
		if(($item['active'] == 'N') && ($editors =& Members::list_anchors_for_member($ancestors))) {
			foreach($editors as $editor)
				if(strpos($editor, 'user:') === 0)
					$restricted[] = substr($editor, strlen('user:'));
		}

		// list users watching one of these anchors
		return Members::list_watchers_by_posts_for_anchor($anchors, $offset, $count, $variant, $restricted);
	}

	/**
	 * lock/unlock an article
	 *
	 * @param int the id of the article to update
	 * @param string the previous locking state
	 * @return TRUE on success toggle, FALSE otherwise
	 */
	public static function lock($id, $status='Y') {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return FALSE;

		// toggle status
		if($status == 'Y')
			$status = 'N';
		else
			$status = 'Y';

		// do the job
		$query = "UPDATE ".SQL::table_name('articles')." SET locked='".SQL::escape($status)."' WHERE id = ".SQL::escape($id);
		if(SQL::query($query) === FALSE)
			return FALSE;

		return TRUE;
	}

	/**
	 * get the id of one article knowing its nick name
	 *
	 * @param string the nick name looked for
	 * @return string either 'article:&lt;id&gt;', or NULL
	 */
	public static function lookup($nick_name) {
		global $context;

		// the page already exists
		if($item = Articles::get($nick_name))
			return 'article:'.$item['id'];

		// attempt to create a default item
		Articles::post_default($nick_name);

		// do the check again
		if($item = Articles::get($nick_name))
			return 'article:'.$item['id'];

		// tough luck
		return NULL;
	}

	/**
	 * post a new article
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param array an array of fields
	 * @return the id of the new article, or FALSE on error
	 *
	 * @see articles/edit.php
	**/
	public static function post(&$fields) {
		global $context;

		// title cannot be empty
		if(!isset($fields['title']) || !$fields['title']) {
			Logger::error(i18n::s('No title has been provided.'));
			return FALSE;
		}

		// sanity filter
		$fields['title'] = strip_tags($fields['title'], '<br>');

		// anchor cannot be empty
		if(!isset($fields['anchor']) || !$fields['anchor'] || (!$anchor = Anchors::get($fields['anchor']))) {
			Logger::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// protect from hackers
		if(isset($fields['icon_url']))
			$fields['icon_url'] = encode_link($fields['icon_url']);
		if(isset($fields['thumbnail_url']))
			$fields['thumbnail_url'] = encode_link($fields['thumbnail_url']);

		// set default values for this editor
		Surfer::check_default_editor($fields);

		// reinforce date formats
		if(!isset($fields['create_date']) || ($fields['create_date'] <= NULL_DATE))
			$fields['create_date'] = $fields['edit_date'];
		if(!isset($fields['publish_date']) || ($fields['publish_date'] <= NULL_DATE))
			$fields['publish_date'] = NULL_DATE;

		// set conservative default values
		if(!isset($fields['active_set']))
			$fields['active_set'] = 'Y';
		if(isset($fields['edit_action']) && $fields['edit_action'])
			$fields['edit_action'] = preg_replace('/import$/i', 'update', $fields['edit_action']);
		if(!isset($fields['rank']))
			$fields['rank'] = 10000;
		if(!isset($fields['nick_name']))
			$fields['nick_name'] = '';

		// set canvas default value
		if(!isset($fields['canvas']) || !$fields['canvas'])
			$fields['canvas'] = 'standard';

		// clean provided tags
		if(isset($fields['tags']))
			$fields['tags'] = trim($fields['tags'], " \t.:,!?");

		// cascade anchor access rights
		$fields['active'] = $anchor->ceil_rights($fields['active_set']);

		// fields to update
		$query = array();

		// on import
		if(isset($fields['id']))
			$query[] = "id=".SQL::escape($fields['id']);

		// fields that are visible only to associates -- see articles/edit.php
		if(Surfer::is_associate()) {
			$query[] = "prefix='".SQL::escape(isset($fields['prefix']) ? $fields['prefix'] : '')."'";
			$query[] = "suffix='".SQL::escape(isset($fields['suffix']) ? $fields['suffix'] : '')."'";
			$query[] = "canvas='".SQL::escape(isset($fields['canvas']) ? $fields['canvas'] : '')."'";
		}

		$query[] = "nick_name='".SQL::escape(isset($fields['nick_name']) ? $fields['nick_name'] : '')."'";
		$query[] = "behaviors='".SQL::escape(isset($fields['behaviors']) ? $fields['behaviors'] : '')."'";
		$query[] = "extra='".SQL::escape(isset($fields['extra']) ? $fields['extra'] : '')."'";
		$query[] = "icon_url='".SQL::escape(isset($fields['icon_url']) ? $fields['icon_url'] : '')."'";
		$query[] = "thumbnail_url='".SQL::escape(isset($fields['thumbnail_url']) ? $fields['thumbnail_url'] : '')."'";
		$query[] = "rank='".SQL::escape($fields['rank'])."'";
		$query[] = "meta='".SQL::escape(isset($fields['meta']) ? $fields['meta'] : '')."'";
		$query[] = "options='".SQL::escape(isset($fields['options']) ? $fields['options'] : '')."'";
		$query[] = "trailer='".SQL::escape(isset($fields['trailer']) ? $fields['trailer'] : '')."'";

		// controlled fields
		$query[] = "active='".SQL::escape($fields['active'])."'";
		$query[] = "active_set='".SQL::escape($fields['active_set'])."'";

		// fields visible to authorized member
		$query[] = "anchor='".SQL::escape($fields['anchor'])."'";
		$query[] = "anchor_type=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', 1)";
		$query[] = "anchor_id=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', -1)";
		$query[] = "title='".SQL::escape($fields['title'])."'";
		$query[] = "source='".SQL::escape(isset($fields['source']) ? $fields['source'] : '')."'";
		$query[] = "introduction='".SQL::escape(isset($fields['introduction']) ? $fields['introduction'] : '')."'";
		$query[] = "description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."'";
		$query[] = "language='".SQL::escape(isset($fields['language']) ? $fields['language'] : '')."'";
		$query[] = "locked='".SQL::escape(isset($fields['locked']) ? $fields['locked'] : 'N')."'";
		$query[] = "overlay='".SQL::escape(isset($fields['overlay']) ? $fields['overlay'] : '')."'";
		$query[] = "overlay_id='".SQL::escape(isset($fields['overlay_id']) ? $fields['overlay_id'] : '')."'";
		$query[] = "owner_id=".SQL::escape(isset($fields['create_id']) ? $fields['create_id'] : $fields['edit_id']);
		$query[] = "tags='".SQL::escape(isset($fields['tags']) ? $fields['tags'] : '')."'";
		$query[] = "hits=0";
		$query[] = "create_name='".SQL::escape(isset($fields['create_name']) ? $fields['create_name'] : $fields['edit_name'])."'";
		$query[] = "create_id=".SQL::escape(isset($fields['create_id']) ? $fields['create_id'] : (isset($fields['edit_id']) ? $fields['edit_id'] : '0'));
		$query[] = "create_address='".SQL::escape(isset($fields['create_address']) ? $fields['create_address'] : $fields['edit_address'])."'";
		$query[] = "create_date='".SQL::escape($fields['create_date'])."'";
		$query[] = "edit_name='".SQL::escape($fields['edit_name'])."'";
		$query[] = "edit_id=".SQL::escape(isset($fields['edit_id']) ? $fields['edit_id'] : '0');
		$query[] = "edit_address='".SQL::escape($fields['edit_address'])."'";
		$query[] = "edit_action='".SQL::escape(isset($fields['edit_action']) ? $fields['edit_action'] : 'article:submit')."'";
		$query[] = "edit_date='".SQL::escape($fields['edit_date'])."'";

		// reset user assignment, if any
		$query[] = "assign_name=''";
		$query[] = "assign_id=0";
		$query[] = "assign_address=''";
		$query[] = "assign_date='".SQL::escape(NULL_DATE)."'";

		// set or change the publication date
		if(isset($fields['publish_date']) && ($fields['publish_date'] > NULL_DATE)) {
			$query[] = "publish_name='".SQL::escape(isset($fields['publish_name']) ? $fields['publish_name'] : $fields['edit_name'])."'";
			if(isset($fields['publish_id']) || isset($fields['edit_id']))
				$query[] = "publish_id=".SQL::escape(isset($fields['publish_id']) ? $fields['publish_id'] : $fields['edit_id']);
			$query[] = "publish_address='".SQL::escape(isset($fields['publish_address']) ? $fields['publish_address'] : $fields['edit_address'])."'";
			$query[] = "publish_date='".SQL::escape($fields['publish_date'])."'";
		}

		// always create a random handle for this article
		if(!isset($fields['handle']) || (strlen($fields['handle']) < 32))
			$fields['handle'] = md5(mt_rand());
		$query[] = "handle='".SQL::escape($fields['handle'])."'";

		// allow anonymous surfer to access this page during his session
		if(!Surfer::get_id())
			Surfer::add_handle($fields['handle']);		

		// insert a new record
		$query = "INSERT INTO ".SQL::table_name('articles')." SET ".implode(', ', $query);

		// actual insert
		if(SQL::query($query) === FALSE)
			return FALSE;

		// remember the id of the new item
		$fields['id'] = SQL::get_last_id($context['connection']);

		// assign the page to related categories
		Categories::remember('article:'.$fields['id'], isset($fields['publish_date']) ? $fields['publish_date'] : NULL_DATE, isset($fields['tags']) ? $fields['tags'] : '');

		// turn author to page editor and update author's watch list
		if(isset($fields['edit_id']) && $fields['edit_id']) {
			Members::assign('user:'.$fields['edit_id'], 'article:'.$fields['id']);
			Members::assign('article:'.$fields['id'], 'user:'.$fields['edit_id']);
		}

		// clear the cache
		Articles::clear($fields);

		// return the id of the new item
		return $fields['id'];
	}

	/**
	 * create a default named page
	 *
	 * @param string the nick name of the item to create
	 * @return string text to be displayed in the resulting page
	 */
	public static function post_default($nick_name) {
		global $context;

		// the page already exists
		if($item = Articles::get($nick_name))
			return '';

		// use the provided model for this item
		if(is_readable($context['path_to_root'].'articles/defaults/'.$nick_name.'.php')) {
			include_once $context['path_to_root'].'articles/defaults/'.$nick_name.'.php';

			// do the job
			if(is_callable(array($nick_name, 'initialize')))
				return call_user_func(array($nick_name, 'initialize'));
		}

		// tough luck
		return '';
	}

	/**
	 * limit the number of articles for one anchor
	 *
	 * This function deletes oldest pages going beyond the given threshold.
	 *
	 * @param int the maximum number of pages to keep in the database
	 * @return void
	 */
	public static function purge_for_anchor($anchor, $limit=1000) {
		global $context;

		// lists oldest entries beyond the limit
		$query = "SELECT articles.* FROM ".SQL::table_name('articles')." AS articles "
			." WHERE (articles.anchor LIKE '".SQL::escape($anchor)."')"
			." ORDER BY articles.edit_date DESC LIMIT ".$limit.', 10';

		// no result
		if(!$result = SQL::query($query))
			return;

		// empty list
		if(!SQL::count($result))
			return;

		// delete silently all matching items
		while($item = SQL::fetch($result))
			Articles::delete($item['id']);

		// end of processing
		SQL::free($result);

	}

	/**
	 * put an updated article in the database
	 *
	 * @param array an array of fields
	 * @return TRUE on success, or FALSE on error
	 *
	 * @see articles/edit.php
	 * @see services/blog.php
	**/
	public static function put(&$fields) {
		global $context;

		// id cannot be empty
		if(!isset($fields['id']) || !is_numeric($fields['id'])) {
			Logger::error(i18n::s('No item has the provided id.'));
			return FALSE;
		}

		// title cannot be empty
		if(!isset($fields['title']) || !$fields['title']) {
			Logger::error(i18n::s('No title has been provided.'));
			return FALSE;
		}

		// sanity filter
		$fields['title'] = strip_tags($fields['title'], '<br>');

		// anchor cannot be empty
		if(!isset($fields['anchor']) || !$fields['anchor'] || (!$anchor = Anchors::get($fields['anchor']))) {
			Logger::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// protect from hackers
		if(isset($fields['icon_url']))
			$fields['icon_url'] = preg_replace('/[^\w\/\.,:%&\?=-]+/', '_', $fields['icon_url']);
		if(isset($fields['thumbnail_url']))
			$fields['thumbnail_url'] = preg_replace('/[^\w\/\.,:%&\?=-]+/', '_', $fields['thumbnail_url']);

		// set default values for this editor
		Surfer::check_default_editor($fields);

		// reinforce date formats
		if(!isset($fields['publish_date']) || ($fields['publish_date'] <= NULL_DATE))
			$fields['publish_date'] = NULL_DATE;

		// set conservative default values
		if(!isset($fields['active_set']))
			$fields['active_set'] = 'Y';
		if(!isset($fields['rank']))
			$fields['rank'] = 10000;

		// set canvas default value
		if(!isset($fields['canvas']) || !$fields['canvas'])
			$fields['canvas'] = 'standard';

		// clean provided tags
		if(isset($fields['tags']))
			$fields['tags'] = trim($fields['tags'], " \t.:,!?");

		// cascade anchor access rights
		$fields['active'] = $anchor->ceil_rights($fields['active_set']);

		// columns updated
		$query = array();

		// fields that are visible only to associates -- see articles/edit.php
		if(Surfer::is_associate()) {
			$query[] = "prefix='".SQL::escape(isset($fields['prefix']) ? $fields['prefix'] : '')."'";
			$query[] = "suffix='".SQL::escape(isset($fields['suffix']) ? $fields['suffix'] : '')."'";
			$query[] = "canvas='".SQL::escape(isset($fields['canvas']) ? $fields['canvas'] : '')."'";
		}

		// fields that are visible only to associates and to editors -- see articles/edit.php
		if(Surfer::is_empowered() && Surfer::is_member()) {
			$query[] = "nick_name='".SQL::escape(isset($fields['nick_name']) ? $fields['nick_name'] : '')."'";
			$query[] = "behaviors='".SQL::escape(isset($fields['behaviors']) ? $fields['behaviors'] : '')."'";
			$query[] = "extra='".SQL::escape(isset($fields['extra']) ? $fields['extra'] : '')."'";
			$query[] = "icon_url='".SQL::escape(isset($fields['icon_url']) ? $fields['icon_url'] : '')."'";
			$query[] = "thumbnail_url='".SQL::escape(isset($fields['thumbnail_url']) ? $fields['thumbnail_url'] : '')."'";
			$query[] = "rank='".SQL::escape($fields['rank'])."'";
			$query[] = "locked='".SQL::escape(isset($fields['locked']) ? $fields['locked'] : 'N')."'";
			$query[] = "meta='".SQL::escape(isset($fields['meta']) ? $fields['meta'] : '')."'";
			$query[] = "options='".SQL::escape(isset($fields['options']) ? $fields['options'] : '')."'";
			$query[] = "trailer='".SQL::escape(isset($fields['trailer']) ? $fields['trailer'] : '')."'";
			$query[] = "active='".SQL::escape($fields['active'])."'";
			$query[] = "active_set='".SQL::escape($fields['active_set'])."'";
		}

		// fields visible to authorized member
		$query[] = "anchor='".SQL::escape($fields['anchor'])."'";
		$query[] = "anchor_type=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', 1)";
		$query[] = "anchor_id=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', -1)";
		$query[] = "title='".SQL::escape($fields['title'])."'";
		$query[] = "source='".SQL::escape(isset($fields['source']) ? $fields['source'] : '')."'";
		$query[] = "introduction='".SQL::escape(isset($fields['introduction']) ? $fields['introduction'] : '')."'";
		$query[] = "description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."'";
		$query[] = "language='".SQL::escape(isset($fields['language']) ? $fields['language'] : '')."'";
		$query[] = "overlay='".SQL::escape(isset($fields['overlay']) ? $fields['overlay'] : '')."'";
		$query[] = "overlay_id='".SQL::escape(isset($fields['overlay_id']) ? $fields['overlay_id'] : '')."'";
		$query[] = "tags='".SQL::escape(isset($fields['tags']) ? $fields['tags'] : '')."'";

		// set or change the publication date
		if(isset($fields['publish_date']) && ($fields['publish_date'] > NULL_DATE)) {
			$query[] = "publish_name='".SQL::escape(isset($fields['publish_name']) ? $fields['publish_name'] : $fields['edit_name'])."'";
			if(isset($fields['publish_id']) || isset($fields['edit_id']))
				$query[] = "publish_id=".SQL::escape(isset($fields['publish_id']) ? $fields['publish_id'] : $fields['edit_id']);
			$query[] = "publish_address='".SQL::escape(isset($fields['publish_address']) ? $fields['publish_address'] : $fields['edit_address'])."'";
			$query[] = "publish_date='".SQL::escape($fields['publish_date'])."'";
		}

		// maybe a silent update
		if(!isset($fields['silent']) || ($fields['silent'] != 'Y') || !Surfer::is_empowered()) {
			$query[] = "edit_name='".SQL::escape($fields['edit_name'])."'";
			$query[] = "edit_id=".SQL::escape(isset($fields['edit_id']) ? $fields['edit_id'] : '0');
			$query[] = "edit_address='".SQL::escape($fields['edit_address'])."'";
			$query[] = "edit_action='article:update'";
			$query[] = "edit_date='".SQL::escape($fields['edit_date'])."'";
		}

		// reset user assignment, if any
		$query[] = "assign_name=''";
		$query[] = "assign_id=0";
		$query[] = "assign_address=''";
		$query[] = "assign_date='".SQL::escape(NULL_DATE)."'";

		// update an existing record
		$query = "UPDATE ".SQL::table_name('articles')." SET ".implode(', ', $query)." WHERE id = ".SQL::escape($fields['id']);
		if(SQL::query($query) === FALSE)
			return FALSE;

		// list the article in categories
		Categories::remember('article:'.$fields['id'], isset($fields['publish_date']) ? $fields['publish_date'] : NULL_DATE, isset($fields['tags']) ? $fields['tags'] : '');

		// add this page to surfer watch list
		if(Surfer::get_id())
			Members::assign('article:'.$fields['id'], 'user:'.Surfer::get_id());

		// clear the cache
		Articles::clear($fields);

		// end of job
		return TRUE;
	}

	/**
	 * change only some attributes
	 *
	 * @param array an array of fields
	 * @return TRUE on success, or FALSE on error
	**/
	public static function put_attributes(&$fields) {
		global $context;

		// id cannot be empty
		if(!isset($fields['id']) || !is_numeric($fields['id'])) {
			Logger::error(i18n::s('No item has the provided id.'));
			return FALSE;
		}

		// set default values for this editor
		Surfer::check_default_editor($fields);

		// quey components
		$query = array();

		// change access rights
		if(isset($fields['active_set'])) {

			// anchor cannot be empty
			if(!isset($fields['anchor']) || !$fields['anchor'] || (!$anchor = Anchors::get($fields['anchor']))) {
				Logger::error(i18n::s('No anchor has been found.'));
				return FALSE;
			}

			// determine the actual right
			$fields['active'] = $anchor->ceil_rights($fields['active_set']);

			// remember these in this record
			$query[] = "active='".SQL::escape($fields['active'])."'";
			$query[] = "active_set='".SQL::escape($fields['active_set'])."'";

			// cascade anchor access rights
			Anchors::cascade('article:'.$fields['id'], $fields['active']);

		}

		// anchor this page to another place
		if(isset($fields['anchor'])) {
			$query[] = "anchor='".SQL::escape($fields['anchor'])."'";
			$query[] = "anchor_type=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', 1)";
			$query[] = "anchor_id=SUBSTRING_INDEX('".SQL::escape($fields['anchor'])."', ':', -1)";
		}

		// other fields that can be modified individually
		if(isset($fields['behaviors']))
			$query[] = "behaviors='".SQL::escape($fields['behaviors'])."'";
		if(isset($fields['extra']))
			$query[] = "extra='".SQL::escape($fields['extra'])."'";
		if(isset($fields['description']))
			$query[] = "description='".SQL::escape($fields['description'])."'";
		if(isset($fields['handle']) && $fields['handle'])
			$query[] = "handle='".SQL::escape($fields['handle'])."'";
		if(isset($fields['icon_url']))
			$query[] = "icon_url='".SQL::escape(preg_replace('/[^\w\/\.,:%&\?=-]+/', '_', $fields['icon_url']))."'";
		if(isset($fields['introduction']))
			$query[] = "introduction='".SQL::escape($fields['introduction'])."'";
		if(isset($fields['language']))
			$query[] = "language='".SQL::escape($fields['language'])."'";
		if(isset($fields['locked']))
			$query[] = "locked='".SQL::escape($fields['locked'])."'";
		if(isset($fields['meta']))
			$query[] = "meta='".SQL::escape($fields['meta'])."'";
		if(isset($fields['nick_name']))
			$query[] = "nick_name='".SQL::escape($fields['nick_name'])."'";
		if(isset($fields['options']))
			$query[] = "options='".SQL::escape($fields['options'])."'";
		if(isset($fields['overlay']))
			$query[] = "overlay='".SQL::escape($fields['overlay'])."'";
		if(isset($fields['overlay_id']))
			$query[] = "overlay_id='".SQL::escape($fields['overlay_id'])."'";
		if(isset($fields['owner_id']))
			$query[] = "owner_id=".SQL::escape($fields['owner_id']);
		if(isset($fields['publish_date'])) {
			$query[] = "publish_name='".SQL::escape(isset($fields['publish_name']) ? $fields['publish_name'] : $fields['edit_name'])."'";
			$query[] = "publish_id=".SQL::escape(isset($fields['publish_id']) ? $fields['publish_id'] : $fields['edit_id']);
			$query[] = "publish_address='".SQL::escape(isset($fields['publish_address']) ? $fields['publish_address'] : $fields['edit_address'])."'";
			$query[] = "publish_date='".SQL::escape($fields['publish_date'])."'";
		}
		if(isset($fields['prefix']))
			$query[] = "prefix='".SQL::escape($fields['prefix'])."'";
		if(isset($fields['rank']))
			$query[] = "rank='".SQL::escape($fields['rank'])."'";
		if(isset($fields['source']))
			$query[] = "source='".SQL::escape($fields['source'])."'";
		if(isset($fields['suffix']))
			$query[] = "suffix='".SQL::escape($fields['suffix'])."'";
		if(isset($fields['thumbnail_url']))
			$query[] = "thumbnail_url='".SQL::escape(preg_replace('/[^\w\/\.,:%&\?=-]+/', '_', $fields['thumbnail_url']))."'";
		if(isset($fields['tags']))
			$query[] = "tags='".SQL::escape($fields['tags'])."'";
		if(isset($fields['title'])) {
			$fields['title'] = strip_tags($fields['title'], '<br>');
			$query[] = "title='".SQL::escape($fields['title'])."'";
		}
		if(isset($fields['trailer']))
			$query[] = "trailer='".SQL::escape($fields['trailer'])."'";

		if(isset($fields['rating_sum']))
			$query[] = "rating_sum='".SQL::escape($fields['rating_sum'])."'";

		// nothing to update
		if(!count($query))
			return TRUE;

		// maybe a silent update
		if(!isset($fields['silent']) || ($fields['silent'] != 'Y')) {
			$query[] = "edit_name='".SQL::escape($fields['edit_name'])."'";
			$query[] = "edit_id=".SQL::escape($fields['edit_id']);
			$query[] = "edit_address='".SQL::escape($fields['edit_address'])."'";
			$query[] = "edit_action='article:update'";
			$query[] = "edit_date='".SQL::escape($fields['edit_date'])."'";
		}

		// actual update query
		$query = "UPDATE ".SQL::table_name('articles')
			." SET ".implode(', ', $query)
			." WHERE id = ".SQL::escape($fields['id']);

		if(!SQL::query($query))
			return FALSE;

		// clear the cache
		Articles::clear($fields);

		// end of job
		return TRUE;
	}

	/**
	 * rate a page
	 *
	 * Errors are not reported, if any
	 *
	 * @param int the id of the article to rate
	 * @param int the rate
	 */
	public static function rate($id, $rating) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return i18n::s('No item has the provided id.');

		// update rating in database
		$query = "UPDATE ".SQL::table_name('articles')
			." SET rating_sum = rating_sum + ".SQL::escape($rating).", rating_count = rating_count + 1"
			." WHERE id = ".SQL::escape($id);
		SQL::query($query);

	}

	/**
	 * search for some keywords in all articles
	 *
	 * @see search.php
	 * @see services/search.php
	 * @see categories/set_keyword.php
	 *
	 * @param string the search string
	 * @param float maximum score to look at
	 * @param int the number of items to display
	 * @param mixed the layout, if any
	 * @return NULL on error, else an ordered array of array($score, $summary)
	 */
	public static function &search($pattern, $offset=1.0, $count=50, $layout='search') {
		global $context;

		$output =& Articles::search_in_section(NULL, $pattern, $offset, $count, $layout);
		return $output;
	}

	/**
	 * search for some keywords articles anchored to one precise section
	 *
	 * This function also searches in sub-sections, with up to three levels of depth.
	 *
	 * @see search.php
	 *
	 * Modification dates are taken into account to prefer freshest information.
	 *
	 * @link http://www.artfulcode.net/articles/full-text-searching-mysql/
	 *
	 * @param int the id of the section to look in
	 * @param string the search string
	 * @param float maximum score to look at
	 * @param int the number of items to display
	 * @param mixed the layout, if any
	 * @return NULL on error, else an ordered array of array($score, $summary)
	 */
	public static function &search_in_section($section_id, $pattern, $offset=1.0, $count=10, $layout='search') {
		global $context;

		// sanity check
		if(!$pattern = trim($pattern)) {
			$output = NULL;
			return $output;
		}

		// restrict the query to addressable content
		$where = Articles::get_sql_where();

		// search is restricted to one section
		$sections_where = '';
		if($section_id) {

			// look for children
			$anchors = Sections::get_branch_at_anchor('section:'.$section_id);

			// the full set of sections searched
			$where .= " AND (anchor IN ('".join("', '", $anchors)."'))";

		}

		// anonymous surfers and subscribers will see only published articles
		if(!Surfer::is_member())
			$where .= " AND NOT ((publish_date is NULL) OR (publish_date <= '0000-00-00'))"
				." AND (publish_date < '".$context['now']."')";

		// only consider live articles
		$where .= " AND ((expiry_date is NULL) "
				."OR (expiry_date <= '".NULL_DATE."') OR (expiry_date > '".$context['now']."'))";

		// how to compute the score for articles
		$score = "(MATCH(title, source, introduction, overlay, description)"
			." AGAINST('".SQL::escape($pattern)."' IN BOOLEAN MODE)"
			."/SQRT(GREATEST(1.1, DATEDIFF(NOW(), edit_date))))";

		// the list of articles
		$query = "SELECT *, ".$score." AS score FROM ".SQL::table_name('articles')." AS articles"
			." WHERE (".$where.") AND (".$score." < ".$offset.") AND (".$score." > 0)"
			." ORDER BY score DESC"
			." LIMIT ".$count;

		$output =& Articles::list_selected(SQL::query($query), $layout);
		return $output;
	}

	/**
	 * create tables for articles
	 */
	public static function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['active']		= "ENUM('Y','R','N') DEFAULT 'Y' NOT NULL";
		$fields['active_set']	= "ENUM('Y','R','N') DEFAULT 'Y' NOT NULL";
		$fields['anchor']		= "VARCHAR(64) DEFAULT 'section:1' NOT NULL";
		$fields['anchor_type']	= "VARCHAR(64) DEFAULT 'section' NOT NULL";
		$fields['anchor_id']	= "MEDIUMINT UNSIGNED NOT NULL";
		$fields['assign_address']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['assign_date']	= "DATETIME";
		$fields['assign_id']	= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['assign_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['behaviors']	= "TEXT NOT NULL";
		$fields['canvas']		= "VARCHAR(255) DEFAULT 'standard' NOT NULL";
		$fields['create_address']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['create_date']	= "DATETIME";
		$fields['create_id']	= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['create_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['description']	= "MEDIUMTEXT NOT NULL";
		$fields['edit_action']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";
		$fields['edit_id']		= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['expiry_date']	= "DATETIME";
		$fields['extra']		= "TEXT NOT NULL";
		$fields['handle']		= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['hits'] 		= "INT UNSIGNED DEFAULT 0 NOT NULL";
		$fields['icon_url'] 	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['introduction'] = "TEXT NOT NULL";
		$fields['language'] 	= "VARCHAR(64) DEFAULT '' NOT NULL";
		$fields['locked']		= "ENUM('Y', 'N') DEFAULT 'N' NOT NULL";
		$fields['meta'] 		= "TEXT NOT NULL";
		$fields['nick_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['options']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['overlay']		= "TEXT NOT NULL";
		$fields['overlay_id']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['owner_id']		= "MEDIUMINT UNSIGNED DEFAULT 0 NOT NULL";
		$fields['prefix']		= "TEXT NOT NULL";
		$fields['publish_address']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['publish_date'] = "DATETIME";
		$fields['publish_id']	= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['publish_name'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['rank'] 		= "INT UNSIGNED DEFAULT 10000 NOT NULL";
		$fields['rating_count'] = "INT UNSIGNED DEFAULT 0 NOT NULL";
		$fields['rating_sum']	= "INT UNSIGNED DEFAULT 0 NOT NULL";
		$fields['review_date']	= "DATETIME";
		$fields['source']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['suffix']		= "TEXT NOT NULL";
		$fields['tags'] 		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['thumbnail_url']= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['title']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['trailer']		= "TEXT NOT NULL";

		$indexes = array();
		$indexes['PRIMARY KEY'] 		= "(id)";
		$indexes['INDEX active']		= "(active)";
		$indexes['INDEX anchor']		= "(anchor)";
		$indexes['INDEX anchor_id'] 	= "(anchor_id)";
		$indexes['INDEX anchor_type']	= "(anchor_type)";
		$indexes['INDEX create_date']	= "(create_date)";
		$indexes['INDEX create_id'] 	= "(create_id)";
		$indexes['INDEX edit_date'] 	= "(edit_date)";
		$indexes['INDEX edit_id']		= "(edit_id)";
		$indexes['INDEX expiry_date']	= "(expiry_date)";
		$indexes['INDEX handle']		= "(handle)";
		$indexes['INDEX hits']			= "(hits)";
		$indexes['INDEX language']		= "(language)";
		$indexes['INDEX locked']		= "(locked)";
		$indexes['INDEX nick_name'] 	= "(nick_name)";
		$indexes['INDEX overlay_id']	= "(overlay_id)";
		$indexes['INDEX publish_date']	= "(publish_date)";
		$indexes['INDEX publish_id']	= "(publish_id)";
		$indexes['INDEX rank']			= "(rank)";
		$indexes['INDEX rating_sum']	= "(rating_sum)";
		$indexes['INDEX review_date']	= "(review_date)";
		$indexes['INDEX title'] 		= "(title(255))";
		$indexes['FULLTEXT INDEX']		= "full_text(title, source, introduction, overlay, description)";

		return SQL::setup_table('articles', $fields, $indexes);

	}

	/**
	 * stamp an article
	 *
	 * This function is used to change various dates for one article.
	 *
	 * [*] If a publication date is provided, it is saved along the article.
	 * An optional expiry date will be saved as well.
	 *
	 * [*] If only an expiry date is provided, it is saved along the article.
	 *
	 * [*] If no date is provided, the review field is updated to the current date and time.
	 *
	 * Dates are supposed to be in UTC time zone.
	 *
	 * The name of the surfer is registered as the official publisher.
	 * As an alternative, publisher attributes ('name', 'id' and 'address') can be provided
	 * in parameters.
	 *
	 * @param int the id of the item to publish
	 * @param string the target publication date, if any
	 * @param string the target expiration date, if any
	 * @param array attributes of the publisher, if any
	 * @return string either a null string, or some text describing an error to be inserted into the html response
	 *
	 * @see articles/publish.php
	 * @see sections/manage.php
	**/
	public static function stamp($id, $publication=NULL, $expiry=NULL, $publisher=NULL) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return i18n::s('No item has the provided id.');

		// server offset
		$server_offset = 0;
		if(isset($context['gmt_offset']))
			$server_offset = intval($context['gmt_offset']);

		// surfer offset
		$surfer_offset = Surfer::get_gmt_offset();

		// no publication time is provided
		if(!isset($publication) || !$publication)
			$publication_stamp = 0;

		// YYMMDD-HH:MM:SS GMT -- this one is natively GMT
		elseif(preg_match('/GMT$/', $publication) && (strlen($publication) == 19)) {

			// YYMMDD-HH:MM:SS GMT -> HH, MM, SS, MM, DD, YY
			$publication_stamp = gmmktime(intval(substr($publication, 7, 2)), intval(substr($publication, 10, 2)), intval(substr($publication, 13, 2)),
				intval(substr($publication, 2, 2)), intval(substr($publication, 4, 2)), intval(substr($publication, 0, 2)));

		// time()-like stamp
		} elseif(intval($publication) > 1000000000) {

			// adjust to UTC time zone
			$publication_stamp = intval($publication) + ($context['gmt_offset'] * 3600);

		// YYYY-MM-DD HH:MM:SS, or a string that can be readed
		} elseif(($publication_stamp = SQL::strtotime($publication)) != -1)
			;

		// invalid date
		else
			return sprintf(i18n::s('"%s" is not a valid date'), $publication);

		// no expiry date
		if(!isset($expiry) || !$expiry)
			$expiry_stamp = 0;

		// YYMMDD-HH:MM:SS GMT -- this one is natively GMT
		elseif(preg_match('/GMT$/', $expiry) && (strlen($expiry) == 19)) {

			// YYMMDD-HH:MM:SS GMT -> HH, MM, SS, MM, DD, YY
			$expiry_stamp = gmmktime(substr($expiry, 7, 2), substr($expiry, 10, 2), substr($expiry, 13, 2),
				substr($expiry, 2, 2), substr($expiry, 4, 2), substr($expiry, 0, 2));

		// time()-like stamp
		} elseif(intval($expiry) > 1000000000) {

			// adjust to server time zone
			$expiry_stamp = intval($expiry) + ($context['gmt_offset'] * 3600);

		// YYYY-MM-DD HH:MM:SS, or a string that can be readed
		} elseif(($expiry_stamp = SQL::strtotime($expiry)) != -1)
			;

		// invalid date
		else
			return sprintf(i18n::s('"%s" is not a valid date'), $expiry);

		// review date
		$review_stamp = 0;
		if(!$publication_stamp && !$expiry_stamp)
			$review_stamp = time();

		// shape the query
		$query = array();

		if($publication_stamp > 0)
			$query[] = "publish_name='".SQL::escape(isset($publisher['name']) ? $publisher['name'] : Surfer::get_name())."',"
				."publish_id=".SQL::escape(isset($publisher['id']) ? $publisher['id'] : Surfer::get_id()).","
				."publish_address='".SQL::escape(isset($publisher['address']) ? $publisher['address'] : Surfer::get_email_address())."',"
				."publish_date='".gmstrftime('%Y-%m-%d %H:%M:%S', $publication_stamp)."',"
				."edit_name='".SQL::escape(isset($publisher['name']) ? $publisher['name'] : Surfer::get_name())."',"
				."edit_id=".SQL::escape(isset($publisher['id']) ? $publisher['id'] : Surfer::get_id()).","
				."edit_address='".SQL::escape(isset($publisher['address']) ? $publisher['address'] : Surfer::get_email_address())."',"
				."edit_action='article:publish',"
				."edit_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."'";
		if($expiry_stamp > 0)
			$query[] = "expiry_date='".gmstrftime('%Y-%m-%d %H:%M:%S', $expiry_stamp)."'";
		if($review_stamp > 0)
			$query[] = "review_date='".gmstrftime('%Y-%m-%d %H:%M:%S', $review_stamp)."'";

		// update an existing record
		$query = "UPDATE ".SQL::table_name('articles')." SET ".implode(',', $query)." WHERE id = ".SQL::escape($id);
		if(SQL::query($query) === FALSE)
			return NULL;

		// remember the publication in weekly and monthly categories
		if($publication_stamp > 0)
			Categories::remember('article:'.$id, gmstrftime('%Y-%m-%d %H:%M:%S', $publication_stamp));

		// end of job
		return NULL;
	}

	/**
	 * get some statistics
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - article is protected (active='N'), but surfer is an associate, and we are not feeding someone
	 * - article has been officially published
	 * - an expiry date has not been defined, or is not yet passed
	 * - related section is regularly displayed at the front page
	 *
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see articles/index.php
	 */
	public static function stat() {
		global $context;

		// restrict the query to addressable content
		$where = Articles::get_sql_where();

		// list only published articles
		$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
			." AND (articles.publish_date < '".$context['now']."')";

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$context['now']."'))";

		// avoid articles pushed away from the front page
		$where .= ' AND (sections.index_map != "N")';


		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(articles.edit_date) as oldest_date, MAX(articles.edit_date) as newest_date"
			." FROM ".SQL::table_name('articles')." AS articles"
			.", ".SQL::table_name('sections')." AS sections"
			." WHERE ((articles.anchor_type LIKE 'section') AND (articles.anchor_id = sections.id))  AND ".$where;

		$output = SQL::query_first($query);
		return $output;
	}

	/**
	 * get some statistics for one anchor
	 *
	 * Only articles matching following criteria are returned:
	 * - article is visible (active='Y')
	 * - article is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - article is protected (active='N'), but surfer is an associate, and we are not feeding someone
	 * - surfer is anonymous or the variant is 'boxes', and article has been officially published
	 * - logged surfers are restricted to their own articles, plus published articles
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param the selected anchor (e.g., 'section:12')
	 * @param boolean FALSE to include sticky pages, TRUE otherwise
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see sections/view.php
	 */
	public static function stat_for_anchor($anchor, $without_sticky=FALSE) {
		global $context;

		// sanity check
		if(!$anchor)
			return NULL;

		// restrict the query to addressable content
		$where = Articles::get_sql_where();

		// avoid sticky articles
		if($without_sticky)
			$where .= " AND (articles.rank >= 10000)";

		// anonymous surfers and subscribers will see only published articles
		if(!Surfer::is_member()) {
			$where .= " AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')";

		// logged surfers that are non-associates are restricted to their own articles, plus published articles
		} elseif(!Surfer::is_empowered()) {
			$where .= " AND ((articles.create_id=".Surfer::get_id().") OR (NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '0000-00-00'))"
				." AND (articles.publish_date < '".$context['now']."')))";
		}

		// only consider live articles
		$where .= " AND ((articles.expiry_date is NULL) "
				."OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$context['now']."'))";

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date"
			." FROM ".SQL::table_name('articles')." AS articles"
			." WHERE (articles.anchor LIKE '".SQL::escape($anchor)."') AND (".$where.")";

		$output = SQL::query_first($query);
		return $output;
	}

	/**
	 * encode an item to XML
	 *
	 * @param array attributes of the item to encode
	 * @param object overlay instance of this item, if any
	 * @return string the XML encoding of this item
	 */
	public static function to_xml($item, $overlay) {
		global $context;

		// article header
		$text = '<article>'."\n";

		// get unique handle of the anchor of this item
		if(isset($item['anchor']) && !strncmp($item['anchor'], 'section:', 8) && ($handle = Sections::get_handle(substr($item['anchor'], 8)))) {

			$text .= "\t".'<anchor_type>section</anchor_type>'."\n"
				."\t".'<anchor_handle>'.$handle.'</anchor_handle>'."\n";

		}

		// fields to be exported
		$labels = array('id',
			'active',
			'active_set',
			'behaviors',
			'canvas',
			'create_address',
			'create_date',
			'create_id',
			'create_name',
			'description',
			'edit_action',
			'edit_address',
			'edit_date',
			'edit_id',
			'edit_name',
			'expiry_date',
			'extra',
			'handle',
			'hits',
			'icon_url',
			'introduction',
			'language',
			'locked',
			'meta',
			'nick_name',
			'options',
			'owner_id',
			'prefix',
			'publish_address',
			'publish_date',
			'publish_id',
			'publish_name',
			'rank',
			'rating_count',
			'rating_sum',
			'review_date',
			'source',
			'suffix',
			'tags',
			'thumbnail_url',
			'title',
			'trailer');

		// process all fields
		foreach($labels as $label) {

			// export this field
			if(isset($item[ $label ]) && $item[ $label ])
				$text .= "\t".'<'.$label.'>'.encode_field($item[ $label ]).'</'.$label.'>'."\n";

		}

		// handle of item owner
		if(isset($item['owner_id']) && ($user = Users::get($item['owner_id'])))
			$text .= "\t".'<owner_nick_name>'.$user['nick_name'].'</owner_nick_name>'."\n";

		// handle of item creator
		if(isset($item['create_id']) && ($user = Users::get($item['create_id'])))
			$text .= "\t".'<create_nick_name>'.$user['nick_name'].'</create_nick_name>'."\n";

		// handle of last editor
		if(isset($item['edit_id']) && ($user = Users::get($item['edit_id'])))
			$text .= "\t".'<edit_nick_name>'.$user['nick_name'].'</edit_nick_name>'."\n";

		// handle of publisher
		if(isset($item['publish_id']) && ($user = Users::get($item['publish_id'])))
			$text .= "\t".'<publish_nick_name>'.$user['nick_name'].'</publish_nick_name>'."\n";

		// the overlay, if any
		if(is_object($overlay))
			$text .= $overlay->export();


		// article footer
		$text .= '</article>'."\n";

		// job done
		return $text;

	}


	/**
	 * unpublish an article
	 *
	 * Clear all publishing information
	 *
	 * @param int the id of the item to unpublish
	 * @return string either a null string, or some text describing an error to be inserted into the html response
	 * @see articles/unpublish.php
	**/
	public static function unpublish($id) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return i18n::s('No item has the provided id.');

		// set default values
		$fields = array();
		Surfer::check_default_editor($fields);

		// update an existing record, except the date
		$query = "UPDATE ".SQL::table_name('articles')." SET "
			." publish_name='',"
			." publish_id=0,"
			." publish_address='',"
			." publish_date='',"
			." edit_name='".SQL::escape($fields['edit_name'])."',"
			." edit_id=".SQL::escape($fields['edit_id']).","
			." edit_address='".SQL::escape($fields['edit_address'])."',"
			." edit_action='article:update'"
			." WHERE id = ".SQL::escape($id);
		SQL::query($query);

		// end of job
		return NULL;
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('articles');

?>
