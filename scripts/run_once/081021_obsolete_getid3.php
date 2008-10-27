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

// files to delete, from root path
$files = array();
$files[] = 'included/getid3/getid3.lib.php';
$files[] = 'included/getid3/getid3.php';
$files[] = 'included/getid3/module.audio.aac.php';
$files[] = 'included/getid3/module.audio.ac3.php';
$files[] = 'included/getid3/module.audio.au.php';
$files[] = 'included/getid3/module.audio.avr.php';
$files[] = 'included/getid3/module.audio.bonk.php';
$files[] = 'included/getid3/module.audio.flac.php';
$files[] = 'included/getid3/module.audio.la.php';
$files[] = 'included/getid3/module.audio.lpac.php';
$files[] = 'included/getid3/module.audio.midi.php';
$files[] = 'included/getid3/module.audio.mod.php';
$files[] = 'included/getid3/module.audio.monkey.php';
$files[] = 'included/getid3/module.audio.mp3.php';
$files[] = 'included/getid3/module.audio.mpc.php';
$files[] = 'included/getid3/module.audio.ogg.php';
$files[] = 'included/getid3/module.audio.optimfrog.php';
$files[] = 'included/getid3/module.audio.rkau.php';
$files[] = 'included/getid3/module.audio.shorten.php';
$files[] = 'included/getid3/module.audio.tta.php';
$files[] = 'included/getid3/module.audio.voc.php';
$files[] = 'included/getid3/module.audio.vqf.php';
$files[] = 'included/getid3/module.audio.wavpack.php';
$files[] = 'included/getid3/module.audio-video.asf.php';
$files[] = 'included/getid3/module.audio-video.bink.php';
$files[] = 'included/getid3/module.audio-video.flv.php';
$files[] = 'included/getid3/module.audio-video.matroska.php';
$files[] = 'included/getid3/module.audio-video.mpeg.php';
$files[] = 'included/getid3/module.audio-video.nsv.php';
$files[] = 'included/getid3/module.audio-video.quicktime.php';
$files[] = 'included/getid3/module.audio-video.real.php';
$files[] = 'included/getid3/module.audio-video.riff.php';
$files[] = 'included/getid3/module.audio-video.swf.php';
$files[] = 'included/getid3/module.graphic.bmp.php';
$files[] = 'included/getid3/module.graphic.gif.php';
$files[] = 'included/getid3/module.graphic.jpg.php';
$files[] = 'included/getid3/module.graphic.pcd.php';
$files[] = 'included/getid3/module.graphic.png.php';
$files[] = 'included/getid3/module.graphic.tiff.php';
$files[] = 'included/getid3/module.misc.exe.php';
$files[] = 'included/getid3/module.misc.iso.php';
$files[] = 'included/getid3/module.tag.apetag.php';
$files[] = 'included/getid3/module.tag.id3v1.php';
$files[] = 'included/getid3/module.tag.id3v2.php';
$files[] = 'included/getid3/module.tag.lyrics3.php';
$files[] = 'included/helperapps/readme.txt';

// process every file
$count = 0;
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