<?php
if (isset($language)) {
    header('Content-type: text/html; charset=UTF-8');
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!-- Trean: Copyright 2000-2009 The Horde Project.  Trean is under a Horde license. -->
<!--   Horde Project: http://www.horde.org/ | Trean: http://www.horde.org/trean/    -->
<!--              Horde Licenses: http://www.horde.org/licenses/                    -->
<?php echo !empty($language) ? '<html lang="' . strtr($language, '_', '-') . '">' : '<html>' ?>
<head>
<?php

$page_title = $registry->get('name');
if (!empty($title)) {
    $page_title .= ' :: ' . $title;
}

Horde::outputMetaTags();
Horde::includeScriptFiles();

Horde_Themes::addStylesheet($registry->get('themesfs') . '/grids-min.css', $registry->get('themesuri')  . '/grids-min.css');

$rss = Horde::url('rss.php', true, -1);
if (Horde_Util::getFormData('f')) {
    $rss = Horde_Util::addParameter($rss, 'f', Horde_Util::getFormData('f'), false);
}
echo '<link rel="alternate" type="application/rss+xml" title="' . htmlspecialchars(_("Bookmarks Feed")) . '" href="' . $rss . '" />';

$bc = Horde_Util::nonInputVar('bodyClass');
if ($prefs->getValue('show_folder_actions')) {
    if ($bc) {
        $bc .= ' ';
    }
    $bc .= 'folderActions';
}

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<?php echo Horde::getFavicon(); ?>
<?php Horde_Themes::includeStylesheetFiles() ?>
</head>

<body<?php if ($bc) echo ' class="' . $bc . '"' ?><?php if ($bi = Horde_Util::nonInputVar('bodyId')) echo ' id="' . $bi . '"'; ?>>
