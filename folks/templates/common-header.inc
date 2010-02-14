<?php
if (isset($language)) {
    header('Content-type: text/html; charset=' . Horde_Nls::getCharset());
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!-- Folks: Copyright 2008 Obala d.o.o.. www.obala.si     -->
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
<link href="<?php echo $GLOBALS['registry']->getImageDir()?>/favicon.ico" rel="SHORTCUT ICON" />
<link rel="alternate" type="application/rss+xml" href="<?php echo Folks::getUrlFor('feed', 'online') ?>" title="<?php echo _("Online users") ?>" />
<?php if (Horde_Auth::isAuthenticated()): ?>
<link rel="alternate" type="application/rss+xml" href="<?php echo Folks::getUrlFor('feed', 'friends') ?>" title="<?php echo _("Online friends") ?>" />
<link rel="alternate" type="application/rss+xml" href="<?php echo Folks::getUrlFor('feed', 'activity') ?>" title="<?php echo _("Friends activity") ?>" />
<link rel="alternate" type="application/rss+xml" href="<?php echo Folks::getUrlFor('feed', 'know') ?>" title="<?php echo _("People you might know") ?>" />
<?php endif; ?>
<?php Horde::includeStylesheetFiles() ?>
</head>

<body>
