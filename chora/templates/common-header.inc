<?php
if (isset($language)) {
    header('Content-type: text/html; charset=UTF-8');
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<?php
echo !empty($language) ? '<html lang="' . strtr($language, '_', '-') . '">' : '<html><head>';

$page_title = $GLOBALS['registry']->get('name');
if (!empty($title)) {
    $page_title .= ' :: ' . $title;
}

Horde::outputMetaTags();

if (Horde_Util::nonInputVar('js_vars')) {
    Horde::addInlineScript('var Chora = ' . Horde_Serialize::serialize($js_vars, Horde_Serialize::JSON));
}

Horde::includeScriptFiles();

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<link href="<?php echo Horde_Themes::img('favicon.ico', array('nohorde' => true)) ?>" rel="SHORTCUT ICON" />
<?php Horde_Themes::includeStylesheetFiles() ?>
</head>

<body>
