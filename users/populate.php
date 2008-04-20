<?php
/**
 * populate users
 *
 * Create sample accounts to play with several profiles.
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

// load localized strings
i18n::bind('users');

// clear the cache for users
Cache::clear('users');

// this page is dedicated to users
$text = '';

// 'editor' user
if(Users::get('editor'))
	$text .= i18n::s('A \'editor\' account already exists.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'editor';
	$fields['password'] = 'editor';
	$fields['confirm'] = 'editor';
	$fields['introduction'] = i18n::c('Sample editor profile');
	$fields['description'] = i18n::c('Authenticate with this account to experience what a regular editor sees from your site.');
	$fields['capability'] = 'M';
	if(Users::post($fields))
		$text .= sprintf(i18n::s('A user profile %s has been created, with the password %s.'), $fields['nick_name'], $fields['password']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'member' user
if(Users::get('member'))
	$text .= i18n::s('A \'member\' account already exists.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'member';
	$fields['password'] = 'member';
	$fields['confirm'] = 'member';
	$fields['introduction'] = i18n::c('Sample member profile');
	$fields['description'] = i18n::c('Authenticate with this account to experience what a regular member sees from your site.');
	$fields['capability'] = 'M';
	if(Users::post($fields))
		$text .= sprintf(i18n::s('A user profile %s has been created, with the password %s.'), $fields['nick_name'], $fields['password']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// 'subscriber' user
if(Users::get('subscriber'))
	$text .= i18n::s('A \'subscriber\' account already exists.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'subscriber';
	$fields['password'] = 'subscriber';
	$fields['confirm'] = 'subscriber';
	$fields['introduction'] = i18n::c('Sample subscriber profile');
	$fields['description'] = i18n::c('Authenticate with this account to experience what a regular subscriber sees from your site.');
	$fields['capability'] = 'S';
	if(Users::post($fields))
		$text .= sprintf(i18n::s('A user profile %s has been created, with the password %s.'), $fields['nick_name'], $fields['password']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// print error message, if any
echo $text;

?>