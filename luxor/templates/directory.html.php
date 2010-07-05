<table id="filelist" class="striped sortable horde-table" cellspacing="0">
<thead>
 <tr>
  <th class="sortdown" width="20%"><?php echo _("Name") ?></th>
  <th width="10%"><?php echo _("Size") ?></th>
  <th width="10%"><?php echo _("Date (GMT)") ?></th>
  <th width="60%"><?php echo _("Description") ?></th>
 </tr>
</thead>
<tbody>
 <? foreach ($this->files as $file): ?>
 <tr>
  <td><a href="<?php echo $file['link'] ?>"><?php echo $file['icon'] ?> <?php echo $this->escape($file['name']) ?></a></td>
  <td><?php echo $this->escape($file['filesize']) ?> <?php echo $this->escape($file['bytes']) ?></td>
  <td><?php echo $this->escape($file['modtime']) ?></td>
  <td><?php echo $file['description'] ?></td>
 </tr>
 <? endforeach ?>
</tbody>
</table>
