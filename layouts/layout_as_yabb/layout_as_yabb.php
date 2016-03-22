<?php
/**
 * layout entites as boards in a yabb forum
 *
 * This script layouts sections as boards in a discussion forum.
 *
 * The title of each section is also a link to the section itself.
 * A title attribute of the link displays the reference to use to link to the page.
 *
 * Moderators are listed below each board, if any.
 * Moderators of a board are the members who have been explicitly assigned as editors of the related section.
 *
 * The script also lists children boards, if any.
 * This helps to provide a comprehensive view to forum surfers.
 * 
 * The title of each article is also a link to the article itself.
 * A title attribute of the link displays the reference to use to link to the page (Thanks to Anatoly).
 *
 * @see sections/view.php
 *
 * The initial development of YACS forum has been heavily inspired by YABB.
 *
 * @link http://www.yabbforum.com/ Yet Another Bulletin Board
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli [email]contact@vdp-digital.com[/email]
 * @author Alexis Raimbault
 * @tester Anatoly
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_as_yabb extends Layout_interface {
    
    
        /**
	 * the preferred number of items for this layout
	 *
	 * @return 50
	 *
	 * @see layouts/layout.php
	 */
	function items_per_page() {
		return 50;
	}
    

	/**
	 * list sections as topics in a forum
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	**/
	function layout($result) {
		global $context;

		// empty list
		if(!SQL::count($result))
			return $text;

		// output as a string
		$text = '';
                
                // page size for comments
                include_once $context['path_to_root'].'comments/layout_comments_as_updates.php';
                $layout = new Layout_comments_as_updates();

		// build a list of sections
		$family = '';
		$first = TRUE;
		while($item = SQL::fetch($result)) {

			// change the family
			if(isset($item['family']) && $item['family'] != $family) {
				$family = $item['family'];

				// close last table only if a section has been already listed
				if(!$first) {
				    $text .= Skin::table_suffix();
				}
				// show the family
				$text .= '<h2><span>'.$family.'&nbsp;</span></h2>'."\n"
					.Skin::table_prefix('yabb')
					.Skin::table_row(array(i18n::s('Board'), 'center='.i18n::s('Topics'), i18n::s('Last post')), 'header');
			} elseif($first) {
			    $text .= Skin::table_prefix('yabb');
			    $text .= Skin::table_row(array(i18n::s('Board'), 'center='.i18n::s('Topics'), i18n::s('Last post')), 'header');
			}
                        
                        $entity = new $this->listed_type($item);
                        
                        $overlay = $entity->overlay;

			// done with this case
			$first = FALSE;

			// reset everything
			$prefix = $label = $suffix = $icon = '';
                        
                        // signal articles to be published
			if(isset($item['publish_date']) && ( ($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S'))))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private sections
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// indicate the id in the hovering popup
                        if($this->listed_type == 'section')
                            $hover = i18n::s('View the section');
                        else
                            $hover = i18n::s('View the page');
                        
			if(Surfer::is_member())
				$hover .= ' ['.$entity.']';

			// the url to view this item
			$url = $entity->get_permalink($item);

			// use the title as a link to the page
			$title = Skin::build_link($url, Codes::beautify_title($entity->get_title()), 'basic', $hover);

			// also use a clickable thumbnail, if any
			if($this->listed_type == 'section' && $item['thumbnail_url'])
				$prefix = Skin::build_link($url, '<img src="'.$item['thumbnail_url'].'" alt="" title="'.encode_field($hover).'" class="left_image" />', 'basic', $hover)
					.$prefix;

			// flag sections updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$suffix = EXPIRED_FLAG.' ';
			elseif($item['create_date'] >= $context['fresh'])
				$suffix = NEW_FLAG.' ';
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix = UPDATED_FLAG.' ';
                        
                        // rating
			if(isset($item['rating_count']) && $item['rating_count'] && !$entity->has_option('without_rating'))
				$suffix .= ' '.Skin::build_link(Articles::get_url($item['id'], 'like'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// board introduction
                        $suffix .= BR.Codes::beautify_introduction($entity->get_introduction());
                        
                        // insert overlay data, if any
			if(is_object($overlay))
				$suffix .= $overlay->get_text('list', $item);
			

                        if($this->listed_type == 'section') {        
                            // more details
                            $details = '';
                            $more = array();

                            // board moderators
                            if($moderators = Sections::list_editors_by_name($item, 0, 7, 'comma5'))
                                    $more[] = sprintf(i18n::ns('Moderator: %s', 'Moderators: %s', count($moderators)), $moderators);

                            // children boards
                            if($children = Sections::list_by_title_for_anchor('section:'.$item['id'], 0, COMPACT_LIST_SIZE, 'comma'))
                                    $more[] = sprintf(i18n::ns('Child board: %s', 'Child boards: %s', count($children)), Skin::build_list($children, 'comma'));

                            // as a compact list
                            if(count($more)) {
                                    $details .= '<ul class="compact">';
                                    foreach($more as $list_item) {
                                            $details .= '<li>'.$list_item.'</li>'."\n";
                                    }
                                    $details .= '</ul>'."\n";
                            }

                            // all details
                            if($details)
                                    $details = BR.'<span class="details">'.$details."</span>\n";

                            // count posts here, and in children sections
                            $anchors = Sections::get_branch_at_anchor('section:'.$item['id']);
                            if(!$count = Articles::count_for_anchor($anchors))
                                    $count = 0;

                            // get last post
                            $last_post = '--';
                            $article = Articles::get_newest_for_anchor($anchors, TRUE);
                            if($article['id']) {

                                    // flag articles updated recently
                                    if(($article['expiry_date'] > NULL_DATE) && ($article['expiry_date'] <= $context['now']))
                                            $flag = EXPIRED_FLAG.' ';
                                    elseif($article['create_date'] >= $context['fresh'])
                                            $flag = NEW_FLAG.' ';
                                    elseif($article['edit_date'] >= $context['fresh'])
                                            $flag = UPDATED_FLAG.' ';
                                    else
                                            $flag = '';

                                    // title
                                    $last_post = Skin::build_link(Articles::get_permalink($article), Codes::beautify_title($article['title']), 'article');

                                    // last editor
                                    if($article['edit_date']) {

                                            // find a name, if any
                                            if($article['edit_name']) {

                                                    // label the action
                                                    if(isset($article['edit_action']))
                                                            $action = Anchors::get_action_label($article['edit_action']);
                                                    else
                                                            $action = i18n::s('edited');

                                                    // name of last editor
                                                    $user = sprintf(i18n::s('%s by %s'), $action, Users::get_link($article['edit_name'], $article['edit_address'], $article['edit_id']));
                                            }

                                            $last_post .= $flag.BR.'<span class="tiny">'.$user.' '.Skin::build_date($article['edit_date']).'</span>';
                                    }

                            }

                            // this is another row of the output
                            $text .= Skin::table_row(array($prefix.$title.$suffix.$details, 'center='.$count, $last_post));
                            
                        } else { // article
                            
                            // shortcuts to comments pages
                            if(isset($item['comments_count']) && ($pages = (integer)ceil($item['comments_count'] / $layout->items_per_page())) && ($pages > 1)) {
                                    $suffix .= '<p class="details">Pages ';
                                    for($index = 1; $index <= $pages; $index++)
                                            $suffix .= Skin::build_link('comments/list.php?id=article:'.$item['id'].'&amp;page='.$index, $index, 'basic', i18n::s('One page of comments')).' ';
                                    $suffix .= Skin::build_link('comments/list.php?id=article:'.$item['id'].'&amp;page='.$pages, MORE_IMG, 'basic', i18n::s('Most recent comments')).'</p>';
                            }

                            // links to sections and categories
                            $anchors = array();

                            // the main anchor link
                            if(is_object($anchor) && (!isset($this->focus) || ($item['anchor'] != $this->focus)))
                                    $anchors[] = Skin::build_link($anchor->get_url(), ucfirst($anchor->get_title()), 'basic', i18n::s('In this section'));


                            // list categories by title, if any
                            if($members = Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 7, 'raw')) {
                                    foreach($members as $category_id => $attributes) {

                                            // add background color to distinguish this category against others
                                            if(isset($attributes['background_color']) && $attributes['background_color'])
                                                    $attributes['title'] = '<span style="background-color: '.$attributes['background_color'].'; padding: 0 3px 0 3px;">'.$attributes['title'].'</span>';

                                            if(!isset($this->focus) || ($this->focus != 'category:'.$category_id))
                                                    $anchors[] = Skin::build_link(Categories::get_permalink($attributes), $attributes['title'], 'basic', i18n::s('Related topics'));
                                    }
                            }

                            // list section and categories in the suffix
                            if(@count($anchors))
                                    $suffix .= '<p class="tags">'.implode(' ', $anchors).'</p>';

                            // the creator of this article
                            $starter = '';
                            if($item['create_name']) {
                                    $starter = '<span class="details">'.Users::get_link($item['create_name'], $item['create_address'], $item['create_id']).'</span>';
                            }

                            // the last editor
                            $details = '';
                            if($item['edit_date']) {

                                    // find a name, if any
                                    $user = '';
                                    if($item['edit_name']) {

                                            // label the action
                                            if(isset($item['edit_action']))
                                                    $user .= Anchors::get_action_label($item['edit_action']).' ';

                                            // name of last editor
                                            $user .= sprintf(i18n::s('by %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']));
                                    }

                                    $details .= $user.' '.Skin::build_date($item['edit_date']);
                            }

                            // signal locked articles
                            if(isset($item['locked']) && ($item['locked'] == 'Y') && Articles::is_owned($item, $anchor))
                                    $details .= ', '.LOCKED_FLAG;

                            // poster details
                            if($details)
                                    $details = '<p class="details">'.$details."</p>\n";

                            if(!isset($item['comments_count']))
                                    $item['comments_count'] = 0;

                            // this is another row of the output
                            $cells = array($title.$suffix, 'center='.$starter, 'center='.$item['comments_count'], 'center='.Skin::build_number($item['hits']), $details);
                            if(THREAD_IMG)
                                    $cells = array_merge(array($icon), $cells);

                            $rows[] = $cells;
                            
                        }

		}

		// end of processing
		SQL::free($result);
                
                if($this->listed_type == 'section') {
                    $text .= Skin::table_suffix();
                } else {
                    // headers
                    $headers = array(i18n::s('Topic'), 'center='.i18n::s('Poster'), 'center='.i18n::s('Replies'), 'center='.i18n::s('Views'), i18n::s('Last post'));
                    if(THREAD_IMG)
                            $headers = array_merge(array(''), $headers);

                    // make a sortable table
                    $text = Skin::table($headers, $rows, 'yabb');
                }
                
                $this->load_scripts_n_styles();
		return $text;
	}
}
