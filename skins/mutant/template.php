<?php

include 'header.php';

// 3 columns container
echo '<section class="main" role="main">'."\n";
echo '<div class="inner">'."\n";

if(Surfer::is_associate()) {
    // first column, main content
    echo '<div class="grid-2-small-1 contenu has-gutter">'."\n";
    echo '<div class="content">'."\n";
} else {
    echo '<div class="grid-1">'."\n";
    echo '<div class="content">'."\n";
}

// display bread crumbs if not at the front page
if($context['skin_variant'] != 'home')
    Page::bread_crumbs(1);

// display main content
Page::content();
echo '</div>'."\n";

if(Surfer::is_associate()) {
    // navigation column
    echo '<nav id="navigation_panel" class="k-pas">'."\n";
    Page::side();
    Page::extra_panel(NULL, FALSE);
    echo '</nav>'."\n";
}
echo '</div>'."\n"; 
echo '</div>'."\n"; // inner

echo '</section>'."\n";

include 'footer.php';
?>