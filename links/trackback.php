<?php
/**
 * process trackback requests
 *
 * TrackBack is a framework for peer-to-peer communication and notifications between web sites.
 * The central idea behind TrackBack is the idea of a TrackBack ping, a request saying, essentially, &quot;resource A is related/linked to resource B.&quot;
 * A TrackBack &quot;resource&quot; is represented by a TrackBack Ping URL, which is just a standard URI.
 *
 * Here is an example of TrackBack. Note that line breaks have been inserted for readability.
 *
 * [snippet]
 * POST http://www.foo.com/yacs/links/trackback.php?anchor=article:123
 * Content-Type: application/x-www-form-urlencoded
 *
 * title=Foo+Bar
 * &url=http://www.bar.com/
 * &excerpt=My+Excerpt
 * &blog_name=Foo
 * [/snippet]
 *
 * Using TrackBack, sites can communicate about related resources.
 * For example, if Weblogger A wishes to notify Weblogger B that he has written something interesting/related/shocking, A sends a TrackBack ping to B.
 *
 * This script can be triggered either remotely, by some weblog software POSTing trackback attributes, or locally,
 * through some GET access to a form aiming to collect these attributes.
 *
 * Basically, the form will be used by those who want to be referenced by your server but that don't have a trackback-enable platform.
 * Others will operate remotely and silently.
 *
 * Note: it's useless to ask for surfers to be authenticated, since trackback posts are anonymous by construction.
 * Check how to harden this script agains spammers and hackers.
 *
 * @link http://www.movabletype.org/docs/mttrackback.html TrackBack Technical Specification
 *
 * Note that YACS also has a client implementation of the trackback specification into [script]links/links.php[/script].
 *
 * Accepted calls:
 * - trackback.php/&lt;type&gt;/&lt;id&gt;			create a new link for this anchor
 * - trackback.php?anchor=&lt;type&gt;:&lt;id&gt;	create a new link for this anchor
 * - trackback.php/&lt;id&gt;						create a new link for the anchor 'article:&lt;id&gt;'
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @see links/links.php#ping
 * @see articles/view.php
 * @see articles/publish.php
 * @see sections/view.php
 * @see categories/view.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester NickR
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../links/links.php';

// the source url
$source = NULL;
if(isset($_REQUEST['url']))
	$source = $_REQUEST['url'];
$source = strip_tags($source);

// link title
$title = NULL;
if(isset($_REQUEST['title']))
	$title = $_REQUEST['title'];
$title = strip_tags($title);

// if title is not provided, the value for url will be set as the title
if(!$title)
	$title = $source;

// the excerpt
$excerpt = NULL;
if(isset($_REQUEST['excerpt']))
	$excerpt = $_REQUEST['excerpt'];
$excerpt = strip_tags($excerpt);

// the blog name
$blog_name = NULL;
if(isset($_REQUEST['blog_name']))
	$blog_name = $_REQUEST['blog_name'];
$blog_name = strip_tags($blog_name);

// look for the anchor reference
$anchor = NULL;
if(isset($_REQUEST['anchor']))
	$anchor = $_REQUEST['anchor'];
elseif(isset($context['arguments'][1]))
	$anchor = $context['arguments'][0].':'.$context['arguments'][1];
elseif(isset($context['arguments'][0]))
	$anchor = 'article:'.$context['arguments'][0];
$anchor = strip_tags($anchor);

// get the related anchor, if any
if($anchor)
	$anchor = Anchors::get($anchor);

// load the skin, maybe with a variant
load_skin('links', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'links/' => i18n::s('Links') );

// the title of the page
if(is_object($anchor) && ($title = $anchor->get_title()))
	$context['page_title'] = sprintf(i18n::s('Reference: %s'), $title);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// save the request if debug mode
	if(isset($context['debug_trackback']) && ($context['debug_trackback'] == 'Y'))
		Logger::remember('links/trackback.php: trackback request', $_REQUEST, 'debug');

	// do we have a valid target to track?
	if(!$anchor || !is_object($anchor))
		$response = array('faultCode' => 1, 'faultString' => 'Nothing to track');

	// check the source has not already been registered
	elseif(Links::have($source, $anchor->get_reference()))
		$response = array('faultCode' => 1, 'faultString' => 'The source has already been registered');

	// read the source file
	elseif(($content = http::proceed($source)) === FALSE)
		$response = array('faultCode' => 1, 'faultString' => 'Cannot read source address '.$source);

	// we have to find a reference to the target here
	else {

		// ensure enough execution time
		Safe::set_time_limit(30);

		// we are coming from this form -- stop robots
		if(strpos($_SERVER['HTTP_REFERER'], $context['script_url']) !== FALSE) {
			if(Surfer::may_be_a_robot())
				$response = array('faultCode' => 1, 'faultString' => 'Please prove you are not a robot');

		// remote call -- get network address a.b.c.d of caller
		} elseif(!isset($_SERVER['REMOTE_ADDR']) || (!$ip = preg_replace('/[^0-9.]/', '', $_SERVER['REMOTE_ADDR'])))
			$response = array('faultCode' => 1, 'faultString' => 'Invalid request');

		// remote call -- get host name for referencing page www.foo.bar
		elseif((!$items = @parse_url($source)) || !isset($items['host']))
			$response = array('faultCode' => 1, 'faultString' => 'Invalid request');

		// remote call -- only accepted from referenced server (by network address or by name)
		elseif(($items['host'] != $ip) && is_callable('gethostbyname') && (gethostbyname($items['host']) != $ip))
			$response = array('faultCode' => 1, 'faultString' => 'Invalid request');

		// we already have an error
		if(isset($response))
			;

		// look for a reference to us in the target page
		elseif(($position = strpos($content, $anchor->get_url())) === FALSE)
			$response = array('faultCode' => 17, 'faultString' => 'No reference found in the source to the target address '.$target);

		// register the source link back from the target page
		else {

			// link title
			$fields['title'] = $title;

			// link description (the excerpt combined with the blog name)
			$fields['description'] = $excerpt;
			if($blog_name)
				$fields['description'] .= ' ('.$blog_name.')';

			// save in the database
			$fields['anchor'] = $anchor->get_reference();
			$fields['link_url'] = $source;
			if(!$fields['id'] = Links::post($fields))
				$response = array('faultCode' => 1, 'faultString' => Logger::error_pop());
			else
				Links::clear($fields);
		}
	}

	// display results if we are coming from this form
	if(strpos($_SERVER['HTTP_REFERER'], $context['script_url']) !== FALSE) {

		// an error has been encountered
		if(is_array($response))
			Logger::error($response['faultString'].' ('.$response['faultCode'].')');

		// everything's going fine
		else
			$context['text'] = '<p>'.i18n::s('Thank you for your contribution')."</p>\n";

	// send some XML
	} else {

		// an error has been encountered
		if(is_array($response)) {
			$response = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'
				."\n".'<response>'
				."\n".'<error>'.$response['faultCode'].'</error>'
				."\n".'<message>'.$response['faultString'].'</message>'
				."\n".'</response>';

		// everything's going fine
		} else {
			$response = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'
				."\n".'<response>'
				."\n".'<error>0</error>'
				."\n".'</response>';
		}

		// save the response if debug mode
		if(isset($context['debug_trackback']) && ($context['debug_trackback'] == 'Y'))
			Logger::remember('links/trackback.php: trackback response', $response, 'debug');

		// send the response
		Safe::header('Content-Type: text/xml');
		Safe::header('Content-Length: '.strlen($response));
		echo $response;
		return;

	}

// we don't know which resource is tracked back
} elseif(!$anchor || !is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No resource to track back.'));

// ensure that access is allowed
} elseif(is_object($anchor) && !$anchor->is_viewable()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// we have a valid reference, collect other information
} else {

	// internal reference, but only to authenticated surfers
	//
	if(Surfer::is_logged()) {
		$label = i18n::s('At any place of this site, use the following code to reference the target page:');
		$value = '['.str_replace(':', '=', $anchor->get_reference()).']';
		$text = '<p>'.$label.' <code>'.$value.'</code></p>'."\n";

		$context['text'] .= Skin::build_box(i18n::s('Internal reference'), $text);
	}

	// external reference
	//
	$text = '';

	// compute the summary
	$summary = '<a href="'.$context['url_to_home'].$context['url_to_root'].$anchor->get_url().'">'
		."\n".$anchor->get_title()."\n".'</a>';
	if(is_object($anchor) && ($excerpt = $anchor->get_teaser('basic')))
		$summary .= ' &mdash; '.$excerpt;

	// a suggestion of text to be pasted in referencing page
	$text .= '<p>'.i18n::s('We suggest you to cut and paste the following piece of text to reference this page:').'</p>'
		.Skin::build_block(encode_field($summary), 'code');

	// permalink
	$label = i18n::s('Permanent address (permalink):');
	$value = $context['url_to_home'].$context['url_to_root'].$anchor->get_url();
	$text .= '<p>'.$label.BR.'<code>'.$value.'</code></p>'."\n";

	// other links
	$other = array();
	if($link = $anchor->get_named_url())
		$other[] = $context['url_to_home'].$context['url_to_root'].$link;
	if($link = $anchor->get_short_url())
		$other[] = $context['url_to_home'].$context['url_to_root'].$link;
	if($other) {
		$label = i18n::ns('Other address:', 'Other addresses:', count($other));
		$text .= '<p>'.$label.BR.'<code>'.join(BR, $other).'</code></p>'."\n";
	}

	$context['text'] .= Skin::build_box(i18n::s('External reference'), $text);


	// trackback
	//
	$text = '';

	// splash screen
	$text .= '<p>'.i18n::s('You can use the form below to manually trackback any of your pages to this site. Of course, use this capability only if your weblogging software is not able to do it automatically.')."</p>\n";

	// the form to edit a link
	$text .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// the link url
	if(!isset($url) || !$url)
		$url = 'http://'.i18n::s('your_server/your_page').'...';
	$label = i18n::s('Referencing address');
	$input = '<input type="text" name="url" id="url" size="45" value="'.encode_field($url).'" maxlength="255" />';
	$hint = i18n::s('The remote address that is referencing content at this site');
	$fields[] = array($label, $input, $hint);

	// the title
	$label = i18n::s('Its title');
	$input = '<input type="text" name="title" size="45" maxlength="255" />';
	$hint = i18n::s('The title of your page');
	$fields[] = array($label, $input, $hint);

	// the excerpt
	$label = i18n::s('Excerpt or description');
	$input = '<textarea name="excerpt" rows="5" cols="50"></textarea>';
	$hint = i18n::s('As this field may be searched by surfers, please choose adequate searchable words');
	$fields[] = array($label, $input, $hint);

	// the blog name
	$label = i18n::s('Blog name or section');
	$input = '<input type="text" name="blog_name" size="45" value="'.encode_field($blog_name).'" maxlength="64" />';
	$hint = i18n::s('To complement the excerpt');
	$fields[] = array($label, $input, $hint);

	// random string to stop robots
	if(!Surfer::is_logged() && ($field = Surfer::get_robot_stopper()))
		$fields[] = $field;

	// build the form
	$text .= Skin::build_form($fields);

	// the submit button
	$text .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>';

	// other hidden fields
	$text .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	// end of the form
	$text .= '</div></form>';

	// the script used for form handling at the browser
	$text .= JS_PREFIX
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// url is mandatory'."\n"
		.'	if(!container.url.value) {'."\n"
		.'		alert("'.i18n::s('Please type a valid link.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	// title is mandatory'."\n"
		.'	if(!container.title.value) {'."\n"
		.'		alert("'.i18n::s('Please provide a meaningful title.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	// exceprt is mandatory'."\n"
		.'	if(!container.exceprt.value) {'."\n"
		.'		alert("'.i18n::s('You must type an excerpt of the referencing page.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	// blog_name is mandatory'."\n"
		.'	if(!container.blog_name.value) {'."\n"
		.'		alert("'.i18n::s('You must name the originating blog.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	// successful check'."\n"
		.'	return true;'."\n"
		.'}'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'$("#url").focus();'."\n"
		.JS_SUFFIX."\n";

	// trackback link
	$label = i18n::s('Trackback address:');
	$value = $context['url_to_home'].$context['url_to_root'].'links/trackback.php?anchor='.$anchor->get_reference();
	$text .= '<p>'.$label.' <code>'.$value.'</code></p>'."\n";

	$context['text'] .= Skin::build_box(i18n::s('Trackback'), $text);

	// general help on this form
	$help = '<p>'.sprintf(i18n::s('This server supports the %s created by Ben Trott and Mena Trott. Please note that any %s system attempts to trackback up to seven links from each published page.'), Skin::build_link('http://www.movabletype.org/docs/mttrackback.html', i18n::s('trackback specification'), 'external'), Skin::build_link('http://www.yacs.fr/', 'Yacs', 'external')).'</p>'
		.'<p>'.i18n::s('You can use this form to manually trackback your pages to this site.').'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

}

// render the skin
render_skin();
?>
