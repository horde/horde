<h1 class="header">
 <?php echo _("Hashtable Administration") ?>
</h1>

<div class="horde-content">
 <p>
  <?php echo _("Driver") ?>: <strong><?php echo $this->h($this->driver) ?></strong>
 </p>
 <p>
  <?php echo _("Does the backend provide persistent storage?") ?>:
  <strong><?php echo ($this->persistent ? _("Yes") : _("No")) ?></strong>
 </p>
 <p>
  <?php echo _("Does the backend provide locking support?") ?>:
  <strong><?php echo ($this->locking ? _("Yes") : _("No")) ?></strong>
 </p>
 <p>
  <?php echo _("Backend Read/Write Test") ?>:
<?php if ($this->rw): ?>
  <strong><?php echo _("Success") ?></strong>
<?php else: ?>
  <strong class="htAdminError"><?php echo _("FAIL") ?></strong>
  (<?php echo _("Check your hashtable settings in horde/conf.php.") ?>)
<?php endif; ?>
 </p>
</div>

<?php if ($this->rw): ?>
<form name="hashtable" action="<?php echo $this->action ?>" method="post">
 <p class="horde-form-buttons">
  <input type="submit" class="horde-default" name="clearht" value="<?php echo _("Clear Hashtable") ?>"
 </p>
</form>
<?php endif; ?>
