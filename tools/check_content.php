<?php
/**
 * check the content of the database
 *
 * This script reads the database and lists the rows that look suspicious,
 * for example a name that contains markup, a script or an event handler in a page,
 * or a link to javascript:. Nothing is changed: an associate reviews each row,
 * and fixes it from the page itself.
 *
 * Use it after an incident, or when some content is suspected. Checks are
 * listed in $checks and $patterns below, and other kinds of controls can be
 * added there.
 *
 * Accented characters are often stored as numeric entities (e.g., &#233;),
 * so only entities of markup characters are reported.
 *
 * To run this script, the surfer has to be an associate.
 *
 * @author Christian Loubechine
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load localized strings
i18n::bind('tools');

// load the skin
load_skin('tools');

// do not index this page
$context->sif('robots','noindex');

// the path to this page
$context['path_bar'] = array( 'tools/' => i18n::s('Tools') );

// the title of the page
$context['page_title'] = i18n::s('Check database content');

// the columns to read, by table and by kind of content
// - 'name': names of persons, copied in many places
// - 'text': titles and HTML content
// - 'url': web addresses
$checks = array(
	array('users', 'users/view.php?id=', 'name', array('nick_name', 'full_name')),
	array('users', 'users/view.php?id=', 'text', array('introduction', 'description', 'signature', 'vcard_title', 'vcard_organization', 'vcard_label')),
	array('users', 'users/view.php?id=', 'url', array('web_address', 'avatar_url')),
	array('articles', 'articles/view.php?id=', 'name', array('create_name', 'edit_name', 'publish_name', 'assign_name')),
	array('articles', 'articles/view.php?id=', 'text', array('title', 'introduction', 'description', 'trailer', 'extra', 'prefix', 'suffix')),
	array('articles', 'articles/view.php?id=', 'url', array('icon_url', 'thumbnail_url')),
	array('sections', 'sections/view.php?id=', 'name', array('create_name', 'edit_name')),
	array('sections', 'sections/view.php?id=', 'text', array('title', 'introduction', 'description', 'trailer', 'extra', 'prefix', 'suffix')),
	array('sections', 'sections/view.php?id=', 'url', array('icon_url', 'thumbnail_url')),
	array('categories', 'categories/view.php?id=', 'name', array('create_name', 'edit_name')),
	array('categories', 'categories/view.php?id=', 'text', array('title', 'introduction', 'description', 'trailer', 'extra', 'prefix', 'suffix')),
	array('categories', 'categories/view.php?id=', 'url', array('icon_url', 'thumbnail_url')),
	array('comments', 'comments/view.php?id=', 'name', array('create_name', 'edit_name')),
	array('comments', 'comments/view.php?id=', 'text', array('description')),
	array('files', 'files/view.php?id=', 'name', array('create_name', 'edit_name', 'assign_name')),
	array('files', 'files/view.php?id=', 'text', array('title', 'description')),
	array('files', 'files/view.php?id=', 'url', array('alternate_href', 'file_href', 'icon_url', 'thumbnail_url')),
	array('links', 'links/edit.php?id=', 'name', array('edit_name')),
	array('links', 'links/edit.php?id=', 'text', array('title', 'link_title', 'description')),
	array('links', 'links/edit.php?id=', 'url', array('link_url')),
	array('images', 'images/view.php?id=', 'name', array('edit_name')),
	array('images', 'images/view.php?id=', 'text', array('title', 'description')),
	array('images', 'images/view.php?id=', 'url', array('link_url')),
	array('locations', 'locations/view.php?id=', 'name', array('edit_name')),
	array('locations', 'locations/view.php?id=', 'text', array('geo_place_name', 'description')),
	array('dates', 'dates/view.php?id=', 'name', array('edit_name')),
);

// what is looked for in each kind of content: level, reason, regular expression
// - 'fix': most likely an attack
// - 'review': can be legitimate content of an associate
$patterns = array(
	'name' => array(
		array('fix', i18n::s('Markup in a name'), '/[<>]|&#0*(?:34|39|60|62);|&#x0*(?:22|27|3c|3e);|&(?:lt|gt|quot);|(?:javascript|vbscript)\s*:/i'),
		array('review', i18n::s('Quote in a name'), '/"/'),
	),
	'text' => array(
		array('fix', i18n::s('Script'), '/<\s*\/?\s*script\b/i'),
		// the handler that opens external links in a new window is written by Skin::build_link() itself
		array('fix', i18n::s('Event handler (on...=)'), '/<[^>]*?[\s\/"\']on[a-z]+\s*=(?!\s*(["\'])window\.open\(this\.href\); return false;\1)/i'),
		array('fix', i18n::s('Link to javascript: or vbscript:'), '/(?:href|src|action|formaction|xlink:href|data)\s*=\s*["\']?\s*(?:javascript|vbscript)\s*:/i'),
		array('fix', i18n::s('Script in a style'), '/(?:style\s*=\s*["\'][^"\']*|<style\b[^>]*>[^<]*)(?:expression\s*\(|url\s*\(\s*["\']?\s*(?:javascript|vbscript)\s*:)/i'),
		array('fix', i18n::s('Tag encoded as an entity'), '/(?:&#0*60;?|&#x0*3c;?)\s*\/?\s*[a-z!]/i'),
		array('review', i18n::s('Link to data:'), '/(?:href|src)\s*=\s*["\']?\s*data\s*:(?!image\/(?:png|gif|jpe?g|webp)[;,])/i'),
		array('fix', i18n::s('Meta tag with http-equiv'), '/<\s*meta\b[^>]*\bhttp-equiv\s*=/i'),
		array('review', i18n::s('Embedded object (iframe, object, embed...)'), '/<\s*(?:iframe|object|embed|applet|base|link|form)\b/i'),
	),
	'url' => array(
		array('fix', i18n::s('Address to javascript: or vbscript:'), '/^\s*(?:javascript|vbscript)\s*:/i'),
		array('fix', i18n::s('Markup in an address'), '/[<>"]/'),
		array('review', i18n::s('Address to data:'), '/^\s*data\s*:/i'),
	),
);

// rows are first selected in SQL with these, then checked with the patterns above
$filters = array(
	'name' => array('<', '>', '&', '"', 'script:'),
	'text' => array('<', '&#', 'script:'),
	'url' => array(':', '<', '"'),
);

// the maximum number of rows listed
$limit = 300;

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('tools/check_content.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the index page
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// do the job
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && isset($_REQUEST['action']) && ($_REQUEST['action'] == 'check')) {

	$levels = array('fix' => i18n::s('To fix'), 'review' => i18n::s('To review'));
	$rows = array();
	$count = 0;
	$columns_of = array();

	foreach($checks as $check) {
		list($table, $view, $kind, $columns) = $check;

		// user profiles may be in another database
		$connection = NULL;
		if(($table == 'users') && isset($context['users_connection']))
			$connection = $context['users_connection'];

		// only columns that exist in this installation
		$name = SQL::table_name($table);
		if(!isset($columns_of[$table])) {
			$columns_of[$table] = array();
			$query = "SHOW COLUMNS FROM ".$name;
			if($result = SQL::query($query, TRUE, $connection)) {
				while($field = SQL::fetch($result))
					$columns_of[$table][] = $field['Field'];
			}
		}
		$columns = array_values(array_intersect($columns, $columns_of[$table]));
		if(!count($columns))
			continue;

		// a first selection in SQL
		$where = array();
		foreach($columns as $column)
			foreach($filters[$kind] as $filter)
				$where[] = $column." LIKE '%".SQL::escape($filter)."%'";
		// who changed the row last, to tell associates from other surfers
		$editor = in_array('edit_name', $columns_of[$table]) ? ', edit_name AS last_editor' : '';

		$query = "SELECT id, ".implode(', ', $columns).$editor." FROM ".$name." WHERE (".implode(' OR ', $where).")";
		if(!$result = SQL::query($query, TRUE, $connection))
			continue;

		// check each value
		while($item = SQL::fetch($result)) {
			foreach($columns as $column) {
				$value = (string)$item[$column];
				if($value === '')
					continue;
				$to_fix = FALSE;
				foreach($patterns[$kind] as $pattern) {
					list($level, $reason, $expression) = $pattern;

					// a value to fix is not listed again for review
					if(($level == 'review') && $to_fix)
						continue;
					if(!preg_match($expression, $value, $matches, PREG_OFFSET_CAPTURE))
						continue;
					if($level == 'fix')
						$to_fix = TRUE;
					$count++;
					if(count($rows) >= $limit)
						continue;

					// an extract around the match, always encoded
					$start = mb_strlen(substr($value, 0, $matches[0][1]), 'UTF-8');
					$from = max(0, $start - 40);
					$extract = ($from ? '...' : '').mb_substr($value, $from, 120, 'UTF-8').((mb_strlen($value, 'UTF-8') > $from + 120) ? '...' : '');

					$reference = $table.':'.$item['id'];
					$rows[] = array(
						$levels[$level],
						htmlspecialchars($reason, ENT_QUOTES, 'UTF-8'),
						Skin::build_link($view.$item['id'], $reference, 'basic'),
						$column,
						isset($item['last_editor']) ? htmlspecialchars($item['last_editor'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '',
						'<code>'.htmlspecialchars($extract, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</code>');
				}
			}
		}
	}

	// report to surfer
	if(!$count)
		$context['text'] .= '<p>'.i18n::s('No suspicious value has been found.')."</p>\n";
	else {
		$context['text'] .= '<p>'.sprintf(i18n::s('%d suspicious values have been found.'), $count)."</p>\n";
		if($count > count($rows))
			$context['text'] .= '<p>'.sprintf(i18n::s('Only the first %d values are listed.'), count($rows))."</p>\n";
		$headers = array(i18n::s('Level'), i18n::s('Reason'), i18n::s('Page'), i18n::s('Field'), i18n::s('Last change by'), i18n::s('Extract'));
		$context['text'] .= Skin::table($headers, $rows);
	}

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// do it again
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><p>'
		.Skin::build_submit_button(i18n::s('Check again'))
		.'<input type="hidden" name="action" value="check" />'
		.'</p></form>'."\n";

// explain and ask for confirmation
} else {

	$context['text'] .= '<p>'.i18n::s('This tool reads the database and lists the values that look suspicious: markup in a name, a script or an event handler in a page, a link to javascript:, and so on. Nothing is changed: review each value, and fix it from the page itself.')."</p>\n";

	$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><p>'
		.Skin::build_submit_button(i18n::s('Start the check'))
		.'<input type="hidden" name="action" value="check" />'
		.'</p></form>'."\n";

}

// render the skin
render_skin();

?>
