<?php
if (isset($language)) {
    header('Content-type: text/html; charset=' . NLS::getCharset());
    header('Vary: Accept-Language');
}
echo !empty($language) ? '<html lang="' . strtr($language, '_', '-') . '">' : '<html><head>';

$page_title = $GLOBALS['registry']->get('name');
if (!empty($title)) $page_title .= ' :: ' . $title;
if (!empty($refresh_time) && ($refresh_time > 0) && !empty($refresh_url)) {
    echo "<meta http-equiv=\"refresh\" content=\"$refresh_time;url=$refresh_url\">\n";
}

if (Util::nonInputVar('js_vars')) {
    echo '<script type="text/javascript">//<![CDATA[' . "\n" .
       'var Chora = ' . Horde_Serialize::serialize($js_vars, SERIALIZE_JSON, NLS::getCharset()) . ";\n//]]></script>\n";
}
Horde::includeScriptFiles();

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<link href="<?php echo $GLOBALS['registry']->getImageDir() ?>/favicon.ico" rel="SHORTCUT ICON" />
<?php echo Horde::stylesheetLink('chora') ?>
</head>

<body<?php if ($bc = Util::nonInputVar('bodyClass')) echo ' class="' . $bc . '"' ?><?php if ($bi = Util::nonInputVar('bodyId')) echo ' id="' . $bi . '"'; ?>>
