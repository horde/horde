<br />
<? foreach ($this->duplicates as $field => $duplicates): ?>
<p><? printf(_("Duplicates of %s"), $this->h($this->attributes[$field]['label'])) ?></p>
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
      <td><a href="<?= $this->link->add('dupe', $value) ?>"><?= $this->h($value) ?></a></td>
      <td><a href="<?= $this->link->add('dupe', $value) ?>"><?= $list->count() ?></a></td>
    </tr>
    <? endforeach; ?>
  </tbody>
</table>
<? endforeach; ?>
