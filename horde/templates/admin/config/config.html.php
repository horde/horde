<?php echo $this->form ?>

<?php if ($this->php): ?>
<br />
<h1 class="header">
 <?php echo _("Generated Code") ?>
<?php if ($this->diff_popup): ?>
 <small>[ <?php echo $this->diff_popup ?> ]</small>
<?php endif; ?>
</h1>
<label for="php_config" class="hidden"><?php echo _("Configuration") ?></label>
<textarea id="php_config" style="width:100%" rows="20"><?php echo $this->h($this->php) ?></textarea>
<?php endif; ?>
