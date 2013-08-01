<form name="edit_form" action="<?php echo $this->self_url ?>" method="post">
 <?php echo $this->forminput ?>
 <input type="hidden" name="actionID" value="save_file" />
 <input type="hidden" name="driver" value="<?php echo $this->vars->driver ?>" />
 <input type="hidden" name="dir" value="<?php echo $this->vars->dir ?>" />
 <input type="hidden" name="file" value="<?php echo $this->vars->file ?>" />

 <h1 class="header"><?php echo sprintf(_("Edit %s"), $this->vars->file) ?></h1>

 <textarea name="content" id="gollem-edit" rows="38"><?php echo htmlspecialchars($this->data) ?></textarea>

<p class="horde-form-buttons">
 <input type="submit" class="horde-default" value="<?php echo _("Save") ?>" />
 <input type="reset" value="<?php echo _("Reset") ?>" />
 <input type="button" class="horde-cancel" id="cancelbutton" value="<?php echo _("Cancel") ?>" />
</p>
</form>
