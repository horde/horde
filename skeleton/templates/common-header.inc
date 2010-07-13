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

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<link href="<?php echo Horde_Themes::img('favicon.ico', array('nohorde' => true)) ?>" rel="SHORTCUT ICON" />
<?php echo Horde_Themes::includeStylesheetFiles() ?>
</head>

<body>
