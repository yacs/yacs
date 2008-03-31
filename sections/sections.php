<?php
/**
 * the database abstraction layer for sections
 *
 * @todo ensure that locked sections cannot receive images
 *
 * [title]How do sections differ from categories?[/title]
 *
 * Sections are the top-level containers of articles.
 *
 * Sections provide following advanced features:
 *
 * [*] Access restriction - Some sections are public, while others are reserved to authenticated members or only to associates.
 * Of course, YACS ensures that access rules are cascaded to anchored articles.
 * Create protected sections each time you want to restrict access to some information.
 *
 * [*] Overlay extension - Articles and sub-sections can be extended using the overlay interface.
 * You just have to edit a section and to mention, in the content overlay field, the name of the PHP class to use.
 * For example, drop 'recipe' to transform a section to a cookbook, or 'poll' to create a section dedicated to polls.
 * Of course, you can create overlays of your own.
 * The index of supported overlays is at [link]overlays/index.php[/link].
 *
 * [*] Skin variant - Each section can have its own skin, that will be cascaded to related articles as well.
 * Basically, this gives you the power of selecting quite rich rendering options for your site.
 * For example, you can select one blog style for section A, and another blog style for section B.
 * If you have a single skin, and want to transmit the variant you want to use, add the 'variant_XXXX' to the options field,
 * where XXXX is your actual option.
 * If you prefer to use a particular skin at some section, use 'skin_YYYY' instead, where YYYY is the name of the target skin.
 *
 *
 * [title]Pre-defined sections[/title]
 *
 * At the moment following section nick names are used throughout yacs:
 * - 'chats' - private interactive discussions - created automatically in [script]users/contact.php[/script]
 * - 'clicks' - to put orphan links when clicked - created automatically in [script]links/links.php[/script]
 * - 'covers' - the articles displayed at the very first page of the site
 * - 'extra_boxes' - boxes only displayed at the front page, in the extra panel
 * - 'files' - a sample library of files
 * - 'forum' - a sample forum
 * - 'gadget_boxes' - boxes only displayed at the front page, as gadgets
 * - 'letters' - pages sent by e-mail to subscribers - created automatically in [script]letters/new.php[/script]
 * - 'links' - a sample library of links
 * - 'menus' - the articles used to build the main menu of the server
 * - 'my_section' - a sample plain section
 * - 'navigation_boxes' - boxes on the left of the page
 * - 'partners' - our preferred partners
 * - 'polls' - some of the polls published on this site
 * - 'queries' - pages sent by surfers to submit their queries to the webmaster - created automatically in [script]query.php[/script]
 * - 'recipes' - a sample cookbook
  *
 * @see letters/new.php
 * @see links/links.php
 * @see query.php
 *
 *
 * [title]How to lock a section?[/title]
 *
 * A section can be locked to avoid new posts.
 * This feature only concerns regular members of the community, as associates and editors are always allowed to add, change of remove any page.
 * Note that locked sections do not appear in w.bloggar lists, even to authenticated associates.
 *
 * [title]How to manage options for sections?[/title]
 *
 * The options fields are a convenient place to save attributes for any section without extending the database schema.
 * As sections are commonly used to anchor some pages, their options can also be checked through the [code]has_option()[/code]
 * member function of the [code]Anchor[/code] interface. Check [script]shared/anchor.php[/script] for more information.
 *
 * Options for section content (articles, etc.) are distinct from options for the section itself.
 *
 * You can combine any of following keywords in fields for content, with the separator (spaces, tabs, commas) of your choice:
 *
 * [*] [code]anonymous_edit[/code] - Allow anonymous surfers to handle pages, files, etc., attached to the section.
 * The section itself cannot be modified anonymously. Use this setting to configure Wiki-like sections.
 *
 * [*] [code]auto_publish[/code] - Every post is automatically published, without control from an associate.
 * Posters can prevent publication by adding the option 'draft' to their post.
 *
 * [*] [code]members_edit[/code] - Allow authenticated members to handle pages, files, etc., attached to the section.
 * Use this setting to create Wikis restricted to the community.
 *
 * [*] [code]no_comments[/code] - Prevent surfers to react to posted articles.
 *
 * [*] [code]no_neighbours[/code] - Use this setting to avoid previous / next links added by YACS to navigate a section.
 *
 * [*] [code]with_bottom_tools[/code] - Add tools at the bottom of each article to mail and convert text
 * to PDF, Palm, MS-Word or to a printer. These tools are not displayed by default.
 * You may find useful to activate them to further help surfers to reuse published material.
 *
 * [*] [code]with_extra_profile[/code] - Display poster profile in the extra panel of the template.
 * This setting is suitable to blogs. By default YACS does not display poster profile.
 *
 * [*] [code]with_prefix_profile[/code] - Display poster profile at the top of the page, after page title.
 * This setting is suitable to original publications, white papers, etc. By default YACS does not display poster profile.
 *
 * [*] [code]with_rating[/code] - Allow for page rating by surfers.
 * By default YACS does not display rating tools.
 * Use this setting to involve your community in the improvement of your site.
 *
 * [*] [code]with_suffix_profile[/code] - Display poster profile at the bottom of the page, after main content.
 * This setting is suitable to original publications, white papers, etc. By default YACS does not display poster profile.
 *
 *
 * You can combine any of following keywords in fields for section options, with the separator (spaces, tabs, commas) of your choice:
 *
 * [*] [code]articles_by_title[/code] - Order pages by alphabetical order instead of using edition time information.
 *
 * [*] [code]files_by_title[/code] - Order files by alphabetical order instead of using edition time information.
 * To be used jointly with '[code]with_files[/code]', to activate the posting of files.
 *
 * [*] [code]links_by_title[/code] - Rank links by alphabetical order instead of using edition time information.
 * To be used jointly with '[code]with_links[/code]', to activate the posting of links.
 *
 * [*] [code]skin_&lt;xxxx&gt;[/code] - Select one skin explicitly.
 * Use this option to apply a specific skin to a section.
 * Articles anchored to this section will use the same skin.
 *
 * [*] [code]variant_&lt;xxxx&gt;[/code] - Select one skin variant explicitly.
 * Usually only the variant '[code]sections[/code]' is used throughout sections.
 * This can be changed to '[code]xxxx[/code]' by using the option [code]variant_&lt;xxxx&gt;[/code].
 * Then the underlying skin may adapt to this code by looking at [code]$context['skin_variant'][/code].
 * Basically, use variants to change the rendering of individual articles of your site, if the skin allows it.
 *
 * [*] [code]with_comments[/code] - The section index page is a thread, and can be commented.
 * By default YACS allows comments only in content pages.
 * However, in some situations you may ned to capture surfers feed-back directly at some particular section.
 * Set the option [code]with_comments[/code] to activate the commenting system.
 * Please note that threads based on sections differ from threads based on articles.
 * For example, they are not listed at the front page.
 *
 * [*] [code]with_files[/code] - Files can be attached to the section index page.
 * By default YACS allows for file attachment only in content.
 * But you may have to create a special set of files out of a section.
 * If this is the case, add the option [code]with_files[/code] manually and upload shared files.
 *
 * [*] [code]with_links[/code] - Links can be posted to the section index page.
 * By default inks can be attached only to content pages.
 * But you may have to create a special set of bookmarks out of a section.
 * If this is the case, add the option [code]with_links[/code] manually and add shared links.
 *
 *
 * [title]How to change sections layout?[/title]
 *
 *
 * [*] [code]boxesandarrows[/code]
 *
 * @see sections/layout_sections_as_boxesandarrows.php
 * @see articles/layout_articles_as_boxesandarrows.php
 * @see comments/layout_comments_as_boxesandarrows.php
 *
 * [*] [code]manual[/code]
 *
 * @see articles/layout_articles_as_manual.php
 * @see comments/layout_comments_as_manual.php
 *
 * [*] [code]jive[/code]
 *
 * @see articles/layout_articles_as_jive.php
 * @see comments/layout_comments_as_jive.php
 *
 * [*] [code]yabb[/code] - This section acts a threaded forum, or bulletin board.
 * Each article is a topic. Comments are attached to articles to build a straightforward threaded system.
 *
 * @see articles/layout_articles_as_yabb.php
 * @see comments/layout_comments_as_yabb.php
 *
 *
 * [title]Handling sections at the index page[/title]
 *
 * This applies to sections that are not at the top level of the hierarchy.
 * For anchored sections, the parameter 'index_panel' defines how their content is handled on index pages of their parent section.
 *
 * [*] '[code]main[/code]' - The default value.
 * Use the layout specified in the field 'articles_layout' of the parent section ('daily', 'boxesandarrows', etc.)
 *
 * [*] '[code]extra[/code]' - Summarize most recent entries in an extra box at the index page.
 * May prove to be useful with discussion boards.
 *
 * [*] '[code]extra_boxes[/code]' - Same as the previous one, except that YACS creates one extra box per article.
 *
 * [*] '[code]gadget[/code]' - Summarize most recent entries in a gadget box at the index page.
 * May prove to be useful with discussion boards for example.
 *
 * [*] '[code]gadget_boxes[/code]' - Same as the previous one, except that YACS creates one gadget box per article.
 *
 * [*] '[code]icon_bottom[/code]' - List thumbnails of related articles at the bottom of the page.
 * Useful to feature logos of partners on index pages.
 *
 * [*] '[code]icon_top[/code]' - List thumbnails of related articles at the top of the page.
 * Useful to feature logos of partners on index pages.
 *
 * [*] '[code]news[/code]' - List articles in the area dedicated to flashy news
 *
 * [*] '[code]none[/code]' - Do not list section content at the front page.
 * Useful to cache some sections, such as the archives newsletters.
 *
 *
 * [title]How to order sections?[/title]
 *
 * Usually sections are ranked by edition date, with the most recent section coming first.
 * You can change this 'natural' order by modifying the value of the rank field.
 *
 * What is the result obtained, depending on the value set?
 *
 * [*] 10000 - This is the default value. All sections created by YACS are ranked equally.
 *
 * [*] Less than 10000 - Useful to order sections, and to make them listed at the front page.
 * Ordered, since the lower rank values come before higher rank values.
 * Pages that have the same rank value are ordered by dates, with the newest item coming first.
 * Moreover, the very first sections of the list can be listed at the front page either in
 * tabs, if the template manages this, or in the page menu, if activated at the configuration panel for skins.
 *
 * [*] More than 10000 - To reject sections at the end of the site map.
 *
 *
 * [title]Handling sections at the front page[/title]
 *
 * By default section content is automatically featured at the front page.
 * This can be changed through the 'home_panel' field.
 *
 * [*] '[code]main[/code]' - The default value.
 * Use the main layout specified in the configuration panel for skins ('alistapart', 'boxesandarrows', etc.)
 *
 * [*] '[code]extra[/code]' - Summarize most recent entries in an extra box at the front page.
 * May prove to be useful with discussion boards.
 *
 * [*] '[code]extra_boxes[/code]' - Same as the previous one, except that YACS creates one extra box per article.
 *
 * [*] '[code]gadget[/code]' - Summarize most recent entries in a gadget box at the front page.
 * May prove to be useful with discussion boards for example.
 *
 * [*] '[code]gadget_boxes[/code]' - Same as the previous one, except that YACS creates one gadget box per article.
 *
 * [*] '[code]icon[/code]' - List thumbnails of related articles at the bottom of the page.
 * Useful to feature logos of partners at the front page.
 *
 * [*] '[code]news[/code]' - List articles in the area dedicated to flashy news
 * Actual rendering depends of parameters 'root_news_layout' and 'root_news_count', set in [script]configure.php[/script]
 *
 * [*] '[code]none[/code]' - Do not list section content at the front page.
 * Useful to cache some sections, such as the archives newsletters.
 *
 *
 * [title]Handling sections at the site map[/title]
 *
 * By default top most sections (aka, not anchored to another section) are publicly listed at the site map.
 * Change the field 'index_map' to 'N' to prevent this behaviour. Hidden sections are listed among other special sections to preserve access from associates.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Fw_crocodile
 * @tester Tingadon
 * @tester Mark
 * @tester Ddaniel
 * @tester Olivier
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Sections {

	/**
	 * check if new sections can be added
	 *
	 * This function returns TRUE if sections can be added to some place,
	 * and FALSE otherwise.
	 *
	 * The function prevents the creation of new sections when:
	 * - the global parameter 'users_without_submission' has been set to 'Y'
	 * - provided item has been locked
	 * - item has some option 'no_sections' that prevents new sections
	 * - the anchor has some option 'no_sections' that prevents new sections
	 *
	 * Then the function allows for new sections when:
	 * - surfer has been authenticated as a valid member
	 * - or parameter 'users_without_teasers' has not been set to 'Y'
	 *
	 * Then, ultimately, the default is not allow for the creation of new
	 * sections.
	 *
	 * @param object an instance of the Anchor interface, if any
	 * @param array a set of item attributes, if any
	 * @return TRUE or FALSE
	 */
	function are_allowed($anchor=NULL, $item=NULL) {
		global $context;

		// sections are prevented in anchor
		if(is_object($anchor) && is_callable(array($anchor, 'has_option')) && $anchor->has_option('no_sections'))
			return FALSE;

		// sections are prevented in item
		if(isset($item['options']) && is_string($item['options']) && preg_match('/\bno_sections\b/i', $item['options']))
			return FALSE;

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// submissions have been disallowed
		if(isset($context['users_without_submission']) && ($context['users_without_submission'] == 'Y'))
			return FALSE;

		// surfer has special privileges
		if(Surfer::is_empowered())
			return TRUE;

		// surfer screening
		if(isset($item['active']) && ($item['active'] == 'N') && !Surfer::is_empowered())
			return FALSE;
		if(isset($item['active']) && ($item['active'] == 'R') && !Surfer::is_logged())
			return FALSE;

		// anchor has been locked
		if(is_object($anchor) && is_callable(array($anchor, 'has_option')) && $anchor->has_option('locked'))
			return FALSE;

		// item has been locked
		if(isset($item['locked']) && is_string($item['locked']) && ($item['locked'] == 'Y'))
			return FALSE;

		// anonymous contributions are allowed for this anchor
		if(is_object($anchor) && $anchor->is_editable())
			return TRUE;

		// anonymous contributions are allowed for this section
		if(isset($item['content_options']) && preg_match('/\banonymous_edit\b/i', $item['content_options']))
			return TRUE;

		// anonymous contributions are allowed for this item
		if(isset($item['options']) && preg_match('/\banonymous_edit\b/i', $item['options']))
			return TRUE;

		// teasers are activated
		if(!isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			return TRUE;

		// the default is to not allow for new sections
		return FALSE;
	}

	/**
	 * count records for some anchor
	 *
	 * Only sections matching following criteria are returned:
	 * - section is visible (active='Y')
	 * - section is restricted (active='R'), but surfer is a logged user
	 * - section is hidden (active='N'), but surfer is an associate
	 *
	 * Non-activated and expired sections are counted as well.
	 *
	 * @param string the selected anchor (e.g., 'section:12')
	 * @return int resulting count, or NULL on error
	 */
	function count_for_anchor($anchor = '') {
		global $context;

		// profiling mode
		if($context['with_profile'] == 'Y')
			logger::profile('sections::count_for_anchor');

		// limit the query to one level
		if($anchor)
			$where = "(sections.anchor LIKE '".SQL::escape($anchor)."')";
		else
			$where = "(sections.anchor='' OR sections.anchor is NULL)";

		// display active and restricted items
		$where .= "AND (sections.active='Y'";

		// list restricted sections to authenticated surfers
		if(Surfer::is_logged())
			$where .= " OR sections.active='R'";

		// list hidden sections to associates, editors and readers
		if(Surfer::is_empowered('S'))
			$where .= " OR sections.active='N'";

		// include managed sections
		if(count($my_sections = Surfer::assigned_sections()))
			$where .= " OR sections.id LIKE ".join(" OR sections.id LIKE ", $my_sections);

		$where .= ")";

		// hide sections removed from index maps
		$where .= " AND ((sections.index_map IS NULL) OR (sections.index_map != 'N'))";

		// non-associates will have only live sections
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		if($anchor && !Surfer::is_empowered()) {
			$where .= " AND ((sections.activation_date is NULL)"
				."	OR (sections.activation_date <= '".$now."'))"
				." AND ((sections.expiry_date is NULL)"
				."	OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$now."'))";
		}

		// list sections
		$query = "SELECT COUNT(*) as count"
			." FROM ".SQL::table_name('sections')." AS sections"
			." WHERE ".$where;

		return SQL::query_scalar($query);
	}

	/**
	 * delete one section
	 *
	 * @param int the id of the section to delete
	 * @return boolean TRUE on success, FALSE otherwise
	 *
	 * @see sections/delete.php
	 */
	function delete($id) {
		global $context;

		// load the row
		$item =& Sections::get($id);
		if(!$item['id']) {
			Skin::error(i18n::s('No item has the provided id.'));
			return FALSE;
		}

		// delete related items
		Anchors::delete_related_to('section:'.$item['id']);

		// delete the record in the database
		$query = "DELETE FROM ".SQL::table_name('sections')." WHERE id = ".SQL::escape($item['id']);
		if(SQL::query($query) === FALSE)
			return FALSE;

		// clear the cache for sections
		Cache::clear(array('sections', 'section:'.$item['id'], 'categories'));

		// job done
		return TRUE;
	}

	/**
	 * duplicate all sections for a given anchor
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
	function duplicate_for_anchor($anchor_from, $anchor_to) {
		global $context;

		// look for records attached to this anchor
		$count = 0;
		$query = "SELECT * FROM ".SQL::table_name('sections')." WHERE anchor LIKE '".SQL::escape($anchor_from)."'";
		if(($result =& SQL::query($query)) && SQL::count($result)) {

			// the list of transcoded strings
			$transcoded = array();

			// process all matching records one at a time
			while($item =& SQL::fetch($result)) {

				// a new id will be allocated
				$old_id = $item['id'];
				unset($item['id']);

				// target anchor
				$item['anchor'] = $anchor_to;

				// actual duplication
				if($new_id = Sections::post($item)) {

					// more pairs of strings to transcode
					$transcoded[] = array('/\[section='.preg_quote($old_id, '/').'/i', '[section='.$new_id);

					// duplicate elements related to this item
					Anchors::duplicate_related_to('section:'.$old_id, 'section:'.$new_id);

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
	 * get one section
	 *
	 * @param int or string the id or nick name of the section
	 * @param boolean TRUE to always fetch a fresh instance, FALSE to enable cache
	 * @return the resulting $item array, with at least keys: 'id', 'title', 'description', etc.
	 */
	function &get($id, $mutable=FALSE) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// ensure proper unicode encoding
		$id = (string)$id;
		$id = utf8::to_unicode($id);

		// strip extra text from enhanced ids '3-section-title' -> '3'
		if($position = strpos($id, '-'))
			$id = substr($id, 0, $position);

		// cache previous answers
		static $cache;
		if(!is_array($cache))
			$cache = array();

		// cache hit, but only for immutable objects
		if(!$mutable && isset($cache[$id]))
			return $cache[$id];

		// select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('sections')." AS sections"
			." WHERE (sections.id LIKE '".SQL::escape($id)."') OR (sections.nick_name LIKE '".SQL::escape($id)."')";
		$output =& SQL::query_first($query);

		// save in cache
		if(isset($output['id']))
			$cache[$id] = $output;

		// return by reference
		return $output;
	}

	/**
	 * list sections as anchors
	 *
	 * This function is mainly used to build the front page and section index pages.
	 * It is call to list sections and, in a second time, articles related to these
	 * sections are actually listed.
	 *
	 * If the anchor parameter is null, this function will locate sections having the given variant
	 * in the field 'home_panel'.
	 * Else it will locate sections having the given variant in the field 'index_panel'.
	 *
	 * It accepts following variants:
	 * - 'extra' - one extra box per section
	 * - 'extra_boxes' - one extra box per article
	 * - 'icon_bottom' - list thumbnails at the bottom of the page
	 * - 'icon_top' - list thumbnails at the top of the page
	 * - 'gadget' - one gadget box per section
	 * - 'gadget_boxes' - one gadget box per article
	 * - 'main' - the main part of the index page
	 * - 'news' - flashy news
	 * - 'none' - sections are not displayed at all
	 *
	 * Normally this function is used before listing related articles. Ths is why we are not checking 'index_map' here.
	 *
	 * @param string the main anchor (e.g., 'section:123')
	 * @param string the target area (e.g., 'main')
	 * @return an array of anchors (e.g., array('section:456', 'section:789'))
	 *
	 * @see index.php
	 * @see sections/view.php
	 */
	function &get_anchors_for_anchor($anchor=NULL, $variant='main') {
		global $context;

		$criteria = array();

		// we are targeting a section index page
		if($anchor) {
			$criteria[] = "sections.anchor LIKE '".SQL::escape($anchor)."'";
			$target = 'index_panel';

		// we are targeting the front page
		} else {
			$target = 'home_panel';
		}

		// target a index area
		switch($variant) {
		case 'extra':
			$criteria[] = "(sections.".$target." = 'extra')";
			break;

		case 'extra_boxes':
			$criteria[] = "(sections.".$target." = 'extra_boxes')";
			break;

		case 'gadget':
			$criteria[] = "(sections.".$target." = 'gadget')";
			break;

		case 'gadget_boxes':
			$criteria[] = "(sections.".$target." = 'gadget_boxes')";
			break;

		case 'icon_bottom':
			$criteria[] = "(sections.".$target." = 'icon_bottom')";
			break;

		case 'icon_top':
			$criteria[] = "(sections.".$target." = 'icon_top')";
			break;

		case 'main':
		default:
			$criteria[] = "((sections.".$target." IS NULL) OR (sections.".$target." = '') OR (sections.".$target." = 'main'))";
			break;

		case 'news':
			$criteria[] = "(sections.".$target." = 'news')";
			break;

		case 'none':
			$criteria[] = "(sections.".$target." = 'none')";
			break;

		}

		// display active items
		$active = "(sections.active='Y'";

		// add restricted items to members, or if teasers are allowed
		if(Surfer::is_logged() || !isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			$active .= " OR sections.active='R'";

		// include hidden sections for associates
		if(Surfer::is_associate())
			$active .= " OR sections.active='N'";

		// end of filter on active field
		$criteria[] = $active.")";

		// use only live sections
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$criteria[] = "((sections.activation_date is NULL)"
			." OR (sections.activation_date <= '".$now."'))"
			." AND ((sections.expiry_date is NULL)"
			." OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$now."'))";

		// list up to 200 sections
		$query = "SELECT sections.id FROM ".SQL::table_name('sections')." AS sections"
			." WHERE ".implode(' AND', $criteria)
			." ORDER BY sections.rank, sections.title, sections.edit_date DESC LIMIT 200";
		if(!$result =& SQL::query($query)) {
			$output = NULL;
			return $output;
		}

		// process all matching sections
		$anchors = array();
		while($item =& SQL::fetch($result))
			$anchors[] = 'section:'.$item['id'];

		// return a list of anchors
		return $anchors;
	}

	/**
	 * list anchors that are part of the content tree
	 *
	 * This function is mainly used to list all containers of the content tree at once.
	 * It is call to list sections and, in a second time, articles related to these
	 * sections are actually listed.
	 *
	 * The variant is used to filter sections, as follows:
	 * - 'main' - list sections that send articles in main panel
	 * - 'index' - list sections that are listed in the main panel
	 *
	 * @param string the main anchor (e.g., 'section:123')
	 * @param string filter to apply
	 * @return an array of anchors (e.g., array('section:456', 'section:789'))
	 *
	 * @see sections/feed.php
	 */
	function &get_children_of_anchor($anchor=NULL, $variant='main') {
		global $context;

		$criteria = array();

		// several anchors
		if(is_array($anchor)) {
			$items = array();
			foreach($anchor as $token)
				$items[] = "sections.anchor LIKE '".SQL::escape($token)."'";
			$criteria[] = join(' OR ', $items);
			$target = 'index_panel';

		// we are targeting a section index page
		} elseif($anchor) {
			$criteria[] = "sections.anchor LIKE '".SQL::escape($anchor)."'";
			$target = 'index_panel';

		// we are targeting the front page
		} else {
			$target = 'home_panel';
		}

		// list sections listed in the main panel
		if($variant == 'index')
			$criteria[] = "((sections.index_map IS NULL) OR (sections.index_map = 'Y'))";

		// list sections that produce main content
		else
			$criteria[] = "((sections.".$target." IS NULL) OR (sections.".$target." = '') OR (sections.".$target." = 'main') OR (sections.".$target." = 'none'))";

		// display active items
		$active = "(sections.active='Y'";

		// add restricted items to members, or if teasers are allowed
		if(Surfer::is_logged() || !isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			$active .= " OR sections.active='R'";

		// include hidden sections for associates
		if(Surfer::is_associate())
			$active .= " OR sections.active='N'";

		// end of filter on active field
		$criteria[] = $active.")";

		// use only live sections
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$criteria[] = "((sections.activation_date is NULL)"
			." OR (sections.activation_date <= '".$now."'))"
			." AND ((sections.expiry_date is NULL)"
			." OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$now."'))";

		// limit to 50 sections
		$query = "SELECT sections.id FROM ".SQL::table_name('sections')." AS sections"
			." WHERE ".implode(' AND', $criteria)
			." ORDER BY sections.rank, sections.title, sections.edit_date DESC LIMIT 200";
		if(!$result =& SQL::query($query)) {
			$output = NULL;
			return $output;
		}

		// process all matching sections
		$anchors = array();
		while($item =& SQL::fetch($result))
			$anchors[] = 'section:'.$item['id'];

		// return a list of anchors
		return $anchors;
	}

	/**
	 * get the default section
	 *
	 * The default section is the one that has the nick name 'default'.
	 *
	 * Else it is the first top level section that appears at the site map
	 * (actually, at [script]sections/index.php[/script]).
	 *
	 * @return NULL on error, else the id of the default section
	 *
	 * @see files/edit.php
	 */
	function get_default() {
		global $context;

		// look for a 'default' section
		if($item =& Sections::get('default'))
			return $item['id'];

		// look only at top level
		$where = "(sections.anchor='' OR sections.anchor is NULL)";

		// display only active sections
		$where .= " AND (sections.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR sections.active='R'";

		// include managed sections
		if(is_callable(array('surfer', 'assigned_sections')) && count($my_sections = Surfer::assigned_sections()))
			$where .= " OR sections.id LIKE ".join(" OR sections.id LIKE ", $my_sections);

		// end of scope
		$where .= ")";

		// hide sections removed from index maps
		$where .= " AND ((sections.index_map IS NULL) OR (sections.index_map != 'N'))";

		// only consider live sections
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where = "(".$where.")"
			." AND ((sections.activation_date is NULL)"
			."	OR (sections.activation_date <= '".$now."'))"
			." AND ((sections.expiry_date is NULL)"
			."	OR (sections.expiry_date < '".NULL_DATE."') OR (sections.expiry_date > '".$now."'))";

		// if the user is listing sections to write an article, only consider open sections, even for associates
		$where .= ' AND (sections.locked NOT LIKE "Y")';

		// select among available sections
		$query = "SELECT sections.id FROM ".SQL::table_name('sections')." AS sections"
			." WHERE (".$where.")"
			." ORDER BY sections.rank, sections.title, sections.edit_date DESC LIMIT 0, 1";
		if($item =& SQL::query_first($query))
			return $item['id'];

		return NULL;
	}

	/**
	 * get sections as options of a &lt;SELECT&gt; field
	 *
	 * Only sections matching following criteria are returned:
	 * - section is visible (active='Y')
	 * - section is restricted (active='R'), but surfer is a logged user
	 * - section is restricted (active='N'), but surfer is an associate
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * This function uses the cache to save on database requests.
	 *
	 * @param string the current anchor to an existing section (e.g., 'section:12')
	 * @param string the reference of the current section (i.e., 'section:234'), to be avoided, or 'no_subsections'
	 * @return the HTML to insert in the page
	 *
	 * @see articles/edit.php
	 * @see articles/import.php
	 * @see files/edit.php
	 * @see images/edit.php
	 * @see links/edit.php
	 * @see panel.php
	 * @see skins/upload.php
	 */
	function get_options($default=NULL, $to_avoid=NULL) {
		global $context;

		// use the cache
		$cache_id = 'sections/sections.php/get_options/'.$default.'/'.$to_avoid;
		if(!$text =& Cache::get($cache_id)) {

			// all options
			$text = '';

			// assigned sections come first
			if(($assigned = Surfer::assigned_sections()) && count($assigned)) {

				// in a separate group
				$text .= '<optgroup label="'.i18n::s('Assigned sections').'">';

				// one option per assigned section
				foreach($assigned as $assigned_id) {
					if($section = Anchors::get('section:'.$assigned_id))
						$text .= '<option value="'.$section->get_reference().'">'.Skin::strip($section->get_title())."</option>\n";
				}

				// end of group
				$text .= "</optgroup>\n";
			}

			// we don't want a default section
			if($default == 'none')
				$default = NULL;

			// use the default section
			elseif(!$default)
				$default = 'section:'.Sections::get_default();

			// in a separate group
			if($assigned && count($assigned))
				$text .= '<optgroup label="'.i18n::s('Site map').'">';

			// list sections recursively
			$text .= Sections::get_options_for_anchor(NULL, '', $default, $to_avoid);

			// end of group
			if($assigned && count($assigned))
				$text .= "</optgroup>\n";

			// associates can also see inactive sections at the top level
			if(Surfer::is_associate() && ($sections = Sections::list_inactive_by_title_for_anchor(NULL, 0, 100, 'raw'))) {

				$text .= '<optgroup label="'.i18n::s('Special sections').'">';

				// add to text
				foreach($sections as $id => $attributes) {

					$reference = 'section:'.$id;

					// skip some sections
					if(preg_match('/^'.preg_quote($reference, '/').'$/', $to_avoid))
						continue;

					// this section
					$text .= '<option value="'.$reference.'"';
					if($default && ($default == $reference))
						$text .= ' selected="selected"';
					$text .= '>'.Skin::strip($attributes['title'])."</option>\n";

					// list sub-sections recursively
					$text .= Sections::get_options_for_anchor($reference, '&nbsp;&nbsp;', $default, $to_avoid);

				}

				$text .= "</optgroup>\n";
			}

			// save in cache
			Cache::put($cache_id, $text, 'sections');
		}

		return $text;
	}

	/**
	 * get options recursively
	 *
	 * This function is called internally by Sections::get_options(), above.
	 *
	 * @param string the current anchor to an existing section (e.g., 'section:12')
	 * @param string spaces to prepend before section name -- to reflect depth
	 * @param string the reference of the default section
	 * @param string the reference of the current section (i.e., 'section:234'), to be avoided, or 'no_subsections'
	 * @return the HTML to insert in the page
	 *
	 */
	function get_options_for_anchor($anchor, $spaces, $default, $to_avoid) {
		global $context;

		// add to text
		$text = '';

		// list sections at this level
		if($sections = Sections::list_by_title_for_anchor($anchor, 0, 1000, 'raw')) {

			foreach($sections as $id => $attributes) {

				$reference = 'section:'.$id;

				// skip some sections
				if(preg_match('/^'.preg_quote($reference, '/').'$/', $to_avoid))
					continue;

				// this section
				$text .= '<option value="'.$reference.'"';

				// the section is locked
				if(isset($attributes['locked']) && ($attributes['locked'] == 'Y') && !Surfer::is_associate())
					$text .= ' style="font-style: italic;" disabled="disabled"';

				// currently selected
				if($default && ($default == $reference))
					$text .= ' selected="selected"';
				$text .='>'.$spaces.Skin::strip($attributes['title'])."</option>\n";

				// depending sections, if any
				if($to_avoid == 'no_subsections')
					;
				elseif($depending = Sections::get_options_for_anchor($reference, $spaces.'&nbsp;&nbsp;', $default, $to_avoid))
					$text .= $depending;

			}
		}

		// associates can also access inactive sections at this level
		if($anchor && Surfer::is_associate()) {

			if($sections = Sections::list_inactive_by_title_for_anchor($anchor, 0, 100, 'raw')) {

				foreach($sections as $id => $attributes) {

					$reference = 'section:'.$id;

					// skip some sections
					if(preg_match('/^'.preg_quote($reference, '/').'$/', $to_avoid))
						continue;

					// this section
					$text .= '<option value="'.$reference.'"';
					if($default && ($default == $reference))
						$text .= ' selected="selected"';
					$text .='>'.$spaces.Skin::strip($attributes['title'])."</option>\n";

					// depending sections, if any
					if($depending = Sections::get_options_for_anchor($reference, $spaces.'&nbsp;&nbsp;', $default, $to_avoid))
						$text .= $depending;

				}
			}
		}

		// end of job
		return $text;
	}

	/**
	 * build a reference to a section
	 *
	 * Depending on parameter '[code]with_friendly_urls[/code]' and on action,
	 * following results can be observed:
	 *
	 * - view - sections/view.php?id=123 or sections/view.php/123 or section-123
	 *
	 * - other - sections/edit.php?id=123 or sections/edit.php/123 or section-edit/123
	 *
	 * If a fourth parameter is provided, it will take over the third one. This
	 * is used to leverage nick names in YACS, as per the following invocation:
	 * [php]
	 * Sections::get_url($item['id'], 'view', $item['title'], $item['nick_name']);
	 * [/php]
	 *
	 * @param int the id of the section to handle
	 * @param string the expected action ('view', 'print', 'edit', 'delete', ...)
	 * @param string additional data, such as section nick name, if any
	 * @param string nick name, if any, to take over on previous parameter
	 * @return string a normalized reference
	 *
	 * @see control/configure.php
	 */
	function get_url($id, $action='view', $name=NULL, $nick_name=NULL) {
		global $context;

		// use nick name instead of regular name, if one is provided
		if($nick_name)
			$name = str_replace('_', ' ', $nick_name);

		// the rss feed for files --deprecated to files::get_url()
		if($action == 'files') {
			if($context['with_friendly_urls'] == 'R')
				return 'files/feed.php/section/'.rawurlencode($id);
			elseif($context['with_friendly_urls'] == 'Y')
				return 'files/feed.php/section/'.rawurlencode($id);
			else
				return 'files/feed.php?anchor='.urlencode('section:'.$id);
		}

		// the prefix for navigation links --the parameter $name references the things to page, e.g., 'sections', 'comments', ...
		if($action == 'navigate') {
			if($context['with_friendly_urls'] == 'R')
				return 'sections/view.php/'.rawurlencode($id).'/'.rawurlencode($name).'/';
			elseif($context['with_friendly_urls'] == 'Y')
				return 'sections/view.php/'.rawurlencode($id).'/'.rawurlencode($name).'/';
			else
				return 'sections/view.php?id='.urlencode($id).'&amp;'.urlencode($name).'=';
		}

		// the purge of content in this section
		if($action == 'purge') {
			if($context['with_friendly_urls'] == 'R')
				return 'sections/bulk.php/'.rawurlencode($id).'/purge';
			elseif($context['with_friendly_urls'] == 'Y')
				return 'sections/bulk.php/'.rawurlencode($id).'/purge';
			else
				return 'sections/bulk.php?id='.urlencode($id).'&amp;action=purge';
		}

		// check the target action
		if(!preg_match('/^(bulk|delete|describe|duplicate|edit|feed|freemind|import|lock|print|slideshow|view|view_as_freemind)$/', $action))
			$action = 'view';

		// normalize the link
		return normalize_url(array('sections', 'section'), $action, $id, $name);
	}

	/**
	 * has the surfer been assign to this section?
	 *
	 * This would be the case either:
	 * - if he is a member and has been granted the editor privilege
	 * - if he is a subscriber and has been granted the reader privilege
	 *
	 * @param int the id of the target section
	 * @param int optional id to impersonate
	 * @return TRUE or FALSE
	 */
	function is_assigned($id, $surfer_id=NULL) {
		global $context;

		// no impersonation
		if(!$surfer_id) {

			// a managed section requires an authenticated user
			if(!Surfer::get_id())
				return FALSE;

			// use surfer profile
			$surfer_id = Surfer::get_id();

		}

		// ensure this section has been linked to this user
		return Members::check('user:'.$surfer_id, 'section:'.$id);
	}

	/**
	 * list sections assigned to one surfer
	 *
	 * Only sections matching following criteria are returned:
	 * - section is visible (active='Y')
	 * - or section is restricted (active='R'), but surfer is a logged user
	 * - or section is hidden (active='N'), but surfer is an associate
	 *
	 * @param int surfer id
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_assigned_by_title($surfer_id, $offset=0, $count=20, $variant='full') {
		global $context;

		// obviously we need some assigned sections
		if(!$assigned = Surfer::assigned_sections($surfer_id)) {
			$output = NULL;
			return $output;
		}

		// limit the query to one level
		$where = "(sections.id = ".join(" OR sections.id = ", $assigned).")";

		// display active items
		$where .= " AND (sections.active='Y'";

		// add restricted items to logged members, or if teasers are allowed
		if(Surfer::is_logged() || !isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			$where .= " OR sections.active='R'";

		// list hidden sections to associates and to editors
		if(is_callable(array('Surfer', 'is_empowered')) && Surfer::is_empowered())
			$where .= " OR sections.active='N'";

		// end of scope
		$where .= ")";

		// list sections
		$query = "SELECT sections.*"
			." FROM ".SQL::table_name('sections')." AS sections"
			." WHERE ".$where
			." ORDER BY sections.title, sections.edit_date DESC LIMIT ".$offset.','.$count;

		$output =& Sections::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * apply a layout to one section
	 *
	 * Only sections matching following criteria are returned:
	 * - related section is visible (active='Y')
	 * - related section is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - related section is hidden (active='N'), but the surfer is an associate or an editor,
	 *
	 * @param int id of the target section
	 * @param string the list variant, if any
	 * @return NULL on error, else the outcome of the layout
	 */
	function &list_for_id($id, $variant='compact') {
		global $context;

		// select among active items
		$where = "sections.active='Y'";

		// add restricted items to members, or if teasers are allowed
		if(Surfer::is_logged() || !isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			$where .= " OR sections.active='R'";

		// add hidden items to associates, editors and readers
		if(Surfer::is_empowered('S'))
			$where .= " OR sections.active='N'";

		// bracket OR statements
		$where = '('.$where.')';

		// sections by title
		$query = "SELECT sections.*"
			." FROM ".SQL::table_name('sections')." AS sections"
			." WHERE (sections.id = LIKE '".SQL::escape($name)."') AND ".$where;

		$output =& Sections::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list named sections
	 *
	 * This function lists all sections with the same nick name.
	 *
	 * This is used by the page locator to offer alternatives when several pages have the same nick names.
	 * It is also used to link a page to twins, these being, most of the time, translations.
	 *
	 * Only sections matching following criteria are returned:
	 * - related section is visible (active='Y')
	 * - related section is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - related section is hidden (active='N'), but the surfer is an associate or an editor,
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param string the nick name
	 * @param int the id of the current page, which will not be listed
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_for_name($name, $exception=NULL, $variant='compact') {
		global $context;

		// select among active items
		$where = "sections.active='Y'";

		// add restricted items to members, or if teasers are allowed
		if(Surfer::is_logged() || !isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			$where .= " OR sections.active='R'";

		// add hidden items to associates, editors and readers
		if(Surfer::is_empowered('S'))
			$where .= " OR sections.active='N'";

		// bracket OR statements
		$where = '('.$where.')';

		// avoid exception, if any
		if($exception)
			$where .= " AND (sections.id != ".SQL::escape($exception).")";

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// only consider live sections
		$where .= " AND ((sections.expiry_date is NULL) "
				."OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$now."'))";

		// sections by title -- up to 100 sections with the same name
		$query = "SELECT sections.*"
			." FROM ".SQL::table_name('sections')." AS sections"
			." WHERE (sections.nick_name LIKE '".SQL::escape($name)."') AND ".$where
			." ORDER BY sections.title LIMIT 100";

		$output =& Sections::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list sections by title at a given level of the hierarchy
	 *
	 * Actually list sections by rank, then by title, then by edition date.
	 * If you select to not use the ranking system, sections will be ordered by title only.
	 * Else sections with a low ranking mark will appear at the beginning of the list,
	 * and sections with a high ranking mark will be put at the end of the list.
	 *
	 * Only sections matching following criteria are returned:
	 * - section is visible (active='Y')
	 * - or section is restricted (active='R'), but surfer is a logged user
	 * - or section is hidden (active='N'), but surfer is an associate
	 * - section is publicly available (index_map != 'N')
	 * - an activation date has not been defined, or is over
	 * - an expiry date has not been defined, or is not yet passed
	 * - if called remotely, section is not locked
	 *
	 * For associates to see other sections, function [code]Sections::list_inactive_by_title_for_anchor()[/code] is called from
	 * the site map ([script]sections/index.php[/script]), and from sections index pages ([script]sections/view.php[/script]).
	 *
	 * @param mixed the section anchor(s) to which these sections are linked
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @param boolean TRUE to not report on SQL error, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see articles/edit.php
	 * @see feeds/describe.php
	 * @see index.php
	 * @see sections/index.php
	 * @see sections/layout_sections_as_inline.php
	 * @see sections/layout_sections_as_yahoo.php
	 * @see sections/view.php
	 */
	function &list_by_title_for_anchor($anchor, $offset=0, $count=20, $variant='full', $silent=FALSE) {
		global $context;

		// limit the query to one level
		if(is_array($anchor))
			$where = "(sections.anchor LIKE '".join("' OR sections.anchor LIKE '", $anchor)."')";
		elseif($anchor)
			$where = "(sections.anchor LIKE '".SQL::escape($anchor)."')";
		else
			$where = "(sections.anchor='' OR sections.anchor IS NULL)";

		// display active items
		$where .= " AND (sections.active='Y'";

		// add restricted items to logged members, or if teasers are allowed
		if(Surfer::is_logged() || !isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			$where .= " OR sections.active='R'";

		// list hidden sections to associates, editors and subscribers
		if(Surfer::is_empowered('S'))
			$where .= " OR sections.active='N'";

		// include managed sections
		if(count($my_sections = Surfer::assigned_sections()))
			$where .= " OR sections.id LIKE ".join(" OR sections.id LIKE ", $my_sections);

		// end of scope
		$where .= ")";

		// hide sections removed from index maps
		$where .= " AND ((sections.index_map IS NULL) OR (sections.index_map != 'N'))";

		// non-associates will have only live sections
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(!Surfer::is_associate()) {
			$where .= " AND ((sections.activation_date is NULL)"
				."	OR (sections.activation_date <= '".$now."'))"
				." AND ((sections.expiry_date is NULL)"
				."	OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$now."'))";
		}

		// if the user is listing sections to write an article, only consider open sections, even for associates
		if(is_string($variant) && ($variant == 'select'))
			$where .= ' AND (sections.locked NOT LIKE "Y")';

		// list sections
		$query = "SELECT sections.*"
			." FROM ".SQL::table_name('sections')." AS sections"
			." WHERE ".$where
			." ORDER BY sections.rank, sections.title, sections.edit_date DESC LIMIT ".$offset.','.$count;

		$output =& Sections::list_selected(SQL::query($query, $silent), $variant);
		return $output;
	}

	/**
	 * list all sections for a given parent
	 *
	 * This function is suitable is you want to handle all sub-sections
	 * of a section.
	 *
	 * Only sections matching following criteria are returned:
	 * - section is visible (active='Y')
	 * - or section is restricted (active='R'), but surfer is a logged user
	 * - or section is hidden (active='N'), but surfer is an associate
	 *
	 * @param string reference to the parent section
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return array an ordered array with $url => ($prefix, $label, $suffix, $icon), else NULL on error
	 */
	function &list_for_anchor($anchor, $variant='raw') {
		global $context;

		// limit the scope to one section
		$where = "(sections.anchor LIKE '".SQL::escape($anchor)."')";

		// display active items
		$where .= " AND (sections.active='Y'";

		// add restricted items to logged members, or if teasers are allowed
		if(Surfer::is_logged() || !isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			$where .= " OR sections.active='R'";

		// list hidden sections to associates, editors and subscribers
		if(Surfer::is_empowered('S'))
			$where .= " OR sections.active='N'";

		// include managed sections
		if(count($my_sections = Surfer::assigned_sections()))
			$where .= " OR sections.id LIKE ".join(" OR sections.id LIKE ", $my_sections);

		// end of scope
		$where .= ')';

		// non-associates will have only live sections
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(!Surfer::is_associate()) {
			$where .= " AND ((sections.activation_date is NULL)"
				."	OR (sections.activation_date <= '".$now."'))"
				." AND ((sections.expiry_date is NULL)"
				."	OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$now."'))";
		}

		// list sections
		$query = "SELECT sections.*"
			." FROM ".SQL::table_name('sections')." AS sections"
			." WHERE ".$where
			." ORDER BY sections.rank, sections.title, sections.edit_date DESC LIMIT 0, 500";

		$output =& Sections::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list inactive sub-sections by title for a given anchor
	 *
	 * Actually list sections by rank, then by title, then by edition date.
	 * If you select to not use the ranking system, sections will be ordered by title only.
	 * Else sections with a low ranking mark will appear first,
	 * and sections with a high ranking mark will be put at the end of the list.
	 *
	 * To be used by associates to access special sections (menu, boxes, etc.)
	 *
	 * Only sections matching following criteria are returned:
	 * - an activation date has been set in the future
	 * - or an expiry date has been defined, and the section is now dead
	 * - or the section has been removed from index maps (index_map='N')
	 *
	 * Alternatively, all sub-sections are listed if the parent section
	 * does not feature sections (sections_layout='none').
	 *
	 * @param the section anchor to which these sections are linked
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see sections/index.php
	 * @see sections/view.php
	 */
	function &list_inactive_by_title_for_anchor($anchor, $offset=0, $count=20, $variant='full') {
		global $context;

		// only for associates and editors
		if(!Surfer::is_empowered())
			return NULL;

		// limit the query to one level
		if($anchor)
			$where = "(sections.anchor LIKE '".SQL::escape($anchor)."')";
		else
			$where = "(sections.anchor='' OR sections.anchor is NULL)";

		// display everything if no sub-section is laid out in parent section
		if($anchor && ($parent = Anchors::get($anchor)) && $parent->has_value('sections_layout', 'none'))
			;

		// only inactive sections have to be displayed
		else {

			// restrict the scope
			$where .= ' AND (';

			// list dead sections
			$now = gmstrftime('%Y-%m-%d %H:%M:%S');
			$where .= "(sections.activation_date >= '".$now."')"
				." OR ((sections.expiry_date > '".NULL_DATE."') AND (sections.expiry_date <= '".$now."'))";

			// add sections removed from normal index map
			$where .= " OR (sections.index_map = 'N')";

			// end of scope
			$where .= ')';

		}

		// list sections
		$query = "SELECT sections.*"
			." FROM ".SQL::table_name('sections')." AS sections"
			." WHERE ".$where
			." ORDER BY sections.rank, sections.title, sections.edit_date DESC	LIMIT ".$offset.','.$count;

		$output =& Sections::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list selected sections
	 *
	 * Accept following layouts:
	 * - 'compact' - to build short lists in boxes and sidebars
	 * - 'freemind' - to create Freemind maps
	 * - 'full' - include anchor information -- also the default value
	 * - 'menu' - returns the url and the title - used mainly at services/blog.php
	 * - 'raw' - returns the id and the title
	 * - 'references' - like 'full', but urls are references to sections
	 * - 'select' - like 'full', but urls are links to the article editor form - used at articles/edit.php
	 * - 'tabs' - more compact than compact
	 * - 'thumbnails' - to build a visual list
	 *
	 * Built-in variants 'compact', 'full' or 'select' all return a list of $url => ($prefix, $label, $suffix, $type, $icon),
	 * but the content of these variables vary from one variant to another.
	 *
	 * Variables returned when the variant equals 'full' or 'select':
	 * - $prefix = ( expired | new | updated ) ( private | restricted )
	 * - $label = &lt;title&gt;
	 * - $suffix = (n pages, n files, n links, n comments) \n &lt;introduction&gt;
	 * - $type = section
	 * - $icon = &lt;thumbnail_url&gt;
	 *
	 * Variables returned when the variant equals 'compact':
	 * - $prefix = NULL
	 * - $label = &lt;title&gt;
	 * - $suffix = section_&lt;id&gt; (may be used to implement the 'you are here' feature in any template)
	 * - $type = basic
	 * - $icon = NULL
	 *
	 *
	 * @param resource result of database query
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see skins/boxesandarrows/template.php
	 */
	function &list_selected(&$result, $variant='full') {
		global $context;

		// no result
		if(!$result) {
			$output = NULL;
			return $output;
		}

		// special layouts
		if(is_object($variant)) {
			$output =& $variant->layout($result);
			return $output;
		}

		// build an array of links
		switch($variant) {

		case 'compact':
			include_once $context['path_to_root'].'sections/layout_sections_as_compact.php';
			$layout =& new Layout_sections_as_compact();
			$output =& $layout->layout($result);
			return $output;

		case 'freemind':
			include_once $context['path_to_root'].'sections/layout_sections_as_freemind.php';
			$layout =& new Layout_sections_as_freemind();
			$output =& $layout->layout($result);
			return $output;

		case 'menu':
			include_once $context['path_to_root'].'sections/layout_sections_as_menu.php';
			$layout =& new Layout_sections_as_menu();
			$output =& $layout->layout($result);
			return $output;

		case 'raw':
			include_once $context['path_to_root'].'sections/layout_sections_as_raw.php';
			$layout =& new Layout_sections_as_raw();
			$output =& $layout->layout($result);
			return $output;

		case 'tabs':
			include_once $context['path_to_root'].'sections/layout_sections_as_tabs.php';
			$layout =& new Layout_sections_as_tabs();
			$output =& $layout->layout($result);
			return $output;

		case 'thumbnails':
			include_once $context['path_to_root'].'sections/layout_sections_as_thumbnails.php';
			$layout =& new Layout_sections_as_thumbnails();
			$output =& $layout->layout($result);
			return $output;

		case 'yahoo':
			include_once $context['path_to_root'].'sections/layout_sections_as_yahoo.php';
			$layout =& new Layout_sections_as_yahoo();
			$output =& $layout->layout($result);
			return $output;

		default:

			// allow for overload in skin -- see skins/import.php
			if(is_callable(array('skin', 'layout_section'))) {

				// build an array of links
				$items = array();
				while($item =& SQL::fetch($result)) {

					// reset the rendering engine between items
					if(is_callable(array('Codes', 'initialize')))
						Codes::initialize(sections::get_url($item['id'], 'view', $item['title']));

					// url to read the full section
					$url = Sections::get_url($item['id'], 'view', $item['title']);

					// format the resulting string depending on variant
					$items[$url] = Skin::layout_section($item, $variant);

				}

				// end of processing
				SQL::free($result);
				return $items;

			// else use an external layout
			} else {
				include_once $context['path_to_root'].'sections/layout_sections.php';
				$layout =& new Layout_sections();
				$layout->set_variant($variant);
				$output =& $layout->layout($result);
				return $output;
			}

		}

	}

	/**
	 * lock/unlock a section
	 *
	 * @param int the id of the section to update
	 * @param string the previous locking state
	 * @return TRUE on success toggle, FALSE otherwise
	 */
	function lock($id, $status='Y') {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id)) {
			Skin::error(i18n::s('No item has the provided id.'));
			return FALSE;
		}

		// toggle status
		if($status == 'Y')
			$status = 'N';
		else
			$status = 'Y';

		// do the job
		$query = "UPDATE ".SQL::table_name('sections')." SET locked='".SQL::escape($status)."' WHERE id = ".SQL::escape($id);
		if(SQL::query($query) === FALSE)
			return FALSE;
		return TRUE;
	}

	/**
	 * get the id of one section knowing its nick name
	 *
	 * At the moment following section nick names are used throughout yacs:
	 * - 'clicks' - to host external link that are embed into ordinary pages
	 * - 'covers' - pages to be displayed as introductory articles at the front page
	 * - 'forum' - the coffee machine, for those that are not drinking so much coffee
	 * - 'extra_boxes' - page to be displayed as small boxes at the front page
	 * - 'letters' - pages sent by e-mail to subscribers
	 * - 'menus' - page to be displayed on all pages as the main site menu
	 * - 'my_section' - sample section for demonstrattion purpose
	 * - 'navigation_boxes' - pages to be displayed as small boxes on all pages
	 * - 'partners' - pages dedicated to external partners
	 * - 'queries' - pages sent by surfers to submit their queries to the webmaster
	 *
	 * @param string the nick name looked for
	 * @return string either 'section:&lt;id&gt;', or NULL
	 *
	 * @see articles/populate.php
	 * @see articles/review.php
	 * @see control/import.php
	 * @see index.php
	 * @see letters/index.php
	 * @see letters/new.php
	 * @see links/index.php
	 * @see links/links.php
	 */
	function lookup($nick_name) {
		if($item =& Sections::get($nick_name))
			return 'section:'.$item['id'];
		return NULL;
	}

	/**
	 * post a new section
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param array an array of fields
	 * @return the id of the new article, or FALSE on error
	 *
	 * @see control/import.php
	 * @see sections/edit.php
	 * @see sections/populate.php
	 * @see letters/new.php
	 * @see links/links.php
	 * @see query.php
	**/
	function post($fields) {
		global $context;

		// title cannot be empty
		if(!isset($fields['title']) || !trim($fields['title'])) {
			Skin::error(i18n::s('No title has been provided.'));
			return FALSE;
		}

		// protect from hackers
		if(isset($fields['bullet_url']))
			$fields['bullet_url'] = preg_replace(FORBIDDEN_CHARS_IN_URLS, '_', $fields['bullet_url']);
		if(isset($fields['icon_url']))
			$fields['icon_url'] = preg_replace(FORBIDDEN_CHARS_IN_URLS, '_', $fields['icon_url']);
		if(isset($fields['thumbnail_url']))
			$fields['thumbnail_url'] = preg_replace(FORBIDDEN_CHARS_IN_URLS, '_', $fields['thumbnail_url']);

		// set default values for this editor
		$fields = Surfer::check_default_editor($fields);

		// reinforce date formats
		if(!isset($fields['activation_date']) || ($fields['activation_date'] <= NULL_DATE))
			$fields['activation_date'] = NULL_DATE;
		if(!isset($fields['create_date']) || ($fields['create_date'] <= NULL_DATE))
			$fields['create_date'] = $fields['edit_date'];
		if(!isset($fields['expiry_date']) || ($fields['expiry_date'] <= NULL_DATE))
			$fields['expiry_date'] = NULL_DATE;
		if(!isset($fields['publish_date']) || ($fields['publish_date'] <= NULL_DATE))
			$fields['publish_date'] = NULL_DATE;

		// set conservative default values
		if(!isset($fields['active_set']))
			$fields['active_set'] = 'Y';
		if(isset($fields['edit_action']))
			$fields['edit_action'] = preg_replace('/import$/i', 'update', $fields['edit_action']);
		if(!isset($fields['home_panel']) || !$fields['home_panel'])
			$fields['home_panel'] = 'main';
		if(!isset($fields['index_map']) || !$fields['index_map'])
			$fields['index_map'] = 'Y';
		if(!isset($fields['index_news']) || !$fields['index_news'])
			$fields['index_news'] = 'static';
		if(!isset($fields['index_panel']) || !$fields['index_panel'])
			$fields['index_panel'] = 'main';
		if(!isset($fields['rank']) || !$fields['rank'])
			$fields['rank'] = 10000;

		// set layout for sections
		if(!isset($fields['sections_layout']) || !$fields['sections_layout'] || !preg_match('/(compact|custom|decorated|folded|freemind|inline|jive|map|titles|yabb|none)/', $fields['sections_layout']))
			$fields['sections_layout'] = 'map';
		elseif($fields['sections_layout'] == 'custom') {
			if(isset($fields['sections_custom_layout']) && $fields['sections_custom_layout'])
				$fields['sections_layout'] = $fields['sections_custom_layout'];
			else
				$fields['sections_layout'] = 'map';
		}

		// set layout for articles
		if(!isset($fields['articles_layout']) || !$fields['articles_layout'] || !preg_match('/(alistapart|boxesandarrows|compact|daily|decorated|digg|jive|manual|map|none|slashdot|table|threads|wiki|yabb)/', $fields['articles_layout']))
			$fields['articles_layout'] = 'decorated';
		elseif($fields['articles_layout'] == 'custom') {
			if(isset($fields['articles_custom_layout']) && $fields['articles_custom_layout'])
				$fields['articles_layout'] = $fields['articles_custom_layout'];
			else
				$fields['articles_layout'] = 'decorated';
		}

		// cascade anchor access rights
		if(isset($fields['anchor']) && ($anchor = Anchors::get($fields['anchor'])))
			$fields['active'] = $anchor->ceil_rights($fields['active_set']);
		else
			$fields['active'] = $fields['active_set'];

		// insert a new record
		$query = "INSERT INTO ".SQL::table_name('sections')." SET ";

		// on import
		if(isset($fields['id']))
			$query .= "id='".SQL::escape($fields['id'])."',";

		// all fields should be visible
		$query .= "anchor='".SQL::escape(isset($fields['anchor']) ? $fields['anchor'] : '')."',"
			."activation_date='".SQL::escape($fields['activation_date'])."',"
			."active='".SQL::escape($fields['active'])."',"
			."active_set='".SQL::escape($fields['active_set'])."',"
			."articles_layout='".SQL::escape(isset($fields['articles_layout']) ? $fields['articles_layout'] : 'decorated')."',"
			."articles_templates='".SQL::escape(isset($fields['articles_templates']) ? $fields['articles_templates'] : '')."',"
			."behaviors='".SQL::escape(isset($fields['behaviors']) ? $fields['behaviors'] : '')."',"
			."bullet_url='".SQL::escape(isset($fields['bullet_url']) ? $fields['bullet_url'] : '')."',"
			."content_options='".SQL::escape(isset($fields['content_options']) ? $fields['content_options'] : '')."',"
			."content_overlay='".SQL::escape(isset($fields['content_overlay']) ? $fields['content_overlay'] : '')."',"
			."create_address='".SQL::escape(isset($fields['create_address']) ? $fields['create_address'] : $fields['edit_address'])."', "
			."create_date='".SQL::escape($fields['create_date'])."',"
			."create_id='".SQL::escape(isset($fields['create_id']) ? $fields['create_id'] : $fields['edit_id'])."', "
			."create_name='".SQL::escape(isset($fields['create_name']) ? $fields['create_name'] : $fields['edit_name'])."', "
			."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."',"
			."edit_action='".SQL::escape(isset($fields['edit_action']) ? $fields['edit_action'] : 'section:create')."', "
			."edit_address='".SQL::escape($fields['edit_address'])."', "
			."edit_date='".SQL::escape($fields['edit_date'])."',"
			."edit_id='".SQL::escape($fields['edit_id'])."', "
			."edit_name='".SQL::escape($fields['edit_name'])."', "
			."expiry_date='".SQL::escape($fields['expiry_date'])."',"
			."extra='".SQL::escape(isset($fields['extra']) ? $fields['extra'] : '')."',"
			."family='".SQL::escape(isset($fields['family']) ? $fields['family'] : '')."',"
			."hits=".SQL::escape(isset($fields['hits']) ? $fields['hits'] : '0').","
			."home_panel='".SQL::escape(isset($fields['home_panel']) ? $fields['home_panel'] : 'main')."',"
			."icon_url='".SQL::escape(isset($fields['icon_url']) ? $fields['icon_url'] : '')."',"
			."index_map='".SQL::escape(isset($fields['index_map']) ? $fields['index_map'] : 'Y')."',"
			."index_news='".SQL::escape(isset($fields['index_news']) ? $fields['index_news'] : 'static')."',"
			."index_news_count=".SQL::escape(isset($fields['index_news_count']) ? $fields['index_news_count'] : 5).","
			."index_panel='".SQL::escape(isset($fields['index_panel']) ? $fields['index_panel'] : 'main')."',"
			."index_title='".SQL::escape(isset($fields['index_title']) ? $fields['index_title'] : '')."',"
			."introduction='".SQL::escape(isset($fields['introduction']) ? $fields['introduction'] : '')."',"
			."language='".SQL::escape(isset($fields['language']) ? $fields['language'] : '')."',"
			."locked='".SQL::escape(isset($fields['locked']) ? $fields['locked'] : 'N')."',"
			."meta='".SQL::escape(isset($fields['meta']) ? $fields['meta'] : '')."',"
			."nick_name='".SQL::escape(isset($fields['nick_name']) ? $fields['nick_name'] : '')."',"
			."options='".SQL::escape(isset($fields['options']) ? $fields['options'] : '')."',"
			."overlay='".SQL::escape(isset($fields['overlay']) ? $fields['overlay'] : '')."',"
			."overlay_id='".SQL::escape(isset($fields['overlay_id']) ? $fields['overlay_id'] : '')."',"
			."prefix='".SQL::escape(isset($fields['prefix']) ? $fields['prefix'] : '')."',"
			."rank='".SQL::escape(isset($fields['rank']) ? $fields['rank'] : 10000)."',"
			."section_overlay='".SQL::escape(isset($fields['section_overlay']) ? $fields['section_overlay'] : '')."',"
			."sections_count=".SQL::escape(isset($fields['sections_count']) ? $fields['sections_count'] : 5).","
			."sections_layout='".SQL::escape(isset($fields['sections_layout']) ? $fields['sections_layout'] : 'map')."',"
			."suffix='".SQL::escape(isset($fields['suffix']) ? $fields['suffix'] : '')."',"
			."template='".SQL::escape(isset($fields['template']) ? $fields['template'] : '')."',"
			."thumbnail_url='".SQL::escape(isset($fields['thumbnail_url']) ? $fields['thumbnail_url'] : '')."',"
			."title='".SQL::escape(isset($fields['title']) ? $fields['title'] : '')."',"
			."trailer='".SQL::escape(isset($fields['trailer']) ? $fields['trailer'] : '')."'";

		// actual insert
		if(SQL::query($query) === FALSE)
			return FALSE;

		// remember the id of the new item
		$id = SQL::get_last_id($context['connection']);

		// clear the cache
		Cache::clear(array('sections', 'categories'));

		// return the id of the new item
		return $id;
	}

	/**
	 * put an updated section in the database
	 *
	 * @param array an array of fields
	 * @return string either a null string, or some text describing an error to be inserted into the html response
	 *
	 * @see sections/edit.php
	**/
	function put($fields) {
		global $context;

		// id cannot be empty
		if(!isset($fields['id']) || !is_numeric($fields['id']))
			return i18n::s('No item has the provided id.');

		// title cannot be empty
		if(!isset($fields['title']) || !trim($fields['title']))
			return i18n::s('No title has been provided.');

		// protect from hackers
		if(isset($fields['bullet_url']))
			$fields['bullet_url'] = preg_replace(FORBIDDEN_CHARS_IN_URLS, '_', $fields['bullet_url']);
		if(isset($fields['icon_url']))
			$fields['icon_url'] = preg_replace(FORBIDDEN_CHARS_IN_URLS, '_', $fields['icon_url']);
		if(isset($fields['thumbnail_url']))
			$fields['thumbnail_url'] = preg_replace(FORBIDDEN_CHARS_IN_URLS, '_', $fields['thumbnail_url']);

		// set default values for this editor
		$fields = Surfer::check_default_editor($fields);

		// reinforce date formats
		if(!isset($fields['activation_date']) || ($fields['activation_date'] <= NULL_DATE))
			$fields['activation_date'] = NULL_DATE;
		if(!isset($fields['expiry_date']) || ($fields['expiry_date'] <= NULL_DATE))
			$fields['expiry_date'] = NULL_DATE;
		if(!isset($fields['publish_date']) || ($fields['publish_date'] <= NULL_DATE))
			$fields['publish_date'] = NULL_DATE;

		// set conservative default values
		if(!isset($fields['active_set']))
			$fields['active_set'] = 'Y';
		if(isset($fields['edit_action']))
			$fields['edit_action'] = preg_replace('/import$/i', 'update', $fields['edit_action']);
		if(!isset($fields['home_panel']) || !$fields['home_panel'])
			$fields['home_panel'] = 'main';
		if(!isset($fields['index_map']) || !$fields['index_map'])
			$fields['index_map'] = 'Y';
		if(!isset($fields['index_news']) || !$fields['index_news'])
			$fields['index_news'] = 'static';
		if(!isset($fields['index_panel']) || !$fields['index_panel'])
			$fields['index_panel'] = 'main';
		if(!isset($fields['rank']) || !$fields['rank'])
			$fields['rank'] = 10000;

		// set layout for sections
		if(!isset($fields['sections_layout']) || !$fields['sections_layout'] || !preg_match('/(compact|custom|decorated|folded|freemind|inline|jive|map|titles|yabb|none)/', $fields['sections_layout']))
			$fields['sections_layout'] = 'map';
		elseif($fields['sections_layout'] == 'custom') {
			if(isset($fields['sections_custom_layout']) && $fields['sections_custom_layout'])
				$fields['sections_layout'] = $fields['sections_custom_layout'];
			else
				$fields['sections_layout'] = 'map';
		}

		// set layout for articles
		if(!isset($fields['articles_layout']) || !$fields['articles_layout'] || !preg_match('/(alistapart|boxesandarrows|compact|custom|daily|decorated|digg|jive|manual|map|none|slashdot|table|threads|wiki|yabb)/', $fields['articles_layout']))
			$fields['articles_layout'] = 'decorated';
		elseif($fields['articles_layout'] == 'custom') {
			if(isset($fields['articles_custom_layout']) && $fields['articles_custom_layout'])
				$fields['articles_layout'] = $fields['articles_custom_layout'];
			else
				$fields['articles_layout'] = 'decorated';
		}

		// cascade anchor access rights
		if(isset($fields['anchor']) && ($anchor = Anchors::get($fields['anchor'])))
			$fields['active'] = $anchor->ceil_rights($fields['active_set']);
		else
			$fields['active'] = $fields['active_set'];

		// update an existing record
		$query = "UPDATE ".SQL::table_name('sections')." SET ";

		// fields that are visible only to associates
		if(Surfer::is_associate())
			$query .= "anchor='".SQL::escape($fields['anchor'])."',"
				."home_panel='".SQL::escape(isset($fields['home_panel']) ? $fields['home_panel'] : 'main')."',";

		// fields also visible to editors
		$query .= "title='".SQL::escape($fields['title'])."',"
			."activation_date='".SQL::escape($fields['activation_date'])."',"
			."active='".SQL::escape($fields['active'])."',"
			."active_set='".SQL::escape($fields['active_set'])."',"
			."articles_layout='".SQL::escape(isset($fields['articles_layout']) ? $fields['articles_layout'] : 'decorated')."',"
			."articles_templates='".SQL::escape(isset($fields['articles_templates']) ? $fields['articles_templates'] : '')."',"
			."behaviors='".SQL::escape(isset($fields['behaviors']) ? $fields['behaviors'] : '')."',"
			."bullet_url='".SQL::escape(isset($fields['bullet_url']) ? $fields['bullet_url'] : '')."',"
			."content_overlay='".SQL::escape(isset($fields['content_overlay']) ? $fields['content_overlay'] : '')."',"
			."content_options='".SQL::escape(isset($fields['content_options']) ? $fields['content_options'] : '')."',"
			."expiry_date='".SQL::escape($fields['expiry_date'])."',"
			."extra='".SQL::escape(isset($fields['extra']) ? $fields['extra'] : '')."',"
			."family='".SQL::escape($fields['family'])."',"
			."icon_url='".SQL::escape(isset($fields['icon_url']) ? $fields['icon_url'] : '')."',"
			."index_map='".SQL::escape(isset($fields['index_map']) ? $fields['index_map'] : 'Y')."',"
			."index_news='".SQL::escape(isset($fields['index_news']) ? $fields['index_news'] : 'static')."',"
			."index_news_count=".SQL::escape(isset($fields['index_news_count']) ? $fields['index_news_count'] : 5).","
			."index_panel='".SQL::escape(isset($fields['index_panel']) ? $fields['index_panel'] : 'main')."',"
			."index_title='".SQL::escape(isset($fields['index_title']) ? $fields['index_title'] : '')."',"
			."introduction='".SQL::escape(isset($fields['introduction']) ? $fields['introduction'] : '')."',"
			."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."',"
			."nick_name='".SQL::escape(isset($fields['nick_name']) ? $fields['nick_name'] : '')."',"
			."language='".SQL::escape(isset($fields['language']) ? $fields['language'] : '')."',"
			."locked='".SQL::escape(isset($fields['locked']) ? $fields['locked'] : 'N')."',"
			."meta='".SQL::escape(isset($fields['meta']) ? $fields['meta'] : '')."',"
			."options='".SQL::escape(isset($fields['options']) ? $fields['options'] : '')."',"
			."overlay='".SQL::escape(isset($fields['overlay']) ? $fields['overlay'] : '')."',"
			."overlay_id='".SQL::escape(isset($fields['overlay_id']) ? $fields['overlay_id'] : '')."',"
			."prefix='".SQL::escape(isset($fields['prefix']) ? $fields['prefix'] : '')."',"
			."rank='".SQL::escape($fields['rank'])."',"
			."section_overlay='".SQL::escape(isset($fields['section_overlay']) ? $fields['section_overlay'] : '')."',"
			."sections_count='".SQL::escape(isset($fields['sections_count']) ? $fields['sections_count'] : 5)."',"
			."sections_layout='".SQL::escape(isset($fields['sections_layout']) ? $fields['sections_layout'] : 'map')."',"
			."suffix='".SQL::escape(isset($fields['suffix']) ? $fields['suffix'] : '')."',"
			."thumbnail_url='".SQL::escape(isset($fields['thumbnail_url']) ? $fields['thumbnail_url'] : '')."',"
			."trailer='".SQL::escape(isset($fields['trailer']) ? $fields['trailer'] : '')."'";

		// don't stamp silent updates
		if(!isset($fields['silent']) || ($fields['silent'] != 'Y')) {
			$query .= ",\n"
				."edit_name='".SQL::escape($fields['edit_name'])."',"
				."edit_id='".SQL::escape($fields['edit_id'])."',"
				."edit_address='".SQL::escape($fields['edit_address'])."',"
				."edit_action='section:update',"
				."edit_date='".SQL::escape($fields['edit_date'])."'";
		}

		// actual update query
		$query .= " WHERE id = ".SQL::escape($fields['id']);
		SQL::query($query);

		// clear the cache
		Cache::clear(array('section:'.$fields['id'], 'sections', 'categories'));

		// end of job
		return NULL;
	}

	/**
	 * change the template of a section
	 *
	 * This function saves the template as an attribute of the section.
	 *
	 * Also, it attempts to translate it as a valid YACS skin made
	 * of [code]template.php[/code] and [code]skin.php[/code].
	 * The skin name is [code]section_&lt;id&gt;[/code].
	 *
	 * Lastly, it updates the options field to actually use the template for pages of this section.
	 *
	 * @param int the id of the target section
	 * @param string the new or updated template
	 * @return string either a null string, or some text describing an error to be inserted into the html response
	 *
	 * @see services/blog.php
	 * @see skins/import.php
	**/
	function put_template($id, $template, $directory=NULL) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return i18n::s('No item has the provided id.');

		// load section attributes
		if(!$item =& Sections::get($id))
			return sprintf(i18n::s('Unknown section %s'), $id);

		// locate the new skin
		if(!$directory)
			$directory = 'section_'.$id;

		// make a valid YACS skin
		include_once $context['path_to_root'].'skins/import.php';
		if($error = Import::process($template, $directory))
			return $error;

		// change the skin for this section
		$options = preg_replace('/\bskin_.+\b/i', '', $item['options']).' skin_'.$directory;

		// set default values for this editor
		$fields = Surfer::check_default_editor(array());

		// update an existing record
		$query = "UPDATE ".SQL::table_name('sections')." SET "
			."template='".SQL::escape($template)."',\n"
			."options='".SQL::escape($options)."',\n"
			."edit_name='".SQL::escape($fields['edit_name'])."',\n"
			."edit_id='".SQL::escape($fields['edit_id'])."',\n"
			."edit_address='".SQL::escape($fields['edit_address'])."',\n"
			."edit_action='section:update',\n"
			."edit_date='".SQL::escape($fields['edit_date'])."'\n"
			."	WHERE id = ".SQL::escape($id);
		SQL::query($query);

		// clear the cache because of the new rendering
		Cache::clear(array('sections', 'section:'.$id, 'categories'));

	}

	/**
	 * search for some keywords in all sections
	 *
	 * Only sections matching following criteria are returned:
	 * - section is visible (active='Y')
	 * - section is restricted (active='R'), but the surfer is an authenticated member,
	 * or YACS is allowed to show restricted teasers
	 * - section is hidden (active='N'), but surfer is an associate
	 * - an expiry date has not been defined, or is not yet passed
	 *
	 * @param the search string
	 * @param int the offset from the start of the list; usually, 0 or 1 - default is 0
	 * @param int the number of items to display - default is 10
	 * @param string the list variant, if any - default is 'full'
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see search.php
	 */
	function &search($pattern, $offset=0, $count=50, $variant='full') {
		global $context;

		// limit the scope of the request
		$where = "sections.active='Y'";

		// add restricted items to members, or if teasers are allowed
		if(Surfer::is_logged() || !isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			$where .= " OR sections.active='R'";

		if(Surfer::is_associate())
			$where .= " OR sections.active='N'";

		// only consider live sections
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where = "(".$where.")"
			." AND ((sections.activation_date is NULL)"
			."	OR (sections.activation_date <= '".$now."'))"
			." AND ((sections.expiry_date is NULL)"
			."	OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$now."'))";

		// match
		$match = '';
		$words = preg_split('/\s/', $pattern);
		while($word = each($words)) {
			if($match)
				$match .= ' AND ';
			$match .=  "MATCH(title, introduction, description) AGAINST('".SQL::escape($word['value'])."')";
		}

		// list sections
		$query = "SELECT sections.*"
			." FROM ".SQL::table_name('sections')." AS sections"
			." WHERE (".$where.") AND ".$match
			." ORDER BY sections.edit_date DESC"
			." LIMIT ".$offset.','.$count;

		$output =& Sections::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * set the hits counter - errors are not reported, if any
	 *
	 * Hits are aiming to track service usage of anonymous and of authenticated users.
	 * Normally this function is not called is the surfer is either an associates or an editor for this section.
	 *
	 * @param the id of the section to update
	 *
	 * @see sections/view.php
	 */
	function increment_hits($id) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return;

		// do the job
		$query = "UPDATE ".SQL::table_name('sections')." SET hits=hits+1 WHERE id LIKE '".SQL::escape($id)."'";
		SQL::query($query);

	}

	/**
	 * create tables for sections
	 *
	 * @see control/populate.php
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['activation_date']	= "DATETIME";
		$fields['active']		= "ENUM('Y','R','N') DEFAULT 'Y' NOT NULL"; 				// Yes, Restricted or No
		$fields['active_set']	= "ENUM('Y','R','N') DEFAULT 'Y' NOT NULL"; 				// set locally
		$fields['anchor']		= "VARCHAR(64)";											// up to 64 chars
		$fields['articles_layout']	= "VARCHAR(255) DEFAULT 'decorated' NOT NULL";			// 'decorated', 'boxesandarrows', 'jive', 'manual', 'yabb' or 'none'
		$fields['articles_templates']	= "VARCHAR(255) DEFAULT '' NOT NULL";				// to use an article as a model
		$fields['behaviors']	= "TEXT NOT NULL";											// up to 64k chars
		$fields['bullet_url']	= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
		$fields['content_options']	= "VARCHAR(255) DEFAULT '' NOT NULL";					// up to 255 chars
		$fields['content_overlay']	= "VARCHAR(64) DEFAULT '' NOT NULL";					// up to 32 chars
		$fields['create_address']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['create_date']	= "DATETIME";
		$fields['create_id']	= "MEDIUMINT UNSIGNED DEFAULT '1' NOT NULL";
		$fields['create_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";						// item creation
		$fields['edit_action']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";
		$fields['edit_id']		= "MEDIUMINT UNSIGNED DEFAULT '1' NOT NULL";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";						// item modification
		$fields['expiry_date']	= "DATETIME";												// expiry date
		$fields['extra']		= "TEXT NOT NULL";											// up to 64k chars
		$fields['family']		= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
		$fields['icon_url'] 	= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
		$fields['index_title']	= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
		$fields['introduction'] = "TEXT NOT NULL";											// up to 64k chars
		$fields['description']	= "TEXT NOT NULL";											// up to 64k chars
		$fields['hits'] 		= "INT UNSIGNED DEFAULT '0' NOT NULL";						// counter
		$fields['home_panel']	= "VARCHAR(255) DEFAULT 'main' NOT NULL";					// 'main', 'news', 'extra', 'extra_boxes', 'gadget', 'gadget_boxes', 'icon' or 'none'
		$fields['index_map']	= "ENUM('Y', 'N') DEFAULT 'Y' NOT NULL";					// Yes or No
		$fields['index_news']	= "VARCHAR(255) DEFAULT 'static' NOT NULL"; 				// 'static', 'scroll', 'rotate' or 'none'
		$fields['index_news_count'] = "SMALLINT UNSIGNED DEFAULT '5' NOT NULL";
		$fields['index_panel']	= "VARCHAR(255) DEFAULT 'main' NOT NULL";					// 'main', 'news', 'extra', 'extra_boxes', 'gadget', 'gadget_boxes', 'icon' or 'none'
		$fields['language'] 	= "VARCHAR(64) DEFAULT '' NOT NULL";						// up to 64 chars
		$fields['locked']		= "ENUM('Y', 'N') DEFAULT 'N' NOT NULL";					// Yes or No
		$fields['maximum_items']	= "MEDIUMINT UNSIGNED";
		$fields['meta'] 		= "TEXT NOT NULL";											// up to 64k chars
		$fields['nick_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";						// up to 128 chars
		$fields['options']		= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
		$fields['overlay']		= "TEXT NOT NULL";											// up to 64k chars
		$fields['overlay_id']	= "VARCHAR(128) DEFAULT '' NOT NULL";						// to find the page by its overlay
		$fields['prefix']		= "TEXT NOT NULL";											// up to 64k chars
		$fields['rank'] 		= "MEDIUMINT UNSIGNED DEFAULT '10000' NOT NULL";			// 1 to 64k
		$fields['section_overlay']	= "VARCHAR(64) DEFAULT '' NOT NULL";					// up to 32 chars
		$fields['sections_count']	= "SMALLINT UNSIGNED DEFAULT '5' NOT NULL"; 			// number of sections at sections/view.php
		$fields['sections_layout']	= "VARCHAR(255) DEFAULT 'map' NOT NULL";				// 'menu', 'map', 'inline' or 'none' or custom
		$fields['suffix']		= "TEXT NOT NULL";											// up to 64k chars
		$fields['template'] 	= "TEXT NOT NULL";											// up to 64k chars
		$fields['thumbnail_url']= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
		$fields['title']		= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
		$fields['trailer']		= "TEXT NOT NULL";											// up to 64k chars

		$indexes = array();
		$indexes['PRIMARY KEY'] 		= "(id)";
		$indexes['INDEX anchor']		= "(anchor)";
		$indexes['INDEX nick_name'] 	= "(nick_name)";
		$indexes['INDEX title'] 		= "(title(255))";
		$indexes['INDEX rank']			= "(rank)";
		$indexes['INDEX activation_date'] = "(activation_date)";
		$indexes['INDEX active']		= "(active)";
		$indexes['INDEX hits']			= "(hits)";
		$indexes['INDEX home_panel']	= "(home_panel)";
		$indexes['INDEX index_map'] 	= "(index_map)";
		$indexes['INDEX index_panel']	= "(index_panel)";
		$indexes['INDEX language']		= "(language)";
		$indexes['INDEX locked']		= "(locked)";
		$indexes['INDEX create_name']	= "(create_name)";
		$indexes['INDEX create_id'] 	= "(create_id)";
		$indexes['INDEX create_date']	= "(create_date)";
		$indexes['INDEX edit_name'] 	= "(edit_name)";
		$indexes['INDEX edit_id']		= "(edit_id)";
		$indexes['INDEX edit_date'] 	= "(edit_date)";
		$indexes['INDEX expiry_date']	= "(expiry_date)";
		$indexes['FULLTEXT INDEX']		= "full_text(title, introduction, description)";

		return SQL::setup_table('sections', $fields, $indexes);
	}

	/**
	 * get some statistics for some sections
	 *
	 * Only sections matching following criteria are returned:
	 * - section is visible (active='Y')
	 * - section is restricted (active='R'), but surfer is a logged user
	 * - section is hidden (active='N'), but surfer is an associate
	 *
	 * Non-activated and expired sections are counted as well.
	 *
	 * @param string the selected anchor (e.g., 'section:12')
	 * @return array the resulting ($count, $min_date, $max_date) array
	 *
	 * @see sections/delete.php
	 * @see sections/index.php
	 * @see sections/layout_sections.php
	 * @see sections/layout_sections_as_boxesandarrows.php
	 * @see sections/layout_sections_as_yahoo.php
	 * @see sections/view.php
	 */
	function &stat_for_anchor($anchor = '') {
		global $context;

		// profiling mode
		if($context['with_profile'] == 'Y')
			logger::profile('sections::stat_for_anchor');

		// limit the query to one level
		if($anchor)
			$where = "(sections.anchor LIKE '".SQL::escape($anchor)."')";
		else
			$where = "(sections.anchor='' OR sections.anchor is NULL)";

		// show everything if we are about to suppress a section
		if(!preg_match('/delete\.php/', $context['script_url'])) {

			// display active and restricted items
			$where .= "AND (sections.active='Y'";

			// list restricted sections to authenticated surfers
			if(Surfer::is_logged())
				$where .= " OR sections.active='R'";

			// list hidden sections to associates, editors and readers
			if(Surfer::is_empowered('S'))
				$where .= " OR sections.active='N'";

			$where .= ")";

			// hide sections removed from index maps
			$where .= " AND ((sections.index_map IS NULL) OR (sections.index_map != 'N'))";

			// non-associates will have only live sections
			$now = gmstrftime('%Y-%m-%d %H:%M:%S');
			if($anchor && !Surfer::is_empowered()) {
				$where .= " AND ((sections.activation_date is NULL)"
					."	OR (sections.activation_date <= '".$now."'))"
					." AND ((sections.expiry_date is NULL)"
					."	OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$now."'))";
			}
		}

		// list sections
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date"
			." FROM ".SQL::table_name('sections')." AS sections"
			." WHERE ".$where;

		$output =& SQL::query_first($query);
		return $output;
	}

}

// ensure this library has been fully localized
i18n::bind('sections');

?>