<?php
global $language;
if (isset($language)) {
    header('Content-type: text/html; charset=' . NLS::getCharset());
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!-- Passwd: Copyright 2002-2009 The Horde Project.  Passwd is under a Horde license. -->
<!--   Horde Project: http://www.horde.org/ | Passwd: http://www.horde.org/passwd/    -->
<!--                Horde Licenses: http://www.horde.org/licenses/                    -->
<?php echo !empty($language) ? '<html lang="' . strtr($language, '_', '-') . '">' : '<html>' ?>
<head>
<?php

/* Print the page title. */
$page_title = $registry->get('name');
if (!empty($title)) {
    $page_title .= ' :: ' . $title;
}

Horde::outputMetaTags();

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<link href="<?php echo $GLOBALS['registry']->getImageDir()?>/favicon.ico" rel="SHORTCUT ICON" />
<?php echo Horde::stylesheetLink('passwd') ?>
</head>

<body<?php if ($bc = Util::nonInputVar('bodyClass')) echo ' class="' . $bc . '"' ?><?php if ($bi = Util::nonInputVar('bodyId')) echo ' id="' . $bi . '"'; ?>>
