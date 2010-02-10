<?php
/**
 * display filters attached to a table
 *
 * @author CityTech
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../shared/xml.php';	// input validation
include_once 'tables.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]) && !isset($context['arguments'][1]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Tables::get($id);

// look for the target anchor on item creation
$target_anchor = NULL;
if(isset($_REQUEST['anchor']))
	$target_anchor = $_REQUEST['anchor'];
if(!isset($target_anchor) && isset($context['arguments'][1]))
	$target_anchor = $context['arguments'][0].':'.$context['arguments'][1];

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']))
	$anchor =& Anchors::get($item['anchor']);
elseif($target_anchor)
	$anchor =& Anchors::get($target_anchor);

// associates and owners can do what they want
if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_owned()))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// do not always show the edition form
$with_form = FALSE;

// load the skin, maybe with a variant
load_skin('tables', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'tables/' => i18n::s('Tables') );

// the title of the page
if(isset($item['id']))
	$context['page_title'] = i18n::s('Edit filters');
else
	$context['page_title'] = i18n::s('Add filters');

// always validate input syntax
if(isset($_REQUEST['introduction']))
	xml::validate($_REQUEST['introduction']);
if(isset($_REQUEST['description']))
	xml::validate($_REQUEST['description']);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an anchor is mandatory
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged()) {

		if(isset($item['id']))
			$link = Tables::get_url($item['id'], 'filters');
		elseif(isset($_REQUEST['anchor']))
			$link = 'tables/filters.php?anchor='.urlencode($_REQUEST['anchor']);
		else
			$link = 'tables/filters.php';

		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode($link));
	}

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// maybe posts are not allowed here
} elseif(!isset($item['id']) && $anchor->has_option('locked') && !Surfer::is_empowered()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('This page has been locked.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	$next = $context['url_to_home'].$context['url_to_root'].$anchor->get_url();

	$item = $_REQUEST;

	// array initialization

	$query_tab = array();


	// array initialization

	$query_tab_new = array();

	if(isset($item['count_tb_1'])) {

	$sqlDelete = "Delete FROM ".SQL::table_name('tables_filters')." WHERE `table_id`='".$item['id']."'";

	SQL::query($sqlDelete);

		for($p=1;$p<=$item['count_tb_1'];$p++) {

			if(isset($item['option_name_'.$p]) && !empty($item['option_name_'.$p])) {


			if(!isset($item['is_remove_'.$p])) {
			$query_tab[] = "INSERT INTO  ".SQL::table_name('tables_filters')." SET \n"
			."table_id=".SQL::escape($item['id']).", \n"
			."option_name='".SQL::escape(isset($_REQUEST['option_name_'.$p]) ? $_REQUEST['option_name_'.$p] : '')."', \n"
			."is_file='".SQL::escape(isset($_REQUEST['is_file_'.$p]) ? $_REQUEST['is_file_'.$p] : 'No')."', \n"
			."file_path='".SQL::escape(isset($_REQUEST['file_path_'.$p]) ? $_REQUEST['file_path_'.$p] : '')."', \n"
			."array_name='".SQL::escape(isset($_REQUEST['array_name_'.$p]) ? $_REQUEST['array_name_'.$p] : '')."', \n"
			."option_sql='".SQL::escape(isset($_REQUEST['option_sql_'.$p]) ? $_REQUEST['option_sql_'.$p] : '')."', \n"
			."table_alies='".SQL::escape(isset($_REQUEST['table_alies_'.$p]) ? $_REQUEST['table_alies_'.$p] : '')."', \n"
			."option_code='".SQL::escape(isset($_REQUEST['option_code_'.$p]) ? $_REQUEST['option_code_'.$p] : '')."', \n"
			."filter_order='".SQL::escape(isset($_REQUEST['filter_order_'.$p]) ? $_REQUEST['filter_order_'.$p] : '1')."' \n";
			   }



			}

		}

	}


	if(isset($item['count_new_tb'])) {

		for($p=1;$p<=$item['count_new_tb'];$p++) {
			if(isset($item['option_name_new_'.$p]) && !empty($item['option_name_new_'.$p])) {

			  if(!isset($item['is_remove_new_'.$p])) {
				$query_tab_new[] = "INSERT INTO  ".SQL::table_name('tables_filters')." SET \n"
				."table_id=".SQL::escape($item['id']).", \n"
				."option_name='".SQL::escape(isset($_REQUEST['option_name_new_'.$p]) ? $_REQUEST['option_name_new_'.$p] : '')."', \n"
				."is_file='".SQL::escape(isset($_REQUEST['is_file_new_'.$p]) ? $_REQUEST['is_file_new_'.$p] : 'No')."', \n"
			    ."file_path='".SQL::escape(isset($_REQUEST['file_path_new_'.$p]) ? $_REQUEST['file_path_new_'.$p] : '')."', \n"
			    ."array_name='".SQL::escape(isset($_REQUEST['array_name_new_'.$p]) ? $_REQUEST['array_name_new_'.$p] : '')."', \n"
				."option_sql='".SQL::escape(isset($_REQUEST['option_sql_new_'.$p]) ? $_REQUEST['option_sql_new_'.$p] : '')."', \n"
				."table_alies='".SQL::escape(isset($_REQUEST['table_alies_new_'.$p]) ? $_REQUEST['table_alies_new_'.$p] : '')."', \n"
				."option_code='".SQL::escape(isset($_REQUEST['option_code_new_'.$p]) ? $_REQUEST['option_code_new_'.$p] : '')."', \n"
				."filter_order='".SQL::escape(isset($_REQUEST['filter_order_new_'.$p]) ? $_REQUEST['filter_order_new_'.$p] : '1')."' \n";
				}

			}

		}

	}


	if(isset($query_tab) && is_array($query_tab)) {

		foreach($query_tab as $query){

		SQL::query($query);

		}
	}

	if(isset($query_tab_new) && is_array($query_tab_new)) {

		foreach($query_tab_new as $query) {

		SQL::query($query);

		}

	}



	$with_form = TRUE;

	Safe::redirect($next);

} else
	$with_form = TRUE;

// display the form
if($with_form) {
	// reference the anchor page
	if(is_object($anchor) && $anchor->is_viewable())
		$context['text'] .= '<p>'.sprintf(i18n::s('In: %s'), Skin::build_link($anchor->get_url(), $anchor->get_title()))."</p>\n";

	// the form to edit an table
	//onsubmit="return validateDocumentPost(this)"

	$context['text'] .= '<form method="post" action="'.$context['script_url'].'"  id="main_form" onsubmit="return checksubmit()"><div>';

	// encode fields
	$fields = array();

	// display info on current version
	if(isset($item['id'])) {

		// the last poster
		if(isset($item['edit_id'])) {
			$text = Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id'])
				.' '.Skin::build_date($item['edit_date']);
			$fields[] = array(i18n::s('Posted by'), $text);
		}
	}




	function get_tab_information($table_id) {


	global $context, $local;

		// sanity check

		if(!$table_id)

			return NULL;


		$tableInformationSql = "SELECT * FROM ".SQL::table_name('tables_filters')." AS tb "

		." WHERE (tb.table_id = ".SQL::escape($table_id).") ORDER BY tb.filter_order,tb.option_name";

		// fetch the first row

		if(!$row =& SQL::query($tableInformationSql))

			return NULL;


			return $row;


	}





			$fields = array();

			$tab_information_tracking_result = get_tab_information($item['id']);

			$tab_information_tracking=array();


			if(SQL::count($tab_information_tracking_result)>0)

			{

				while($each_row=SQL::fetch($tab_information_tracking_result))

				{

					$tab_information_tracking[]=$each_row;

				}

			}


			$i=1;

			if(count($tab_information_tracking)>0)

			{

				#if there is existing contact records, loop through them

				foreach ($tab_information_tracking as $tabinformation_row)

				{


					$tabinformation.='<div class="bottom">';

					$tabinformation.= Skin::build_box("Filter #$i",'', 'header2');

					$fields=array();

                   // Tab Title

					$label = i18n::user('Filter Title<span style="color:#FF0000">*</span>');

					if(!isset($tabinformation_row['option_name']))

					  $tabinformation_row['option_name'] = '';


					$input = '<input type="text" name="option_name_'.$i.'" id="option_name_'.$i.'" value ="'.encode_field($tabinformation_row['option_name']).'" size="55" maxlength="255" />';



					$fields[] = array($label, $input);



					// Indicator for file

				   $label = i18n::user('Is populate from file ?');

                   $checked='';


					if(isset($tabinformation_row['is_file']) && $tabinformation_row['is_file']=='Yes')
					   $checked = 'checked="checked"';


                   $input = '<input type="radio" name="is_file_'.$i.'" value="Yes"  '.$checked.' id="is_file_yes_'.$i.'" onclick="showhide(\''.$i.'\',this.value,\'0\')"/>'.i18n::user('Yes');
				   //onclick="showhide(\''.$i.'\',this.value)"

				   $checked='';

					if(isset($tabinformation_row['is_file']) && $tabinformation_row['is_file']=='No')
				       $checked = 'checked="checked"';

				   $input .= '&nbsp;&nbsp;<input type="radio" name="is_file_'.$i.'" value="No"  '.$checked.' id="is_file_no_'.$i.'" onclick="showhide(\''.$i.'\',this.value,\'0\')" />'.i18n::user('No');

				   //$hint = i18n::user('Type some letters of the name and select in the list');

					$fields[] = array($label, $input);




                   // File path

					$label = i18n::user('File path');

					if(!isset($tabinformation_row['file_path']))

					  $tabinformation_row['file_path'] = '';

					if(isset($tabinformation_row['is_file']) && $tabinformation_row['is_file']=='No')
					{	$disable='disabled';
						$bg_style='style="background-color:#64605B"';
					}
					else
					{
						$disable='';
						$bg_style='style="background-color:#FFFFFF"';
					}


					$input = '<input type="text" name="file_path_'.$i.'" id="file_path_'.$i.'" value ="'.encode_field($tabinformation_row['file_path']).'" size="55" maxlength="255" '.$disable.' '.$bg_style.'/>';

                    $hint = i18n::user('Provide location of the file like overlays/sgs/parameters.include.php');
					$fields[] = array($label, $input,$hint);



				    // Array name

					$label = i18n::user('Array Name');

					if(!isset($tabinformation_row['array_name']))

					  $tabinformation_row['array_name'] = '';

					if(isset($tabinformation_row['is_file']) && $tabinformation_row['is_file']=='No')
					{	$disable='disabled';
						$bg_style='style="background-color:#64605B"';
					}
					else
					{
						$disable='';
						$bg_style='style="background-color:#FFFFFF"';
					}


					$input = '<input type="text" name="array_name_'.$i.'" id="array_name_'.$i.'" value ="'.encode_field($tabinformation_row['array_name']).'" size="55" maxlength="255" '.$disable.' '.$bg_style.'/>';

                    $hint = i18n::user('Please enter array name with index like context|technical_area');


					$fields[] = array($label, $input,$hint);






					// SQL Query

					$label = i18n::user('Populate drop-down filter');

					if(!isset($tabinformation_row['option_sql']))

						$tabinformation_row['option_sql'] = '';

					if(isset($tabinformation_row['is_file']) && $tabinformation_row['is_file']=='Yes')
					{	$disable='disabled';
						$bg_style='style="background-color:#64605B"';
					}
					else
					{
						$disable='';
						$bg_style='style="background-color:#FFFFFF"';
					}


					$input = '<textarea name="option_sql_'.$i.'" id="option_sql_'.$i.'" rows="15" cols="50" '.$disable.' '.$bg_style.' >'.encode_field($tabinformation_row['option_sql']).'</textarea><div><input type="button" value="Check Query" name="btncheck" onclick="checkquery(\''.$i.'\')"><span id="check_message_'.$i.'"></span></div> ';

					$hint = i18n::user('Please enter valid SQL for populating drop-down');

					$fields[] = array($label, $input, $hint);





					// Tab table_alies

					$label = i18n::user('Filter Alies');

					if(!isset($tabinformation_row['table_alies']))

					  $tabinformation_row['table_alies'] = '';


					$input = '<input type="text" name="table_alies_'.$i.'" id="table_alies_'.$i.'" value ="'.encode_field($tabinformation_row['table_alies']).'" size="55" maxlength="255" />';

					$fields[] = array($label, $input);









					// Tab option_code

					$label = i18n::user('Drop-Down Name<span style="color:#FF0000">*</span>');

					if(!isset($tabinformation_row['option_code']))

					  $tabinformation_row['option_code'] = '';


					$input = '<input type="text" name="option_code_'.$i.'" id="option_code_'.$i.'" value ="'.encode_field($tabinformation_row['option_code']).'" size="55" maxlength="255" />';

					$fields[] = array($label, $input);






					// Tab filter order

					$label = i18n::user('Filter Order');

					if(!isset($tabinformation_row['filter_order']))

					  $tabinformation_row['filter_order'] = '';


					$input = '<input type="text" name="filter_order_'.$i.'" id="filter_order_'.$i.'" value ="'.encode_field($tabinformation_row['filter_order']).'" size="5" maxlength="3" />';

					$fields[] = array($label, $input);


					// Is remove

					$label = i18n::user('<span style="color:#FF0000">Remove</span>');


					$input = '<input type="checkbox" name="is_remove_'.$i.'" id="is_remove_'.$i.'" value ="Yes"  />';

					$fields[] = array($label, $input);





					// hidden field , count issue register

					$label = '';

					$input = '<input type="hidden" name="count_tb_'.$i.'" value ="'.count($tab_information_tracking).'" size="10" /> ';

					$hint='<script>function checkquery(counter){val=$("option_sql_"+counter).value;  if(val!=""){Yacs.update(\'check_message_\'+counter,\''.$context['url_to_root'].'tables/checkquery.php?query=\'+val,\'\' );} return false;}function checkquery2(counter){val=$("option_sql_new_"+counter).value;  if(val!=""){Yacs.update(\'check_message_new_\'+counter,\''.$context['url_to_root'].'tables/checkquery.php?query=\'+val,\'\' );} return false;} function showhide(counter,value,isnew){if(isnew=="0")var extra="";else var extra="new_";if(value=="Yes"){$("file_path_"+extra+counter).enable();$("file_path_"+extra+counter).setStyle("background-color:#FFFFFF");$("array_name_"+extra+counter).enable();$("array_name_"+extra+counter).setStyle("background-color:#FFFFFF");$("option_sql_"+extra+counter).disable();$("option_sql_"+extra+counter).setStyle("background-color:#64605B");}else{$("file_path_"+extra+counter).disable();$("file_path_"+extra+counter).setStyle("background-color:#64605B");$("array_name_"+extra+counter).disable();$("array_name_"+extra+counter).setStyle("background-color:#64605B");$("option_sql_"+extra+counter).enable();$("option_sql_"+extra+counter).setStyle("background-color:#FFFFFF");}} function checksubmit(){var removestr="";var count=$("count_total_row").value;for(i=1;i<=count;i++){if($("is_remove_"+i).checked){removestr=removestr+"record #"+i+",";}
else{if($("option_name_"+i).value==""){ alert("Enter the filter title");Yacs.stopWorking();$("option_name_"+i).focus();return false;} if($("is_file_yes_"+i).checked){if($("file_path_"+i).value==""){		alert("Please enter the file path"); Yacs.stopWorking();$("file_path_"+i).focus();return false;}if($("array_name_"+i).value==""){alert("Please enter the array name");Yacs.stopWorking();$("array_name_"+i).focus();	return false;}}else{if($("option_sql_"+i).value==""){alert("Please enter the sql for the filter");Yacs.stopWorking();$("option_sql_"+i).focus();return false;}} if($("option_code_"+i).value==""){ alert("Enter the drop down name");Yacs.stopWorking();$("option_code_"+i).focus();return false;} }} var count=$("count_new_tb").value-1; for(i=1;i<=count;i++){if($("is_remove_new_"+i).checked){removestr=removestr+" new record #"+i+",";}else{if($("option_name_new_"+i).value==""){ alert("Enter the filter title");Yacs.stopWorking();$("option_name_new_"+i).focus();return false;}if($("is_file_new_yes_"+i).checked){if($("file_path_new_"+i).value==""){		alert("Please enter the file path"); Yacs.stopWorking();$("file_path_new_"+i).focus();return false;}if($("array_name_new_"+i).value==""){alert("Please enter the array name");Yacs.stopWorking();	$("array_name_new_"+i).focus();	return false;}}else{if($("option_sql_new_"+i).value==""){alert("Please enter the sql");Yacs.stopWorking();$("option_sql_new_"+i).focus();return false;}} if($("option_code_new_"+i).value==""){ alert("Enter the drop down name"); Yacs.stopWorking();$("option_code_new_"+i).focus();return false;}}}if(removestr){if(confirm("You are going to delete "+removestr+". Are you sure?")){	return true;}else{Yacs.stopWorking();return false;}}}
</script>';


					$fields[] = array($label, $input,$hint);

					$tabinformation .= Skin::build_form($fields);

					$tabinformation.="</div>";


				$i++;

				}

			}

			else

			{

					#NO existig record, print one blank form

					$tabinformation.='<div class="bottom">';

					$tabinformation.= Skin::build_box("Filter #$i",'', 'header2');

					$fields=array();

					// Issue Description

				    $label = i18n::user('Filter Title');

					//$input = '<textarea name="option_name_'.$i.'"rows="15" cols="50"></textarea> ';

					$input = '<input type="text" name="option_name_'.$i.'" id="option_name_'.$i.'" value ="" size="55" maxlength="255" />';


					$fields[] = array($label, $input);







					// Indicator for file

				   $label = i18n::user('Is populate from file ?');

                   $input = '<input type="radio" name="is_file_'.$i.'" value="Yes"  onclick="showhide(\''.$i.'\',this.value,\'0\')"/>'.i18n::user('Yes');

				   $checked = 'checked="checked"';

				   $input .= '&nbsp;&nbsp;<input type="radio" name="is_file_'.$i.'" value="No"  '.$checked.' onclick="showhide(\''.$i.'\',this.value,\'0\')"/>'.i18n::user('No');

					$fields[] = array($label, $input);




                   // File path

					$label = i18n::user('File path');

					$input = '<input type="text" name="file_path_'.$i.'" id="file_path_'.$i.'" value ="" size="55" maxlength="255" />';

                    $hint = i18n::user('Provide location of the file like overlays/sgs/parameters.include.php');

					$fields[] = array($label, $input,$hint);



				    // Array name

					$label = i18n::user('Array Name');

					$input = '<input type="text" name="array_name_'.$i.'" id="array_name_'.$i.'" value ="" size="55" maxlength="255" />';

                    $hint = i18n::user('Please entry array name with index like context|lesson_type');


					$fields[] = array($label, $input,$hint);




					// SQL Query

					$label = i18n::user('Populate drop-down filter');

					$input = '<textarea name="option_sql_'.$i.'"rows="15" cols="50"></textarea><div><input type="button" value="Check Query" name="btncheck" onclick="checkquery(\''.$i.'\')"><span id="check_message_'.$i.'"></span></div>  ';

					$hint = i18n::user('Please enter valid SQL for populating drop-down');

					$fields[] = array($label, $input, $hint);






								// Tab table_alies

					$label = i18n::user('Filter Alies');

					$input = '<input type="text" name="table_alies_'.$i.'" id="table_alies_'.$i.'" value ="" size="55" maxlength="255" />';

					$fields[] = array($label, $input);




					// Tab option_code

					$label = i18n::user('Drop-Down Name');

					$input = '<input type="text" name="option_code_'.$i.'" id="option_code_'.$i.'" value ="" size="55" maxlength="255" />';

					$fields[] = array($label, $input);







					// Tab filter order

					$label = i18n::user('Filter Order');

					$input = '<input type="text" name="filter_order_'.$i.'" id="filter_order_'.$i.'" value ="" size="5" maxlength="3" />';

					$fields[] = array($label, $input);


                  // Remove

					$label = i18n::user('<span style="color:#FF0000">Remove</span>');


					$input = '<input type="checkbox" name="is_remove_'.$i.'" id="is_remove_'.$i.'" value ="Yes"  />';

					$fields[] = array($label, $input);


					// hidden field , count contact

					$label = '';

					$input = '<input type="hidden" name="count_tb_1" value ="1" size="10" /> ';

					$hint='<script>function checkquery(counter){val=$("option_sql_"+counter).value;  if(val!=""){Yacs.update(\'check_message_\'+counter,\''.$context['url_to_root'].'tables/checkquery.php?query=\'+val,\'\' );} return false;}function checkquery2(counter){val=$("option_sql_new_"+counter).value;  if(val!=""){Yacs.update(\'check_message_new_\'+counter,\''.$context['url_to_root'].'tables/checkquery.php?query=\'+val,\'\' );} return false;} function showhide(counter,value,isnew){if(isnew=="0")var extra="";else var extra="new_";if(value=="Yes"){$("file_path_"+extra+counter).enable();$("file_path_"+extra+counter).setStyle("background-color:#FFFFFF");$("array_name_"+extra+counter).enable();$("array_name_"+extra+counter).setStyle("background-color:#FFFFFF");$("option_sql_"+extra+counter).disable();$("option_sql_"+extra+counter).setStyle("background-color:#64605B");}else{$("file_path_"+extra+counter).disable();$("file_path_"+extra+counter).setStyle("background-color:#64605B");$("array_name_"+extra+counter).disable();$("array_name_"+extra+counter).setStyle("background-color:#64605B");$("option_sql_"+extra+counter).enable();$("option_sql_"+extra+counter).setStyle("background-color:#FFFFFF");}}</script>';

					//$fields[] = array($label, $input);

					$fields[] = array($label, $input);

					$tabinformation .= Skin::build_form($fields);

					$tabinformation .="</div>";

			}



			$fields = array();


			$tabinformation.="<div id='more_tb_1'><input type='hidden' name='count_new_tb' value ='1' id='count_new_tb' size='10' /></div>";

			$tabinformation.="<div id='more_tb_loading'></div>";

			$tabinformation.='<div style="padding:0px 0px 0px 470px;"><a href="javascript:void(0);" onclick="Yacs.update(\'more_tb_\'+$(\'count_new_tb\').getValue(),\'../addtab.php?tab_row=\'+$(\'count_new_tb\').getValue(),\'\' ); return false;">Add New Filter</a></div><br/>';


			$tabinformation.="<input type='hidden' name='count_total_row' value ='".($i-1)."' id='count_total_row' size='10' /><script>function showhidemeeting(){document.getElementById('more_tb_link').style.display='none';document.getElementById('more_tb').style.display='block'; }  </script>";




           $context['text'] .= $tabinformation;









	// associates may decide to not stamp changes -- complex command
	//if(Surfer::is_associate() && Surfer::has_all())
		//$context['text'] .= '<p><input type="checkbox" name="silent" value="Y" /> '.i18n::s('Do not change modification date of the main page.').'</p>';

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// other hidden fields
	//$context['text'] .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	//   .'	 alert(container.count_tb_'.($i-1).'.value);'."\n"
/*	$context['text'] .= JS_PREFIX
		.'	// check that main fields are not empty'."\n"
		.'	func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'		// query is mandatory'."\n"
		.'		if(!container.query.value) {'."\n"
		.'			alert("'.i18n::s('Please type a valid SQL query.').'");'."\n"
		.'			Yacs.stopWorking();'."\n"
		.'			return false;'."\n"
		.'		}'."\n"
		."\n"
		.'		// successful check'."\n"
		.'		return true;'."\n"
		.'	}'."\n"
		."\n"
		.'// set the focus on first form field'."\n"

		.JS_SUFFIX."\n";
		*/

	// the help panel
	$help = '<p>'.i18n::s('Please ensure you are using a compliant and complete SQL SELECT statement.').'</p>'
		.'<p>'.sprintf(i18n::s('For more information check the %s.'), Skin::build_link('http://dev.mysql.com/doc/mysql/en/select.html', i18n::s('MySQL reference page'), 'external')).'</p>'
		.'<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'help'), Skin::build_link('smileys/', i18n::s('smileys'), 'help')).'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'extra', 'help');

}

// render the skin
render_skin();

?>