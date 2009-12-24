<?php
if (isset($language)) {
    header('Content-type: text/html; charset=' . Horde_Nls::getCharset());
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!-- Agora: Copyright 2002-2009 The Horde Project.  Agora is under a Horde license. -->
<!--   Horde Project: http://www.horde.org/ | Agora: http://www.horde.org/agora/    -->
<!--               Horde Licenses: http://www.horde.org/licenses/                   -->
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
<title><?php echo htmlspecialchars($page_title) ?></title>
<link href="<?php echo $GLOBALS['registry']->getImageDir() ?>/favicon.ico" rel="SHORTCUT ICON" />
<?php Horde::includeStylesheetFiles() ?>
<link rel="alternate" title="<?php echo _("Forums") ?>" href="<?php echo Horde_Util::addParameter(Horde::applicationUrl('rss/index.php', true, -1), 'scope', $scope) ?>" type="application/rss+xml" />
</head>

<body<?php if ($bc = Horde_Util::nonInputVar('bodyClass')) echo ' class="' . $bc . '"' ?><?php if ($bi = Horde_Util::nonInputVar('bodyId')) echo ' id="' . $bi . '"'; ?>>
