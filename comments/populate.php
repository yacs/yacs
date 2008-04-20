<?php
/**
 * populate comments
 *
 * Creates one comment for 'my_article' page.
 * Also add many comments to 'my_blog_page', 'my_manual_page', 'my_jive_thread', 'my_wiki_page', and 'my_yabb_thread' pages.
 *
 * @see control/populate.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// if it was a HEAD request, stop here
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// stop hackers
defined('YACS') or exit('Script must be included');

include_once $context['path_to_root'].'comments/comments.php';

// load localized strings
i18n::bind('comments');

// clear the cache for comments
Cache::clear('comments');

// distribute names
$names = array( 'Alice', 'Bob', 'Charly' );

$text = '';

// add a sample comment to 'my_article'
if($anchor = Articles::lookup('my_article')) {
	$fields = array();
	$fields['anchor'] = $anchor;
	$fields['description'] = i18n::c('Hello World!');
	$fields['edit_name'] = $names[ rand(0, 2) ];
	if(Comments::post($fields))
		$text .= i18n::s('A comment has been posted to the sample article.').BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// add sample comments to 'my_blog_page'
if($anchor = Articles::lookup('my_blog_page')) {

	// add a bunch of comments
	for($index = 1; $index <= 50; $index++) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['description'] = sprintf(i18n::c('Comment #%d'), $index);
		$fields['edit_name'] = $names[ rand(0, 2) ];
		if(!Comments::post($fields)) {
			$text .= Skin::error_pop().BR."\n";
			break;
		}
	}

	if($index > 1)
		$text .= i18n::s('Comments have been added to the sample blog entry.').BR."\n";

}

// add sample comments to 'my_jive_thread'
if($anchor = Articles::lookup('my_jive_thread')) {

	// add a bunch of comments
	for($index = 1; $index <= 50; $index++) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['description'] = sprintf(i18n::c('Reply #%d'), $index);
		$fields['edit_name'] = $names[ rand(0, 2) ];
		if(!Comments::post($fields)) {
			$text .= Skin::error_pop().BR."\n";
			break;
		}
	}

	if($index > 1)
		$text .= i18n::s('Replies have been added to the sample jive thread.').BR."\n";

}

// add sample comments to 'my_manual_page'
if($anchor = Articles::lookup('my_manual_page')) {

	// add a bunch of comments
	for($index = 1; $index <= 50; $index++) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['description'] = sprintf(i18n::c('Note #%d'), $index);
		$fields['edit_name'] = $names[ rand(0, 2) ];
		if(!Comments::post($fields)) {
			$text .= Skin::error_pop().BR."\n";
			break;
		}
	}

	if($index > 1)
		$text .= i18n::s('Comments have been added to the sample page in manual.').BR."\n";

}

// add sample comments to 'my_wiki_page'
if($anchor = Articles::lookup('my_wiki_page')) {

	// add a bunch of comments
	for($index = 1; $index <= 50; $index++) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['description'] = sprintf(i18n::c("Note #%d\nfoo bar"), $index);
		$fields['edit_name'] = $names[ rand(0, 2) ];
		if(!Comments::post($fields)) {
			$text .= Skin::error_pop().BR."\n";
			break;
		}
	}

	if($index > 1)
		$text .= i18n::s('Notes have been added to the sample wiki entry.').BR."\n";

}

// add sample comments to 'my_yabb_thread'
if($anchor = Articles::lookup('my_yabb_thread')) {

	// add a bunch of comments
	for($index = 1; $index <= 50; $index++) {
		$fields = array();
		$fields['anchor'] = $anchor;
		$fields['description'] = sprintf(i18n::c('Comment #%d'), $index);
		$fields['edit_name'] = $names[ rand(0, 2) ];
		if(!Comments::post($fields)) {
			$text .= Skin::error_pop().BR."\n";
			break;
		}
	}

	if($index > 1)
		$text .= i18n::s('Comments have been added to the sample yabb thread.').BR."\n";

}

// print messages, if any
echo $text."\n";

?>