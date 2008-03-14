<?php
/**
 * import external skins
 *
 * Following patterns are replaced by PHP scripts in the resulting template.php:
 * - &lt;$BlogTitle$&gt; - the page title
 * - &lt;$BlogDescription$&gt; - the cover page, if any
 *
 * This is initial work that only support basic blog templates
 *
 * @see skins/upload.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Import {

	function process($template, $directory = 'blogger_import') {
		global $context;

		// we also have to prepare a skin -- the skin split is for scripts/validate.php
		$skin = '<?php'."\n"
			.'class Sk'.'in extends Skin_skeleton {'."\n";

		// fix images relative path
		$template = preg_replace('/src="(.+?)"/ie', "'src=\"'.fix_relative('\\1', '$directory').'\"'", $template);
		$template = preg_replace('/background="(.+?)"/ie', "'background=\"'.fix_relative('\\1', '$directory').'\"'", $template);
		$template = preg_replace('/url\((.+?)\)/ie', "'url('.fix_relative('\\1', '$directory').')'", $template);

		// <$BlogArchiveFileName$>
		$from = '/<\$BlogArchiveFileName\$>/i';
		$to = '<?php echo $context[\'url_to_root\'].\'categories/view.php?id=monthly\'; ?>';
		$template = preg_replace($from, $to, $template);

		// <$BlogArchiveName$>
		$from = '/<\$BlogArchiveName\$>/i';
		$to = '<?php echo \'Monthly Archives\'; ?>';
		$template = preg_replace($from, $to, $template);

		// <$BlogArchiveLink$>
		$from = '/<\$BlogArchiveLink\$>/i';
		$to = '<?php echo $context[\'url_to_root\'].\'categories/view.php?id=monthly\'; ?>';
		$template = preg_replace($from, $to, $template);

		// <$BlogArchiveURL$>
		$from = '/<\$BlogArchiveURL\$>/i';
		$to = '<?php echo $context[\'url_to_root\'].\'categories/view.php?id=monthly\'; ?>';
		$template = preg_replace($from, $to, $template);

		// <$BlogDescription$>
		$from = '/<\$BlogDescription\$>/i';
		$to = '<?php'."\n"
			.'if(is_object($anchor))'."\n"
			.'	echo $anchor->get_teaser();'."\n"
			.'?>';
		$template = preg_replace($from, $to, $template);

		// <$BlogMemberProfile$>
		$from = '/<\$BlogMemberProfile\$>/i';
		$to = '<?php echo $context[\'creator_profile\']; ?>';
		$template = preg_replace($from, $to, $template);

		// <$BlogMetaData$>
		$from = '/<\$BlogMetaData\$>/i';
		$to = '<?php echo $context[\'page_header\']; ?>';
		$template = preg_replace($from, $to, $template);

		// <$BlogPageTitle$>
		$from = '/<\$BlogPageTitle\$>/i';
		$to = '<?php echo ucfirst(strip_tags($context[\'page_title\'])); ?>';
		$template = preg_replace($from, $to, $template);

		// <$BlogTitle$>
		$from = '/<\$BlogTitle\$>/i';
		$to = '<?php echo ucfirst(strip_tags($context[\'page_title\'])); ?>';
		$template = preg_replace($from, $to, $template);

		// <$BlogURL$>
		$from = '/<\$BlogURL\$>/i';
		$to = '<?php echo $context[\'url_to_home\'].$context[\'url_to_root\']; ?>';
		$template = preg_replace($from, $to, $template);

		// only one type of new lines
		$template = str_replace("\r\n", "\n", $template);
		$template = str_replace("\r", "\n", $template);

		// <MainOrArchivePage>
		$from = '/<MainOrArchivePage>(.*?)<\/MainOrArchivePage>/is';
		$to = '';
		$template = preg_replace($from, $to, $template);

		// the <BlogItemComments>...</BlogItemComments> block
		$areas = preg_split('/<BlogItemComments>(.*?)<\/BlogItemComments>/is', trim($template), -1, PREG_SPLIT_DELIM_CAPTURE);
		$template = '';
		$index = 0;
		foreach($areas as $area) {
			switch($index%3) {
			case 0: // prefix block
				$template .= $area;
				break;
			case 1: // commenting area

				// <$BlogCommentDateTime$>
				$from = '/<\$BlogCommentDateTime\$>/i';
				$to = '\'.Skin::build_date($item[\'create_date\']).\'';
				$area = preg_replace($from, $to, $area);

				// <$BlogCommentNumber$>
				$from = '/<\$BlogCommentNumber\$>/i';
				$to = '\'.$item[\'id\'].\'';
				$area = preg_replace($from, $to, $area);

				// <$BlogCommentAuthor$>
				$from = '/<\$BlogCommentAuthor\$>/i';
				$to = '\'.$item[\'create_name\'].\'';
				$area = preg_replace($from, $to, $area);

				// <$BlogCommentAuthorNickname$>
				$from = '/<\$BlogCommentAuthorNickname\$>/i';
				$to = '\'.$item[\'create_name\'].\'';
				$area = preg_replace($from, $to, $area);

				// <$BlogCommentBody$>
				$comment_prefix .= 'unset($BlogCommentBody);'."\n"
					.'$BlogCommentBody .= Codes::beautify(trim($item[\'description\']));'."\n"
					."\n";
				$from = '/<\$BlogCommentBody\$>/i';
				$to = '\'.$BlogCommentBody.\'';
				$area = preg_replace($from, $to, $area);

				// make a skin
				$skin .= "\n"
					.'	function layout_comment($item, $variant = \'compact\') {'."\n"
					.'		global $context;'."\n"
					.'		'.str_replace("\n", "\n\t\t", $comment_prefix)."\n"
					.'		//	array($prefix, $title, $suffix, $type, $icon)'."\n"
					.'		$prefix = \''.trim($item_prefix).'\';'."\n"
					.'		$title = \'_\';'."\n"
					.'		$suffix = \''.trim($area).'\';'."\n"
					.'		return array($prefix, $title, $suffix, \'comment\', NULL);'."\n"
					."	}\n";

				break;
			case 2: // suffix block
				$template .= $area;
				break;
			}
			$index++;
		}

		// the <Blogger>...</Blogger> block
		$areas = preg_split('/<Blogger>(.*?)<\/Blogger>/is', trim($template), -1, PREG_SPLIT_DELIM_CAPTURE);
		$template = '';
		$index = 0;
		foreach($areas as $area) {

			// blogging area
			if($index == 1) {
				$template .= '<?php '."\n"
					.'// display the menu bar, if any'."\n"
					.'if(@count($context[\'page_menu\']) > 0)'."\n"
					.'	echo Skin::build_list($context[\'page_menu\'], \'page_menu\');'."\n"
					."\n"
					.'// display the prefix, if any'."\n"
					.'if($context[\'prefix\'])'."\n"
					.'	echo $context[\'prefix\'];'."\n"
					."\n"
					.'// display the error message, if any'."\n"
					.'if($context[\'error\'])'."\n"
					.'	  echo Skin::build_block($context[\'error\'], \'error\');'."\n"
					."\n"
					.'// display the page image, if any'."\n"
					.'if($context[\'page_image\'])'."\n"
					.'	  echo \'<img src="\'.$context[\'page_image\'].\'" class="icon" alt=""'.EOT.'\';'."\n"
					."\n"
					.'// the main part of the page'."\n"
					.'echo $context[\'text\'];'."\n"
					."\n"
					.'// display the suffix, if any'."\n"
					.'if($context[\'suffix\'])'."\n"
					.'	echo \'<p>\'.$context[\'suffix\'].\'</p>\';'."\n"
					.'?>';

				// make a skin for each item of the blogging area

				// break lines to not interfere with regular code
				$area = str_replace("\n", "'\n\t\t\t.'", addcslashes(trim($area), "'"));

				// <$BlogDateHeaderDate$>
				$from = '/<\$BlogDateHeaderDate\$>/i';
				$to = '\'.Skin::build_date($item[\'create_date\']).\'';
				$area = preg_replace($from, $to, $area);

				// <$BlogItemArchiveFileName$>
				$from = '/<\$BlogItemArchiveFileName\$>/i';
				$to = '\'.$context[\'url_to_root\'].Articles::get_url($item[\'id\']).\'';
				$area = preg_replace($from, $to, $area);

				// <$BlogItemAuthor$>
				$from = '/<\$BlogItemAuthor\$>/i';
				$to = '\'.$item[\'create_name\'].\'';
				$area = preg_replace($from, $to, $area);

				// <$BlogItemAuthorNickname$>
				$from = '/<\$BlogItemAuthorNickname\$>/i';
				$to = '\'.$item[\'create_name\'].\'';
				$area = preg_replace($from, $to, $area);

				// <$BlogItemBody$>
				$article_prefix .= 'unset($BlogItemBody);'."\n"
					.'// the introduction'."\n"
					.'if($item[\'introduction\'])'."\n"
					.'	$BlogItemBody .= Codes::beautify(trim($item[\'introduction\']));'."\n"
					.'elseif(!is_object($overlay)) {'."\n"
					.'	// extract up to markup, if any'."\n"
					.'	$raw = preg_split(\'/(\\[|<)/\', $item[\'description\']);'."\n"
					.'	$BlogItemBody .= Skin::strip(trim($raw[0]), 30);'."\n"
					.'}'."\n"
					.'if($suffix)'."\n"
					.'	$BlogItemBody = \' -&nbsp;\'.$suffix;'."\n"
					."\n"
					.'// insert overlay data, if any'."\n"
					.'if(is_object($overlay))'."\n"
					.'	$BlogItemBody .= $overlay->get_text(\'list\', $item);'."\n"
					."\n";
				$from = '/<\$BlogItemBody\$>/i';
				$to = '\'.$BlogItemBody.\'';
				$area = preg_replace($from, $to, $area);

				// <$BlogItemCommentCount$>
				$article_prefix .= 'unset($BlogItemCommentCount);'."\n"
					.'// info on related comments'."\n"
					.'include_once $context[\'path_to_root\'].\'comments/comments.php\';'."\n"
					.'$BlogItemCommentCount = Comments::count_for_anchor(\'article:\'.$item[\'id\']);'."\n"
					."\n";
				$from = '/<\$BlogItemCommentCount\$>/i';
				$to = '\'.$BlogItemCommentCount.\'';
				$area = preg_replace($from, $to, $area);

				// <$BlogItemControl$> -- the menu bar for associates and poster
				$article_prefix .= 'unset($BlogItemControl);'."\n"
					.'if(Surfer::is_associate() || Surfer::is_creator($item[\'create_id\']) || Surfer::is_creator($item[\'edit_id\'])) {'."\n"
					.'	$menu = array( Articles::get_url($item[\'id\'], \'edit\') => i18n::s(\'edit\'),'."\n"
					.'		Articles::get_url($item[\'id\'], \'delete\') => i18n::s(\'delete\') );'."\n"
					.'	$BlogItemControl = \' \'.Skin::build_list($menu, \'menu\');'."\n"
					.'}'."\n"
					."\n";
				$from = '/<\$BlogItemControl\$>/i';
				$to = '\'.$BlogItemControl.\'';
				$area = preg_replace($from, $to, $area);

				// <$BlogItemDateTime$>
				$from = '/<\$BlogItemDateTime\$>/i';
				$to = '\'.Skin::build_date($item[\'create_date\']).\'';
				$area = preg_replace($from, $to, $area);

				// <$BlogItemNumber$>
				$from = '/<\$BlogItemNumber\$>/i';
				$to = '\'.$item[\'id\'].\'';
				$area = preg_replace($from, $to, $area);

				// <$BlogItemPermalinkURL$>
				$from = '/<\$BlogItemPermalinkURL\$>/i';
				$to = '\'.$context[\'url_to_root\'].Articles::get_url($item[\'id\']).\'';
				$area = preg_replace($from, $to, $area);

				// <$BlogItemTitle$> -- it has to be the last one for this item
				$from = '/<\$BlogItemTitle\$>/i';
				list($item_prefix, $item_suffix) = preg_split($from, $area);

				// make a skin
				$skin .= "\n"
					.'	function layout_article($item, $variant = \'compact\') {'."\n"
					.'		global $context;'."\n"
					.'		'.str_replace("\n", "\n\t\t", $article_prefix)."\n"
					.'		//	array($prefix, $title, $suffix, $type, $icon)'."\n"
					.'		$prefix = \''.trim($item_prefix).'\';'."\n"
					.'		$title = trim($item[\'title\']);'."\n"
					.'		$suffix = \''.trim($item_suffix).'\';'."\n"
					.'		return array($prefix, $title, $suffix, \'article\', NULL);'."\n"
					."	}\n";

			} else {
				// suffix block
				$template .= $area;
			}
			$index++;
		}

		// skin end
		$skin .= "}\n"
			.'?>'."\n";

		// backup the old skin, if any
		Safe::unlink($context['path_to_root'].'skins/'.$directory.'/skin.php.bak');
		Safe::rename($context['path_to_root'].'skins/'.$directory.'/skin.php', $context['path_to_root'].'skins/'.$directory.'/skin.php.bak');

		// create a new skin file
		if(!$skin)
			Skin::error(i18n::s('No blogging block has been found.'));

		elseif(!Safe::make_path('skins/'.$directory))
			Skin::error(sprintf(i18n::s('Impossible to create path %s.'), 'skins/'.$directory));

		elseif(!$handle = Safe::fopen($context['path_to_root'].'skins/'.$directory.'/skin.php', 'wb'))
			Skin::error(sprintf(i18n::s('Impossible to write to %s.'), $context['path_to_root'].'skins/'.$directory.'/skin.php'));

		// write to it
		else {
			fwrite($handle, $skin);
			fclose($handle);
		}

		// backup the old template, if any
		Safe::unlink($context['path_to_root'].'skins/'.$directory.'/template.php.bak');
		if(!$template)
			Skin::error(i18n::s('Empty template file'));

		else
			Safe::rename($context['path_to_root'].'skins/'.$directory.'/template.php', $context['path_to_root'].'skins/'.$directory.'/template.php.bak');

		// create a new template file
		if(!Safe::make_path('skins/'.$directory))
			Skin::error(sprintf(i18n::s('Impossible to create path %s.'), 'skins/'.$directory));

		elseif(!$handle = Safe::fopen($context['path_to_root'].'skins/'.$directory.'/template.php', 'wb'))
			Skin::error(sprintf(i18n::s('Impossible to write to %s.'), $context['path_to_root'].'skins/'.$directory.'/template.php'));

		// write to it
		else {
			fwrite($handle, $template);
			fclose($handle);
			$context['text'] .= '<p>'.sprintf(i18n::s('Template has been imported. Check skin %s'), Skin::build_link('skins/test.php?skin='.$directory, $directory, 'shortcut'))."</p>\n";
		}

		return NULL;

	}


}

function fix_relative($path, $directory) {
	global $context;

	// get rid of " and of '
	$path = trim(stripslashes($path), '"\'');

	if(preg_match('/^(ftp:|http:)/i', $path))
		return $path;
	if(preg_match('/^\//', $path))
		return $path;
	return $context['url_to_root'].'skins/'.$directory.'/'.$path;
}

?>