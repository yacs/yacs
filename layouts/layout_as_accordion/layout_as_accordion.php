<?php
/**
 * layout items as folded boxes in an accordion
 *
 * @see articles/view.php
 * @see sections/view.php
 *
 * @author Bernard Paques
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_as_accordion extends Layout_interface {

	/**
	 * list pages
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see layouts/layout.php
	**/
	function layout($result) {
		global $context;

		// allow for multiple calls
		static $accordion_id;
		if(!isset($accordion_id))
			$accordion_id = 1;
		else
			$accordion_id++;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// the maximum number of items per article
		if(!defined('MAXIMUM_ITEMS_PER_ACCORDION'))
			define('MAXIMUM_ITEMS_PER_ACCORDION', 100);

		// we return plain text
		$text = '';
		
		// type of listed object
		$items_type = $this->listed_type;

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		$family = '';
		while($item = SQL::fetch($result)) {
		    
		        // get the object interface, this may load parent and overlay
			$entity = new $items_type($item);			
			
			// change the family (layout of sections)
			if(isset($item['family']) && $item['family'] != $family) {
				$family = $item['family'];

				// show the family
				$text .= '<h2><span>'.$family.'&nbsp;</span></h2>'."\n";

			}

			// one box per page
			$box = array('title' => '', 'text' => '');

			// signal entity to be published
			if(isset($item['publish_date']) && (($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S'))))
				$box['title'] .= DRAFT_FLAG;
			
			// signal entity to be activated
			if(isset($item['activation_date']) && ($item['activation_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$box['title'] .= DRAFT_FLAG;

			// signal restricted and private entity
			if($item['active'] == 'N')
				$box['title'] .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$box['title'] .= RESTRICTED_FLAG;

			// use the title to label the link
			if(is_object($entity->overlay))
				$box['title'] .= Codes::beautify_title($entity->overlay->get_text('title', $item));
			else
				$box['title'] .= Codes::beautify_title($item['title']);

			// box content
			$elements = array();

			// complement the title with interesting details
			$details = array();
			
			// info on related article, only for sections
			if($items_type == 'section') {
			    
			    if(preg_match('/\barticles_by_([a-z_]+)\b/i', $item['options'], $matches))
				    $order = $matches[1];
			    else
				    $order = 'edition';
			    $items =& Articles::list_for_anchor_by($order, $entity->get_reference(), 0, MAXIMUM_ITEMS_PER_ACCORDION+1, 'compact');

			    if(@count($items)) {

				    // mention the number of items in folded title
				    $details[] = sprintf(i18n::ns('%d page', '%d pages', count($items)), count($items));

				    // add one link per item
				    foreach($items as $url => $label) {
					    $prefix = $suffix = '';
					    if(is_array($label)) {
						    $prefix = $label[0];
						    $suffix = $label[2];
						    $label = $label[1];
					    }
					    $elements[] = $prefix.Skin::build_link($url, $label, 'article').$suffix;
				    }
			    }
			}

			// info on related files
			if($entity->has_option('files_by') == 'title')
				$items = Files::list_by_title_for_anchor($entity->get_reference(), 0, MAXIMUM_ITEMS_PER_ACCORDION+1, 'compact');
			else
				$items = Files::list_by_date_for_anchor($entity->get_reference(), 0, MAXIMUM_ITEMS_PER_ACCORDION+1, 'compact');
			if($items) {

				// mention the number of items in folded title
				$details[] = sprintf(i18n::ns('%d file', '%d files', count($items)), count($items));

				// add one link per item
				foreach($items as $url => $label) {
					if(is_array($label)) {
						$prefix = $label[0];
						$suffix = $label[2];
						$label = $label[1];
					}
					$elements[] = $prefix.Skin::build_link($url, $label, 'file').$suffix;
				}
			}

			// info on related comments
			if($items = Comments::list_by_date_for_anchor($entity->get_reference(), 0, MAXIMUM_ITEMS_PER_ACCORDION+1, 'compact', TRUE)) {

				// mention the number of items in folded title
				$details[] = sprintf(i18n::ns('%d comment', '%d comments', count($items)), count($items));

				// add one link per item
				foreach($items as $url => $label) {
					$prefix = $suffix = '';
					if(is_array($label)) {
						$prefix = $label[0];
						$suffix = rtrim(Codes::strip(' '.$label[2]), '- ');
						$label = $label[1];
					}
					$elements[] = $prefix.Skin::build_link($url, $label, 'comment').$suffix;
				}
			}

			// info on related links
			if($entity->has_option('links_by_title'))
				$items = Links::list_by_title_for_anchor($entity->get_reference(), 0, MAXIMUM_ITEMS_PER_ACCORDION+1, 'compact');
			else
				$items = Links::list_by_date_for_anchor($entity->get_reference(), 0, MAXIMUM_ITEMS_PER_ACCORDION+1, 'compact');
			if($items) {

				// mention the number of items in folded title
				$details[] = sprintf(i18n::ns('%d link', '%d links', count($items)), count($items));

				// add one link per item
				foreach($items as $url => $label) {
					$prefix = $suffix = '';
					if(is_array($label)) {
						$prefix = $label[0];
						$suffix = $label[2];
						$label = $label[1];
					}
					$elements[] = $prefix.Skin::build_link($url, $label).$suffix;
				}
			}
			
			// list related sub-sections, if any
			if($items_type == 'section') {
			    if($items =& Sections::list_by_title_for_anchor($entity->get_reference(), 0, MAXIMUM_ITEMS_PER_ACCORDION+1, 'compact')) {

				    // mention the number of sections in folded title
				    $details[] = sprintf(i18n::ns('%d section', '%d sections', count($items)), count($items));

				    // add one link per item
				    foreach($items as $url => $label) {
					    $prefix = $suffix = '';
					    if(is_array($label)) {
						    $prefix = $label[0];
						    $suffix = $label[2];
						    $label = $label[1];
					    }
					    $elements[] = $prefix.Skin::build_link($url, $label, 'section').$suffix;
				    }
			    }
			}

			// a link to the page			
			$permalink = $entity->get_permalink($item);
			$elements[] = Skin::build_link($permalink, sprintf(i18n::s('View the %s'),$items_type).MORE_IMG, 'shortcut');

			// complement title
			if(count($details))
				$box['title'] .= ' <span class="details">('.join(', ', $details).')</span>';

			// insert introduction, if any
			if(is_object($entity->overlay))
				$box['text'] .= Skin::build_block($entity->overlay->get_text('introduction', $item), 'introduction');
			elseif(trim($item['introduction']))
				$box['text'] .= Skin::build_block($item['introduction'], 'introduction');

			// no introduction, display entity full content
			else {

				// insert overlay data, if any
				if(is_object($entity->overlay))
					$box['text'] .= $entity->overlay->get_text('box', $item);

				// the content of this box
				$box['text'] .= Codes::beautify($item['description'], $item['options']);

			}

			// make a full list
			if(count($elements))
				$box['text'] .= Skin::finalize_list($elements, 'compact');

			// display all tags
			if($item['tags'])
				$box['text'] .= ' <p class="tags" style="margin-bottom: 0">'.Skin::build_tags($item['tags'], $entity->get_reference()).'</p>';

			// if we have an icon for this page, use it
			if(isset($item['thumbnail_url']) && $item['thumbnail_url']) {

				// adjust the class
				$class= '';
				if(isset($context['classes_for_thumbnail_images']))
					$class = 'class="'.$context['classes_for_thumbnail_images'].'" ';

				// build the complete HTML element
				$icon = '<img src="'.$item['thumbnail_url'].'" alt="" title="'.encode_field(Codes::beautify_title($item['title'])).'" '.$class.'/>';

				// make it clickable
				$link = Skin::build_link($permalink, $icon, 'basic');

				// put this aside
				$box['text'] = '<table class="decorated"><tr>'
					.'<td class="image">'.$link.'</td>'
					.'<td class="content">'.$box['text'].'</td>'
					.'</tr></table>';

			}

			// always make a box
			$text .= $this->build_accordion_box($box['title'], $box['text'], 'accordion_'.$accordion_id);

		}
		
		// we have bounded styles and scripts
		$this->load_scripts_n_styles();

		// end of processing
		SQL::free($result);
		return $text;
	}
	
	/**
	 * build a box part of one accordion
	 *
	 * @param string the box title, if any
	 * @param string the box content
	 * @param string the accordion id, used as CSS class
	 * @return the HTML to display
	 */
	 function build_accordion_box($title, $content, $id) {
		global $context;

		// we need a clickable title
		if(!$title)
			$title = i18n::s('Click to slide');

		// maybe we have an image to enhance rendering
		$img = '';

		// the icon to close accordion boxes
		Skin::define_img_href('ACCORDION_CLOSE_IMG_HREF', 'layouts/accordion_minus.jpg');

		// the icon to open accordion boxes
		Skin::define_img_href('ACCORDION_OPEN_IMG_HREF', 'layouts/accordion_plus.jpg');

		// detect first box of the accordion
		static $fused;
		if(!isset($fused))
			$fused = array();
                $more_class = '';

		// first box is always open
		if(!isset($fused[ $id ])) {

			$style = '';

			if(ACCORDION_CLOSE_IMG_HREF)
				$img = '<img src="'.ACCORDION_CLOSE_IMG_HREF.'" alt="" title="'.encode_field(i18n::s('Click to slide')).'" class="handle" />';

			// close following boxes
			$fused[ $id ] = TRUE;
                        
                        // help css
                        $more_class = ' accordion-open';

		// following boxes are closed
		} else {

			$style = ' style="display: none"';

			if(ACCORDION_OPEN_IMG_HREF)
				$img = '<img src="'.ACCORDION_OPEN_IMG_HREF.'" alt="" title="'.encode_field(i18n::s('Click to slide')).'" class="handle" />';

		}

		// Yacs.toggle_folder() is in shared/yacs.js -- div.accordion_content div is required for slide effect to work
		$text = '<div class="accordion_handle '.$id.'"><div class="accordion_link'.$more_class.'" onclick="javascript:accordion.toggle(this, \''.ACCORDION_OPEN_IMG_HREF.'\', \''.ACCORDION_CLOSE_IMG_HREF.'\', \''.$id.'\');">'.$img.$title.'</div>'
			.'<div class="accordion_content'.$more_class.'"'.$style.'><div>'.$content."</div></div></div>\n";

		// pass by reference
		return $text;

	}

}

?>
