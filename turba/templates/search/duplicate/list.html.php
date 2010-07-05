<? if ($this->hasDuplicate): ?>
<br class="clear" />
<? endif; ?>
<br />
<? foreach ($this->duplicates as $field => $duplicates): ?>
<h3><?php echo $this->h(sprintf(_("Duplicates of %s"), $this->attributes[$field]['label'])) ?></h3>
<table class="horde-table horde-block-links sortable">
  <thead>
    <tr>
      <th><?php echo $this->h($this->attributes[$field]['label']) ?></th>
      <th><?php echo _("Count") ?></th>
    </tr>
  </thead>
  <tbody>
    <? foreach ($duplicates as $value => $list): ?>
    <tr>
      <td><a href="<?php echo $this->link->add(array('type' => $field, 'dupe' => $value)) ?>"><?php echo $this->h($value) ?></a></td>
      <td><a href="<?php echo $this->link->add(array('type' => $field, 'dupe' => $value)) ?>"><?php echo $list->count() ?></a></td>
    </tr>
    <? endforeach; ?>
  </tbody>
</table>
<? endforeach; ?>
