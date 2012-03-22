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

$page_output = $GLOBALS['injector']->getInstance('Horde_PageOutput');

$page_output->outputMetaTags();
if (!in_array($GLOBALS['registry']->getView(), array(Horde_Registry::VIEW_MINIMAL, Horde_Registry::VIEW_SMARTMOBILE))) {
    $page_output->includeFavicon();
    $page_output->includeStylesheetFiles();
    $page_output->includeScriptFiles();
    $page_output->outputLinkTags();
    $page_output->outputInlineScript();
}

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
</head>

<body<?php echo $body_attr ?>>
