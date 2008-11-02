<?php
/**
 * layout sections as boxesandarrows do
 *
 * This layout is based upon a definition list (i.e., &lt;dl&gt...&lt;/dl&gt;).
 * Each definition term (i.e., &lt;dt&gt...&lt;/dt&gt;) is made of the section title, eventually prefixed with special icons.
 * Definition data (i.e., &lt;dd&gt...&lt;/dd&gt;) is built upon the section introduction, followed by the list of articles in the section.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli [email]contact@vdp-digital.com[/email]
 * @tester Pierre Robert
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see index.php
 */
Class Layout_sections_as_boxesandarrows extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * @return int the optimised count of items for this layout
	 *
	 * @see skins/layout.php
	 */
	function items_per_page() {
		return 50;
	}

	/**
	 * list sections as boxandarrows does
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// flag sections updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// build a definition list
		$text = '';
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'files/files.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item =& SQL::fetch($result)) {

			// reset everything
			$prefix = $label = $suffix = $icon = $details = '';

			// the url to view this item
			$url =& Sections::get_permalink($item);

			// flag sections that are draft, dead, or created or updated very recently
			if($item['activation_date'] >= $now)
				$prefix .= DRAFT_FLAG;
			elseif(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$prefix .= EXPIRED_FLAG;
			elseif($item['create_date'] >= $dead_line)
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$suffix .= UPDATED_FLAG;

			// signal restricted and private sections
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// use the title to label the link
			$label = ucfirst(Skin::strip($item['title'], 10));

			// introduction
			if($item['introduction'])
				$suffix =& Codes::beautify_introduction($item['introduction']);

			// details
			$details = array();

			// info on related sections
			if($count = Sections::count_for_anchor('section:'.$item['id'])) {
				$details[] = sprintf(i18n::ns('%d section', '%d sections', $count), $count);

				// add sub-sections on index pages
				if($related =& Sections::list_by_title_for_anchor('section:'.$item['id'], 0, COMPACT_LIST_SIZE, 'compact')) {
					$sections = array();
					foreach($related as $sub_url => $sub_label) {
						if(is_array($sub_label))
							$sub_label = $sub_label[0].' '.$sub_label[1];
						$sections[] = Skin::build_link($sub_url, $sub_label, 'basic');
					}
					$suffix .= '&raquo; '.implode(', ', $sections);
				}

			}

			// info on related articles
			if($count = Articles::count_for_anchor('section:'.$item['id']))
				$details[] = sprintf(i18n::ns('%d page', '%d pages', $count), $count);

			// info on related files
			if($count = Files::count_for_anchor('section:'.$item['id']))
				$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

			// info on related links
			if($count = Links::count_for_anchor('section:'.$item['id']))
				$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

			// info on related comments
			if($count = Comments::count_for_anchor('section:'.$item['id']))
				$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);

			// actually insert details
			if(count($details))
				$details = ' ('.implode(', ', $details).')';
			else
				$details = '';

			// list all components for this item
			$text .= '<dt>'.$prefix.Skin::build_link($url, $label, 'section').$details.'</dt><dd>'.$suffix.'<p>&nbsp;</p></dd>';
		}

		// end of processing
		SQL::free($result);

		$text = '<dl class="boxesandarrows">'.$text.'</dl>';
		return $text;
	}
}

?>