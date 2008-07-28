<?php
/**
 * import records from another database
 *
 * This page is used to alter the database. Its usage is restricted to associates
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// include libraries
include_once '../shared/global.php';

// load localized strings
i18n::bind('control');

// load the skin
load_skin('control');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = i18n::s('Import data from an existing database');

/**
 * dynamically generate the page
 *
 * @see skins/index.php
 */
function send_body() {
	global $context;

	// if the user table exists, check that the user is an admin
	$query = "SELECT count(*) FROM ".SQL::table_name('users')." AS articles";
	if(SQL::query($query) !== FALSE && !Surfer::is_associate()) {

		// prevent access to this script
		Safe::header('Status: 403 Forbidden', TRUE, 403);
		Skin::error(i18n::s('You are not allowed to perform this operation.'));

		// forward to the control panel
		$menu = array('control/' => i18n::s('Control Panel'));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

		return;
	}

	// get the parameters of the input database
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'POST')) {

		// the splash message
		echo i18n::s('Please indicate below the parameters for the input database. At the moment this script is able to process phpwebsite databases.');

		// the form
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';

		// use a 2-column table for the layout
		$context['text'] .= Skin::table_prefix('form');
		$lines = 1;

		// the server
		$cells = array();
		$cells[] = i18n::s('Server');
		$cells[] = '<input type="text" name="server" id="server" size="45" value="localhost" maxlength="255" /></p>'."\n";
		$context['text'] .= Skin::table_row($cells, $lines++);

		// the user
		$cells = array();
		$cells[] = i18n::s('User');
		$cells[] = '<input type="text" name="user" size="45" value="user" maxlength="255" /></p>'."\n";
		$context['text'] .= Skin::table_row($cells, $lines++);

		// the password
		$cells = array();
		$cells[] = i18n::s('Password');
		$cells[] = '<input type="password" name="password" size="45" value="password" maxlength="255" /></p>'."\n";
		$context['text'] .= Skin::table_row($cells, $lines++);

		// the database
		$cells = array();
		$cells[] = i18n::s('Database');
		$cells[] = '<input type="text" name="database" size="45" value="phpwebsite" maxlength="255" /></p>'."\n";
		$context['text'] .= Skin::table_row($cells, $lines++);

		// the http root
		$cells = array();
		$cells[] = i18n::s('Web address');
		$cells[] = '<input type="text" name="web_url" size="45" value="http://127.0.0.1/phpwebsite" maxlength="255" /></p>'."\n";
		$context['text'] .= Skin::table_row($cells, $lines++);

		// the submit button
		$cells = array();
		$cells[] = '';
		$cells[] = Skin::build_submit_button(i18n::s('Yes, I want to read data from this source'));
		$context['text'] .= Skin::table_row($cells, $lines++);

		// end of the table
		$context['text'] .= Skin::table_suffix();

		// end of the form
		$context['text'] .= '</div></form>';

		// the script used for form handling at the browser
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
			.'// set the focus on first form field'."\n"
			.'$("server").focus();'."\n"
			.'// ]]></script>'."\n";

		// warning
		$context['text'] .= '<p>'.i18n::s('If you have cleaned your database before this import session, have you cleaned related files (attachments and images) as well?')."</p>\n";

		return;
	}

	// connect to the server
	if(!$handle =& SQL::connect($_REQUEST['server'], $_REQUEST['user'], $_REQUEST['password'], $_REQUEST['database'])) {
		echo sprintf(i18n::s('Impossible to connect to %s.'), strip_tags($_REQUEST['database']));

	// do the conversion
	} else {

		//
		// import from phpwebsite environment
		//

		// in order to avoid id collisions, we will compute a random hash number to be added to all ids
		list($usec, $sec) = explode(' ', microtime(), 2);
		srand((float) $sec + ((float) $usec * 100000));
		$hash = rand(1000, 9999);
		echo '<p>session hash = '.$hash."\n";


		// import records in the users table from the authors table
		$query = "SELECT * from authors";
		if($result =& SQL::query($query, $handle)) {

			echo '<p>authors => users';

			$count = 0;
			$errors_count = 0;
			while($input =& SQL::fetch($result)) {
				$row = array();
				//$row['id']
				$row['email'] = $input['email'];
				$row['password'] = $input['pwd'];
				$row['nick_name'] = $input['aid'];
				$row['full_name'] = $input['name'];
				//$row['introduction']
				//$row['description']
				//$row['icon_url']
				$row['options'] = '';
				//$row['create_name']
				//$row['create_id']
				//$row['create_address']
				//$row['create_date']
				//$row['edit_name']
				//$row['edit_id']
				//$row['edit_address']
				$row['edit_action'] = 'user:import';
				//$row['edit_date']
				$row['capability'] = 'M';
				//$row['posts']

				$count++;
				if(!$id = Users::post($row)) {
					echo Skin::error_pop().BR."\n";
					if(++$errors_count >= 5) {
						echo BR."\n".i18n::s('Too many successive errors. Aborted');
						break;
					}
				} else
					$errors_count = 0;

				// animate user screen and take care of time
				if(!($count%100))
					echo BR."\n".sprintf(i18n::s('%d records have been processed'), $count);

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			echo BR."\n".sprintf(i18n::s('%d records have been processed. Finished'), $count).'</p>';
		}

		// import records in the sections table from the categories table
		$query = "SELECT * from categories";
		if($result =& SQL::query($query, $handle)) {

			echo '<p>categories => sections';

			$count = 0;
			$errors_count = 0;
			while($input =& SQL::fetch($result)) {
				$row = array();
				$row['id'] = intval($hash+$input['categoryid']);
				$row['title'] = $input['categorytext'];
				$row['introduction'] = $input['name'];
				$row['description'] = $input['categorylongtext'];
				$row['icon_url'] = $input['categoryimage'];
				$row['options'] = '';
				//$row['create_name']
				//$row['create_id']
				//$row['create_address']
				//$row['create_date']
				//$row['edit_name']
				//$row['edit_id']
				//$row['edit_address']
				$row['edit_action'] = 'section:import';
				$row['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');

				$count++;
				if(!$new_id = Sections::post($row)) {
					echo BR."\n".$error;
					if(++$errors_count >= 5) {
						echo BR."\n".i18n::s('Too many successive errors. Aborted');
						break;
					}
				} else
					$errors_count = 0;

				// animate user screen and take care of time
				if(!($count%100))
					echo BR."\n".sprintf(i18n::s('%d records have been processed'), $count);

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			echo BR."\n".sprintf(i18n::s('%d records have been processed. Finished'), $count).'</p>';
		}

		// import records in the articles table from the stories table
		$query = "SELECT * from stories";
		if($result =& SQL::query($query, $handle)) {

			echo '<p>stories => articles';

			$count = 0;
			$errors_count = 0;
			while($input =& SQL::fetch($result)) {
				$row = array();
				$row['id'] = intval($hash+$input['sid']);
				$row['anchor'] = 'section:'.intval($hash+$input['category']);
				$row['title'] = $input['title'];
				//$row['source']
				$row['introduction'] = $input['hometext'];
				$row['description'] = $input['bodytext'];
				//$row['icon_url']
				$row['options'] = '';
				$row['create_name'] = $input['informant'];
				//$row['create_id']
				//$row['create_address']
				$row['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $input['time']);
				$row['publish_name'] = $input['aid'];
				//$row['publish_id']
				//$row['publish_address']
				$row['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $input['time']);
				$row['edit_name'] = $input['informant'];
				//$row['edit_id']
				//$row['edit_address']
				$row['edit_action'] = 'article:import';
				$row['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $input['time']);
				$row['hits'] = $input['counter'];

				$count++;
				if(!Articles::post($row)) {
					echo Skin::error_pop().BR."\n";
					if(++$errors_count >= 5) {
						echo BR."\n".i18n::s('Too many successive errors. Aborted');
						break;
					}
				} else
					$errors_count = 0;

				// animate user screen and take care of time
				if(!($count%100))
					echo BR."\n".sprintf(i18n::s('%d records have been processed'), $count);

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			echo BR."\n".sprintf(i18n::s('%d records have been processed. Finished'), $count).'</p>';
		}

		//
		// import from phpwebsite/mod_documents environment
		//

		// in order to avoid id collisions, we will compute a random hash number to be added to all ids
		$hash = rand(1000, 9999);
		echo '<p>session hash = '.$hash."\n";

		// import records in the sections table from the mod_document_sections table
		$query = "SELECT * from mod_documents_sections";
		if($result =& SQL::query($query, $handle)) {

			echo '<p>mod_documents_sections => sections';

			$count = 0;
			$errors_count = 0;
			while($input =& SQL::fetch($result)) {

				// alter the description
				$description = $input['description'];
				$description = preg_replace('/image(\d+)/ie', "'image='.intval($1+$hash)", $description);
				$description = preg_replace('/\[url=documents.php\/(\d+)\]/ie', "'[article='.intval($1+$hash).']'", $description);
				$description = preg_replace('/\[url=documents.php\/id\/(\d+)\]/ie', "'[article='.intval($1+$hash).']'", $description);
				$description = preg_replace('/\[url=documents.php\/id_cat\/(\d+)\]/ie', "'[section='.intval($1+$hash).']'", $description);
				$description = preg_replace('/\[url/i', '[link', $description);
				$description = preg_replace('/url\]/i', 'link]', $description);

				// insert one record in the database
				$row = array();
				$row['id'] = intval($hash+$input['id_cat']);
				$row['title'] = $input['sections'];
				$row['introduction'] = $input['introduction'];
				$row['description'] = $description;
				$row['icon_url'] = $input['logo_url'];
				$row['options'] = '';
				$row['create_name'] = $input['poster_name'];
				//$row['create_id']
				$row['create_address'] = $input['poster_address'];
				$row['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $input['poster_date']);
				$row['edit_name'] = $input['update_name'];
				//$row['edit_id']
				$row['edit_address'] = $input['update_address'];
				$row['edit_action'] = 'section:import';
				$row['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $input['date']);
				$row['active'] = $input['active'];

				$count++;
				if(!$new_id = Sections::post($row)) {
					echo BR."\n".$error;
					if(++$errors_count >= 5) {
						echo BR."\n".i18n::s('Too many successive errors. Aborted');
						break;
					}
				} else
					$errors_count = 0;

				// animate user screen and take care of time
				if(!($count%100))
					echo BR."\n".sprintf(i18n::s('%d records have been processed'), $count);

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			echo BR."\n".sprintf(i18n::s('%d records have been processed. Finished'), $count).'</p>';
		}

		// import records in the categories table from the mod_document_themes table
		$query = "SELECT * from mod_documents_themes";
		if($result =& SQL::query($query, $handle)) {

			echo '<p>mod_documents_themes => categories';
			include_once '../categories/categories.php';

			$count = 0;
			$errors_count = 0;
			while($input =& SQL::fetch($result)) {
				$row = array();
				$row['id'] = intval($hash+$input['id_theme']);
				$row['title'] = $input['title'];
				$row['introduction'] = $input['introduction'];
				$row['description'] = $input['description'];
				$row['icon_url'] = $input['logo_url'];
				$row['options'] = '';
				$row['create_name'] = $input['poster_name'];
				//$row['create_id']
				$row['create_address'] = $input['poster_address'];
				$row['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $input['poster_date']);
				$row['edit_name'] = $input['update_name'];
				//$row['edit_id']
				$row['edit_address'] = $input['update_address'];
				$row['edit_action'] = 'category:import';
				$row['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $input['date']);
				$row['active'] = $input['active'];

				$count++;
				if(!Categories::post($row)) {
					echo Skin::error_pop().BR."\n";
					if(++$errors_count >= 5) {
						echo BR."\n".i18n::s('Too many successive errors. Aborted');
						break;
					}
				} else
					$errors_count = 0;

				// animate user screen and take care of time
				if(!($count%100))
					echo BR."\n".sprintf(i18n::s('%d records have been processed'), $count);

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			echo BR."\n".sprintf(i18n::s('%d records have been processed. Finished'), $count).'</p>';
		}

		// import records in the members table from the mod_document_members table
		$query = "SELECT * from mod_documents_members";
		if($result =& SQL::query($query, $handle)) {

			echo '<p>mod_documents_members => members';
			include_once '../categories/categories.php';

			$count = 0;
			$errors_count = 0;
			while($input =& SQL::fetch($result)) {
				$row = array();
				$row['id'] = intval($hash+$input['id_member']);
				list($base, $id) = explode(':', str_replace('id_', '', $input['id_group']));
				if($base == 'cat')
					$base = 'section';
				elseif($base == 'theme')
					$base = 'category';
				elseif($base == 'id')
					$base = 'article';
				$row['anchor'] = $base.':'.intval($hash+intval($id));
				list($base, $id) = explode(':', str_replace('id_', '', $input['id']));
				if($base == 'cat')
					$base = 'section';
				elseif($base == 'theme')
					$base = 'category';
				elseif($base == 'id')
					$base = 'article';
				$row['member'] = $base.':'.intval($hash+intval($id));
				$count++;
				if($error = Members::assign($row['anchor'], $row['member'])) {
					echo BR."\n".$error;
					if(++$errors_count >= 5) {
						echo BR."\n".i18n::s('Too many successive errors. Aborted');
						break;
					}
				} else
					$errors_count = 0;

				// animate user screen and take care of time
				if(!($count%100))
					echo BR."\n".sprintf(i18n::s('%d records have been processed'), $count);

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			echo BR."\n".sprintf(i18n::s('%d records have been processed. Finished'), $count).'</p>';
		}

		// import records in the articles table from the mod_document_items table
		$query = "SELECT * from mod_documents_items";
		if($result =& SQL::query($query, $handle)) {

			echo '<p>mod_documents_items => articles';

			$count = 0;
			$errors_count = 0;
			while($input =& SQL::fetch($result)) {

				// alter the description
				$description = $input['description'];
				$description = preg_replace('/image(\d+)/ie', "'image='.intval($1+$hash)", $description);
				$description = preg_replace('/\[url=documents.php\/(\d+)\]/ie', "'[article='.intval($1+$hash).']'", $description);
				$description = preg_replace('/\[url=documents.php\/id\/(\d+)\]/ie', "'[article='.intval($1+$hash).']'", $description);
				$description = preg_replace('/\[url=documents.php\/id_cat\/(\d+)\]/ie', "'[section='.intval($1+$hash).']'", $description);
				$description = preg_replace('/\[url/i', '[link', $description);
				$description = preg_replace('/url\]/i', 'link]', $description);

				// insert one record in the database
				$row = array();
				$row['id'] = $hash+$input['id'];
				$row['anchor'] = 'section:'.intval($hash+$input['id_cat']);
				$row['title'] = $input['title'].' ';
				$row['source'] = $input['source'];
				$row['introduction'] = $input['introduction'];
				$row['description'] = $description;
				$row['icon_url'] = $input['logo_url'];
				$row['options'] = '';
				$row['create_name'] = $input['poster_name'];
				//$row['create_id']
				$row['create_address'] = $input['poster_address'];
				$row['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $input['poster_date']);
				$row['publish_name'] = $input['poster_name'];
				//$row['publish_id']
				$row['publish_address'] = $input['poster_address'];
				$row['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $input['poster_date'] ? $input['poster_date'] : $input['date']);
				$row['edit_name'] = $input['update_name'];
				//$row['edit_id']
				$row['edit_address'] = $input['update_address'];
				$row['edit_action'] = 'article:import';
				$row['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $input['date']);
				$row['active'] = $input['active'];
				$row['hits'] = $input['accesstimes'];

				$count++;
				if(!Articles::post($row)) {
					echo Skin::error_pop().BR."\n";
					if(++$errors_count >= 5) {
						echo BR."\n".i18n::s('Too many successive errors. Aborted');
						break;
					}
				} else
					$errors_count = 0;

				// animate user screen and take care of time
				if(!($count%100))
					echo BR."\n".sprintf(i18n::s('%d records have been processed'), $count);

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			echo BR."\n".sprintf(i18n::s('%d records have been processed. Finished'), $count).'</p>';
		}

		// import records in the images table from the mod_document_inlines table
		$query = "SELECT * from mod_documents_inlines";
		if($result =& SQL::query($query, $handle)) {

			echo '<p>mod_documents_inlines => images';
			include_once '../images/images.php';

			$count = 0;
			$errors_count = 0;
			while($input =& SQL::fetch($result)) {

				// copy the file from the original place
				list($base, $id) = explode(':', $input['id']);
				$source_file = $_REQUEST['web_url'].'/mod/documents/inlines/'.$base.'/'.$id.'/'.rawurlencode($input['file_name']);
				if($source = Safe::fopen($source_file, 'rb'))
					$read_count = 0;
				else {
					echo BR."\n".sprintf(i18n::s('Impossible to read %s.'), $source_file);
					if(++$read_count >= 5) {
						echo BR."\n".i18n::s('Too many successive errors. Aborted');
						break;
					}
				}

				// the destination file
				if($base == 'id_cat')
					$base = 'section';
				elseif($base == 'id_theme')
					$base = 'category';
				elseif($base == 'id')
					$base = 'article';
				Safe::make_path('images/'.$context['virtual_path'].$base.'/'.intval($id+$hash));
				$target_file = $context['path_to_root'].'images/'.$context['virtual_path'].$base.'/'.intval($id+$hash).'/'.$input['file_name'];
				if($target = Safe::fopen($target_file, 'wb'))
					$write_count = 0;
				else {
					echo BR."\n".sprintf(i18n::s('Impossible to write to %s.'), $target_file);
					if(++$write_count >= 5) {
						echo BR."\n".i18n::s('Too many successive errors. Aborted');
						break;
					}
				}

				// copy file
				if($source && $target) {
					while($buffer = fread($source, 102400)) {
						if($size = strlen($buffer))
							fwrite($target, $buffer, $size); // size to avoid magic quoting
						else
							break;
					}
					fclose($target);
					fclose($source);
				}

				// update the database
				$row = array();
				$row['id'] = intval($hash+$input['id_inline']);
				$row['anchor'] = $base.':'.intval($hash+intval($id));
				$row['image_name'] = $input['file_name'];
				$row['image_size'] = $input['file_size'];
				$row['title'] = $input['comment'];
				//$row['description']
				//$row['source']
				//$row['edit_name']
				//$row['edit_id']
				//$row['edit_address']
				$row['edit_action'] = 'image:import';
				$row['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $input['date']);

				$count++;
				if(!$id = Images::post($row)) {
					echo BR."\n".'impossible to save '.$row['image_name'];
					if(++$errors_count >= 5) {
						echo BR."\n".i18n::s('Too many successive errors. Aborted');
						break;
					}
				} else
					$errors_count = 0;

				// animate user screen and take care of time
				if(!($count%100))
					echo BR."\n".sprintf(i18n::s('%d records have been processed'), $count);

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			echo BR."\n".sprintf(i18n::s('%d records have been processed. Finished'), $count).'</p>';
		}

		// import records in the files table from the mod_document_attachments table
		$query = "SELECT * from mod_documents_attachments";
		if($result =& SQL::query($query, $handle)) {

			echo '<p>mod_documents_attachments => files';
			include_once '../files/files.php';

			$count = 0;
			$errors_count = 0;
			while($input =& SQL::fetch($result)) {

				// copy the file from the original place
				list($base, $id) = explode(':', $input['id']);
				$source_file = $_REQUEST['web_url'].'/mod/documents/attachments/'.$base.'/'.$id.'/'.rawurlencode($input['file_name']);
				if($source = Safe::fopen($source_file, 'rb'))
					$read_count = 0;
				else {
					echo BR."\n".sprintf(i18n::s('Impossible to read %s.'), $source_file);
					if(++$read_count >= 5) {
						echo BR."\n".i18n::s('Too many successive errors. Aborted');
						break;
					}
				}

				// the destination file
				if($base == 'id_cat')
					$base = 'section';
				elseif($base == 'id')
					$base = 'article';
				Safe::make_path('files/'.$context['virtual_path'].$base.'/'.intval($id+$hash));
				$target_file = $context['path_to_root'].'files/'.$context['virtual_path'].$base.'/'.intval($id+$hash).'/'.$input['file_name'];
				if($target = Safe::fopen($target_file, 'wb'))
					$write_count = 0;
				else {
					echo BR."\n".sprintf(i18n::s('Impossible to write to %s.'), $target_file);
					if(++$write_count >= 5) {
						echo BR."\n".i18n::s('Too many successive errors. Aborted');
						break;
					}
				}

				// copy file
				if($source && $target) {
					while($buffer = fread($source, 102400)) {
						if($size = strlen($buffer))
							fwrite($target, $buffer, $size); // size to avoid magic quoting
						else
							break;
					}
					fclose($target);
					fclose($source);
				}

				// update the database
				$row = array();
				$row['id'] = intval($hash+$input['id_attachment']);
				$row['anchor'] = $base.':'.intval($hash+intval($id));
				$row['file_name'] = $input['file_name'];
				$row['file_size'] = $input['file_size'];
				// $row['title']
				$row['description'] = $input['version'].' '.$input['comment'];
				// $row['source']
				$row['create_name'] = $input['poster_name'];
				//$row['create_id']
				$row['create_address'] = $input['poster_address'];
				$row['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $input['poster_date']);
				$row['edit_name'] = $input['poster_name'];
				//$row['edit_id']
				$row['edit_address'] = $input['poster_address'];
				$row['edit_action'] = 'file:import';
				$row['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $input['poster_date']);

				$count++;
				if(!Files::post($row)) {
					echo Skin::error_pop().BR."\n";
					if(++$errors_count >= 5) {
						echo BR."\n".i18n::s('Too many successive errors. Aborted');
						break;
					}
				} else
					$errors_count = 0;

				// animate user screen and take care of time
				if(!($count%100))
					echo BR."\n".sprintf(i18n::s('%d records have been processed'), $count);

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			echo BR."\n".sprintf(i18n::s('%d records have been processed. Finished'), $count).'</p>';
		}

		// import records in the links table from the mod_document_links table
		$query = "SELECT * from mod_documents_links";
		if($result =& SQL::query($query, $handle)) {

			echo '<p>mod_documents_links => links';
			include_once '../links/links.php';

			$count = 0;
			$errors_count = 0;
			while($input =& SQL::fetch($result)) {
				$row = array();
				//$row['id']
				list($base, $id) = explode(':', str_replace('id_', '', $input['id']));
				if($base == 'cat')
					$base = 'section';
				elseif($base == 'id')
					$base = 'article';
				$row['anchor'] = $base.':'.intval($hash+intval($id));
				$row['link_url'] = $input['link_address'];
				$row['title'] = $input['link_title'];
				$row['description'] = $input['comment'];
				//$row['hits']
				//$row['edit_name']
				//$row['edit_id']
				//$row['edit_address']
				$row['edit_action'] = 'link:import';
				$row['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $input['date']);

				$count++;
				if(!Links::post($row)) {
					echo BR."\n".Skin::error_pop();
					if(++$errors_count >= 5) {
						echo BR."\n".i18n::s('Too many successive errors. Aborted');
						break;
					}
				} else
					$errors_count = 0;

				// animate user screen and take care of time
				if(!($count%100))
					echo BR."\n".sprintf(i18n::s('%d records have been processed'), $count);

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			echo BR."\n".sprintf(i18n::s('%d records have been processed. Finished'), $count).'</p>';
		}

		//
		// import from phpwebsite/mod_partners environment
		//

		// in order to avoid id collisions, we will compute a random hash number to be added to all ids
		$hash = rand(1000, 9999);
		echo '<p>session hash = '.$hash."\n";

		// import records in the articles table from the mod_partners_items table
		$query = "SELECT * from mod_partners_items";
		if($result =& SQL::query($query, $handle)) {

			echo '<p>mod_partners_items => articles';

			$anchor = Sections::lookup('partners');

			$count = 0;
			$errors_count = 0;
			while($anchor && ($input =& SQL::fetch($result))) {
				$row = array();
				$row['id'] = intval($hash+$input['id']);
				$row['anchor'] = $anchor;
				$row['title'] = $input['name'];
				//$row['source']
				$row['introduction'] = $input['introduction'];
				$row['description'] = $input['description'];
				if($input['contact'])
					$row['description'] .= '<p>'.i18n::c('Contact:').' '.$input['contact'];
				if($input['address'])
					$row['description'] .= '<p>'.i18n::c('Address:').' '.$input['address'];
				if($input['phone_number'])
					$row['description'] .= '<p>'.i18n::c('Phone number:').' '.$input['phone_number'];
				if($input['fax_number'])
					$row['description'] .= '<p>'.i18n::c('Fax number:').' '.$input['fax_number'];
				if($input['mail_url'])
					$row['description'] .= '<p>'.i18n::c('Mail address:').' '.$input['mail_url'];
				if($input['web_url'])
					$row['description'] .= '<p>'.i18n::c('Web server:').' '.Skin::build_link($input['web_url']);
				$row['icon_url'] = $input['logo_url'];
				$row['options'] = '';
				//$row['create_name']
				//$row['create_id']
				//$row['create_address']
				$row['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $input['date']);
				//$row['publish_name']
				//$row['publish_id']
				//$row['publish_address']
				$row['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $input['date']);
				//$row['edit_name']
				//$row['edit_id']
				//$row['edit_address']
				$row['edit_action'] = 'article:import';
				$row['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $input['date']);
				$row['active'] = $input['active'];
				$row['hits'] = $input['accesstimes'];
				$row['rank'] = $input['priority'];

				$count++;
				if(!Articles::post($row)) {
					echo Skin::error_pop().BR."\n";
					if(++$errors_count >= 5) {
						echo BR."\n".i18n::s('Too many successive errors. Aborted');
						break;
					}
				} else
					$errors_count = 0;

				// animate user screen and take care of time
				if(!($count%100))
					echo BR."\n".sprintf(i18n::s('%d records have been processed'), $count);

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			echo BR."\n".sprintf(i18n::s('%d records have been processed. Finished'), $count).'</p>';
		}

		// import records in the images table from the mod_partners_inlines table
		$query = "SELECT * from mod_partners_inlines";
		if($result =& SQL::query($query, $handle)) {

			echo '<p>mod_partners_inlines => images';
			include_once '../images/images.php';

			$count = 0;
			$errors_count = 0;
			while($input =& SQL::fetch($result)) {

				// copy the file from the original place
				list($base, $id) = explode(':', $input['id']);
				$source_file = $_REQUEST['web_url'].'/mod/partners/inlines/'.$base.'/'.$id.'/'.rawurlencode($input['file_name']);
				if($source = Safe::fopen($source_file, 'rb'))
					$read_count = 0;
				else {
					echo BR."\n".sprintf(i18n::s('Impossible to read %s.'), $source_file);
					if(++$read_count >= 5) {
						echo BR."\n".i18n::s('Too many successive errors. Aborted');
						break;
					}
				}

				// the destination file
				if($base == 'id_cat')
					$base = 'section';
				elseif($base == 'id_theme')
					$base = 'category';
				elseif($base == 'id')
					$base = 'article';
				Safe::make_path('images/'.$context['virtual_path'].$base.'/'.intval($id+$hash));
				$target_file = $context['path_to_root'].'images/'.$context['virtual_path'].$base.'/'.intval($id+$hash).'/'.$input['file_name'];
				if($target = Safe::fopen($target_file, 'wb'))
					$write_count = 0;
				else {
					echo BR."\n".sprintf(i18n::s('Impossible to write to %s.'), $target_file);
					if(++$write_count >= 5) {
						echo BR."\n".i18n::s('Too many successive errors. Aborted');
						break;
					}
				}

				// copy file
				if($source && $target) {
					while($buffer = fread($source, 102400)) {
						if($size = strlen($buffer))
							fwrite($target, $buffer, $size); // size to avoid magic quoting
						else
							break;
					}
					fclose($target);
					fclose($source);
				}

				// update the database
				$row = array();
				$row['id'] = intval($hash+$input['id_inline']);
				$row['anchor'] = $base.':'.intval($hash+intval($input['id']));
				$row['image_name'] = $input['file_name'];
				$row['image_size'] = $input['file_size'];
				$row['title'] = $input['comment'];
				//$row['description']
				//$row['source']
				//$row['edit_name']
				//$row['edit_id']
				//$row['edit_address']
				$row['edit_action'] = 'image:import';
				$row['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', $input['date']);

				$count++;
				if(!$id = Images::post($row)) {
					echo BR."\n".'impossible to save '.$row['image_name'];
					if(++$errors_count >= 5) {
						echo BR."\n".i18n::s('Too many successive errors. Aborted');
						break;
					}
				} else
					$errors_count = 0;

				// animate user screen and take care of time
				if(!($count%100))
					echo BR."\n".sprintf(i18n::s('%d records have been processed'), $count);

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			echo BR."\n".sprintf(i18n::s('%d records have been processed. Finished'), $count).'</p>';
		}

	}

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'));
	echo Skin::build_list($menu, 'menu_bar');

	// flush the full cache
	Cache::clear();

}

// render the skin
render_skin();

?>