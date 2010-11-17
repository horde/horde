<?php
if (isset($language)) {
    header('Content-type: text/html; charset=UTF-8');
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

$page_title = $GLOBALS['registry']->get('name');
if (!empty($title)) {
    $page_title .= ' :: ' . $title;
}

Horde::outputMetaTags();
Horde::includeScriptFiles();

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<?php echo Horde::getFavicon(); ?>
<?php echo Ansel::stylesheetLinks() ?>
<?php echo Horde::wrapInlineScript(Ansel::initJSVars());?>
<?php Horde::outputInlineScript(); ?>
</head>

<body>
