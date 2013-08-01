<tr class="text">
 <td nowrap="nowrap">
  <?php echo $event->getLink() ?>
 </td>
 <td class="nowrap"><?php echo htmlspecialchars($event->getLocation()) ?></td>
 <td class="nowrap"><?php echo Kronolith::statusToString($event->status) ?></td>
 <td class="nowrap"><?php echo $event->start->strftime($prefs->getValue('date_format')) . $event->start->strftime($prefs->getValue('twentyFour') ? ' %H:%M' : ' %I:%M %p') ?></td>
 <td class="nowrap"><?php echo $event->end->strftime($prefs->getValue('date_format')) . $event->end->strftime($prefs->getValue('twentyFour') ? ' %H:%M' : ' %I:%M %p') ?></td>
</tr>
