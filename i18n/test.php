<?php
/**
 * test i18n operation
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load localized strings
i18n::bind('i18n');

// load the skin
load_skin('i18n');

// page title
$context['page_title'] = i18n::s('Test i18n');

// the date of last modification
if(Surfer::is_associate())
	$context['text'] .= '<p>'.i18n::s('Edited').' '.Skin::build_date(getlastmod()).'</p>';

//
// translations based on surfer preferences
//
$context['text'] .= Skin::build_block(i18n::s('Translation to surfer language'), 'title');

// simple label
$context['text'] .= '<p>'.i18n::s('This is a simple label, according to user preference').'</p>';

// complex label
$context['text'] .= '<p>'.sprintf(i18n::s('This string, using surfer language, allows for argument reordering: %2$s %1$s'), 'hello', 'world').'</p>';

// plural label for one item
$context['text'] .= '<p>'.sprintf(i18n::ns('There is %d item', 'There are %d items', 1), 1).'</p>';

// plural label for several items
$context['text'] .= '<p>'.sprintf(i18n::ns('There is %d item', 'There are %d items', 7), 7).'</p>';

// descriptive text
$context['text'] .= i18n::s('<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p><p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>');

//
// translations based on community preferences
//
$context['text'] .= Skin::build_block(i18n::c('Translation to community language'), 'title');

// simple label, translated using community preference
$context['text'] .= '<p>'.i18n::c('This is a simple label, according to community preference').'</p>';

// complex label, translated using community preference
$context['text'] .= '<p>'.sprintf(i18n::c('This string, using community language, allows for argument reordering: %2$s %1$s'), 'hello', 'world').'</p>';

// plural label for one item
$context['text'] .= '<p>'.sprintf(i18n::nc('There is %d item', 'There are %d items', 1), 1).'</p>';

// plural label for several items
$context['text'] .= '<p>'.sprintf(i18n::nc('There is %d item', 'There are %d items', 7), 7).'</p>';

// descriptive text
$context['text'] .= i18n::c('<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p><p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>');

// render the skin
render_skin();

?>