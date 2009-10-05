<?php
/**
 * update reference skins
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Update reference skins';
$local['label_fr'] = 'Mise &agrave; jour des styles de r&eacute;f&eacute;rence';
echo i18n::user('label')."<br />\n";

// the reference server to use
@include_once $context['path_to_root'].'scripts/parameters.include.php';
if(!isset($context['reference_server']) || !$context['reference_server'])
	$context['reference_server'] = 'www.yetanothercommunitysystem.com';

// files to fetch, from root path
unset($files);
$files[] = 'skins/_reference/down.gif';
$files[] = 'skins/_reference/yacs.css';
$files[] = 'skins/_reference/up.gif';
$files[] = 'skins/boxesandarrows/boxesandarrows.css';
$files[] = 'skins/digital/digital.css';
$files[] = 'skins/images/articles/add.gif';
$files[] = 'skins/images/articles/assign.gif';
$files[] = 'skins/images/articles/delete.gif';
$files[] = 'skins/images/articles/duplicate.gif';
$files[] = 'skins/images/articles/edit.gif';
$files[] = 'skins/images/articles/email.gif';
$files[] = 'skins/images/articles/export_pdf.gif';
$files[] = 'skins/images/articles/export_word.gif';
$files[] = 'skins/images/articles/hot_thread.gif';
$files[] = 'skins/images/articles/invite.gif';
$files[] = 'skins/images/articles/lock.gif';
$files[] = 'skins/images/articles/poll.gif';
$files[] = 'skins/images/articles/publish.gif';
$files[] = 'skins/images/articles/stamp.gif';
$files[] = 'skins/images/articles/sticky_thread.gif';
$files[] = 'skins/images/articles/thread.gif';
$files[] = 'skins/images/articles/unlock.gif';
$files[] = 'skins/images/articles/unpublish.gif';
$files[] = 'skins/images/articles/versions.gif';
$files[] = 'skins/images/articles/very_hot_thread.gif';
$files[] = 'skins/images/categories/add.gif';
$files[] = 'skins/images/categories/delete.gif';
$files[] = 'skins/images/categories/edit.gif';
$files[] = 'skins/images/comments/add.gif';
$files[] = 'skins/images/comments/delete.gif';
$files[] = 'skins/images/comments/edit.gif';
$files[] = 'skins/images/comments/list.gif';
$files[] = 'skins/images/comments/promote.gif';
$files[] = 'skins/images/comments/quote.gif';
$files[] = 'skins/images/comments/reply.gif';
$files[] = 'skins/images/feeds/atom.png';
$files[] = 'skins/images/feeds/opml.png';
$files[] = 'skins/images/feeds/rss_0.9.png';
$files[] = 'skins/images/feeds/rss_1.0.png';
$files[] = 'skins/images/feeds/rss_2.0.png';
$files[] = 'skins/images/files/delete.gif';
$files[] = 'skins/images/files/download.gif';
$files[] = 'skins/images/files/edit.gif';
$files[] = 'skins/images/files/list.gif';
$files[] = 'skins/images/files/play.gif';
$files[] = 'skins/images/files/upload.gif';
$files[] = 'skins/images/files/versions.gif';
$files[] = 'skins/images/icons/accordion/minus.jpg';
$files[] = 'skins/images/icons/accordion/plus.jpg';
$files[] = 'skins/images/icons/pagers/twitter.gif';
$files[] = 'skins/images/images/add.gif';
$files[] = 'skins/images/images/delete.gif';
$files[] = 'skins/images/images/edit.gif';
$files[] = 'skins/images/layouts/carrousel_bubble.png';
$files[] = 'skins/images/links/add.gif';
$files[] = 'skins/images/links/delete.gif';
$files[] = 'skins/images/links/edit.gif';
$files[] = 'skins/images/links/list.gif';
$files[] = 'skins/images/sections/add.gif';
$files[] = 'skins/images/sections/assign.gif';
$files[] = 'skins/images/sections/delete.gif';
$files[] = 'skins/images/sections/edit.gif';
$files[] = 'skins/images/sections/email.gif';
$files[] = 'skins/images/sections/invite.gif';
$files[] = 'skins/images/sections/lock.gif';
$files[] = 'skins/images/sections/manage.gif';
$files[] = 'skins/images/sections/select.gif';
$files[] = 'skins/images/sections/unlock.gif';
$files[] = 'skins/images/sections/versions.gif';
$files[] = 'skins/images/smileys/smash.gif';
$files[] = 'skins/images/tools/print.gif';
$files[] = 'skins/images/tools/trackback.gif';
$files[] = 'skins/images/tools/watch.gif';
$files[] = 'skins/images/users/add.gif';
$files[] = 'skins/images/users/delete.gif';
$files[] = 'skins/images/users/edit.gif';
$files[] = 'skins/images/users/password.gif';
$files[] = 'skins/images/users/vcard.gif';
$files[] = 'skins/images/users/watch.gif';
$files[] = 'skins/joi/joi.css';
$files[] = 'skins/skeleton/skeleton.css';

// process every file
$count = 0;
foreach($files as $file) {

	// content of the updated file
	$content = '';

	// expected location in staging repository
	$local_reference = $context['path_to_root'].'scripts/staging/'.$file;

	// expected link from reference server
	include_once $context['path_to_root'].'links/link.php';

	// don't execute PHP scripts, just get them
	if(preg_match('/\.php$/i', $file))
		$remote_reference = 'http://'.$context['reference_server'].'/scripts/fetch.php?script='.urlencode($file);

	// fetch other files from remote reference store
	else
		$remote_reference = 'http://'.$context['reference_server'].'/scripts/reference/'.$file;

	// get the file locally
	if(file_exists($local_reference))
		$content = Safe::file_get_contents($local_reference);

	// or get the file from reference server
	elseif(($content = Link::fetch($remote_reference)) === FALSE) {
		$local['error_en'] = 'Unable to get '.$file;
		$local['error_fr'] = 'Impossible d\'obtenir '.$file;
		echo i18n::user('error')."<br />\n";
	}

	// we have something in hand
	if($content) {

		// create missing directories where applicable
		Safe::make_path(dirname($file));

		// create backups, if possible
		if(file_exists($context['path_to_root'].$file)) {
			Safe::unlink($context['path_to_root'].$file.'.bak');
			Safe::rename($context['path_to_root'].$file, $context['path_to_root'].$file.'.bak');
		}

		// update the target file
		if(!Safe::file_put_contents($file, $content)) {
			$local['label_en'] = 'Impossible to write to the file '.$file.'.';
			$local['label_fr'] = 'Impossible d\'&eacute;crire le fichier '.$file.'.';
			echo i18n::user('label')."<br />\n";
		} else {
			$local['label_en'] = 'has been updated';
			$local['label_fr'] = 'a &eacute;t&eacute; mis &agrave; jour';
			echo $file.' '.i18n::user('label')."<br />\n";
		}

	}

	// next one
	$count += 1;
	Safe::set_time_limit(30);
}

// basic reporting
$local['label_en'] = 'files have been processed';
$local['label_fr'] = 'fichiers ont &eacute;t&eacute; trait&eacute;s';
echo $count.' '.i18n::user('label')."<br />\n";
?>