<?php
$registry = Horde_Registry::singleton();
$rtl = isset(Horde_Nls::$config['rtl'][$GLOBALS['language']]);
$sidebar_width = isset($GLOBALS['prefs']) ? $GLOBALS['prefs']->getValue('sidebar_width') : 150;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "DTD/xhtml1-frameset.dtd">
<html>
<head>
 <link rel="SHORTCUT ICON" href="<?php echo $registry->getImageDir() ?>/favicon.ico" />
 <title><?php echo htmlspecialchars($registry->get('name')) ?></title>
</head>

<frameset id="hf" cols="<?php echo ($rtl ? '*,' : '') . $sidebar_width . ($rtl ? '' : ',*') ?>" framespacing="0" frameborder="0">
<?php if ($rtl): ?>
  <frame name="horde_main" src="<?php echo htmlspecialchars($main_page) ?>" scrolling="<?php echo $scrollbar ?>" noresize="noresize" />
  <frame name="horde_menu" src="<?php echo Horde::applicationUrl('services/portal/sidebar.php', true) ?>" scrolling="auto" noresize="noresize" />
<?php else: ?>
  <frame name="horde_menu" src="<?php echo Horde::applicationUrl('services/portal/sidebar.php', true) ?>" scrolling="auto" noresize="noresize" />
  <frame name="horde_main" src="<?php echo htmlspecialchars($main_page) ?>" scrolling="<?php echo $scrollbar ?>" noresize="noresize" />
<?php endif; ?>
</frameset>

</html>
