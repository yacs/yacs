<?php
/**
 * scan subdirectories for downloadable skins
 *
 * This page lists all skins that can be downloaded from this server.
 *
 * Simply speaking, a skin is a straightforward way of changing the visual rendering of your pages.
 *
 * [title]What is a skin?[/title]
 *
 * A skin is a set of files used by YACS to generate the text that is sent to the end-user.
 *
 * Any skin should provide a complete set of files with at least:
 * - skin.php - the library of functions used to build items (titles, blocks, etc.)
 * - template.php - the main template used for the final rendering
 * - manifest.php - the skin descriptor
 *
 * For the skin named myskin,
 * you should put skin.php and template.php in the myskin directory.
 * skin.php will contain the Skin class providing a library of common functions and declarations
 * template.php will be included at the end of the page to produce the actual HTML
 *
 * All images, style sheets, and other HTML components related to myskin should be
 * located into a sub-directory of the skins directory, e.g., put everything into skins/myskin.
 *
 * [title]How to select a skin?[/title]
 * If you are an associate, click on the preview image, or on a 'Use this skin' link.
 *
 * Alternatively, go the main configuration panel at control/configure.php. A list of available skins is displayed.
 * You can select one of them and submit your change.
 *
 * [title]How to derive a skin?[/title]
 * Since reference skins are part of the YACS core set of files, they may be
 * updated unattended in a future release. Therefore, the best approach is to
 * create a skin on your own, dedicated to your server. You don't have to start
 * from scratch for that. Look at all skins featured at your site, then derive
 * one. YACS will copy files of the selected skin to a new directory, where you
 * will be able to safely modify everything that has to be modified.
 *
 * @see skins/derive.php
 *
 * [title]How to remotely change a skin?[/title]
 * For complex situations, it is recommended to access and test all skin files locally.
 * You may install a local copy of YACS for this purpose.
 * Then download skin files to your computer, modify them and test rendering.
 * When the result looks good you will have to upload the entire set of skin files to the server.
 *
 * For minor changes, we recommend you to use the on-line web form.
 * This little tool allows you to edit remotely any cascaded style sheet, and the main template script.
 *
 * @see skins/edit.php
 *
 * [title]How to install a new skin?[/title]
 * First, select among available skins at some reference site.
 * You may start at [link]http://www.yacs.fr/yacs/skins/index.php[/link].
 * On private installations within a large company, the ideal solution is to have a reference server to publish all recommended skins for intranet servers.
 *
 * Second, create a subdirectory under the 'skins' directory, and name it after the skin name.
 *
 * Third, download the skin archive from the origin server, and put all files into the skin subdirectory.
 *
 * Four, go to the main configuration panel at control/configure.php and activate the new skin.
 *
 * [title]How to describe a skin?[/title]
 *
 * Any skin may be described through a file named 'manifest.php' put into the skin directory.
 * For example, here is an excerpt of skins/acme_marketing/manifest.php:
 * [php]
 * $skins['skins/sita_marketing'] = array(
 *	'label_en' => 'The ACME Marketing skin',
 *	'label_fr' => 'Le style ACME Marketing',
 *	'description_en' => 'This theme has been build after the original marketing web site.'
 *		.' To be used only on web servers inside the ACME intranet.',
 *	'description_fr' => 'Ce style a &eacute;t&eacute; d&eacute;velopp&eacute; d\'apr&egrave;s le site du d&eacute;partement marketing.'
 *		.' A utiliser seulement sur des serveurs web &agrave; l\'int&eacute;rieur de l\'intranet ACME.',
 *	'thumbnail' => 'preview.jpg',
 *	'home_url' => 'http://marketing.acme.info/');
 * [/php]
 *
 * Obviously, manifest.php simple task is to append to a public variable named $skins an array of attributes
 * to describe the skin.
 *
 * [title]What variables can be used in template.php?[/title]
 *
 * All attributes (x can be changed via skins/configure.php) that can be used throughout template.php are listed below:
 * - $context['page_title'] - the page title
 * - $context['site_name'] x - server name, maybe something like that: 'My little but interesting web server'
 * - $context['site_copyright'] x - usually something like that: '2002-2003, acme'
 * - $context['language'] - 'en' or 'fr', etc.
 * - $context['skin_variant'] - to select among skin variants, if any
 * - $context['site_email'] x - the e-mail address of the web master
 *
 * Here is the list of attributes (x can be changed via skins/configure.php) that are used specifically into the head part of the page:
 * - $context['site_description'] x - to build the &lt;meta name="description"> field
 * - $context['site_keywords'] x - to build the &lt;meta name="keywords"> field
 * - $context['site_icon'] x - the small icon that may be locked to this page, if not favicon.ico
 * - $context['site_head'] x - to be silently inserted between &lt;head> and &lt;/head>
 *
 * And here are all attributes used only while preparing the body of the output page:
 * - $context['debug'] - for the software developer only - an array of strings to be displayed at the bottom of the page
 * - $context['error'] - stack of error message to display, if any, usually in the first part of the page - an array of strings
 * - $context['extra'] - additional extra boxes such as comments, sidebars, etc.
 * - $context['page_image'] - the main image of the page, if any
 * - $context['page_menu'] - an array of $url => $label to show available commands (e.g., 'back', 'edit', 'delete', etc.)
 * - $context['path_bar'] - an array of $url => $label to show the stack of pages from home
 * - $context['prefix'] - some page prefix
 * - $context['suffix'] - some page suffix
 * - $context['text'] - the main content of the page
 *
 * [title]How to design a skin?[/title]
 *
 * If you need some color scheme, go to the excellent on-line tool provided by
 * [link=Wellstyled]http://wellstyled.com/tools/colorscheme2/index-en.html[/link].
 *
 * @link http://wellstyled.com/tools/colorscheme2/index-en.html Color schemes generator 2
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
include_once '../shared/global.php';

// load the skin
load_skin('skins');

// the title of the page
$context['page_title'] = i18n::s('Themes');

// read all manifest.php files to list skins
$skins = array();
if($dir = Safe::opendir(".")) {
	while(($item = Safe::readdir($dir)) !== FALSE) {
		if(($item[0] == '.') || !is_dir($item))
			continue;
		if(is_readable($item.'/manifest.php'))
			include_once $item.'/manifest.php';
	}
	Safe::closedir($dir);
}

// no skin is shared on this server
if(!count($skins))
	$context['text'] .= '<p>'.i18n::s('No theme is currently shared on this server.')."</p>\n";

// lists shared skins
else {

	// splash message to associates
	if(Surfer::is_associate())
		$context['text'] .= '<p>'.i18n::s('Click on any thumbnail image below to change the current theme.')."</p>\n";

	// rank by id
	ksort($skins);

	// gathering stage
	foreach($skins as $id => $attributes) {

		// skip invalid skins -- for example, obsoleted skins
		if(!file_exists($context['path_to_root'].$id.'/template.php'))
			continue;

		// style description
		$text = '';

		// the style title
		if($label = i18n::l($attributes, 'label'))
			$text .= Skin::build_block($label, 'subtitle');

		// style description
		if($description = i18n::l($attributes, 'description'))
			$text .= '<p>'.Codes::beautify($description).'</p>';

		// a small menu
		$menu = array();

		// test the skin
		$menu = array_merge($menu, array('skins/test.php?skin='.substr($id, 6) => i18n::s('Test this theme')));

		// commands for associates
		if(Surfer::is_associate()) {

			// this skin is not yet used
			if($context['skin'] != $id)
				$menu = array_merge($menu, array('control/configure.php?parameter=skin&value='.$id => i18n::s('Use this theme')));

			// edit this skin
			$menu = array_merge($menu, array('skins/edit.php?skin='.substr($id, 6) => i18n::s('Edit this theme')));

			// derive this skin
			$menu = array_merge($menu, array('skins/derive.php?skin='.substr($id, 6) => i18n::s('Derive this theme')));
		}

		// where this skin comes from
		if($attributes['home_url'])
			$menu = array_merge($menu, array($attributes['home_url'] => i18n::s('Origin page')));


		// append the menu to the text
		$text .= Skin::build_list($menu, 'menu');

		// the image
		if($attributes['thumbnail']) {

			// associates can change the skin
			if(Surfer::is_associate()) {
				$link = 'control/configure.php?parameter=skin&value='.$id;
				$label = i18n::s('Use this theme');

			// other surfers can test it
			} else {
				$link = 'skins/test.php?skin='.substr($id, 6);
				$label = i18n::s('Test this theme');

			}

			// display a clickable image
			$img = '<img src="'.$context['url_to_root'].$id.'/'.$attributes['thumbnail'].'" alt="" title="'.encode_field($label).'" />';
			$text .= BR.Skin::build_link($link, $img);
		}

		// pack it together
		$items[ $id ] = array($text, '_', '', NULL, NULL);
	}

	// rendering stage
	$context['text'] .= Skin::build_list($items, 'rows');
}

// page tools
if(Surfer::is_associate()) {
	$context['page_tools'][] = Skin::build_link('skins/configure.php', i18n::s('Configure'), 'basic');
	$context['page_tools'][] = Skin::build_link('skins/test.php', i18n::s('Theme test'), 'basic');
	$context['page_tools'][] = Skin::build_link('skins/upload.php', i18n::s('Upload a theme'), 'basic');
	$context['page_tools'][] = Skin::build_link('skins/derive.php', i18n::s('Derive a theme'), 'basic');
}

// how to get a skin
if(Surfer::is_associate()) {
	$help = '<p>'.sprintf(i18n::s('Do not attempt to modify a reference theme directly, your changes would be overwritten on next software update. %s instead to preserve your work over time.'), Skin::build_link('skins/derive.php', i18n::s('Derive a theme'), 'shortcut')).'</p>';
	$context['aside']['boxes'] = Skin::build_box(i18n::s('How to get a theme?'), $help, 'navigation', 'help');
}

// referrals, if any
$context['aside']['referrals'] =& Skin::build_referrals('skins/index.php');

// render the skin
render_skin();

?>