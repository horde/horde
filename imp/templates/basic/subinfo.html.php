<?php if ($this->quotaText): ?>
<span class="<?php echo $this->quotaClass ?>"><?php echo $this->quotaText ?></span>
<?php endif ?>
<span id="mailboxLabel">
 <?php echo $this->label ?>
 <?php echo $this->value ?>
<?php if ($this->readonly): ?>
 <span class="iconImg readonlyImg" title="<?php echo _("Read-Only") ?>"></span>
<?php endif ?>
</span>
