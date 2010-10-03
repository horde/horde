<?php
if (isset($GLOBALS['language'])) {
    header('Content-type: text/html; charset=' . 'UTF-8');
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<?php
echo (!empty($GLOBALS['language']) ? '<html lang="' . strtr($GLOBALS['language'], '_', '-') . '"' : '<html')
    . (($hi = Horde_Util::nonInputVar('htmlId')) ? ' id="' . $hi . '"' : '')
    . '>';
?>
<head>
<?php

$page_title = $GLOBALS['registry']->get('name');
if (!empty($title)) {
    $page_title .= ' :: ' . $title;
}

Horde::outputMetaTags();

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<?php if (!$GLOBALS['browser']->isMobile()): ?>
<link href="<?php echo Horde_Themes::img('favicon.ico', array('nohorde' => true)) ?>" rel="SHORTCUT ICON" />
<?php
echo Horde_Themes::includeStylesheetFiles(array('additional' => Horde_Util::nonInputVar('horde_css_stylesheets', array())));
echo Horde::includeScriptFiles();
echo implode("\n", Horde_Util::nonInputVar('linkTags', array()));
Horde::outputInlineScript();
?>
<?php endif ?>
</head>

<body<?php if ($bc = Horde_Util::nonInputVar('bodyClass')) echo ' class="' . $bc . '"' ?><?php if ($bi = Horde_Util::nonInputVar('bodyId')) echo ' id="' . $bi . '"'; ?>>
