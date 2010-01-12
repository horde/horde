<?php

$cManager = new Horde_Prefs_CategoryManager();
$categories = $cManager->getSelect('default_category_select', $GLOBALS['prefs']->getValue('default_category'));

echo _("Default category for galleries");
echo $categories;
