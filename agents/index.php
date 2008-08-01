<?php
/**
 * display information gathered by background agents
 *
 * This script features several tabs:
 *
 * - events - last events on this server (configuration changes, etc.)
 *
 * - values - updated in the background.
 *
 * - profiles - response times and number of script call. Information is captured by [script]agents/profiles.php[/script]
 * through [script]agents/profiles_hook.php[/script].
 * Performance data can be purged from the control panel by triggering [script]control/purge.php[/script]
 *
 * - agents - stats on user agents. Information is captured by [script]agents/browsers.php[/script]
 * through [script]agents/browsers_hook.php[/script].
 * Gathered data can be purged from the control panel by triggering [script]control/purge.php[/script]
 *
 * - referrals - stats on referrals. Information is captured by [script]agents/referrals.php[/script]
 * through [script]agents/referrals_hook.php[/script].
 *
 * - searches - stats on keywords used for searches. Information is captured by [script]agents/referrals.php[/script]
 * through [script]agents/referrals_hook.php[/script].
 *
 * @author Bernard Paques
 * @tester Pat
 * @tester ArunK
 * @tester Guillaume Perez
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see control/info.php
 */

// common definitions and initial processing
include_once '../shared/global.php';

// locate the information source
$subject = NULL;
if(isset($_REQUEST['subject']))
	$subject = $_REQUEST['subject'];
elseif(isset($context['arguments'][0]))
	$subject = $context['arguments'][0];
$subject = strip_tags($subject);

// load localized strings
i18n::bind('agents');

// load the skin
load_skin('agents');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// default page title
$context['page_title'] = i18n::s('Background processing');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('agents/'));

// only associates can use this tool
elseif(!Surfer::is_associate())
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// give information
else {

	//
	// tabbed panels
	//
	$panels = array();

	//
	// last events
	//
	$events = '';

	// display last events
	$items = Logger::get_tail(50, 'all');
	if(is_array($items)) {

		// splash message
		$events .= '<p>'.i18n::s('You can sort the table below by clicking on column headers.')
			.' '.sprintf(i18n::s('To get the full list of events, please download %s.'), Skin::build_link('temporary/log.txt', 'temporary/log.txt', 'shortcut'))."</p>\n";

		// the actual list of events
		$headers = array(i18n::s('What?'), i18n::s('When?'), i18n::s('Where?'), i18n::s('Who?'));
		$rows = array();
		foreach($items as $event) {
			list($stamp, $surfer, $script, $label, $description) = $event;
			$label = implode(BR, array($label, $description));
			$rows[] = array('left='.$label, 'left='.$stamp, 'left='.$script, 'left='.$surfer);
		}
		$events .= Skin::table($headers, $rows);

	} else
		$events .= '<p>'.i18n::s('No event has been logged')."</p\>";

	// display in a separate panel
	if(trim($events))
		$panels[] = array('events_tab', i18n::s('Events'), 'events_panel', $events);

	//
	// values updated in the background
	//
	$values = '';

	$query = "SELECT * FROM ".SQL::table_name('values')." ORDER BY id";
	if(!$result =& SQL::query($query)) {
		$values .= Skin::error_pop().BR."\n";
	} else {
		$values .= Skin::table_prefix('grid');
		while($row =& SQL::fetch($result)) {
			$values .= '<tr><td>'.$row['id'].'</td><td>'.str_replace("\n", BR, $row['value']).'</td><td>'.Surfer::from_GMT($row['edit_date'])."</td></tr>\n";
		}
		$values .= "</table>\n";
	}

	// display in a separate panel
	if(trim($values))
		$panels[] = array('values_tab', i18n::s('Values'), 'values_panel', $values);

	//
	// script profiles
	//
	$profiles = '';

	include_once $context['path_to_root'].'agents/profiles.php';
	if($rows = Profiles::list_by_hits(0, 50)) {

		// splash message
		$profiles .= '<p>'.i18n::s('You can sort the table below by clicking on column headers.')."</p>\n";

		// the actual list of events
		$headers = array(i18n::s('Script'), i18n::s('Hits'), i18n::s('Average time'), i18n::s('Minimum time'), i18n::s('Maximum time'), i18n::s('Total time'));
		$profiles .= Skin::table($headers, $rows);

	}

	// display in a separate panel
	if(trim($profiles))
		$panels[] = array('profiles_tab', i18n::s('Performance'), 'profiles_panel', $profiles);

	//
	// stats on user agents
	//
	$agents = '';

	$query = "SELECT type, variable, hits FROM ".SQL::table_name('counters')." ORDER BY hits DESC";
	if($result =& SQL::query($query)) {

		// fetch data
		while($item =& SQL::fetch($result)) {
			if(($item['type'] == 'total') && ($item['variable'] == 'hits'))
				$total_hits = $item['hits'];
			elseif($item['type'] == 'browser')
				$browsers[$item['variable']] = $item['hits'];
			elseif($item['type'] == 'os')
				$os[$item['variable']] = $item['hits'];
		}

		if(@count($browsers)) {
			$icons['bot']	= '';
			$icons['HP-UX'] = '';
			$icons['Konqueror'] = '<img src="../skins/images/user-agents/konqueror.gif" alt=""'.EOT;
			$icons['Lynx']	= '<img src="../skins/images/user-agents/lynx.gif" alt=""'.EOT;
			$icons['Mozilla']	= '<img src="../skins/images/user-agents/mozilla.gif" alt=""'.EOT;
			$icons['MSIE']	= '<img src="../skins/images/user-agents/explorer.gif" alt=""'.EOT;
			$icons['Netscape']	= '<img src="../skins/images/user-agents/netscape.gif" alt=""'.EOT;
			$icons['Opera'] = '<img src="../skins/images/user-agents/opera.gif" alt=""'.EOT;
			$icons['Other'] = '<img src="../skins/images/user-agents/question.gif" alt=""'.EOT;
			$icons['WebTV'] = '<img src="../skins/images/user-agents/webtv.gif" alt=""'.EOT;
			$agents .= Skin::table_prefix('grid');
			$count = 1;
			foreach($browsers as $name => $value)
				$agents .= Skin::table_row(array('left='.$icons[$name].' '.$name, substr(100 * $value / $total_hits, 0, 5).'&nbsp;%', $value), $count++);
			$agents .= Skin::table_suffix();
		} else
			$agents .= i18n::s('No data to display');

		// display information on operating systems
		$agents .= Skin::build_block(i18n::s('Operating systems'), 'header2');

		if(@count($os)) {
			$icons['AIX']	= '<img src="../skins/images/user-agents/aix.gif" alt=""'.EOT;
			$icons['BeOS']	= '<img src="../skins/images/user-agents/be.gif" alt=""'.EOT;
			$icons['FreeBSD']	= '<img src="../skins/images/user-agents/bsd.gif" alt=""'.EOT;
			$icons['IRIX']	= '<img src="../skins/images/user-agents/irix.gif" alt=""'.EOT;
			$icons['Linux'] = '<img src="../skins/images/user-agents/linux.gif" alt=""'.EOT;
			$icons['Mac']	= '<img src="../skins/images/user-agents/mac.gif" alt=""'.EOT;
			$icons['Other'] = '<img src="../skins/images/user-agents/question.gif" alt=""'.EOT;
			$icons['OS/2']	= '<img src="../skins/images/user-agents/os2.gif" alt=""'.EOT;
			$icons['SunOS'] = '<img src="../skins/images/user-agents/sun.gif" alt=""'.EOT;
			$icons['Windows']	= '<img src="../skins/images/user-agents/windows.gif" alt=""'.EOT;
			$agents .= Skin::table_prefix('grid');
			$count = 1;
			foreach($os as $name => $value)
				$agents .= Skin::table_row(array('left='.$icons[$name].' '.$name, substr(100 * $value / $total_hits, 0, 5).'&nbsp;%', $value), $count++);
			$agents .= Skin::table_suffix();
		} else
			$agents .= i18n::s('No data to display');
	}

	// display in a separate panel
	if(trim($agents))
		$panels[] = array('agents_tab', i18n::s('Browsers'), 'agents_panel', $agents);

	//
	// display referer information
	//
	$referrals = '';

	include_once $context['path_to_root'].'agents/referrals.php';
	if($result = Referrals::list_by_domain(0, 50)) {

		// table header
		$headers = array(i18n::s('Domain'), i18n::s('Count'));

		// table rows
		$rows = array();
		while($item =& SQL::fetch($result))
			$rows[] = array('left='.Skin::build_link($item['referer'], $item['domain'], 'external'), 'left='.Skin::build_number($item['hits']));

		// render the table
		$referrals .= Skin::table($headers, $rows);

	}

	// display in a separate panel
	if(trim($referrals))
		$panels[] = array('referrals_tab', i18n::s('Referrals'), 'referrals_panel', $referrals);

	//
	// requests from search engines
	//
	$searches = '';

	include_once $context['path_to_root'].'agents/referrals.php';
	if($result = Referrals::list_by_keywords(0, 50)) {

		// table row
		$headers = array(i18n::s('Keywords'), i18n::s('Count'));

		// table rows
		$rows = array();
		while($item =& SQL::fetch($result))
			$rows[] = array('left='.Skin::build_link($item['referer'], $item['keywords'], 'external'), 'left='.Skin::build_number($item['hits']));

		// render the table
		$searches .= Skin::table($headers, $rows);

	}

	// display in a separate panel
	if(trim($searches))
		$panels[] = array('searches_tab', i18n::s('Searches'), 'searches_panel', $searches);

	//
	// assemble all tabs
	//
	$context['text'] .= Skin::build_tabs($panels);

}

// referrals, if any
$context['extra'] .= Skin::build_referrals('agents/index.php');

// render the skin
render_skin();

?>