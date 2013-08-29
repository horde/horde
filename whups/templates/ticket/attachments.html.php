<?php if (!$this->attachments): ?>
<p class="horde-content">
 <em><?php echo _("No attachments") ?></em>
</p>
<?php else: ?>
<table class="horde-table sortable">
 <thead><tr>
  <th class="sortdown"><?php echo _("Date") ?></th>
  <th><?php echo _("Name") ?></th>
  <th class="nosort"></th>
  <th><?php echo _("User") ?></th>
  <th class="nosort"></th>
 </tr></thead>
 <tbody>
<?php foreach ($this->attachments as $attachment): ?>
  <tr>
   <td sortval="<?php echo $attachment['timestamp'] ?>"><?php echo $attachment['date'] ?></td>
   <td><?php echo $attachment['view'] ?></td>
   <td><?php echo $attachment['download'] ?></td>
   <td><?php echo $attachment['user'] ?></td>
   <td><?php echo $attachment['delete'] ?></td>
  </tr>
<?php endforeach ?>
 </tbody>
</table>
<?php endif ?>
