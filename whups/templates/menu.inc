<form name="tjump" method="get" action="<?php echo Horde::applicationUrl('ticket/', false, -1) ?>">
<?php echo Horde_Util::formInput() ?>
<div id="menu">
 <span class="rightFloat">
  <input type="text" size="8" name="id" value="<?php echo _("#Id") ?>" onfocus="this.value='';" <?php echo Horde::getAccessKeyAndTitle(_("_Go")) ?> /> <input type="submit" onclick="if (!document.tjump.id.value) return false;" class="button" value="<?php echo _("Go") ?>" />
 </span>
 <?php echo Whups::getMenu('string') ?>
 <br class="clear" />
</div>
</form>
<?php echo Horde_Util::nonInputVar('menuBottom'); ?>
<?php require HORDE_BASE . '/services/portal/sidebar.php'; ?>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
