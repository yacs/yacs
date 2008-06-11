<?php
/**
 * populate articles
 *
 * Creates articles with following nick names:
 * - 'about' - a page providing an overview of this site
 * - 'cover' - a cover article for the front page
 * - 'extra_rss' - a sample extra box to link to our XML RSS feed
 * - 'menu' - a sample general menu displayed on all pages
 * - 'privacy' - to provide clear statements to community members
 *
 * @see control/populate.php
 *
 * This script do not use the standard publication mechanism ([code]Articles::publish()[/code])
 * and do not put created pages into weekly and monthly categories.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// if it was a HEAD request, stop here
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// this script will be included most of the time
$included = TRUE;

// but direct call is also allowed
if(!defined('YACS')) {
	$included = FALSE;

	// include global declarations
	include_once '../shared/global.php';

	// load the skin
	load_skin('articles');

	// the path to this page
	$context['path_bar'] = array( 'articles/' => i18n::s('All pages') );

	// the title of the page
	$context['page_title'] = i18n::s('Add default pages');

	// stop hackers the hard way
	if(!Surfer::is_associate())
		exit('You are not allowed to perform this operation.');

}

// start to create some new pages
$text = '';

// 'about' article
if(Articles::get('about'))
	$text .= i18n::s('An about page already exists.').BR."\n";
elseif($anchor = Sections::lookup('global')) {
	$fields = array();
	$fields['anchor'] = $anchor;
	$fields['nick_name'] = 'about';
	$fields['title'] = i18n::c('About this site');
	$fields['description'] = '[toc]'."\n"
		.'[title]'.i18n::c('What is [parameter=site_name] about?').'[/title]'."\n"
		.'<p>'.i18n::c('Welcome to this server! Here are attributes transmitted in the header of each page:').'</p>'."\n"
		.'<table class="wide">'."\n"
		.'<tr><td>'.i18n::c('Site name').'</td><td>[parameter=site_name]</td></tr>'."\n"
		.'<tr><td>'.i18n::c('Description').'</td><td>[parameter=site_description]</td></tr>'."\n"
		.'<tr><td>'.i18n::c('Keywords').'</td><td>[parameter=site_keywords]</td></tr>'."\n"
		.'<tr><td>'.i18n::c('Geographical position').'</td><td>[parameter=site_position]</td></tr>'."\n"
		.'<tr><td>'.i18n::c('Copyright').'</td><td>[parameter=site_copyright]</td></tr>'."\n"
		.'<tr><td>'.i18n::c('Owner').'</td><td>[parameter=site_owner]</td></tr>'."\n"
		.'</table>'."\n"
		.'<p>'.i18n::c('This site is powered by [link=YACS]http://www.yetanothercommunitysystem.com/[/link], [link=PHP]http://www.php.net/[/link], and [link=MySQL]http://www.mysql.com/[/link] - a fast, easy-to-use site that lets you access, review, and download information that matters for you.').'</p>'."\n"
		.'[title]'.i18n::c('Contact Us').'[/title]'."\n"
		.'<p>'.i18n::c('The preferred mean to join us is to use the general-purpose query form. This is a convenient tool for you and for us, because your request and related answers will be listed at a single place accessible from your web browser. Moreover, you will be informed by e-mail of any progressing step to your query.').'</p>'."\n"
		.'<p class="indent">'.i18n::c('Query: [link=Use the on-line form]query.php[/link]').'</p>'."\n"
		.'<p>'.i18n::c('It seems to be one of the Laws of the Internet that every site of any importance must have an email address for the webmaster. Far be it from us to break the trend; so, if you have something vitally important to tell the folks who designed this place, send a message at the following address.').'</p>'."\n"
		.'<p class="indent">'.i18n::c('Webmaster: [parameter=site_email]').'</p>'."\n"
		.'[note]'.i18n::c('Due to the large amount of e-mail that we get, we can NOT guarantee a response! This is especially true if your e-mail concerns issues we can\'t really help you with.').'[/note]'."\n"
		.'[title]'.i18n::c('How you can help').'[/title]'."\n"
		.'<p>'.i18n::c('We are proud of this server, but it certainly isn\'t perfect. We\'re always looking for ways to improve the site. So, if you\'ve got any great ideas, then please let us know about them. Or, if you know of some important information that isn\'t listed here, email the author and tell them you want it listed on this site! It\'ll help make this server a better site for the whole community - and that\'s who we\'re here to serve.').'</p>'."\n"
		.'[title]'.i18n::c('Legal Stuff').'[/title]'."\n"
		.'<p>'.i18n::c('Be nice to other people, don\'t spam them, and don\'t do anything bad with any information you might get from this site.').'</p>'."\n"
		.'<p>'.i18n::c('All content, including but not limited to user reviews, application listings and information contained therein, is owned by [parameter=site_owner] and may not be copied without permission. However, the above content my be freely modified and/or removed by the person or persons responsible for submitting it.').'</p>'."\n"
		.'<p>'.i18n::c('[parameter=site_owner] is not responsible for the content of information submitted to the site. [parameter=site_owner] is not responsible for any content contained on other sites that may link to or be linked to [parameter=site_name]. As such, the existence of a link to another site on [parameter=site_name] does not express endorsement by [parameter=site_owner] of said site or its contents.').'</p>'."\n"
		.'<p>'.i18n::c('All other trademarks, icons, and logos, shown or mentioned, are the property of their respective owners, including those associated with any solutions listed in [parameter=site_name]. Although [parameter=site_owner] does not own and is not responsible for all of the content on the site, we reserve the right to edit or remove any content at any time in any way we deem necessary, without any notification whatselver to the owner(s) of that content, to keep it better in line with our stated and/or unstated policies. [parameter=site_owner] is not responsible for any copyright laws violated by the applications listed herein, although we will do everything we can do resolve any disputes that may arise in this area.').'</p>'."\n";
	$fields['locked'] = 'Y'; // only associates can change this page
	$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
	if(Articles::post($fields))
		$text .= sprintf(i18n::s('An article %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'cover' article - basic data
if(Articles::get('cover'))
	$text .= i18n::s('A cover article already exists.').BR."\n";
elseif($anchor = Sections::lookup('covers')) {
	$fields = array();
	$fields['anchor'] = $anchor;
	$fields['nick_name'] = 'cover';
	$fields['title'] = i18n::c('Welcome!');
	$fields['introduction'] = i18n::c('Cover article');
	$fields['description'] = i18n::s("This is the cover page posted in the [link=global section]sections/view.php?id=global[/link].\nVisit the section to change any global page, such as this one, the about page, or the privacy page.\nIf you don't know how to proceed, visit the [link=help index]help.php[/link].");
	$fields['locked'] = 'Y'; // only associates can change this page
	$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
	if(Articles::post($fields))
		$text .= sprintf(i18n::s('An article %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'extra_rss' article - basic data
if(Articles::get('extra_rss'))
	$text .= i18n::s('A RSS feeding extra box already exists.').BR."\n";
elseif($anchor = Sections::lookup('extra_boxes')) {
	$fields = array();
	$fields['anchor'] = $anchor;
	$fields['nick_name'] = 'extra_rss';
	$fields['title'] = i18n::c('Stay tuned');
	$fields['introduction'] = '';
	$fields['description'] = Skin::build_link('feeds/rss_2.0.php', i18n::c('Recent pages'), 'xml')
		.BR.Skin::build_link('feeds/', i18n::c('Information channels'), 'shortcut');
	$fields['locked'] = 'Y'; // only associates can change this page
	$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
	if(Articles::post($fields))
		$text .= sprintf(i18n::s('An article %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'menu' article - basic data
if(Articles::get('menu'))
	$text .= i18n::s('A navigation menu already exists.').BR."\n";
elseif($anchor = Sections::lookup('global')) {
	$fields = array();
	$fields['anchor'] = $anchor;
	$fields['nick_name'] = 'menu';
	$fields['title'] = i18n::c('Menu');
	$fields['active_set'] = 'N'; // this page is integrated into every page anyway
	$fields['introduction'] = '';
	$fields['description'] = '[search]'."\n"
		.'[menu='.i18n::c('Home').']'.$context['url_to_root'].'[/menu]'."\n"
		.'[submenu='.i18n::c('Site Map').']sections/[/submenu]'."\n"
		.'[submenu='.i18n::c('Categories').']categories/[/submenu]'."\n"
		.'[submenu='.i18n::c('Users').']users/[/submenu]'."\n"
		.'[submenu='.i18n::c('Help').']help.php[/submenu]'."\n";
	$fields['locked'] = 'Y'; // only associates can change this page
	$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
	if(Articles::post($fields))
		$text .= sprintf(i18n::s('An article %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'privacy' article
if(Articles::get('privacy'))
	$text .= i18n::s('A privacy disclaimer already exists.').BR."\n";
elseif($anchor = Sections::lookup('global')) {
	$fields = array();
	$fields['anchor'] = $anchor;
	$fields['nick_name'] = 'privacy';
	$fields['title'] = i18n::c('Privacy Policy');
	$fields['description'] = '[toc]'."\n"
		.'<p>'.i18n::c('We respect your privacy! Any and all information collected at this site will be kept strictly confidential and will not be sold, reused, rented, loaned or otherwise disclosed. Any information you give to us will be held with the utmost care, and will not be used in ways that you have not consented to. Read on for more specific information.').'</p>'."\n"
		.'[title]'.i18n::c('User Information').'[/title]'."\n"
		.'<p>'.i18n::c('In order to make certain parts of this server work, some sections of the site require you to give us your e-mail address and/or other types of personal information (or may do so in the future). We do not sell, rent, loan, trade, or lease any personal information collected at our site, including survey forms or email lists. In other words, your information is safe with us.').'</p>'."\n"
		.'[title]'.i18n::c('Mailings').'[/title]'."\n"
		.'<p>'.i18n::c('In some cases we will use your email address internally, both for identification purposes and to email you updates related to your pages. Your explicit approval is required to mail you our periodic newsletter. At any time you can easily configure your account to enable or to disable such mailings. We <i>may</i>, in certain cases, send you email updates you haven\'t specifically requested to receive; if and when we do this, we won\'t do it without a good reason (and this certainly won\'t serve as our excuse to spam you.)').'</p>'."\n"
		.'[title]'.i18n::c('Web Logs').'[/title]'."\n"
		.'<p>'.i18n::c('We occasionally analyzes our website logs to constantly improve the value of the content available on our website. Our website logs are not personally identifiable, and we make no attempt to link them with the individuals that actually browse the site.').'</p>'."\n"
		.'[title]'.i18n::c('Cookies').'[/title]'."\n"
		.'<p>'.i18n::c('Although some Internet users think cookies are a serious privacy issue, as web designers we think that they come in darned handy. This site uses cookies for basic account identification purposes, but that\'s as far as we go. We won\'t use any information from cookies to track your browsing sessions, attempt to extract personal information from them that we wouldn\'t otherwise have access to, or do any other naughty things with your cookies. If you don\'t agree with our use of cookies, you can configure most web browsers not to accept them. Even without a cookie, a significant part of this server will still be accessible to you (although you will lose the ability to do anything that requires you to be logged into the server).').'</p>'."\n"
		.'[title]'.i18n::c('Passwords').'[/title]'."\n"
		.'<p>'.i18n::c('This server requires you to define and enter passwords to access certain areas of the site. In case this conjures up images of crackers breaking into our databases and getting your passwords, you don\'t have to worry about it. Passwords are stored in an encrypted format, so that <i>we</i> can\'t even look at them. This encryption <i>is</i> breakable, but it takes a lot of effort and computing time (days or weeks to crack a single password) so for all intents and purposes, your passwords are safe here. The downside to this, of course, is that if you lose or forget your password we can\'t get it back for you. We\'ve provided a method to reset your password if this happens, but as with any other password, it\'s best not to forget in the first place.').'</p>'."\n"
		.'[title]'.i18n::c('Posted material').'[/title]'."\n"
		.'<p>'.i18n::c('Posted material is, of course, not private; several persons can look at them. But this is as good a time as any to point out that we own and retain control of whatever records you enter into the system. This means that we reserve the right to view and modify your articles, files, links, comments, etc. and we exercise this right. Most of the time we do this to fix trivial things; for example, if you post a page into an incorrect section, made a really obvious typo, or put in something really inappropriate, then we might modify it. But in any case, you should be aware that this could happen. FYI, <i>we</i> get to decide what\'s inappropriate, but you should all know your netiquette by now, right?  :-)').'</p>'."\n"
		.'<hr/><p>'.i18n::c('If you have any questions or comments about our privacy policy, or would like more information about a particular category, please [article=about, get in touch] with us.').'</p>'."\n";
	$fields['locked'] = 'Y'; // only associates can change this page
	$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
	if(Articles::post($fields))
		$text .= sprintf(i18n::s('An article %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}


// clear the full cache
Cache::clear();

// report on actions performed
if($included)
	echo $text;
else {
	$context['text'] .= $text;
	$menu = array('articles/' => i18n::s('All pages'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');
	render_skin();
}
?>