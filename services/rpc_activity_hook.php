<?php
/**
 * Remember some on-line activity
 *
 * A service aiming to remember some activity through JSON-RPC.
 *
 * Syntax: activity(anchor, action) returns 'OK'
 *
 * This can be used for example to gather activity information captured from within
 * the browser, and to transmit it via some AJAX call, like in the following example:
 *
 * [snippet]
 * Yacs.call( { method: 'user.activity', params: { anchor: 'file:123', action: 'fetch' } }, function(s) { if(s.message) { alert(s.message); } } );
 * [/snippet]
 *
 * An example of invocation is available into ##tools/ajax.php##
 *
 * @see tools/ajax.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// server hook to some web service
global $hooks;
$hooks[] = array(
	'id'		=> 'user.activity',
	'type'		=> 'serve',
	'script'	=> 'services/rpc_activity_hook.php',
	'function'	=> 'RPC_Activity::post',
	'label_en'	=> 'Activity tracking',
	'label_fr'	=> "Suivi d'activit&eacute;",
	'description_en' => 'Remember some on-line activity.',
	'description_fr' => "M&eacute;morisation des activit&eacute;s en ligne.",
	'source' => 'http://www.yacs.fr/'
);

class RPC_Activity {

	/**
	 * add one activity record
	 *
	 * @param array e.g., array('anchor' => 'article:123', 'action' => 'edit')
	 * @return mixed some error array, or 'OK'
	 */
	function post($parameters) {
		global $context;

		// look for an anchor
		if(empty($parameters['anchor']))
			return array('code' => -32602, 'message' => 'Invalid param "anchor"');

		// look for an action
		if(empty($parameters['action']))
			return array('code' => -32602, 'message' => 'Invalid param "action"');

		// save this in the database
		include $context['path_to_root'].'users/activities.php';
		Activities::post($parameters['anchor'], $parameters['action']);

		// done
		return 'OK';
	}

}

?>