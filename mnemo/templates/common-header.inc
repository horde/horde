<?php
if (isset($language)) {
    header('Content-type: text/html; charset=UTF-8');
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!-- Mnemo: Copyright 2002-2009 The Horde Project.  Mnemo is under a Horde license. -->
<!--   Horde Project: http://www.horde.org/ | Mnemo: http://www.horde.org/mnemo/    -->
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
Horde::includeFavicon();

$injector->getInstance('Horde_Themes_Css')->addStylesheet($registry->get('themesfs') . '/categoryCSS.php', $registry->get('themesuri') . '/categoryCSS.php');
Horde::includeStylesheetFiles();

$bc = $prefs->getValue('show_panel')
    ? 'rightPanel'
    : '';

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
</head>

<body<?php if ($bc) echo ' class="' . $bc . '"' ?><?php if ($bi = Horde_Util::nonInputVar('bodyId')) echo ' id="' . $bi . '"'; ?>>
