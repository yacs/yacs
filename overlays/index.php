<?php
/**
 * extends articles functionality with overlays
 *
 * @todo create a 'task' overlay (cloubech)
 *
 * Overlays are a mean to extend the functionality of articles without having to copy the whole
 * package over and over.
 *
 * If, during some analysis of your needs, you end up with the conclusion that adding some fields to an article
 * would suffice, then consider overlays as the faster track to deliver.
 *
 * YACs is provided with following overlays:
 * - contact - to build address books
 * - issue - to support bug tracking
 * - petition - to show evidence of people opinions
 * - poll - to understand average view on any topic
 * - recipe - to create cooking books (see below)
 * - vote - to achieve motivated decisions
 *
 * Let's consider for example the way we have developed the support of recipes within YACS.
 *
 * [title]Storing recipes[/title]
 *
 * YACS itself is almost capable of supporting recipes from the outset. We have sections to classify recipes,
 * we have articles to describe recipes and to add camera shots of example realizations. We also are able to attach additional
 * information and to add links to related pages on the web. More importantly, we are able to create a community of people
 * interested in published recipes and to gather comments from all of them.
 *
 * Basically, this means that we are not too far away from a complete recipes database. The only
 * missing piece is a way to structure the several components of a regular recipe. In all countries of the world, a
 * good recipe is made of:
 * - a title,
 * - a short description,
 * - the number of people to be served,
 * - the average preparation time,
 * - the planned cooking time,
 * - the list of ingredients,
 * - the several steps of the preparation.
 *
 * We could use existing fields of YACS articles to support some of these data:
 * - the 'title' field will store the recipe title,
 * - the 'introduction' field will store the short description,
 * - the 'description' field will store the several steps of the preparation, plus any information we would add.
 *
 * The simplest solution for remaining fields would be to put them into an array, and to save the array
 * with the article. For example,we would serialize this array and store it into the 'overlay' field
 * that has been designed for this purpose in the database.
 *
 * As the 'overlay' field has been indexed, its content may be retrieved through usual searching mechanisms.
 * For us, it means that if our recipe is based upon potatoes, that are described into the 'ingredients' field,
 * our recipe will be listed on searches on the word 'potatoe'. Aren't you impressed?
 *
 * [title]Editing recipes[/title]
 *
 * Now that we have a solution for the back-end, let's talk of the front-end.
 *
 * What we need is to extend the existing scripts to handle additional fields correctly.
 * For example, in articles/edit.php, we would like to introduce specialized fields if a recipe
 * is submitted or modified.
 *
 * As usual with YACS we have selected straighforward extension mechanisms.
 *
 * Firstly, the serialization and unserialization operations are supported natively by PHP and YACS.
 *
 * We could use the following code to save and restore some recipe along one article:
 * [php]
 * // serialize our recipe
 * $article['overlay'] = serialize($recipe);
 *
 * // save the article plus the recipe in it
 * Articles::post($article);
 *
 * ...
 *
 * // retrieve the article
 * $article =& Articles::get(123);
 *
 * // restore the embedded recipe
 * $recipe = Safe::unserialize($article['overlay']);
 * [/php]
 *
 * Secondly, because the support of serialized objects is a complex matter with every scripted language, including PHP,
 * we have selected to implement a lightweight function instead.
 * If you need some object to add useful methods to our recipe, just ask for it!
 *
 * [php]
 * // create a recipe
 * $recipe =& new Recipe();
 *
 * // display an empty form with adequate fields
 * $form = '<form method="post">';
 * $form .= $recipe->get_fields($host);
 * $form .= Skin::build_submit_button('Send your recipe!');
 * $form .= '</form>';
 *
 * ...
 *
 * // parse POST values gathered through the form
 * $recipe->parse($_REQUEST);
 *
 * ...
 * [/php]
 *
 * The set of required member functions is well-known, because they are supporting generic
 * operations around articles. Therefore, we have a class out of it named Overlay.
 * Basically, our Recipe has just to implement Overlay to be fully-supported by YACS articles.
 *
 * [php]
 * // our recipe
 * class Recipe extends Overlay {
 *
 *   // create some HTML fields to be inserted into a form
 *   function get_fields($host) {
 *   ...
 *   }
 *
 *   // parse some values from a form
 *   function parse($fields) {
 *   ...
 *   }
 * }
 * [/php]
 *
 * Thirdly, the array serialized in the 'overlay' field must have a 'type' attribute. Of course,
 * this atribute will be used by YACS to distinguish among several overlays.
 *
 * In our case, we have decided that all our recipes will have the type 'recipe:john.doe@acme.com:v1'.
 * Ok, this is a rather long an complicated type. But it enables us to distinguish from recipes introduced
 * by other people, while providing an email address for further support.
 * And it carries some version information as well, that we could use in the future to
 * convert our recipes to a new format.
 *
 * Here is the code to enable the creation of a new recipe. In sections/view.php, we just add
 * a link to articles/edit.php to create a new article. Moreover, we are adding some parameters
 * to say the articles/edit.php script what we want exactly.
 *
 * [php]
 * // in sections/view.php, add a link to create a recipe
 * if(preg_match('/:with_recipes:/i', $section['options']) {
 *   $page .= Skin::build_link(rawurlencode('articles/edit.php/overlay/recipe:john.doe@acme.com:v1'
 *     .'?anchor=section:'.$section['id']), i18n::s('Post a new recipe'));
 * }
 * [/php]
 *
 * Here is the code to handle the overlay in the articles/edit.php script.
 * Once we have retrieved that an overlay is required, we just ask for the related instance, and that's it.
 * The two little helper functions have been implemented in shared/global.php.
 *
 * [php]
 * // we have to create a new overlay
 * if($context['arguments'][0] == 'overlay')
 *   $overlay = new_overlay($context['arguments'][1]);
 *
 * // or we have to edit an existing one
 * elseif($id = $context['arguments'][0]) {
 *   $item =& Articles::get($id);
 *   $overlay = get_overlay($item);
 * }
 * [/php]
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see overlays/overlay.php
 * @see overlays/recipe.php
 * @see overlays/poll.php
 */
include_once '../shared/global.php';

// load localized strings
i18n::bind('overlays');

// load the skin
load_skin('overlays');

// the title of the page
$context['page_title'] = i18n::s('Available overlays');

// splash message
if(Surfer::is_associate())
	$context['text'] .= '<p>'.i18n::s('Overlays listed below can be used to customise articles attached to some sections.').'</p>';

// list overlays available on this system
$context['text'] .= '<ul>';
if ($dir = Safe::opendir($context['path_to_root'].'overlays')) {

	// every php script is an overlay, except index.php, overlay.php, and hooks
	while(($file = Safe::readdir($dir)) !== FALSE) {
		if($file == '.' || $file == '..' || is_dir($context['path_to_root'].'overlays/'.$file))
			continue;
		if($file == 'index.php')
			continue;
		if($file == 'overlay.php')
			continue;
		if(preg_match('/hook\.php$/i', $file))
			continue;
		if(!preg_match('/(.*)\.php$/i', $file, $matches))
			continue;
		$overlays[] = $matches[1];
	}
	Safe::closedir($dir);
	if(@count($overlays)) {
		sort($overlays);
		foreach($overlays as $overlay)
			$context['text'] .= '<li>'.$overlay."</li>\n";
	}
}
$context['text'] .= '</ul>';

// how to use overlays
if(Surfer::is_associate()) {
	$context['text'] .= '<p>'.sprintf(i18n::s('For example, if you want to apply the overlay <code>foo</code>, go to the %s, and select a target section, or create a new one.'), Skin::build_link('sections/', i18n::s('site map'), 'shortcut')).'</p>'
		.'<p>'.i18n::s('In the form used to edit the section, type the keyword <code>foo</code> in the overlay field, then save changes.').'</p>';
}

// referrals, if any
if(Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y'))) {

	$cache_id = 'overlays/index.php#referrals#';
	if(!$text =& Cache::get($cache_id)) {

		// box content in a sidebar box
		include_once '../agents/referrals.php';
		if($text = Referrals::list_by_hits_for_url($context['url_to_root_parameter'].'overlays/index.php'))
			$text =& Skin::build_box(i18n::s('Referrals'), $text, 'navigation', 'referrals');

		// save in cache for one hour 60 * 60 = 3600
		Cache::put($cache_id, $text, 'referrals', 3600);

	}

	// in the extra panel
	$context['extra'] .= $text;
}

// render the skin
render_skin();

?>