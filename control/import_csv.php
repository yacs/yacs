<?php
/**
 * import csv file to create pages
 *
 * This work support overlays
 *
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

global $attributes;

// the maximum size for uploads
$file_maximum_size = str_replace('M', '000000', Safe::get_cfg_var('upload_max_filesize'));
if(!$file_maximum_size || $file_maximum_size > 50000000)
	$file_maximum_size = 5000000;

// do not always show the edition form
$with_form = FALSE;

// load localized strings
i18n::bind('control');

// load the skin
load_skin('control');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = i18n::s('Import some articles from a csv file');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('control/import_csv.php'));

// only associates can use this tool!
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// process uploaded data
} else {

	// retrieve parameters
	$etape = (isset($_REQUEST['etape']) && $_REQUEST['etape'])?$_REQUEST['etape']:'1';
	$nom_fichier = (isset($_REQUEST['nom_fichier']) && $_REQUEST['nom_fichier'])?$_REQUEST['nom_fichier']:'';
	$delimiteur = stripslashes((isset($_REQUEST['delimiteur']) && $_REQUEST['delimiteur'])?$_REQUEST['delimiteur']:'"');
	$separateur = stripslashes((isset($_REQUEST['separateur']) && $_REQUEST['separateur'])?$_REQUEST['separateur']:',');
	$display_lines = (isset($_REQUEST['display_lines']) && $_REQUEST['display_lines'])?$_REQUEST['display_lines']:'2';
	$desc_line = (isset($_REQUEST['desc_line']) && $_REQUEST['desc_line'])?$_REQUEST['desc_line']:'1';
	$data_start = (isset($_REQUEST['data_start']) && $_REQUEST['data_start'])?$_REQUEST['data_start']:'2';
	$overlay = (isset($_REQUEST['overlay']) && $_REQUEST['overlay'])?$_REQUEST['overlay']:'';
	$nb_fields = (isset($_REQUEST['nb_fields']) && $_REQUEST['nb_fields'])?$_REQUEST['nb_fields']:0;
	$language = (isset($_REQUEST['language']) && $_REQUEST['language'])?$_REQUEST['language']:$context['language'];
	$anchor = (isset($_REQUEST['anchor']) && $_REQUEST['anchor'])?$_REQUEST['anchor']:0;

	// fight hackers
	$nom_fichier = preg_replace(FORBIDDEN_STRINGS_IN_PATHS, '', strip_tags($nom_fichier));

	//
	for($i=0;$i<$nb_fields;$i++) {

		$sec_fld = $_REQUEST['section_'.$i]?$_REQUEST['section_'.$i]:'off';
		$section_sel[$i]=$sec_fld;

		$art_fld = $_REQUEST['article_'.$i]?$_REQUEST['article_'.$i]:'';
		$article_sel[$i]=$art_fld;

		$ovr_fld = $_REQUEST['overlay_'.$i]?$_REQUEST['overlay_'.$i]:'';
		$overlay_sel[$i]=$ovr_fld;
	}

	// fichier reçu
	if($nom_fichier == '') {
		if (isset($_FILES["userfile"])) {
			if ($_FILES['userfile']['error']) {
				$context['text'].=	"<br><font color=red>";
				switch ($_FILES['userfile']['error']){
				   case 1: // UPLOAD_ERR_INI_SIZE
						$context['text'].= i18n::s('The size of this file is over PHP settings in php.ini file.');
				   break;
				   case 2: // UPLOAD_ERR_FORM_SIZE
						$context['text'].= i18n::s('The size of this file is over html form limit.');
				   break;
				   case 3: // UPLOAD_ERR_PARTIAL
						$context['text'].= i18n::s('The transfer of this file was interrupted.');
				   break;
				   case 4: // UPLOAD_ERR_NO_FILE
						$context['text'].= i18n::s('This file is empty.');
				   break;
				  }
				$context['text'].=	"</font><br>";
				$etape = '1';
			}
			else {
				// $_FILES['userfile']['error'] vaut 0 soit UPLOAD_ERR_OK
				// ce qui signifie qu'il n'y a eu aucune erreur

				//les infos disponibles sur le fichier sont (entre autres)
				$nom_fichier = str_replace(' ','_',$_FILES["userfile"]["name"]);
				$taille = $_FILES["userfile"]["size"];
				$type = $_FILES["userfile"]["type"];

				// si l'upload s'est bien passé, l'élément size est > à 0
				if($taille>0) {

					// save the uploaded file
					Safe::make_path('inbox/import');
					$fichier= $context['path_to_root'].'inbox/import/'.$nom_fichier;

					//et on déplace le fichier temp au bon endroit
					$temp = $_FILES["userfile"]["tmp_name"];
					if(Safe::move_uploaded_file($temp, $fichier)) {
						$context['text'].= BR.'<font color=green>'.sprintf(i18n::s('The file %s (of %d bytes) has been properly transmitted.'), $nom_fichier, $taille).'</font>'.BR;
						$etape = '2';
					} else {
						$context['text'].= BR.'<font color=red>'.sprintf(i18n::s('Error found during copy of %s on server. The inbox/import folder must exist on the server.'), $nom_fichier).'</font>'.BR;
						$etape = '1';
					}
				}
				else {
					$context['text'].= BR.'<font color=green>'.i18n::s('An empty file has been transmitted.').'</font>'.BR;
					$etape = '1';
				}
			}
		}
	}
	else $fichier =  '../inbox/import/'.$nom_fichier;

	//Test :<input type=checkbox name="test" checked>Décocher cette zone pour mettre à jour la base.<br>

	/* On ouvre le fichier à importer en lecture seulement */
	 if ((int)$etape > 1) {
		$fp = Safe::fopen("$fichier", "r");
		if (!$fp)
		 { /* le fichier n'existe pas */
		   $context['text'].= i18n::s('File not found! Import canceled.');
		   exit();
		 }

		$rcd_nbr = 0;
		$sections_gen = $articles_gen = $articles_mod = $sections_mode = array();

		while (!feof($fp)) /* Et Hop on importe */
		{ /* Tant qu'on n'atteint pas la fin du fichier */

			if ($etape == '2') $ligne = fgets($fp, 4096);
			else $ligne = fgetcsv($fp, 4096, $separateur, $delimiteur); /* On lit une ligne */

			$rcd_nbr++;

			switch($etape) {

				case '2':
					/* affichage du csv reçu */
					if ($rcd_nbr <= (int)$display_lines)
						$context['text'] .= '<b>'.sprintf(i18n::s('Line %s:'), $rcd_nbr).'</b>'.BR.$ligne.BR;
					else break 2;

					break;

				case '3':
					if ($rcd_nbr == (int)$desc_line) {
						$nb_fields = count($ligne);
						$attributes = array_flip($ligne);
						break 2;
					}
					break;
				case '4':
					if ($rcd_nbr == (int)$desc_line) {
						$nb_fields = count($ligne);
					}
					if ($rcd_nbr >= (int)$data_start) {
						$article_rcd = $overlay_rcd = null;
						for ($i=0; $i< $nb_fields; $i++) {
							$section_field = $section_sel[$i];
							$article_field = $article_sel[$i];
							$overlay_field = $overlay_sel[$i];

							if ($section_field =='on') traitement_section_field($i);
							elseif ($article_field != '') traitement_article_field($i, $article_field);
							elseif ($overlay_field != '') traitement_overlay_field($i, $overlay_field);
						}
						$context['text'] .= traitement_rcd();
					}
					break;
				case '5':
					if ($rcd_nbr == (int)$desc_line) {
						$nb_fields = count($ligne);
					}
					if ($rcd_nbr >= (int)$data_start) {
						$article_rcd = $overlay_rcd = null;
						for ($i=0; $i< $nb_fields; $i++) {
							$section_field = $section_sel[$i];
							$article_field = $article_sel[$i];
							$overlay_field = $overlay_sel[$i];

							if ($section_field =='on') traitement_section_field($i);
							elseif ($article_field != '') traitement_article_field($i, $article_field);
							elseif ($overlay_field != '') traitement_overlay_field($i, $overlay_field);
						}
						if (integre_rcd()) $context['text'] .= 'Importation ligne '.$rcd_nbr.' réussie.<br>';
					}
					break;
			}
		}
		if ($etape == '4') {
			sort($sections_gen);
			sort($articles_gen);
			$context['text'] .= affiche_tableau(count($sections_gen).' sections à générer', $sections_gen, 2).'<br>'.affiche_tableau(count($articles_gen).' articles à générer', $articles_gen, 2).'<br>'.affiche_tableau(count($sections_mod).' sections à modifier', $sections_mod, 2).'<br>'.affiche_tableau(count($articles_mod).' articles à modifier', $articles_mod, 2).'<br>';
		}
	}

	switch($etape) {
		case '1':
			$context['text'].= '<hr/><h3>'.i18n::s('Step 1 : Transfer of csv file').'</h3>'
				.'<FORM action="./import_csv.php" METHOD="POST" ENCTYPE="multipart/form-data"><div>'
				.'<input type="HIDDEN" name="MAX_FILE_SIZE" VALUE="'.$file_maximum_size .'" />'
				.'<INPUT TYPE="HIDDEN" NAME="etape" value="2">'
				.i18n::s('Import local csv file:').' <INPUT TYPE=FILE NAME="userfile">'.BR
				.i18n::s('or import this csv file:').' <INPUT TYPE=text name="nom_fichier" value="'.$nom_fichier.'">'.i18n::s('(from inbox/import server folder)').BR
				.i18n::s('Number of lines to display for test:').' <input type="text" name="display_lines" value="'.$display_lines.'">'.BR
				.Skin::build_submit_button(i18n::s('Send file'))
				.'</div></FORM>'.BR;
			break;
		case '2':
			$context['text'].= BR.'<font color="green">'.i18n::s('Step 1 has been completed.').'</font>'.BR
				."<a href=".$PHP_SELF."?etape=1&nom_fichier=".$nom_fichier."&display_lines=".$display_lines.'>'.i18n::s('Restart step 1').'</a><hr/>'
				.'<h3>'.i18n::s('Step 2 : Read csv file information').'</h3>'
				.'<FORM action="./import_csv.php" METHOD="POST"><div>'
				.'<INPUT TYPE="HIDDEN" NAME="etape" value="3">'
				.'<input type="hidden" name="nom_fichier" value="'.$nom_fichier.'">'
				.'<input type="hidden" name="nb_fields" value="'.$nb_fields.'">'
				.i18n::s('Field delimitor:').' <input type="text" size="1" value="'.str_replace('"', '&quot', $delimiteur).'" name="delimiteur">'.BR
				.i18n::s('Field separator:').' <input type="text" size="1" value="'.str_replace('"', '&quot', $separateur).'" name="separateur">'.BR
				.i18n::s('Data description line number in csv:').' <input type=text" value="'.$desc_line.'" name="desc_line">'.BR
				.i18n::s('Data start line number in csv:').' <input type="text" value="'.$data_start.'" name="data_start">'.BR
				.i18n::s('Overlay used during the import:').' <select name="overlay">'.get_overlays_list().'</select>'.BR
				.Skin::build_submit_button(i18n::s('Read csv file information'))
				.'</div></FORM>'.BR;
			break;
		case '3':
			$context['text'].= "\n<script language='javascript'>\n";
			$context['text'].= "function raz_select(sel1, sel2) {\n";
			$context['text'].= "sel = $(sel1);\n";
			$context['text'] .= "if (sel) {\n";
			$context['text'] .= "if (sel.type == 'checkbox') sel.checked = false;\n";
			$context['text'] .= "else sel.value='';\n";
			$context['text'].= "}\n";
			$context['text'].= "sel = $(sel2);\n";
			$context['text'] .= "if (sel) sel.value='';\n";
			$context['text'].= "}\n";
			$context['text'].= "</script>\n";
			$context['text'] .= BR.'<font color="green">'.i18n::s('Step 2 has been completed.').'</font>'.BR
				."<a href=".$PHP_SELF."?etape=1&nom_fichier=".$nom_fichier."&display_lines=".$display_lines.'>'.i18n::s('Restart step 1').'</a>&nbsp;'
				."<a href=".$PHP_SELF."?etape=2&nom_fichier=".$nom_fichier."&delimiteur=".$delimiteur."&separateur=".$separateur.'&display_lines='.$display_lines."&data_start=".$data_start."&desc_line=".$desc_line."&overlay=".$overlay."&nb_fields=".$nb_fields.'>'.i18n::s('Restart step 2').'</a><hr/>'.'<h3>'.i18n::s('Step 3 : Test import').'</h3>'.BR;
			$context['text'].= '<FORM action="./import_csv.php" METHOD="POST"><div>';
			$context['text'].= '<INPUT TYPE="HIDDEN" NAME="etape" value="4">';
			$context['text'].= '<input type="hidden" name="nom_fichier" value="'.$nom_fichier.'">';
			$context['text'].= '<input type="hidden" name="nb_fields" value="'.$nb_fields.'">';
			$context['text'].= '<input type="hidden" name="display_lines" value="'.$display_lines.'">';
			$context['text'].= '<input type="hidden"value="'.str_replace('"', '&quot', $delimiteur).'" name="delimiteur">';
			$context['text'].= '<input type="hidden"value="'.str_replace('"', '&quot', $separateur).'" name="separateur">';
			$context['text'].= '<input type="hidden"value="'.$desc_line.'" name="desc_line">';
			$context['text'].= '<input type="hidden"value="'.$data_start.'" name="data_start">';
			$context['text'].= '<input type="hidden"value="'.$overlay.'" name="overlay">';

			// the section into which we import data
			$context['text'] .=  i18n::s('Section')." : ";
			$context['text'] .= '<select name="anchor">'.Sections::get_options($item['anchor'] ? $item['anchor'] : $anchor).'</select><br>';
			// language of the imported elements
			$context['text'] .= i18n::s('Language')." : ";
			$context['text'] .= i18n::get_languages_select(isset($item['language'])?$item['language']:'');

			$context['text'].= '<table><tr><td><b>CSV</b></td><td><b>Section</b></td>';
			$context['text'].= '<td><b>Article</b></td>';
			if ($overlay != '') $context['text'].= '<td><b>'.$overlay.'</b></td>';
			$context['text'].= '</tr>';
			for ($i=0; $i< $nb_fields; $i++) {
				$context['text'].= '<tr><td>'.traite_champ($ligne[$i]).'</td>';
				$context['text'].= '<td><input type="checkbox" id="section_'.$i. '" name="section_'.$i.'"';
				if ($section_sel[$i]=='on') $context['text'].= ' checked';
				$context['text'].= ' onChange="if (this.checked) raz_select(\'article_'.$i.'\', \'overlay_'.$i.'\');">';
				$context['text'].= '</td>';
				$context['text'].= '<td><select id="article_'.$i. '" name="article_'.$i;
				$context['text'].= '" onChange="raz_select(\'section_'.$i.'\', \'overlay_'.$i.'\');">';
				$context['text'].= get_article_fields_options($article_sel[$i]).'</select></td>';
				if ($overlay != '') {
					$context['text'].= '<td><select id="overlay_'.$i. '" name="overlay_'.$i;
					$context['text'].= '" onChange="raz_select(\'section_'.$i.'\', \'article_'.$i.'\');">';
					$context['text'].= get_overlay_fields_options($overlay_sel[$i], $overlay).'</select></td>';
				}
			}
			$context['text'].= '</tr></table>';
			$context['text'] .= Skin::build_submit_button(i18n::s('Test import'));
			$context['text'].= '</div></FORM><br>';
			break;
		case '4':
			$context['text'] .= BR.'<font color="green">'.i18n::s('Step 3 has been completed.').'</font>'.BR
				."<a href=".$PHP_SELF."?etape=1&nom_fichier=".$nom_fichier."&display_lines=".$display_lines.'>'.i18n::s('Restart step 1').'</a>&nbsp;'
				."<a href=".$PHP_SELF."?etape=2&nom_fichier=".$nom_fichier."&delimiteur=".$delimiteur."&separateur=".$separateur.'&display_lines='.$display_lines."&delimiteur=".$delimiteur."&separateur=".$separateur.'&display_lines='.$display_lines."&data_start=".$data_start."&desc_line=".$desc_line."&overlay=".$overlay."&nb_fields=".$nb_fields.'>'.i18n::s('Restart step 2').'</a>&nbsp;'
				."<a href=".$PHP_SELF."?etape=3&nom_fichier=".$nom_fichier."&delimiteur=".$delimiteur."&separateur=".$separateur.'&display_lines='.$display_lines."&data_start=".$data_start."&desc_line=".$desc_line."&overlay=".$overlay."&nb_fields=".$nb_fields;
			for($i=0;$i<$nb_fields; $i++) {
				$context['text'] .= "&section_".$i."=".$section_sel[$i];
				$context['text'] .= "&article_".$i."=".$article_sel[$i];
				$context['text'] .= "&overlay_".$i."=".$overlay_sel[$i];
			}
			$context['text'] .= '>'.i18n::s('Restart step 3').'</a><hr/>'
				.'<h3>'.i18n::s('Step 4 : Import data').'</h3>';
			$context['text'].= '<FORM action="./import_csv.php" METHOD="POST"><div>';
			$context['text'].= '<INPUT TYPE="HIDDEN" NAME="etape" value="5">';
			$context['text'].= '<input type="hidden" name="nom_fichier" value="'.$nom_fichier.'">';
			$context['text'].= '<input type="hidden" name="nb_fields" value="'.$nb_fields.'">';
			$context['text'].= '<input type="hidden" name="display_lines" value="'.$display_lines.'">';
			$context['text'].= '<input type="hidden" value="'.str_replace('"', '&quot', $delimiteur).'" name="delimiteur">';
			$context['text'].= '<input type="hidden" value="'.str_replace('"', '&quot', $separateur).'" name="separateur">';
			$context['text'].= '<input type="hidden" value="'.$desc_line.'" name="desc_line">';
			$context['text'].= '<input type="hidden" value="'.$data_start.'" name="data_start">';
			$context['text'].= '<input type="hidden" value="'.$overlay.'" name="overlay">';
			$context['text'].= '<input type="hidden" value="'.$anchor.'" name="anchor">';
			$context['text'].= '<input type="hidden" value="'.$language.'" name="language">';

			for($i=0;$i<$nb_fields; $i++) {
				$context['text'].= '<input type="hidden" value="'.$section_sel[$i].'" name="section_'.$i.'">';
				$context['text'].= '<input type="hidden" value="'.$article_sel[$i].'" name="article_'.$i.'">';
				$context['text'].= '<input type="hidden" value="'.$overlay_sel[$i].'" name="overlay_'.$i.'">';
			}

			$context['text'].= Skin::build_submit_button(i18n::s('Import data'));
			$context['text'].= '</div></FORM><br>';
			break;
		case '5':
			$context['text'].= BR.'<font color="green">'.i18n::s('Step 4 has been completed.').'</font>'.BR;
			break;
	} // end switch

	// flush the cache
	Cache::clear();

}

// render the skin
render_skin();

/////////////////////////////////////////////////////////////////

function get_article_fields_options($sel) {

	$article_fields_options = '<option value="">&nbsp;</option>'."\n";

	$query = "SHOW COLUMNS FROM ".SQL::table_name('articles');

	// no result
	if(!$result =& SQL::query($query))
		return $article_fields_options;

	// empty list
	if(!SQL::count($result))
		return $article_fields_options;

	//prendre chaque champ
	while ($ligne = SQL::fetch_row($result))
	{
		// afficher le nom
		$article_fields_options .= '<option value="'.$ligne[0].'"';
		if ($sel == $ligne[0]) $article_fields_options .= ' selected';
		$article_fields_options .= '>'.$ligne[0].'</option>'."\n";
	}
	return $article_fields_options;
}

function new_overlay() {
// create a new overlay based on csv fields

  // we copy overlay.php to overlay_nameofcsv_template.php
  // this file HAS TO BE EDITED :
  // replace "Class Overlay {" by "Class Overlay_nameofcsv {"
  // add features for your overlay fields
  // save and rename to "overlay_nameofcsv"
  // if you don't, the overlay won't be searched by yacs

  global $nom_fichier;
  $new_overlay = substr($nom_fichier, 0, strrpos($nom_fichier, '.') -1);
  Safe::copy($context['path_to_root'].'overlays/overlay.php', $context['path_to_root'].'overlays/template_'.$new_overlay.'.php');

  return $new_overlay;

}

function get_overlays_list() {
	global $overlay;

	// list overlays available on this system
	// updated on 2007-06-22 : create a default overlay based on csv fields
	if ($overlay == '') $overlay = new_overlay();

	$list = '<option value="">&nbsp;</option>'."\n";
	if ($dir = Safe::opendir("../overlays")) {

		// every php script is an overlay, except index.php and overlay.php
		while(($file = Safe::readdir($dir)) !== FALSE) {
			if($file == '.' || $file == '..' || is_dir($context['path_to_root'].'overlays/'.$file))
				continue;
			if($file == 'index.php')
				continue;
			if($file == 'overlay.php')
				continue;
			if(substr($file, 0, 9) == 'template_') $file = substr($file, 9);
			if(!preg_match('/(.*)\.php$/i', $file, $matches))
				continue;
			$overlays[] = $matches[1];
		}
		Safe::closedir($dir);
		if(@count($overlays)) {
			sort($overlays);
			foreach($overlays as $ovr) {
				$list .= '<option value="'.$ovr.'"';
				if ($ovr == $overlay) $list .= ' selected';
				$list .= '>'.$ovr."</option>\n";
			}
		}
	}
	return $list;
}
function get_overlay_fields_options($sel, $name) {

	$overlay_fields_options = '<option value="">&nbsp;</option>';

	include_once '../overlays/overlay.php';
	global $attributes;

	if ($obj = Overlay::bind($name)) {
	$attributes = $obj->attributes;
  }

	foreach($attributes as $label => $value) {
		$overlay_fields_options.='<option value="'.$label.'"';
		if ($sel == $label) $overlay_fields_options .= ' selected';
		$overlay_fields_options.='>'.$label.'</option>'."\n";
	}
	return $overlay_fields_options;
}

function traite_champ($champ) {
	return str_replace(chr(146),"'",$champ);
}

function traitement_section_field($id) {
	global $ligne, $section_rcd, $overlay, $anchor;
	$section_rcd['title'] = traite_champ($ligne[$id]);
	$section_rcd['nick_name'] = traite_champ($ligne[$id]);
	$section_rcd['anchor'] = $anchor;
	$section_rcd['overlay'] = $overlay;
}

function traitement_article_field($id, $article_field) {
	global $ligne, $article_rcd;
	$article_rcd[$article_field] = traite_champ($ligne[$id]);
}

function traitement_overlay_field($id, $overlay_field) {
	global $ligne, $overlay_rcd;
	$overlay_rcd[$overlay_field] = traite_champ($ligne[$id]);
}

function traitement_rcd() {
	global $section_rcd, $article_rcd, $overlay_rcd;
	global $sections_gen, $articles_gen, $articles_mod;
	global $overlay, $language, $anchor;

	if ($article_rcd['nick_name'] != '') $nick_name = $article_rcd['nick_name'];
	elseif ($article_rcd['title'] != '') $nick_name = $article_rcd['nick_name'] = $article_rcd['title'];
	else return null;

	if ($article_rcd['title'] == '') $article_rcd['title'] = $article_rcd['nick_name'];

	if ($nick_name != '' && ! array_key_exists($nick_name, $articles_gen)) {

		$section = $section_rcd['nick_name'];
		$section_anchor = Sections::lookup($section, $language);

		if ($section!='' && ! array_key_exists($section, $sections_gen)) {
			if (! $section_anchor) {
				$section_rcd['language'] = $language;
				$sections_gen[$section] = $section_rcd;
				$section_anchor = 'section:'.$section;
			}
			else {
				$section_rcd['language'] = $language;
				$sections_mod[$section] = $section_rcd;
			}
		}

		if ($article_rcd['anchor'] != '') $article_anchor = $article_rcd['anchor'];

		$anchor_anchor = Sections::lookup($article_anchor, $language);
		if ($article_anchor!='' && ! array_key_exists($article_anchor, $sections_gen)) {
			if (!$anchor_anchor) {
				$sections_gen[$article_anchor] = array('nick_name'=>$article_anchor, 'title'=>$article_anchor, 'anchor'=>$section_anchor, 'overlay'=>$overlay, 'language'=>$language);
				$anchor_anchor = 'section:'.$article_anchor;
			}
			else {
				$sections_mod[$article_anchor] = array('nick_name'=>$article_anchor, 'title'=>$article_anchor, 'anchor'=>$section_anchor, 'overlay'=>$overlay, 'language'=>$language);
			}
			$article_rcd['anchor'] = $anchor_anchor;
		}

		if ($article_rcd['anchor'] == '') $article_rcd['anchor'] = 'section:'.Sections::get_default();

	$overlay_rcd['overlay_type'] = $overlay;
		$overlay_rcd['reference'] = $nick_name; // for APM
		$article_rcd['overlay'] = serialize($overlay_rcd);
		$article_rcd['language'] = $language;
		$article_rcd['home_panel'] = 'none'; // for apm
		$article_rcd['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
		$article_anchor = Articles::lookup($nick_name, $language);
		if (!$article_anchor) {
			$articles_gen[$nick_name] = $article_rcd;
		}
		else {
			//unset($article_rcd['anchor']);
			$articles_mod[$nick_name] = $article_rcd;
		}
	}

	return '';//affiche_tableau($anchor, $section_rcd).'<br>'.affiche_tableau($nick_name, $article_rcd).'<br>';
}
function integre_rcd() {
	global $section_rcd, $article_rcd, $overlay_rcd;
	global $sections_gen, $articles_gen;
	global $overlay, $language, $anchor;

	if ($article_rcd['nick_name'] != '') $nick_name = $article_rcd['nick_name'];
	elseif ($article_rcd['title'] != '') $nick_name = $article_rcd['nick_name'] = $article_rcd['title'];
	else return null;

	if ($article_rcd['title'] == '') $article_rcd['title'] = $article_rcd['nick_name'];

	if ($nick_name != '' && ! array_key_exists($nick_name, $articles_gen)) {

		$section = $section_rcd['nick_name'];
		$section_anchor = Sections::lookup($section, $language);

		if ($section!='' && ! array_key_exists($section, $sections_gen)) {
			if (!$section_anchor) {
				$section_rcd['language'] = $language;
				$section_anchor = 'section:'.Sections::post($section_rcd);
				$sections_gen[$section] = $section_rcd;
			}
			else {
				$section_rcd['language'] = $language;
				$s = explode(':',$section_anchor);
				$section_rcd['id'] = $s[1];
				Sections::put($section_rcd);
			}
		}

		if ($article_rcd['anchor'] != '') $article_anchor = $article_rcd['anchor'];

		$anchor_anchor = Sections::lookup($article_anchor, $language);
		if ($article_anchor!='' && ! array_key_exists($article_anchor, $sections_gen)) {
			if (!$anchor_anchor) {
				$sections_gen[$article_anchor] = array('nick_name'=>$article_anchor, 'title'=>$article_anchor, 'anchor'=>$section_anchor, 'overlay'=>$overlay, 'language'=>$language);
				$anchor_anchor = 'section:'.Sections::post(array('nick_name'=>$article_anchor, 'title'=>$article_anchor, 'anchor'=>$section_anchor, 'overlay'=>$overlay, 'language'=>$language));
			}
			else {
				$s = explode(':',$anchor_anchor);
				Sections::put(array('id'=>$s[1], 'nick_name'=>$article_anchor, 'title'=>$article_anchor, 'anchor'=>$section_anchor, 'overlay'=>$overlay, 'language'=>$language));
			}
		}

		if ($anchor_anchor == '') $anchor_anchor = 'section:'.Sections::get_default();

		$article_rcd['anchor'] = $anchor_anchor;
		$overlay_rcd['overlay_type'] = $overlay;
		$overlay_rcd['reference'] = $nick_name; // for APM
		$article_rcd['overlay'] = serialize($overlay_rcd);
		$article_rcd['language'] = $language;
		$article_rcd['home_panel'] = 'none'; // for apm
		$article_rcd['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');

		$article_anchor = Articles::lookup($nick_name, $language);
		if (!$article_anchor) {
			$articles_gen[$nick_name] = $article_rcd;
			$article_anchor = 'article:'.Articles::post($article_rcd);
		}
		else {
			$s = explode(':',$article_anchor);
			$article_rcd['id'] = $s[1];
			Articles::put($article_rcd);
		}
	}

	return '';//affiche_tableau($anchor, $section_rcd).'<br>'.affiche_tableau($nick_name, $article_rcd).'<br>';
}
function affiche_tableau($titre, $tableau, $nbcell=null) {

	$elem = '<b>'.$titre.'</b><br>';
	$i = 0;
	foreach($tableau as $key=>$value) {
		if (is_array($value)) $elem .= affiche_tableau($key, $value, 2);
		else  {
			if ($i == 0) $elem .= '<table border=1>';
			if ($nbcell == 1) $elem .= '<tr><td>'.$value.'</td></tr>';
			else $elem .= '<tr><td>'.$key.'</td><td>'.$value.'</td></tr>';
			$i++;
			if ($i == count($tableau)) $elem .= '</table>';
		}
	}

	return $elem;
}
?>