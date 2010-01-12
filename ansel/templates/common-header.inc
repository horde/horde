<?php
if (isset($language)) {
    header('Content-type: text/html; charset=' . Horde_Nls::getCharset());
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!--  Ansel: Copyright 2001-2010 The Horde Project.  Ansel is under the GPL.   -->
<!-- Horde Project: http://www.horde.org/ | Ansel: http://www.horde.org/ansel/ -->
<!--        GNU Public License: http://www.fsf.org/copyleft/gpl.html           -->
<?php echo !empty($language) ? '<html lang="' . strtr($language, '_', '-') . '">' : '<html>' ?>
<head>
<?php

$page_title = $registry->get('name');
if (!empty($title)) $page_title .= ' :: ' . $title;
if (!empty($refresh_time) && ($refresh_time > 0) && !empty($refresh_url)) {
    echo "<meta http-equiv=\"refresh\" content=\"$refresh_time;url=$refresh_url\">\n";
}
Horde::includeScriptFiles();
?>
<title><?php echo htmlspecialchars($page_title, ENT_COMPAT, Horde_Nls::getCharset()) ?></title>
<link href="<?php echo $GLOBALS['registry']->getImageDir() ?>/favicon.ico" rel="SHORTCUT ICON" />
<link rel="SHORTCUT ICON" href="<?php echo $GLOBALS['registry']->getImageDir() ?>/favicon.ico" />
<?php echo Ansel::stylesheetLinks() ?>
<?php echo Horde::wrapInlineScript(Ansel::initJSVars());?>
<?php Horde::outputInlineScript(); ?>
</head>

<body<?php if ($bc = Horde_Util::nonInputVar('bodyClass')) echo ' class="' . $bc . '"' ?><?php if ($bi = Horde_Util::nonInputVar('bodyId')) echo ' id="' . $bi . '"'; ?>>
