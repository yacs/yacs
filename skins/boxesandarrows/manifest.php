<?php
/**
 * the skin used at boxesandarrows
 *
 * @link http://www.boxesandarrows.com/
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
if(count(get_included_files()) < 3) {
	echo 'Script must be included';
	return;
}

// let share this skin
global $skins;
$skins['skins/boxesandarrows'] = array(
	'label_en' => 'boxesandarrows',
	'description_en' => 'This skin has a modern layout perfectly adapted to weblogs.'
		.' This style has been adapted from [link=boxes&arrows]http://www.boxesandarrows.com/[/link]',
	'description_fr' => 'Ce style est adapt&eacute; du c&eacute;l&egrave;bre site [link=boxes&arrows]http://www.boxesandarrows.com/[/link].',
	'thumbnail' => 'preview.jpg',
	'home_url' => 'http://www.yetanothercommunitysystem.com/' );
?>