<?php

header('Content-type: text/html; charset=UTF-8');

$html_attr = $body_attr = '';
if (isset($GLOBALS['language'])) {
    header('Vary: Accept-Language');
    $html_attr .= ' lang="' . strtr($GLOBALS['language'], '_', '-') . '"';
}
if ($hi = Horde_Util::nonInputVar('htmlId')) {
    $html_attr .= ' id="' . $hi . '"';
}
if ($bc = Horde_Util::nonInputVar('bodyClass')) {
    $body_attr .= ' class="' . $bc . '"';
}
if ($bi = Horde_Util::nonInputVar('bodyId')) {
    $body_attr .= ' id="' . $bi . '"';
}

$page_title = $GLOBALS['registry']->get('name');
if (!empty($title)) {
    $page_title .= ' :: ' . $title;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html<?php echo $html_attr ?>>
<head>
<?php

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

<body<?php echo $body_attr ?>>
