<?php
/**
 * $Horde: beatnik/templates/common-header.inc,v 1.9 2009/09/15 09:20:03 duck Exp $
 *
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
if (!empty($title)) $page_title .= ' :: ' . $title;
if (!empty($refresh_time) && ($refresh_time > 0) && !empty($refresh_url)) {
    echo "<meta http-equiv=\"refresh\" content=\"$refresh_time;url=$refresh_url\">\n";
}

Horde::includeScriptFiles();

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<link href="<?php echo $GLOBALS['registry']->getImageDir()?>/favicon.ico" rel="SHORTCUT ICON" />
<?php echo Horde::includeStylesheetFiles(); ?>
</head>
<body<?php if ($bc = Horde_Util::nonInputVar('bodyClass')) echo ' class="' . $bc . '"' ?><?php if ($bi = Horde_Util::nonInputVar('bodyId')) echo ' id="' . $bi . '"'; ?>>
