<?php echo $GLOBALS['injector']->getInstance('Horde_View_Topbar')->render(); ?>
<?php echo Horde::menu(array('menu_ob' => true))->render() ?>
<form name="tjump" method="get" action="<?php echo Horde::url('ticket/', false, -1) ?>">
<?php echo Horde_Util::formInput() ?>
 <span class="rightFloat">
  <input type="text" size="8" name="id" value="<?php echo _("#Id") ?>" onfocus="this.value='';" <?php echo Horde::getAccessKeyAndTitle(_("_Go")) ?> /> <input type="submit" onclick="if (!document.tjump.id.value) return false;" class="button" value="<?php echo _("Go") ?>" />
 </span>
</form>
<?php echo Horde_Util::nonInputVar('menuBottom'); ?>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
