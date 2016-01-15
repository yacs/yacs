<?php
/**
 * layout as a set of titles with thumbnails
 *
 * @see sections/sections.php
 *
 * @author Bernard Paques
 * @author Thierry Pinelli [email]contact@vdp-digital.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_as_titles extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * The compact format of this layout allows a high number of items to be listed
	 *
	 * @return int the optimised count of items fro this layout
	 */
	function items_per_page() {
		return 1000;
	}

	/**
	 * list sections
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see layouts/layout.php
	**/
	function layout($result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;
                
                if(!defined('YAHOO_LIST_SIZE')) define('YAHOO_LIST_SIZE', 7);
                if(!$maximum_items = $this->has_variant('max_subitems')) {
                    $maximum_items = YAHOO_LIST_SIZE;
                }

		// clear flows
		$text .= '<br style="clear: left" />';

		// process all items in the list
		$family = '';
		while($item = SQL::fetch($result)) {

			// change the family
			if(isset($item['family']) && $item['family'] != $family) {
				$family = $item['family'];

				$text .= '<h2><span>'.$family.'&nbsp;</span></h2>'."\n";
			}

                        // get object interface
                        $entity     = new $this->listed_type($item);
                        
			// get the anchor
			$anchor     = $entity->anchor;
                        
                        $overlay    = $entity->overlay;

			// the url to view this item
			$url        = $entity->get_permalink();

			// initialize variables
			$prefix = $label = $suffix = $icon = '';

			// signal restricted and private sections
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// flag sections that are draft, dead, or created or updated very recently

			if(isset($item['activation_date']) && $item['activation_date'] >= $context['now'])
				$prefix .= DRAFT_FLAG;
			elseif(isset($item['expiry_date']) && ($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$prefix .= EXPIRED_FLAG;
			elseif($item['create_date'] >= $context['fresh'])
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix .= UPDATED_FLAG;
                        elseif(isset($item['publish_date']) && (($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S'))))
                                $prefix .= DRAFT_FLAG;

                        if(!$hover = strip_tags(Codes::beautify_introduction($entity->get_introduction()))){
                            $hover = i18n::s('View the page');
                        }

			// details and content
			$details = array();
			$content = array();

			// count related sub-elements
                        $related_count = 0;
                        $subcat = $relsec = $subsec = $relart = $subart = $relusr = $rellink = $relcmt = $relfile = 0;

                        if($this->listed_type == 'category') {

                            // info on sub categories
                            $stats = Categories::stat_for_anchor('category:'.$item['id']);
                            if($subcat = $stats['count'])
                                    $details[] = sprintf(i18n::ns('%d category', '%d categories', $subcat), $subcat);
                            $related_count += $subcat;

                            // info on related sections
                            if($relsec = Members::count_sections_for_anchor('category:'.$item['id'])) {
                                    $details[] = sprintf(i18n::ns('%d section', '%d sections', $relsec), $relsec);
                                    $related_count += $relsec;
                            }

                            // info on related articles
                            if($relart = Members::count_articles_for_anchor('category:'.$item['id'])) {
                                    $details[] = sprintf(i18n::ns('%d page', '%d pages', $relart), $relart);
                                    $related_count += $relart;
                            }

                            // info on related users
                            if($relusr = Members::count_users_for_anchor('category:'.$item['id'])) {
                                    $details[] = sprintf(i18n::ns('%d user', '%d users', $relusr), $relusr);
                                    $related_count += $relusr;
                            }

                        }
                        
                        // info on related articles (in sections)
                        if($subart = Articles::count_for_anchor($entity)) {
                                $details[] = sprintf(i18n::ns('%d page', '%d pages', $subart), $subart);
                                $related_count += $subart;
                        }

                        // info on related links
                        if($rellink = Links::count_for_anchor($entity, TRUE)) {
                                $details[] = sprintf(i18n::ns('%d link', '%d links', $rellink), $rellink);
                                $related_count += $rellink;
                        }

                        // info on related comments
                        if($relcmt = Comments::count_for_anchor($entity, TRUE)) {
                                $details[] = sprintf(i18n::ns('%d comment', '%d comments', $relcmt), $relcmt);
                        }

                        // info on related files
                        if($relfile = Files::count_for_anchor($entity, TRUE)) {
                                $details[] = sprintf(i18n::ns('%d file', '%d files', $relfile), $relfile);
                                $related_count += $relfile;
                        }

                        // rank
                        if(($item['rank'] != 10000) && (Surfer::is_associate() || $entity->is_assigned()))
                                $details[] = '{'.$item['rank'].'}';
                        
                        // new or updated flag
			if($suffix)
				$details[] = $suffix;

			$yet_to_list = $maximum_items;
                    
                        // add sub-sections
                        if($subsec = min($subsec,$yet_to_list)) {
                            $related = Sections::list_by_title_for_anchor($entity, 0, $subsec, 'compact');
                            self::layout_related($related, 'section', $content);

                            $yet_to_list -= $subsec;
                        }

                        // add sub-articles
                        if($subart = min($subart,$yet_to_list)) {
                            if(!$order = $entity->has_option('articles_by')) $order = 'edition';
                            $related = Articles::list_for_anchor_by($order, $entity, 0, $subart, 'compact');
                            self::layout_related($related, 'article', $content);

                            $yet_to_list -= $subart;
                        }

                        // add sub-categories
                        if($subcat = min($subcat,$yet_to_list)) {
                            $related = Categories::list_by_date_for_anchor($entity, 0, $subcat, 'compact');
                            self::layout_related($related, 'category', $content);

                            $yet_to_list -= $subcat;
                        }

                        // add related sections
                        if($relsec = min($relsec, $yet_to_list)) {
                            $related = Members::list_sections_by_title_for_anchor($entity, 0, $relsec, 'compact');
                            self::layout_related($related, 'section', $content);

                            $yet_to_list -= $relsec;
                        }

                        // add related articles
                        if($relart = min($relart, $yet_to_list)) {
                            $related = Members::list_articles_by_date_for_anchor($entity, 0, $relart, 'compact');
                            self::layout_related($related, 'article', $content);

                            $yet_to_list -= $relart;
                        }

                        // add related users
                        if($relusr = min($relusr, $yet_to_list)) {
                            $related = Members::list_users_by_name_for_anchor($entity, 0, $relusr, 'compact');
                            self::layout_related($related, 'user', $content);

                            $yet_to_list -= $relusr;
                        }

                        // info on related files
                        if($relfile = min($relfile, $yet_to_list)) {
                            $related = Files::list_by_date_for_anchor($entity, 0, $relfile, 'compact');
                            self::layout_related($related, 'file', $content);

                            $yet_to_list -= $relfile;
                        }

                        // info on related links
                        if($rellink = min($rellink, $yet_to_list)) {
                            $related = Links::list_by_date_for_anchor($entity, 0, $rellink, 'compact');
                            self::layout_related($related, 'link', $content);

                            $yet_to_list -= $rellink;
                        }

			// give me more
			if(!$yet_to_list && $related_count > $maximum_items) {
                            $content[] = Skin::build_link($url, i18n::s('More').MORE_IMG, 'more', i18n::s('View the page'));
                        }

			// layout details
			if(count($content)) {
				$hover .= '<ul><li>'.implode('</li><li>', $content).'</li></ul>';
			}

			// add a link to the main page
			if(!$hover)
				$hover = i18n::s('View the page');

			// use the title to label the link
			$title = Skin::strip($entity->get_title(), 50);
                        
                        // append details
			if(count($details))
				$title .= BR.'<span class="details">'.implode(', ', $details).'</span>';

			// look for an image
			$icon = '';
			if(isset($item['thumbnail_url']) && $item['thumbnail_url'])
				$icon = $item['thumbnail_url'];
			elseif(is_callable(array($anchor, 'get_bullet_url')))
				$icon = $anchor->get_bullet_url();

			// use the thumbnail for this section
			if($icon) {

				// fix relative path
				if(!preg_match('/^(\/|http:|https:|ftp:)/', $icon))
					$icon = $context['url_to_root'].$icon;

				// use parameter of the control panel for this one
				$options = '';
				if(isset($context['classes_for_thumbnail_images']))
					$options = 'class="'.$context['classes_for_thumbnail_images'].'" ';

				// build the complete HTML element
				$icon = '<img src="'.$icon.'" alt="" title="" '.$options.' />';

			// use default icon if nothing to display
			} else
				$icon = MAP_IMG;

			// use tipsy on hover
			$content = '<a href="'.$url.'" id="titles_'.$item['id'].'">'.$icon.BR.$prefix.$title.'</a>';
				
			Page::insert_script(
				'$(function() {'."\n"
				.'	$("a#titles_'.$item['id'].'").each(function() {'."\n"
				.'		$(this).tipsy({fallback: \'<div style="text-align: left;">'.str_replace(array("'", "\n"), array('"', '<br />'), $hover).'</div>\','."\n"
				.	'		 html: true,'."\n"
				.	'		 gravity: $.fn.tipsy.autoWE,'."\n"
				.	'		 fade: true,'."\n"
				.	'		 offset: 8,'."\n"
				.	'		 opacity: 1.0});'."\n"
				.'	});'."\n"
				.'});'."\n"
				);

			// add a floating box
			$text .= Skin::build_box(NULL, $content, 'floating');

		}

		// clear flows
		$text .= '<br style="clear: left" />';

		// end of processing
		SQL::free($result);
                
                $this->load_scripts_n_styles();
		return $text;
	}
        
        private static function layout_related($related, $type, &$details) {
            
            foreach($related as $sub_url => $label) {
                $sub_prefix = $sub_suffix = $sub_hover = '';
                if(is_array($label)) {
                        $sub_prefix = $label[0];
                        $sub_suffix = $label[2];
                        if(@$label[5])
                                $sub_hover = $label[5];
                        $label = $label[1];
                }
                                
            $details[] = $sub_prefix.Skin::build_link($sub_url, $label, $type, $sub_hover).$sub_suffix;
            }
        }

}
