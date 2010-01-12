<?php
$style_select = Ansel::getStyleSelect('default_gallerystyle_select', $GLOBALS['prefs']->getValue('default_gallerystyle'));
echo _("Default style for galleries");
echo $style_select . '<br />';