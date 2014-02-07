<h1 class="header"><?php echo _("Script") ?></h1>
<?php if ($this->scriptexists): ?>
<table class="scriptHeader">
 <tr>
  <td align="center">
<?php if ($this->showactivate): ?>
   <form method="post" name="activate_script" action="<?php echo $this->scripturl ?>">
    <input type="hidden" name="actionID" value="action_activate" />
    <input class="horde-default" type="submit" name="submit" value="<?php echo _("Activate Script") ?>" />
   </form>
   <form method="post" name="deactivate_script" action="<?php echo $this->scripturl ?>">
    <input type="hidden" name="actionID" value="action_deactivate" />
    <input type="submit" name="submit" value="<?php echo _("Deactivate Script") ?>" />
   </form>
   <form method="post" name="show_active_script" action="<?php echo $this->scripturl ?>">
    <input type="hidden" name="actionID" value="show_active" />
    <input type="submit" name="submit" value="<?php echo _("Show Active Script") ?>" />
   </form>
<?php else: ?>
   <form method="post" name="show_current_script" action="<?php echo $this->scripturl ?>">
    <input type="submit" name="submit" value="<?php echo _("Show Current Script") ?>" />
   </form>
<?php endif; ?>
  </td>
 </tr>
</table>

<?php echo $this->renderPartial('script', array('collection' => $this->scripts)) ?>

<?php else: ?>
<div>[<?php echo _("No script generated.") ?>]</div>
<?php endif; ?>
