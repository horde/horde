<form method="get" name="appswitch" id="appswitch" action="<?php echo Horde::getServiceLink('options') ?>">
<?php echo Horde_Util::formInput() ?>
<div class="header">
 <span class="smallheader rightFloat">
  <?php echo Horde::label('app', _("Edit options for:")) ?>
  <select id="app" name="app" onchange="document.appswitch.submit();">
   <option value=""><?php echo _("Choose Application:") ?></option>
<?php if (!empty($apps['horde'])): ?>
   <option value="" disabled="disabled">- - - - - - - - -</option>
   <option value="horde"><?php echo _("Global Options") ?></option>
   <option value="" disabled="disabled">- - - - - - - - -</option>
<?php endif; ?>
<?php foreach (array_diff($apps, array('horde')) as $key => $val): ?>
   <option value="<?php echo htmlspecialchars($key) ?>"><?php echo htmlspecialchars($val) ?></option>
<?php endforeach; ?>
  </select>
  <input class="button" type="submit" onclick="return document.appswitch.app.selectedIndex != 0;" value="<?php echo _("Go") ?>" />
 </span>
 <span>
<?php if ($registry->getApp() == 'horde'): ?>
  <?php echo _("Global Options") ?>
<?php else: ?>
  <?php printf(_("Options for %s"), $GLOBALS['registry']->get('name')) ?>
<?php endif; ?>
 </span>
 <br class="clear" />
</div>
</form>
<br class="spacer" />
