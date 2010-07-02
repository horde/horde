<? if ($this->hasDuplicate): ?>
<br class="clear" />
<? endif; ?>
<br />
<? foreach ($this->duplicates as $field => $duplicates): ?>
<h3><?= $this->h(sprintf(_("Duplicates of %s"), $this->attributes[$field]['label'])) ?></h3>
<table class="horde-table horde-block-links sortable">
  <thead>
    <tr>
      <th><?= $this->h($this->attributes[$field]['label']) ?></th>
      <th><?= _("Count") ?></th>
    </tr>
  </thead>
  <tbody>
    <? foreach ($duplicates as $value => $list): ?>
    <tr>
      <td><a href="<?= $this->link->add(array('type' => $field, 'dupe' => $value)) ?>"><?= $this->h($value) ?></a></td>
      <td><a href="<?= $this->link->add(array('type' => $field, 'dupe' => $value)) ?>"><?= $list->count() ?></a></td>
    </tr>
    <? endforeach; ?>
  </tbody>
</table>
<? endforeach; ?>
