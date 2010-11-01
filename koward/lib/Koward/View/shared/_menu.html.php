<div id="menu">
 <?php echo $this->menu->render(); ?>
</div>
     <?php if (!empty($this->current_user)): ?>
  <div id="menuBottom"><?php echo _("Current user: ") . $this->current_user; ?>
  <?php if (!empty($this->role)) {echo '| ' . _("Role: ") . $this->role;} ?>
  </div><br class="clear" />
<?php endif; ?>
<?php $this->koward->notification->notify(array('listeners' => 'status')) ?>

