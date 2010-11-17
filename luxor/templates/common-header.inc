<?php
if (isset($language)) {
    header('Content-type: text/html; charset=UTF-8');
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!--  Luxor: Copyright 2003-2009 The Horde Project.  Luxor is under the GPL.   -->
<!-- Horde Project: http://www.horde.org/ | Luxor: http://www.horde.org/luxor/ -->
<!--             Horde Licenses: http://www.horde.org/licenses/                -->
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
Horde::includeStylesheetFiles();

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
</head>

<body<?php if ($bc = Horde_Util::nonInputVar('bodyClass')) echo ' class="' . $bc . '"' ?><?php if ($bi = Horde_Util::nonInputVar('bodyId')) echo ' id="' . $bi . '"'; ?>>
