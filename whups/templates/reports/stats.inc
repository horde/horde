<h1 class="header"><?php echo _("Ticket Stats") ?></h1>
<table class="item" width="100%" cellspacing="1">
 <tr class="item">
<?php foreach ($stats as $label): ?>
  <th nowrap="nowrap" align="left"><?php echo $label ?></th>
<?php endforeach; ?>
 </tr><tr class="item">
<?php
foreach ($stats as $stat => $label):
    $time = $reporter->getTime($stat, 'type_name');
    if (is_a($time, 'PEAR_Error')):
?>
   <td><?php echo $time->getMessage() ?></td>
<?php else: ?>
   <td><?php foreach ($time as $type => $type_time)  echo $type . ': ' . sprintf(_("%s days"), $type_time) . '<br />' ?></td>
<?php endif; endforeach; ?>
 </tr>
</table>
