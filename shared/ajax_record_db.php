<?php
/**
 * update a value in a yacs record
 * called by view.php scripts to update record after inline editing
 *
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

include_once '../shared/global.php';
include_once $context['path_to_root'].'shared/surfer.php';
include_once $context['path_to_root'].'shared/anchors.php';
include_once $context['path_to_root'].'shared/codes.php';

load_skin(); // needed to access Skin functions for Codes::beautify

$user_id = Surfer::get_id();
$user_name = Surfer::get_name();
$user_address = Surfer::get_email_address();

$anchor =& Anchors::get($_REQUEST['current_anchor']);

if ($anchor && $anchor->is_assigned($user_id)) {

  $class = $_REQUEST['current_anchor_class'];
  include_once $context['path_to_root'].$class.'/'.$class.'.php';
  eval('$record = '.$class.'::get($anchor->item[\'id\']);');

  if ($_REQUEST['key']=='descr') $key = 'description';
  else $key = $_REQUEST['key'];
  
  $record[$key] = $_REQUEST['value'];
  
	$record['edit_name'] = SQL::escape($user_name);
	$record['edit_id'] = SQL::escape($user_id);
	$record['edit_address'] = SQL::escape($user_address);
	$record['edit_action'] = $anchor->get_type().':update';
	$record['edit_date'] = SQL::escape(gmstrftime('%Y-%m-%d %H:%M:%S'));

  eval($class.'::put($record);');
  echo Codes::beautify($_REQUEST['value']);
}
else
  echo Codes::beautify($_REQUEST['value']);
?>