<?php
/**
 * displayed when the server has been switched off
 *
 * Surfers are driven to this page when the file [code]parameters/switch.off[/code] exists.
 *
 * This page is not using skins at all, to avoid collisions with possible
 * updates in the background.
 *
 * Therefore, messages are displayed in several languages.
 *
 * If some parameters are provided in [code]parameters/switch.include.php[/code],
 * they are used as follow:
 *
 * [*] If a redirected server has been defined, a link to it is displayed.
 *
 * [*] Else a message asks for patience.
 *
 * [*] If some contact information is available, it is displayed as well.
 *
 * Parameters are captured while switching off.
 *
 * @see control/switch.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// if it was a HEAD request, stop here
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// load switch parameters, if any
@include_once '../parameters/switch.include.php';

// the server has moved
if(isset($context['switch_target']) && $context['switch_target']) {

	// en
	echo '<html>'."\n"
		.'<title>We have moved</title>'."\n"
		.'<body>'."\n"
		.'<h1>We have moved</h1>'."\n"
		.'Content of this server has been moved to the following address: '."\n"
		.' <b><a href="'.$context['switch_target'].'">'.$context['switch_target'].'</a></b>'."\n"
		.'<p>Please update your bookmarks.'."\n"
		.' We apologize for inconvenience and thank you for your fidelity.</p>'."\n";

	if(isset($context['switch_contact']) && $context['switch_contact'])
		echo '<p>For more information you can can use following contact information: '.$context['switch_contact']."</p>\n";

	// fr
	echo '<h2>Nous avons d&eacute;m&eacute;nag&eacute;</h2>'."\n"
		.'Le contenu de ce serveur a &eacute;t&eacute; d&eacute;plac&eacute; &agrave; l\'adresse suivante&nbsp;: '."\n"
		.' <b><a href="'.$context['switch_target'].'">'.$context['switch_target'].'</a></b>'."\n"
		.'<p>Merci de mettre vos signets &agrave; jour.'."\n"
		.' Nous nous excusons par avance des d&eacute;sagr&eacute;ments dus &agrave; cet arr&ecirc;t et vous remercions de votre fid&eacute;lit&eacute;.</p>'."\n";

	if(isset($context['switch_contact']) && $context['switch_contact'])
		echo '<p>Pour recevoir plus d\'information vous pouvez utiliser le contact suivant : '.$context['switch_contact']."</p>\n";

// the server is closed
} else {

	// link to front page -- url_to_home is unavailable
	include_once '../parameters/control.include.php';
	$link = '';
	if(isset($context['url_to_root']))
		$link = $context['url_to_root'];
	if(!$link)
		$link = '/';

	// en
	echo '<html>'."\n"
		.'<title>We are closed</title>'."\n"
		.'<body>'."\n"
		.'<h1>We are closed</h1>'."\n"
		.'This server has been temporarily closed because of a maintenance operation.'."\n"
		.' Usually such an operation does not require a lot of time so please come back soon.'."\n"
		.' We apologize for inconvenience and thank you for your fidelity.'."\n"
		.'<p><a href="'.$link.'">Try again</a></p>'."\n";

	if(isset($context['switch_contact']) && $context['switch_contact'])
		echo '<p>In case of emergency you can can use following contact information: '.$context['switch_contact']."</p>\n";

	// fr
	echo '<h2>Le serveur est ferm&eacute;</h2>'."\n"
		.'Ce serveur est ferm&eacute; temporairement &agrave; cause d\'une op&eacute;ration de maintenance.'."\n"
		.' Habituellement ce genre d\'op&eacute;ration dure peu de temps.'."\n"
		.' Nous vous remercions de revenir nous visiter un peu plus tard.'."\n"
		.' Nous nous excusons par avance des d&eacute;sagr&eacute;ments dus &agrave; ce changement et vous remercions de votre fid&eacute;lit&eacute;.'."\n"
		.'<p><a href="'.$link.'">Essayer de nouveau</a></p>'."\n";

	if(isset($context['switch_contact']) && $context['switch_contact'])
		echo '<p>En cas d\'urgence vous pouvez utiliser le contact suivant : '.$context['switch_contact']."</p>\n";
}

// page end
echo '</body>'
	.'</html>';

?>