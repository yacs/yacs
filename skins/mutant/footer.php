<?php
// footer panel
echo '<footer id="footer_panel" class="pas">'."\n";

    Skin::footer();
    Skin::mentions();


echo '</footer>'."\n";


// insert the dynamic footer, if any, including inline scripts
echo $context['page_footer'];

echo '</body>'."\n";
echo '</html>';
?>