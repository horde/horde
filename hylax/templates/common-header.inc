<?php
if (isset($language)) {
    header('Content-type: text/html; charset=' . 'UTF-8');
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!-- Hylax: Copyright 2003-2010 The Horde Project.  Hylax is under a Horde license. -->
<!--   Horde Project: http://www.horde.org/ | Hylax: http://www.horde.org/hylax/    -->
<!--               Horde Licenses: http://www.horde.org/licenses/                   -->
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
<link href="<?php echo Horde_Themes::img('favicon.ico', array('nohorde' => true)) ?>" rel="SHORTCUT ICON" />
<?php Horde_Themes::includeStylesheetFiles() ?>
</head>

<body>
