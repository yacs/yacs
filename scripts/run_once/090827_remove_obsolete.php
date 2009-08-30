<?php
/**
 * remove old files, if any
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Remove obsoleted files';
$local['label_fr'] = 'Suppression des fichiers inutiles';
echo get_local('label')."<br />\n";
$count = 0;

// international files have been moved away
if($items=Safe::glob($context['path_to_root'].'temporary/cache_*.mo.php')) {

	foreach($items as $name) {
		if(Safe::unlink($name)) {
			$local['error_en'] = substr($name, strlen($context['path_to_root'])).' has been removed';
			$local['error_fr'] = substr($name, strlen($context['path_to_root'])).' a &eacute;t&eacute; supprim&eacute;';
			echo get_local('error')."<br />\n";
			$count += 1;
		}

	}

}

// files to delete, from root path
$files = array();
$files[] = 'articles/fetch_for_palm.php';
$files[] = 'images/set_as_bullet.php';
$files[] = 'included/php-pdb.php';
$files[] = 'included/php-pdb_doc.php';
$files[] = 'included/php-pdb_html.php';
$files[] = 'skins/boxesandarrows/icons/articles/hot_thread.gif';
$files[] = 'skins/boxesandarrows/icons/articles/locked_thread.gif';
$files[] = 'skins/boxesandarrows/icons/articles/new_thread.gif';
$files[] = 'skins/boxesandarrows/icons/articles/poll.gif';
$files[] = 'skins/boxesandarrows/icons/articles/sticky_locked_thread.gif';
$files[] = 'skins/boxesandarrows/icons/articles/sticky_thread.gif';
$files[] = 'skins/boxesandarrows/icons/articles/thread.gif';
$files[] = 'skins/boxesandarrows/icons/articles/very_hot_thread.gif';
$files[] = 'skins/boxesandarrows/icons/comments/delete.gif';
$files[] = 'skins/boxesandarrows/icons/comments/edit.gif';
$files[] = 'skins/boxesandarrows/icons/comments/new.gif';
$files[] = 'skins/boxesandarrows/icons/comments/quote.gif';
$files[] = 'skins/boxesandarrows/icons/files/download.gif';
$files[] = 'skins/boxesandarrows/icons/files/play.gif';
$files[] = 'skins/boxesandarrows/icons/links/new.gif';
$files[] = 'skins/boxesandarrows/icons/links/trackback.gif';
$files[] = 'skins/boxesandarrows/icons/standards/atom_0.3.png';
$files[] = 'skins/boxesandarrows/icons/standards/opml.png';
$files[] = 'skins/boxesandarrows/icons/standards/rss_0.9.png';
$files[] = 'skins/boxesandarrows/icons/standards/rss_1.0.png';
$files[] = 'skins/boxesandarrows/icons/standards/rss_2.0.png';
$files[] = 'skins/boxesandarrows/icons/tools/comment.gif';
$files[] = 'skins/boxesandarrows/icons/tools/file.gif';
$files[] = 'skins/boxesandarrows/icons/tools/image.gif';
$files[] = 'skins/boxesandarrows/icons/tools/link.gif';
$files[] = 'skins/boxesandarrows/icons/tools/mail.gif';
$files[] = 'skins/boxesandarrows/icons/tools/palm.gif';
$files[] = 'skins/boxesandarrows/icons/tools/pdf.gif';
$files[] = 'skins/boxesandarrows/icons/tools/print.gif';
$files[] = 'skins/boxesandarrows/icons/tools/watch.gif';
$files[] = 'skins/boxesandarrows/icons/tools/word.gif';
$files[] = 'skins/digital/icons/articles/hot_thread.gif';
$files[] = 'skins/digital/icons/articles/locked_thread.gif';
$files[] = 'skins/digital/icons/articles/new_thread.gif';
$files[] = 'skins/digital/icons/articles/poll.gif';
$files[] = 'skins/digital/icons/articles/sticky_locked_thread.gif';
$files[] = 'skins/digital/icons/articles/sticky_thread.gif';
$files[] = 'skins/digital/icons/articles/thread.gif';
$files[] = 'skins/digital/icons/articles/very_hot_thread.gif';
$files[] = 'skins/digital/icons/comments/delete.gif';
$files[] = 'skins/digital/icons/comments/edit.gif';
$files[] = 'skins/digital/icons/comments/new.gif';
$files[] = 'skins/digital/icons/comments/quote.gif';
$files[] = 'skins/digital/icons/files/download.gif';
$files[] = 'skins/digital/icons/files/play.gif';
$files[] = 'skins/digital/icons/links/new.gif';
$files[] = 'skins/digital/icons/links/trackback.gif';
$files[] = 'skins/digital/icons/standards/atom_0.3.png';
$files[] = 'skins/digital/icons/standards/opml.png';
$files[] = 'skins/digital/icons/standards/rss_0.9.png';
$files[] = 'skins/digital/icons/standards/rss_1.0.png';
$files[] = 'skins/digital/icons/standards/rss_2.0.png';
$files[] = 'skins/digital/icons/tools/comment.gif';
$files[] = 'skins/digital/icons/tools/file.gif';
$files[] = 'skins/digital/icons/tools/image.gif';
$files[] = 'skins/digital/icons/tools/link.gif';
$files[] = 'skins/digital/icons/tools/mail.gif';
$files[] = 'skins/digital/icons/tools/palm.gif';
$files[] = 'skins/digital/icons/tools/pdf.gif';
$files[] = 'skins/digital/icons/tools/print.gif';
$files[] = 'skins/digital/icons/tools/watch.gif';
$files[] = 'skins/digital/icons/tools/word.gif';
$files[] = 'skins/images/articles/add.png';
$files[] = 'skins/images/articles/assign.png';
$files[] = 'skins/images/articles/delete.png';
$files[] = 'skins/images/articles/duplicate.png';
$files[] = 'skins/images/articles/edit.png';
$files[] = 'skins/images/articles/lock.png';
$files[] = 'skins/images/articles/publish.png';
$files[] = 'skins/images/articles/stamp.png';
$files[] = 'skins/images/articles/unlock.png';
$files[] = 'skins/images/articles/unpublish.png';
$files[] = 'skins/images/articles/versions.png';
$files[] = 'skins/images/comments/add.png';
$files[] = 'skins/images/comments/delete.png';
$files[] = 'skins/images/comments/edit.png';
$files[] = 'skins/images/comments/list.png';
$files[] = 'skins/images/images/add.png';
$files[] = 'skins/images/sections/add.png';
$files[] = 'skins/images/sections/assign.png';
$files[] = 'skins/images/sections/delete.png';
$files[] = 'skins/images/sections/edit.png';
$files[] = 'skins/images/sections/email.png';
$files[] = 'skins/images/sections/invite.png';
$files[] = 'skins/images/sections/lock.png';
$files[] = 'skins/images/sections/manage.png';
$files[] = 'skins/images/sections/unlock.png';
$files[] = 'skins/images/sections/versions.png';
$files[] = 'skins/joi/icons/articles/hot_thread.gif';
$files[] = 'skins/joi/icons/articles/locked_thread.gif';
$files[] = 'skins/joi/icons/articles/new_thread.gif';
$files[] = 'skins/joi/icons/articles/poll.gif';
$files[] = 'skins/joi/icons/articles/sticky_locked_thread.gif';
$files[] = 'skins/joi/icons/articles/sticky_thread.gif';
$files[] = 'skins/joi/icons/articles/thread.gif';
$files[] = 'skins/joi/icons/articles/very_hot_thread.gif';
$files[] = 'skins/joi/icons/comments/delete.gif';
$files[] = 'skins/joi/icons/comments/edit.gif';
$files[] = 'skins/joi/icons/comments/new.gif';
$files[] = 'skins/joi/icons/comments/quote.gif';
$files[] = 'skins/joi/icons/files/download.gif';
$files[] = 'skins/joi/icons/files/play.gif';
$files[] = 'skins/joi/icons/links/new.gif';
$files[] = 'skins/joi/icons/links/trackback.gif';
$files[] = 'skins/joi/icons/standards/atom_0.3.png';
$files[] = 'skins/joi/icons/standards/opml.png';
$files[] = 'skins/joi/icons/standards/rss_0.9.png';
$files[] = 'skins/joi/icons/standards/rss_1.0.png';
$files[] = 'skins/joi/icons/standards/rss_2.0.png';
$files[] = 'skins/joi/icons/tools/comment.gif';
$files[] = 'skins/joi/icons/tools/file.gif';
$files[] = 'skins/joi/icons/tools/image.gif';
$files[] = 'skins/joi/icons/tools/link.gif';
$files[] = 'skins/joi/icons/tools/mail.gif';
$files[] = 'skins/joi/icons/tools/palm.gif';
$files[] = 'skins/joi/icons/tools/pdf.gif';
$files[] = 'skins/joi/icons/tools/print.gif';
$files[] = 'skins/joi/icons/tools/watch.gif';
$files[] = 'skins/joi/icons/tools/word.gif';
$files[] = 'skins/skeleton/icons/articles/hot_thread.gif';
$files[] = 'skins/skeleton/icons/articles/locked_thread.gif';
$files[] = 'skins/skeleton/icons/articles/new_thread.gif';
$files[] = 'skins/skeleton/icons/articles/poll.gif';
$files[] = 'skins/skeleton/icons/articles/sticky_locked_thread.gif';
$files[] = 'skins/skeleton/icons/articles/sticky_thread.gif';
$files[] = 'skins/skeleton/icons/articles/very_hot_thread.gif';
$files[] = 'skins/skeleton/icons/articles/thread.gif';
$files[] = 'skins/skeleton/icons/comments/delete.gif';
$files[] = 'skins/skeleton/icons/comments/edit.gif';
$files[] = 'skins/skeleton/icons/comments/new.gif';
$files[] = 'skins/skeleton/icons/comments/quote.gif';
$files[] = 'skins/skeleton/icons/files/download.gif';
$files[] = 'skins/skeleton/icons/files/play.gif';
$files[] = 'skins/skeleton/icons/links/new.gif';
$files[] = 'skins/skeleton/icons/links/trackback.gif';
$files[] = 'skins/skeleton/icons/standards/atom_0.3.png';
$files[] = 'skins/skeleton/icons/standards/opml.png';
$files[] = 'skins/skeleton/icons/standards/rss_0.9.png';
$files[] = 'skins/skeleton/icons/standards/rss_1.0.png';
$files[] = 'skins/skeleton/icons/standards/rss_2.0.png';
$files[] = 'skins/skeleton/icons/tools/comment.gif';
$files[] = 'skins/skeleton/icons/tools/file.gif';
$files[] = 'skins/skeleton/icons/tools/image.gif';
$files[] = 'skins/skeleton/icons/tools/link.gif';
$files[] = 'skins/skeleton/icons/tools/mail.gif';
$files[] = 'skins/skeleton/icons/tools/palm.gif';
$files[] = 'skins/skeleton/icons/tools/pdf.gif';
$files[] = 'skins/skeleton/icons/tools/print.gif';
$files[] = 'skins/skeleton/icons/tools/watch.gif';
$files[] = 'skins/skeleton/icons/tools/word.gif';

// process every file
foreach($files as $file) {

	// file does not exist
	if(!file_exists($context['path_to_root'].$file))
		continue;

	// remove it
	if(Safe::unlink($context['path_to_root'].$file)) {
		$local['error_en'] = $file.' has been removed';
		$local['error_fr'] = $file.' a &eacute;t&eacute; supprim&eacute;';
		echo get_local('error')."<br />\n";
	}

	// next one
	$count += 1;
	Safe::set_time_limit(30);
}

// basic reporting
$local['label_en'] = 'files have been processed';
$local['label_fr'] = 'fichiers ont &eacute;t&eacute; trait&eacute;s';
echo $count.' '.get_local('label')."<br />\n";
?>