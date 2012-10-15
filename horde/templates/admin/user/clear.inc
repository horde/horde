<form name="clearuser" action="<?php echo Horde::url('admin/user.php') ?>" method="post">
<?php echo Horde_Util::formInput() ?>
<input type="hidden" name="form" value="clear" />
<input type="hidden" name="user_name" value="<?php echo htmlspecialchars($f_user_name) ?>" />

<h1 class="header">
 <?php printf(_("Clear out user: %s"), htmlspecialchars($f_user_name)) ?>
</h1>
<p class="horde-content">
 <?php printf(_("Really remove user data for user \"%s\"? This operation cannot be undone."), htmlspecialchars($f_user_name)) ?>
</p>
<p class="horde-form-buttons">
 <input type="submit" class="horde-delete" name="submit" value="<?php echo _("Clear user") ?>" />
 <input type="submit" class="horde-cancel" name="submit" value="<?php echo _("Cancel") ?>" />
</p>
</form>
