<?php
if (isset($language)) {
    header('Content-type: text/html; charset=UTF-8');
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!-- Folks: Copyright 2008 Obala d.o.o.. www.obala.si     -->
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
<?php echo Horde::getFavicon(); ?>
<link rel="alternate" type="application/rss+xml" href="<?php echo Folks::getUrlFor('feed', 'online') ?>" title="<?php echo _("Online users") ?>" />
<?php if ($GLOBALS['registry']->isAuthenticated()): ?>
<link rel="alternate" type="application/rss+xml" href="<?php echo Folks::getUrlFor('feed', 'friends') ?>" title="<?php echo _("Online friends") ?>" />
<link rel="alternate" type="application/rss+xml" href="<?php echo Folks::getUrlFor('feed', 'activity') ?>" title="<?php echo _("Friends activity") ?>" />
<link rel="alternate" type="application/rss+xml" href="<?php echo Folks::getUrlFor('feed', 'know') ?>" title="<?php echo _("People you might know") ?>" />
<?php endif; ?>
<?php Horde_Themes::includeStylesheetFiles() ?>
</head>

<body>
