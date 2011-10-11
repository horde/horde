<form name="edit_form" action="<?php echo Horde::url('edit.php') ?>" method="post">
 <?php echo Horde_Util::formInput() ?>
 <input type="hidden" name="actionID" value="save_file" />
 <input type="hidden" name="driver" value="<?php echo $vars->driver ?>" />
 <input type="hidden" name="dir" value="<?php echo $vars->dir ?>" />
 <input type="hidden" name="file" value="<?php echo $vars->file ?>" />

 <h1 class="header"><?php echo sprintf(_("Edit %s"), $vars->file) ?></h1>

 <textarea name="content" id="editcontent" rows="40"><?php echo htmlspecialchars($data) ?></textarea>
 <input type="submit" class="button" value="<?php echo _("Save") ?>" />
 <input type="reset" class="button" value="<?php echo _("Reset") ?>" />
 <input type="button" class="button" id="cancelbutton" value="<?php echo _("Cancel") ?>" />
</form>
