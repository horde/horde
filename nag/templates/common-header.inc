<?php
if (isset($language)) {
    header('Content-type: text/html; charset=' . $GLOBALS['registry']->getCharset());
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<?php echo !empty($language) ? '<html lang="' . strtr($language, '_', '-') . '">' : '<html>' ?>
<head>
<?php

$page_title = $registry->get('name');
if (!empty($title)) {
    $page_title .= ' :: ' . $title;
}

Horde::outputMetaTags();
Horde::includeScriptFiles();

$bc = $prefs->getValue('show_panel')
    ? 'rightPanel'
    : '';

?>
<title><?php echo htmlspecialchars($page_title) ?></title>
<link href="<?php echo Horde_Themes::img('favicon.ico', array('nohorde' => true)) ?>" rel="SHORTCUT ICON" />
<?php Horde_Themes::includeStylesheetFiles() ?>
<link href="<?php echo Horde::url('themes/categoryCSS.php') ?>" rel="stylesheet" type="text/css" />
<script src="<?php echo $GLOBALS['registry']->get('jsuri', 'horde') ?>/glow/1.5.1/core/core.js" type="text/javascript"></script>
<script src="<?php echo $GLOBALS['registry']->get('jsuri', 'horde') ?>/glow/1.5.1/widgets/widgets.js" type="text/javascript"></script>
<link href="<?php echo $GLOBALS['registry']->get('jsuri', 'horde') ?>/glow/1.5.1/widgets/widgets.css" type="text/css" rel="stylesheet" />
<script type="text/javascript">
var Nag = {};

glow.ready(function() {
    $('quickAddInfoPanel').style.display = 'block';
    Nag.quickAddPanel = new glow.widgets.InfoPanel('#quickAddInfoPanel', { context: '.quickAdd', pointerPosition: 't' });
});
</script>
</head>

<body<?php if ($bc) echo ' class="' . $bc . '"' ?>>

<div id="quickAddInfoPanel" style="display:none;">
 <h2><?php echo _("Quick Task Creation") ?></h2>
 <form method="post" action="quick.php">
  <p><?php echo _("Enter one task per line. Create child tasks by indenting them below their parent task. Include due dates like \"laundry tomorrow\" or \"get dry cleaning next Thursday\".") ?></p>
  <textarea name="quickText" id="quickText"></textarea>
  <p><input type="submit" value="<?php echo _("Create") ?>" /></p>
 </form>
</div>
