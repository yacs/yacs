<?php
/**
 * display some recommendations
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Additional information';
$local['label_fr'] = 'Informations compl&eacute;mentaires';
echo '<p>'.i18n::user('label')."</p>\n";

$local['label_en'] = 'After the end of the upgrade, please rebuild the .htaccess file from the Control Panel.';
$local['label_fr'] = 'Lorsque la mise &agrave; jour sera termin&eacute;e, merci de reconstruire le fichier .htaccess &agrave; partir du Panneau de Configuration.';
echo '<p style="color: red;">'.i18n::user('label')."</p>\n";

?>