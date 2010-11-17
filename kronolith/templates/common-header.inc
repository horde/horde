<?php

Horde::addScriptFile('effects.js', 'horde');
Horde::addScriptFile('views.js', 'kronolith');
Horde::addScriptFile('stripe.js', 'horde');

if (isset($language)) {
    header('Content-type: text/html; charset=UTF-8');
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!--   Kronolith: Copyright 2000-2010 The Horde Project.  Kronolith is under the GPL.  -->
<!-- Horde Project: http://www.horde.org/ | Kronolith: http://www.horde.org/kronolith/ -->
<!--              GNU Public License: http://www.fsf.org/copyleft/gpl.html             -->
<?php echo !empty($language) ? '<html lang="' . strtr($language, '_', '-') . '">' : '<html>' ?>
<head>
<?php

$page_title = $registry->get('name');
if (!empty($title)) {
    $page_title .= ' :: ' . $title;
}

Horde::outputMetaTags();
Horde::includeScriptFiles();
require KRONOLITH_TEMPLATES . '/javascript_defs.php';

$bc = (isset($view) && is_object($view) && $prefs->getValue('show_panel'))
    ? 'rightPanel'
    : '';

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<?php echo Horde::getFavicon(); ?>
<?php Horde_Themes::includeStylesheetFiles() ?>
<?php foreach ($GLOBALS['display_calendars'] as $calendar): ?>
<link href="<?php echo Kronolith::feedUrl($calendar) ?>" rel="alternate" type="application/atom+xml" />
<?php endforeach; ?>
<?php Horde::outputInlineScript(); ?>
</head>

<body<?php if ($bc) echo ' class="' . $bc . '"' ?>>
