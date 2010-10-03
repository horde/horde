<?php
if (isset($language)) {
    header('Content-type: text/html; charset=UTF-8');
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!-- news: Copyright 2004-2010 The Horde Project. news is under a Horde license. -->
<!--     Horde Project: http://www.horde.org/ | news: http://www.horde.org/horde/     -->
<!--                 Horde Licenses: http://www.horde.org/licenses/                       -->
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
<link rel="alternate" title="<?php echo _("Last news") ?>" href="<?php echo Horde::url('rss/news.php', true, -1) ?>" type="application/rss+xml" />
<link rel="alternate" title="<?php echo _("Last comments") ?>" href="<?php echo Horde::url('rss/comments.php', true, -1) ?>" type="application/rss+xml" />
<?php Horde_Themes::includeStylesheetFiles() ?>
</head>

<body>
