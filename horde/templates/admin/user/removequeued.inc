<form method="post" action="<?php echo Horde::selfUrl() ?>">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="form" value="removequeued" />
<input type="hidden" name="user_name" value="<?php echo $f_user_name ?>" />

<h1 class="header">
 <?php echo _("Pending Signups:") ?>
</h1>
<p class="horde-content">
 <?php printf(_("Are you sure you want to remove the signup request for \"%s\"?"), $f_user_name) ?>
</p>
<p class="horde-form-buttons">
  <input class="horde-delete" type="submit" value="<?php echo _("Remove") ?>" />
</p>
</form>
