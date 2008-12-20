<table class="stats striped" cellspacing="0">
<thead>
 <tr>
  <th class="rightAlign"><?php echo _("Author") ?></th>
  <th><?php echo _("Commits") ?></th>
 </tr>
</thead>
<tbody>
<?php

$most = max($author_stats);
$factor = $most / 400;
foreach ($author_stats as $author => $commits): $width = (int)($commits / $factor); ?>
 <tr>
  <td class="rightAlign"><?php echo $author ?>&nbsp;&nbsp;</td>
  <td><?php echo Horde::img('pixel.php?c=bbcbff', '', 'style="border:1px solid #000;" width="' . $width . '" height="10"', $registry->get('webroot', 'horde') . '/services/images') . ' (' . $commits . ')</td>' ?>
 </tr>
<?php endforeach ?>
</tbody>
</table>
