<form method="post" id="wicked-edit" action="<?php echo $this->action ?>">
<?php echo $this->formInput ?>
<input type="hidden" name="page" value="NewPage" />
<input type="hidden" name="actionID" value="special" />
<input type="hidden" name="referrer" value="<?php echo $this->h($this->referrer) ?>" />

<h1 class="header">
 <?php printf(_("New Page: %s"), $this->h($this->referrer)) ?>
</h1>

<div class="horde-content">
 <textarea class="fixed" style="width:100%" name="page_text" rows="29" cols="100"><?php echo $this->h($this->text) ?></textarea>
</div>

<div class="horde-form-buttons">
 <input type="submit" value="<?php echo _("Save") ?>" class="horde-default" />
 <input type="button" id="wicked-preview" value="<?php echo _("Preview") ?>" />
</div>

</form>
