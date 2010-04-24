<table id="filelist" class="striped sortable horde-table" cellspacing="0">
<thead>
 <tr>
  <th class="sortdown" width="20%"><?= _("Name") ?></th>
  <th width="10%"><?= _("Size") ?></th>
  <th width="10%"><?= _("Date (GMT)") ?></th>
  <th width="60%"><?= _("Description") ?></th>
 </tr>
</thead>
<tbody>
 <? foreach ($this->files as $file): ?>
 <tr>
  <td><a href="<?= $file['link'] ?>"><?= $file['icon'] ?> <?= $this->escape($file['name']) ?></a></td>
  <td><?= $this->escape($file['filesize']) ?> <?= $this->escape($file['bytes']) ?></td>
  <td><?= $this->escape($file['modtime']) ?></td>
  <td><?= $file['description'] ?></td>
 </tr>
 <? endforeach ?>
</tbody>
</table>
