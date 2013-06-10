<form method="post" id="wicked-edit" action="<?php echo $this->action ?>">
<?php echo $this->formInput ?>
<input type="hidden" name="page" value="EditPage" />
<input type="hidden" name="actionID" value="special" />
<input type="hidden" name="referrer" value="<?php echo $this->h($this->name) ?>" />

<h1 class="header">
 <?php echo _("Edit Page") ?>: <?php echo $this->header ?>
</h1>

<p class="horde-content">
 <textarea class="fixed" style="width:100%" name="page_text" rows="29" cols="100"><?php echo $this->h($this->text) ?></textarea>
</p>

<p class="horde-content">
 <?php echo $this->changelogRequired ?>
 <strong><?php echo _("Change log") ?>: </strong><input type="text" name="changelog" size="50" />
</p>

<?php if ($this->captcha): ?>
<div class="horde-content">
 <?php echo _("Spam Protection - Enter the following letters below:") ?>
 <pre><?php echo $this->captcha ?></pre>
 <input name="wicked_captcha" />
</div>
<?php endif ?>

<div class="horde-form-buttons">
 <input type="submit" value="<?php echo _("Save") ?>" class="horde-default" />
 <input type="button" id="wicked-preview" value="<?php echo _("Preview") ?>" />
 <?php echo $this->cancel ?>
</div>

</form>
