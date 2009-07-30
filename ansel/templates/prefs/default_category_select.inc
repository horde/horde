<?php

require_once 'Horde/Prefs/CategoryManager.php';
$cManager = new Prefs_CategoryManager();
$categories = $cManager->getSelect('default_category_select', $GLOBALS['prefs']->getValue('default_category'));

echo _("Default category for galleries");
echo $categories;
