<?php
if (isset($language)) {
    header('Content-type: text/html; charset=' . $GLOBALS['registry']->getCharset());
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!-- Kastalia: Copyright 2009 Andre Pawlowski  -->
<!--     Horde Project: http://www.horde.org/ | Kastalia: http://h4des.org/index.php?inhalt=kastalia     -->
<!--                 Horde Licenses: http://www.horde.org/licenses/                       -->
<?php echo !empty($language) ? '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' . strtr($language, '_', '-') . '" lang="' . strtr($language, '_', '-') . '">' : '<html>' ?>


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
<?php Horde_Themes::includeStylesheetFiles() ?>
</head>

<body>
