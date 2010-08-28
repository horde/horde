<?php if ($this->hasDuplicate): ?>
<br class="clear" />
<?php endif; ?>
<br />
<?php foreach ($this->duplicates as $field => $duplicates): ?>
<h3><?php echo $this->h(sprintf(_("Duplicates of %s"), $this->attributes[$field]['label'])) ?></h3>
<table class="horde-table horde-block-links sortable">
  <thead>
    <tr>
      <th><?php echo $this->h($this->attributes[$field]['label']) ?></th>
      <th><?php echo _("Count") ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($duplicates as $value => $list): ?>
    <tr>
      <td><a href="<?php echo $this->link->add(array('type' => $field, 'dupe' => $value)) ?>"><?php echo $this->h($value) ?></a></td>
      <td><a href="<?php echo $this->link->add(array('type' => $field, 'dupe' => $value)) ?>"><?php echo count($list) ?></a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endforeach; ?>
