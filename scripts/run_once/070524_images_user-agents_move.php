<?php
/**
 * move images from agents/user-agents to skins/images/user-agents
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Move images related to user agents';
$local['label_fr'] = 'D&eacute;placement des images repr&eacute;sentatives des navigateurs';
echo i18n::l($local, 'label')."<br />\n";

// files to fetch, from root path
unset($files);
$files[] = 'agents/user-agents/altavista.gif';
$files[] = 'agents/user-agents/konqueror.gif';
$files[] = 'agents/user-agents/lynx.gif';
$files[] = 'agents/user-agents/mozilla.gif';
$files[] = 'agents/user-agents/explorer.gif';
$files[] = 'agents/user-agents/netscape.gif';
$files[] = 'agents/user-agents/opera.gif';
$files[] = 'agents/user-agents/question.gif';
$files[] = 'agents/user-agents/webtv.gif';
$files[] = 'agents/user-agents/aix.gif';
$files[] = 'agents/user-agents/be.gif';
$files[] = 'agents/user-agents/bsd.gif';
$files[] = 'agents/user-agents/irix.gif';
$files[] = 'agents/user-agents/linux.gif';
$files[] = 'agents/user-agents/mac.gif';
$files[] = 'agents/user-agents/os2.gif';
$files[] = 'agents/user-agents/sun.gif';
$files[] = 'agents/user-agents/windows.gif';

// the new location for these files
$target = 'skins/images/user-agents';

// create missing directories
Safe::make_path($target);

// process every file
$count = 0;
foreach($files as $file) {

	// get the file locally
	if(file_exists($context['path_to_root'].$file)) {

		// update the target file
		if(!Safe::rename($file, $target.'/'.basename($file))) {
			$local['label_en'] = 'Impossible to move the file '.$file.'.';
			$local['label_fr'] = 'Impossible de d&eacute;placer le fichier '.$file.'.';
			echo i18n::l($local, 'label')."<br />\n";
		} else {
			$local['label_en'] = 'has been moved to';
			$local['label_fr'] = 'a &eacute;t&eacute; d&eacute;plac&eacute vers';
			echo $file.' '.i18n::l($local, 'label').' '.$target."<br />\n";
		}

	}

	// attemp to silently remove the origin directory
	Safe::rmdir(dirname($file));

	// next one
	$count += 1;
	Safe::set_time_limit(30);
}

// basic reporting
$local['label_en'] = 'files have been processed';
$local['label_fr'] = 'fichiers ont été traités';
echo $count.' '.i18n::l($local, 'label')."<br />\n";
?>