<?php

/** 
 * Configuration form for matomo client
 * Parameters will be saved in parameters/matomo.include.php
 * 
 * @author devalxr
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

include_once '../../shared/global.php';
include_once 'tracker.php';

CONST MATO_LOCAL = array(
    'matomo_explain_fr'        => 'Paramètres de traçage par Matomo. Pensez à activer l\'extension dans la configuration de Yacs.',
    'matomo_explain_en'        => 'Tracking parameters by Matomo. Do not forget to activate the extension in Yacs configuration.',
    'matomo_active_fr'         => 'Activation',
    'matomo_active_en'         => 'Activation',
    'matomo_active_hint_fr'    => 'Tracer les visites et les événements',
    'matomo_active_hint_en'    => 'Track visits and events',
    'matomo_url_fr'            => 'Serveur Matomo',
    'matomo_url_en'            => 'Matomo server',
    'matomo_url_hint_fr'       => 'URL complète de la racine de votre installation matomo',
    'matomo_url_hint_en'       => 'Complete URL of the root of your matomo instance',
    'matomo_token_fr'          => 'Jeton d\'autentification',
    'matomo_token_en'          => 'Authentification token',
    'matomo_token_hint_fr'     => 'Jeton à générer dans matomo pour autoriser a envoyer des statistiques',
    'matomo_token_hint_en'     => 'Token to generate inside matomo in order to authorize data sending',
    'matomo_idsite_fr'         => 'ID du site dans matomo',
    'matomo_idsite_en'         => 'Site ID in matomo',
    'matomo_idsite_hint_fr'    => 'Dans le cas où matomo collecte des données de plusieurs sites',
    'matomo_idsite_hint_en'    => 'In case of matomo gather data from several websites',
    'matomo_trackAll_fr'       => 'Etendue',
    'matomo_trackAll_en'       => 'Scope',
    'matomo_trackAll_hint_fr'  => 'Enregistrer aussi les visites des pages non indexées',
    'matomo_trackAll_hint_en'  => 'Record also visits on non-indexed pages',
);

// load localized strings
i18n::bind('control');

// load the skin
load_skin('control');

// do not index this page
$context->sif('robots','noindex');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// ensure we have an associate
if(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');
        
// display the input form
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] !== 'POST') ) {
    
    
    $tracker = new tracker();
    $tracker->loadConf();
    
    $context['text'] .= tag::_p(i18n::l(MATO_LOCAL, 'matomo_explain'));
    
    // the user form
    $context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>'."\n";
    
    $fields = array();
    
    $label  = i18n::l(MATO_LOCAL, 'matomo_active');
    $context->sif('matomo_active', 'N');
    $checked = ($context['matomo_active'] === 'Y')? 'checked' : '';
    $input = '<input type="checkbox" name="matomo_active" value="Y" '.$checked.'/> '.$hint   = i18n::l(MATO_LOCAL,'matomo_active_hint');
    $fields[] = array($label, $input);
    
    $label  = i18n::l(MATO_LOCAL, 'matomo_url');
    $context->sif('matomo_url','');
    $input  = '<input type="text" name="matomo_url" size="50" value="'.encode_field($context['matomo_url']).'" maxlength="255" />';
    $hint   = i18n::l(MATO_LOCAL,'matomo_url_hint');
    $fields[] = array($label, $input, $hint);
    
    $label  = i18n::l(MATO_LOCAL, 'matomo_token');
    $context->sif('matomo_token','');
    $input  = '<input type="text" name="matomo_token" size="50" value="'.encode_field($context['matomo_token']).'" />';
    $hint   = i18n::l(MATO_LOCAL,'matomo_token_hint');
    $fields[] = array($label, $input, $hint);
    
    $label  = i18n::l(MATO_LOCAL, 'matomo_idsite');
    $context->sif('matomo_idsite','1');
    $input  = '<input type="text" name="matomo_idsite" size="5" value="'.encode_field($context['matomo_idsite']).'" />';
    $hint   = i18n::l(MATO_LOCAL,'matomo_idsite_hint');
    $fields[] = array($label, $input, $hint);
    
    $label  = i18n::l(MATO_LOCAL, 'matomo_trackAll');
    $context->sif('matomo_trackAll', 'N');
    $checked = ($context['matomo_trackAll'] === 'Y')? 'checked' : '';
    $input = '<input type="checkbox" name="matomo_trackAll" value="Y" '.$checked.'/> '.$hint   = i18n::l(MATO_LOCAL,'matomo_trackAll_hint');;
    $fields[] = array($label, $input);
    
    $context['text'] .= Skin::build_form($fields);
    
    //
    // bottom commands
    //
    $menu = array();

    // the submit button
    $menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

    // control panel
    if(file_exists('../../parameters/control.include.php'))
            $menu[] = Skin::build_link('control/', i18n::s('Control Panel'), 'span');
    
    // insert the menu in the page
    $context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

    // end of the form
    $context['text'] .= '</div></form>';
    
    
// save updated parameters
} else {
    
    // backup the old version
    Safe::unlink($context['path_to_root'].'parameters/matomo.include.php.bak');
    Safe::rename($context['path_to_root'].'parameters/matomo.include.php', $context['path_to_root'].'parameters/matomo.include.php.bak');
    
    if(!isset($_REQUEST['matomo_active']))      $_REQUEST['matomo_active'] = 'N';
    if(!isset($_REQUEST['matomo_trackAll']))    $_REQUEST['matomo_trackAll'] = 'N';
    
    // build the new configuration file
    $content = '<?php'."\n"
            .'// This file has been created by the configuration script included/matomo/configure.php'."\n"
            .'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n"
            .'global $context;'."\n";
    
    if(isset($_REQUEST['matomo_active']))
		$content .= '$context[\'matomo_active\']=\''.addcslashes($_REQUEST['matomo_active'], "\\'")."';\n";
    
    if(isset($_REQUEST['matomo_url']))
		$content .= '$context[\'matomo_url\']=\''.addcslashes($_REQUEST['matomo_url'], "\\'")."';\n";
    
    if(isset($_REQUEST['matomo_token']))
		$content .= '$context[\'matomo_token\']=\''.addcslashes($_REQUEST['matomo_token'], "\\'")."';\n";
    
    if(isset($_REQUEST['matomo_idsite']))
		$content .= '$context[\'matomo_idsite\']=\''.addcslashes($_REQUEST['matomo_idsite'], "\\'")."';\n";
    
    if(isset($_REQUEST['matomo_trackAll']))
		$content .= '$context[\'matomo_trackAll\']=\''.addcslashes($_REQUEST['matomo_trackAll'], "\\'")."';\n";
    
    $content .= '?>'."\n";
    
    if(!Safe::file_put_contents('parameters/matomo.include.php', $content)) {

            Logger::error(sprintf(i18n::s('ERROR: Impossible to write to the file %s. The configuration has not been saved.'), 'parameters/matomo.include.php'));

            // allow for a manual update
            $context['text'] .= '<p style="text-decoration: blink;">'.sprintf(i18n::s('To actually change the configuration, please copy and paste following lines by yourself in file %s.'), 'parameters/matomo.include.php')."</p>\n";

    // job done
    } else {

            $context['text'] .= '<p>'.sprintf(i18n::s('The following configuration has been saved into the file %s.'), 'parameters/matomo.include.php')."</p>\n";

            // purge the cache
            Cache::clear();

            // also purge session cache for this surfer
            unset($_SESSION['l10n_modules']);

            // remember the change
            $label = sprintf(i18n::c('%s has been updated'), 'parameters/matomo.include.php');
            Logger::remember('control/configure.php: '.$label);

    }
    
    // display updated parameters
    if(is_callable(array('skin', 'build_box')))
            $context['text'] .= Skin::build_box(i18n::s('Configuration'), Safe::highlight_string($content), 'folded');
    else
            $context['text'] .= Safe::highlight_string($content);
    
    // follow-up commands
    $follow_up = i18n::s('Where do you want to go now?');
    $menu = array();
    $menu = array_merge($menu, array( 'control/' => i18n::s('Control Panel') ));
    $menu = array_merge($menu, array( 'included/matomo/configure.php' => i18n::s('Configure again') ));
    $follow_up .= Skin::build_list($menu, 'menu_bar');
    $context['text'] .= Skin::build_block($follow_up, 'bottom');
    
}

// render the skin
render_skin();