<?php
/**
 * layout files
 *
 * This is the default layout for files.
 *
 * @see files/index.php
 * @see files/files.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_files extends Layout_interface {

	/**
	 * list files
	 *
	 * Recognize following variants:
	 * - 'no_anchor' to list items attached to one particular anchor
	 * - 'no_author' to list items attached to one user profile
	 *
	 * @param resource the SQL result
	 * @param string a variant, if any
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result, $variant='full') {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// load localized strings
		i18n::bind('files');

		// flag files updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// we return an array of ($url => $attributes)
		$items = array();

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// initialize variables
			$prefix = $suffix = $icon = '';

			// view the file page if there is some description
			$view_url = Files::get_url($item['id'], 'view', $item['file_name']);
			if(isset($item['description']) && trim($item['description']))
				$url = Files::get_url($item['id'], 'view', $item['file_name']);

			// else stream the file, except for mp3, which benefit from the dewplayer
			elseif(Files::is_stream($item['file_name']) && !(preg_match('/\.mp3$/i', $item['file_name']) && file_exists($context['path_to_root'].'included/browser/dewplayer.swf')))
				$url = Files::get_url($item['id'], 'stream', $item['file_name']);

			// else download the file
			else
				$url = Files::get_url($item['id'], 'fetch', $item['file_name']);

			// reset the rendering engine between items
			if(is_callable(array('Codes', 'initialize')))
				Codes::initialize($url);

			// insert dewplayer, if it exists, and if file is mp3
			if(preg_match('/\.mp3$/i', $item['file_name']) && file_exists($context['path_to_root'].'included/browser/dewplayer.swf')) {

				// the player
				$dewplayer_url = $context['url_to_root'].'included/browser/dewplayer.swf';

				// the mp3 file
				if(isset($item['file_href']) && $item['file_href'])
					$mp3_url = $item['file_href'];
				else
					$mp3_url = $context['url_to_root'].'files/'.$context['virtual_path'].str_replace(':', '/', $item['anchor']).'/'.rawurlencode($item['file_name']);
				$flashvars = 'son='.$mp3_url;

				// combine the two in a single object
				$prefix .= '<div id="mp3_'.$item['id'].'" class="no_print">Flash plugin or Javascript are turned off. Activate both and reload to view the object</div>'."\n"
					.'<script type="text/javascript">// <![CDATA['."\n"
			        .'var params = {};'."\n"
			        .'params.base = "'.dirname($mp3_url).'/";'."\n"
			        .'params.quality = "high";'."\n"
			        .'params.wmode = "transparent";'."\n"
			        .'params.menu = "false";'."\n"
			        .'params.flashvars = "'.$flashvars.'";'."\n"
					.'swfobject.embedSWF("'.$dewplayer_url.'", "mp3_'.$item['id'].'", "200", "20", "6", "'.$context['url_to_home'].$context['url_to_root'].'included/browser/expressinstall.swf", false, params);'."\n"
					.'// ]]></script>'.BR."\n";

			}

			// flag files uploaded recently
			if($item['create_date'] >= $dead_line)
				$prefix .= NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$prefix .= UPDATED_FLAG;

			// file has been detached
			if(isset($item['assign_id']) && $item['assign_id'])
				$prefix .= DRAFT_FLAG;

			// signal restricted and private files
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// file title or file name
			$label = Codes::beautify_title($item['title']);
			if(!$label)
				$label = ucfirst(str_replace(array('%20', '-', '_'), ' ', $item['file_name']));

			// details
			$details = array();

			// file size
			if($item['file_size'] > 1)
				$details[] = Skin::build_number($item['file_size'], i18n::s('bytes'));

			// downloads
			if($item['hits'] > 1)
				$details[] = sprintf(i18n::s('%d downloads'), $item['hits']);

			if(count($details))
				$suffix .= ' '.ucfirst(implode(', ', $details));

			// description
			if(trim($item['description']))
				$suffix .= BR.Codes::beautify($item['description']);

			$suffix = ' '.ucfirst(trim($suffix));

			// append details to the suffix
			$suffix .= BR.'<span class="details">';

			// file poster and last action
			if($variant != 'no_author')
				$suffix .= ucfirst(sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date'])));
			else
				$suffix .= ucfirst(Skin::build_date($item['edit_date']));

			// anchor link
			if(($variant != 'no_anchor') && ($variant != 'no_author') && $item['anchor'] && ($anchor = Anchors::get($item['anchor']))) {
				$anchor_url = $anchor->get_url();
				$anchor_label = ucfirst($anchor->get_title());
				$suffix .= ' '.sprintf(i18n::s('in %s'), Skin::build_link($anchor_url, $anchor_label, 'article'));
			}

			// file has been detached
			if(isset($item['assign_id']) && $item['assign_id']) {

				// who has been assigned?
				if($item['assign_id'] == Surfer::get_id())
					$suffix .= ', '.sprintf(i18n::s('assigned to you %s'), Skin::build_date($item['assign_date']));
				else
					$suffix .= ', '.sprintf(i18n::s('detached by %s %s'), Users::get_link($item['assign_name'], $item['assign_address'], $item['assign_id']), Skin::build_date($item['assign_date']));
			}

			// end of details
			$suffix .= '</span>';

			// menu bar
			$menu = array();

			// view the file
			$menu = array_merge($menu, array($view_url => i18n::s('Zoom')));

			// detach or edit the file
			if((Surfer::is_empowered() && Surfer::is_member())
				|| Surfer::is_creator($item['create_id'])
				|| (Surfer::is_member() && (!isset($context['users_without_file_overloads']) || ($context['users_without_file_overloads'] != 'Y'))) ) {

				if(!isset($item['assign_id']) || ($item['assign_id'] < 1))
					$menu = array_merge($menu, array( Files::get_url($item['id'], 'detach') => i18n::s('Detach') ));

				$menu = array_merge($menu, array( Files::get_url($item['id'], 'edit') => i18n::s('Update') ));
			}

			// clear assignment
			if(isset($item['assign_id']) && $item['assign_id'] && Surfer::is_associate())
				$menu = array_merge($menu, array( Files::get_url($item['id'], 'clear') => i18n::s('Unassign') ));

			// delete the file
			if((Surfer::is_empowered() && Surfer::is_member()) || Surfer::is_creator($item['create_id']))
				$menu = array_merge($menu, array( Files::get_url($item['id'], 'delete') => i18n::s('Delete') ));

			// append the menu, if any
			if(count($menu))
				$suffix .= BR.Skin::build_list($menu, 'menu');

			// explicit icon
			if($item['thumbnail_url'])
				$icon = $item['thumbnail_url'];

			// or reinforce file type
			else {
				include_once $context['path_to_root'].'files/files.php';
				$icon = Files::get_icon_url($item['file_name']);
			}

			// show a reference to the file for members
			$hover = i18n::s('Get the file');
			if(Surfer::is_member())
				$hover .= ' [file='.$item['id'].']';

			// absolute url
			$url = $context['url_to_home'].$context['url_to_root'].$url;

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'file', $icon, $hover);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>