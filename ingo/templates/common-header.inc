<?php
if (isset($language)) {
    header('Content-type: text/html; charset=UTF-8');
    header('Vary: Accept-Language');
}
$page_title = $GLOBALS['registry']->get('name');
if (!empty($title)) {
    $page_title .= ' :: ' . $title;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<?php echo !empty($language) ? '<html lang="' . strtr($language, '_', '-') . '">' : '<html>' ?>
<head>
<?php
    Horde::outputMetaTags();
    Horde::includeStylesheetFiles();
    Horde::includeScriptFiles();
    Horde::includeFavicon();
    Horde::outputInlineScript();
?>
<title><?php echo htmlspecialchars($page_title) ?></title>
</head>

<body>
