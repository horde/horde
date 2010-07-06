<h1 class="header">
  <?php echo _("Current Alarms") ?>
  (<?php echo count($this->alarms) ?>)
</h1>
<?php if ($this->error): ?>
<p class="headerbox"><em><?php echo $this-h($this->error) ?></em></p>
<?php else: ?>
<ul class="headerbox linedRow">
  <?php foreach ($this->alarms as $alarm): ?>
  <li>
    <?php echo $alarm['delete_link'] ?>
    <?php echo $alarm['edit_link'] ?>
  </li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>
<br />
