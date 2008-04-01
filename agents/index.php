<?php
/**
 * display information gathered by background agents
 *
 * On first invocation, the script offers to select among subjects listed below.
 * Also, an extra box introduces a link to the monitoring newsfeed, aiming to be plugged
 * remotely into log updates.
 *
 * Depending on the subject selected, this script will provide detailed information:
 *
 * - referrals - stats on referrals. Information is captured by [script]agents/referrals.php[/script]
 * through [script]agents/referrals_hook.php[/script].
 *
 * - searches - stats on keywords used for searches. Information is captured by [script]agents/referrals.php[/script]
 * through [script]agents/referrals_hook.php[/script].
 *
 * - agents - stats on user agents. Information is captured by [script]agents/browsers.php[/script]
 * through [script]agents/browsers_hook.php[/script].
 * Gathered data can be purged from the control panel by triggering [script]control/purge.php[/script]
 *
 * - profiles - response times and number of script call. Information is captured by [script]agents/profiles.php[/script]
 * through [script]agents/profiles_hook.php[/script].
 * Performance data can be purged from the control panel by triggering [script]control/purge.php[/script]
 *
 * - events - last events on this server (configuration changes, etc.)
 * The main logging facility used throughout YACS is [script]shared/logger.php[/script].
 * Note that this script writes into [code]temporary/log.txt[/code] and not into the database.
 *
 * - values - list content of the table for scalar values.
 *
 * This index also displays the stamp of the last cron.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
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

// the title of the page
$context['page_title'] = i18n::s('Background processing');

// commands for associates
if(Surfer::is_associate()) {

	$context['page_menu'] = array_merge($context['page_menu'], array( 'agents/?subject=referrals' => i18n::s('Referrals') ));

	$context['page_menu'] = array_merge($context['page_menu'], array( 'agents/?subject=searches' => i18n::s('Searches') ));

	$context['page_menu'] = array_merge($context['page_menu'], array( 'agents/?subject=browsers' => i18n::s('Browsers') ));

	$context['page_menu'] = array_merge($context['page_menu'], array( 'agents/?subject=profiles' => i18n::s('Performance') ));

	$context['page_menu'] = array_merge($context['page_menu'], array( 'agents/?subject=values' => i18n::s('Values') ));

	$context['page_menu'] = array_merge($context['page_menu'], array( 'cron.php' => i18n::s('Cron') ));

	$context['page_menu'] = array_merge($context['page_menu'], array( 'agents/?subject=events' => i18n::s('Events') ));

	$context['page_menu'] = array_merge($context['page_menu'], array( 'agents/configure.php' => i18n::s('Configure') ));
}

// the user has to be an associate
if(!Surfer::is_associate()) {
	$context['text'] .= i18n::s('You are not allowed to perform this operation.');

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// give information
} else {

	// stats on user agents
	switch($subject) {

	case 'browsers': // stats on user agents

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

			// display information on browsers
			$context['text'] .= Skin::build_block(i18n::s('User Agents'), 'title');

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
				$context['text'] .= Skin::table_prefix();
				$count = 1;
				foreach($browsers as $name => $value)
					$context['text'] .= Skin::table_row(array('left='.$icons[$name].' '.$name, substr(100 * $value / $total_hits, 0, 5).'&nbsp;%', $value), $count++);
				$context['text'] .= Skin::table_suffix();
			} else
				$context['text'] .= i18n::s('No data to display');

			// display information on operating systems
			$context['text'] .= Skin::build_block(i18n::s('Operating systems'), 'title');

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
				$context['text'] .= Skin::table_prefix();
				$count = 1;
				foreach($os as $name => $value)
					$context['text'] .= Skin::table_row(array('left='.$icons[$name].' '.$name, substr(100 * $value / $total_hits, 0, 5).'&nbsp;%', $value), $count++);
				$context['text'] .= Skin::table_suffix();
			} else
				$context['text'] .= i18n::s('No data to display');

		}

		break;

	case 'events':	// display last events

		// display last events
		$events = Logger::get_tail(30, 'all');
		if(is_array($events)) {

			// put a title for this section
			$context['text'] .= Skin::build_block(i18n::s('Last events on this server'), 'title');

			// splash message
			$context['text'] .= '<p>'.sprintf(i18n::s('You can sort the table below by clicking on column headers. <p>To get the full list of events, please download %s</p>'), Skin::build_link('temporary/log.txt', 'temporary/log.txt', 'shortcut'))."</p>\n";

			// the actual list of events
			$headers = array(i18n::s('What?'), i18n::s('When?'), i18n::s('Where?'), i18n::s('Who?'));
			foreach($events as $event) {
				list($stamp, $surfer, $script, $label, $description) = $event;
				$label = implode(BR, array($label, $description));
				$rows[] = array('left='.$label, 'left='.$stamp, 'left='.$script, 'left='.$surfer);
			}
			$context['text'] .= Skin::table($headers, $rows);

		} else
			$context['text'] .= '<p>'.i18n::s('No event has been logged')."</p\>";

		break;

	case 'profiles': // display performance information

		// profiles
		include_once $context['path_to_root'].'agents/profiles.php';
		$rows = Profiles::list_by_hits(0, 30);
		if($rows) {

			// put a title for this section
			$context['text'] .= Skin::build_block(i18n::s('Most popular scripts'), 'title');

			// the actual list of events
			$headers = array(i18n::s('Script'), i18n::s('Hits'), i18n::s('Average time'), i18n::s('Minimum time'), i18n::s('Maximum time'));
			$context['text'] .= Skin::table($headers, $rows);

		}
		break;

	case 'referrals': // display referer information

		include_once $context['path_to_root'].'agents/referrals.php';
		if($result = Referrals::list_by_domain(0, 50)) {

			// put a title for this section
			$context['text'] .= Skin::build_block(i18n::s('Referrals'), 'title');

			// table header
			$headers = array(i18n::s('Domain'), i18n::s('Count'));

			// table rows
			$rows = array();
			while($item =& SQL::fetch($result))
				$rows[] = array('left='.Skin::build_link($item['referer'], $item['domain'], 'external'), 'left='.$item['hits']);

			// render the table
			$context['text'] .= Skin::table($headers, $rows);

		}
		break;

	case 'searches': // display keywords submitted to search engines

		include_once $context['path_to_root'].'agents/referrals.php';
		if($result = Referrals::list_by_keywords(0, 50)) {

			// put a title for this section
			$context['text'] .= Skin::build_block(i18n::s('Keywords used for searches'), 'title');

			// table row
			$headers = array(i18n::s('Keywords'), i18n::s('Count'));

			// table rows
			$rows = array();
			while($item =& SQL::fetch($result))
				$rows[] = array('left='.Skin::build_link($item['referer'], $item['keywords'], 'external'), 'left='.$item['hits']);

			// render the table
			$context['text'] .= Skin::table($headers, $rows);

		}
		break;

	case 'values': // display values updated in the background

		$query = "SELECT * FROM ".SQL::table_name('values')." ORDER BY id";
		if(!$result =& SQL::query($query)) {
			$context['text'] .= Skin::error_pop().BR."\n";
		} else {
			$context['text'] .= "<table>\n";
			while($row =& SQL::fetch($result)) {
				$context['text'] .= '<tr><td>'.$row['id'].'</td><td>'.str_replace("\n", BR, $row['value']).'</td><td>'.Surfer::from_GMT($row['edit_date'])."</td></tr>\n";
			}
			$context['text'] .= "</table>\n";
		}
		break;

	default:	// select the information source

		// the splash message
		$context['text'] .= sprintf(i18n::s('<p>Please select among available commands:</p><ul><li>%s - Who is driving traffic to us?</li><li>%s - What are the keywords used to come to us?</li><li>%s - Stats about user agents.</li><li>%s - including minimum, maximum, and average response times.</li><li>%s - monitor background processing.</li><li>%s - tick background processing.</li><li>%s - the log of system events.</li></ul>'),
			'<a href="'.$context['url_to_root'].'agents/?subject=referrals">'.i18n::s('Referrals').'</a>',
			'<a href="'.$context['url_to_root'].'agents/?subject=searches">'.i18n::s('Searches').'</a>',
			'<a href="'.$context['url_to_root'].'agents/?subject=browsers">'.i18n::s('Browsers').'</a>',
			'<a href="'.$context['url_to_root'].'agents/?subject=profiles">'.i18n::s('Performance').'</a>',
			'<a href="'.$context['url_to_root'].'agents/?subject=values">'.i18n::s('Values').'</a>',
			'<a href="'.$context['url_to_root'].'cron.php">'.i18n::s('Cron').'</a>',
			'<a href="'.$context['url_to_root'].'agents/?subject=events">'.i18n::s('Events').'</a>')."\n";

		// date of last cron
		include_once $context['path_to_root'].'shared/values.php';	// cron.tick
		if($stamp = Values::get_record('cron.tick'))
			$context['text'] .= '<p>'.sprintf(i18n::s('Date of last cron: %s'), $stamp['edit_date'].' GMT').'</p>';

		// newsfeed for associates
		$context['extra'] .= Skin::build_box(i18n::s('Monitoring Feed'), sprintf(i18n::s('You can get a RSS list of most recent events for this server %s'), Skin::build_link('agents/feed.php', i18n::s('here'), 'xml')), 'extra');

		break;
	}
}

// referrals, if any
$context['extra'] .= Skin::build_referrals('agents/index.php');

// render the skin
render_skin();

?>