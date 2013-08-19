<?php
/**
 * populate basic templates
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Populate templates';
$local['label_fr'] = 'Cr&eacute;ation des mod&egrave;les de page';
echo i18n::user('label')."<br />\n";

// redo the basic steps of data creation
$context['populate_follow_up'] = 'none';
include_once $context['path_to_root'].'control/populate.php';
echo $context['text'];
$context['text'] = '';

?>