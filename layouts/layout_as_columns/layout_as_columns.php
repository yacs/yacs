<?php

/**
 * Layout items as 2 columns
 * 
 * With this layout several items are listed as well.
 * for articles : these can be either files or links, depending of relative availability of both kind of items.
 * for sections : these can be either sub-sections and/or articles, depending of relative availability of both kind of items.
 * for categories : these can be either sub-categories and/or articles, depending of relative availability of both kind of items.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 * 
 */

Class Layout_as_columns extends Layout_interface {
    
    	/**
	 * list entities
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

                // the number of related items to display
		if(!defined('YAHOO_LIST_SIZE'))
			define('YAHOO_LIST_SIZE', 3);
                
                // maximum number of items
                if(!$maximum_items = $this->has_variant('max_subitems'))
                      $maximum_items = YAHOO_LIST_SIZE;
		
                $family     = ''; // to separate section by family
                $items      = array();
                while($item = SQL::fetch($result)) {
                    
                    // change the family
                    if(isset($item['family']) && $item['family'] != $family) {

                            // flush current stack, if any
                            if(count($items))
                                    $text .= Skin::build_list($items, '2-columns');
                            $items = array();

                            // show the family
                            $family = $item['family'];
                            $text .= '<h2><span>'.$family.'&nbsp;</span></h2>'."\n";

                    }
                    
                    $entity = new $this->listed_type($item); // get object interface
                    
                    $title  = Skin::strip($entity->get_title(),50);
                    
                    $url    = $entity->get_permalink();
                    
                    // initialize variables
                    $prefix = $label = $suffix = $icon = '';
                    
                    // flag entities that are dead, or created or updated very recently
                    if(isset($item['expiry_date']) && ($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
                            $prefix .= EXPIRED_FLAG;
                    elseif($item['create_date'] >= $context['fresh'])
                            $suffix .= NEW_FLAG;
                    elseif($item['edit_date'] >= $context['fresh'])
                            $suffix .= UPDATED_FLAG;
                    elseif(isset($item['activation_date']) && $item['activation_date'] >= $context['now'])
                            $prefix .= DRAFT_FLAG;
                    elseif(isset($item['publish_date']) && (($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S'))))
                            $prefix .= DRAFT_FLAG;


                    // signal restricted and private articles
                    if($item['active'] == 'N')
                            $prefix .= PRIVATE_FLAG;
                    elseif($item['active'] == 'R')
                            $prefix .= RESTRICTED_FLAG;
                    
                    // details
                    $details = array();

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
                    
                    // introduction
                    $introduction = $entity->get_introduction();
                    
                    if($introduction)
                        $suffix .= ' - '.Codes::beautify_introduction($introduction);
                    
                    // append details to the suffix
                    if(count($details))
                            $suffix .= ' <span '.tag::_class('details').'>('.implode(', ', $details).')</span>';
                    
                    // add a head list of related sub elements
                    $details = array();
                    $yet_to_list = $maximum_items;
                    
                    // add sub-sections
                    if($subsec = min($subsec,$yet_to_list)) {
                        $related = Sections::list_by_title_for_anchor($entity, 0, $subsec, 'compact');
                        self::layout_related($related, 'section', $details);
                        
                        $yet_to_list -= $subsec;
                    }
                    
                    // add sub-articles
                    if($subart = min($subart,$yet_to_list)) {
                        if(!$order = $entity->has_option('articles_by')) $order = 'edition';
                        $related = Articles::list_for_anchor_by($order, $entity, 0, $subart, 'compact');
                        self::layout_related($related, 'article', $details);
                         
                        $yet_to_list -= $subart;
                    }
                    
                    // add sub-categories
                    if($subcat = min($subcat,$yet_to_list)) {
                        $related = Categories::list_by_date_for_anchor($entity, 0, $subcat, 'compact');
                        self::layout_related($related, 'category', $details);
                        
                        $yet_to_list -= $subcat;
                    }
                    
                    // add related sections
                    if($relsec = min($relsec, $yet_to_list)) {
                        $related = Members::list_sections_by_title_for_anchor($entity, 0, $relsec, 'compact');
                        self::layout_related($related, 'section', $details);
                        
                        $yet_to_list -= $relsec;
                    }
                    
                    // add related articles
                    if($relart = min($relart, $yet_to_list)) {
                        $related = Members::list_articles_by_date_for_anchor($entity, 0, $relart, 'compact');
                        self::layout_related($related, 'article', $details);
                        
                        $yet_to_list -= $relart;
                    }
                    
                    // add related users
                    if($relusr = min($relusr, $yet_to_list)) {
                        $related = Members::list_users_by_name_for_anchor($entity, 0, $relusr, 'compact');
                        self::layout_related($related, 'user', $details);
                        
                        $yet_to_list -= $relusr;
                    }
                    
                    // info on related files
                    if($relfile = min($relfile, $yet_to_list)) {
                        $related = Files::list_by_date_for_anchor($entity, 0, $relfile, 'compact');
                        self::layout_related($related, 'file', $details);
                        
                        $yet_to_list -= $relfile;
                    }
                    
                    // info on related links
                    if($rellink = min($rellink, $yet_to_list)) {
                        $related = Links::list_by_date_for_anchor($entity, 0, $rellink, 'compact');
                        self::layout_related($related, 'link', $details);
                        
                        $yet_to_list -= $rellink;
                    }
                    
                    // give me more
                    if(!$yet_to_list && $related_count > $maximum_items) {
                        $details[] = Skin::build_link($url, i18n::s('More').MORE_IMG, 'more', i18n::s('View the page'));
                    }
                    
                    // layout details
                    if(count($details)) {
                         foreach($details as $line) {
                             $suffix .= '<div '.tag::_class('details').'>'.YAHOO_ITEM_PREFIX.$line.YAHOO_ITEM_SUFFIX.'</div>';
                         }
                    }
                    
                    // display all tags
                    if(isset($item['tags']) && $item['tags'])
                            $suffix .= tag::_('p', tag::_class('tags'), Skin::build_tags($item['tags']));
                    
                    // put the actual icon in the left column
                    if(isset($item['thumbnail_url']))
                            $icon = $item['thumbnail_url'];
                    
                    // some hovering title
                    $hover = i18n::s('View the page');
                    
                    // list all components for this item --use basic link style to avoid prefix or suffix images, if any
                    $items[$url] = array($prefix, $title, $suffix, 'basic', $icon, $hover);
                }
                
                // end of processing
		SQL::free($result);
		$text = Skin::build_list($items, '2-columns');
                
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

