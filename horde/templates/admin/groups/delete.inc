<form name="delete" method="post" action="groups.php">
<input type="hidden" name="actionID" value="deleteform" />
<input type="hidden" name="gid" value="<?php echo htmlspecialchars($gid) ?>" />
<?php Horde_Util::pformInput() ?>

<h1 class="header">
 <?php echo Horde::img('group.png') ?>&nbsp;<?php printf(_("Are you sure you wish to delete \"%s\"?"), htmlspecialchars($group)) ?>
</h1>

<p class="horde-form-buttons">
  <input type="submit" class="horde-delete" name="confirm" value="<?php echo _("Delete") ?>" />
  <input type="submit" class="horde-cancel" name="confirm" value="<?php echo _("Cancel") ?>" />
</p>
</form>
