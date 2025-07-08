<?php
defined('YACS') or exit('Script must be included');

if(isset($context['page_language']))
    $language = $context['page_language'];
else
    $language = $context['language'];

echo '<!doctype html>'."\n"
    .'<html lang="'.$language.'">'."\n"
    .'<head>'."\n";
echo "\t".'<meta charset="'.$context['charset'].'">'."\n";
echo "\t".'<meta name="viewport" content="width=device-width, initial-scale=1">'."\n";
if(!$context['site_icon']) {
    echo "\t".'<link rel="icon" href="'.$context['url_to_root'].$context['skin'].'/images/favicon.ico" type="image/x-icon" />'."\n";
}
Page::meta();
?>
<style>
@import url('https://fonts.googleapis.com/css?family=Lato:400,700|Space+Mono|Roboto');
</style>
<?php
echo '</head>'."\n";
Page::body();
echo '<header id="header_panel" class="k-pas" role="banner">'."\n";
echo '<div class="header-flex inner">'."\n"; //
echo '<div class="logo-group">'."\n";
echo '<a class="logo" href="/">'."\n";
echo '<img src="'.$context['url_to_root'].$context['skin'].'/images/logo.png" alt="le Bazar en Vercors" width="200">'."\n";
echo '</a>'."\n";
echo '</div>'."\n";
echo '<nav class="main-nav">'."\n";
Page::tabs(FALSE, FALSE, FALSE, FALSE, 'compact');
echo '</nav>'."\n";
echo '</div>'."\n";
echo '</header>'."\n";
?>