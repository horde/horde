<h1 class="header">
 <?php echo _("Cache Administration") ?>
</h1>

<div class="horde-content">
 <p>
  <?php echo _("Driver") ?>: <strong><?php echo $this->h($this->driver) ?></strong>
 </p>
 <p>
  <?php echo _("Backend Read/Write Test") ?>:
<?php if ($this->rw): ?>
  <strong><?php echo _("Success") ?></strong>
<?php else: ?>
  <strong class="cacheAdminError"><?php echo _("FAIL") ?></strong>
  (<?php echo _("Check your cache settings in the Horde configuration.") ?>)
<?php endif; ?>
 </p>
</div>

<?php if ($this->rw): ?>
<form name="cache" action="<?php echo $this->action ?>" method="post">
 <p class="horde-form-buttons">
  <input type="submit" class="horde-default" name="clearcache" value="<?php echo _("Clear Cache") ?>"
 </p>
</form>
<?php endif; ?>
