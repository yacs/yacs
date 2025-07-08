<?php

Class Skin extends Skin_Skeleton {
    /**
     * Méthode d'initialisation du skin : définit les constantes globales du skin
     */
    public static function initialize() {
        global $context;

        $context['footer_logo'] = 'skins/mutant/images/logo-footer.png';
        $context['footer_name'] = 'Le Bazar en Vercors';
        $context['footer_adresse'] = 'Place du Tilleul<br>26420 Saint Martin en Vercors';
        $context['footer_tel_display'] = '';
        $context['footer_tel_link'] = '';
        $context['footer_email'] = '';
        $context['footer_horaires'] = '';
        $context['footer_links'] = [
          ['url'=>'https://facebook.com/LeBazarEnVercors','label'=>'Facebook'],
          ['url'=>'https://lebazarenvercors.fr/section-2-programme','label'=>'Programme complet']
        ];
        $context['footer_internal'] = [
          ['url'=>$context['url_to_root'].'plan-du-site.php', 'label'=>'Plan du site'],
          ['url'=>$context['url_to_root'].'query.php', 'label'=>'Contact']
        ];
    }

    /**
     * Affiche le pied de page du site avec données dynamiques.
     */
    public static function footer($home=FALSE) {
        global $context;

        // fallback pour chaque variable
        $footer_logo      = isset($context['footer_logo'])      ? $context['footer_logo']      : 'skins/mutant/images/logo-footer.png';
        $footer_name      = isset($context['footer_name'])      ? $context['footer_name']      : 'Le Bazar en Vercors';
        $footer_adresse   = isset($context['footer_adresse'])   ? $context['footer_adresse']   : 'Place du Tilleul<br>26420 Saint Martin en Vercors';
        $footer_tel_display = isset($context['footer_tel_display']) ? $context['footer_tel_display'] : '';
        $footer_tel_link  = isset($context['footer_tel_link'])  ? $context['footer_tel_link']  : '';
        $footer_email     = isset($context['footer_email'])     ? $context['footer_email']     : '';
        $footer_horaires  = isset($context['footer_horaires'])  ? $context['footer_horaires']  : '';
        $footer_links     = isset($context['footer_links'])     ? $context['footer_links']     : [
            ['url'=>'https://facebook.com/LeBazarEnVercors','label'=>'Facebook'],
            ['url'=>'https://lebazarenvercors.fr/section-2-programme','label'=>'Programme complet']
        ];
        $footer_internal  = isset($context['footer_internal'])  ? $context['footer_internal']  : [
            ['url'=>$context['url_to_root'].'plan-du-site.php', 'label'=>'Plan du site'],
            ['url'=>$context['url_to_root'].'query.php', 'label'=>'Contact']
        ];

        echo '<div class="has-gutter left_footer grid-4-small-2">'."\n";

        // Colonne 1 : Blason/logo
        echo '<div class="blason">';
        echo '<a href="' . $context['url_to_root'] . '" title="' . encode_field(i18n::s('Front page')) . '" accesskey="1">
            <img src="' . $context['url_to_root'] . $footer_logo . '" alt="accueil ' . encode_field($context['site_name']) . '" />
        </a>'."\n";
        echo '</div>'."\n";

        // Colonne 2 : Infos contact
        echo '<div class="contact one-quarter">';
        echo '<h4>' . $footer_name . '</h4>
        <p>
            <strong>Adresse :</strong><br>' . $footer_adresse . '<br>
           <!-- <strong>Téléphone :</strong> <a href="tel:' . $footer_tel_link . '" style="color:inherit;text-decoration:underline dotted;">' . $footer_tel_display . '</a><br>
            <strong>Email :</strong> <a href="mailto:' . $footer_email . '">' . $footer_email . '</a><br>
            <strong>Horaires :</strong> ' . $footer_horaires . '-->
        </p>
        ';
        echo '</div>'."\n";

        // Colonne 3 : Liens externes
        echo '<div class="footer-links one-quarter">';
        echo '<h4>En savoir plus</h4>
        <ul class="unstyled">';
        foreach($footer_links as $link) {
            echo '<li><a href="'.htmlspecialchars($link['url']).'" target="_blank" rel="noopener">'.htmlspecialchars($link['label']).'</a></li>';
        }
        echo '</ul>';
        echo '</div>'."\n";

        // Colonne 4 : Liens internes
        echo '<div class="menufoot one-quarter">';
        foreach($footer_internal as $link) {
            echo '<p><a href="'.htmlspecialchars($link['url']).'">'.htmlspecialchars($link['label']).'</a></p>';
        }
        echo '</div>'."\n";

        echo '</div>'."\n"; // grid-4
    }

    /**
     * Affiche la barre de mentions légales/admin
     */
    public static function mentions($home=FALSE) {
        global $context;
        echo '<div class="mentions">';
        echo '<p id="menufooter">';
        echo '<a href="'.$context['url_to_root'].'users/login.php" rel="nofollow">@</a>';
        echo ' | <a href="https://lebazarenvercors.fr/article-1-a-propos-de-ce-site"rel="noopener">Mentions légales</a>';
        if($context['skin_variant'] == 'home')
            echo ' | <a href="https://actupro.fr" target="_blank" rel="noopener">Création du site internet dans le Vercors Drôme</a>';
        echo '</p>';
        echo '</div>'."\n\n";
    }
}

?>