<table cellspacing="3" width="100%">
 <tr>
  <td valign="top" width="50%">
   <h1 class="header">
<?php if (!is_null($prev_month_url)): ?>
   <?php echo Horde::link($prev_month_url) .
              Horde::img('nav/left.png') ?></a>
<?php endif; ?>
   <?php printf(_("View comics for %s"), strftime("%B %Y", $date)) ?>
<?php if (!is_null($next_month_url)): ?>
   <?php echo Horde::link($next_month_url) .
              Horde::img('nav/right.png') ?></a>
<?php endif; ?>
   </h1>

<?php foreach ($klutz_driver->listDates($date) as $time): ?>
   <div class="linedRow"><?php
$url = Horde::selfUrl()->add(array('actionID' => 'day', 'date' => $time));
if (in_array(date('Ymd',$time), $visited)) {
    echo Horde::link($url, strftime('%B %d, %Y', $time), 'visited');
} else {
    echo Horde::link($url, strftime('%B %d, %Y', $time));
}
echo strftime('%B %d, %Y', $time);
        ?></a></div>
<?php endforeach; ?>

  </td>
  <td valign="top" width="50%">
   <h1 class="header">
    <?php echo _("View by comic") ?>
   </h1>

<?php foreach ($comics as $index => $comic): ?>
   <div class="linedRow"><?php
$url = Horde::selfUrl()->add(array('actionID' => 'comic', 'index' => $index, 'date' => $date));
if (!in_array($index, $selected)) {
    echo Horde::link($url, $comic['name'], 'unselected');
} else {
    echo Horde::link($url, $comic['name']);
}
printf(_("%s by %s"), $comic['name'], $comic['author']);
        ?></a></div>
<?php endforeach; ?>

  </td>
 </tr>
</table>
