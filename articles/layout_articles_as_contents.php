<?php
/**
 * layout articles as a feed, with full content
 *
 * @see articles/articles.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_contents extends Layout_interface {

	/**
	 * list articles
	 *
	 * @param resource the SQL result
	 * @return array, or NULL
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// load localized strings
		i18n::bind('articles');

		// process all items in the list
		include_once $context['path_to_root'].'articles/article.php';
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay
			$overlay = Overlay::load($item);

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// the url to view this item
			$url = Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']);

			// reset the rendering engine between items
			Codes::initialize($url);

			// provide an absolute link
			$url = $context['url_to_home'].$context['url_to_root'].$url;

			// time of last update
			$time = strtotime($item['edit_date']).' UTC';

			// build a title
			if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
				$title = $overlay->get_live_title($item);
			else
				$title = Codes::beautify_title($item['title']);

			// the section
			$section = '';
			if($item['anchor'] && ($anchor = Anchors::get($item['anchor'])))
				$section = ucfirst($anchor->get_title());

			// the icon to use
			$icon = '';
			if($item['thumbnail_url'])
				$icon = $item['thumbnail_url'];
			elseif($item['anchor'] && ($anchor = Anchors::get($item['anchor'])))
				$icon = $anchor->get_thumbnail_url();
			if($icon)
				$icon = $context['url_to_home'].$context['url_to_home'].$icon;

			// the author(s) is an e-mail address, according to rss 2.0 spec
			$author = '';
			if(isset($item['create_address']))
				$author .= $item['create_address'];
			if(isset($item['create_name']) && trim($item['create_name']))
				$author .= ' ('.$item['create_name'].')';
			if(isset($item['edit_address']) && trim($item['edit_address']) && ($item['create_address'] != $item['edit_address'])) {
				if($author)
					$author .= ', ';
				$author .= $item['edit_address'];
				if(isset($item['edit_name']) && trim($item['edit_name']))
					$author .= ' ('.$item['edit_name'].')';
			}

			// some introductory text for this article --this is not related to the description field
			$article =& new Article();
			$article->load_by_content($item);
			$introduction = $article->get_teaser('teaser');

			// warns on restricted access
			if($item['active'] != 'Y')
				$introduction = '['.i18n::c('Restricted to members').'] '.$introduction;

			// the article content
			$description = '';

			if($item['introduction'])
				$description .= Skin::build_block(Codes::beautify(preg_replace(FORBIDDEN_CODES_IN_TEASERS, '', $item['introduction'])), 'introduction');
			$description .= Codes::beautify(preg_replace(FORBIDDEN_CODES_IN_TEASERS, '', $item['description']), $item['options']);

			// cap the number of words
//			$description = Skin::cap($description, 300);

			// fix references
			$description = preg_replace('/"\//', '"'.$context['url_to_home'].'/', $description);

			// other rss fields
			$extensions = array();

			// url for comments
			$extensions[] = '<comments>'.$url.'#comments</comments>';

			// count comments
			$comment_count = Comments::count_for_anchor('article:'.$item['id']);
			$extensions[] = '<slash:comments>'.$comment_count."</slash:comments>";

			// the comment post url
			$extensions[] = '<wfw:comment>'.$context['url_to_home'].$context['url_to_root'].Comments::get_url('article:'.$item['id'], 'service.comment')."</wfw:comment>";

			// the comment Rss url
			$extensions[] = '<wfw:commentRss>'.$context['url_to_home'].$context['url_to_root'].Comments::get_url('article:'.$item['id'], 'feed')."</wfw:commentRss>";

			// the trackback url
			$extensions[] = '<trackback:ping>'.$context['url_to_home'].$context['url_to_root'].'links/trackback.php?anchor=article:'.$item['id']."</trackback:ping>"; // no trackback:about;

			// list all components for this item
			$items[$url] = array($time, $title, $author, $section, $icon, $introduction, $description, $extensions);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>