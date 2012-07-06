<?php echo $GLOBALS['injector']->getInstance('Horde_View_Topbar')->render(); ?>
<?php echo Turba::menu() ?>
<form name="directory_list" action="<?php echo Horde::url('browse.php') ?>" method="post">
<?php echo Horde_Util::formInput() ?>
<?php if ($browse_source_count > 1): ?>
 <div class="rightFloat">
  <label for="menu_source" class="hidden"><?php echo _("Source:") ?></label>
  <select id="menu_source" name="source" onchange="document.directory_list.submit();"><?php echo $browse_source_options ?></select>
  <noscript><input class="button" type="submit" value="<?php echo _("Browse") ?>" /></noscript>
 </div>
<?php endif; ?>
</form>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
