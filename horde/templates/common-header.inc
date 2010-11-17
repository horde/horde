<?php
if (isset($GLOBALS['language'])) {
    header('Content-type: text/html; charset=UTF-8');
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

if (!$GLOBALS['browser']->isMobile()) {
    Horde::includeFavicon();
    Horde::includeStylesheetFiles();
    Horde::includeScriptFiles();
    echo implode("\n", Horde_Util::nonInputVar('linkTags', array()));
    Horde::outputInlineScript();
}

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
</head>

<body<?php if ($bc = Horde_Util::nonInputVar('bodyClass')) echo ' class="' . $bc . '"' ?><?php if ($bi = Horde_Util::nonInputVar('bodyId')) echo ' id="' . $bi . '"'; ?>>
