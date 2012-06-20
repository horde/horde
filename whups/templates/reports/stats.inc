<h1 class="header"><?php echo _("Ticket Stats") ?></h1>
<table class="item" width="100%" cellspacing="1">
 <tr class="item">
<?php foreach ($stats as $label): ?>
  <th nowrap="nowrap" align="left"><?php echo $label ?></th>
<?php endforeach; ?>
 </tr><tr class="item">
<?php
foreach ($stats as $stat => $label):
    try {
        $time = $reporter->getTime($stat, 'type_name');
?>
   <td><?php foreach ($time as $type => $type_time)  echo $type . ': ' . sprintf(_("%s days"), $type_time) . '<br />' ?></td>
<?php
    } catch (Whups_Exception $e) {
?>
   <td><?php echo $e->getMessage() ?></td>
<?php } ?>
<?php endforeach; ?>
 </tr>
</table>
