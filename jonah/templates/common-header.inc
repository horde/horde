<?php
if (isset($language)) {
    header('Content-type: text/html; charset=' . Horde_Nls::getCharset());
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!-- Jonah: Copyright 1999-2009 The Horde Project.  Jonah is under a Horde license. -->
<!--   Horde Project: http://www.horde.org/ | Jonah: http://www.horde.org/jonah/    -->
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

if ($channel_id = Horde_Util::getFormData('channel_id')) {
    $rss_url_params = array('channel_id' => $channel_id);
    if ($tag_id = Horde_Util::getFormData('tag_id')) {
        $rss_url_params['tag_id'] = $tag_id;
    }
    echo '<link rel="alternate" type="application/rss+xml" title="RSS 0.91" href="' . Horde_Util::addParameter(Horde::applicationUrl('delivery/rss.php', true, -1), $rss_url_params) . '" />';
}
?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<link href="<?php echo Horde_Themes::img(null, array('notheme' => true, 'nohorde' => true));?>/favicon.ico" rel="SHORTCUT ICON" />
<?php Horde_Themes::includeStylesheetFiles() ?>
</head>

<body<?php if ($bc = Horde_Util::nonInputVar('bodyClass')) echo ' class="' . $bc . '"' ?><?php if ($bi = Horde_Util::nonInputVar('bodyId')) echo ' id="' . $bi . '"'; ?>>
