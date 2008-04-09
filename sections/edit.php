<?php
/**
 * create a new section or edit an existing one
 *
 * @todo list available overlays
 *
 * A button-based editor is used for the description field.
 * It's aiming to introduce most common [link=codes]codes/index.php[/link] supported by YACS.
 *
 * This script attempts to validate the new or updated article description against a standard PHP XML parser.
 * The objective is to spot malformed or unordered HTML and XHTML tags. No more, no less.
 *
 * Optional expiration date is automatically translated from and to the surfer time zone and the
 * server time zone.
 *
 * Restrictions apply on this page:
 * - associates can always proceed
 * - a managing editor of a container can create a sub-section
 * - a managing editor is allowed to modify a section he is in charge of
 * - else permission is denied
 *
 * Moreover, editors can modify only a subset of all section fields.
 *
 * Accepted calls:
 * - edit.php
 * - edit.php/&lt;id&gt;
 * - edit.php?id=&lt;id&gt;
 * - edit.php?anchor=&lt;anchor&gt;
 *
 * If this section, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author Vincent No&euml;l
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Agnes
 * @tester Marco Pici
 * @tester Ghjmora
 * @tester Aleko
 * @tester Manuel López Gallego
 * @tester Jan Boen
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Sections::get($id);

// get the related anchor, if any --use request first, because anchor can change
$anchor = NULL;
if(isset($_REQUEST['anchor']) && $_REQUEST['anchor'])
	$anchor = Anchors::get($_REQUEST['anchor']);
elseif(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// editors have associate-like capabilities
if((isset($item['id']) && Sections::is_assigned($item['id']) && Surfer::is_member()) || (is_object($anchor) && $anchor->is_editable()))
	Surfer::empower();

// associates and editors can proceed
if(Surfer::is_empowered())
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// do not always show the edition form
$with_form = FALSE;

// load localized strings
i18n::bind('sections');

// load the skin, maybe with a variant
load_skin('sections', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor)&& $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'sections/' => i18n::s('Sections') );
if(isset($item['id']) && isset($item['title']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Sections::get_url($item['id']) => $item['title']));

// the title of the page
if(isset($item['title']))
	$context['page_title'] = sprintf(i18n::s('Edit: %s'), $item['title']);
else
	$context['page_title'] = i18n::s('Add a section');

// validate input syntax
if(!Surfer::is_associate() || (isset($_REQUEST['option_validate']) && ($_REQUEST['option_validate'] == 'Y'))) {
	if(isset($_REQUEST['introduction']))
		validate($_REQUEST['introduction']);
	if(isset($_REQUEST['description']))
		validate($_REQUEST['description']);
}

// adjust dates from surfer time zone to UTC time zone
if(isset($_REQUEST['activation_date']) && $_REQUEST['activation_date'])
	$_REQUEST['activation_date'] = Surfer::to_GMT($_REQUEST['activation_date']);
if(isset($_REQUEST['expiry_date']) && $_REQUEST['expiry_date'])
	$_REQUEST['expiry_date'] = Surfer::to_GMT($_REQUEST['expiry_date']);

// access denied
if(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged()) {

		if(isset($item['id']))
			$link = Sections::get_url($item['id'], 'edit');
		elseif(isset($_REQUEST['anchor']))
			$link = 'sections/edit.php?anchor='.urlencode($_REQUEST['anchor']);
		else
			$link = 'sections/edit.php';

		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode($link));
	}

	// permission denied to authenticated user
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// change editor
} elseif(isset($_REQUEST['preferred_editor']) && $_REQUEST['preferred_editor'] && ($_REQUEST['preferred_editor'] != $_SESSION['surfer_editor'])) {
	$_SESSION['surfer_editor'] = $_REQUEST['preferred_editor'];
	$item = $_REQUEST;
	$with_form = TRUE;

// update an existing section
} elseif(isset($item['id']) && isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// remember the previous version
	include_once '../versions/versions.php';
	Versions::save($item, 'section:'.$item['id']);

	// do the change
	Sections::put($_REQUEST);

	// display the form on error
	if(count($context['error'])) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// else display the updated page
	} else {

		// cascade changes on access rights
		if($_REQUEST['active'] != $item['active'])
			Anchors::cascade('section:'.$item['id'], $_REQUEST['active']);

		// touch the related anchor silently
		if(is_object($anchor))
			$anchor->touch('section:update', $item['id'], TRUE);

		Safe::redirect($context['url_to_home'].$context['url_to_root'].Sections::get_url($item['id'], 'view', $item['title']));
	}

// post a new section
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// display the form on error
	if(!$id = Sections::post($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// post-processing
	} else {

		// touch the related anchor
		if(is_object($anchor))
			$anchor->touch('section:create', $id, isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// get the new item
		$section = Anchors::get('section:'.$id);

		// reward the poster for new posts
		$context['page_title'] = i18n::s('A section has been successfully added');

		$context['text'] .= i18n::s('<p>Please review the new page carefully and fix possible errors rapidly.</p>');

		// follow-up commands
		$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';
		$menu = array();
		$menu = array_merge($menu, array($section->get_url() => i18n::s('View the section')));
		if(Surfer::may_upload())
			$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$id) => i18n::s('Add an image')));
		if(preg_match('/\bwith_files\b/i', $section->item['options']) && Surfer::may_upload())
			$menu = array_merge($menu, array('files/edit.php?anchor='.urlencode('section:'.$id) => i18n::s('Upload a file')));
		if(preg_match('/\bwith_links\b/i', $section->item['options']))
			$menu = array_merge($menu, array('links/edit.php?anchor='.urlencode('section:'.$id) => i18n::s('Add a link')));
		if(is_object($anchor) && Surfer::is_empowered())
			$menu = array_merge($menu, array('sections/edit.php?anchor='.urlencode($anchor->get_reference()) => i18n::s('Add another section')));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

		// log the creation of a new section
		$label = sprintf(i18n::c('New section: %s'), strip_tags($section->get_title()));

		if(is_object($anchor))
			$description = sprintf(i18n::s('Sent by %s in %s'), Surfer::get_name(), $anchor->get_title());
		else
			$description = sprintf(i18n::s('Sent by %s'), Surfer::get_name());
		$description .= "\n\n".$section->get_teaser('basic')
			."\n\n".$context['url_to_home'].$context['url_to_root'].$section->get_url();
		Logger::notify('sections/edit.php', $label, $description);

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// the form to edit a section
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// form fields
	$fields = array();

	//
	// index tab - fields that contribute directly to the section index page
	//
	$index = '';

	// the title
	$label = i18n::s('Title').' *';

	// copy this as compact title on initial edit
	$onchange = '';
	if(!isset($item['id']))
		$onchange = ' onchange="$(\'title\').value = this.value"';
	$input = '<textarea name="index_title" rows="2" cols="50" accesskey="t"'.$onchange.'>'.encode_field(isset($item['index_title']) ? $item['index_title'] : '').'</textarea>';
	$hint = i18n::s('Please provide a meaningful title.');
	$fields[] = array($label, $input, $hint);

	// the introduction
	$label = i18n::s('Introduction');
	$input = '<textarea name="introduction" rows="2" cols="50" accesskey="i">'.encode_field(isset($item['introduction']) ? $item['introduction'] : '').'</textarea>';
	$hint = i18n::s('Appears at the site map, near section title');
	$fields[] = array($label, $input, $hint);

	// the description
	$label = i18n::s('Content');
	$input = Surfer::get_editor('description', isset($item['description'])?$item['description']:'');
	$fields[] = array($label, $input);

	// append regular fields
	$index .= Skin::build_form($fields);
	$fields = array();

	// associates and editors can change the layout of the index page
	if(Surfer::is_empowered()) {

		// layout for sub-sections - 'compact', 'decorated', 'freemind', 'folded', 'inline', 'jive', 'map', 'titles', 'yabb', 'none' or custom - default is decorated
		$label = i18n::s('Sections');
		if(!isset($item['sections_count']) || ($item['sections_count'] < 1))
			$item['sections_count'] = SECTIONS_PER_PAGE;
		$input = sprintf(i18n::s('List up to %s sub-sections with the following layout:'), '<input type="text" name="sections_count" value="'.encode_field($item['sections_count']).'" size="2"'.EOT).BR;
		$custom_layout = '';
		if(!isset($item['sections_layout']))
			$item['sections_layout'] = 'map';
		elseif(!preg_match('/(compact|decorated|folded|freemind|inline|jive|map|titles|yabb|none)/', $item['sections_layout'])) {
			$custom_layout = $item['sections_layout'];
			$item['sections_layout'] = 'custom';
		}
		$input .= '<input type="radio" name="sections_layout" value="decorated"';
		if($item['sections_layout'] == 'decorated')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('decorated - As a decorated list.')
			.BR.'<input type="radio" name="sections_layout" value="map"';
		if($item['sections_layout'] == 'map')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('map - Map in two columns, like Yahoo!')
			.BR.'<input type="radio" name="sections_layout" value="freemind"';
		if($item['sections_layout'] == 'freemind')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('freemind - Build an interactive mind map')
			.BR.'<input type="radio" name="sections_layout" value="jive"';
		if($item['sections_layout'] == 'jive')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('jive - List 5 threads per board')
			.BR.'<input type="radio" name="sections_layout" value="yabb"';
		if($item['sections_layout'] == 'yabb')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('yabb - A discussion forum')
			.BR.'<input type="radio" name="sections_layout" value="inline"';
		if($item['sections_layout'] == 'inline')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('inline - List sub-sections and related articles.')
			.BR.'<input type="radio" name="sections_layout" value="folded"';
		if($item['sections_layout'] == 'folded')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('folded - List sub-sections as folded boxes, with content (one box per section).')
			.BR.'<input type="radio" name="sections_layout" value="compact"';
		if($item['sections_layout'] == 'compact')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('compact - In a compact list, like DMOZ.')
			.BR.'<input type="radio" name="sections_layout" value="titles"';
		if($item['sections_layout'] == 'titles')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('titles - Use only titles and thumbnails.')
			.BR.'<input type="radio" name="sections_layout" value="custom"';
		if($item['sections_layout'] == 'custom')
			$input .= ' checked="checked"';
		$input .= EOT.' '.sprintf(i18n::s('Use the customized layout %s'), '<input type="text" name="sections_custom_layout" value="'.encode_field($custom_layout).'" size="32"'.EOT)
			.BR.'<input type="radio" name="sections_layout" value="none"';
		if($item['sections_layout'] == 'none')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('Do not list sub-sections.');
		$fields[] = array($label, $input);

		// layout for related articles
		$label = i18n::s('Pages');
		$input = i18n::s('List recent pages using the following layout:').BR;
		$custom_layout = '';
		if(!isset($item['articles_layout']))
			$item['articles_layout'] = 'decorated';
		elseif(!preg_match('/(alistapart|boxesandarrows|compact|daily|decorated|digg|jive|manual|map|none|slashdot|table|wiki|yabb)/', $item['articles_layout'])) {
			$custom_layout = $item['articles_layout'];
			$item['articles_layout'] = 'custom';
		}
		$input .= '<input type="radio" name="articles_layout" value="decorated"';
		if($item['articles_layout'] == 'decorated')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('decorated - A decorated list of most recent pages');
		$input .= BR.'<input type="radio" name="articles_layout" value="digg"';
		if($item['articles_layout'] == 'digg')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('digg - To order pages by rating');
		$input .= BR.'<input type="radio" name="articles_layout" value="slashdot"';
		if($item['articles_layout'] == 'slashdot')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('slashdot - List most recent pages equally');
		$input .= BR.'<input type="radio" name="articles_layout" value="map"';
		if($item['articles_layout'] == 'map')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('map - Map in two columns, like Yahoo!');
		$input .= BR.'<input type="radio" name="articles_layout" value="table"';
		if($item['articles_layout'] == 'table')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('table - A table of recent pages');
		$input .= BR.'<input type="radio" name="articles_layout" value="daily"';
		if($item['articles_layout'] == 'daily')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('daily - A list of stamped pages (blog)');
		$input .= BR.'<input type="radio" name="articles_layout" value="boxesandarrows"';
		if($item['articles_layout'] == 'boxesandarrows')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('boxesandarrows - Click on titles to read articles');
		$input .= BR.'<input type="radio" name="articles_layout" value="jive"';
		if($item['articles_layout'] == 'jive')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('jive - Display most of articles content');
		$input .= BR.'<input type="radio" name="articles_layout" value="yabb"';
		if($item['articles_layout'] == 'yabb')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('yabb - A discussion board');
		$input .= BR.'<input type="radio" name="articles_layout" value="alistapart"';
		if($item['articles_layout'] == 'alistapart')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('alistapart - Display entirely the last published page');
		$input .= BR.'<input type="radio" name="articles_layout" value="wiki"';
		if($item['articles_layout'] == 'wiki')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('wiki - A set of editable and extensible pages');
		$input .= BR.'<input type="radio" name="articles_layout" value="manual"';
		if($item['articles_layout'] == 'manual')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('manual - A hierarchy of article titles');
		$input .= BR.'<input type="radio" name="articles_layout" value="compact"';
		if($item['articles_layout'] == 'compact')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('compact - A compact list of items');
		$input .= BR.'<input type="radio" name="articles_layout" value="custom"';
		if($item['articles_layout'] == 'custom')
			$input .= ' checked="checked"';
		$input .= EOT.' '.sprintf(i18n::s('Use the customized layout %s'), '<input type="text" name="articles_custom_layout" value="'.encode_field($custom_layout).'" size="32"'.EOT);
		$input .= BR.'<input type="radio" name="articles_layout" value="none"';
		if($item['articles_layout'] == 'none')
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('Do not display articles.').BR;
		$fields[] = array($label, $input);

		// rendering options
		$label = i18n::s('Rendering');
		$input = '<input type="text" name="options" id="options" size="55" value="'.encode_field(isset($item['options']) ? $item['options'] : '').'" maxlength="255" accesskey="o" />'
			.'<script type="text/javascript">// <![CDATA['."\n"
			.'function append_to_options(keyword) {'."\n"
			.'	var target = document.getElementById("options");'."\n"
			.'	target.value = target.value + " " + keyword;'."\n"
			.'}'."\n"
			.'// ]]></script>'."\n";
		$hint = i18n::s('You may combine several keywords:')
			.'<ul><li><a onclick="javascript:append_to_options(\'articles_by_publication\')" style="cursor: pointer;">articles_by_publication</a> - '.i18n::s('Sort pages by publication date').'</li>'
			.'<li><a onclick="javascript:append_to_options(\'articles_by_rating\')" style="cursor: pointer;">articles_by_rating</a> - '.i18n::s('Sort pages by rating').'</li>'
			.'<li><a onclick="javascript:append_to_options(\'articles_by_title\')" style="cursor: pointer;">articles_by_title</a> - '.i18n::s('Sort pages by title').'</li>'
			.'<li><a onclick="javascript:append_to_options(\'articles_by_reverse_rank\')" style="cursor: pointer;">articles_by_reverse_rank</a> - '.i18n::s('Sort pages by reverse rank').'</li>'
			.'<li><a onclick="javascript:append_to_options(\'no_new_articles\')" style="cursor: pointer;">no_new_articles</a> - '.i18n::s('Do not list recent pages from sub-sections').'</li>'
			.'<li><a onclick="javascript:append_to_options(\'with_files\')" style="cursor: pointer;">with_files</a> - '.i18n::s('Files may be attached directly to section index').'</li>'
			.'<li><a onclick="javascript:append_to_options(\'files_by_title\')" style="cursor: pointer;">files_by_title</a> - '.i18n::s('Sort files by title (and not by date)').'</li>'
			.'<li><a onclick="javascript:append_to_options(\'with_links\')" style="cursor: pointer;">with_links</a> - '.i18n::s('Links may be attached directly to section index').'</li>'
			.'<li><a onclick="javascript:append_to_options(\'links_by_title\')" style="cursor: pointer;">links_by_title</a> - '.i18n::s('Sort links by title (and not by date)').'</li>'
			.'<li><a onclick="javascript:append_to_options(\'with_comments\')" style="cursor: pointer;">with_comments</a> - '.i18n::s('The section index itself is a thread').'</li>'
			.'<li><a onclick="javascript:append_to_options(\'with_slideshow\')" style="cursor: pointer;">with_slideshow</a> - '.i18n::s('Display content as a S5 slideshow').'</li>'
			.'<li>skin_foo_bar - '.i18n::s('Apply a specific skin (in skins/foo_bar) here').'</li>'
			.'<li>variant_foo_bar - '.i18n::s('To load template_foo_bar.php instead of the regular skin template').'</li>'
			.'<li><a onclick="javascript:append_to_options(\'no_contextual_menu\')" style="cursor: pointer;">no_contextual_menu</a> - '.i18n::s('No information about surrounding sections').'</li></ul>';
		$fields[] = array($label, $input, $hint);

		// trailer information
		$label = i18n::s('Trailer');
		$input = Surfer::get_editor('trailer', isset($item['trailer'])?$item['trailer']:'', TRUE);
		$hint = i18n::s('Text to be appended at the bottom of the page, after all other elements attached to this page.');
		$fields[] = array($label, $input, $hint);

		// extra information
		$label = i18n::s('Extra');
		$input = '<textarea name="extra" rows="6" cols="50">'.encode_field(isset($item['extra']) ? $item['extra'] : '').'</textarea>';
		$hint = i18n::s('Text to be inserted in the panel aside the page.');
		$fields[] = array($label, $input, $hint);

		// news can be either a static or an animated list
		$label = i18n::s('News');
		if(!isset($item['index_news_count']) || ($item['index_news_count'] < 1) || ($item['index_news_count'] > 7))
			$item['index_news_count'] = 5;
		$input = '<input type="radio" name="index_news" value="static"';
		if(!isset($item['index_news']) || !preg_match('/(rotate|scroll|none)/', $item['index_news']))
			$input .= ' checked="checked"';
		$input .= EOT.' '.sprintf(i18n::s('List up to %s news aside.'), '<input type="text" name="index_news_count" value="'.encode_field($item['index_news_count']).'" size="2"'.EOT);
		$input .= BR.'<input type="radio" name="index_news" value="scroll"';
		if(isset($item['index_news']) && ($item['index_news'] == 'scroll'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('Similar to the first option, except that displayed information is scrolling.');
		$input .= BR.'<input type="radio" name="index_news" value="rotate"';
		if(isset($item['index_news']) && ($item['index_news'] == 'rotate'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('Similar to the first option, except that news are rotated.');
		$input .= BR.'<input type="radio" name="index_news" value="none"';
		if(isset($item['index_news']) && ($item['index_news'] == 'none'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('Do not list news.');
		$fields[] = array($label, $input);

		// append fields
		$index .= Skin::build_box(i18n::s('More content'), Skin::build_form($fields), 'folder');
		$fields = array();

	// preserve previous settings
	} else {
		if(isset($item['sections_count']))
			$context['text'] .= '<input type="hidden" name="sections_count" value="'.encode_field($item['sections_count']).'" />';
		if(isset($item['sections_layout']))
			$context['text'] .= '<input type="hidden" name="sections_layout" value="'.encode_field($item['sections_layout']).'" />';
		if(isset($item['articles_layout']))
			$context['text'] .= '<input type="hidden" name="articles_layout" value="'.encode_field($item['articles_layout']).'" />';
		if(isset($item['index_news_count']))
			$context['text'] .= '<input type="hidden" name="index_news_count" value="'.encode_field($item['index_news_count']).'" />';
		if(isset($item['index_news']))
			$context['text'] .= '<input type="hidden" name="index_news" value="'.encode_field($item['index_news']).'" />';
	}

	// append fields
	$index .= Skin::build_form($fields);
	$fields = array();

	//
	// content tab - management of the content tree
	//
	$content = '';

	// this section
	//

	// the parent section
	if(Surfer::is_associate()) {
		$label = i18n::s('Parent section');

		$to_select = 'none';
		if(isset($item['anchor']) && $item['anchor'])
			$to_select = $item['anchor'];
		elseif(isset($_REQUEST['anchor']) && $_REQUEST['anchor'])
			$to_select = $_REQUEST['anchor'];
		$to_avoid = '';
		if(isset($item['id']))
			$to_avoid = 'section:'.$item['id'];
		$input = '<select name="anchor">'.'<option value="">'.i18n::s('-- Root level')."</option>\n".Sections::get_options($to_select, $to_avoid).'</select>';

		$hint = i18n::s('Please carefully select a parent section.');

		$fields[] = array($label, $input, $hint);
	} elseif(is_object($anchor))
		$content .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	// the nick name
	if(Surfer::is_empowered()) {
		$label = i18n::s('Nick name');
		$input = '<input type="text" name="nick_name" size="32" value="'.encode_field(isset($item['nick_name']) ? $item['nick_name'] : '').'" maxlength="64" accesskey="n" />';
		$hint = sprintf(i18n::s('To designate a section by its name in the %s'), Skin::build_link('go.php', i18n::s('page selector'), 'help'));
		$fields[] = array($label, $input, $hint);
	}

	// behaviors
	if(Surfer::is_empowered()) {
		$label = i18n::s('Behaviors');
		$input = '<textarea name="behaviors" rows="2" cols="50">'.encode_field(isset($item['behaviors']) ? $item['behaviors'] : '').'</textarea>';
		$hint = sprintf(i18n::s('One %s per line'), Skin::build_link('behaviors/', i18n::s('behavior'), 'help'));
		$fields[] = array($label, $input, $hint);
	}

	// fields related to this section
	$content .= Skin::build_box(i18n::s('This section'), Skin::build_form($fields), 'header2');
	$fields = array();

	// images
	if(Surfer::may_upload()) {
		$box = '';

		// splash message for new pages
		if(!isset($item['id']))
			$box .= '<p>'.i18n::s('Hit the submit button and post images afterwards.').'</p>';

		else {

			$menu = array( 'images/edit.php?anchor='.urlencode('section:'.$item['id']) => i18n::s('Add an image') );
			$box .= Skin::build_list($menu, 'menu_bar');

			// the list of images
			include_once '../images/images.php';
			if($items = Images::list_by_date_for_anchor('section:'.$item['id'], 0, 50, NULL)) {
				$box .= '<p>'.i18n::s('Click on links to insert images in the main field.')."</p>\n"
					.Skin::build_list($items, 'decorated');
			}

		}
		$content .= Skin::build_box(i18n::s('Images'), $box, 'header2', 'edit_images');

	}

	// settings for sub-sections
	//
	if(Surfer::is_empowered()) {

		// content overlay
		$label = i18n::s('Overlay');
		$input = '<input type="text" name="section_overlay" size="20" value="'.encode_field(isset($item['section_overlay']) ? $item['section_overlay'] : '').'" maxlength="64" />';
		$hint = sprintf(i18n::s('Script used to %s in this section'), Skin::build_link('overlays/', i18n::s('overlay sub-sections'), 'help'));
		$fields[] = array($label, $input, $hint);

		// append fields
		$content .= Skin::build_box(i18n::s('Sub-sections'), Skin::build_form($fields), 'folder');
		$fields = array();

	}

	// settings for attached pages
	//
	if(Surfer::is_empowered()) {

		// content options
		$label = i18n::s('Options');
		$input = '<input type="text" name="content_options" id="content_options" size="55" value="'.encode_field(isset($item['content_options']) ? $item['content_options'] : '').'" maxlength="255" accesskey="o" />'
			.'<script type="text/javascript">// <![CDATA['."\n"
			.'function append_to_content_options(keyword) {'."\n"
			.'	var target = document.getElementById("content_options");'."\n"
			.'	target.value = target.value + " " + keyword;'."\n"
			.'}'."\n"
			.'// ]]></script>'."\n";
		$hint = i18n::s('You may enhance every page of this section by adding one or more keywords:')
			.'<ul><li><a onclick="javascript:append_to_content_options(\'anonymous_edit\')" style="cursor: pointer;">anonymous_edit</a> - '.i18n::s('Allow anonymous surfers to change content').'</li>'
			.'<li><a onclick="javascript:append_to_content_options(\'members_edit\')" style="cursor: pointer;">members_edit</a> - '.i18n::s('Allow members to change content').'</li>'
			.'<li><a onclick="javascript:append_to_content_options(\'auto_publish\')" style="cursor: pointer;">auto_publish</a> - '.i18n::s('Pages are not reviewed prior publication').'</li>'
			.'<li><a onclick="javascript:append_to_content_options(\'view_as_thread\')" style="cursor: pointer;">view_as_thread</a> - '.i18n::s('Real-time collaboration').'</li>'
			.'<li>view_as_foo_bar - '.i18n::s('Branch out to articles/view_as_foo_bar.php').'</li>';
		if(isset($context['content_without_details']) && ($context['content_without_details'] == 'Y'))
			$hint .= '<li><a onclick="javascript:append_to_content_options(\'with_details\')" style="cursor: pointer;">with_details</a> - '.i18n::s('Show page details to all surfers').'</li>';
		$hint .= '<li><a onclick="javascript:append_to_content_options(\'with_rating\')" style="cursor: pointer;">with_rating</a> - '.i18n::s('Allow surfers to rate pages of this section').'</li>'
			.'<li><a onclick="javascript:append_to_content_options(\'with_bottom_tools\')" style="cursor: pointer;">with_bottom_tools</a> - '.i18n::s('Add conversion tools to PDF, MS-Word, Palm at the bottom of each page').'</li>'
			.'<li><a onclick="javascript:append_to_content_options(\'with_prefix_profile\')" style="cursor: pointer;">with_prefix_profile</a> - '.i18n::s('Introduce the poster before main text').'</li>'
			.'<li><a onclick="javascript:append_to_content_options(\'with_suffix_profile\')" style="cursor: pointer;">with_suffix_profile</a> - '.i18n::s('Append some poster details at the bottom of the page').'</li>'
			.'<li><a onclick="javascript:append_to_content_options(\'with_extra_profile\')" style="cursor: pointer;">with_extra_profile</a> - '.i18n::s('Append some poster details aside the page (adequate to most weblogs)').'</li>'
			.'<li><a onclick="javascript:append_to_content_options(\'no_comments\')" style="cursor: pointer;">no_comments</a> - '.i18n::s('Disallow post of new comments').'</li>'
			.'<li><a onclick="javascript:append_to_content_options(\'no_links\')" style="cursor: pointer;">no_links</a> - '.i18n::s('Disallow post of new links').'</li>'
			.'<li><a onclick="javascript:append_to_content_options(\'no_neighbours\')" style="cursor: pointer;">no_neighbours</a> - '.i18n::s('Prevent YACS to add links to previous and next pages in the same section').'</li></ul>';
		$fields[] = array($label, $input, $hint);

		// content overlay
		if(Surfer::is_empowered()) {
			$label = i18n::s('Overlay');
			$input = '<input type="text" name="content_overlay" size="50" value="'.encode_field(isset($item['content_overlay']) ? $item['content_overlay'] : '').'" maxlength="64" />';
			$hint = sprintf(i18n::s('Script used to %s in this section'), Skin::build_link('overlays/', i18n::s('overlay articles'), 'help'));
			$fields[] = array($label, $input, $hint);
		}

		// content template
		if(Surfer::is_empowered()) {
			$label = i18n::s('Templates');
			$input = '<input type="text" name="articles_templates" size="50" value="'.encode_field(isset($item['articles_templates']) ? $item['articles_templates'] : '').'" maxlength="250" />';
			$hint = sprintf(i18n::s('One or several %s. This setting overrides the overlay setting.'), Skin::build_link(Sections::get_url('templates'), i18n::s('templates'), 'help'));
			$fields[] = array($label, $input, $hint);
		}

		// the prefix
		$label = i18n::s('Prefix');
		$input = '<textarea name="prefix" rows="2" cols="50">'.encode_field(isset($item['prefix']) ? $item['prefix'] : '').'</textarea>';
		$hint = i18n::s('To be inserted at the top of pages of this section.');
		$fields[] = array($label, $input, $hint);

		// the suffix
		$label = i18n::s('Suffix');
		$input = '<textarea name="suffix" rows="2" cols="50">'.encode_field(isset($item['suffix']) ? $item['suffix'] : '').'</textarea>';
		$hint = i18n::s('To be inserted at the bottom of pages of this section.');
		$fields[] = array($label, $input, $hint);

		// append fields
		$content .= Skin::build_box(i18n::s('Pages'), Skin::build_form($fields), 'folder');
		$fields = array();

	}

	// settings for parent or site map
	//
	$parent = '';

	// layout of the upper index page, or of the site map
	//
	if(is_object($anchor)) {

		// associates and editors may decide define how pages of this section are listed in upper level section index
		if(Surfer::is_empowered()) {

			// define how this section appears
			$label= '';
			$input = i18n::s('This section should be:').BR
				.'<input type="radio" name="index_map" value="Y"';
			if(!isset($item['index_map']) || ($item['index_map'] != 'N'))
				$input .= ' checked="checked"';
			$input .= EOT.' '.sprintf(i18n::s('listed in the main panel, as usual, with the rank %s (default value is 10000).'), '<input type="text" name="rank" size="5" value="'.encode_field(isset($item['rank']) ? $item['rank'] : '10000').'" maxlength="5" />');
			$input .= BR.'<input type="radio" name="index_map" value="N"';
			if(isset($item['index_map']) && ($item['index_map'] == 'N'))
				$input .= ' checked="checked"';
			$input .= EOT.' '.i18n::s('listed only to associates and editors, with other special sections').BR;
			$parent .= '<p>'.$label.BR.$input.'</p>';

			$label = '';
			$input = BR.i18n::s('Recent pages should be:').BR;
			$input .= '<input type="radio" name="index_panel" value="main"';
			if(!isset($item['index_panel']) || ($item['index_panel'] == '') || ($item['index_panel'] == 'main'))
				$input .= ' checked="checked"';
			$input .= EOT.' '.i18n::s('displayed in the main panel, as usual')
				.BR.'<input type="radio" name="index_panel" value="news"';
			if(isset($item['index_panel']) && ($item['index_panel'] == 'news'))
				$input .= ' checked="checked"';
			$input .= EOT.' '.i18n::s('displayed in the area reserved to news')
				.BR.'<input type="radio" name="index_panel" value="gadget"';
			if(isset($item['index_panel']) && ($item['index_panel'] == 'gadget'))
				$input .= ' checked="checked"';
			$input .= EOT.' '.i18n::s('listed in a gadget box in the main panel')
				.BR.'<input type="radio" name="index_panel" value="gadget_boxes"';
			if(isset($item['index_panel']) && ($item['index_panel'] == 'gadget_boxes'))
				$input .= ' checked="checked"';
			$input .= EOT.' '.i18n::s('displayed in distinct gadget boxes')
				.BR.'<input type="radio" name="index_panel" value="icon_top"';
			if(isset($item['index_panel']) && ($item['index_panel'] == 'icon_top'))
				$input .= ' checked="checked"';
			$input .= EOT.' '.i18n::s('listed as thumbnails, at the top of the main panel')
				.BR.'<input type="radio" name="index_panel" value="icon_bottom"';
			if(isset($item['index_panel']) && ($item['index_panel'] == 'icon_bottom'))
				$input .= ' checked="checked"';
			$input .= EOT.' '.i18n::s('listed as thumbnails, at the bottom of the main panel')
				.BR.'<input type="radio" name="index_panel" value="extra"';
			if(isset($item['index_panel']) && ($item['index_panel'] == 'extra'))
				$input .= ' checked="checked"';
			$input .= EOT.' '.i18n::s('listed in an extra box, on page side')
				.BR.'<input type="radio" name="index_panel" value="extra_boxes"';
			if(isset($item['index_panel']) && ($item['index_panel'] == 'extra_boxes'))
				$input .= ' checked="checked"';
			$input .= EOT.' '.i18n::s('displayed in distinct extra boxes')
				.BR.'<input type="radio" name="index_panel" value="none"';
			if(isset($item['index_panel']) && ($item['index_panel'] == 'none'))
				$input .= ' checked="checked"';
			$input .= EOT.' '.i18n::s('not displayed at the parent index page');
			$parent .= '<p>'.$label.BR.$input.'</p>';

			// one for layout options
			$content .= Skin::build_box(sprintf(i18n::s('Contribution to "%s"'), $anchor->get_title()), $parent, 'folder');
			$fields = array();

		// preserve previous settings
		} else {
			if(isset($item['index_map']))
				$content .= '<input type="hidden" name="index_map" value="'.encode_field($item['index_map']).'" />';
			if(isset($item['rank']))
				$content .= '<input type="hidden" name="rank" value="'.encode_field($item['rank']).'" />';
			if(isset($item['index_panel']))
				$content .= '<input type="hidden" name="index_panel" value="'.encode_field($item['index_panel']).'" />';
		}

	// layout options related to the site map
	} else {

		// associates may decide if the top-level section will be hidden on the site map or not
		if(Surfer::is_associate()) {

			// define how this section appear at the site map
			$label= '';
			$input = i18n::s('This section should be:').BR
				.'<input type="radio" name="index_map" value="Y"';
			if(!isset($item['index_map']) || ($item['index_map'] != 'N'))
				$input .= ' checked="checked"';
			$input .= EOT.' '.sprintf(i18n::s('listed in the main panel, as usual, with the rank %s (default value is 10000). If the site front page features the site map, the section will be listed there as well.'), '<input type="text" name="rank" size="5" value="'.encode_field(isset($item['rank']) ? $item['rank'] : '10000').'" maxlength="5" />');
			$input .= BR.'<input type="radio" name="index_map" value="N"';
			if(isset($item['index_map']) && ($item['index_map'] == 'N'))
				$input .= ' checked="checked"';
			$input .= EOT.' '.i18n::s('listed only to associates, with other special sections, and never appears at the site front page').BR;
			$parent .= '<p>'.$label.BR.$input.'</p>';

			// one box for layout options
			$content .= Skin::build_box(i18n::s('Appearance at the site map'), $parent, 'folder');
			$fields = array();

		// preserve previous settings
		} else {
			if(isset($item['index_map']))
				$content .= '<input type="hidden" name="index_map" value="'.encode_field($item['index_map']).'" />';
			if(isset($item['rank']))
				$content .= '<input type="hidden" name="rank" value="'.encode_field($item['rank']).'" />';
		}

	}

	// contribution to the front page
	//
	if(Surfer::is_associate()) {

		// options for recent pages of this section
		$label = '';
		$input = i18n::s('Recent pages should be:').BR;
		$input .= '<input type="radio" name="home_panel" value="main"';
		if(isset($item['home_panel']) && ($item['home_panel'] == 'main'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('displayed in the main panel, as usual')
			.BR.'<input type="radio" name="home_panel" value="news"';
		if(isset($item['home_panel']) && ($item['home_panel'] == 'news'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('displayed in the area reserved to news')
			.BR.'<input type="radio" name="home_panel" value="gadget"';
		if(isset($item['home_panel']) && ($item['home_panel'] == 'gadget'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('listed in a gadget box in the main panel')
			.BR.'<input type="radio" name="home_panel" value="gadget_boxes"';
		if(isset($item['home_panel']) && ($item['home_panel'] == 'gadget_boxes'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('displayed in distinct gadget boxes')
			.BR.'<input type="radio" name="home_panel" value="icon_top"';
		if(isset($item['home_panel']) && ($item['home_panel'] == 'icon_top'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('listed as thumbnails, at the top of the main panel')
			.BR.'<input type="radio" name="home_panel" value="icon_bottom"';
		if(isset($item['home_panel']) && ($item['home_panel'] == 'icon_bottom'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('listed as thumbnails, at the bottom of the main panel')
			.BR.'<input type="radio" name="home_panel" value="extra"';
		if(isset($item['home_panel']) && ($item['home_panel'] == 'extra'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('listed in an extra box, on page side')
			.BR.'<input type="radio" name="home_panel" value="extra_boxes"';
		if(isset($item['home_panel']) && ($item['home_panel'] == 'extra_boxes'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('displayed in distinct extra boxes')
			.BR.'<input type="radio" name="home_panel" value="none"';
		if(!isset($item['home_panel']) || !preg_match('/^(extra|extra_boxes|gadget|gadget_boxes|icon_bottom|icon_top|main|news)$/', $item['home_panel']))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('not displayed at the site front page');
		$fields[] = array($label, $input);

		// one folded box for layout options
		$content .= Skin::build_box(i18n::s('Contribution to the site front page'), Skin::build_form($fields), 'folder');
		$fields = array();

	// preserve previous settings
	} else {
		if(isset($item['home_panel']))
			$content .= '<input type="hidden" name="home_panel" value="'.encode_field($item['home_panel']).'" />';
	}

	// append fields
	$content .= Skin::build_form($fields);
	$fields = array();

	//
	// options tab
	//
	$options = '';

	// editors
	if(isset($item['id']))
		$label = Skin::build_link(Users::get_url('section:'.$item['id'], 'select'), i18n::s('Editors'), 'basic');
	else
		$label = i18n::s('Editors');
	if(isset($item['id']) && ($items = Members::list_editors_by_name_for_member('section:'.$item['id'], 0, USERS_LIST_SIZE, 'compact')))
		$input =& Skin::build_list($items, 'comma');
	else
		$input = i18n::s('No editor has been assigned to this section.');
	if(Surfer::is_associate())
		$hint = sprintf(i18n::s('Visit %s to change sections assigned to one person'), Skin::build_link('users/', i18n::s('user profiles'), 'basic'));
	else
		$hint = NULL;
	$fields[] = array($label, $input, $hint);

	// readers
	$label = i18n::s('Readers');
	if(isset($item['id']) && ($items = Members::list_readers_by_name_for_member('section:'.$item['id'], 0, 30, 'compact')))
		$input =& Skin::build_list($items, 'comma');
	else
		$input = i18n::s('No reader has been assigned to this section.');
	if(Surfer::is_associate())
		$hint = sprintf(i18n::s('Visit %s to change sections assigned to one person'), Skin::build_link('users/', i18n::s('user profiles'), 'basic'));
	else
		$hint = NULL;
	$fields[] = array($label, $input, $hint);

	// the active flag: Yes/public, Restricted/logged, No/associates --we don't care about inheritance, to enable security changes afterwards
	if(Surfer::is_empowered()) {
		$label = i18n::s('Visibility');

		// maybe a public page
		$input = '<input type="radio" name="active_set" value="Y" accesskey="v"';
		if(!isset($item['active_set']) || ($item['active_set'] == 'Y'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('Anyone may read this section').BR;


		// maybe a restricted page
		$input .= '<input type="radio" name="active_set" value="R"';
		if(isset($item['active_set']) && ($item['active_set'] == 'R'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('Access is restricted to authenticated members').BR;

		// or a hidden page
		$input .= '<input type="radio" name="active_set" value="N"';
		if(isset($item['active_set']) && ($item['active_set'] == 'N'))
			$input .= ' checked="checked"';
		$input .= EOT.' '.i18n::s('Access is restricted to associates and editors');

		$fields[] = array($label, $input);
	}

	// locked: Yes / No
	$label = i18n::s('Locked');
	$input = '<input type="radio" name="locked" value="N"';
	if(!isset($item['locked']) || ($item['locked'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('No - Contributions are accepted').' '
		.BR.'<input type="radio" name="locked" value="Y"';
	if(isset($item['locked']) && ($item['locked'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Yes - Only associates and editors can modify content');
	$fields[] = array($label, $input);

	// the activation date
	if(Surfer::is_empowered()) {
		$label = i18n::s('Activation date');

		// adjust date from UTC time zone to surfer time zone
		$value = '';
		if(isset($item['activation_date']) && ($item['activation_date'] > NULL_DATE))
			$value = Surfer::from_GMT($item['activation_date']);

		$input = Skin::build_input('activation_date', $value, 'date_time');
		$hint = i18n::s('YYYY-MM-DD HH:MM - To make this section appear in the future - automatically.');
		$fields[] = array($label, $input, $hint);
	}

	// the expiry date
	if(Surfer::is_empowered()) {
		$label = i18n::s('Expiry date');

		// adjust date from UTC time zone to surfer time zone
		$value = '';
		if(isset($item['expiry_date']) && ($item['expiry_date'] > NULL_DATE))
			$value = Surfer::from_GMT($item['expiry_date']);

		$input = Skin::build_input('expiry_date', $value, 'date_time');
		$hint = i18n::s('YYYY-MM-DD HH:MM - Let the server hide sections on dead-lines - automatically.');
		$fields[] = array($label, $input, $hint);
	}

	// append fields
	$options .= Skin::build_box(i18n::s('Restrictions'), Skin::build_form($fields), 'header2');
	$fields = array();

	// the family
	if(Surfer::is_empowered()) {
		$label = i18n::s('Family');
		$input = '<input type="text" name="family" size="50" value="'.encode_field(isset($item['family']) ? $item['family'] : '').'" maxlength="255"'.EOT;
		$hint = i18n::s('Comes before the title; Used to categorized sections in forums');
		$fields[] = array($label, $input, $hint);
	}

	// index title
	$label = i18n::s('Compact title');
	$input = '<textarea name="title" id="title" rows="2" cols="50">'.encode_field(isset($item['title']) ? $item['title'] : '').'</textarea>';
	$hint = i18n::s('Alternate title used in lists and in the contextual menu');
	$fields[] = array($label, $input, $hint);

	// language of this page
	$label = i18n::s('Language');
	$input = i18n::get_languages_select(isset($item['language'])?$item['language']:'');
	$hint = i18n::s('Select the language used for this page');
	$fields[] = array($label, $input, $hint);

	// meta information
	$label = i18n::s('Meta information');
	$input = '<textarea name="meta" rows="2" cols="50">'.encode_field(isset($item['meta']) ? $item['meta'] : '').'</textarea>';
	$hint = i18n::s('Type here any XHTML tags to be put in page header.');
	$fields[] = array($label, $input, $hint);

	// the thumbnail url may be set after the section creation
	if(isset($item['id']) && (Surfer::is_empowered())) {
		$label = i18n::s('Thumbnail URL');
		$input = '<input type="text" name="thumbnail_url" size="55" value="'.encode_field(isset($item['thumbnail_url']) ? $item['thumbnail_url'] : '').'" maxlength="255" />';
		$hint = i18n::s('To illustrate the section at the Site map');
		$fields[] = array($label, $input, $hint);
	}

	// the icon url may be set after the section creation
	if(isset($item['id']) && (Surfer::is_empowered())) {
		$label = i18n::s('Icon URL');
		$input = '<input type="text" name="icon_url" size="55" value="'.encode_field(isset($item['icon_url']) ? $item['icon_url'] : '').'" maxlength="255" />';
		$hint = i18n::s('Displayed at the section page, and used as the default icon for related article pages');
		$fields[] = array($label, $input, $hint);
	}

	// the bullet url may be set after the section creation
	if(isset($item['id']) && (Surfer::is_empowered())) {
		$label = i18n::s('Bullet URL');
		$input = '<input type="text" name="bullet_url" size="55" value="'.encode_field(isset($item['bullet_url']) ? $item['bullet_url'] : '').'" maxlength="255" />';
		$hint = i18n::s('Displayed in front of articles listed in this section');
		$fields[] = array($label, $input, $hint);
	}

	// add a folded box
	if(count($fields)) {
		$options .= Skin::build_box(i18n::s('More options'), Skin::build_form($fields), 'folder');
		$fields = array();
	}

	// append fields
	$options .= Skin::build_form($fields);
	$fields = array();

	//
	// assemble all tabs
	//
	$all_tabs = array(
		array('index_tab', i18n::s('Index page'), 'index_panel', $index),
		array('content_tab', i18n::s('Content tree'), 'content_panel', $content),
		array('options_tab', i18n::s('Options'), 'options_panel', $options)
		);

	// let YACS do the hard job
	$context['text'] .= Skin::build_tabs($all_tabs);

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// cancel button
	if(isset($item['id']))
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'view', $item['title'], $item['nick_name']), i18n::s('Cancel'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// several options to check
	$input = '';

	// do not stamp edition date
	if(Surfer::is_empowered() && isset($item['id']))
		$input .= '<input type="checkbox" name="silent" value="Y" /> '.i18n::s('Do not change modification date.').BR;

	// validate page content
	if(Surfer::is_empowered())
		$input .= '<input type="checkbox" name="option_validate" value="Y" checked="checked" /> '.i18n::s('Ensure this post is valid XHTML.').BR;

	// append post-processing options
	if($input)
		$context['text'] .= $input;

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// append the script used for data checking on the browser
	$context['page_footer'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// title is mandatory'."\n"
		.'	if(!container.title.value) {'."\n"
		.'		alert("'.i18n::s('Please provide a meaningful title.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	// successful check'."\n"
		.'	return true;'."\n"
		.'}'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'document.getElementById("title").focus();'."\n"
		.'// ]]></script>'."\n";

	// content of the help box
	$help = '';

	// splash message for new pages
	if(!isset($item['id']))
		$help .= '<p>'.i18n::s('Please type the text of your new page and hit the submit button. You will then be able to post images, files and links on subsequent forms.').'</p>';

	// html and codes
	$help .= '<p>'.sprintf(i18n::s('%s and %s are available to beautify your post.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'help'), Skin::build_link('smileys/', i18n::s('smileys'), 'help')).'</p>';

 	// locate mandatory fields
 	$help .= '<p>'.i18n::s('Mandatory fields are marked with a *').'</p>';

	// drive associates to the Content Assistant
	if(Surfer::is_associate())
		$help .= '<p>'.sprintf(i18n::s('Use the %s if you are lost.'), Skin::build_link('control/populate.php', i18n::s('Content Assistant'), 'shortcut')).'</p>'."\n";

	// in a side box
	$context['extra'] .= Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

}

// render the skin
render_skin();

?>