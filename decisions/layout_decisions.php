<?php
/**
 * layout decisions
 *
 * This is the default layout for decisions.
 *
 * @see decisions/decisions.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_decisions extends Layout_interface {

	/**
	 * list decisions
	 *
	 * Recognizes following variants:
	 * - 'no_anchor' -- do not mention anchor link
	 * - 'no_author' -- do not mention author link
	 *
	 * @param resource the SQL result
	 * @param string a variant, if any
	 * @return array
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result, $variant='full') {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// flag decisions updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// process all items in the list
		include_once $context['path_to_root'].'decisions/decisions.php';
		while($item =& SQL::fetch($result)) {

			// initialize variables
			$prefix = $suffix = '';

			// there is no zoom page for decisions
			$label = '_';

			// the icon
			$suffix .= Decisions::get_img($item['type']);

			// the decision date
			if($item['create_name'])
				$stamp = Skin::build_date($item['create_date']);
			else
				$stamp = Skin::build_date($item['edit_date']);

			// mention decision date
			if($variant == 'no_author') {
				switch($item['type']) {
				case 'no':
					$suffix .= ' '.sprintf(i18n::s('Rejected %s'), $stamp);
					break;
				case 'yes':
					$suffix .= ' '.sprintf(i18n::s('Approved %s'), $stamp);
					break;
				}

			// link to profiles of people who have taken some decisions
			} else {
				if($item['create_name'])
					$user = Users::get_link($item['create_name'], $item['create_address'], $item['create_id']);
				else
					$user = Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']);

				// the label
				switch($item['type']) {
				case 'no':
					$suffix .= ' '.sprintf(i18n::s('Rejected by %s %s'), $user, $stamp);
					break;
				case 'yes':
					$suffix .= ' '.sprintf(i18n::s('Approved by %s %s'), $user, $stamp);
					break;
				}

			}

			// commands for this decision
			$menu = array();

			// associates and poster can change the decision
			if(Surfer::is_empowered() || Surfer::is_creator($item['create_id']))
				$menu = array_merge($menu, array( Decisions::get_url($item['id'], 'edit') => i18n::s('edit') ));

			// only associates can delete some decision
			if(Surfer::is_associate())
				$menu = array_merge($menu, array( Decisions::get_url($item['id'], 'delete') => i18n::s('delete') ));

			// append actual commands
			if(count($menu))
				$suffix .= ' '.Skin::build_list($menu, 'menu');

			// new line
			$suffix .= BR;

			// add an anchor for this decision
			$suffix .= '<a name="decision_'.$item['id'].'" ></a>';

			// description
			if($description = ucfirst(trim(Codes::beautify($item['description']))))
					$suffix .= ' '.$description;

			// show an anchor decision
			if(($variant != 'no_anchor') && $item['anchor'] && ($anchor = Anchors::get($item['anchor']))) {
				$anchor_url = $anchor->get_url();
				$anchor_label = ucfirst($anchor->get_title());
				$suffix .= BR.sprintf(i18n::s('In %s'), Skin::build_link($anchor_url, $anchor_label, 'shortcut'))."\n";
			}

			// url to view the decision
			$url = Decisions::get_url($item['id']);

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'decision', NULL);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>