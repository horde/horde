<form name="removeuser" action="<?php echo Horde::selfUrl() ?>" method="post">
<input type="hidden" name="form" value="remove" />
<?php Horde_Util::pformInput() ?>

<h1 class="header">
 <?php printf(_("Remove user: %s"), $f_user_name) ?>
</h1>
<p class="horde-content">
 <?php printf(_("Really delete \"%s\"? This operation cannot be undone."), $f_user_name) ?>
</p>
<p class="horde-form-buttons">
 <input type="submit" class="horde-delete" name="submit" value="<?php echo _("Remove user") ?>" />
 <input type="submit" class="horde-cancel" name="submit" value="<?php echo _("Cancel") ?>" />
</p>
</form>
