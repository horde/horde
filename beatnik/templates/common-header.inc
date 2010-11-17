<?php
/**
 * Copyright 2005-2007 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

// This check has to come after the page has finished all work in case the
// status has changed due to a now-completed edit.
if (count(Beatnik::needCommit())) {
    foreach(Beatnik::needCommit() as $domain) {
        $notification->push(sprintf(_("You have uncommitted changes in %s."), $domain));
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!--    Beatnik Copyright 2005-2007, Ben Klang <ben@alkaloid.net> -->
<!--    Horde Project: http://www.horde.org/                       -->
<!--    Beatnik: http://projects.alkaloid.net/                     -->
<!--    Horde Licenses: http://www.horde.org/licenses/             -->
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
<?php echo Horde_Themes::includeStylesheetFiles(); ?>
</head>
<body>
