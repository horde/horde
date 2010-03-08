<?php
if (isset($language)) {
    header('Content-type: text/html; charset=' . Horde_Nls::getCharset());
    header('Vary: Accept-Language');
}
echo !empty($language) ? '<html lang="' . strtr($language, '_', '-') . '">' : '<html><head>';

$page_title = $GLOBALS['registry']->get('name');
if (!empty($title)) $page_title .= ' :: ' . $title;
if (!empty($refresh_time) && ($refresh_time > 0) && !empty($refresh_url)) {
    echo "<meta http-equiv=\"refresh\" content=\"$refresh_time;url=$refresh_url\">\n";
}

if (Horde_Util::nonInputVar('js_vars')) {
    echo '<script type="text/javascript">//<![CDATA[' . "\n" .
       'var Chora = ' . Horde_Serialize::serialize($js_vars, Horde_Serialize::JSON, Horde_Nls::getCharset()) . ";\n//]]></script>\n";
}
Horde::includeScriptFiles();

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<link href="<?php echo Horde_Themes::img('favicon.ico', array('nohorde' => true)) ?>" rel="SHORTCUT ICON" />
<?php Horde_Themes::includeStylesheetFiles() ?>
</head>

<body>
