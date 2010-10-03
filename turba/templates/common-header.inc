<?php
global $language;
if (isset($language)) {
    header('Content-type: text/html; charset=' . 'UTF-8');
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!-- Turba: Copyright 2000-2010 The Horde Project.  Turba is under an ASL license. -->
<!--   Horde Project: http://www.horde.org/ | Turba: http://www.horde.org/turba/   -->
<!--          Horde ASL License: http://www.horde.org/licenses/asl.php             -->
<?php echo !empty($language) ? '<html lang="' . strtr($language, '_', '-') . '">' : '<html>' ?>
<head>
<?php

$page_title = $registry->get('name');
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

<body<?php if ($bc = Horde_Util::nonInputVar('bodyClass')) echo ' class="' . $bc . '"' ?>>
