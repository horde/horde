<?php
if (isset($language)) {
    header('Content-type: text/html; charset=UTF-8');
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<!--
Koward - The Kolab warden

Copyright

2004 - 2009 Klarälvdalens Datakonsult AB
2009        The Horde Project

Koward is under the GPL. GNU Public License: http://www.fsf.org/copyleft/gpl.html -->

<?php echo !empty($language) ? '<html lang="' . strtr($language, '_', '-') . '">' : '<html>' ?>
<head>
<?php

global $registry;

$page_title = $registry->get('name');
$page_title .= !empty($this->title) ? ' :: ' . $this->title : '';

Horde::includeScriptFiles();
?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<link href="<?php echo Horde_Themes::img('favicon.ico', array('nohorde' => true)) ?>" rel="SHORTCUT ICON" />
<?php Horde_Themes::includeStylesheetFiles() ?>
</head>

<body>
