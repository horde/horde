<table class="stats striped" cellspacing="0">
<thead>
 <tr>
  <th class="rightAlign"><?php echo _("Author") ?></th>
  <th><?php echo _("Commits") ?></th>
 </tr>
</thead>
<tbody>
<?php

$most = max($stats);
$factor = $most / 400;
foreach ($stats as $author => $commits): $width = (int)($commits / $factor); ?>
 <tr>
  <td class="rightAlign"><?php echo htmlspecialchars($author) ?>&nbsp;&nbsp;</td>
  <td><img src="<?php echo $registry->get('webroot', 'horde') ?>/services/images/pixel.php?c=bbcbff" id="statsImg" width="<?php echo $width ?>" height="10" /> (<?php echo $commits ?>)</td>
 </tr>
<?php endforeach ?>
</tbody>
</table>
