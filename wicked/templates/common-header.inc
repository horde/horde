<?php
if (isset($language)) {
    header('Content-type: text/html; charset=UTF-8');
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!-- Wicked: Copyright 2003-2010 The Horde Project.  Wicked is under a Horde license. -->
<!--    Horde Project: http://www.horde.org/ | Wicked: http://www.horde.org/wicked/   -->
<!--               Horde Licenses: http://www.horde.org/licenses/                     -->
<?php echo !empty($language) ? '<html lang="' . strtr($language, '_', '-') . '">' : '<html>' ?>
<head>
<?php

$page_title = $registry->get('name');
if (!empty($title)) {
    $page_title .= ' :: ' . $title;
}

Horde::outputMetaTags();
Horde::includeScriptFiles();
Horde::includeFavicon();
Horde::includeStylesheetFiles();

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<link href="<?php echo Horde::url('opensearch.php', true, -1) ?>" rel="search" type="application/opensearchdescription+xml" title="<?php echo $registry->get('name') . ' (' . Horde::url('', true) . ')' ?>" />
</head>

<body>
