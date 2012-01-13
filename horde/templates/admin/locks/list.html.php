<h1 class="header">
  <?php echo _("Current Locks") ?>
  (<?php echo count($this->locks) ?>)
</h1>
<?php if ($this->error): ?>
<p class="headerbox"><em><?php echo $this->h($this->error) ?></em></p>
<?php elseif (count($this->locks)): ?>
<table class="headerbox sortable striped">
  <thead><tr>
    <th><?php echo _("Scope") ?></th>
    <th><?php echo _("Principal") ?></th>
    <th><?php echo _("User") ?></th>
    <th><?php echo _("Start Time") ?></th>
    <th><?php echo _("End Time") ?></th>
    <th>&nbsp;</th>
  </tr></thead>
  <tbody>
    <?php foreach ($this->locks as $lock): ?>
    <tr>
      <td><?php echo $this->h($lock['scope']) ?></td>
      <td><?php echo $this->h($lock['lock_principal']) ?></td>
      <td><?php echo $this->h($lock['lock_owner']) ?></td>
      <td><?php echo $this->h($lock['start']) ?></td>
      <td><?php echo $this->h($lock['end']) ?></td>
      <td><?php echo $lock['unlock_link'] ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
