<?php
if (isset($language)) {
    header('Content-type: text/html; charset=' . $GLOBALS['registry']->getCharset());
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!-- Whups: Copyright 2002-2010 The Horde Project.  Whups is under a Horde license. -->
<!--   Horde Project: http://www.horde.org/ | Whups: http://www.horde.org/whups/    -->
<!--              Horde Licenses: http://www.horde.org/licenses/                    -->
<?php echo !empty($language) ? '<html lang="' . strtr($language, '_', '-') . '">' : '<html>' ?>
<head>
<?php

$page_title = $GLOBALS['registry']->get('name');
if (!empty($title)) {
    $page_title .= ' :: ' . $title;
}

Horde::outputMetaTags();
Horde::includeScriptFiles();

if (isset($ticket)) {
    echo '<link rel="alternate" type="application/rss+xml" title="' . htmlspecialchars('[#' . $ticket->getId() . '] ' . $ticket->get('summary')) . '" href="' . Whups::urlFor('ticket_rss', $ticket->getId(), true, -1) . '" />';
}
if (isset($whups_query) && isset($whups_query->id)) {
    echo '<link rel="alternate" type="application/rss+xml" title="' . htmlspecialchars($whups_query->name) . '" href="' . Whups::urlFor('query_rss', empty($whups_query->slug) ? array('id' => $whups_query->id) : array('slug' => $whups_query->slug), true, -1) . '" />';
}

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<link href="<?php echo Horde_Themes::img('favicon.ico', array('nohorde' => true)) ?>" rel="SHORTCUT ICON" />
<link href="<?php echo Horde::url('opensearch.php', true, -1) ?>" rel="search" type="application/opensearchdescription+xml" title="<?php echo $registry->get('name') . ' (' . Horde::url('', true) . ')' ?>" />
<?php Horde_Themes::includeStylesheetFiles() ?>
</head>

<body>
