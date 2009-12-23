<?php
global $language;
if (isset($language)) {
    header('Content-type: text/html; charset=' . Horde_Nls::getCharset());
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<?php
echo (!empty($language) ? '<html lang="' . strtr($language, '_', '-') . '"' : '<html')
    . (($hi = Horde_Util::nonInputVar('htmlId')) ? ' id="' . $hi . '"' : '')
    . '>';
?>
<head>
<?php

$page_title = $GLOBALS['registry']->get('name');
if (!empty($title)) $page_title .= ' :: ' . $title;
if (!empty($refresh_time) && !empty($refresh_url)) {
    echo "<meta http-equiv=\"refresh\" content=\"$refresh_time;url=$refresh_url\" />\n";
}
?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<link href="<?php echo $GLOBALS['registry']->getImageDir() ?>/favicon.ico" rel="SHORTCUT ICON" />
<?php
echo Horde::includeStylesheetFiles(array('additional' => Horde_Util::nonInputVar('horde_css_stylesheets', array())));
echo Horde::includeScriptFiles();
echo implode("\n", Horde_Util::nonInputVar('linkTags', array()))
?>
</head>

<body<?php if ($bc = Horde_Util::nonInputVar('bodyClass')) echo ' class="' . $bc . '"' ?><?php if ($bi = Horde_Util::nonInputVar('bodyId')) echo ' id="' . $bi . '"'; ?>>
