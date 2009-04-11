<?php
/**
 * the interface to files exposed to RPC
 *
 * @author Bernard Paques
 * @reference
 * @see control/scan.php
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Files_Hook {

	/**
	 * detect streaming completion
	 *
	 * @param array submitted through AJAX
	 * @return string
	 */
	function complete_stream($id) {
		global $context;
		
		if($context['with_debug'] == 'Y')
			logger::debug($id, 'has been streamed by '.Surfer::get_name());
		
		return array('message' => 'ok');
	}
	
}

// stop hackers
defined('YACS') or exit('Script must be included');

// end of video streaming
$hooks[] = array(
	'id'		=> 'file.streamed',
	'type'		=> 'serve',
	'script'	=> 'files/files_hook.php',
	'function'	=> 'Files_Hook::complete_stream',
	'label_en'	=> 'Detect completion of video streaming',
	'label_fr'	=> 'D&eacute;tection des fins de vid&eacute;os',
	'description_en' => 'AJAX transmission to back-end',
	'description_fr' => 'Transmission AJAX vers le serveur',
	'source' => 'http://www.yacs.fr/' );
	
?>