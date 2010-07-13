<?php
if (isset($language)) {
    header('Content-type: text/html; charset=' . $GLOBALS['registry']->getCharset());
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<?php echo !empty($language) ? '<html lang="' . strtr($language, '_', '-') . '">' : '<html>' ?>
<head>
<?php

$page_title = $registry->get('name');
if (!empty($title)) {
    $page_title .= ' :: ' . $title;
}

Horde::outputMetaTags();
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
